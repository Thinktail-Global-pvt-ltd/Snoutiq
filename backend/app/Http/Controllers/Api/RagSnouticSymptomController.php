<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RagSnouticSymptomService;
use App\Support\GeminiConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RagSnouticSymptomController extends Controller
{
    public function __construct(private readonly RagSnouticSymptomService $symptomService)
    {
    }

    /**
     * GET /api/rag-snoutic-symptom-checker/prefill?pet_id=
     */
    public function prefill(Request $request)
    {
        $petId = $this->resolvePetId($request->input('pet_id', $request->query('pet_id')));
        if ($petId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'pet_id is required',
            ], 422);
        }

        $prefillData = $this->symptomService->prefillPayloadByPetId($petId);
        if (! $prefillData) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pet_id' => $prefillData['pet_id'],
                'pet' => $prefillData['pet'],
                'payload' => $prefillData['payload'],
                'vaccination' => $prefillData['vaccination'],
            ],
        ]);
    }

    /**
     * POST /api/rag-snoutic-symptom-checker/query
     */
    public function query(Request $request)
    {
        $petId = $this->resolvePetId($request->input('pet_id', $request->query('pet_id')));
        if ($petId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'pet_id is required',
            ], 422);
        }

        $prefillData = $this->symptomService->prefillPayloadByPetId($petId);
        if (! $prefillData) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found',
            ], 404);
        }

        $requestPayload = $this->symptomService->normalizePayload(array_merge(
            $prefillData['payload'],
            $request->only(array_keys($this->symptomService->defaultPayload()))
        ));

        $queryResult = $this->symptomService->queryExternal($requestPayload);

        if (! $queryResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $queryResult['error'] ?? 'Unable to fetch symptom checker data right now.',
                'pet_id' => $petId,
                'prefill' => [
                    'pet' => $prefillData['pet'],
                    'payload' => $prefillData['payload'],
                    'vaccination' => $prefillData['vaccination'],
                ],
                'request_payload' => $requestPayload,
                'response_data' => $queryResult['response_data'],
            ], 502);
        }

        return response()->json([
            'success' => true,
            'pet_id' => $petId,
            'prefill' => [
                'pet' => $prefillData['pet'],
                'payload' => $prefillData['payload'],
                'vaccination' => $prefillData['vaccination'],
            ],
            'request_payload' => $requestPayload,
            'response_data' => $queryResult['response_data'],
        ]);
    }

    /**
     * GET|POST /api/rag-snoutic-symptom-checker/page-data
     * Input: manual pet details + question/query (no pet_id lookup required)
     * Returns: same sections as web page (prefill, form_values, request_payload, error, response_data).
     */
    public function pageData(Request $request)
    {
        $validated = $request->validate([
            'question' => ['nullable', 'string', 'required_without:query'],
            'query' => ['nullable', 'string', 'required_without:question'],
            'pet_name' => ['nullable', 'string', 'max:255', 'required_without:name'],
            'name' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'mobile' => ['nullable', 'string', 'max:32'],
            'species' => ['nullable', 'string', 'max:100'],
            'pet_species' => ['nullable', 'string', 'max:100'],
            'pet_type' => ['nullable', 'string', 'max:100'],
            'pet_breed' => ['nullable', 'string', 'max:255'],
            'breed' => ['nullable', 'string', 'max:255'],
            'pet_age' => ['nullable'],
            'age' => ['nullable', 'string', 'max:100'],
            'pet_gender' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', 'string', 'max:100'],
            'weight' => ['nullable', 'string', 'max:100'],
            'medical_history' => ['nullable', 'string'],
            'vaccination_summary' => ['nullable', 'string'],
            'vaccination' => ['nullable'],
            'user_id' => ['nullable', 'integer'],
            'pet_id' => ['nullable', 'integer'],
            'pet_card_for_ai' => ['nullable'],
            'video_calling_upload_file' => ['nullable'],
        ]);

        $query = $this->firstFilled($validated, ['question', 'query']);
        $petName = $this->firstFilled($validated, ['pet_name', 'name']);
        if ($query === null || trim((string) $query) === '' || $petName === null || trim((string) $petName) === '') {
            return response()->json([
                'success' => false,
                'message' => 'question/query and pet_name are required',
            ], 422);
        }

        $vaccination = $this->normalizeVaccination($validated['vaccination'] ?? null);
        $vaccinationSummary = $this->firstFilled($validated, ['vaccination_summary']);
        if ($vaccinationSummary === null) {
            $vaccinationSummary = $this->buildVaccinationSummary($vaccination);
        }

        $persistedEntity = $this->persistUserAndPetFromPageData(
            validated: $validated,
            petName: trim((string) $petName)
        );
        $resolvedUserId = $persistedEntity['user_id'] ?? ($validated['user_id'] ?? null);
        $resolvedPetId = $persistedEntity['pet_id'] ?? ($validated['pet_id'] ?? null);
        $resolvedPhoneNumber = $persistedEntity['phone_number']
            ?? $this->normalizePhoneNumber($this->firstFilled($validated, ['phone_number', 'phone', 'mobile']));

        $payloadInput = [
            'name' => trim((string) $petName),
            'species' => (string) ($this->firstFilled($validated, ['species', 'pet_species', 'pet_type']) ?? 'Dog'),
            'breed' => (string) ($this->firstFilled($validated, ['pet_breed', 'breed']) ?? ''),
            'age' => (string) ($this->firstFilled($validated, ['pet_age', 'age']) ?? ''),
            'weight' => (string) ($this->firstFilled($validated, ['weight']) ?? ''),
            'sex' => (string) ($this->firstFilled($validated, ['pet_gender', 'sex']) ?? ''),
            'vaccination_summary' => (string) ($vaccinationSummary ?? ''),
            'medical_history' => (string) ($this->firstFilled($validated, ['medical_history']) ?? ''),
            'query' => trim((string) $query),
        ];

        $requestPayload = $this->symptomService->normalizePayload($payloadInput);

        $formValues = [
            'user_id' => $resolvedUserId,
            'pet_id' => $resolvedPetId,
            'phone_number' => $resolvedPhoneNumber,
            'question' => trim((string) $query),
            'pet_name' => trim((string) $petName),
            'pet_breed' => $this->firstFilled($validated, ['pet_breed', 'breed']),
            'pet_age' => $this->firstFilled($validated, ['pet_age', 'age']),
            'pet_gender' => $this->firstFilled($validated, ['pet_gender', 'sex']),
            'vaccination' => $vaccination,
            'pet_card_for_ai' => $validated['pet_card_for_ai'] ?? null,
            'video_calling_upload_file' => $validated['video_calling_upload_file'] ?? null,
            'medical_history' => $validated['medical_history'] ?? null,
            'species' => $this->firstFilled($validated, ['species', 'pet_species', 'pet_type']),
            'weight' => $validated['weight'] ?? null,
            'vaccination_summary' => $vaccinationSummary,
        ];

        $queryResult = $this->symptomService->queryExternal($requestPayload);
        $responseData = $queryResult['response_data'] ?? null;
        $error = $queryResult['success'] ? null : ($queryResult['error'] ?? 'Unable to fetch symptom checker data right now.');

        $body = [
            'success' => $queryResult['success'],
            'pet_id' => $resolvedPetId,
            'data' => [
                'prefill_pet_id' => $resolvedPetId,
                'prefill_data' => [
                    'pet_id' => $resolvedPetId,
                    'pet' => [
                        'id' => $resolvedPetId,
                        'name' => trim((string) $petName),
                        'breed' => $this->firstFilled($validated, ['pet_breed', 'breed']),
                        'species' => $this->firstFilled($validated, ['species', 'pet_species', 'pet_type']) ?? 'Dog',
                        'age' => $this->firstFilled($validated, ['pet_age', 'age']),
                        'weight' => $validated['weight'] ?? null,
                        'sex' => $this->firstFilled($validated, ['pet_gender', 'sex']),
                    ],
                    'vaccination' => $vaccination,
                ],
                'form_values' => $formValues,
                'request_payload' => $requestPayload,
                'error' => $error,
                'response_data' => $responseData,
                'symptom_data' => data_get($responseData, 'data', []),
                'persisted' => [
                    'phone_number' => $resolvedPhoneNumber,
                    'user_id' => $resolvedUserId,
                    'pet_id' => $resolvedPetId,
                    'user_created' => (bool) ($persistedEntity['user_created'] ?? false),
                    'pet_created' => (bool) ($persistedEntity['pet_created'] ?? false),
                ],
            ],
        ];

        $statusCode = $queryResult['success'] ? 200 : 502;
        $this->persistPageDataLog(
            request: $request,
            validated: $validated,
            formValues: $formValues,
            requestPayload: $requestPayload,
            queryResult: $queryResult,
            responseBody: $body,
            statusCode: $statusCode
        );

        return response()->json($body, $statusCode);
    }

    /**
     * POST /api/symptom-diagnosis
     * Input: question (required), optional pet/user context fields
     * Returns: diagnosis JSON generated by Gemini.
     */
    public function diagnoseQuestion(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'user_id' => ['nullable', 'integer'],
            'pet_id' => ['nullable', 'integer'],
            'pet_name' => ['nullable', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:100'],
            'breed' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', 'string', 'max:100'],
            'weight' => ['nullable', 'string', 'max:100'],
            'medical_history' => ['nullable', 'string'],
        ]);

        $question = trim((string) $validated['question']);
        if ($question === '') {
            return response()->json([
                'success' => false,
                'message' => 'question is required',
            ], 422);
        }

        $contextLines = array_filter([
            $validated['pet_name'] ?? null ? 'Pet name: '.$validated['pet_name'] : null,
            $validated['species'] ?? null ? 'Species: '.$validated['species'] : null,
            $validated['breed'] ?? null ? 'Breed: '.$validated['breed'] : null,
            $validated['age'] ?? null ? 'Age: '.$validated['age'] : null,
            $validated['sex'] ?? null ? 'Sex: '.$validated['sex'] : null,
            $validated['weight'] ?? null ? 'Weight: '.$validated['weight'] : null,
            $validated['medical_history'] ?? null ? 'Medical history: '.$validated['medical_history'] : null,
        ]);
        $contextBlock = $contextLines ? implode("\n", $contextLines) : 'No additional pet context provided.';

        $prompt = <<<PROMPT
You are a veterinary triage assistant. Analyze the pet owner's question and provide a likely diagnosis summary (not a definitive diagnosis). Use the pet context if provided.

Return STRICT JSON ONLY with the following keys:
- diagnosis_summary (string, 1-3 sentences, no disclaimers)
- possible_causes (array of strings, 2-5 items)
- urgency (one of: "low", "medium", "high", "emergency")
- recommended_next_steps (array of strings, 2-5 items)
- follow_up_questions (array of strings, 0-3 items)

If there is not enough information, set diagnosis_summary to "Insufficient details to determine a likely cause." and use follow_up_questions to ask for missing details.

Pet context:
{$contextBlock}

Question:
{$question}
PROMPT;

        $gemini = $this->callGemini($prompt);
        $rawText = $gemini['text'];
        $parsed = $this->decodeGeminiJson($rawText);
        $hasParse = is_array($parsed);

        $diagnosis = $hasParse ? $parsed : [
            'diagnosis_summary' => $rawText ?: 'Unable to generate a diagnosis at the moment.',
            'possible_causes' => [],
            'urgency' => 'unknown',
            'recommended_next_steps' => [],
            'follow_up_questions' => [],
        ];

        $success = $gemini['ok'];
        $statusCode = $success ? 200 : 502;

        $body = [
            'success' => $success,
            'data' => [
                'question' => $question,
                'diagnosis' => $diagnosis,
                'parsed' => $hasParse,
                'model' => $gemini['model'],
            ],
            'error' => $gemini['error'],
        ];

        $this->persistDiagnosisLog(
            request: $request,
            validated: $validated,
            prompt: $prompt,
            responseBody: $body,
            statusCode: $statusCode,
            rawText: $rawText
        );

        return response()->json($body, $statusCode);
    }

    private function resolvePetId($raw): int
    {
        return is_numeric($raw) ? (int) $raw : 0;
    }

    private function firstFilled(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if (is_string($value) && trim($value) === '') {
                continue;
            }

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeVaccination(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function buildVaccinationSummary(array $vaccination): string
    {
        if ($vaccination === []) {
            return 'No vaccination records available.';
        }

        $parts = [];
        foreach ($vaccination as $key => $details) {
            $label = ucwords(str_replace(['_', '-'], ' ', (string) $key));

            if (is_array($details)) {
                $date = isset($details['date']) ? trim((string) $details['date']) : '';
                $nextDue = isset($details['next_due']) ? trim((string) $details['next_due']) : '';

                if ($date !== '' && $nextDue !== '') {
                    $parts[] = "{$label}: {$date} (next due {$nextDue})";
                    continue;
                }

                if ($date !== '') {
                    $parts[] = "{$label}: {$date}";
                    continue;
                }

                if ($nextDue !== '') {
                    $parts[] = "{$label}: next due {$nextDue}";
                    continue;
                }

                $parts[] = $label;
                continue;
            }

            if (is_scalar($details) && trim((string) $details) !== '') {
                $parts[] = "{$label}: ".trim((string) $details);
                continue;
            }

            $parts[] = $label;
        }

        return $parts === [] ? 'Vaccination data provided.' : implode('; ', $parts);
    }

    /**
     * Persist user/pet when phone number is submitted in page-data payload.
     * Returns resolved IDs and create flags.
     */
    private function persistUserAndPetFromPageData(array $validated, string $petName): array
    {
        $resolvedPhone = $this->normalizePhoneNumber($this->firstFilled($validated, ['phone_number', 'phone', 'mobile']));
        $resolvedUserId = is_numeric($validated['user_id'] ?? null) ? (int) $validated['user_id'] : null;
        $resolvedPetId = is_numeric($validated['pet_id'] ?? null) ? (int) $validated['pet_id'] : null;

        $userCreated = false;
        $petCreated = false;

        if (!Schema::hasTable('users') || !Schema::hasTable('pets')) {
            return [
                'phone_number' => $resolvedPhone,
                'user_id' => $resolvedUserId,
                'pet_id' => $resolvedPetId,
                'user_created' => $userCreated,
                'pet_created' => $petCreated,
            ];
        }

        try {
            $userRow = null;
            if ($resolvedUserId !== null && $resolvedUserId > 0) {
                $userRow = DB::table('users')
                    ->where('id', $resolvedUserId)
                    ->first();
            }

            if (!$userRow && $resolvedPhone !== null && Schema::hasColumn('users', 'phone')) {
                $rawPhone = trim((string) ($this->firstFilled($validated, ['phone_number', 'phone', 'mobile']) ?? ''));
                $userRow = DB::table('users')
                    ->where('phone', $resolvedPhone)
                    ->when($rawPhone !== '', function ($q) use ($rawPhone) {
                        $q->orWhere('phone', $rawPhone);
                    })
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$userRow && $resolvedPhone !== null) {
                $userPayload = [];
                $ownerName = trim((string) ($this->firstFilled($validated, ['owner_name', 'user_name']) ?? ''));
                if (Schema::hasColumn('users', 'name')) {
                    $userPayload['name'] = $ownerName !== '' ? $ownerName : ('RAG User '.$resolvedPhone);
                }

                if (Schema::hasColumn('users', 'phone')) {
                    $userPayload['phone'] = $resolvedPhone;
                }

                if (Schema::hasColumn('users', 'email')) {
                    $emailLocal = preg_replace('/[^a-z0-9]/i', '', $resolvedPhone) ?: (string) now()->timestamp;
                    $emailCandidate = "rag_{$emailLocal}@snoutiq.local";
                    $suffix = 1;
                    while (DB::table('users')->where('email', $emailCandidate)->exists()) {
                        $emailCandidate = "rag_{$emailLocal}_{$suffix}@snoutiq.local";
                        $suffix++;
                    }
                    $userPayload['email'] = $emailCandidate;
                }

                if (Schema::hasColumn('users', 'password')) {
                    $userPayload['password'] = bcrypt(Str::random(32));
                }

                if (Schema::hasColumn('users', 'role')) {
                    $userPayload['role'] = 'pet';
                }

                if (Schema::hasColumn('users', 'phone_verified_at')) {
                    $userPayload['phone_verified_at'] = now();
                }

                if (Schema::hasColumn('users', 'created_at')) {
                    $userPayload['created_at'] = now();
                }
                if (Schema::hasColumn('users', 'updated_at')) {
                    $userPayload['updated_at'] = now();
                }

                if (!empty($userPayload)) {
                    $newUserId = (int) DB::table('users')->insertGetId($userPayload);
                    if ($newUserId > 0) {
                        $userRow = DB::table('users')->where('id', $newUserId)->first();
                        $userCreated = $userRow !== null;
                    }
                }
            }

            if ($userRow) {
                $resolvedUserId = (int) $userRow->id;
            }

            if ($resolvedUserId !== null && $resolvedUserId > 0 && $petName !== '') {
                $petUserColumn = Schema::hasColumn('pets', 'user_id')
                    ? 'user_id'
                    : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);

                if ($petUserColumn !== null) {
                    $petQuery = DB::table('pets')
                        ->where($petUserColumn, $resolvedUserId);

                    $requestedPetId = is_numeric($validated['pet_id'] ?? null) ? (int) $validated['pet_id'] : 0;
                    if ($requestedPetId > 0) {
                        $petQuery->where('id', $requestedPetId);
                    } else {
                        $petQuery->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($petName))]);
                    }

                    $petRow = $petQuery->orderByDesc('id')->first();
                    $petPayload = [];

                    if (Schema::hasColumn('pets', 'name')) {
                        $petPayload['name'] = trim($petName);
                    }
                    if (Schema::hasColumn('pets', 'breed')) {
                        $petPayload['breed'] = (string) ($this->firstFilled($validated, ['pet_breed', 'breed']) ?? '');
                    }
                    if (Schema::hasColumn('pets', 'pet_age')) {
                        $petAge = $this->extractInteger($this->firstFilled($validated, ['pet_age', 'age']));
                        if ($petAge !== null) {
                            $petPayload['pet_age'] = $petAge;
                        }
                    }
                    if (Schema::hasColumn('pets', 'pet_gender')) {
                        $petPayload['pet_gender'] = (string) ($this->firstFilled($validated, ['pet_gender', 'sex']) ?? '');
                    } elseif (Schema::hasColumn('pets', 'gender')) {
                        $petPayload['gender'] = (string) ($this->firstFilled($validated, ['pet_gender', 'sex']) ?? '');
                    }

                    $species = (string) ($this->firstFilled($validated, ['species', 'pet_species', 'pet_type']) ?? '');
                    if ($species !== '') {
                        if (Schema::hasColumn('pets', 'pet_type')) {
                            $petPayload['pet_type'] = strtolower(trim($species));
                        } elseif (Schema::hasColumn('pets', 'type')) {
                            $petPayload['type'] = strtolower(trim($species));
                        }
                    }

                    if (Schema::hasColumn('pets', 'weight')) {
                        $weight = $this->extractFloat($validated['weight'] ?? null);
                        if ($weight !== null) {
                            $petPayload['weight'] = $weight;
                        }
                    }

                    if (Schema::hasColumn('pets', 'medical_history') && isset($validated['medical_history'])) {
                        $petPayload['medical_history'] = $validated['medical_history'];
                    }

                    if (Schema::hasColumn('pets', 'updated_at')) {
                        $petPayload['updated_at'] = now();
                    }

                    if ($petRow) {
                        if (!empty($petPayload)) {
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

                        $newPetId = (int) DB::table('pets')->insertGetId($insertPayload);
                        if ($newPetId > 0) {
                            $resolvedPetId = $newPetId;
                            $petCreated = true;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RAG page-data user/pet persistence failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'phone_number' => $resolvedPhone,
            'user_id' => $resolvedUserId,
            'pet_id' => $resolvedPetId,
            'user_created' => $userCreated,
            'pet_created' => $petCreated,
        ];
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

        $normalized = preg_replace('/[^0-9+]/', '', $raw);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return $normalized;
    }

    private function extractInteger(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value) && preg_match('/-?\d+/', $value, $matches) === 1) {
            return (int) $matches[0];
        }

        return null;
    }

    private function extractFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && preg_match('/-?\d+(?:\.\d+)?/', $value, $matches) === 1) {
            return (float) $matches[0];
        }

        return null;
    }

    private function persistPageDataLog(
        Request $request,
        array $validated,
        array $formValues,
        array $requestPayload,
        array $queryResult,
        array $responseBody,
        int $statusCode
    ): void {
        if (!Schema::hasTable('rag_symptom_checker_logs')) {
            return;
        }

        try {
            $userId = is_numeric($formValues['user_id'] ?? null)
                ? (int) $formValues['user_id']
                : (is_numeric($validated['user_id'] ?? null) ? (int) $validated['user_id'] : null);
            $petId = is_numeric($formValues['pet_id'] ?? null)
                ? (int) $formValues['pet_id']
                : (is_numeric($validated['pet_id'] ?? null) ? (int) $validated['pet_id'] : null);
            $petName = trim((string) ($this->firstFilled($validated, ['pet_name', 'name']) ?? ''));

            DB::table('rag_symptom_checker_logs')->insert([
                'success' => (bool) ($responseBody['success'] ?? false),
                'user_id' => $userId,
                'pet_id' => $petId,
                'pet_name' => $petName !== '' ? $petName : null,
                'endpoint' => '/api/rag-snoutic-symptom-checker/page-data',
                'http_method' => strtoupper((string) $request->method()),
                'input_payload_json' => $this->encodeJson($request->all()),
                'prefill_data_json' => $this->encodeJson(data_get($responseBody, 'data.prefill_data')),
                'form_values_json' => $this->encodeJson($formValues),
                'request_payload_json' => $this->encodeJson($requestPayload),
                'response_data_json' => $this->encodeJson(data_get($responseBody, 'data.response_data')),
                'symptom_data_json' => $this->encodeJson(data_get($responseBody, 'data.symptom_data')),
                'full_response_json' => $this->encodeJson($responseBody),
                'error_message' => data_get($responseBody, 'data.error'),
                'http_status_code' => $statusCode,
                'external_status_code' => is_numeric($queryResult['status'] ?? null) ? (int) $queryResult['status'] : null,
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function encodeJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    private function callGemini(string $prompt): array
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return [
                'ok' => false,
                'text' => 'AI error: Gemini API key is not configured.',
                'error' => 'Gemini API key is not configured.',
                'model' => GeminiConfig::chatModel(),
            ];
        }

        $model = GeminiConfig::chatModel();
        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);
        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'maxOutputTokens' => 500,
            ],
        ];

        try {
            $res = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $apiKey,
            ])->post($endpoint, $payload);

            if (! $res->successful()) {
                $message = data_get($res->json(), 'error.message') ?: 'Gemini API error';
                Log::error('Gemini HTTP non-2xx', ['status' => $res->status(), 'body' => $res->body()]);
                return [
                    'ok' => false,
                    'text' => "AI error: {$message}",
                    'error' => $message,
                    'model' => $model,
                ];
            }

            $text = (string) data_get($res->json(), 'candidates.0.content.parts.0.text', '');
            $text = trim($text);
            if ($text === '') {
                return [
                    'ok' => false,
                    'text' => 'AI error: Gemini returned an empty response.',
                    'error' => 'Gemini returned an empty response.',
                    'model' => $model,
                ];
            }

            return [
                'ok' => true,
                'text' => $text,
                'error' => null,
                'model' => $model,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini request failed', ['error' => $e->getMessage()]);
            return [
                'ok' => false,
                'text' => 'AI error: '.$e->getMessage(),
                'error' => $e->getMessage(),
                'model' => $model,
            ];
        }
    }

    private function decodeGeminiJson(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($trimmed, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function persistDiagnosisLog(
        Request $request,
        array $validated,
        string $prompt,
        array $responseBody,
        int $statusCode,
        string $rawText
    ): void {
        if (!Schema::hasTable('rag_symptom_checker_logs')) {
            return;
        }

        try {
            $userId = is_numeric($validated['user_id'] ?? null) ? (int) $validated['user_id'] : null;
            $petId = is_numeric($validated['pet_id'] ?? null) ? (int) $validated['pet_id'] : null;
            $petName = trim((string) ($validated['pet_name'] ?? ''));

            DB::table('rag_symptom_checker_logs')->insert([
                'success' => (bool) ($responseBody['success'] ?? false),
                'user_id' => $userId,
                'pet_id' => $petId,
                'pet_name' => $petName !== '' ? $petName : null,
                'endpoint' => '/api/symptom-diagnosis',
                'http_method' => strtoupper((string) $request->method()),
                'input_payload_json' => $this->encodeJson($request->all()),
                'request_payload_json' => $this->encodeJson([
                    'prompt' => $prompt,
                ]),
                'response_data_json' => $this->encodeJson(data_get($responseBody, 'data.diagnosis')),
                'symptom_data_json' => $this->encodeJson(data_get($responseBody, 'data.diagnosis')),
                'full_response_json' => $this->encodeJson($responseBody),
                'error_message' => $responseBody['error'] ?? null,
                'http_status_code' => $statusCode,
                'external_status_code' => null,
                'logged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
