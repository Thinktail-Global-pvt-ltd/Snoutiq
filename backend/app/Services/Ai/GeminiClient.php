<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class GeminiClient
{
    private const DEFAULT_API_KEY = 'AIzaSyCIB0yfzSQGGwpVUruqy_sd2WqujTLa1Rk';
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        // Hard-coded key fallback (no .env required)
        $this->apiKey = $apiKey ?? self::DEFAULT_API_KEY;
        $this->model  = $model  ?? 'gemini-1.5-flash';
    }

    public function summarizeTranscript(string $transcript): ?string
    {
        if (!$this->apiKey) {
            // No key: return raw transcript as fallback
            return trim($transcript);
        }

        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $this->model);

        $prompt = "You are a veterinary triage assistant. Summarize the following patient-owner chat into a concise summary for a doctor.\n".
                  "Include: species/breed (if present), age, main symptoms, duration, any red flags, home care already tried.\n".
                  "End with 2-3 possible differentials and immediate next steps.\n\nChat transcript:\n".$transcript;

        $payload = [
            'contents' => [[ 'parts' => [[ 'text' => $prompt ]]]],
        ];

        try {
            $res = Http::withHeaders([
                'Content-Type'   => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post($endpoint, $payload);

            if (!$res->successful()) {
                return trim($transcript);
            }
            $text = $res->json('candidates.0.content.parts.0.text');
            return $text ? trim($text) : trim($transcript);
        } catch (\Throwable $e) {
            return trim($transcript);
        }
    }
}
