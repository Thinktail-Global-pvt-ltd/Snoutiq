<?php

namespace App\Services;

use App\Models\HealthPulseEntry;
use App\Models\Pet;
use App\Support\GeminiConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthPulseAiService
{
    public function analyzeEntry(Pet $pet, HealthPulseEntry $entry, array $recentEntries = []): array
    {
        $loggedDays = max(1, count($recentEntries) + 1);
        $fallback = $this->fallbackAnalysis($entry, $loggedDays);
        $apiKey = trim(GeminiConfig::apiKey());
        if ($apiKey === '') {
            return $fallback;
        }

        $prompt = $this->entryPrompt($entry, $loggedDays);
        $result = $this->callGemini($prompt);
        if (!is_array($result)) {
            return $fallback;
        }

        return $this->normalizeAnalysis($result, $fallback);
    }

    public function summarizeReport(Pet $pet, array $entries): string
    {
        if (count($entries) < 1) {
            return 'No health pulse entries are available yet.';
        }

        $entryCount = count($entries);
        $fallback = "Thanks for keeping the daily pulse going - {$entryCount} days logged. Based on the logged health pulses, keep watching appetite, energy, water intake, digestion, and any repeated symptoms over time.";
        $apiKey = trim(GeminiConfig::apiKey());
        if ($apiKey === '') {
            return $fallback;
        }

        $payload = array_map(fn ($entry) => [
            'date' => $entry['entry_date'] ?? null,
            'food' => $entry['food'] ?? null,
            'energy' => $entry['energy'] ?? null,
            'water' => $entry['water'] ?? null,
            'symptoms' => $entry['symptoms'] ?? null,
            'digestion_issue' => $entry['digestion_issue'] ?? false,
            'ai_flag_level' => $entry['ai_flag_level'] ?? 'None',
        ], $entries);

        $prompt = "You are a pet health journaling assistant for Snoutiq. Do not diagnose.\n"
            ."Write a concise, safe health trend summary from these daily check-ins. Use phrases like worth monitoring or worth a vet check.\n"
            ."Use only these fields: food, energy, water, symptoms, digestion_issue. Ignore any other context.\n"
            ."Start by appreciating the pet parent for logging {$entryCount} days of health data.\n"
            ."Return JSON only: {\"summary\":\"...\"}.\n\n"
            .'Entries: '.json_encode($payload);

        $result = $this->callGemini($prompt);
        $summary = is_array($result) ? trim((string) ($result['summary'] ?? '')) : '';

        return $summary !== '' ? $summary : $fallback;
    }

    private function fallbackAnalysis(HealthPulseEntry $entry, int $loggedDays): array
    {
        $symptoms = trim((string) $entry->symptoms);
        $food = strtolower((string) $entry->food);
        $energy = strtolower((string) $entry->energy);
        $water = strtolower((string) $entry->water);
        $concerning = 0;

        foreach ([$food, $energy, $water] as $value) {
            if (in_array($value, ['low', 'poor', 'less', 'reduced', 'none', 'not_eating', 'not drinking', 'not_drinking'], true)) {
                $concerning++;
            }
        }
        if ($entry->digestion_issue) {
            $concerning++;
        }
        if ($symptoms !== '') {
            $concerning++;
        }

        $flag = $concerning >= 3 ? 'Alert' : ($concerning >= 1 ? 'Watch' : 'None');

        return [
            'short_summary' => $flag === 'None'
                ? "Thanks for logging today's health pulse - {$loggedDays} days logged. Today's pulse looks generally steady from the logged signals."
                : "Thanks for logging today's health pulse - {$loggedDays} days logged. Today's pulse has a few signals worth monitoring.",
            'pattern_observation' => 'This observation is based only on today\'s food, energy, water, symptoms, and digestion inputs.',
            'flag_level' => $flag,
            'recommended_action' => $flag === 'Alert'
                ? 'Keep monitoring closely and consider a vet check if these signs continue or worsen.'
                : ($flag === 'Watch' ? 'Monitor over the next day and log any change.' : 'Continue the daily health pulse habit.'),
        ];
    }

    private function entryPrompt(HealthPulseEntry $entry, int $loggedDays): string
    {
        return "You are a pet health journaling assistant for Snoutiq. You must not diagnose or say the pet is sick.\n"
            ."Analyze one daily check-in and return JSON only with keys: short_summary, pattern_observation, flag_level, recommended_action.\n"
            ."flag_level must be one of None, Watch, Alert. Use safe language like worth monitoring or worth a vet check.\n\n"
            ."Use only these five fields. Do not use pet profile, date, previous entries, FCM token, or any other metadata.\n"
            ."In short_summary, warmly thank the pet parent for logging today's pulse and mention {$loggedDays} days logged. Keep it human and concise.\n"
            .'Pulse fields: '.json_encode([
                'food' => $entry->food,
                'energy' => $entry->energy,
                'water' => $entry->water,
                'symptoms' => $entry->symptoms,
                'digestion_issue' => $entry->digestion_issue,
            ]);
    }

    private function callGemini(string $prompt): ?array
    {
        try {
            $endpoint = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                GeminiConfig::chatModel()
            );
            $response = Http::timeout(12)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => GeminiConfig::apiKey(),
                ])
                ->post($endpoint, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('health_pulse.ai_failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $text = trim((string) $response->json('candidates.0.content.parts.0.text'));
            $decoded = json_decode($text, true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::warning('health_pulse.ai_exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function normalizeAnalysis(array $result, array $fallback): array
    {
        $flag = trim((string) ($result['flag_level'] ?? $fallback['flag_level']));
        if (!in_array($flag, ['None', 'Watch', 'Alert'], true)) {
            $flag = $fallback['flag_level'];
        }

        return [
            'short_summary' => trim((string) ($result['short_summary'] ?? '')) ?: $fallback['short_summary'],
            'pattern_observation' => trim((string) ($result['pattern_observation'] ?? '')) ?: $fallback['pattern_observation'],
            'flag_level' => $flag,
            'recommended_action' => trim((string) ($result['recommended_action'] ?? '')) ?: $fallback['recommended_action'],
        ];
    }

    private function petProfile(Pet $pet): array
    {
        return [
            'id' => $pet->id,
            'name' => $pet->name,
            'type' => $pet->pet_type ?? $pet->type ?? null,
            'breed' => $pet->breed ?? null,
            'gender' => $pet->pet_gender ?? $pet->gender ?? null,
            'dob' => $this->dateString($pet->pet_dob ?? $pet->dob ?? null),
            'age' => $pet->pet_age ?? null,
        ];
    }

    private function dateString($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
