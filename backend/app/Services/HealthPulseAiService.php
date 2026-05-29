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
        $petName = trim((string) ($pet->name ?? 'your pet')) ?: 'your pet';
        $fallback = $this->fallbackAnalysis($entry, $loggedDays, $petName);
        $apiKey = trim(GeminiConfig::apiKey());
        if ($apiKey === '') {
            return $fallback;
        }

        $prompt = $this->entryPrompt($entry, $loggedDays, $petName);
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
        $fallback = "Nice work checking in for {$entryCount} days. Based on these quick daily updates, keep an eye on appetite, energy, water, digestion, and any repeated symptoms.";
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
            ."Start with a warm, informal thank-you for checking in for {$entryCount} days. Do not use the words pulse, logging, logged, or log.\n"
            ."Return JSON only: {\"summary\":\"...\"}.\n\n"
            .'Entries: '.json_encode($payload);

        $result = $this->callGemini($prompt);
        $summary = is_array($result) ? trim((string) ($result['summary'] ?? '')) : '';

        return $summary !== '' ? $summary : $fallback;
    }

    public function analyzeSymptomTrend(Pet $pet, array $symptomEntries): array
    {
        $symptomEntries = array_values(array_filter($symptomEntries, fn ($entry) => trim((string) ($entry['symptoms'] ?? '')) !== ''));
        $entryCount = count($symptomEntries);
        $petName = trim((string) ($pet->name ?? 'your pet')) ?: 'your pet';
        $fallback = $this->fallbackSymptomTrend($petName, $symptomEntries);

        if ($entryCount === 0) {
            return $fallback;
        }

        $apiKey = trim(GeminiConfig::apiKey());
        if ($apiKey === '') {
            return $fallback;
        }

        $payload = array_map(fn ($entry) => [
            'date' => $entry['entry_date'] ?? null,
            'symptoms' => $entry['symptoms'] ?? null,
        ], $symptomEntries);

        $prompt = "You are a pet health journaling assistant for Snoutiq. Do not diagnose, prescribe, or claim the pet is sick.\n"
            ."Analyze the pet parent's symptom notes across all provided daily entries and return JSON only with keys: analysis_text, flag_level, recommended_action.\n"
            ."flag_level must be one of None, Watch, Alert. Use safe language like worth monitoring or worth a vet check.\n"
            ."Focus only on repeated, worsening, or newly mentioned symptoms. If symptoms look isolated, say that simply.\n"
            ."Use the pet name {$petName}. Keep it short, warm, and useful for the pet parent.\n\n"
            .'Symptom entries: '.json_encode($payload);

        $result = $this->callGemini($prompt);
        if (!is_array($result)) {
            return $fallback;
        }

        return $this->normalizeSymptomTrend($result, $fallback);
    }

    private function fallbackAnalysis(HealthPulseEntry $entry, int $loggedDays, string $petName): array
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
        $specificNotes = $this->specificNotes($entry);
        $watchDetail = $specificNotes !== ''
            ? " {$specificNotes}"
            : ' A few answers need a little extra attention.';

        return [
            'short_summary' => $flag === 'None'
                ? "Well done - you completed {$petName}'s care update for today. That's {$loggedDays} days in, and what you shared looks steady."
                : "Well done - you completed {$petName}'s care update for today. That's {$loggedDays} days in.{$watchDetail}",
            'pattern_observation' => "This is based only on {$petName}'s food, energy, water, symptoms, and digestion answers from today.",
            'flag_level' => $flag,
            'recommended_action' => $flag === 'Alert'
                ? "Keep a closer eye on {$petName}; if this continues or feels unusual, a vet check is a good idea."
                : ($flag === 'Watch' ? "You are building a useful habit - check on {$petName} again later today or tomorrow and note any change." : "Keep the streak going - these small daily care updates make {$petName}'s routine easier to follow."),
        ];
    }

    private function entryPrompt(HealthPulseEntry $entry, int $loggedDays, string $petName): string
    {
        return "You are a pet health journaling assistant for Snoutiq. You must not diagnose or say the pet is sick.\n"
            ."Analyze one daily check-in and return JSON only with keys: short_summary, pattern_observation, flag_level, recommended_action.\n"
            ."flag_level must be one of None, Watch, Alert. Use safe language like worth monitoring or worth a vet check.\n\n"
            ."Use only these five fields. Do not use pet profile, date, previous entries, FCM token, or any other metadata.\n"
            ."Use the pet name {$petName} in short_summary. Sound warm, simple, informal, and motivating. Congratulate the pet parent for completing today's care update and mention {$loggedDays} days in. Make them feel good about entering the data. Do not use the words pulse, logging, logged, or log.\n"
            ."If symptoms is not empty, mention the symptom text naturally in short_summary. If digestion_issue is true, mention the tummy/poop update in a caring, human way. Do not say marked as a concern. If both exist, mention both briefly.\n"
            .'Pulse fields: '.json_encode([
                'food' => $entry->food,
                'energy' => $entry->energy,
                'water' => $entry->water,
                'symptoms' => $entry->symptoms,
                'digestion_issue' => $entry->digestion_issue,
            ]);
    }

    private function fallbackSymptomTrend(string $petName, array $symptomEntries): array
    {
        $entryCount = count($symptomEntries);

        if ($entryCount === 0) {
            return [
                'analysis_text' => "No symptom notes have been added for {$petName} yet.",
                'flag_level' => 'None',
                'recommended_action' => "Keep adding any new symptom notes so {$petName}'s changes are easier to follow.",
            ];
        }

        $latest = trim((string) ($symptomEntries[$entryCount - 1]['symptoms'] ?? ''));
        $normalized = array_map(fn ($entry) => strtolower(trim((string) ($entry['symptoms'] ?? ''))), $symptomEntries);
        $repeatCount = count($normalized) - count(array_unique($normalized));
        $flag = $entryCount >= 3 || $repeatCount > 0 ? 'Watch' : 'None';

        return [
            'analysis_text' => $entryCount === 1
                ? "The latest symptom note for {$petName} mentions {$latest}. One note alone is not a pattern yet, but it is useful to track."
                : "{$petName} has {$entryCount} symptom notes so far. The latest mentions {$latest}; watch whether this repeats or changes.",
            'flag_level' => $flag,
            'recommended_action' => $flag === 'Watch'
                ? "Keep an eye on {$petName}; if the same symptom keeps showing up, gets worse, or feels unusual, a vet check is a good idea."
                : "Check {$petName} again later today or tomorrow and add any change you notice.",
        ];
    }

    private function normalizeSymptomTrend(array $result, array $fallback): array
    {
        $flag = trim((string) ($result['flag_level'] ?? $fallback['flag_level']));
        if (!in_array($flag, ['None', 'Watch', 'Alert'], true)) {
            $flag = $fallback['flag_level'];
        }

        return [
            'analysis_text' => trim((string) ($result['analysis_text'] ?? '')) ?: $fallback['analysis_text'],
            'flag_level' => $flag,
            'recommended_action' => trim((string) ($result['recommended_action'] ?? '')) ?: $fallback['recommended_action'],
        ];
    }

    private function specificNotes(HealthPulseEntry $entry): string
    {
        $notes = [];
        $symptoms = trim((string) $entry->symptoms);

        if ($symptoms !== '') {
            $notes[] = "You mentioned {$symptoms}, so keep an eye on that";
        }

        if ($entry->digestion_issue) {
            $notes[] = 'the tummy/poop update is worth watching too';
        }

        if (empty($notes)) {
            return '';
        }

        return ucfirst(implode(', and ', $notes)).'.';
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
