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
     * Input: pet_id only
     * Returns: same sections as web page (prefill, form_values, request_payload, error, response_data).
     */
    public function pageData(Request $request)
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

        $formValues = $prefillData['payload'];
        $requestPayload = $this->symptomService->normalizePayload($formValues);
        $queryResult = $this->symptomService->queryExternal($requestPayload);
        $responseData = $queryResult['response_data'] ?? null;
        $error = $queryResult['success'] ? null : ($queryResult['error'] ?? 'Unable to fetch symptom checker data right now.');

        $body = [
            'success' => $queryResult['success'],
            'pet_id' => $petId,
            'data' => [
                'prefill_pet_id' => $petId,
                'prefill_data' => [
                    'pet_id' => $prefillData['pet_id'],
                    'pet' => $prefillData['pet'],
                    'vaccination' => $prefillData['vaccination'],
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
}
