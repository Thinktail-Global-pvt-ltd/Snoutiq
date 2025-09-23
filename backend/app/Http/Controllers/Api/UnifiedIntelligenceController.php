<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UnifiedIntelligenceController extends Controller
{
    public function process(Request $r)
    {
        $data = $r->validate([
            'session_id' => 'nullable|string',
            'message'    => 'required|string',
            'image'      => 'nullable|image|max:6144', // 6MB
            'pet_name'   => 'nullable|string',
            'pet_breed'  => 'nullable|string',
            'pet_age'    => 'nullable|string',
            'pet_weight' => 'nullable|string',
            'location'   => 'nullable|string',
        ]);

        $sessionId = $data['session_id'] ?? bin2hex(random_bytes(16));
        $cacheKey  = "unified:{$sessionId}";

        $state = Cache::get($cacheKey, $this->defaultState());

        // profile
        $state['pet_profile'] = [
            'name'     => $data['pet_name']   ?? 'Your pet',
            'breed'    => $data['pet_breed']  ?? 'Mixed breed',
            'age'      => $data['pet_age']    ?? 'Unknown',
            'weight'   => $data['pet_weight'] ?? 'Unknown',
            'location' => $data['location']   ?? 'India',
        ];

        // image path (temp)
        $imagePath = $r->hasFile('image') ? $r->file('image')->getRealPath() : null;

        // a) score
        $this->updateScoreAndEvidence($state, $data['message']);

        // b) decision (with backstop override based on number of user turns)
        $baseDecision = $this->makeDecision((int)$state['evidence_score']);
        $userTurnsNow = count($state['conversation_history']) + 1; // include this incoming message
        [$decision, $backstopApplied] = $this->backstopOverride($baseDecision, $userTurnsNow);

        // c) prompt
        $prompt = $this->buildPrompt($decision, $data['message'], $state['pet_profile'], $state['conversation_history']);

        // d) call AI via pure cURL (no deps)
        $aiText = $this->callGeminiApi_curl($prompt, $imagePath);

        // e) history
        $state['service_decision'] = $decision;
        $state['conversation_history'][] = [
            'user'             => $data['message'],
            'response'         => $aiText,
            'evidence_score'   => $state['evidence_score'],
            'service_decision' => $decision,
            'timestamp'        => now()->format('H:i:s'),
        ];

        // f) display helpers
        $statusText = $this->statusLine($decision, (int)$state['evidence_score'], $backstopApplied);
        $vetSummary = $this->generateVetSummary($state);
        $html       = $this->renderBubbles($data['message'], $aiText, $decision);

        // save
        $ttl = (int) env('UNIFIED_SESSION_TTL_MIN', 1440);
        Cache::put($cacheKey, $state, now()->addMinutes($ttl));

        return response()->json([
            'success'           => true,
            'session_id'        => $sessionId,
            'ai_text'           => $aiText,
            'decision'          => $decision,
            'score'             => $state['evidence_score'],
            'evidence_tags'     => $state['evidence_details']['symptoms'],
            'status_text'       => $statusText,
            'vet_summary'       => $vetSummary,
            'conversation_html' => $html,
        ]);
    }

    public function status(Request $r)
    {
        $r->validate(['session_id' => 'required|string']);
        $state = Cache::get("unified:".$r->session_id);
        if (!$state) {
            return response()->json(['success' => false, 'message' => 'No session found'], 404);
        }

        // compute current decision with backstop for consistency
        $score     = (int)($state['evidence_score'] ?? 0);
        $decision  = $this->makeDecision($score);
        $userTurns = count($state['conversation_history']);
        [$decision, $backstopApplied] = $this->backstopOverride($decision, $userTurns);

        return response()->json([
            'success'     => true,
            'status'      => $this->getIntelligenceStatus($state),
            'vet_summary' => $this->generateVetSummary($state),
            'decision'    => $decision,
            'score'       => $score,
            'backstop'    => $backstopApplied,
        ]);
    }

    public function reset(Request $r)
    {
        $r->validate(['session_id' => 'required|string']);
        Cache::forget("unified:".$r->session_id);
        return response()->json(['success' => true, 'message' => 'Session reset']);
    }

    /* ----------------- Core Logic (no DI, facades OK) ----------------- */

    private function defaultState(): array
    {
        return [
            'conversation_history' => [],
            'pet_profile' => [],
            'evidence_score' => 0,
            'evidence_details' => [
                'symptoms' => [],
                'severity' => [],
                'environmental' => [],
                'timeline' => [],
            ],
            'service_decision' => null,
        ];
    }

    private function updateScoreAndEvidence(array &$state, string $message): void
    {
        $text = mb_strtolower($message);

        // precedence buckets: critical > high > mild
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
            ]],
            ['high', 2, [
                "painful","hurts","yelps","cries","pulls away","won't let me",
                "won't","can't","unable","difficulty","struggling",
                "still","keeps","won't stop","always","constant",
            ]],
            ['mild', 1, [
                "different","off","not normal","changed","unusual",
                "little","slightly","somewhat","a bit","minor",
                "started","began","since","yesterday","today","few days",
                "sometimes","occasionally","comes and goes",
            ]],

            // ---- optional extra tokens (help users hit threshold faster) ----
            ['high', 3, [
                'not bearing weight','non weight bearing','no weight on leg',
                'refuses to stand','unable to stand','dragging leg',
                'leg looks very swollen','open wound','bleeding a lot'
            ]],
            ['high', 2, [
                'whimpering','whining','yelp','yelps','tender',
                'flinches on touch',"won't use leg","won't put weight","reluctant to put weight"
            ]],
            ['mild', 1, [
                'limp','limping','favoring the leg','favoring one leg',
                'stiff','sore','reluctant to walk','hobble','hobbling'
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

        // sum
        $sum = 0;
        foreach ($state['evidence_details']['symptoms'] as $detail) {
            if (preg_match('/\+\s*(\d+)/', $detail, $m)) {
                $sum += (int) $m[1];
            }
        }
        $state['evidence_score'] = max(0, $sum);
    }

    private function makeDecision(int $score): string
    {
        if ($score >= 8) return 'EMERGENCY';
        if ($score >= 4) return 'IN_CLINIC';
        if ($score >= 2) return 'VIDEO_CONSULT';
        return 'GATHER_INFO';
    }

    /**
     * Backstop rule:
     * If user has sent > 5 messages (turns), and we're still at GATHER_INFO,
     * force VIDEO_CONSULT to avoid loops.
     *
     * @return array{0:string,1:bool} [decision, applied?]
     */
    private function backstopOverride(string $decision, int $userTurns): array
    {
        if ($userTurns > 5 && $decision === 'GATHER_INFO') {
            return ['VIDEO_CONSULT', true];
        }
        return [$decision, false];
    }

    private function buildPrompt(string $decision, string $userMessage, array $pet, array $history): string
    {
        $petLine = "PET: ".($pet['name'] ?? 'Pet').
                   " (".($pet['breed'] ?? 'Mixed breed').", ".($pet['age'] ?? 'age unknown').")\n".
                   "LOCATION: ".($pet['location'] ?? 'India');

        if (in_array($decision, ['EMERGENCY','IN_CLINIC','VIDEO_CONSULT'], true)) {
            return "You are PetPal AI, a veterinary triage assistant. Based on the evidence, provide a direct service recommendation.\n\n".
                   $petLine."\n\n".
                   'USER MESSAGE: "'.$userMessage."\"\n\n".
                   $this->servicePrompt($decision).
                   "\nRespond directly as PetPal AI - do not mention evidence scores or internal analysis.";
        }

        $intro = empty($history)
            ? "Hello! I'm PetPal AI, and I'm here to help you and your pet. I understand how worrying it can be when our furry family members aren't feeling well."
            : "Thank you for that information.";

        return "You are PetPal AI, a caring veterinary triage assistant.\n\n".
               $petLine."\n\n".
               'USER MESSAGE: "'.$userMessage."\"\n\n".
               "Start with: \"$intro\"\n\n".
               "Then ask 2-3 caring questions about:\n".
               "1. How severe the symptoms seem (mild vs concerning)\n".
               "2. Any other changes they've noticed\n".
               "3. How it's affecting their pet's daily routine\n\n".
               "Be warm, professional, and genuinely helpful.";
    }

    private function servicePrompt(string $decision): string
    {
        if ($decision === 'EMERGENCY') {
            return "🚨 EMERGENCY RECOMMENDATION:\n\n".
                   "**This is an EMERGENCY situation requiring immediate veterinary care.**\n\n".
                   "**IMMEDIATE ACTIONS:**\n• Find the nearest 24-hour emergency vet clinic\n• Call ahead to inform them you're coming\n• Leave for the emergency vet NOW\n\n".
                   "**WHILE TRAVELING:**\n• Keep your pet calm and comfortable\n• Monitor breathing and symptoms\n• Do not give food, water, or medications\n• Note any changes in condition\n\n**NO DELAYS - ACT IMMEDIATELY**";
        }
        if ($decision === 'IN_CLINIC') {
            return "🏥 IN-CLINIC APPOINTMENT RECOMMENDATION:\n\n".
                   "**Schedule an in-clinic appointment within 24-48 hours.**\n\n".
                   "**WHY IN-CLINIC IS NEEDED:**\n• Physical examination required for proper diagnosis\n• Potential diagnostic tests (blood work, imaging, cultures)\n• Hands-on assessment of symptoms needed\n\n".
                   "**WHAT TO EXPECT:**\n• Comprehensive physical examination\n• Diagnostic tests if indicated\n• Treatment plan based on findings\n• Follow-up care instructions\n\n".
                   "**NEXT STEPS:**\n• Call your veterinarian to schedule appointment\n• Mention symptoms when booking for priority scheduling\n• Prepare list of questions for the vet visit";
        }
        // VIDEO
        return "💻 VIDEO CONSULTATION RECOMMENDATION:\n\n".
               "**A video consultation should be sufficient for this concern.**\n\n".
               "**WHY VIDEO IS APPROPRIATE:**\n• Symptoms can be assessed visually and behaviorally\n• No urgent physical examination required\n• Safe to monitor remotely with professional guidance\n\n".
               "**HOW TO PREPARE:**\n• Ensure good lighting in the consultation area\n• Have your pet comfortable and accessible\n• Prepare to show specific areas of concern to the camera\n• List any questions about care and treatment\n\n".
               "**NEXT STEPS:**\n• Schedule a video consultation through our platform\n• Follow any preparation instructions provided\n• Be ready to implement recommended care plans";
    }

    private function statusLine(string $decision, int $score, bool $backstopApplied = false): string
    {
        if (in_array($decision, ['EMERGENCY','IN_CLINIC','VIDEO_CONSULT'], true)) {
            $tag = $backstopApplied ? " • ⛳ backstop" : "";
            return "✅ {$decision} recommended{$tag} | Evidence: {$score}/10 | 📋 Summary ready for vet";
        }
        return "🔍 Evidence gathering | Score: {$score}/10 | Need: ".max(0, 2-$score)." more for decision";
    }

    private function getIntelligenceStatus(array $state): string
    {
        $score    = (int) ($state['evidence_score'] ?? 0);
        $decision = $this->makeDecision($score);
        $userTurns= count($state['conversation_history']);
        [$decision, $backstop] = $this->backstopOverride($decision, $userTurns);

        $sym   = $state['evidence_details']['symptoms'] ?? ['No evidence collected yet'];
        $depth = count($state['conversation_history'] ?? []);
        $lines = implode("\n", $sym);

        return "UNIFIED INTELLIGENCE SYSTEM STATUS:\n\n".
               "🎯 EVIDENCE SCORE: {$score}/10\n".
               "🚀 SERVICE DECISION: {$decision}".($backstop ? " (backstop)" : "")."\n".
               "📊 DECISION THRESHOLD: ".($score >= 2 ? "✅ REACHED" : "Need ".max(0, 2-$score)." more points")."\n\n".
               "🔍 EVIDENCE BREAKDOWN:\n{$lines}\n\n".
               "💬 CONVERSATION DEPTH: {$depth} exchanges\n\n".
               "🚪 DECISION GATES:\n".
               "• 🚨 Emergency: 8+ points → Immediate care\n".
               "• 🏥 In-Clinic: 4+ points → Physical examination needed\n".
               "• 💻 Video Consult: 2-3 points → Remote assessment sufficient\n".
               "• 🔍 Gather Info: 0-1 points → Need more evidence\n\n".
               "🔄 CURRENT STATUS: ".($score >= 2 || $backstop ? "🚨 PROVIDING SERVICE RECOMMENDATION" : "🔍 GATHERING EVIDENCE");
    }

    private function generateVetSummary(array $state): string
    {
        $profile = $state['pet_profile'] ?? [];
        $petName = $profile['name']  ?? 'Pet';
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

        return "🐕 PATIENT: {$petName} | {$petBreed} | {$petAge}\n".
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
        $aiEsc   = nl2br(htmlspecialchars($ai, ENT_QUOTES, 'UTF-8'));

        $userHtml = "<div style='display:flex;justify-content:flex-end;margin:15px 0;'>".
            "<div style='background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:15px 20px;border-radius:20px 20px 5px 20px;max-width:70%;box-shadow:0 4px 15px rgba(102,126,234,.3);'>".
            "<div style='font-size:12px;opacity:.8;margin-bottom:5px;'>You • {$ts}</div>".
            "<div>{$userEsc}</div>".
            "</div></div>";

        $aiHtml = "<div style='display:flex;justify-content:flex-start;margin:15px 0;'>".
            "<div style='background:white;border:2px solid #f0f0f0;padding:15px 20px;border-radius:20px 20px 20px 5px;max-width:75%;box-shadow:0 4px 15px rgba(0,0,0,.1);'>".
            "<div style='font-size:12px;color:#666;margin-bottom:8px;'>{$avatar} PetPal AI • {$ts}</div>".
            "<div style='line-height:1.6;color:#333;'>{$aiEsc}</div>".
            "</div></div>";

        return $userHtml.$aiHtml;
    }

    /* ----------------- cURL HTTP (no Guzzle, no deps) ----------------- */

    private function callGeminiApi_curl(string $prompt, ?string $imagePath): string
    {
        // 🔴 Hard-coded creds (TEMP USE ONLY)
        $apiKey = 'AIzaSyCIB0yfzSQGGwpVUruqy_sd2WqujTLa1Rk';
        $url    = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

        $parts = [['text' => $prompt]];

        if ($imagePath && is_readable($imagePath)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($imagePath) : 'application/octet-stream';
            $data = base64_encode(file_get_contents($imagePath));
            $parts[] = ['inline_data' => ['mime_type' => $mime, 'data' => $data]];
        }

        $payload = json_encode(['contents' => [[ 'role' => 'user', 'parts' => $parts ]]], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-goog-api-key: '.$apiKey,
            ],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_CAINFO         => '/etc/ssl/certs/ca-certificates.crt',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            Log::error('Gemini cURL error', ['err'=>$err, 'info'=>$info]);
            return "AI error: ".$err;
        }
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300) {
            Log::error('Gemini HTTP non-2xx', ['status'=>$http, 'body'=>$resp]);
            return "AI error: HTTP {$http}";
        }

        $json = json_decode($resp, true);
        return $json['candidates'][0]['content']['parts'][0]['text']
            ?? "No response.";
    }
}
