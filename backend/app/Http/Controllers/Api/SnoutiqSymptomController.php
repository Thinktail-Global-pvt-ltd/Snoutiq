<?php

/**
 * ============================================================
 * SnoutiqSymptomController.php
 * ============================================================
 * INTEGRATION INSTRUCTIONS FOR DEV
 * ============================================================
 *
 * 1. PLACE THIS FILE AT:
 *    app/Http/Controllers/Api/SnoutiqSymptomController.php
 *
 * 2. REGISTER ROUTES in routes/api.php:
 *    use App\Http\Controllers\Api\SnoutiqSymptomController;
 *    Route::post('/symptom-check',  [SnoutiqSymptomController::class, 'check']);
 *    Route::post('/symptom-followup', [SnoutiqSymptomController::class, 'followup']);
 *    Route::get('/symptom-session/{session_id}', [SnoutiqSymptomController::class, 'session']);
 *
 * 3. ENV VARIABLE REQUIRED in .env:
 *    GEMINI_API_KEY=your_key_here
 *    GEMINI_MODEL=gemini-2.0-flash
 *
 * 4. CACHE DRIVER: Redis or database recommended (not array).
 *    In .env: CACHE_DRIVER=redis
 *    Sessions expire after 24h automatically.
 *
 * 5. WHAT THIS FILE NEEDS FROM YOUR DATABASE:
 *    Pets table columns used (all nullable except id):
 *      - id, user_id, name, breed, pet_age (string), pet_gender
 *      - dob (date YYYY-MM-DD) ← ADD THIS COLUMN if missing
 *      - neutered (enum: yes/no/unknown) ← ADD THIS COLUMN if missing
 *      - species (string: dog/cat/rabbit/bird/reptile) ← ADD if missing
 *      - location (string) ← ADD if missing
 *
 *    Chat / ChatRoom models: reuse your existing ones.
 *    This controller saves to Chat + ChatRoom same as before.
 *
 * 6. WHAT THE FRONTEND RECEIVES (clean, predictable JSON):
 *    See OUTPUT SCHEMA comment below at the check() method.
 *
 * 7. DEEPLINKS — update these constants to match your app:
 *    DEEPLINK_EMERGENCY, DEEPLINK_VIDEO, DEEPLINK_CLINIC, DEEPLINK_MONITOR
 *
 * 8. TEXT LIMITS — tuned to keep Gemini costs low:
 *    - User input capped at 600 chars
 *    - History passed to Gemini: last 3 turns only
 *    - Gemini response capped at 400 tokens (response) + 800 tokens (triage)
 *    - Prompt size guard: if total prompt > 3000 chars, history is trimmed
 * ============================================================
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SnoutiqSymptomController extends Controller
{
    // ── App deeplinks — update to match your routing ──────────────────────────
    private const DEEPLINK_EMERGENCY = 'snoutiq://emergency';
    private const DEEPLINK_VIDEO     = 'snoutiq://video-consult';
    private const DEEPLINK_CLINIC    = 'snoutiq://find-clinic';
    private const DEEPLINK_MONITOR   = 'snoutiq://monitor-guide';
    private const DEEPLINK_GOVT      = 'snoutiq://govt-hospitals';

    // ── Session / cost controls ───────────────────────────────────────────────
    private const SESSION_TTL_MINUTES  = 1440;   // 24h
    private const MAX_INPUT_CHARS      = 600;    // cap user message
    private const MAX_HISTORY_TURNS    = 3;      // turns sent to Gemini
    private const MAX_PROMPT_CHARS     = 3000;   // total prompt size guard
    private const TRIAGE_MAX_TOKENS    = 800;
    private const RESPONSE_MAX_TOKENS  = 400;

    // ── Gemini config ─────────────────────────────────────────────────────────
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->model  = env('GEMINI_MODEL', 'gemini-2.0-flash');
    }

    // =========================================================================
    // PUBLIC ENDPOINTS
    // =========================================================================

    /**
     * POST /api/symptom-check
     *
     * REQUIRED INPUT:
     * {
     *   "user_id":    123,           (int, required)
     *   "pet_id":     456,           (int, required — fetches pet from DB)
     *   "message":    "Bruno is vomiting...", (string, required, max 600 chars)
     *
     *   // Optional overrides (if not in DB or user wants to send fresh):
     *   "pet_name":    "Bruno",
     *   "species":     "dog",        // dog|cat|rabbit|bird|reptile
     *   "breed":       "Labrador",
     *   "dob":         "2021-06-15", // YYYY-MM-DD — used to calculate age
     *   "sex":         "male",       // male|female|unknown
     *   "neutered":    "no",         // yes|no|unknown
     *   "location":    "Gurgaon, Delhi NCR",
     *
     *   // Session — send back the session_id from previous response to continue chat
     *   "session_id":  "room_abc123", // nullable — auto-created on first message
     *
     *   // Image — optional, base64 encoded
     *   "image_base64": "...",
     *   "image_mime":   "image/jpeg"
     * }
     *
     * OUTPUT SCHEMA:
     * {
     *   "success": true,
     *   "session_id": "room_abc123",       // send this back on next message
     *   "routing": "emergency|video_consult|in_clinic|monitor",
     *   "severity": "critical|moderate|mild|informational",
     *   "turn": 1,                         // conversation turn number
     *   "score": 6,                        // evidence score 0-10
     *   "response": {
     *     "message": "Main message to pet parent (plain text, no markdown)",
     *     "do_now": "One immediate action they can take",
     *     "time_sensitivity": "Go now / Within 2 hours / If no better in 24h",
     *     "what_to_watch": ["sign1", "sign2", "sign3"]
     *   },
     *   "buttons": {
     *     "primary": {
     *       "label": "Start Video Consult",
     *       "type": "video_consult",
     *       "deeplink": "snoutiq://video-consult",
     *       "color": "#3b82f6",
     *       "icon": "video"
     *     },
     *     "secondary": {
     *       "label": "Find Nearby Vet",
     *       "type": "clinic",
     *       "deeplink": "snoutiq://find-clinic",
     *       "color": "#8b5cf6",
     *       "icon": "map-pin"
     *     }
     *   },
     *   "triage_detail": {
     *     "possible_causes": ["cause1", "cause2"],
     *     "red_flags_found": [],
     *     "india_context": "Monsoon season increases leptospirosis risk...",
     *     "safe_to_wait_hours": 4,
     *     "image_observation": "Skin shows redness and hair loss..."  // only if image sent
     *   },
     *   "vet_summary": "Patient: Bruno, Labrador, 2yr male...", // for vet to read
     *   "red_flag_bypass": false  // true if emergency detected before AI call
     * }
     */
    public function check(Request $request): \Illuminate\Http\JsonResponse
    {
        // ── Validate ──────────────────────────────────────────────────────────
        $data = $request->validate([
            'user_id'      => 'nullable|integer',
            'pet_id'       => 'nullable|integer',
            'message'      => 'required|string|max:600',
            'pet_name'     => 'nullable|string|max:50',
            'species'      => 'nullable|string|max:20',
            'breed'        => 'nullable|string|max:80',
            'dob'          => 'nullable|date_format:Y-m-d',
            'sex'          => 'nullable|string|max:10',
            'neutered'     => 'nullable|string|max:10',
            'location'     => 'nullable|string|max:100',
            'session_id'   => 'nullable|string|max:100',
            'image_base64' => 'nullable|string',
            'image_mime'   => 'nullable|string|max:20',
        ]);

        // ── Trim input to cost limit ──────────────────────────────────────────
        $message = mb_substr(trim($data['message']), 0, self::MAX_INPUT_CHARS);

        // ── Load pet from DB, merge with request overrides ────────────────────
        $pet = $this->loadPetProfile($data);
        if (isset($pet['error'])) {
            return response()->json(['success' => false, 'message' => $pet['error']], 404);
        }

        // ── Session state ─────────────────────────────────────────────────────
        $sessionId = $this->resolveSessionId($request, $data);
        $cacheKey = "snoutiq_symptom:{$sessionId}";
        $state    = Cache::get($cacheKey, $this->defaultState());

        // ── Soft reset on greeting ────────────────────────────────────────────
        if ($this->isGreeting($message) && !empty($state['history'])) {
            $this->softReset($state);
        }

        $state['pet'] = $pet;
        $turn         = count($state['history']) + 1;

        // ── LAYER 1: Hardcoded red flag check — NO AI NEEDED ─────────────────
        [$isRedFlag, $flagPhrase] = $this->checkRedFlags($message, $pet);

        if ($isRedFlag) {
            $routing  = 'emergency';
            $triage   = $this->emergencyTriageData($flagPhrase, $pet);
            $response = $this->emergencyResponse($pet['name'], $flagPhrase);
            $score    = 10;

            $this->appendHistory($state, $message, $response['message'], $routing, $score);
            Cache::put($cacheKey, $state, now()->addMinutes(self::SESSION_TTL_MINUTES));
            $this->saveToDb($data, $sessionId, $message, $response['message'], $routing, 'emergency');

            return response()->json([
                'success'         => true,
                'session_id'      => $sessionId,
                'routing'         => $routing,
                'severity'        => 'critical',
                'turn'            => $turn,
                'score'           => $score,
                'response'        => $response,
                'buttons'         => $this->buttons($routing),
                'triage_detail'   => $triage,
                'vet_summary'     => $this->vetSummary($state, $score, $routing),
                'red_flag_bypass' => true,
            ]);
        }

        // ── LAYER 2: Evidence scoring (keyword heuristics, fast, no API call) ─
        $this->scoreEvidence($state, $message);
        $score       = min(10, (int) $state['score']);
        $baseRouting = $this->routingFromScore($score, $turn);

        // ── LAYER 3: Gemini — Triage call (structured JSON) ───────────────────
        $imageB64  = $data['image_base64'] ?? null;
        $imageMime = $data['image_mime']   ?? 'image/jpeg';

        $triageJson = $this->callGeminiTriage($pet, $message, $state['history'], $imageB64, $imageMime, $score);

        // Gemini routing can upgrade but not downgrade the heuristic decision
        $routing = $this->mergeRouting($baseRouting, $triageJson['routing'] ?? $baseRouting);

        // Force emergency if severity is critical
        if (($triageJson['severity'] ?? '') === 'critical') {
            $routing = 'emergency';
        }

        // ── LAYER 4: Gemini — Response writer (warm, plain language) ──────────
        $response = $this->callGeminiResponse($pet, $message, $routing, $triageJson, $state['history'] ?? []);

        // ── Update state ──────────────────────────────────────────────────────
        $this->appendHistory($state, $message, $response['message'] ?? '', $routing, $score);
        Cache::put($cacheKey, $state, now()->addMinutes(self::SESSION_TTL_MINUTES));

        // ── Save to DB ────────────────────────────────────────────────────────
        $severity = $triageJson['severity'] ?? 'mild';
        $this->saveToDb($data, $sessionId, $message, $response['message'] ?? '', $routing, $severity);

        return response()->json([
            'success'         => true,
            'session_id'      => $sessionId,
            'routing'         => $routing,
            'severity'        => $severity,
            'turn'            => $turn,
            'score'           => $score,
            'response'        => $response,
            'buttons'         => $this->buttons($routing),
            'triage_detail'   => [
                'possible_causes'   => $triageJson['possible_causes']   ?? [],
                'red_flags_found'   => $triageJson['red_flags_present'] ?? [],
                'india_context'     => $triageJson['india_context_note'] ?? '',
                'safe_to_wait_hours'=> $triageJson['safe_to_wait_hours'] ?? 0,
                'image_observation' => $triageJson['image_observation']  ?? '',
            ],
            'vet_summary'     => $this->vetSummary($state, $score, $routing),
            'red_flag_bypass' => false,
        ]);
    }

    /**
     * POST /api/symptom-followup
     * Continues an existing session. Simpler input — just session + new message.
     *
     * INPUT: { "user_id": 123, "session_id": "room_abc", "message": "he is still vomiting" }
     * OUTPUT: same schema as check()
     */
    public function followup(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'nullable|integer',
            'session_id' => 'nullable|string|max:100',
            'message'    => 'required|string|max:600',
        ]);

        $sessionId = $this->resolveExistingSessionId($request, $data['session_id'] ?? null);
        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'session_id is required to continue this conversation.',
            ], 422);
        }

        // Reuse check() — session_id passed in so it continues the conversation
        $request->merge([
            'pet_id' => 0,
            'session_id' => $sessionId,
        ]); // pet loaded from cached session
        return $this->check($request);
    }

    /**
     * POST /api/chat/send (compat mode)
     * Accepts legacy payload from old GeminiChatController sendMessage endpoint.
     */
    public function chatSend(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->validate([
            'user_id'        => 'nullable|integer',
            'question'       => 'nullable|string|max:600|required_without:message',
            'message'        => 'nullable|string|max:600|required_without:question',
            'pet_id'         => 'nullable|integer',
            'context_token'  => 'nullable|string|max:100',
            'chat_room_token'=> 'nullable|string|max:100',
            'session_id'     => 'nullable|string|max:100',
            'pet_name'       => 'nullable|string|max:50',
            'pet_type'       => 'nullable|string|max:20',
            'species'        => 'nullable|string|max:20',
            'pet_breed'      => 'nullable|string|max:80',
            'breed'          => 'nullable|string|max:80',
            'pet_age'        => 'nullable|string|max:30',
            'sex'            => 'nullable|string|max:10',
            'neutered'       => 'nullable|string|max:10',
            'pet_location'   => 'nullable|string|max:100',
            'location'       => 'nullable|string|max:100',
            'image_base64'   => 'nullable|string',
            'image_mime'     => 'nullable|string|max:20',
        ]);

        $sessionId = $payload['session_id']
            ?? $payload['chat_room_token']
            ?? $payload['context_token']
            ?? null;
        $promptText = (string) ($payload['question'] ?? $payload['message'] ?? '');

        $internal = Request::create('/api/symptom-check', 'POST', [
            'user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            'pet_id' => isset($payload['pet_id']) ? (int) $payload['pet_id'] : 0,
            'message' => $promptText,
            'session_id' => $sessionId,
            'pet_name' => $payload['pet_name'] ?? null,
            'species' => $payload['species'] ?? ($payload['pet_type'] ?? null),
            'breed' => $payload['breed'] ?? ($payload['pet_breed'] ?? null),
            'sex' => $payload['sex'] ?? null,
            'neutered' => $payload['neutered'] ?? null,
            'location' => $payload['location'] ?? ($payload['pet_location'] ?? null),
            'image_base64' => $payload['image_base64'] ?? null,
            'image_mime' => $payload['image_mime'] ?? null,
        ]);

        $response = $this->check($internal);
        $rawBody = json_decode($response->getContent(), true);
        if (!is_array($rawBody)) {
            return $response;
        }

        $routing = (string) ($rawBody['routing'] ?? 'video_consult');
        $score = (int) ($rawBody['score'] ?? 0);
        $session = (string) ($rawBody['session_id'] ?? $sessionId ?? '');

        $assistantMessage = (string) ($rawBody['response']['message'] ?? '');

        $compatBody = $rawBody;
        $compatBody['context_token'] = $session;
        $compatBody['chat_room_token'] = $session;
        $compatBody['message'] = $assistantMessage;
        $compatBody['chat'] = [
            'question' => $promptText,
            'answer' => $assistantMessage,
        ];
        $compatBody['diagnosis_summary'] = (string) ($rawBody['response']['diagnosis_summary'] ?? '');
        $compatBody['decision'] = $routing;
        $compatBody['emergency_status'] = in_array($routing, ['emergency', 'in_clinic'], true)
            ? strtoupper($routing)
            : null;
        $compatBody['status_text'] = sprintf('Triage: %s (%d/10)', strtoupper($routing), $score);
        $compatBody['evidence_tags'] = is_array($rawBody['triage_detail']['red_flags_found'] ?? null)
            ? $rawBody['triage_detail']['red_flags_found']
            : [];
        $compatBody['symptom_analysis'] = [
            'routing' => $routing,
            'severity' => $rawBody['severity'] ?? null,
            'possible_causes' => $rawBody['triage_detail']['possible_causes'] ?? [],
            'red_flags_present' => $rawBody['triage_detail']['red_flags_found'] ?? [],
            'india_context_note' => $rawBody['triage_detail']['india_context'] ?? '',
            'diagnosis_summary' => (string) ($rawBody['response']['diagnosis_summary'] ?? ''),
        ];
        $compatBody['conversation_html'] = sprintf(
            '<div class="chat-bubble user">%s</div><div class="chat-bubble ai">%s</div>',
            e($promptText),
            e($assistantMessage)
        );

        return response()->json($compatBody, $response->getStatusCode());
    }

    /**
     * POST /api/symptom-answer
     * Re-runs triage using a structured answer to a follow-up question.
     */
    public function answer(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'nullable|integer',
            'session_id' => 'nullable|string|max:100',
            'question'   => 'required|string|max:300',
            'answer'     => 'required|string|max:200',
        ]);

        $sessionId = $this->resolveExistingSessionId($request, $data['session_id'] ?? null);
        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'session_id is required to answer a follow-up question.',
            ], 422);
        }

        $message = sprintf(
            'Follow-up answer. You asked: "%s". My answer: %s',
            trim((string) $data['question']),
            trim((string) $data['answer'])
        );

        $internal = Request::create('/api/symptom-check', 'POST', [
            'user_id' => $data['user_id'] ?? null,
            'pet_id' => 0,
            'message' => $message,
            'session_id' => $sessionId,
        ]);

        $response = $this->check($internal);
        $payload = json_decode($response->getContent(), true);

        if (is_array($payload)) {
            $payload['revised_assessment'] = true;
            return response()->json($payload, $response->getStatusCode());
        }

        return $response;
    }

    /**
     * GET /api/symptom-session/{session_id}
     * Returns full session state — useful for debugging or resuming UI.
     */
    public function session(Request $request, string $session_id): \Illuminate\Http\JsonResponse
    {
        $state = Cache::get("snoutiq_symptom:{$session_id}");
        if (!$state) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }
        return response()->json(['success' => true, 'session_id' => $session_id, 'state' => $state]);
    }

    /**
     * POST /api/symptom-session/{session_id}/reset
     * Clears cached triage state for the provided session id.
     */
    public function resetSession(Request $request, string $session_id): \Illuminate\Http\JsonResponse
    {
        Cache::forget("snoutiq_symptom:{$session_id}");

        return response()->json([
            'success' => true,
            'session_id' => $session_id,
            'message' => 'Session state reset successfully.',
        ]);
    }

    // =========================================================================
    // PET PROFILE LOADER
    // =========================================================================

    private function loadPetProfile(array $data): array
    {
        $petId = (int) ($data['pet_id'] ?? 0);

        // If pet_id is 0 (followup without pet_id), try to get from cached session
        if ($petId === 0 && !empty($data['session_id'])) {
            $state = Cache::get("snoutiq_symptom:{$data['session_id']}", []);
            return $state['pet'] ?? $this->defaultPet($data);
        }

        if ($petId > 0) {
            $row = DB::table('pets')
                ->select('id','user_id','name','breed','pet_age','pet_gender',
                         'species','dob','neutered','location')
                ->where('id', $petId)->first();

            if (!$row) return ['error' => 'Pet not found'];
            if (!empty($row->user_id) && !empty($data['user_id']) && (int)$row->user_id !== (int)$data['user_id']) {
                return ['error' => 'Pet does not belong to this user'];
            }

            // Calculate age from dob if available
            $dob = $data['dob'] ?? $row->dob ?? null;
            [$ageVal, $ageUnit] = $this->calcAge($dob);
            $ageStr = $ageVal ? "{$ageVal} {$ageUnit}" : ($row->pet_age ?? 'unknown age');

            return [
                'name'     => $data['pet_name'] ?? $row->name   ?? 'Your pet',
                'species'  => strtolower($data['species']  ?? $row->species    ?? 'dog'),
                'breed'    => $data['breed']    ?? $row->breed   ?? 'Mixed breed',
                'age'      => $ageStr,
                'dob'      => $dob,
                'sex'      => strtolower($data['sex'] ?? $row->pet_gender ?? 'unknown'),
                'neutered' => strtolower($data['neutered'] ?? $row->neutered ?? 'unknown'),
                'location' => $data['location'] ?? $row->location ?? 'India',
            ];
        }

        return $this->defaultPet($data);
    }

    private function defaultPet(array $data): array
    {
        [$ageVal, $ageUnit] = $this->calcAge($data['dob'] ?? null);
        return [
            'name'     => $data['pet_name'] ?? 'Your pet',
            'species'  => strtolower($data['species'] ?? 'dog'),
            'breed'    => $data['breed']    ?? 'Mixed breed',
            'age'      => $ageVal ? "{$ageVal} {$ageUnit}" : 'unknown age',
            'dob'      => $data['dob'] ?? null,
            'sex'      => strtolower($data['sex']      ?? 'unknown'),
            'neutered' => strtolower($data['neutered'] ?? 'unknown'),
            'location' => $data['location'] ?? 'India',
        ];
    }

    private function resolveSessionId(Request $request, array $data): string
    {
        $sessionId = trim((string) ($data['session_id'] ?? ''));
        if ($sessionId !== '') {
            $this->storeSessionIdOnRequest($request, $sessionId);
            return $sessionId;
        }

        if ($request->hasSession()) {
            $existing = trim((string) $request->session()->get('snoutiq_symptom.session_id', ''));
            if ($existing !== '') {
                return $existing;
            }
        }

        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        if ($userId > 0) {
            $latest = ChatRoom::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($latest?->chat_room_token) {
                $sessionId = (string) $latest->chat_room_token;
                $this->storeSessionIdOnRequest($request, $sessionId);
                return $sessionId;
            }
        }

        $sessionId = 'room_' . Str::uuid()->toString();
        $this->storeSessionIdOnRequest($request, $sessionId);

        return $sessionId;
    }

    private function resolveExistingSessionId(Request $request, ?string $sessionId): ?string
    {
        $sessionId = trim((string) ($sessionId ?? ''));
        if ($sessionId !== '') {
            $this->storeSessionIdOnRequest($request, $sessionId);
            return $sessionId;
        }

        if ($request->hasSession()) {
            $existing = trim((string) $request->session()->get('snoutiq_symptom.session_id', ''));
            if ($existing !== '') {
                return $existing;
            }
        }

        return null;
    }

    private function storeSessionIdOnRequest(Request $request, string $sessionId): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->session()->put('snoutiq_symptom.session_id', $sessionId);
    }

    private function calcAge(?string $dob): array
    {
        if (!$dob) return [null, null];
        try {
            $d     = new \DateTime($dob);
            $now   = new \DateTime();
            $days  = (int) $now->diff($d)->days;
            if ($days < 30)  return [$days,                 'days'];
            if ($days < 365) return [round($days / 30.4),  'months'];
            $years  = intdiv($days, 365);
            $months = intdiv($days % 365, 30);
            return [$years, $months > 0 ? "years {$months} months" : 'years'];
        } catch (\Exception $e) {
            return [null, null];
        }
    }

    // =========================================================================
    // LAYER 1 — HARDCODED RED FLAGS (no API, instant emergency)
    // =========================================================================

    private const RED_FLAGS = [
        'not breathing', "can't breathe", 'difficulty breathing', 'gasping', 'choking',
        'seizure', 'fitting', 'convulsion', 'convulsing',
        'collapsed', 'collapse', 'unconscious', 'unresponsive',
        'not urinating', 'straining to urinate', 'cannot pee', "can't pee", 'blocked bladder',
        'ate poison', 'ate rat poison', 'rodenticide', 'poisoning', 'ate something toxic',
        'hit by car', 'road accident', 'run over', 'severe bleeding', 'bleeding heavily',
        'pale gums', 'blue gums', 'white gums', 'yellow gums',
        'bloated stomach', 'stomach bloat', 'abdomen swollen', 'gdv',
        'broken bone', 'bone visible', 'open fracture',
        'not moving at all', 'limp body',
    ];

    /** Special species-specific red flags */
    private const CAT_RED_FLAGS = [
        'straining', 'cant urinate', "can't urinate", 'no urine', 'litter box straining',
        'crying in litter', 'not passed urine',
    ];

    private function checkRedFlags(string $message, array $pet): array
    {
        $text = mb_strtolower($message);

        foreach (self::RED_FLAGS as $flag) {
            if (str_contains($text, $flag)) {
                return [true, $flag];
            }
        }

        // Male cat + any urinary straining = always emergency
        if (($pet['species'] ?? '') === 'cat' && ($pet['sex'] ?? '') === 'male') {
            foreach (self::CAT_RED_FLAGS as $flag) {
                if (str_contains($text, $flag)) {
                    return [true, "urinary obstruction in male cat ({$flag})"];
                }
            }
        }

        return [false, null];
    }

    private function emergencyTriageData(?string $flag, array $pet): array
    {
        return [
            'possible_causes'    => ['Requires immediate veterinary assessment'],
            'red_flags_present'  => $flag ? [$flag] : [],
            'india_context_note' => 'Government veterinary hospitals (IVRI, state vet colleges, municipal vet centres) are available in most Indian cities at low or no cost. Call ahead if possible.',
            'safe_to_wait_hours' => 0,
            'image_observation'  => '',
        ];
    }

    private function emergencyResponse(string $petName, ?string $flag): array
    {
        return [
            'message'          => "{$petName} needs emergency veterinary care right now. Do not wait — this situation can become life-threatening very quickly. Go to the nearest vet clinic or government veterinary hospital immediately.",
            'do_now'           => 'Go to the nearest vet or emergency animal hospital NOW. Call ahead if possible.',
            'time_sensitivity' => 'Go now — every minute matters',
            'what_to_watch'    => [
                'Keep ' . $petName . ' calm and still while travelling',
                'Do not give food, water, or any medications',
                'Note any changes in breathing or consciousness',
            ],
        ];
    }

    // =========================================================================
    // LAYER 2 — EVIDENCE SCORING (keyword heuristics, zero API cost)
    // =========================================================================

    private function scoreEvidence(array &$state, string $message): void
    {
        $text = mb_strtolower($message);

        $buckets = [
            // [score_to_add, keywords]
            [4, ['severe','extreme','emergency','not responding','all night','getting much worse',
                 'rapidly worsening','suddenly collapsed','non-stop vomiting']],
            [3, ['blood in stool','bloody stool','blood in urine','bloody vomit','blood from',
                 'open wound','deep cut','very swollen','dragging leg','cannot stand',
                 'not eating for 2 days','not eating for 3 days','not eaten in 2','not eaten in 3']],
            [2, ['vomiting','vomit','diarrhea','diarrhoea','not eating','no appetite','lethargic',
                 'lethargy','painful','difficulty walking','swollen','discharge','pus',
                 'yelping','crying in pain','wont let me touch']],
            [1, ['limping','slight limp','off food','a bit low','not himself','not herself',
                 'seems tired','scratching','sneezing','runny nose','mild','started today',
                 'since yesterday','for a few days']],
        ];

        foreach ($buckets as [$pts, $keywords]) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    $state['score'] = ($state['score'] ?? 0) + $pts;
                    $state['evidence'][] = $kw;
                    break 1; // one match per bucket per message
                }
            }
        }

        // Regex additions
        $regexRules = [
            [2, '/\bvomit\w*\b|\bthrow(?:ing)?\s+up\b/'],
            [2, '/\bdiarrh?[eo]a\b/'],
            [2, '/\b(not\s+eating|loss\s+of\s+appetite|refus\w+\s+food)\b/'],
            [3, '/\bblood\s+in\s+(stool|urine|vomit)\b/'],
            [3, '/\bseizure|convuls\w+|fitting\b/'],
        ];
        foreach ($regexRules as [$pts, $rx]) {
            if (preg_match($rx, $text)) {
                $state['score'] = ($state['score'] ?? 0) + $pts;
            }
        }
    }

    private function routingFromScore(int $score, int $turn): string
    {
        // Backstop: after 3 turns with any symptoms, move to video at minimum
        if ($turn >= 3 && $score >= 1) return 'video_consult';

        if ($score >= 8)  return 'emergency';
        if ($score >= 5)  return 'in_clinic';
        if ($score >= 2)  return 'video_consult';
        return 'monitor';
    }

    /** Gemini routing can only upgrade severity, not downgrade */
    private function mergeRouting(string $heuristic, string $gemini): string
    {
        $rank = ['monitor' => 0, 'video_consult' => 1, 'in_clinic' => 2, 'emergency' => 3];
        $h = $rank[$heuristic] ?? 1;
        $g = $rank[$gemini]    ?? 1;
        $merged = $g > $h ? $gemini : $heuristic;
        return $merged;
    }

    // =========================================================================
    // LAYER 3 — GEMINI TRIAGE CALL (structured JSON output)
    // =========================================================================

    private const INDIA_CONTEXT = "
INDIA VETERINARY CONTEXT — ALWAYS APPLY:
- Monsoon season = high risk: leptospirosis, tick fever (Ehrlichia/Babesia), GI infections
- Summer = heatstroke, dehydration, urolithiasis common
- Winter North India = respiratory infections peak
- Stray/unvaccinated dogs = parvo, distemper, TVT, rabies risk
- Stray/unvaccinated cats = panleukopenia, ringworm (very common), FIP
- MALE CAT not urinating or straining = ALWAYS emergency (obstruction fatal in 24-48h)
- Large dog + bloated stomach = ALWAYS emergency (GDV)
- Indian owners often try home remedies first: paracetamol (TOXIC to pets), turmeric, coconut oil
- Cost sensitivity is real — suggest govt vet hospital for emergencies (free/low cost)
- Tick fever is common year-round in India and causes sudden severe illness
- Intact female dog/cat not eating + lethargy = consider pyometra
";

    private function callGeminiTriage(
        array $pet, string $message, array $history,
        ?string $imageB64, string $imageMime, int $score
    ): array {
        $historyStr = $this->formatHistoryForPrompt($history);
        $petStr     = $this->petToString($pet);

        $prompt =
            self::INDIA_CONTEXT . "\n\n" .
            "PET: {$petStr}\n\n" .
            ($historyStr ? "CONVERSATION SO FAR:\n{$historyStr}\n\n" : '') .
            "CURRENT MESSAGE: " . mb_substr($message, 0, self::MAX_INPUT_CHARS) . "\n" .
            "EVIDENCE SCORE SO FAR: {$score}/10\n\n" .
            "TASK: Assess this pet's situation and output routing decision.\n\n" .
            "Routing options:\n" .
            "- emergency: life-threatening right now\n" .
            "- video_consult: vet can assess via video, no immediate danger\n" .
            "- in_clinic: needs physical exam, cannot be assessed remotely\n" .
            "- monitor: safe to watch at home with clear instructions\n\n" .
            "Return ONLY this JSON object, nothing else:\n" .
            '{"routing":"emergency|video_consult|in_clinic|monitor",' .
            '"severity":"critical|moderate|mild|informational",' .
            '"possible_causes":["cause1","cause2"],' .
            '"red_flags_present":["flag if any"],' .
            '"india_context_note":"1 sentence India-specific risk",' .
            '"safe_to_wait_hours":0}';

        // Add image if provided
        $raw = $imageB64
            ? $this->geminiCallWithImage($prompt, $imageB64, $imageMime, self::TRIAGE_MAX_TOKENS)
            : $this->geminiCall($prompt, self::TRIAGE_MAX_TOKENS);

        $decoded = $this->decodeJson($raw);
        return is_array($decoded) ? $decoded : ['routing' => 'video_consult', 'severity' => 'mild'];
    }

    // =========================================================================
    // LAYER 4 — GEMINI RESPONSE WRITER (warm, plain language for pet parent)
    // =========================================================================

    private function callGeminiResponse(array $pet, string $message, string $routing, array $triage, array $history = []): array
    {
        $routingInstructions = [
            'emergency'    => 'This pet needs emergency care NOW. Be calm but very direct — go to nearest vet or government hospital immediately. Give ONE thing they can do right now while going (keep warm, do not feed). Do not say it will be okay. Be honest but not terrifying.',
            'video_consult'=> 'Recommend a Snoutiq video consultation. A vet can see the pet on screen and give proper guidance in minutes. Great for night-time or when clinic feels too far. Be reassuring — this can be handled with professional guidance.',
            'in_clinic'    => 'This needs a physical examination — a vet needs to feel and examine the pet in person. Video will not be enough. Book the earliest clinic appointment or go today. Be gentle but clear.',
            'monitor'      => 'Reassure the pet parent this looks manageable for now. Give 3-4 very specific warning signs to watch for. Give a time window — if not better in X hours, book a consult. Offer Snoutiq video consult if they want a vet to check.',
        ];

        $causes = implode(', ', array_slice($triage['possible_causes'] ?? [], 0, 3));
        $redFlags = implode(', ', array_slice($triage['red_flags_present'] ?? [], 0, 3));
        $historyStr = $this->formatHistoryForPrompt($history);
        $imageObservation = trim((string) ($triage['image_observation'] ?? ''));

        $prompt =
            "You are Snoutiq, a caring pet health assistant. " .
            "You speak to worried pet parents in warm, simple language. " .
            "You never diagnose. You never say you are an AI. " .
            "You always give one specific next action. No medical jargon. " .
            "Make the response feel tailored to this exact conversation, not like a reusable template.\n\n" .
            "Pet: {$pet['name']} ({$pet['species']}, {$pet['breed']}, {$pet['age']})\n" .
            ($historyStr ? "Recent conversation:\n{$historyStr}\n" : '') .
            "Message from owner: " . mb_substr($message, 0, 300) . "\n" .
            "Routing decision: {$routing}\n" .
            ($causes ? "Possible causes (do NOT state as diagnosis): {$causes}\n" : '') .
            ($redFlags ? "Red flags already seen: {$redFlags}\n" : '') .
            ($imageObservation !== '' ? "Image observation: {$imageObservation}\n" : '') .
            "India context: " . ($triage['india_context_note'] ?? '') . "\n\n" .
            "Instruction: " . ($routingInstructions[$routing] ?? $routingInstructions['video_consult']) . "\n\n" .
            "The `message` field must sound natural and should reference what the owner is seeing right now. " .
            "If there is recent conversation history, continue from it instead of restarting.\n\n" .
            "Return ONLY this JSON:\n" .
            '{"message":"Main response 2-4 sentences plain language",' .
            '"diagnosis_summary":"One short sentence with preliminary likely causes, never a confirmed diagnosis",' .
            '"do_now":"One immediate action",' .
            '"time_sensitivity":"e.g. Go now / Within 2-4 hours / If not better in 24 hours",' .
            '"what_to_watch":["sign1","sign2","sign3"]}';

        $raw     = $this->geminiCall($prompt, self::RESPONSE_MAX_TOKENS);
        $decoded = $this->decodeJson($raw);

        if (is_array($decoded)) {
            return $this->normalizeGeminiResponsePayload($decoded, $pet, $routing, $message, $triage);
        }

        $recovered = $this->recoverStructuredResponse($raw);
        if (is_array($recovered)) {
            return $this->normalizeGeminiResponsePayload($recovered, $pet, $routing, $message, $triage);
        }

        return $this->normalizeGeminiResponsePayload([], $pet, $routing, $message, $triage, $raw);
    }

    // =========================================================================
    // GEMINI API CALLS
    // =========================================================================

    private function geminiCall(string $prompt, int $maxTokens = 500): string
    {
        if (mb_strlen($prompt) > self::MAX_PROMPT_CHARS) {
            $prompt = mb_substr($prompt, 0, self::MAX_PROMPT_CHARS);
        }

        $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $body = json_encode([
            'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => ['maxOutputTokens' => $maxTokens, 'temperature' => 0.2],
        ]);

        return $this->curlPost($url, $body);
    }

    private function geminiCallWithImage(string $prompt, string $imageB64, string $mime, int $maxTokens = 500): string
    {
        // Strip data URI prefix if present
        if (preg_match('/^data:[^;]+;base64,(.+)$/s', $imageB64, $m)) {
            $imageB64 = $m[1];
        }

        $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $body = json_encode([
            'contents' => [[
                'role'  => 'user',
                'parts' => [
                    ['inline_data' => ['mime_type' => $mime, 'data' => $imageB64]],
                    ['text'        => $prompt],
                ],
            ]],
            'generationConfig' => ['maxOutputTokens' => $maxTokens, 'temperature' => 0.2],
        ]);

        return $this->curlPost($url, $body);
    }

    private function curlPost(string $url, string $body): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $raw === false) {
            Log::error('Gemini curl error', ['error' => $err]);
            return '';
        }

        $decoded = json_decode($raw, true);
        return $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function decodeJson(string $text): ?array
    {
        // Strip markdown fences
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) return null;

        $json    = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeGeminiResponsePayload(
        array $payload,
        array $pet,
        string $routing,
        string $ownerMessage,
        array $triage,
        string $rawText = ''
    ): array {
        $message = $this->cleanAssistantText((string) ($payload['message'] ?? ''));
        if ($message === '' && $rawText !== '') {
            $message = $this->extractNarrativeFromRawResponse($rawText);
        }
        if ($message === '') {
            $message = $this->buildDynamicFallbackMessage($pet, $routing, $ownerMessage, $triage);
        }

        $diagnosisSummary = $this->cleanAssistantText((string) ($payload['diagnosis_summary'] ?? ''));
        if ($diagnosisSummary === '') {
            $diagnosisSummary = $this->buildDiagnosisSummary($triage, $ownerMessage);
        }
        $message = $this->appendDiagnosisToMessage($message, $diagnosisSummary);

        $doNow = $this->cleanAssistantText((string) ($payload['do_now'] ?? ''));
        if ($doNow === '') {
            $doNow = $this->defaultDoNow($pet, $routing);
        }

        $timeSensitivity = $this->cleanAssistantText((string) ($payload['time_sensitivity'] ?? ''));
        if ($timeSensitivity === '') {
            $timeSensitivity = $this->defaultTimeSensitivity($routing, $triage);
        }

        $whatToWatch = [];
        if (isset($payload['what_to_watch']) && is_array($payload['what_to_watch'])) {
            $whatToWatch = array_values(array_filter(array_map(
                fn ($item) => $this->cleanAssistantText((string) $item),
                $payload['what_to_watch']
            )));
        }
        if (!$whatToWatch) {
            $whatToWatch = $this->defaultWhatToWatch($routing, $triage);
        }

        return [
            'message' => $message,
            'diagnosis_summary' => $diagnosisSummary,
            'do_now' => $doNow,
            'time_sensitivity' => $timeSensitivity,
            'what_to_watch' => array_slice(array_values(array_unique($whatToWatch)), 0, 4),
        ];
    }

    private function recoverStructuredResponse(string $raw): ?array
    {
        $message = $this->extractJsonStringField($raw, 'message');
        $doNow = $this->extractJsonStringField($raw, 'do_now');
        $timeSensitivity = $this->extractJsonStringField($raw, 'time_sensitivity');
        $whatToWatch = $this->extractJsonStringArrayField($raw, 'what_to_watch');

        $payload = array_filter([
            'message' => $message,
            'do_now' => $doNow,
            'time_sensitivity' => $timeSensitivity,
            'what_to_watch' => $whatToWatch,
        ], function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }
            return $value !== null && $value !== '';
        });

        return $payload ?: null;
    }

    private function extractJsonStringField(string $raw, string $field): ?string
    {
        if (!preg_match('/"' . preg_quote($field, '/') . '"\s*:\s*"((?:\\\\.|[^"\\\\])*)"/s', $raw, $matches)) {
            return null;
        }

        $decoded = json_decode('"' . $matches[1] . '"', true);
        if (is_string($decoded)) {
            return $decoded;
        }

        return stripcslashes($matches[1]);
    }

    private function extractJsonStringArrayField(string $raw, string $field): array
    {
        if (!preg_match('/"' . preg_quote($field, '/') . '"\s*:\s*\[(.*?)\]/s', $raw, $matches)) {
            return [];
        }

        preg_match_all('/"((?:\\\\.|[^"\\\\])*)"/s', $matches[1], $itemMatches);
        $items = [];
        foreach ($itemMatches[1] ?? [] as $item) {
            $decoded = json_decode('"' . $item . '"', true);
            $items[] = is_string($decoded) ? $decoded : stripcslashes($item);
        }

        return array_values(array_filter($items, fn ($item) => trim((string) $item) !== ''));
    }

    private function extractNarrativeFromRawResponse(string $raw): string
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = preg_replace('/^\s*[{[].*[}\]]\s*$/s', '', $text);
        $text = strip_tags((string) $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/^\s*(message|do_now|time_sensitivity)\s*:\s*/mi', '', $text);
        $lines = array_values(array_filter(array_map(
            fn ($line) => trim(preg_replace('/^[\-\*\x{2022}]+\s*/u', '', $line)),
            explode("\n", $text)
        )));

        if (!$lines) {
            return '';
        }

        return $this->cleanAssistantText(implode(' ', array_slice($lines, 0, 3)));
    }

    private function cleanAssistantText(string $text): string
    {
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[`*_#>]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim((string) $text, " \t\n\r\0\x0B\"'");
    }

    private function buildDynamicFallbackMessage(array $pet, string $routing, string $ownerMessage, array $triage): string
    {
        $petName = trim((string) ($pet['name'] ?? 'your pet')) ?: 'your pet';
        $issue = $this->summarizeOwnerConcern($ownerMessage);
        $causes = array_values(array_filter(array_map(
            fn ($cause) => $this->cleanAssistantText((string) $cause),
            array_slice($triage['possible_causes'] ?? [], 0, 2)
        )));
        $issueLine = $issue !== '' ? "Based on your note about {$issue}, " : '';
        $causeLine = $causes ? ' This could be related to ' . implode(' or ', $causes) . '.' : '';

        $message = match ($routing) {
            'emergency' => "{$petName} needs urgent hands-on veterinary care right now. {$issueLine}{$causeLine} Please go to the nearest vet clinic or emergency animal hospital immediately.",
            'in_clinic' => "{$issueLine}{$petName} needs an in-person veterinary exam rather than home monitoring alone.{$causeLine} Please arrange the earliest clinic visit today.",
            'monitor' => "{$issueLine}{$petName}'s situation sounds manageable to monitor for the moment if they stay otherwise stable.{$causeLine} Keep a close eye on symptoms and upgrade to a consult if anything worsens.",
            default => "{$issueLine}A live vet review would be the best next step for {$petName}.{$causeLine} A video consult can help you decide quickly whether home care is enough or a clinic visit is needed.",
        };

        return preg_replace('/\s+/', ' ', trim($message));
    }

    private function defaultDoNow(array $pet, string $routing): string
    {
        $petName = trim((string) ($pet['name'] ?? 'your pet')) ?: 'your pet';

        return match ($routing) {
            'emergency' => "Leave now for the nearest vet or emergency hospital with {$petName} kept calm and still.",
            'in_clinic' => "Call the nearest clinic and book the earliest same-day appointment for {$petName}.",
            'monitor' => "Offer rest, water if tolerated, and note any change in appetite, energy, vomiting, stool, or breathing.",
            default => "Start a Snoutiq video consult so a vet can review {$petName}'s symptoms in real time.",
        };
    }

    private function defaultTimeSensitivity(string $routing, array $triage): string
    {
        $safeToWait = isset($triage['safe_to_wait_hours']) ? (int) $triage['safe_to_wait_hours'] : 0;

        return match ($routing) {
            'emergency' => 'Go now',
            'in_clinic' => $safeToWait > 0 ? "Within {$safeToWait} hours" : 'Same day',
            'monitor' => $safeToWait > 0 ? "If not improving within {$safeToWait} hours" : 'If not improving within 24 hours',
            default => $safeToWait > 0 ? "Within {$safeToWait} hours" : 'Within the next few hours',
        };
    }

    private function defaultWhatToWatch(string $routing, array $triage): array
    {
        $items = [];

        foreach (array_slice($triage['red_flags_present'] ?? [], 0, 2) as $flag) {
            $flagText = $this->cleanAssistantText((string) $flag);
            if ($flagText !== '') {
                $items[] = 'Any sign of ' . $flagText;
            }
        }

        $defaults = match ($routing) {
            'emergency' => [
                'Breathing becoming fast, noisy, or laboured',
                'Collapse, weakness, or reduced responsiveness',
                'Repeated vomiting, seizures, or sudden worsening',
            ],
            'in_clinic' => [
                'Pain, swelling, or limping getting worse',
                'Eating or drinking much less than usual',
                'Vomiting, diarrhoea, or low energy continuing',
            ],
            'monitor' => [
                'Low energy or appetite getting worse',
                'New vomiting, diarrhoea, or trouble urinating',
                'Any new pain, swelling, or breathing changes',
            ],
            default => [
                'Low energy, appetite loss, or dehydration',
                'Symptoms spreading, worsening, or becoming more frequent',
                'Any breathing difficulty or sudden distress',
            ],
        };

        return array_slice(array_values(array_unique(array_merge($items, $defaults))), 0, 4);
    }

    private function buildDiagnosisSummary(array $triage, string $ownerMessage = ''): string
    {
        $causes = array_values(array_filter(array_map(
            fn ($cause) => $this->cleanAssistantText((string) $cause),
            array_slice($triage['possible_causes'] ?? [], 0, 3)
        )));

        if (!$causes) {
            $causes = $this->inferPossibleCausesFromMessage($ownerMessage);
        }

        if (!$causes) {
            return '';
        }

        return 'Possible causes include ' . $this->humanJoin($causes) . '.';
    }

    private function appendDiagnosisToMessage(string $message, string $diagnosisSummary): string
    {
        $message = trim($message);
        $diagnosisSummary = trim($diagnosisSummary);

        if ($diagnosisSummary === '') {
            return $message;
        }

        if (preg_match('/\b(possible causes?|likely causes?|preliminary diagnosis|diagnosis summary)\b/i', $message)) {
            return $message;
        }

        if ($message !== '' && !preg_match('/[.!?]$/', $message)) {
            $message .= '.';
        }

        return trim($message . ' ' . $diagnosisSummary);
    }

    private function inferPossibleCausesFromMessage(string $ownerMessage): array
    {
        $text = mb_strtolower($this->cleanAssistantText($ownerMessage));
        if ($text === '') {
            return [];
        }

        $rules = [
            [['vomit', 'vomiting', 'throwing up'], ['stomach upset', 'dietary indiscretion', 'a gastrointestinal infection']],
            [['diarrhea', 'diarrhoea', 'loose motion', 'loose stool'], ['stomach upset', 'diet change', 'a gastrointestinal infection']],
            [['itch', 'itching', 'scratching', 'skin', 'rash'], ['an allergy flare', 'a skin infection', 'fleas or ticks']],
            [['limp', 'limping', 'paw pain', 'leg pain'], ['a soft tissue injury', 'a paw injury', 'joint pain']],
            [['cough', 'coughing', 'sneeze', 'sneezing', 'runny nose'], ['respiratory irritation', 'an infection', 'an allergy']],
            [['not eating', 'no appetite', 'loss of appetite', 'lethargic', 'lethargy'], ['fever', 'stomach upset', 'pain or dehydration']],
            [['urine', 'urinating', 'pee', 'straining to pee'], ['a urinary infection', 'stones', 'a blockage']],
        ];

        foreach ($rules as [$keywords, $causes]) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $causes;
                }
            }
        }

        return [];
    }

    private function humanJoin(array $items): string
    {
        $items = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $items
        )));

        $count = count($items);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $items[0];
        }
        if ($count === 2) {
            return $items[0] . ' or ' . $items[1];
        }

        $last = array_pop($items);
        return implode(', ', $items) . ', or ' . $last;
    }

    private function summarizeOwnerConcern(string $ownerMessage): string
    {
        $text = $this->cleanAssistantText($ownerMessage);
        if ($text === '') {
            return '';
        }

        $text = mb_strtolower($text);
        $text = mb_substr($text, 0, 90);
        return rtrim($text, " .,;:!?");
    }

    // =========================================================================
    // BUTTON CONFIG — frontend reads this to show CTA buttons
    // =========================================================================

    private function buttons(string $routing): array
    {
        $map = [
            'emergency' => [
                'primary'   => ['label' => 'Find Emergency Vet Now', 'type' => 'emergency',
                                'deeplink' => self::DEEPLINK_EMERGENCY, 'color' => '#ef4444', 'icon' => 'alert-triangle'],
                'secondary' => ['label' => 'Govt. Vet Hospital',     'type' => 'govt',
                                'deeplink' => self::DEEPLINK_GOVT,      'color' => '#f97316', 'icon' => 'building'],
            ],
            'video_consult' => [
                'primary'   => ['label' => 'Start Video Consult',    'type' => 'video_consult',
                                'deeplink' => self::DEEPLINK_VIDEO,     'color' => '#3b82f6', 'icon' => 'video'],
                'secondary' => ['label' => 'Find Nearby Vet',        'type' => 'clinic',
                                'deeplink' => self::DEEPLINK_CLINIC,    'color' => '#8b5cf6', 'icon' => 'map-pin'],
            ],
            'in_clinic' => [
                'primary'   => ['label' => 'Find Nearby Vet',        'type' => 'clinic',
                                'deeplink' => self::DEEPLINK_CLINIC,    'color' => '#8b5cf6', 'icon' => 'map-pin'],
                'secondary' => ['label' => 'Video Consult First',    'type' => 'video_consult',
                                'deeplink' => self::DEEPLINK_VIDEO,     'color' => '#3b82f6', 'icon' => 'video'],
            ],
            'monitor' => [
                'primary'   => ['label' => 'Monitor at Home',        'type' => 'info',
                                'deeplink' => self::DEEPLINK_MONITOR,   'color' => '#10b981', 'icon' => 'eye'],
                'secondary' => ['label' => 'Video Consult if Worried','type' => 'video_consult',
                                'deeplink' => self::DEEPLINK_VIDEO,      'color' => '#3b82f6', 'icon' => 'video'],
            ],
        ];
        return $map[$routing] ?? $map['video_consult'];
    }

    // =========================================================================
    // STATE + HELPERS
    // =========================================================================

    private function defaultState(): array
    {
        return ['history' => [], 'pet' => [], 'score' => 0, 'evidence' => []];
    }

    private function softReset(array &$state): void
    {
        $state['score']    = 0;
        $state['evidence'] = [];
        $state['history']  = [];
    }

    private function appendHistory(array &$state, string $q, string $a, string $routing, int $score): void
    {
        $state['history'][] = [
            'user'      => mb_substr($q, 0, 200),
            'assistant' => mb_substr($a, 0, 300),
            'routing'   => $routing,
            'score'     => $score,
            'ts'        => now()->format('H:i'),
        ];
        // Keep only last N turns in state
        if (count($state['history']) > self::MAX_HISTORY_TURNS + 2) {
            $state['history'] = array_slice($state['history'], -self::MAX_HISTORY_TURNS);
        }
    }

    private function formatHistoryForPrompt(array $history): string
    {
        $recent = array_slice($history, -self::MAX_HISTORY_TURNS);
        $lines  = [];
        foreach ($recent as $turn) {
            $lines[] = 'Owner: ' . ($turn['user']      ?? '');
            $lines[] = 'Snoutiq: ' . ($turn['assistant'] ?? '');
        }
        return implode("\n", $lines);
    }

    private function petToString(array $pet): string
    {
        return sprintf(
            '%s (%s, %s, %s, Sex: %s, Neutered: %s, Location: %s)',
            $pet['name'] ?? 'Pet',
            $pet['species'] ?? 'unknown',
            $pet['breed']   ?? 'mixed',
            $pet['age']     ?? 'unknown age',
            $pet['sex']     ?? 'unknown',
            $pet['neutered'] ?? 'unknown',
            $pet['location'] ?? 'India'
        );
    }

    private function vetSummary(array $state, int $score, string $routing): string
    {
        $pet  = $state['pet'] ?? [];
        $evid = implode(', ', array_slice($state['evidence'] ?? [], 0, 8));
        $hist = array_slice($state['history'] ?? [], -3);
        $msgs = array_map(fn($h) => '• ' . mb_substr($h['user'] ?? '', 0, 80), $hist);

        return sprintf(
            "PATIENT: %s | %s | %s | %s | Sex: %s | Neutered: %s | Location: %s\n" .
            "TRIAGE: Score %d/10 → %s\n" .
            "EVIDENCE KEYWORDS: %s\n" .
            "OWNER MESSAGES:\n%s",
            $pet['name'] ?? 'Unknown', $pet['species'] ?? '?', $pet['breed'] ?? '?',
            $pet['age'] ?? '?', $pet['sex'] ?? '?', $pet['neutered'] ?? '?', $pet['location'] ?? 'India',
            $score, strtoupper($routing),
            $evid ?: 'none captured',
            implode("\n", $msgs)
        );
    }

    private function isGreeting(string $msg): bool
    {
        return (bool) preg_match(
            '/^(hi|hello|hey|hii|hlo|namaste|namaskar|yo|sup|good\s+(morning|evening|night))[!. ]*$/iu',
            trim($msg)
        );
    }

    private function saveToDb(array $data, string $sessionId, string $question, string $answer, string $routing, string $severity): void
    {
        try {
            $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
            if ($userId <= 0) {
                return;
            }

            $emergencyStatus = in_array($routing, ['emergency', 'in_clinic'], true)
                ? strtoupper($routing) : null;

            $room = ChatRoom::firstOrCreate(
                ['chat_room_token' => $sessionId],
                ['user_id' => $userId, 'name' => 'Symptom Check']
            );

            Chat::create([
                'user_id'          => $userId,
                'chat_room_id'     => $room->id,
                'chat_room_token'  => $room->chat_room_token,
                'context_token'    => $sessionId,
                'question'         => mb_substr($question, 0, 500),
                'answer'           => mb_substr($answer, 0, 1000),
                'response_tag'     => $routing,
                'emergency_status' => $emergencyStatus,
                'pet_name'         => $data['pet_name'] ?? null,
                'pet_breed'        => $data['breed'] ?? null,
                'pet_age'          => $data['dob'] ?? null,
                'pet_location'     => $data['location'] ?? null,
            ]);

            $room->last_emergency_status = $emergencyStatus;
            $room->touch();
            $room->save();
        } catch (\Exception $e) {
            Log::error('SnoutiqSymptomController DB save failed', ['error' => $e->getMessage()]);
        }
    }
}
