<?php

namespace App\Services\Ai;

use App\Support\GeminiConfig;
use Illuminate\Support\Facades\Http;

class DogDiseaseSuggester
{
    public function suggest(string $input, array $petContext = []): string
    {
        $prompt = $this->buildPrompt($input, $petContext);

        $models = array_values(array_unique([
            GeminiConfig::chatModel() ?: GeminiConfig::defaultModel(),
            GeminiConfig::defaultModel(),
        ]));

        $lastError = null;
        foreach ($models as $model) {
            try {
                $raw = $this->callGemini($prompt, $model);
                $name = $this->extractDiseaseName($raw);
                if ($name !== '') {
                    return $name;
                }
            } catch (\Throwable $e) {
                $lastError = $e;
                continue; // try next model
            }
        }

        // If all models fail, degrade gracefully
        return 'Unknown dog disease';
    }

    private function buildPrompt(string $symptom, array $pet): string
    {
        $context = [];
        foreach (['name', 'breed', 'pet_age', 'pet_gender'] as $key) {
            if (!empty($pet[$key])) {
                $label = match ($key) {
                    'name' => 'Pet name',
                    'breed' => 'Breed',
                    'pet_age' => 'Age',
                    'pet_gender' => 'Gender',
                    default => ucfirst($key),
                };
                $context[] = "{$label}: {$pet[$key]}";
            }
        }
        $patientContext = $context ? implode(', ', $context) : 'Dog patient';

        return <<<PROMPT
You are a veterinary assistant and only answer about dog diseases. The user will share either symptoms or a possibly misspelled disease name.
Tasks:
- Return the single best-matching dog disease/condition with corrected spelling.
- If the text is unrelated to dogs or you are unsure, answer "Unknown dog disease".
- Stick to concise clinical disease names and avoid explanations (examples: Canine parvovirus, Kennel cough (infectious tracheobronchitis), Canine distemper, Heartworm disease, Tick fever (canine ehrlichiosis/babesiosis), Lyme disease, Gastroenteritis, Pancreatitis, Otitis externa, Mange, Hip dysplasia).
- Output strictly one line of JSON: {"disease_name": "<corrected dog disease name or Unknown dog disease>"}.

Patient context: {$patientContext}
User text: "{$symptom}"
PROMPT;
    }

    private function callGemini(string $prompt, ?string $modelOverride = null): string
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        $model = $modelOverride ?: (GeminiConfig::chatModel() ?: GeminiConfig::defaultModel());
        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.25,
                'topP' => 0.9,
                'topK' => 32,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->post($endpoint, $payload);

        if (!$response->successful()) {
            $message = data_get($response->json(), 'error.message') ?: 'Gemini API error';
            throw new \RuntimeException($message);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (!$text) {
            throw new \RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
    }

    private function extractDiseaseName(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $jsonStart = strpos($raw, '{');
        if ($jsonStart !== false) {
            $json = substr($raw, $jsonStart);
            $decoded = json_decode($json, true);
            if (is_array($decoded) && !empty($decoded['disease_name'])) {
                return trim((string) $decoded['disease_name']);
            }
        }

        if (preg_match('/disease[_ ]name[^:]*[:=]\\s*\"?([^\\n\"\\}]+)\"?/i', $raw, $m)) {
            return trim($m[1]);
        }

        if (stripos($raw, 'unknown dog disease') !== false) {
            return 'Unknown dog disease';
        }

        return trim(trim($raw), "\"'");
    }
}
