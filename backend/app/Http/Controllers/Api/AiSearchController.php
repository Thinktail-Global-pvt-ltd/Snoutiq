<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Support\GeminiConfig;

class AiSearchController extends Controller
{
    /**
     * Search using Gemini with optional Google Search Grounding.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // 1. Validate inputs
        $request->validate([
            'prompt'        => 'required|string',
            'use_grounding' => 'nullable|boolean',
            'model'         => 'nullable|string',
        ]);

        $prompt = $request->input('prompt');
        $useGrounding = $request->boolean('use_grounding', true);

        // 2. Fetch Gemini Configuration
        $apiKey = GeminiConfig::apiKey();
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'error'   => 'AI service is not configured (API key missing).'
            ], 503);
        }

        // Use requested model or fall back to default chat model (e.g. gemini-2.0-flash)
        $model = $request->input('model', GeminiConfig::chatModel());
        if (empty($model)) {
            $model = 'gemini-2.0-flash';
        }

        // 3. Construct API URL
        $apiUrl = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($apiKey)
        );

        // 4. Build payload
        $payload = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        // Enable google search grounding if requested
        if ($useGrounding) {
            $payload['tools'] = [
                ['google_search' => (object)[]]
            ];
        }

        // 5. Send Request to Gemini API
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiUrl, $payload);

            if ($response->failed()) {
                $errorMessage = data_get($response->json(), 'error.message', 'Gemini API error.');
                return response()->json([
                    'success' => false,
                    'error'   => $errorMessage
                ], 502);
            }

            $json = $response->json();
            $text = data_get($json, 'candidates.0.content.parts.0.text');
            $groundingMetadata = data_get($json, 'candidates.0.groundingMetadata');

            return response()->json([
                'success'            => true,
                'text'               => $text,
                'grounding_metadata' => $groundingMetadata,
                'raw_response'       => $json
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'An error occurred while calling the AI search service: ' . $e->getMessage()
            ], 500);
        }
    }
}
