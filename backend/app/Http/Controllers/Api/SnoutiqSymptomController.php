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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SnoutiqSymptomController extends Controller
{
    // ── App deeplinks — update to match your routing ──────────────────────────
    private const DEEPLINK_EMERGENCY = 'snoutiq://emergency';
    private const DEEPLINK_VIDEO     = 'snoutiq://video-consult';
    private const DEEPLINK_CLINIC    = 'snoutiq://find-clinic';
    private const DEEPLINK_MONITOR   = 'snoutiq://monitor-guide';
    private const DEEPLINK_GOVT      = 'snoutiq://govt-hospitals';
    private const DEEPLINK_VET_HOME  = 'snoutiq://vet-at-home';
    private const DEEPLINK_CLINIC_BOOKING = 'snoutiq://clinic-booking';

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
     *     "safe_to_do_while_waiting": ["step1", "step2", "step3"],
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
            'phone'        => 'nullable|string|max:30',
            'lat'          => 'nullable|numeric|between:-90,90',
            'latitude'     => 'nullable|numeric|between:-90,90',
            'long'         => 'nullable|numeric|between:-180,180',
            'lng'          => 'nullable|numeric|between:-180,180',
            'lon'          => 'nullable|numeric|between:-180,180',
            'longitude'    => 'nullable|numeric|between:-180,180',
            'owner_name'   => 'nullable|string|max:120',
            'pet_owner_name' => 'nullable|string|max:120',
            'user_name'    => 'nullable|string|max:120',
            'pet_name'     => 'nullable|string|max:120',
            'species'      => 'nullable|string|max:20',
            'type'         => 'nullable|string|max:20',
            'breed'        => 'nullable|string|max:80',
            'dob'          => 'nullable|date_format:Y-m-d',
            'sex'          => 'nullable|string|max:10',
            'neutered'     => 'nullable|string|max:10',
            'location'     => 'nullable|string|max:100',
            'session_id'   => 'nullable|string|max:100',
            'image_base64' => 'nullable|string',
            'image_mime'   => 'nullable|string|max:20',
            'image'        => 'nullable|file|image|max:5120',
            'user'         => 'nullable|array',
            'user.phone'   => 'nullable|string|max:30',
            'user.name'    => 'nullable|string|max:120',
            'user.lat'     => 'nullable|numeric|between:-90,90',
            'user.latitude'=> 'nullable|numeric|between:-90,90',
            'user.long'    => 'nullable|numeric|between:-180,180',
            'user.lng'     => 'nullable|numeric|between:-180,180',
            'user.lon'     => 'nullable|numeric|between:-180,180',
            'user.longitude' => 'nullable|numeric|between:-180,180',
            'users'        => 'nullable|array',
            'users.phone'  => 'nullable|string|max:30',
            'users.name'   => 'nullable|string|max:120',
            'users.lat'    => 'nullable|numeric|between:-90,90',
            'users.latitude'=> 'nullable|numeric|between:-90,90',
            'users.long'   => 'nullable|numeric|between:-180,180',
            'users.lng'    => 'nullable|numeric|between:-180,180',
            'users.lon'    => 'nullable|numeric|between:-180,180',
            'users.longitude' => 'nullable|numeric|between:-180,180',
            'pet'          => 'nullable|array',
            'pet.pet_name' => 'nullable|string|max:120',
            'pet.name'     => 'nullable|string|max:120',
            'pet.breed'    => 'nullable|string|max:80',
            'pet.dob'      => 'nullable|date_format:Y-m-d',
            'pet.type'     => 'nullable|string|max:20',
            'pet.species'  => 'nullable|string|max:20',
            'pets'         => 'nullable|array',
            'pets.pet_name'=> 'nullable|string|max:120',
            'pets.name'    => 'nullable|string|max:120',
            'pets.breed'   => 'nullable|string|max:80',
            'pets.dob'     => 'nullable|date_format:Y-m-d',
            'pets.type'    => 'nullable|string|max:20',
            'pets.species' => 'nullable|string|max:20',
        ]);

        $data = $this->normalizeSymptomEntryPayload($data);
        $resolvedEntities = $this->persistSymptomEntryUserAndPet($data);
        if (!empty($resolvedEntities['user_id'])) {
            $data['user_id'] = $resolvedEntities['user_id'];
        }
        if (!empty($resolvedEntities['pet_id'])) {
            $data['pet_id'] = $resolvedEntities['pet_id'];
        }

        // ── Trim input to cost limit ──────────────────────────────────────────
        $message = mb_substr(trim($data['message']), 0, self::MAX_INPUT_CHARS);

        // ── Load pet from DB, merge with request overrides ────────────────────
        $pet = $this->loadPetProfile($data);
        if (isset($pet['error'])) {
            return response()->json(['success' => false, 'message' => $pet['error']], 404);
        }

        // ── Session state ─────────────────────────────────────────────────────
        $sessionId = $this->resolveSessionId($data);
        $cacheKey = "snoutiq_symptom:{$sessionId}";
        $state    = $this->loadConversationState($sessionId) ?? $this->defaultState();

        // ── Soft reset on greeting ────────────────────────────────────────────
        if ($this->isGreeting($message) && !empty($state['history'])) {
            $this->softReset($state);
        }

        $state['pet'] = $pet;
        $state['user_id'] = $data['user_id'] ?? ($state['user_id'] ?? null);
        $state['pet_id'] = $data['pet_id'] ?? ($state['pet_id'] ?? null);
        $data['user_id'] = $state['user_id'] ?? null;
        $data['pet_id'] = $state['pet_id'] ?? null;
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
            $this->saveWebChatCampaign($data, $sessionId, $turn, $message, $response, $state, $routing, 'critical', $score);

            $triageDetail = [
                'possible_causes' => $triage['possible_causes'] ?? [],
                'red_flags_found' => $triage['red_flags_present'] ?? [],
                'india_context' => $triage['india_context_note'] ?? '',
                'safe_to_wait_hours' => $triage['safe_to_wait_hours'] ?? 0,
                'image_observation' => $triage['image_observation'] ?? '',
            ];

            return response()->json(
                $this->buildApiPayload(
                    $sessionId,
                    $routing,
                    'critical',
                    $turn,
                    $score,
                    $response,
                    $triageDetail,
                    $state,
                    true
                )
            );
        }

        // ── LAYER 2: Evidence scoring (keyword heuristics, fast, no API call) ─
        $this->scoreEvidence($state, $message);
        $score       = min(10, (int) $state['score']);
        $baseRouting = $this->routingFromScore($score, $turn);

        // ── LAYER 3: Gemini — Triage call (structured JSON) ───────────────────
        [$imageB64, $imageMime] = $this->resolveImagePayload($request, $data);

        $triageJson = $this->callGeminiTriage(
            $pet,
            $message,
            $state['history'],
            $state['follow_up_history'] ?? [],
            $imageB64,
            $imageMime,
            $score
        );
        $triageJson['possible_causes'] = $this->normalizePossibleCauses($triageJson['possible_causes'] ?? [], $message, $pet);
        $triageJson['india_context_note'] = $this->normalizeIndiaContextNote(
            $triageJson['india_context_note'] ?? '',
            $pet,
            $message,
            $triageJson['routing'] ?? ''
        );

        // Gemini routing can upgrade but not downgrade the heuristic decision
        $routing = $this->mergeRouting($baseRouting, $triageJson['routing'] ?? $baseRouting);

        // Force emergency if severity is critical
        if (($triageJson['severity'] ?? '') === 'critical') {
            $routing = 'emergency';
        }

        // ── LAYER 4: Gemini — Response writer (warm, plain language) ──────────
        $response = $this->callGeminiResponse(
            $pet,
            $message,
            $routing,
            $triageJson,
            $state['history'] ?? [],
            $state['follow_up_history'] ?? [],
            $imageB64,
            $imageMime
        );

        // ── Update state ──────────────────────────────────────────────────────
        $this->appendHistory($state, $message, $response['message'] ?? '', $routing, $score);
        Cache::put($cacheKey, $state, now()->addMinutes(self::SESSION_TTL_MINUTES));

        // ── Save to DB ────────────────────────────────────────────────────────
        $severity = $triageJson['severity'] ?? 'mild';
        $this->saveToDb($data, $sessionId, $message, $response['message'] ?? '', $routing, $severity);
        $this->saveWebChatCampaign($data, $sessionId, $turn, $message, $response, $state, $routing, $severity, $score);

        $triageDetail = [
            'possible_causes' => $triageJson['possible_causes'] ?? [],
            'red_flags_found' => $triageJson['red_flags_present'] ?? [],
            'india_context' => $triageJson['india_context_note'] ?? '',
            'safe_to_wait_hours' => $triageJson['safe_to_wait_hours'] ?? 0,
            'image_observation' => $triageJson['image_observation'] ?? '',
        ];

        return response()->json(
            $this->buildApiPayload(
                $sessionId,
                $routing,
                $severity,
                $turn,
                $score,
                $response,
                $triageDetail,
                $state,
                false
            )
        );
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
            'image_base64' => 'nullable|string',
            'image_mime' => 'nullable|string|max:20',
            'image' => 'nullable|file|image|max:5120',
        ]);

        $sessionId = $this->resolveExistingSessionId($data['session_id'] ?? null);
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
            'image_base64' => $data['image_base64'] ?? null,
            'image_mime' => $data['image_mime'] ?? null,
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
            'image'          => 'nullable|file|image|max:5120',
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
        if ($request->hasFile('image')) {
            $internal->files->set('image', $request->file('image'));
        }

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
        $compatBody['safe_to_do_while_waiting'] = $rawBody['response']['safe_to_do_while_waiting'] ?? [];
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
            'safe_to_do_while_waiting' => $rawBody['response']['safe_to_do_while_waiting'] ?? [],
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
            'image_base64' => 'nullable|string',
            'image_mime' => 'nullable|string|max:20',
            'image' => 'nullable|file|image|max:5120',
        ]);

        $sessionId = $this->resolveExistingSessionId($data['session_id'] ?? null);
        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'session_id is required to answer a follow-up question.',
            ], 422);
        }

        $cacheKey = "snoutiq_symptom:{$sessionId}";
        $state = $this->loadConversationState($sessionId) ?? $this->defaultState();
        $state['follow_up_history'] = $state['follow_up_history'] ?? [];
        $state['follow_up_history'][] = [
            'question' => trim((string) $data['question']),
            'answer' => trim((string) $data['answer']),
            'ts' => now()->format('H:i'),
        ];
        $state['follow_up_history'] = array_slice($state['follow_up_history'], -6);
        Cache::put($cacheKey, $state, now()->addMinutes(self::SESSION_TTL_MINUTES));

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
            'image_base64' => $data['image_base64'] ?? null,
            'image_mime' => $data['image_mime'] ?? null,
        ]);
        if ($request->hasFile('image')) {
            $internal->files->set('image', $request->file('image'));
        }

        $response = $this->check($internal);
        $payload = json_decode($response->getContent(), true);

        if (is_array($payload)) {
            $payload['revised_assessment'] = true;
            $payload['revised_context'] = [
                'question' => trim((string) $data['question']),
                'answer' => trim((string) $data['answer']),
            ];
            $payload['follow_up_history'] = $state['follow_up_history'];
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
        $state = $this->loadConversationState($session_id);
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
        DB::table('web_chat_campaign')->where('session_id', $session_id)->delete();

        return response()->json([
            'success' => true,
            'session_id' => $session_id,
            'message' => 'Session state reset successfully.',
        ]);
    }

    /**
     * POST /api/ask/chat-rooms/new
     * Creates an empty Ask chat room backed by web_chat_campaign.
     */
    public function createWebChatRoom(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'title' => 'nullable|string|max:120',
            'pet_id' => 'nullable|integer',
            'pet_name' => 'nullable|string|max:120',
            'pet_breed' => 'nullable|string|max:120',
            'pet_location' => 'nullable|string|max:120',
            'species' => 'nullable|string|max:30',
        ]);

        if (!Schema::hasTable('web_chat_campaign')) {
            return response()->json([
                'status' => 'error',
                'message' => 'web_chat_campaign table is missing.',
            ], 500);
        }

        $sessionId = 'room_' . Str::uuid()->toString();
        $state = $this->defaultState();
        $state['user_id'] = (int) $data['user_id'];
        $state['pet_id'] = isset($data['pet_id']) ? (int) $data['pet_id'] : null;
        $state['pet'] = array_filter([
            'name' => $data['pet_name'] ?? null,
            'species' => $data['species'] ?? null,
            'breed' => $data['pet_breed'] ?? null,
            'location' => $data['pet_location'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        DB::table('web_chat_campaign')->insert([
            'session_id' => $sessionId,
            'user_id' => (int) $data['user_id'],
            'pet_id' => isset($data['pet_id']) ? (int) $data['pet_id'] : null,
            'turn' => 0,
            'routing' => 'new_room',
            'severity' => null,
            'score' => 0,
            'pet_name' => $data['pet_name'] ?? null,
            'species' => $data['species'] ?? null,
            'breed' => $data['pet_breed'] ?? null,
            'location' => $data['pet_location'] ?? null,
            'user_message' => null,
            'assistant_message' => null,
            'request_payload_json' => json_encode($this->sanitizePayloadForStorage($data), JSON_UNESCAPED_UNICODE),
            'response_payload_json' => json_encode([
                'event' => 'room_created',
                'title' => $data['title'] ?? 'Symptom Check',
            ], JSON_UNESCAPED_UNICODE),
            'state_payload_json' => json_encode($state, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::put("snoutiq_symptom:{$sessionId}", $state, now()->addMinutes(self::SESSION_TTL_MINUTES));

        return response()->json([
            'status' => 'success',
            'chat_room_token' => $sessionId,
            'context_token' => $sessionId,
            'session_id' => $sessionId,
            'name' => $data['title'] ?? 'Symptom Check',
        ], 201);
    }

    /**
     * GET /api/ask/chat/listRooms?user_id=123
     * Lists Ask rooms from web_chat_campaign grouped by session_id.
     */
    public function listWebChatRooms(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if (!Schema::hasTable('web_chat_campaign')) {
            return response()->json([
                'status' => 'success',
                'rooms' => [],
            ]);
        }

        $rows = DB::table('web_chat_campaign')
            ->where('user_id', (int) $data['user_id'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(max(100, ((int) ($data['limit'] ?? 30)) * 10))
            ->get();

        $rooms = $rows
            ->groupBy('session_id')
            ->map(function ($sessionRows) {
                $latest = $sessionRows->first();
                $ordered = $sessionRows->sortBy('id')->values();
                $firstMessage = $ordered->first(function ($row) {
                    return trim((string) ($row->user_message ?? '')) !== '';
                });
                $latestMessage = $sessionRows->first(function ($row) {
                    return trim((string) ($row->assistant_message ?? '')) !== ''
                        || trim((string) ($row->user_message ?? '')) !== '';
                });

                $title = $this->webChatRoomTitle($ordered, $firstMessage);
                $lastRouting = (string) ($latestMessage->routing ?? $latest->routing ?? '');

                return [
                    'id' => $latest->id,
                    'chat_room_token' => $latest->session_id,
                    'name' => $title,
                    'summary' => mb_substr((string) ($latestMessage->assistant_message ?? ''), 0, 160),
                    'last_emergency_status' => in_array($lastRouting, ['emergency', 'in_clinic'], true)
                        ? strtoupper($lastRouting)
                        : null,
                    'turns' => $ordered->filter(function ($row) {
                        return trim((string) ($row->user_message ?? '')) !== '';
                    })->count(),
                    'created_at' => $ordered->first()->created_at ?? null,
                    'updated_at' => $latest->updated_at ?? $latest->created_at ?? null,
                ];
            })
            ->values()
            ->take((int) ($data['limit'] ?? 30));

        return response()->json([
            'status' => 'success',
            'rooms' => $rooms,
        ]);
    }

    /**
     * GET /api/ask/chat-rooms/{session_id}/chats?user_id=123&sort=asc
     * Returns Ask chat turns from web_chat_campaign.
     */
    public function webChatHistory(Request $request, string $session_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'sort' => 'nullable|in:asc,desc',
        ]);

        if (!Schema::hasTable('web_chat_campaign')) {
            return response()->json([
                'status' => 'error',
                'message' => 'web_chat_campaign table is missing.',
            ], 500);
        }

        $sort = $data['sort'] ?? 'asc';
        $rows = DB::table('web_chat_campaign')
            ->where('session_id', $session_id)
            ->where('user_id', (int) $data['user_id'])
            ->where(function ($query) {
                $query->whereNotNull('user_message')
                    ->orWhereNotNull('assistant_message');
            })
            ->orderBy('id', $sort)
            ->get();

        if ($rows->isEmpty()) {
            $roomExists = DB::table('web_chat_campaign')
                ->where('session_id', $session_id)
                ->where('user_id', (int) $data['user_id'])
                ->exists();

            if (!$roomExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Room not found for this user',
                ], 404);
            }
        }

        $latest = $rows->last();

        return response()->json([
            'status' => 'success',
            'room' => [
                'id' => $latest->id ?? null,
                'chat_room_token' => $session_id,
                'name' => $this->webChatRoomTitle($rows, $rows->first()),
            ],
            'count' => $rows->count(),
            'chats' => $rows->map(function ($row) {
                return [
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'pet_id' => $row->pet_id,
                    'chat_room_token' => $row->session_id,
                    'context_token' => $row->session_id,
                    'question' => $row->user_message,
                    'answer' => $row->assistant_message,
                    'response_tag' => $row->routing,
                    'emergency_status' => in_array((string) $row->routing, ['emergency', 'in_clinic'], true)
                        ? strtoupper((string) $row->routing)
                        : null,
                    'severity' => $row->severity,
                    'score' => $row->score,
                    'pet_name' => $row->pet_name,
                    'pet_breed' => $row->breed,
                    'pet_location' => $row->location,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            })->values(),
        ]);
    }

    /**
     * DELETE /api/ask/chat-rooms/{session_id}
     * Deletes an Ask chat room and all turns from web_chat_campaign.
     */
    public function deleteWebChatRoom(Request $request, string $session_id): \Illuminate\Http\JsonResponse
    {
        if (!Schema::hasTable('web_chat_campaign')) {
            return response()->json([
                'status' => 'error',
                'message' => 'web_chat_campaign table is missing.',
            ], 500);
        }

        $exists = DB::table('web_chat_campaign')
            ->where('session_id', $session_id)
            ->exists();

        if (!$exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found',
            ], 404);
        }

        $deletedRows = DB::table('web_chat_campaign')
            ->where('session_id', $session_id)
            ->delete();

        Cache::forget("snoutiq_symptom:{$session_id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Ask chat room deleted successfully.',
            'deleted' => [
                'chat_room_token' => $session_id,
                'rows_deleted' => $deletedRows,
            ],
        ]);
    }

    private function webChatRoomTitle($rows, $firstMessageRow = null): string
    {
        foreach ($rows as $row) {
            $requestPayload = json_decode((string) ($row->request_payload_json ?? ''), true);
            if (is_array($requestPayload)) {
                $title = trim((string) ($requestPayload['title'] ?? ''));
                if ($title !== '') {
                    return mb_substr($title, 0, 80);
                }
            }

            $responsePayload = json_decode((string) ($row->response_payload_json ?? ''), true);
            if (is_array($responsePayload)) {
                $title = trim((string) ($responsePayload['title'] ?? ''));
                if ($title !== '') {
                    return mb_substr($title, 0, 80);
                }
            }
        }

        $message = trim((string) ($firstMessageRow->user_message ?? ''));
        if ($message !== '') {
            return mb_substr($message, 0, 80);
        }

        return 'Symptom Check';
    }

    // =========================================================================
    // PET PROFILE LOADER
    // =========================================================================

    private function loadPetProfile(array $data): array
    {
        $petId = (int) ($data['pet_id'] ?? 0);

        // If pet_id is 0 (followup without pet_id), try to get from cached session
        if ($petId === 0 && !empty($data['session_id'])) {
            $state = $this->loadConversationState($data['session_id']);
            return is_array($state) && !empty($state['pet'])
                ? $state['pet']
                : $this->defaultPet($data);
        }

        if ($petId > 0) {
            $petSelect = array_values(array_filter([
                'id',
                'user_id',
                'name',
                'breed',
                'pet_age',
                Schema::hasColumn('pets', 'pet_gender') ? 'pet_gender' : null,
                Schema::hasColumn('pets', 'species') ? 'species' : null,
                Schema::hasColumn('pets', 'dob') ? 'dob' : null,
                Schema::hasColumn('pets', 'neutered') ? 'neutered' : null,
                Schema::hasColumn('pets', 'location') ? 'location' : null,
                Schema::hasColumn('pets', 'type') ? 'type' : null,
                Schema::hasColumn('pets', 'pet_type') ? 'pet_type' : null,
                Schema::hasColumn('pets', 'pet_dob') ? 'pet_dob' : null,
                Schema::hasColumn('pets', 'gender') ? 'gender' : null,
            ]));

            $row = DB::table('pets')
                ->select($petSelect)
                ->where('id', $petId)->first();

            if (!$row) return ['error' => 'Pet not found'];
            if (!empty($row->user_id) && !empty($data['user_id']) && (int)$row->user_id !== (int)$data['user_id']) {
                return ['error' => 'Pet does not belong to this user'];
            }

            // Calculate age from dob if available
            $dob = $data['dob'] ?? ($row->dob ?? $row->pet_dob ?? null);
            [$ageVal, $ageUnit] = $this->calcAge($dob);
            $ageStr = $ageVal ? "{$ageVal} {$ageUnit}" : ($row->pet_age ?? 'unknown age');

            return [
                'name'     => $data['pet_name'] ?? $row->name   ?? 'Your pet',
                'species'  => strtolower($data['species']  ?? $row->species ?? $row->pet_type ?? $row->type ?? 'dog'),
                'breed'    => $data['breed']    ?? $row->breed   ?? 'Mixed breed',
                'age'      => $ageStr,
                'dob'      => $dob,
                'sex'      => strtolower($data['sex'] ?? $row->pet_gender ?? $row->gender ?? 'unknown'),
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
            'species'  => strtolower($data['species'] ?? $data['type'] ?? 'dog'),
            'breed'    => $data['breed']    ?? 'Mixed breed',
            'age'      => $ageVal ? "{$ageVal} {$ageUnit}" : 'unknown age',
            'dob'      => $data['dob'] ?? null,
            'sex'      => strtolower($data['sex']      ?? 'unknown'),
            'neutered' => strtolower($data['neutered'] ?? 'unknown'),
            'location' => $data['location'] ?? 'India',
        ];
    }

    private function resolveSessionId(array $data): string
    {
        $sessionId = trim((string) ($data['session_id'] ?? ''));
        if ($sessionId !== '') {
            return $sessionId;
        }

        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        if ($userId > 0) {
            $latest = ChatRoom::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($latest?->chat_room_token) {
                return (string) $latest->chat_room_token;
            }
        }

        return 'room_' . Str::uuid()->toString();
    }

    private function resolveExistingSessionId(?string $sessionId): ?string
    {
        $sessionId = trim((string) ($sessionId ?? ''));
        return $sessionId !== '' ? $sessionId : null;
    }

    private function loadConversationState(string $sessionId): ?array
    {
        $cacheKey = "snoutiq_symptom:{$sessionId}";
        $state = Cache::get($cacheKey);
        if (is_array($state) && !empty($state)) {
            return $state;
        }

        $row = DB::table('web_chat_campaign')
            ->where('session_id', $sessionId)
            ->orderByDesc('id')
            ->first();

        if (!$row) {
            return null;
        }

        $state = json_decode((string) ($row->state_payload_json ?? ''), true);
        if (!is_array($state) || empty($state)) {
            return null;
        }

        Cache::put($cacheKey, $state, now()->addMinutes(self::SESSION_TTL_MINUTES));

        return $state;
    }

    private function normalizeSymptomEntryPayload(array $data): array
    {
        $data['phone'] = $this->cleanOptionalString($this->firstFilled($data, [
            'phone', 'user.phone', 'users.phone',
        ]));
        $data['latitude'] = $this->normalizeCoordinate($this->firstFilled($data, [
            'latitude', 'lat', 'user.latitude', 'user.lat', 'users.latitude', 'users.lat',
        ]), -90, 90);
        $data['longitude'] = $this->normalizeCoordinate($this->firstFilled($data, [
            'longitude', 'long', 'lng', 'lon',
            'user.longitude', 'user.long', 'user.lng', 'user.lon',
            'users.longitude', 'users.long', 'users.lng', 'users.lon',
        ]), -180, 180);
        $data['owner_name'] = $this->cleanOptionalString($this->firstFilled($data, [
            'owner_name', 'pet_owner_name', 'user_name', 'user.name', 'users.name',
        ]));
        $data['pet_name'] = $this->cleanOptionalString($this->firstFilled($data, [
            'pet_name', 'pet.pet_name', 'pets.pet_name', 'pet.name', 'pets.name',
        ])) ?? ($data['pet_name'] ?? null);
        $data['breed'] = $this->cleanOptionalString($this->firstFilled($data, [
            'breed', 'pet.breed', 'pets.breed',
        ])) ?? ($data['breed'] ?? null);
        $data['dob'] = $this->cleanOptionalString($this->firstFilled($data, [
            'dob', 'pet.dob', 'pets.dob',
        ])) ?? ($data['dob'] ?? null);
        $data['type'] = $this->cleanOptionalString($this->firstFilled($data, [
            'type', 'pet.type', 'pets.type',
        ])) ?? ($data['type'] ?? null);
        $data['species'] = $this->cleanOptionalString($this->firstFilled($data, [
            'species', 'pet.species', 'pets.species', 'type', 'pet.type', 'pets.type',
        ])) ?? ($data['species'] ?? null);

        return $data;
    }

    private function persistSymptomEntryUserAndPet(array $data): array
    {
        $resolvedPhone = $this->normalizePhoneNumber($data['phone'] ?? null);
        $rawPhone = trim((string) ($data['phone'] ?? ''));
        $resolvedUserId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $resolvedPetId = isset($data['pet_id']) ? (int) $data['pet_id'] : null;
        [$petImageBlob, $petImageMime] = $this->extractPetImageBlobFromPayload($data);
        $ownerName = trim((string) ($data['owner_name'] ?? ''));
        $petName = trim((string) ($data['pet_name'] ?? ''));
        $breed = trim((string) ($data['breed'] ?? ''));
        $dob = trim((string) ($data['dob'] ?? ''));
        $species = strtolower(trim((string) ($data['species'] ?? $data['type'] ?? '')));
        $latitude = $this->normalizeCoordinate($data['latitude'] ?? null, -90, 90);
        $longitude = $this->normalizeCoordinate($data['longitude'] ?? null, -180, 180);
        $hasOwnerOrPetContext = $ownerName !== ''
            || $petName !== ''
            || $breed !== ''
            || $dob !== ''
            || $species !== ''
            || $latitude !== null
            || $longitude !== null
            || !empty($data['sex'])
            || !empty($data['neutered'])
            || !empty($data['location']);
        $hasPetContext = $petName !== ''
            || $breed !== ''
            || $dob !== ''
            || $species !== ''
            || !empty($data['sex'])
            || !empty($data['neutered'])
            || !empty($data['location'])
            || ($resolvedPetId !== null && $resolvedPetId > 0);

        if (!Schema::hasTable('users') || !Schema::hasTable('pets')) {
            return [
                'user_id' => $resolvedUserId,
                'pet_id' => $resolvedPetId,
            ];
        }

        try {
            $userRow = null;

            if ($resolvedPhone !== null && Schema::hasColumn('users', 'phone')) {
                $userRow = DB::table('users')
                    ->where('phone', $resolvedPhone)
                    ->when($rawPhone !== '' && $rawPhone !== $resolvedPhone, function ($q) use ($rawPhone) {
                        $q->orWhere('phone', $rawPhone);
                    })
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$userRow && $resolvedUserId !== null && $resolvedUserId > 0) {
                $userRow = DB::table('users')->where('id', $resolvedUserId)->first();
            }

            if ($userRow) {
                $resolvedUserId = (int) $userRow->id;
                $userUpdate = [];

                if (Schema::hasColumn('users', 'name') && $ownerName !== '') {
                    $userUpdate['name'] = $ownerName;
                }
                if (Schema::hasColumn('users', 'phone') && $resolvedPhone !== null) {
                    $phoneTakenByOther = DB::table('users')
                        ->where('phone', $resolvedPhone)
                        ->where('id', '!=', $resolvedUserId)
                        ->exists();
                    if (!$phoneTakenByOther) {
                        $userUpdate['phone'] = $resolvedPhone;
                    }
                }
                if (Schema::hasColumn('users', 'pet_name') && $petName !== '') {
                    $userUpdate['pet_name'] = $petName;
                }
                if (Schema::hasColumn('users', 'breed') && $breed !== '') {
                    $userUpdate['breed'] = $breed;
                }
                if (Schema::hasColumn('users', 'latitude') && $latitude !== null) {
                    $userUpdate['latitude'] = $latitude;
                }
                if (Schema::hasColumn('users', 'longitude') && $longitude !== null) {
                    $userUpdate['longitude'] = $longitude;
                }
                if (Schema::hasColumn('users', 'updated_at')) {
                    $userUpdate['updated_at'] = now();
                }

                if ($userUpdate) {
                    DB::table('users')->where('id', $resolvedUserId)->update($userUpdate);
                }
            }

            if (!$userRow && ($resolvedPhone !== null || $hasOwnerOrPetContext)) {
                $userPayload = [];
                $userSeed = $resolvedPhone
                    ?? Str::slug($ownerName !== '' ? $ownerName : ($petName !== '' ? $petName : Str::random(8)));

                if (Schema::hasColumn('users', 'name')) {
                    $userPayload['name'] = $ownerName !== ''
                        ? $ownerName
                        : ($petName !== '' ? ($petName . '\'s parent') : 'Symptom Check User');
                }
                if (Schema::hasColumn('users', 'phone') && $resolvedPhone !== null) {
                    $userPayload['phone'] = $resolvedPhone;
                }
                if (Schema::hasColumn('users', 'email')) {
                    $userPayload['email'] = $this->uniqueSymptomEntryEmail($userSeed);
                }
                if (Schema::hasColumn('users', 'password')) {
                    $userPayload['password'] = bcrypt(Str::random(32));
                }
                if (Schema::hasColumn('users', 'role')) {
                    $userPayload['role'] = 'pet_owner';
                }
                if (Schema::hasColumn('users', 'phone_verified_at') && $resolvedPhone !== null) {
                    $userPayload['phone_verified_at'] = now();
                }
                if (Schema::hasColumn('users', 'pet_name') && $petName !== '') {
                    $userPayload['pet_name'] = $petName;
                }
                if (Schema::hasColumn('users', 'breed') && $breed !== '') {
                    $userPayload['breed'] = $breed;
                }
                if (Schema::hasColumn('users', 'latitude') && $latitude !== null) {
                    $userPayload['latitude'] = $latitude;
                }
                if (Schema::hasColumn('users', 'longitude') && $longitude !== null) {
                    $userPayload['longitude'] = $longitude;
                }
                if (Schema::hasColumn('users', 'created_at')) {
                    $userPayload['created_at'] = now();
                }
                if (Schema::hasColumn('users', 'updated_at')) {
                    $userPayload['updated_at'] = now();
                }

                if ($userPayload) {
                    $newUserId = (int) DB::table('users')->insertGetId($userPayload);
                    if ($newUserId > 0) {
                        $resolvedUserId = $newUserId;
                        $userRow = DB::table('users')->where('id', $newUserId)->first();
                    }
                }
            }

            if ($userRow) {
                $resolvedUserId = (int) $userRow->id;
            }

            if ($resolvedUserId !== null && $resolvedUserId > 0 && $hasPetContext) {
                $petUserColumn = Schema::hasColumn('pets', 'user_id')
                    ? 'user_id'
                    : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);

                if ($petUserColumn !== null) {
                    $petQuery = DB::table('pets')->where($petUserColumn, $resolvedUserId);

                    if ($resolvedPetId !== null && $resolvedPetId > 0) {
                        $petQuery->where('id', $resolvedPetId);
                    } elseif ($petName !== '') {
                        $petQuery->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($petName)]);
                    }

                    $petRow = $petQuery->orderByDesc('id')->first();
                    $petInsertName = $petName !== ''
                        ? $petName
                        : trim((string) (($userRow->pet_name ?? null) ?: 'Pet'));
                    $petPayload = [];

                    if (Schema::hasColumn('pets', 'name') && ($petName !== '' || !$petRow)) {
                        $petPayload['name'] = $petName !== '' ? $petName : $petInsertName;
                    }
                    if (Schema::hasColumn('pets', 'breed') && $breed !== '') {
                        $petPayload['breed'] = $breed;
                    }
                    if ($species !== '') {
                        if (Schema::hasColumn('pets', 'species')) {
                            $petPayload['species'] = $species;
                        }
                        if (Schema::hasColumn('pets', 'pet_type')) {
                            $petPayload['pet_type'] = $species;
                        }
                        if (Schema::hasColumn('pets', 'type')) {
                            $petPayload['type'] = $species;
                        }
                    }
                    if ($dob !== '') {
                        if (Schema::hasColumn('pets', 'dob')) {
                            $petPayload['dob'] = $dob;
                        }
                        if (Schema::hasColumn('pets', 'pet_dob')) {
                            $petPayload['pet_dob'] = $dob;
                        }
                        [$ageVal, $ageUnit] = $this->calcAge($dob);
                        if (Schema::hasColumn('pets', 'pet_age') && is_numeric($ageVal)) {
                            $petPayload['pet_age'] = str_contains((string) $ageUnit, 'year') ? (int) $ageVal : 0;
                        }
                    }
                    if (Schema::hasColumn('pets', 'pet_gender') && !empty($data['sex'])) {
                        $petPayload['pet_gender'] = strtolower((string) $data['sex']);
                    }
                    if (Schema::hasColumn('pets', 'gender') && !empty($data['sex'])) {
                        $petPayload['gender'] = strtolower((string) $data['sex']);
                    }
                    if (Schema::hasColumn('pets', 'location') && !empty($data['location'])) {
                        $petPayload['location'] = (string) $data['location'];
                    }
                    if (Schema::hasColumn('pets', 'neutered') && !empty($data['neutered'])) {
                        $petPayload['neutered'] = strtolower((string) $data['neutered']);
                    }
                    if (Schema::hasColumn('pets', 'updated_at')) {
                        $petPayload['updated_at'] = now();
                    }
                    if ($petImageBlob !== null && Schema::hasColumn('pets', 'pet_doc2_blob')) {
                        $petPayload['pet_doc2_blob'] = $petImageBlob;
                    }
                    if ($petImageMime !== null && Schema::hasColumn('pets', 'pet_doc2_mime')) {
                        $petPayload['pet_doc2_mime'] = $petImageMime;
                    }

                    if ($petRow) {
                        if ($petPayload) {
                            DB::table('pets')->where('id', $petRow->id)->update($petPayload);
                        }
                        $resolvedPetId = (int) $petRow->id;
                    } else {
                        $insertPayload = array_merge([$petUserColumn => $resolvedUserId], $petPayload);
                        if (Schema::hasColumn('pets', 'created_at')) {
                            $insertPayload['created_at'] = now();
                        }
                        if (Schema::hasColumn('pets', 'updated_at')) {
                            $insertPayload['updated_at'] = now();
                        }
                        if ($insertPayload) {
                            $resolvedPetId = (int) DB::table('pets')->insertGetId($insertPayload);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('symptom_check.user_pet_persistence_failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'user_id' => $resolvedUserId,
            'pet_id' => $resolvedPetId,
        ];
    }

    private function extractPetImageBlobFromPayload(array $data): array
    {
        if (!empty($data['image_base64'])) {
            $imageB64 = trim((string) $data['image_base64']);
            if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/s', $imageB64, $matches)) {
                $mime = $matches[1];
                $imageB64 = $matches[2];
            } else {
                $mime = trim((string) ($data['image_mime'] ?? 'image/jpeg'));
            }

            $decoded = base64_decode($imageB64, true);
            if ($decoded !== false && $decoded !== '') {
                return [$decoded, $mime !== '' ? $mime : 'image/jpeg'];
            }
        }

        if (!empty($data['image']) && is_object($data['image']) && method_exists($data['image'], 'isValid') && $data['image']->isValid()) {
            $raw = @file_get_contents($data['image']->getRealPath());
            if ($raw !== false && $raw !== '') {
                return [
                    $raw,
                    method_exists($data['image'], 'getMimeType') ? ($data['image']->getMimeType() ?: 'image/jpeg') : 'image/jpeg',
                ];
            }
        }

        return [null, null];
    }

    private function firstFilled(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($data, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function cleanOptionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $clean = trim((string) $value);
        return $clean === '' ? null : $clean;
    }

    private function normalizePhoneNumber(mixed $phone): ?string
    {
        if (!is_scalar($phone)) {
            return null;
        }

        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
            return substr($digits, 0, 12);
        }

        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        return $digits;
    }

    private function normalizeCoordinate(mixed $value, float $min, float $max): ?float
    {
        if (!is_scalar($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || !is_numeric($raw)) {
            return null;
        }

        $number = (float) $raw;
        if ($number < $min || $number > $max) {
            return null;
        }

        return round($number, 7);
    }

    private function uniqueSymptomEntryEmail(string $seed): string
    {
        $emailLocal = preg_replace('/[^a-z0-9]/i', '', $seed) ?: (string) now()->timestamp;
        $emailCandidate = "symptom_{$emailLocal}@snoutiq.local";
        $suffix = 1;

        while (DB::table('users')->where('email', $emailCandidate)->exists()) {
            $emailCandidate = "symptom_{$emailLocal}_{$suffix}@snoutiq.local";
            $suffix++;
        }

        return $emailCandidate;
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
        $whatWeThink = "{$petName} has red-flag symptoms that can become life-threatening very quickly.";
        if ($flag) {
            $whatWeThink .= ' The biggest concern right now is ' . trim((string) $flag) . '.';
        }

        return [
            'message'          => "{$petName} needs emergency veterinary care right now. Do not wait — this situation can become life-threatening very quickly. Go to the nearest vet clinic or government veterinary hospital immediately.",
            'what_we_think_is_happening' => $whatWeThink,
            'do_now'           => 'Go to the nearest vet or emergency animal hospital NOW. Call ahead if possible.',
            'time_sensitivity' => 'Go now — every minute matters',
            'safe_to_do_while_waiting' => [
                "Keep {$petName} calm, quiet, and as still as possible while leaving for the vet.",
                'Do not give food, treats, or any human medicine unless a vet has specifically told you to.',
                'If a toxin, medicine, or unusual food may be involved, take the packet or a photo of it with you.',
            ],
            'what_to_watch'    => [
                'Keep ' . $petName . ' calm and still while travelling',
                'Do not give food, water, or any medications',
                'Note any changes in breathing or consciousness',
            ],
            'be_ready_to_tell_vet' => 'Tell the vet exactly when this started and whether there has been collapse, breathing change, bloating, repeated vomiting, or seizure activity on the way.',
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
        array $pet, string $message, array $history, array $followUpHistory,
        ?string $imageB64, string $imageMime, int $score
    ): array {
        $historyStr = $this->formatHistoryForPrompt($history);
        $followUpHistoryStr = $this->formatFollowUpHistoryForPrompt($followUpHistory);
        $petStr     = $this->petToString($pet);

        $prompt =
            self::INDIA_CONTEXT . "\n\n" .
            "PET: {$petStr}\n\n" .
            ($historyStr ? "CONVERSATION SO FAR:\n{$historyStr}\n\n" : '') .
            ($followUpHistoryStr ? "FOLLOW-UP ANSWERS SO FAR:\n{$followUpHistoryStr}\n\n" : '') .
            "CURRENT MESSAGE: " . mb_substr($message, 0, self::MAX_INPUT_CHARS) . "\n" .
            "EVIDENCE SCORE SO FAR: {$score}/10\n\n" .
            "TASK: Assess this pet's situation and output routing decision.\n\n" .
            "Use the pet location if provided. If no location is available, assume India. " .
            "Possible causes should be short, practical, and prioritized for common Indian veterinary presentations when reasonable. " .
            "india_context_note must be a single useful India- or location-aware line, not a disclaimer.\n\n" .
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

    private function callGeminiResponse(
        array $pet,
        string $message,
        string $routing,
        array $triage,
        array $history = [],
        array $followUpHistory = [],
        ?string $imageB64 = null,
        string $imageMime = 'image/jpeg'
    ): array
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
        $followUpHistoryStr = $this->formatFollowUpHistoryForPrompt($followUpHistory);
        $imageObservation = trim((string) ($triage['image_observation'] ?? ''));

        $prompt =
            "You are Snoutiq, a caring pet health assistant. " .
            "You speak to worried pet parents in warm, simple language. " .
            "You never diagnose. You never say you are an AI. " .
            "You always give one specific next action. No medical jargon. " .
            "Make the response feel tailored to this exact conversation, not like a reusable template.\n\n" .
            "Pet: {$pet['name']} ({$pet['species']}, {$pet['breed']}, {$pet['age']})\n" .
            ($historyStr ? "Recent conversation:\n{$historyStr}\n" : '') .
            ($followUpHistoryStr ? "Follow-up answers already collected:\n{$followUpHistoryStr}\n" : '') .
            "Message from owner: " . mb_substr($message, 0, 300) . "\n" .
            "Routing decision: {$routing}\n" .
            ($causes ? "Possible causes (do NOT state as diagnosis): {$causes}\n" : '') .
            ($redFlags ? "Red flags already seen: {$redFlags}\n" : '') .
            ($imageObservation !== '' ? "Image observation: {$imageObservation}\n" : '') .
            "India context: " . ($triage['india_context_note'] ?? '') . "\n\n" .
            "Instruction: " . ($routingInstructions[$routing] ?? $routingInstructions['video_consult']) . "\n\n" .
            ($imageB64 ? "An image is attached. Use only visible findings from the image and keep observations modest and concrete.\n" : '') .
            "The `message` field must sound natural and should reference what the owner is seeing right now. " .
            "If there is recent conversation history, continue from it instead of restarting.\n\n" .
            "Make `safe_to_do_while_waiting` a list of 3-4 very safe, low-risk steps the owner can do while waiting. " .
            "These must be practical, species-aware, and should never include medication doses, home remedies, or anything risky. " .
            "It is fine to include a warning like do not give human medication.\n" .
            "Make `what_to_watch` a list of 3-4 specific triggers that would make the owner upgrade to clinic care or emergency care. " .
            "Make `be_ready_to_tell_vet` one concise sentence containing the most useful thing to tell the vet next.\n\n" .
            "If routing is video_consult or in_clinic, include ONE focused follow-up question that would meaningfully change the advice. " .
            "Give 2-3 short answer choices. If routing is emergency, set follow_up_question to null.\n\n" .
            "Return ONLY this JSON:\n" .
            '{"message":"Main response 2-4 sentences plain language",' .
            '"what_we_think_is_happening":"2-4 sentence explanation for the section titled What we think is happening",' .
            '"diagnosis_summary":"One short sentence with preliminary likely causes, never a confirmed diagnosis",' .
            '"do_now":"One immediate action",' .
            '"time_sensitivity":"e.g. Go now / Within 2-4 hours / If not better in 24 hours",' .
            '"safe_to_do_while_waiting":["step1","step2","step3"],' .
            '"what_to_watch":["sign1","sign2","sign3"],' .
            '"be_ready_to_tell_vet":"One concise sentence for the section titled Be ready to tell the vet",' .
            '"follow_up_question":{"label":"One question to narrow this down","question":"One focused question","options":["Option 1","Option 2","Option 3"]}}';

        $raw = $imageB64
            ? $this->geminiCallWithImage($prompt, $imageB64, $imageMime, self::RESPONSE_MAX_TOKENS)
            : $this->geminiCall($prompt, self::RESPONSE_MAX_TOKENS);
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
        if (mb_strlen($prompt) > self::MAX_PROMPT_CHARS) {
            $prompt = mb_substr($prompt, 0, self::MAX_PROMPT_CHARS);
        }

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

    private function resolveImagePayload(Request $request, array $data): array
    {
        $imageB64 = trim((string) ($data['image_base64'] ?? ''));
        $imageMime = trim((string) ($data['image_mime'] ?? 'image/jpeg'));

        if ($imageB64 !== '') {
            return [$imageB64, $imageMime !== '' ? $imageMime : 'image/jpeg'];
        }

        if (!$request->hasFile('image')) {
            return [null, 'image/jpeg'];
        }

        $file = $request->file('image');
        if (!$file || !$file->isValid()) {
            return [null, 'image/jpeg'];
        }

        $raw = @file_get_contents($file->getRealPath());
        if ($raw === false || $raw === '') {
            return [null, 'image/jpeg'];
        }

        return [
            base64_encode($raw),
            $file->getMimeType() ?: 'image/jpeg',
        ];
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

        $whatWeThink = $this->cleanAssistantText((string) ($payload['what_we_think_is_happening'] ?? ''));
        if ($whatWeThink === '') {
            $whatWeThink = $message;
        }
        if ($whatWeThink === '') {
            $whatWeThink = $this->buildDynamicFallbackMessage($pet, $routing, $ownerMessage, $triage);
        }
        if ($message === '') {
            $message = $whatWeThink;
        }

        $diagnosisSummary = $this->cleanAssistantText((string) ($payload['diagnosis_summary'] ?? ''));
        if ($diagnosisSummary === '') {
            $diagnosisSummary = $this->buildDiagnosisSummary($triage, $ownerMessage, $pet);
        }
        $message = $this->appendDiagnosisToMessage($message, $diagnosisSummary);
        $whatWeThink = $this->appendDiagnosisToMessage($whatWeThink, $diagnosisSummary);

        $doNow = $this->cleanAssistantText((string) ($payload['do_now'] ?? ''));
        if ($doNow === '') {
            $doNow = $this->defaultDoNow($pet, $routing);
        }

        $timeSensitivity = $this->cleanAssistantText((string) ($payload['time_sensitivity'] ?? ''));
        if ($timeSensitivity === '') {
            $timeSensitivity = $this->defaultTimeSensitivity($routing, $triage);
        }

        $safeToDoWhileWaiting = $this->normalizeSafeToDoWhileWaiting(
            is_array($payload['safe_to_do_while_waiting'] ?? null) ? $payload['safe_to_do_while_waiting'] : [],
            $routing,
            $pet,
            $ownerMessage,
            $triage
        );

        $whatToWatch = $this->normalizeWhatToWatch(
            is_array($payload['what_to_watch'] ?? null) ? $payload['what_to_watch'] : [],
            $routing,
            $pet,
            $ownerMessage,
            $triage
        );

        $beReadyToTellVet = $this->cleanAssistantText((string) (
            $payload['be_ready_to_tell_vet']
            ?? $payload['vet_question']
            ?? ''
        ));
        if ($beReadyToTellVet === '') {
            $beReadyToTellVet = $this->defaultBeReadyToTellVet($pet, $routing, $ownerMessage, $triage);
        }

        $followUpQuestion = $this->normalizeFollowUpQuestion(
            $payload['follow_up_question'] ?? null,
            $routing,
            $pet,
            $ownerMessage,
            $triage
        );

        return [
            'message' => $message,
            'what_we_think_is_happening' => $whatWeThink,
            'diagnosis_summary' => $diagnosisSummary,
            'do_now' => $doNow,
            'time_sensitivity' => $timeSensitivity,
            'safe_to_do_while_waiting' => $safeToDoWhileWaiting,
            'what_to_watch' => $whatToWatch,
            'be_ready_to_tell_vet' => $beReadyToTellVet,
            'follow_up_question' => $followUpQuestion,
        ];
    }

    private function recoverStructuredResponse(string $raw): ?array
    {
        $message = $this->extractJsonStringField($raw, 'message');
        $whatWeThink = $this->extractJsonStringField($raw, 'what_we_think_is_happening');
        $doNow = $this->extractJsonStringField($raw, 'do_now');
        $timeSensitivity = $this->extractJsonStringField($raw, 'time_sensitivity');
        $safeToDoWhileWaiting = $this->extractJsonStringArrayField($raw, 'safe_to_do_while_waiting');
        $whatToWatch = $this->extractJsonStringArrayField($raw, 'what_to_watch');
        $beReadyToTellVet = $this->extractJsonStringField($raw, 'be_ready_to_tell_vet');

        $payload = array_filter([
            'message' => $message,
            'what_we_think_is_happening' => $whatWeThink,
            'do_now' => $doNow,
            'time_sensitivity' => $timeSensitivity,
            'safe_to_do_while_waiting' => $safeToDoWhileWaiting,
            'what_to_watch' => $whatToWatch,
            'be_ready_to_tell_vet' => $beReadyToTellVet,
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

    private function normalizeFollowUpQuestion(
        mixed $value,
        string $routing,
        array $pet,
        string $ownerMessage,
        array $triage
    ): ?array {
        if (!$this->shouldAskFollowUpQuestion($routing)) {
            return null;
        }

        if (is_array($value)) {
            $label = $this->cleanAssistantText((string) ($value['label'] ?? 'One question to narrow this down'));
            $question = $this->cleanAssistantText((string) ($value['question'] ?? ''));
            $options = array_values(array_filter(array_map(
                fn ($item) => $this->cleanAssistantText((string) $item),
                is_array($value['options'] ?? null) ? $value['options'] : []
            )));

            $options = array_slice(array_values(array_unique($options)), 0, 3);

            if ($question !== '' && count($options) >= 2) {
                return [
                    'label' => $label !== '' ? $label : 'One question to narrow this down',
                    'question' => $question,
                    'options' => $options,
                ];
            }
        }

        return $this->defaultFollowUpQuestion($pet, $routing, $ownerMessage, $triage);
    }

    private function shouldAskFollowUpQuestion(string $routing): bool
    {
        return in_array($routing, ['video_consult', 'in_clinic'], true);
    }

    private function defaultFollowUpQuestion(array $pet, string $routing, string $ownerMessage, array $triage): ?array
    {
        if (!$this->shouldAskFollowUpQuestion($routing)) {
            return null;
        }

        $text = mb_strtolower($this->cleanAssistantText($ownerMessage));

        $question = [
            'label' => 'One question to narrow this down',
            'question' => 'Did this start suddenly in the last 24 hours, or has it been building over a few days?',
            'options' => [
                'Started suddenly in the last 24 hours',
                'Has been building over 2-3 days',
                "I'm not sure",
            ],
        ];

        if (preg_match('/\b(vomit|vomiting|throwing up|diarrh|not eating|no appetite|letharg|fever)\b/', $text)) {
            $question['question'] = sprintf(
                'Has %s been outdoors recently, or could %s have eaten anything unusual in the past 3 days?',
                $pet['name'] ?? 'your pet',
                (($pet['sex'] ?? '') === 'female') ? 'she' : 'he'
            );
            $question['options'] = [
                'Yes — outdoors / may have eaten something unusual',
                'No — stayed home, nothing unusual',
                "I'm not sure",
            ];
        } elseif (preg_match('/\b(limp|limping|swollen|swelling|paw|leg|joint|yelp|pain)\b/', $text)) {
            $question['question'] = sprintf(
                'Did the problem start suddenly after activity or a fall, or did it come on gradually over several days for %s?',
                $pet['name'] ?? 'your pet'
            );
            $question['options'] = [
                'Suddenly — after activity or a fall',
                'Gradually — no specific incident',
                "I'm not sure",
            ];
        } elseif (preg_match('/\b(cough|coughing|sneez|runny nose|nasal|eye discharge|breath)\b/', $text)) {
            $question['question'] = sprintf(
                'Is %s having only mild upper-respiratory signs, or are there also breathing changes or visible discharge?',
                $pet['name'] ?? 'your pet'
            );
            $question['options'] = [
                'Only mild sneezing / cough',
                'There is discharge or breathing change too',
                "I'm not sure",
            ];
        } elseif (preg_match('/\b(itch|itching|scratch|scratching|rash|skin|ear|ears)\b/', $text)) {
            $question['question'] = sprintf(
                'Did this flare start after any new food, shampoo, grooming, or flea/tick exposure for %s?',
                $pet['name'] ?? 'your pet'
            );
            $question['options'] = [
                'Yes — new product / possible flea-tick exposure',
                'No — nothing new that I know of',
                "I'm not sure",
            ];
        } elseif (preg_match('/\b(urine|urinating|pee|straining)\b/', $text)) {
            $question['question'] = sprintf(
                'Has %s passed normal urine recently, or is there straining / very little urine coming out?',
                $pet['name'] ?? 'your pet'
            );
            $question['options'] = [
                'Normal urine has passed',
                'Straining or very little / no urine',
                "I'm not sure",
            ];
        } elseif (!empty($triage['red_flags_present'])) {
            $question['question'] = sprintf(
                'Since this started, has %s stayed about the same, or is %s clearly getting worse?',
                $pet['name'] ?? 'your pet',
                (($pet['sex'] ?? '') === 'female') ? 'she' : 'he'
            );
            $question['options'] = [
                'About the same',
                'Clearly getting worse',
                "I'm not sure",
            ];
        }

        return $question;
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

    private function normalizePossibleCauses(mixed $value, string $ownerMessage, array $pet = []): array
    {
        $causes = [];

        if (is_array($value)) {
            $causes = array_values(array_filter(array_map(
                fn ($cause) => $this->cleanAssistantText((string) $cause),
                $value
            )));
        }

        if (count($causes) < 3) {
            $causes = array_merge($causes, $this->inferPossibleCausesFromMessage($ownerMessage, $pet));
        }

        return array_slice(array_values(array_unique($causes)), 0, 4);
    }

    private function normalizeIndiaContextNote(mixed $value, array $pet, string $ownerMessage, string $routing = ''): string
    {
        $note = $this->cleanAssistantText((string) $value);
        if ($note !== '') {
            return $note;
        }

        return $this->defaultIndiaContextNote($pet, $ownerMessage, $routing);
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

    private function normalizeSafeToDoWhileWaiting(array $items, string $routing, array $pet, string $ownerMessage, array $triage): array
    {
        $clean = [];
        foreach ($items as $item) {
            $text = $this->cleanAssistantText((string) $item);
            if ($text === '' || !$this->isSafeWaitingAdvice($text)) {
                continue;
            }
            $clean[] = $text;
        }

        $defaults = $this->defaultSafeToDoWhileWaiting($routing, $pet, $ownerMessage, $triage);
        return array_slice(array_values(array_unique(array_merge($clean, $defaults))), 0, 4);
    }

    private function isSafeWaitingAdvice(string $text): bool
    {
        $lower = mb_strtolower($text);

        if (preg_match('/\b(force[\s-]?feed|induce vomiting|home remedy|turmeric|essential oil|coconut oil|alcohol|dose|dosage|tablet|capsule|syrup|ml|mg)\b/', $lower)) {
            return false;
        }

        if (
            preg_match('/\b(paracetamol|crocin|dolo|ibuprofen|aspirin|diclofenac|antibiotic|steroid|painkiller)\b/', $lower)
            && !preg_match('/\b(do not|don\'t|avoid|never|toxic|no human medication)\b/', $lower)
        ) {
            return false;
        }

        return true;
    }

    private function defaultSafeToDoWhileWaiting(string $routing, array $pet, string $ownerMessage, array $triage): array
    {
        $petName = trim((string) ($pet['name'] ?? 'your pet')) ?: 'your pet';
        $pronoun = $this->subjectPronoun($pet);
        $species = mb_strtolower((string) ($pet['species'] ?? 'pet'));
        $text = mb_strtolower($this->cleanAssistantText($ownerMessage));

        if ($routing === 'emergency') {
            return [
                "Keep {$petName} calm, quiet, and as still as possible during travel.",
                'Do not give food, treats, or any human medicine unless a vet has specifically told you to.',
                'If poisoning or a foreign item is possible, take the packet, wrapper, or a photo with you.',
            ];
        }

        if (preg_match('/\b(vomit|vomiting|throwing up|diarrh|not eating|no appetite|letharg|fever)\b/', $text)) {
            return [
                "Offer small sips of water every 20-30 minutes if {$petName} can keep it down; do not force-feed.",
                "Note the last time {$petName} passed urine, stool, or vomited and what it looked like.",
                'Do not give paracetamol, ibuprofen, antibiotics, or any human medicine without veterinary advice.',
            ];
        }

        if (preg_match('/\b(limp|limping|swollen|swelling|paw|leg|joint|yelp|pain)\b/', $text)) {
            return [
                "Restrict running, stairs, jumping, and rough play until {$petName} is examined.",
                "Check the paw pads and nails gently for a thorn, cut, or lodged object, but stop if {$pronoun} is painful.",
                'Do not massage the area or give human painkillers while waiting.',
            ];
        }

        if (preg_match('/\b(cough|coughing|sneez|runny nose|nasal|eye discharge|breath)\b/', $text)) {
            $speciesSpecific = $species === 'cat'
                ? "Warm {$petName}'s food slightly so smell encourages eating if the nose feels blocked."
                : "Keep {$petName} rested in a cool, well-ventilated room and avoid exercise until breathing is reassessed.";

            return [
                'Keep the room calm, cool, and well ventilated; avoid smoke, dust, incense, or strong sprays nearby.',
                'Gently wipe visible eye or nose discharge with clean lukewarm water if needed.',
                $speciesSpecific,
                'Do not give cough syrups, steam directly to the face, or any human cold medicine.',
            ];
        }

        if (preg_match('/\b(itch|itching|scratch|scratching|rash|skin|ear|ears)\b/', $text)) {
            return [
                "Use a cone or gentle distraction if needed so {$petName} does not keep chewing or scratching the same area.",
                'Keep the skin dry and note any new shampoo, treats, medicines, or flea exposure from the last few days.',
                'Do not apply human creams, antiseptics, or pain-relief gels unless a vet has advised it.',
            ];
        }

        if (preg_match('/\b(urine|urinating|pee|straining)\b/', $text)) {
            return [
                "Keep water available and give {$petName} easy access to the usual toilet spot or litter tray.",
                "Note the last normal urination, whether only drops are coming out, and whether there is any blood.",
                'Do not squeeze the belly or give human pain medicine while waiting.',
            ];
        }

        $hours = isset($triage['safe_to_wait_hours']) ? max(0, (int) $triage['safe_to_wait_hours']) : 0;
        $timeNote = $hours > 0
            ? "If {$petName} is not clearly improving within {$hours} hours, upgrade to a vet consult."
            : "If {$petName} is getting worse, upgrade to a vet consult rather than waiting.";

        return [
            "Keep {$petName} rested and offer water normally unless drinking seems to worsen symptoms.",
            'Take a short video or photo of the symptom if it comes and goes, so the vet can see the exact pattern.',
            'Avoid human medication or home remedies unless a vet has specifically recommended them.',
            $timeNote,
        ];
    }

    private function normalizeWhatToWatch(array $items, string $routing, array $pet, string $ownerMessage, array $triage): array
    {
        $clean = array_values(array_filter(array_map(
            fn ($item) => $this->cleanAssistantText((string) $item),
            $items
        )));

        $defaults = $this->defaultWhatToWatch($routing, $triage, $pet, $ownerMessage);
        return array_slice(array_values(array_unique(array_merge($clean, $defaults))), 0, 4);
    }

    private function defaultWhatToWatch(string $routing, array $triage, array $pet = [], string $ownerMessage = ''): array
    {
        $petName = trim((string) ($pet['name'] ?? 'your pet')) ?: 'your pet';
        $pronoun = $this->subjectPronoun($pet);
        $text = mb_strtolower($this->cleanAssistantText($ownerMessage));
        $items = [];

        foreach (array_slice($triage['red_flags_present'] ?? [], 0, 2) as $flag) {
            $flagText = $this->cleanAssistantText((string) $flag);
            if ($flagText !== '') {
                $items[] = 'Any sign of ' . $flagText;
            }
        }

        $symptomSpecific = [];
        if (preg_match('/\b(vomit|vomiting|throwing up|diarrh|not eating|no appetite|letharg|fever)\b/', $text)) {
            $symptomSpecific = [
                "If {$petName} vomits more than twice in the next 6 hours, upgrade to a same-day clinic visit.",
                "If gums turn pale, white, or yellow at any point, {$petName} should be seen the same day.",
                "If {$pronoun} has not urinated in 12+ hours, mention it urgently because dehydration or organ stress may be developing.",
                "Any of the above with rapid or laboured breathing means skip video and go to emergency care immediately.",
            ];
        } elseif (preg_match('/\b(limp|limping|swollen|swelling|paw|leg|joint|yelp|pain)\b/', $text)) {
            $symptomSpecific = [
                "If swelling spreads quickly or the leg becomes hot and very painful, arrange an urgent same-day clinic visit.",
                "If {$petName} stops bearing weight on the leg, do not wait for home monitoring.",
                "If {$pronoun} becomes lethargic or stops eating along with the pain, tell the clinic the situation has escalated.",
                "Any of the above with crying in pain or breathing change means emergency care is safer.",
            ];
        } elseif (preg_match('/\b(cough|coughing|sneez|runny nose|nasal|eye discharge|breath)\b/', $text)) {
            $symptomSpecific = [
                "If breathing becomes fast, open-mouth, noisy, or laboured, go straight to a clinic or emergency vet.",
                "If yellow or green discharge appears from the nose or eyes, book a same-day consult.",
                "If appetite drops or energy falls noticeably, the situation is no longer a simple monitor-at-home case.",
                "Any blue, grey, or very pale gums should be treated as an emergency.",
            ];
        } elseif (preg_match('/\b(itch|itching|scratch|scratching|rash|skin|ear|ears)\b/', $text)) {
            $symptomSpecific = [
                "If the rash spreads quickly, the skin starts oozing, or there is facial swelling, book a same-day vet visit.",
                "If {$petName} keeps scratching nonstop or cries when touched, clinic care is more appropriate than monitoring.",
                "If vomiting, low energy, or breathing change appears along with the skin flare, seek urgent care.",
                "Any swelling around the face or difficulty breathing is an emergency.",
            ];
        } elseif (preg_match('/\b(urine|urinating|pee|straining)\b/', $text)) {
            $symptomSpecific = [
                "If {$petName} is straining but only passing drops, this can become urgent quickly.",
                "If no urine is passed for several hours despite repeated attempts, go to a clinic immediately.",
                "If there is blood in the urine or obvious pain, same-day veterinary care is needed.",
                "Any straining with vomiting, collapse, or severe distress is an emergency.",
            ];
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

        return array_slice(array_values(array_unique(array_merge($symptomSpecific, $items, $defaults))), 0, 4);
    }

    private function defaultBeReadyToTellVet(array $pet, string $routing, string $ownerMessage, array $triage): string
    {
        $petName = trim((string) ($pet['name'] ?? 'your pet')) ?: 'your pet';
        $text = mb_strtolower($this->cleanAssistantText($ownerMessage));

        if (preg_match('/\b(vomit|vomiting|throwing up|diarrh|not eating|no appetite|letharg|fever)\b/', $text)) {
            return "Be ready to tell the vet when {$petName} last ate a completely normal meal, and whether stool, urine, and vomiting have looked normal in the last 24 hours.";
        }
        if (preg_match('/\b(limp|limping|swollen|swelling|paw|leg|joint|yelp|pain)\b/', $text)) {
            return "Be ready to tell the vet when the limping started, whether {$petName} can bear any weight, and whether there was a fall, rough play, or paw injury first.";
        }
        if (preg_match('/\b(cough|coughing|sneez|runny nose|nasal|eye discharge|breath)\b/', $text)) {
            return "Be ready to tell the vet whether there is any eye or nasal discharge, how breathing looks at rest, and whether {$petName} has been around other pets recently.";
        }
        if (preg_match('/\b(itch|itching|scratch|scratching|rash|skin|ear|ears)\b/', $text)) {
            return "Be ready to tell the vet when the itching started, whether fleas are visible, and if there was any new food, shampoo, medicine, or grooming before the flare.";
        }
        if (preg_match('/\b(urine|urinating|pee|straining)\b/', $text)) {
            return "Be ready to tell the vet when {$petName} last passed a normal amount of urine and whether there has been straining, blood, or obvious pain.";
        }
        if ($routing === 'emergency') {
            return "Be ready to tell the vet exactly when this started and whether there has been collapse, vomiting, breathing change, seizure activity, or sudden worsening.";
        }

        return "Be ready to tell the vet when this started, what changed first, and whether appetite, urination, stool, and energy have otherwise been normal.";
    }

    private function defaultIndiaContextNote(array $pet, string $ownerMessage, string $routing = ''): string
    {
        $text = mb_strtolower($this->cleanAssistantText($ownerMessage));
        $location = trim((string) ($pet['location'] ?? 'India'));
        $locationLower = mb_strtolower($location);

        $region = 'India';
        if (preg_match('/\b(delhi|gurgaon|gurugram|noida|ghaziabad|faridabad|ncr)\b/', $locationLower)) {
            $region = 'Delhi NCR';
        } elseif (preg_match('/\bmumbai\b/', $locationLower)) {
            $region = 'Mumbai';
        } elseif (preg_match('/\bbengaluru|bangalore\b/', $locationLower)) {
            $region = 'Bengaluru';
        } elseif (preg_match('/\bpune\b/', $locationLower)) {
            $region = 'Pune';
        } elseif ($location !== '') {
            $region = $location;
        }

        $isSpecificRegion = $region !== 'India';
        $prefix = $isSpecificRegion ? "In {$region}" : 'Across India';

        if (preg_match('/\b(not eating|no appetite|loss of appetite|letharg|fever)\b/', $text) && (($pet['species'] ?? '') === 'dog')) {
            return $isSpecificRegion
                ? "Tick fever (Ehrlichia canis) is year-round in {$region} and frequently causes sudden appetite loss with lethargy — often missed because fever can be intermittent. Worth ruling out early."
                : 'Tick fever (Ehrlichia canis) is common year-round across India and can cause sudden appetite loss with lethargy, sometimes with only intermittent fever early on.';
        }
        if (preg_match('/\b(vomit|vomiting|throwing up|diarrh)\b/', $text)) {
            return "{$prefix}, sudden vomiting or diarrhea is often linked to dietary indiscretion, food contamination, or a gastrointestinal infection, so hydration can worsen faster than owners expect.";
        }
        if (preg_match('/\b(cough|coughing|sneez|runny nose|nasal|eye discharge|breath)\b/', $text)) {
            return "{$prefix}, seasonal respiratory infections and dust exposure are common reasons mild sneezing or coughing can flare, especially in flat-faced pets.";
        }
        if (preg_match('/\b(itch|itching|scratch|scratching|rash|skin|ear|ears)\b/', $text)) {
            return "{$prefix}, flea and tick exposure plus ringworm are common reasons itching, patchy hair loss, and skin irritation need treatment rather than simple home care.";
        }
        if (preg_match('/\b(urine|urinating|pee|straining)\b/', $text)) {
            return "{$prefix}, dehydration and urinary crystal disease are common problems, and straining with little urine can become urgent quickly.";
        }
        if (preg_match('/\b(limp|limping|swollen|swelling|paw|leg|joint|yelp|pain)\b/', $text)) {
            return "{$prefix}, paw injuries, soft-tissue strains, and tick-borne fever can all present with limping or reluctance to walk, so worsening pain is worth checking early.";
        }
        if ($routing === 'emergency') {
            return "{$prefix}, government veterinary hospitals and teaching hospitals can be useful low-cost options if private emergency care is far away.";
        }

        return "{$prefix}, tick-borne disease, dehydration, and gastrointestinal infections are common reasons pets worsen faster than owners expect, so close monitoring matters.";
    }

    private function buildDiagnosisSummary(array $triage, string $ownerMessage = '', array $pet = []): string
    {
        $causes = array_values(array_filter(array_map(
            fn ($cause) => $this->cleanAssistantText((string) $cause),
            array_slice($triage['possible_causes'] ?? [], 0, 3)
        )));

        if (!$causes) {
            $causes = $this->inferPossibleCausesFromMessage($ownerMessage, $pet);
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

    private function inferPossibleCausesFromMessage(string $ownerMessage, array $pet = []): array
    {
        $text = mb_strtolower($this->cleanAssistantText($ownerMessage));
        $species = strtolower((string) ($pet['species'] ?? ''));
        if ($text === '') {
            return [];
        }

        if (preg_match('/\b(not eating|no appetite|loss of appetite|letharg|lethargic)\b/', $text) && $species === 'dog') {
            return ['tick fever (Ehrlichia)', 'gastroenteritis', 'dietary indiscretion', 'early infection / fever'];
        }

        $rules = [
            [['vomit', 'vomiting', 'throwing up'], ['stomach upset', 'dietary indiscretion', 'a gastrointestinal infection']],
            [['diarrhea', 'diarrhoea', 'loose motion', 'loose stool'], ['stomach upset', 'diet change', 'a gastrointestinal infection']],
            [['itch', 'itching', 'scratching', 'skin', 'rash'], ['an allergy flare', 'a skin infection', 'fleas or ticks']],
            [['limp', 'limping', 'paw pain', 'leg pain'], ['a soft tissue injury', 'a paw injury', 'joint pain']],
            [['cough', 'coughing', 'sneeze', 'sneezing', 'runny nose'], $species === 'cat' ? ['an upper respiratory infection', 'viral irritation', 'an allergy flare'] : ['respiratory irritation', 'an infection', 'an allergy']],
            [['not eating', 'no appetite', 'loss of appetite', 'lethargic', 'lethargy'], ['fever', 'stomach upset', 'pain or dehydration']],
            [['urine', 'urinating', 'pee', 'straining to pee'], $species === 'cat' ? ['a urinary blockage', 'a urinary infection', 'bladder inflammation'] : ['a urinary infection', 'stones', 'a blockage']],
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

    private function subjectPronoun(array $pet): string
    {
        return (($pet['sex'] ?? '') === 'female') ? 'she' : 'he';
    }

    // =========================================================================
    // BUTTON CONFIG — frontend reads this to show CTA buttons
    // =========================================================================

    private function buildApiPayload(
        string $sessionId,
        string $routing,
        string $severity,
        int $turn,
        int $score,
        array $response,
        array $triageDetail,
        array $state,
        bool $redFlagBypass
    ): array {
        $pet = $state['pet'] ?? [];
        $ui = $this->buildUiPayload($routing, $score, $pet, $response);
        $view = (string) ($ui['view'] ?? $routing);

        return [
            'success' => true,
            'session_id' => $sessionId,
            'user_id' => $state['user_id'] ?? null,
            'pet_id' => $state['pet_id'] ?? null,
            'routing' => $routing,
            'severity' => $severity,
            'turn' => $turn,
            'score' => $score,
            'health_score' => $ui['health_score']['value'] ?? null,
            'score_band' => $ui['score_band'] ?? null,
            'response' => $response,
            'safe_to_do_while_waiting' => $response['safe_to_do_while_waiting'] ?? [],
            'follow_up_question' => $response['follow_up_question'] ?? null,
            'follow_up_history' => array_values($state['follow_up_history'] ?? []),
            'be_ready_to_tell_vet' => $response['be_ready_to_tell_vet'] ?? null,
            'buttons' => $this->buttons($view),
            'ui' => $ui,
            'triage_detail' => $triageDetail,
            'vet_summary' => $this->vetSummary($state, $score, $routing),
            'red_flag_bypass' => $redFlagBypass,
        ];
    }

    private function buildUiPayload(string $routing, int $score, array $pet, array $response): array
    {
        $healthScore = $this->healthScorePercent($routing, $score);
        $view = $this->uiViewFromHealthScore($healthScore, $routing);
        $healthMeta = $this->healthScoreMeta($healthScore);
        $scoreBand = $this->scoreBandMeta($view);
        $petName = trim((string) ($pet['name'] ?? ''));
        $shareTitle = $petName !== '' ? 'Share ' . $this->possessive($petName) . ' score' : 'Share this score';

        return [
            'view' => $view,
            'theme' => $this->uiTheme($view),
            'score_band' => $scoreBand,
            'banner' => $this->uiBanner($view, $pet, $response),
            'health_score' => [
                'value' => $healthScore,
                'label' => $healthMeta['label'],
                'subtitle' => $healthMeta['subtitle'],
                'color' => $healthMeta['color'],
                'share' => [
                    'title' => $shareTitle,
                    'helper' => 'Help other pet parents find Snoutiq',
                    'whatsapp_text' => $this->shareWhatsappText($petName, $healthScore, $healthMeta['label']),
                ],
            ],
            'service_cards' => $this->serviceCards($view),
        ];
    }

    private function healthScorePercent(string $routing, int $score): int
    {
        $score = max(0, min(10, $score));

        switch ($routing) {
            case 'emergency':
                return max(10, min(30, (int) round(32 - ($score * 1.2))));
            case 'in_clinic':
                return max(36, min(55, (int) round(63 - ($score * 2.8))));
            case 'monitor':
                return max(76, min(92, (int) round(96 - ($score * 5.0))));
            case 'video_consult':
            default:
                return max(56, min(75, (int) round(83 - ($score * 3.3))));
        }
    }

    private function uiViewFromHealthScore(int $healthScore, string $routing): string
    {
        if ($routing === 'emergency' || $healthScore <= 30) {
            return 'emergency';
        }
        if ($healthScore <= 55) {
            return 'in_clinic';
        }
        if ($healthScore <= 75) {
            return 'video_consult';
        }

        return 'monitor';
    }

    private function healthScoreMeta(int $healthScore): array
    {
        if ($healthScore <= 30) {
            return [
                'label' => 'Critical Risk',
                'subtitle' => 'Needs emergency care now',
                'color' => '#C62828',
            ];
        }
        if ($healthScore <= 55) {
            return [
                'label' => 'High Risk',
                'subtitle' => 'Needs vet attention today',
                'color' => '#e53935',
            ];
        }
        if ($healthScore <= 75) {
            return [
                'label' => 'Medium Risk',
                'subtitle' => 'Needs professional check today',
                'color' => '#F57C00',
            ];
        }

        return [
            'label' => 'Low Risk',
            'subtitle' => 'Monitor closely at home',
            'color' => '#2E7D32',
        ];
    }

    private function scoreBandMeta(string $view): array
    {
        return match ($view) {
            'emergency' => [
                'min' => 0,
                'max' => 30,
                'range' => '0-30',
                'view' => 'emergency',
            ],
            'in_clinic' => [
                'min' => 31,
                'max' => 55,
                'range' => '31-55',
                'view' => 'in_clinic',
            ],
            'monitor' => [
                'min' => 76,
                'max' => 100,
                'range' => '76-100',
                'view' => 'monitor',
            ],
            default => [
                'min' => 56,
                'max' => 75,
                'range' => '56-75',
                'view' => 'video_consult',
            ],
        };
    }

    private function uiTheme(string $view): string
    {
        return match ($view) {
            'emergency' => 'emergency',
            'in_clinic' => 'clinic',
            'monitor' => 'monitor',
            default => 'video',
        };
    }

    private function uiBanner(string $view, array $pet, array $response): array
    {
        $petName = trim((string) ($pet['name'] ?? 'Your pet'));

        return match ($view) {
            'emergency' => [
                'eyebrow' => 'Assessment complete — urgent action needed',
                'title' => 'Go to Emergency Vet Now',
                'subtitle' => sprintf('%s needs immediate care — this can be fatal within hours', $petName),
                'time_badge' => $response['time_sensitivity'] ?? 'Go now — every minute matters',
            ],
            'in_clinic' => [
                'eyebrow' => 'Assessment complete — routing decision',
                'title' => 'Visit a Vet Clinic Today',
                'subtitle' => 'A physical examination is needed today',
                'time_badge' => $response['time_sensitivity'] ?? 'Book the earliest appointment today',
            ],
            'monitor' => [
                'eyebrow' => 'Assessment complete — home monitoring guidance',
                'title' => 'Monitor at Home for Now',
                'subtitle' => 'Symptoms look manageable right now — follow these steps closely',
                'time_badge' => $response['time_sensitivity'] ?? 'If no improvement in 48 hours, book a consult',
            ],
            default => [
                'eyebrow' => 'Assessment complete — routing decision',
                'title' => 'See a Vet Today via Video',
                'subtitle' => 'Symptoms need professional assessment today',
                'time_badge' => $response['time_sensitivity'] ?? 'Book a consult within the next 2-3 hours',
            ],
        };
    }

    private function serviceCards(string $view): array
    {
        $video = [
            'badge' => 'Most popular',
            'badge_variant' => '',
            'title' => 'Video Consultation',
            'price' => '₹499',
            'orig_price' => '₹599',
            'guarantee' => "Connect in 15 mins or it's free",
            'bullets' => ['Experienced vets only', 'Connect in 15 mins', 'Money-back guarantee'],
            'theme' => 'video',
            'featured' => true,
            'cta' => [
                'label' => '📱 Book Video Consult Now',
                'deeplink' => self::DEEPLINK_VIDEO,
            ],
        ];

        $vetAtHome = [
            'badge' => 'Selected cities',
            'badge_variant' => 'vah',
            'title' => 'Vet at Home',
            'price' => '₹999',
            'orig_price' => null,
            'guarantee' => 'Vet at your door in 60 mins or money back',
            'bullets' => ['Qualified vet visits you at home', 'In 60 mins or full money back', 'No travel stress for your pet'],
            'theme' => 'vah',
            'featured' => false,
            'cta' => [
                'label' => '🏠 Book Vet at Home',
                'deeplink' => self::DEEPLINK_VET_HOME,
            ],
        ];

        $clinicBooking = [
            'badge' => 'Confirmed slot',
            'badge_variant' => 'cb',
            'title' => 'Confirmed Clinic Booking',
            'price' => '₹350',
            'orig_price' => null,
            'guarantee' => 'Guaranteed appointment, skip the wait',
            'bullets' => ['No queue - appointment confirmed instantly', 'Nearest available vet'],
            'theme' => 'cb',
            'featured' => false,
            'cta' => [
                'label' => '🗺 Book Clinic Appointment',
                'deeplink' => self::DEEPLINK_CLINIC_BOOKING,
            ],
        ];

        return match ($view) {
            'emergency' => [$vetAtHome, $clinicBooking],
            'in_clinic' => [$vetAtHome, $clinicBooking],
            'monitor' => [[
                'badge' => 'If symptoms worsen',
                'badge_variant' => '',
                'title' => $video['title'],
                'price' => $video['price'],
                'orig_price' => $video['orig_price'],
                'guarantee' => $video['guarantee'],
                'bullets' => $video['bullets'],
                'theme' => $video['theme'],
                'featured' => true,
                'cta' => [
                    'label' => '📱 Book Video Consult',
                    'deeplink' => self::DEEPLINK_VIDEO,
                ],
            ]],
            default => [$video, $vetAtHome],
        };
    }

    private function shareWhatsappText(string $petName, int $healthScore, string $label): string
    {
        $subject = $petName !== '' ? "my pet {$petName}" : 'my pet';
        $askUrl = rtrim((string) config('app.url', 'https://snoutiq.com'), '/') . '/ask';

        return "🐾 I just checked {$subject} on Snoutiq AI.\n\n" .
            "Pet Health Score: *{$healthScore}/100* ({$label})\n\n" .
            "Snoutiq AI gave me specific advice in seconds - it's free for all pet parents in India.\n\n" .
            "Check your pet here 👇\n{$askUrl}";
    }

    private function possessive(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'this';
        }

        return preg_match('/s$/i', $value) ? "{$value}'" : "{$value}'s";
    }

    private function buttons(string $routing): array
    {
        $map = [
            'emergency' => [
                'primary'   => ['label' => 'Find Emergency Vet Near Me', 'type' => 'emergency',
                                'deeplink' => self::DEEPLINK_EMERGENCY, 'color' => '#ef4444', 'icon' => 'alert-triangle'],
                'secondary' => ['label' => 'Govt. Vet Hospital (Free/Low Cost)', 'type' => 'govt',
                                'deeplink' => self::DEEPLINK_GOVT,      'color' => '#f97316', 'icon' => 'building'],
            ],
            'video_consult' => [
                'primary'   => ['label' => 'Book Video Consult - ₹499', 'type' => 'video_consult',
                                'deeplink' => self::DEEPLINK_VIDEO,     'color' => '#3b82f6', 'icon' => 'video'],
                'secondary' => ['label' => 'Find Clinic Instead',    'type' => 'clinic',
                                'deeplink' => self::DEEPLINK_CLINIC,    'color' => '#8b5cf6', 'icon' => 'map-pin'],
            ],
            'in_clinic' => [
                'primary'   => ['label' => 'Book Vet at Home - ₹999', 'type' => 'vet_at_home',
                                'deeplink' => self::DEEPLINK_VET_HOME,  'color' => '#8b5cf6', 'icon' => 'home'],
                'secondary' => ['label' => 'Find Nearest Clinic',     'type' => 'clinic',
                                'deeplink' => self::DEEPLINK_CLINIC,    'color' => '#3b82f6', 'icon' => 'map-pin'],
            ],
            'monitor' => [
                'primary'   => ['label' => 'Save Monitoring Guide',   'type' => 'info',
                                'deeplink' => self::DEEPLINK_MONITOR,   'color' => '#10b981', 'icon' => 'eye'],
                'secondary' => ['label' => 'Video Consult if Worried - ₹499', 'type' => 'video_consult',
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
        return ['history' => [], 'follow_up_history' => [], 'pet' => [], 'score' => 0, 'evidence' => []];
    }

    private function softReset(array &$state): void
    {
        $state['score']    = 0;
        $state['evidence'] = [];
        $state['history']  = [];
        $state['follow_up_history'] = [];
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

    private function formatFollowUpHistoryForPrompt(array $followUpHistory): string
    {
        $recent = array_slice($followUpHistory, -4);
        $lines = [];
        foreach ($recent as $item) {
            $question = $this->cleanAssistantText((string) ($item['question'] ?? ''));
            $answer = $this->cleanAssistantText((string) ($item['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            $lines[] = "Q: {$question}";
            $lines[] = "A: {$answer}";
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

    private function saveWebChatCampaign(
        array $data,
        string $sessionId,
        int $turn,
        string $question,
        array $response,
        array $state,
        string $routing,
        string $severity,
        int $score
    ): void {
        try {
            $pet = $state['pet'] ?? [];

            DB::table('web_chat_campaign')->insert([
                'session_id' => $sessionId,
                'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
                'pet_id' => isset($data['pet_id']) ? (int) $data['pet_id'] : null,
                'turn' => max(1, $turn),
                'routing' => $routing,
                'severity' => $severity,
                'score' => max(0, min(255, $score)),
                'pet_name' => $pet['name'] ?? ($data['pet_name'] ?? null),
                'species' => $pet['species'] ?? ($data['species'] ?? null),
                'breed' => $pet['breed'] ?? ($data['breed'] ?? null),
                'location' => $pet['location'] ?? ($data['location'] ?? null),
                'user_message' => mb_substr($question, 0, 65535),
                'assistant_message' => mb_substr((string) ($response['message'] ?? ''), 0, 65535),
                'request_payload_json' => json_encode($this->sanitizePayloadForStorage($data), JSON_UNESCAPED_UNICODE),
                'response_payload_json' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'state_payload_json' => json_encode($state, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SnoutiqSymptomController web_chat_campaign insert failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);
        }
    }

    private function sanitizePayloadForStorage(array $data): array
    {
        if (!empty($data['image_base64'])) {
            $data['image_present'] = true;
            $data['image_bytes_estimate'] = strlen((string) $data['image_base64']);
            $data['image_base64'] = '[omitted base64 image]';
        }

        if (!empty($data['image']) && is_object($data['image'])) {
            $data['image_present'] = true;
            $data['image_upload_name'] = method_exists($data['image'], 'getClientOriginalName')
                ? $data['image']->getClientOriginalName()
                : 'uploaded-image';
            $data['image_upload_mime'] = method_exists($data['image'], 'getMimeType')
                ? $data['image']->getMimeType()
                : null;
            $data['image'] = '[uploaded image omitted]';
        }

        return $data;
    }
}
