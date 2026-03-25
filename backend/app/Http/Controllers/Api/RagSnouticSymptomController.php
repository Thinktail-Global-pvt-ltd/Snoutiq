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
            'user_id' => $validated['user_id'] ?? null,
            'pet_id' => $validated['pet_id'] ?? null,
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
            'pet_id' => null,
            'data' => [
                'prefill_pet_id' => null,
                'prefill_data' => [
                    'pet_id' => $validated['pet_id'] ?? null,
                    'pet' => [
                        'id' => $validated['pet_id'] ?? null,
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
            $userId = is_numeric($validated['user_id'] ?? null) ? (int) $validated['user_id'] : null;
            $petId = is_numeric($validated['pet_id'] ?? null) ? (int) $validated['pet_id'] : null;
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
