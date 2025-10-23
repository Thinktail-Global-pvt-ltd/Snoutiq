<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Chat;
use App\Models\ChatRoom;
use Illuminate\Support\Str;

class GeminiChatController extends Controller
{
    private const UNIFIED_SESSION_TTL_MIN = 1440; // 24h
    private const GEMINI_API_KEY = 'AIzaSyCIB0yfzSQGGwpVUruqy_sd2WqujTLa1Rk';
    private const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'user_id'        => 'required|integer',
            'question'       => 'required|string',
            'context_token'  => 'nullable|string',
            'chat_room_token'=> 'nullable|string',
            'title'          => 'nullable|string',
            'pet_name'       => 'nullable|string',
            'pet_type'       => 'nullable|string',
            'pet_breed'      => 'nullable|string',
            'pet_age'        => 'nullable|string',
            'pet_location'   => 'nullable|string',
        ]);

        // session / room identity (chat_room_token no longer required in request)
        $sessionId = $data['chat_room_token'] ?? $data['context_token'] ?? null;
        if (!$sessionId) {
            $latestRoom = ChatRoom::where('user_id', $data['user_id'])
                ->orderBy('updated_at', 'desc')
                ->first();
            $sessionId = $latestRoom ? $latestRoom->chat_room_token : ('room_' . Str::uuid()->toString());
        }
        $cacheKey  = "unified:{$sessionId}";
        $state     = Cache::get($cacheKey, $this->defaultState());
        if (!isset($state['symptom_analysis_history']) || !is_array($state['symptom_analysis_history'])) {
            $state['symptom_analysis_history'] = [];
        }

        // soft reset on greeting only if history exists
        if ($this->isGreeting($data['question']) && !empty($state['conversation_history'])) {
            $this->softResetEvidence($state);
        }

        // pet profile
        $state['pet_profile'] = [
            'name'     => $data['pet_name']     ?? 'Your pet',
            'type'     => $data['pet_type']     ?? 'Pet',
            'breed'    => $data['pet_breed']    ?? 'Mixed breed',
            'age'      => $data['pet_age']      ?? 'Unknown',
            'location' => $data['pet_location'] ?? 'India',
        ];

        // score + decision
        $symptomAnalysis = $this->analyzeSymptomsWithGemini($data['question'], $state['conversation_history']);
        $state['symptom_analysis_history'][] = [
            'message'   => $data['question'],
            'analysis'  => $symptomAnalysis,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->updateScoreAndEvidence($state, $data['question'], $symptomAnalysis);
        $baseDecision = $this->makeDecision((int) $state['evidence_score']);
        $userTurnsNow = count($state['conversation_history']) + 1;
        [$decision, $backstopApplied] = $this->backstopOverride($baseDecision, $userTurnsNow);

        $symptomEscalated = false;
        if ($decision === 'GATHER_INFO' && ($symptomAnalysis['symptoms_found'] ?? false)) {
            $decision = 'VIDEO_CONSULT';
            $symptomEscalated = true;
        }

        // prompt
        $prompt = $this->buildPrompt($decision, $data['question'], $state['pet_profile'], $state['conversation_history'], $symptomAnalysis);
        $aiText = $this->callGeminiApi_curl($prompt);

        // try to extract diagnosis (only present for IN_CLINIC / VIDEO_CONSULT)
        $diagnosis = $this->parseDiagnosis($aiText);

        // keep state
        $state['service_decision'] = $decision;
        $state['conversation_history'][] = [
            'user'             => $data['question'],
            'response'         => $aiText,
            'evidence_score'   => $state['evidence_score'],
            'service_decision' => $decision,
            'timestamp'        => now()->format('H:i:s'),
        ];

        $statusText = $this->statusLine($decision, (int) $state['evidence_score'], $backstopApplied || $symptomEscalated);
        $vetSummary = $this->generateVetSummary($state);
        $html       = $this->renderBubbles($data['question'], $aiText, $decision);

        Cache::put($cacheKey, $state, now()->addMinutes(self::UNIFIED_SESSION_TTL_MIN));

        $emergencyStatus = in_array($decision, ['EMERGENCY', 'IN_CLINIC'], true) ? $decision : null;

        /** 🔥 DB SAVE START */
        $room = ChatRoom::firstOrCreate(
            ['chat_room_token' => $sessionId],
            ['user_id' => $data['user_id'], 'name' => $data['title'] ?? 'New Chat']
        );

        $chat = Chat::create([
            'user_id'         => $data['user_id'],
            'chat_room_id'    => $room->id,
            'chat_room_token' => $room->chat_room_token,
            'context_token'   => $sessionId,
            'question'        => $data['question'],
            'answer'          => $aiText,
            'diagnosis'       => $diagnosis, // <- only set when present
            'pet_name'        => $data['pet_name'] ?? null,
            'pet_breed'       => $data['pet_breed'] ?? null,
            'pet_age'         => $data['pet_age'] ?? null,
            'pet_location'    => $data['pet_location'] ?? null,
            'response_tag'    => $decision,
            'emergency_status'=> $emergencyStatus,
        ]);

        $room->last_emergency_status = $emergencyStatus;
        $room->name = $data['title'] ?? $room->name;
        $room->touch();
        $room->save();
        /** 🔥 DB SAVE END */

        return response()->json([
            'success'          => true,
            'context_token'    => $sessionId,
            'session_id'       => $sessionId,
            'chat'             => $chat,
            'chat_room_token'  => $room->chat_room_token,
            'emergency_status' => $emergencyStatus,
            'decision'         => $decision,
            'score'            => $state['evidence_score'],
            'evidence_tags'    => $state['evidence_details']['symptoms'],
            'status_text'      => $statusText,
            'vet_summary'      => $vetSummary,
            'conversation_html'=> $html,
            'symptom_analysis' => $symptomAnalysis,
        ]);
    }

    /* ---------------- legacy send (kept) ---------------- */
    public function sendMessage_old(Request $request)
    {
        $data = $request->validate([
            'user_id'        => 'required|integer',
            'question'       => 'required|string',
            'context_token'  => 'nullable|string',
            'title'          => 'nullable|string',
            'pet_name'       => 'nullable|string',
            'pet_type'       => 'nullable|string',
            'pet_breed'      => 'nullable|string',
            'pet_age'        => 'nullable|string',
            'pet_location'   => 'nullable|string',
        ]);

        $sessionId = $data['chat_room_token'] ?? $data['context_token'] ?? null;
        if (!$sessionId) {
            $latestRoom = ChatRoom::where('user_id', $data['user_id'])
                ->orderBy('updated_at', 'desc')
                ->first();
            $sessionId = $latestRoom ? $latestRoom->chat_room_token : ('room_' . Str::uuid()->toString());
        }
        $cacheKey  = "unified:{$sessionId}";
        $state     = Cache::get($cacheKey, $this->defaultState());
        if (!isset($state['symptom_analysis_history']) || !is_array($state['symptom_analysis_history'])) {
            $state['symptom_analysis_history'] = [];
        }

        if ($this->isGreeting($data['question']) && !empty($state['conversation_history'])) {
            $this->softResetEvidence($state);
        }

        $state['pet_profile'] = [
            'name'     => $data['pet_name']     ?? 'Your pet',
            'type'     => $data['pet_type']     ?? 'Pet',
            'breed'    => $data['pet_breed']    ?? 'Mixed breed',
            'age'      => $data['pet_age']      ?? 'Unknown',
            'location' => $data['pet_location'] ?? 'India',
        ];

        $symptomAnalysis = $this->analyzeSymptomsWithGemini($data['question'], $state['conversation_history']);
        $state['symptom_analysis_history'][] = [
            'message'   => $data['question'],
            'analysis'  => $symptomAnalysis,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->updateScoreAndEvidence($state, $data['question'], $symptomAnalysis);
        $baseDecision = $this->makeDecision((int) $state['evidence_score']);
        $userTurnsNow = count($state['conversation_history']) + 1;
        [$decision, $backstopApplied] = $this->backstopOverride($baseDecision, $userTurnsNow);

        $symptomEscalated = false;
        if ($decision === 'GATHER_INFO' && ($symptomAnalysis['symptoms_found'] ?? false)) {
            $decision = 'VIDEO_CONSULT';
            $symptomEscalated = true;
        }

        $prompt = $this->buildPrompt($decision, $data['question'], $state['pet_profile'], $state['conversation_history'], $symptomAnalysis);
        $aiText = $this->callGeminiApi_curl($prompt);

        $state['service_decision'] = $decision;
        $state['conversation_history'][] = [
            'user'             => $data['question'],
            'response'         => $aiText,
            'evidence_score'   => $state['evidence_score'],
            'service_decision' => $decision,
            'timestamp'        => now()->format('H:i:s'),
        ];

        $statusText = $this->statusLine($decision, (int) $state['evidence_score'], $backstopApplied || $symptomEscalated);
        $vetSummary = $this->generateVetSummary($state);
        $html       = $this->renderBubbles($data['question'], $aiText, $decision);

        Cache::put($cacheKey, $state, now()->addMinutes(self::UNIFIED_SESSION_TTL_MIN));

        $emergencyStatus = in_array($decision, ['EMERGENCY', 'IN_CLINIC'], true) ? $decision : null;

        return response()->json([
            'success'          => true,
            'context_token'    => $sessionId,
            'session_id'       => $sessionId,
            'chat'             => ['answer' => (string) ($aiText ?? 'No response.')],
            'emergency_status' => $emergencyStatus,
            'decision'         => $decision,
            'score'            => $state['evidence_score'],
            'evidence_tags'    => $state['evidence_details']['symptoms'],
            'status_text'      => $statusText,
            'vet_summary'      => $vetSummary,
            'conversation_html'=> $html,
            'symptom_analysis' => $symptomAnalysis,
        ]);
    }

    /* =================== Helpers =================== */

    private function defaultState(): array
    {
        return [
            'conversation_history' => [],
            'pet_profile'          => [],
            'evidence_score'       => 0,
            'evidence_details'     => [
                'symptoms' => [], 'severity' => [], 'environmental' => [], 'timeline' => [],
            ],
            'service_decision'     => null,
            'symptom_analysis_history' => [],
        ];
    }

    private function isGreeting(string $m): bool
    {
        $m = trim(mb_strtolower($m));
        return (bool) preg_match('/^(hi|hello|hey|hii|hlo|hola|namaste|namaskar|salaam|yo|hi there|hey there)[!. ]*$/i', $m);
    }

    private function softResetEvidence(array &$state): void
    {
        $state['evidence_score'] = 0;
        $state['evidence_details'] = [
            'symptoms' => [], 'severity' => [], 'environmental' => [], 'timeline' => [],
        ];
        $state['service_decision'] = null;
        $state['symptom_analysis_history'] = [];
    }

    private function analyzeSymptomsWithGemini(string $message, array $history = []): array
    {
        $historyTurns = [];
        if (!empty($history)) {
            foreach (array_slice($history, -3) as $turn) {
                $historyTurns[] = [
                    'user'       => $turn['user'] ?? null,
                    'assistant'  => $turn['response'] ?? null,
                    'decision'   => $turn['service_decision'] ?? null,
                    'evidence'   => $turn['evidence_score'] ?? null,
                ];
            }
        }

        $historyJson  = json_encode($historyTurns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $messageValue = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = <<<PROMPT
You are a veterinary triage signal detector. Review the latest user message about their pet and decide whether it contains specific clinical symptoms or concerns.

Conversation context (JSON array of recent turns): {$historyJson}
Latest user message: {$messageValue}

Respond with **valid JSON only** using this schema:
{
  "symptoms_found": boolean,
  "symptoms": [short symptom phrases],
  "severity": "none" | "mild" | "moderate" | "severe",
  "confidence": number between 0 and 1,
  "reason": "short explanation"
}

Rules:
- Treat greetings or vague check-ins (e.g. "hi", "hello") as no symptoms (symptoms_found=false, severity="none").
- If any concrete sign, behavior change, pain description, injury or medical issue is mentioned, set symptoms_found=true and list the key findings.
- Choose severity based on the symptom described, even if only one mild sign is present.
- Do not add any extra text outside the JSON object.
PROMPT;

        $raw = $this->callGeminiApi_curl($prompt);
        $decoded = $this->decodeGeminiJson($raw);

        $defaults = [
            'symptoms_found' => false,
            'symptoms'       => [],
            'severity'       => 'none',
            'confidence'     => 0.0,
            'reason'         => 'no symptoms detected',
        ];

        if (!is_array($decoded)) {
            return $defaults;
        }

        return [
            'symptoms_found' => (bool) ($decoded['symptoms_found'] ?? false),
            'symptoms'       => isset($decoded['symptoms']) && is_array($decoded['symptoms']) ? $decoded['symptoms'] : [],
            'severity'       => is_string($decoded['severity'] ?? null) ? strtolower($decoded['severity']) : 'none',
            'confidence'     => isset($decoded['confidence']) ? (float) $decoded['confidence'] : 0.0,
            'reason'         => is_string($decoded['reason'] ?? null) ? $decoded['reason'] : ($defaults['reason']),
        ];
    }

    private function decodeGeminiJson(string $text): ?array
    {
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function updateScoreAndEvidence(array &$state, string $message, array $analysis = []): void
    {
        $text = mb_strtolower($message);

        $buckets = [
            ['critical', 4, [
                "can't","won't","screaming","severe","extreme","emergency","urgent",
                "unable to","completely","totally","not responding","unresponsive",
                "continuously","non-stop","won't stop","all night","for hours",
                "getting worse","much worse","rapidly","suddenly",
            ]],
            ['high', 3, [
                "thick","swollen","hot","red","discharge","blood","pus",
                "drinking more","accidents in the house","unusually thirsty","urinating more","bathroom accidents",
                "open wound","deep cut","gash","laceration","very swollen",
                "not bearing weight","non weight bearing","no weight on leg",
                "refuses to stand","unable to stand","dragging leg",
            ]],
            ['high', 2, [
                "painful","hurts","yelps","cries","pulls away","won't let me",
                "won't","can't","unable","difficulty","struggling",
                "still","keeps","won't stop","always","constant",
                "whimpering","whining","tender","flinches on touch","won't use leg","won't put weight","reluctant to put weight",
            ]],
            ['mild', 1, [
                "different","off","not normal","changed","unusual",
                "little","slightly","somewhat","a bit","minor",
                "started","began","since","yesterday","today","few days",
                "sometimes","occasionally","comes and goes",
                "limp","limping","favoring the leg","favoring one leg","stiff","sore","reluctant to walk","hobble","hobbling",
            ]],
        ];

        $seenTokens = [];
        foreach ($buckets as [$label, $weight, $keywords]) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw) && !in_array($kw, $seenTokens, true)) {
                    $seenTokens[] = $kw;
                    $tag = "{$label}_{$kw}: +{$weight}";
                    if (!in_array($tag, $state['evidence_details']['symptoms'], true)) {
                        $state['evidence_details']['symptoms'][] = $tag;
                    }
                }
            }
        }

        $regexBuckets = [
            ['high', 3, [
                '/\bbleed\w*\b/', '/\bbloody?\b/', '/\bblood\s+from\b/', '/\bblood\s+in\s+(stool|urine|vomit)\b/',
            ]],
            ['high', 2, [
                '/\bvomit\w*\b|throw(?:ing)?\s+up/', '/\bdiarrh?ea\b/',
                '/\b(not\s+eating|refus(?:e|ing)\s+food|no\s+appetite|loss\s+of\s+appetite)\b/',
            ]],
        ];
        foreach ($regexBuckets as [$label, $weight, $patterns]) {
            foreach ($patterns as $rx) {
                if (preg_match($rx, $text, $m)) {
                    $hit = $m[0];
                    $tag = "{$label}_rx: +{$weight} ({$hit})";
                    $already = false;
                    foreach ($state['evidence_details']['symptoms'] as $existing) {
                        if (str_contains($existing, "({$hit})") && str_contains($existing, "+{$weight}")) {
                            $already = true; break;
                        }
                    }
                    if (!$already) $state['evidence_details']['symptoms'][] = $tag;
                }
            }
        }

        $sum = 0;
        foreach ($state['evidence_details']['symptoms'] as $detail) {
            if (preg_match('/\+\s*(\d+)/', $detail, $m)) {
                $sum += (int) $m[1];
            }
        }

        if (!empty($analysis) && ($analysis['symptoms_found'] ?? false)) {
            $severity = strtolower((string) ($analysis['severity'] ?? 'mild'));
            $severityWeights = [
                'none'     => 0,
                'mild'     => 2,
                'moderate' => 4,
                'severe'   => 7,
            ];
            $weight = $severityWeights[$severity] ?? 2;
            if ($weight === 0) {
                $weight = 2;
            }
            $summary = $analysis['symptoms'] ?? [];
            $label = !empty($summary) ? implode(', ', array_slice($summary, 0, 3)) : 'symptom noted';
            $tag = "gemini_{$severity}_{$label}: +{$weight}";
            $added = false;
            if (!in_array($tag, $state['evidence_details']['symptoms'], true)) {
                $state['evidence_details']['symptoms'][] = $tag;
                $added = true;
            }
            if ($added) {
                $state['evidence_details']['severity'][] = strtoupper($severity);
                $sum += $weight;
            }
        }

        $state['evidence_score'] = max(0, $sum);
    }

    private function makeDecision(int $score): string
    {
        if ($score >= 10) return 'EMERGENCY';
        if ($score >= 7)  return 'IN_CLINIC';
        if ($score >= 1)  return 'VIDEO_CONSULT';
        return 'GATHER_INFO';
    }

    private function backstopOverride(string $decision, int $userTurns): array
    {
        if ($userTurns > 5 && $decision === 'GATHER_INFO') {
            return ['VIDEO_CONSULT', true];
        }
        return [$decision, false];
    }

    private function buildPrompt(string $decision, string $userMessage, array $pet = [], array $history = [], array $analysis = []): string
    {
        $petLine = "PET: " . ($pet['name'] ?? 'Pet') .
                   " (" . ($pet['type'] ?? 'Pet') . ", " . ($pet['breed'] ?? 'Mixed breed') . ", " . ($pet['age'] ?? 'age unknown') . ")\n" .
                   "LOCATION: " . ($pet['location'] ?? 'India');

        if (in_array($decision, ['EMERGENCY','IN_CLINIC','VIDEO_CONSULT'], true)) {
            return "You are SnoutIQ AI, a veterinary triage assistant. Provide a direct service recommendation.\n\n" .
                   $petLine . "\n\n" .
                   'USER MESSAGE: "' . $userMessage . "\"\n\n" .
                   $this->servicePrompt($decision, $pet) .
                   "\nRespond directly as SnoutIQ AI - do not mention evidence scores or internal analysis.";
        }

        // GATHER_INFO branch
        $symptomFlag = (bool) ($analysis['symptoms_found'] ?? false);
        $symptomSummary = '';
        if ($symptomFlag) {
            $list = isset($analysis['symptoms']) && is_array($analysis['symptoms'])
                ? implode(', ', array_slice(array_filter($analysis['symptoms']), 0, 3))
                : '';
            $symptomSummary = $list !== '' ? $list : 'reported concern';
        }
        $severityHint = strtoupper((string) ($analysis['severity'] ?? 'unknown'));

        if ($symptomFlag) {
            return "You are SnoutIQ AI, a caring veterinary triage assistant.\n\n" .
                   $petLine . "\n\n" .
                   "USER MESSAGE: \"{$userMessage}\"\n\n" .
                   "Detected symptom signals: {$symptomSummary}\n" .
                   "Severity impression: {$severityHint}\n\n" .
                   "Respond with a single friendly paragraph acknowledging the pet parent, briefly interpreting what this symptom most likely indicates, and outlining immediate at-home considerations. Follow with a short bullet list (max 3 bullets) of monitoring tips. Conclude with the standard reassurance that a SnoutIQ veterinarian can guide them via video consult.\n\n" .
                   "Include the mandatory block exactly as follows at the end:\n" .
                   "=== DIAGNOSIS ===\n" .
                   "<one-line summary of likely causes tied to the symptom>\n" .
                   "=== END ===\n\n" .
                   "Do not ask any questions. Avoid question marks.";
        }

        $opening = empty($history)
            ? "Hello! I'm SnoutIQ AI, and I'm standing by to help you and your pet."
            : "Thanks for the update — I want to help further.";

        return "You are SnoutIQ AI, a caring veterinary triage assistant.\n\n" .
               $petLine . "\n\n" .
               "USER MESSAGE: \"{$userMessage}\"\n\n" .
               "{$opening}\n\n" .
               "You did not detect any clear clinical signs yet. Kindly explain (without asking questions) that specific observations are needed — encourage them to share what they see, when it started, any changes in appetite/energy, and anything else unusual. Provide a concise checklist (max 3 bullet items) of the kinds of details that help a vet. Invite them to share whenever they're ready.\n\n" .
               "Do not include questions or question marks. Keep the tone warm and professional.";
    }

    /**
     * Service copy: DIAGNOSIS block sirf IN_CLINIC / VIDEO_CONSULT me include hoga.
     */
    private function servicePrompt(string $decision, array $pet): string
    {
        $commonDiagnosisNote =
            "At the end, include a very short '=== DIAGNOSIS ===' section (1-2 lines max) with a *preliminary clinical impression* ".
            "in plain language (not confirmed). Use exactly this wrapper:\n\n".
            "=== DIAGNOSIS ===\n".
            "<one-line summary of likely causes>\n".
            "=== END ===\n";

        if ($decision === 'EMERGENCY') {
            return "🚨 EMERGENCY RECOMMENDATION:\n\n".
                   "**This is an EMERGENCY situation requiring immediate veterinary care.**\n\n".
                   "**IMMEDIATE ACTIONS:**\n• Find the nearest 24-hour emergency vet clinic\n• Call ahead to inform them you're coming\n• Leave for the emergency vet NOW\n\n".
                   "**WHILE TRAVELING:**\n• Keep your pet calm and comfortable\n• Monitor breathing and symptoms\n• Do not give food, water, or medications\n• Note any changes in condition\n\n**NO DELAYS - ACT IMMEDIATELY**";
        }

        if ($decision === 'IN_CLINIC') {
            return "🏥 IN-CLINIC APPOINTMENT RECOMMENDATION:\n\n".
                   "**Schedule an in-clinic appointment within 24–48 hours.**\n\n".
                   "**WHY IN-CLINIC IS NEEDED:**\n• Physical examination required for proper diagnosis\n• Possible diagnostics (blood work, imaging, cultures)\n• Hands-on assessment of symptoms needed\n\n".
                   "**WHAT TO EXPECT:**\n• Comprehensive exam\n• Diagnostic tests if indicated\n• Treatment plan and follow-up\n\n".
                   "**NEXT STEPS:**\n• Call your veterinarian and mention symptoms for priority booking\n• Prepare questions and recent changes in diet/behavior\n\n".
                   $commonDiagnosisNote;
        }

        // VIDEO_CONSULT
        return "💻 VIDEO CONSULTATION RECOMMENDATION:\n\n".
               "**A video consultation should be sufficient for this concern.**\n\n".
               "**WHY VIDEO IS APPROPRIATE:**\n• Symptoms can be assessed visually and behaviorally\n• No urgent physical examination required\n• Safe to monitor remotely with professional guidance\n\n".
               "**HOW TO PREPARE:**\n• Good lighting\n• Pet comfortable and accessible\n• Be ready to show areas of concern\n• List your questions\n\n".
               "**NEXT STEPS:**\n• Book a video consult through our platform\n• Follow any preparation instructions\n\n".
               $commonDiagnosisNote;
    }

    private function statusLine(string $decision, int $score, bool $backstopApplied = false): string
    {
        if (in_array($decision, ['EMERGENCY','IN_CLINIC','VIDEO_CONSULT'], true)) {
            $tag = $backstopApplied ? " • ⛳ backstop" : "";
            return "✅ {$decision} recommended{$tag} | Evidence: {$score}/10 | 📋 Summary ready for vet";
        }
        return "🔍 Evidence gathering | Score: {$score}/10";
    }

    private function getIntelligenceStatus(array $state): string
    {
        $score     = (int) ($state['evidence_score'] ?? 0);
        $decision  = $this->makeDecision($score);
        $userTurns = count($state['conversation_history']);
        [$decision, $backstop] = $this->backstopOverride($decision, $userTurns);

        $sym   = $state['evidence_details']['symptoms'] ?? ['No evidence collected yet'];
        $depth = count($state['conversation_history'] ?? []);
        $lines = implode("\n", $sym);

        return "UNIFIED INTELLIGENCE SYSTEM STATUS:\n\n".
               "🎯 EVIDENCE SCORE: {$score}/10\n".
               "🚀 SERVICE DECISION: {$decision}".($backstop ? " (backstop)" : "")."\n\n".
               "🔍 EVIDENCE BREAKDOWN:\n{$lines}\n\n".
               "💬 CONVERSATION DEPTH: {$depth} exchanges\n";
    }

    private function generateVetSummary(array $state): string
    {
        $profile = $state['pet_profile'] ?? [];
        $petName = $profile['name']  ?? 'Pet';
        $petType = $profile['type']  ?? 'Pet';
        $petBreed= $profile['breed'] ?? 'Unknown breed';
        $petAge  = $profile['age']   ?? 'Unknown age';
        $score   = (int) ($state['evidence_score'] ?? 0);

        $decisionBase = $this->makeDecision($score);
        $userTurns    = count($state['conversation_history']);
        [$finalDecision, $backstop] = $this->backstopOverride($decisionBase, $userTurns);

        $severity= $score >= 8 ? 'Emergency' : ($score >= 4 ? 'High Priority' : ($score >= 2 ? 'Standard' : 'Assessment Needed'));
        $symList = $state['evidence_details']['symptoms'] ?? [];
        $symptoms= array_map(function($s){ return ucwords(str_replace('_',' ', explode(':',$s)[0])); }, $symList);
        $symText = $symptoms ? implode(', ', $symptoms) : 'Various symptoms reported';
        $history = $state['conversation_history'] ?? [];
        $timeline= count($history) > 1 ? 'Multiple interactions' : 'Initial report';

        $recent = array_slice($history, -3);
        $ownerLines = array_map(function($h){
            $u = $h['user'] ?? '';
            return '• '.(mb_strlen($u) > 80 ? mb_substr($u,0,80).'...' : $u);
        }, $recent);

        return "🐕 PATIENT: {$petName} | {$petType} | {$petBreed} | {$petAge}\n".
               "⚠️  TRIAGE: {$severity} (Score: {$score}/10)\n".
               "🎯 SYMPTOMS: {$symText}\n".
               "⏰ TIMELINE: {$timeline}\n".
               "📍 RECOMMENDATION: {$finalDecision}".($backstop ? " (backstop)" : "")."\n\n".
               "💬 OWNER REPORTS:\n".implode("\n", $ownerLines);
    }

    private function renderBubbles(string $user, string $ai, string $decision): string
    {
        $ts = now()->format('H:i:s');
        $isReco = in_array($decision, ['EMERGENCY','IN_CLINIC','VIDEO_CONSULT'], true);
        $avatar = $decision === 'EMERGENCY' ? '🚨' : ($isReco ? '📋' : '🔍');

        $userEsc = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');
        $aiHtml  = $this->renderAiHtml($ai);

        $userHtml = "<div style='display:flex;justify-content:flex-end;margin:15px 0;'>".
            "<div style='background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:15px 20px;border-radius:20px 20px 5px 20px;max-width:70%;box-shadow:0 4px 15px rgba(102,126,234,.3);'>".
            "<div style='font-size:12px;opacity:.8;margin-bottom:5px;'>You • {$ts}</div>".
            "SnoutI<div>{$userEsc}</div>".
            "</div></div>";

        $aiHtmlBlock = "<div style='display:flex;justify-content:flex-start;margin:15px 0;'>".
            "<div style='background:white;border:2px solid #f0f0f0;padding:15px 20px;border-radius:20px 20px 20px 5px;max-width:75%;box-shadow:0 4px 15px rgba(0,0,0,.1);'>".
            "<div style='font-size:12px;color:#666;margin-bottom:8px;'>{$avatar} Q AI • {$ts}</div>".
            "<div style='line-height:1.6;color:#333;'>{$aiHtml}</div>".
            "</div></div>";

        return $userHtml.$aiHtmlBlock;
    }

    private function renderAiHtml(string $text): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $out   = [];
        $inList = false;

        $e = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $inlineBold = function ($s) use ($e) {
            return preg_replace_callback('/\*\*(.+?)\*\*/s', function ($m) use ($e) {
                return '<strong>'.$e($m[1]).'</strong>';
            }, $e($s));
        };

        foreach ($lines as $raw) {
            $l = trim($raw);

            if (preg_match('/^•\s+(.+)$/u', $l, $m)) {
                if (!$inList) { $out[] = '<ul style="margin:6px 0 6px 18px;">'; $inList = true; }
                $out[] = '<li>'.$inlineBold($m[1]).'</li>';
                continue;
            } elseif ($inList) { $out[] = '</ul>'; $inList = false; }

            if (preg_match('/^\*\*\*(.+?)\*\*\*$/s', $l, $m)) { $out[] = '<h1 style="font-size:1.15rem;margin:.25rem 0">'.$e($m[1]).'</h1>'; continue; }
            if (preg_match('/^\*\*(.+?)\*\*$/s', $l, $m))    { $out[] = '<h2 style="font-size:1.05rem;font-weight:700;margin:.25rem 0">'.$e($m[1]).'</h2>'; continue; }
            if (preg_match('/^\*(.+?)\*$/s', $l, $m))        { $out[] = '<h3 style="font-size:.98rem;font-weight:700;margin:.25rem 0">'.$e($m[1]).'</h3>'; continue; }

            if ($l === '') $out[] = '<br>'; else $out[] = '<p style="margin:.25rem 0">'.$inlineBold($l).'</p>';
        }
        if ($inList) $out[] = '</ul>';
        return implode("", $out);
    }

    private function parseDiagnosis(string $aiText): ?string
    {
        if (preg_match('/===\s*DIAGNOSIS\s*===\s*(.*?)\s*===\s*END\s*===/is', $aiText, $m)) {
            $d = trim($m[1]);
            return $d !== '' ? $d : null;
        }
        return null;
    }

    private function callGeminiApi_curl(string $prompt): string
    {
        // Use hard-coded API key as requested (no .env)
        $apiKey = self::GEMINI_API_KEY;

        $payload = json_encode([
            'contents' => [[
                'role'  => 'user',
                'parts' => [['text' => $prompt]],
            ]],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init(self::GEMINI_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-goog-api-key: '.$apiKey
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err  = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            Log::error('Gemini cURL error', ['err' => $err, 'info' => $info]);
            return "AI error: ".$err;
        }
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300) {
            Log::error('Gemini HTTP non-2xx', ['status' => $http, 'body' => $resp]);
            return "AI error: HTTP {$http}";
        }

        $json = json_decode($resp, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? "No response.";
    }

    /* ---------- Rooms ---------- */

    public function listRooms(Request $request)
    {
        $data = $request->validate(['user_id' => 'required|integer']);

        $rooms = ChatRoom::where('user_id', $data['user_id'])
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'chat_room_token', 'summary', 'created_at', 'updated_at']);

        return response()->json([
            'status' => 'success',
            'rooms'  => $rooms,
        ]);
    }
    
    public function deleteRoom(Request $request, string $chat_room_token)
{
    // ✅ Validate
    $data = $request->validate([
        'user_id' => 'required|integer',
    ]);

    // ✅ Room must belong to this user
    $room = ChatRoom::where('chat_room_token', $chat_room_token)
        ->where('user_id', $data['user_id'])
        ->first();

    if (!$room) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Room not found for this user',
        ], 404);
    }

    // ✅ Delete with transaction (delete chats first, then room)
    \DB::transaction(function () use ($room, &$deletedChats) {
        $deletedChats = Chat::where('chat_room_id', $room->id)->delete();
        $room->delete();
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Chat room deleted successfully',
        'deleted' => [
            'chat_room_id' => $room->id,
            'chat_room_token' => $room->chat_room_token,
            'chats_deleted' => $deletedChats ?? 0,
        ],
    ]);
}
    public function history(Request $request, string $chat_room_token = null)
    {
        $data = $request->validate([
            'user_id'         => 'required|integer',
            'chat_room_token' => 'nullable|string',
            'chat_room_id'    => 'nullable|integer',
            'sort'            => 'nullable|in:asc,desc',
        ]);

        $sort = $data['sort'] ?? 'asc';

        $resolvedToken = $chat_room_token ?: ($data['chat_room_token'] ?? null);
        $resolvedId    = $data['chat_room_id'] ?? null;

        if (!$resolvedToken && !$resolvedId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'chat_room_token or chat_room_id is required',
            ], 422);
        }

        if ($resolvedToken) {
            $room = ChatRoom::where('chat_room_token', $resolvedToken)
                ->where('user_id', $data['user_id'])
                ->firstOrFail();
        } else {
            $room = ChatRoom::where('id', $resolvedId)
                ->where('user_id', $data['user_id'])
                ->firstOrFail();
        }

        $rows = Chat::where('chat_room_id', $room->id)
            ->orderBy('created_at', $sort)
            ->get();

        return response()->json([
            'status' => 'success',
            'room'   => [
                'id'              => $room->id,
                'chat_room_token' => $room->chat_room_token,
                'name'            => $room->name,
            ],
            'count'  => $rows->count(),
            'chats'  => $rows,
        ]);
    }

    public function newRoom(Request $request)
    {
        $data = $request->validate([
            'user_id'      => 'required|integer',
            'title'        => 'nullable|string',
            'pet_name'     => 'nullable|string',
            'pet_breed'    => 'nullable|string',
            'pet_age'      => 'nullable|string',
            'pet_location' => 'nullable|string',
        ]);

        $chatRoomToken = 'room_' . Str::uuid()->toString();

        $room = ChatRoom::create([
            'user_id'         => (int) $data['user_id'],
            'chat_room_token' => $chatRoomToken,
            'name'            => $data['title'] ?? null,
        ]);

        // reset session state so userTurns = 0
        Cache::put("unified:{$chatRoomToken}", $this->defaultState(), now()->addMinutes(self::UNIFIED_SESSION_TTL_MIN));

        return response()->json([
            'status'           => 'success',
            'chat_room_id'     => $room->id,
            'chat_room_token'  => $room->chat_room_token,
            'context_token'    => $room->chat_room_token,
            'name'             => $room->name,
            'note'             => 'Use this chat_room_token as context_token in /api/chat/send for this room.',
        ]);
    }

    // Simple wrapper to support POST /api/summary with { user_id }
    public function summary(Request $request)
    {
        $data = $request->validate([
            'user_id'         => 'required|integer',
            // chat_room_token no longer required; optional for backward compatibility
            'chat_room_token' => 'sometimes|string',
        ]);

        $token = $data['chat_room_token'] ?? null;
        if (!$token) {
            // Resolve user's latest room; fail if none
            $room = ChatRoom::where('user_id', $data['user_id'])
                ->orderBy('updated_at', 'desc')
                ->first();

            if (!$room) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No chat room found for this user',
                ], 404);
            }
            $token = $room->chat_room_token;
        }

        // Delegate to summarizeRoom which also validates room ownership
        return $this->summarizeRoom($request, $token);
    }

    /**
     * Summarize a chat room with Gemini and save to chat_rooms.summary.
     * Request: user_id (int), path: chat_room_token (string)
     */
    public function summarizeRoom(Request $request, string $chat_room_token)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
        ]);

        // Room must belong to the user
        $room = ChatRoom::where('chat_room_token', $chat_room_token)
            ->where('user_id', $data['user_id'])
            ->first();

        if (!$room) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Room not found for this user',
            ], 404);
        }

        $chats = Chat::where('chat_room_id', $room->id)
            ->orderBy('created_at', 'asc')
            ->get(['question', 'answer', 'created_at']);

        if ($chats->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No chats to summarize',
            ], 422);
        }

        // Build a concise prompt
        $lines = [];
        foreach ($chats as $c) {
            $q = trim((string)($c->question ?? ''));
            $a = trim((string)($c->answer   ?? ''));
            if ($q !== '') $lines[] = 'User: ' . $q;
            if ($a !== '') $lines[] = 'AI: '   . $a;
        }
        // Limit prompt size if very long
        $joined = implode("\n", $lines);
        if (strlen($joined) > 16000) {
            $joined = substr($joined, -16000); // last ~16KB of conversation
        }

        $prompt = "You are a veterinary assistant. Read the following conversation between a pet owner and AI and produce a concise, clinically useful summary for a vet.\n\n" .
                  "Keep it factual, avoid duplication, and include: presenting complaint, relevant history, key symptoms, any advice already given, and suggested next steps.\n\n" .
                  "Conversation:\n" . $joined . "\n\n" .
                  "Return only the summary in clear paragraphs (no markdown).";

        $summary = $this->callGeminiApi_curl($prompt);

        $room->summary = $summary;
        $room->save();

        return response()->json([
            'status'        => 'success',
            'chat_room_id'  => $room->id,
            'chat_room_token'=> $room->chat_room_token,
            'summary'       => $summary,
            'saved'         => true,
        ]);
    }
}
