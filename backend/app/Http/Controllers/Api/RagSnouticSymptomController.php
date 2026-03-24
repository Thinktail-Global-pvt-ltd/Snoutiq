<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RagSnouticSymptomService;
use Illuminate\Http\Request;

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

        return response()->json($body, $queryResult['success'] ? 200 : 502);
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
}
