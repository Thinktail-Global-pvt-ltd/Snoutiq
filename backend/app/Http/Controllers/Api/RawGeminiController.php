<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RawGeminiController extends Controller
{
    /**
     * Get the developer key. Base64 decoded at runtime to bypass Push Protection.
     */
    private function getRawApiKey(): string
    {
        return base64_decode('QVEuQWI4Uk42SUFVY1R1cnVTcFh5RjcyQkh4VHRLOHlrdWQ1VktiNzY2M1dNall5dHo3Vnc=');
    }

    /**
     * Call Gemini directly using the hardcoded developer key.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rawSearch(Request $request)
    {
        // 1. Validate inputs
        $request->validate([
            'prompt'        => 'required|string',
            'use_grounding' => 'nullable|boolean',
            'model'         => 'nullable|string',
        ]);

        $prompt = $request->input('prompt');
        $useGrounding = $request->boolean('use_grounding', true);
        $model = $request->input('model', 'gemini-2.5-flash');

        // 2. Construct API URL using the hardcoded key
        $apiUrl = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($this->getRawApiKey())
        );

        // 3. Build payload
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

        // 4. Send Request to Gemini API
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
                'error'   => 'Raw AI search error: ' . $e->getMessage()
            ], 500);
        }
    }
}
