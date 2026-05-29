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
        $symptomEntries = array_values(array_filter($symptomEntries, fn ($entry) => $this->hasMeaningfulSymptoms($entry['symptoms'] ?? null)));
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
            ."Analyze the pet parent's symptom notes across all provided daily entries and return JSON only with keys: analysis_text, trend_summary, latest_symptom_note, repeated_symptoms, possible_pattern, flag_level, recommended_action, next_steps, disclaimer.\n"
            ."flag_level must be one of None, Watch, Alert. Use safe language like worth monitoring or worth a vet check.\n"
            ."next_steps must be an array of 2 to 4 short practical strings. repeated_symptoms must be an array of strings.\n"
            ."Focus only on repeated, worsening, or newly mentioned symptoms. If symptoms look isolated, say that simply. Ignore entries meaning no symptoms.\n"
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
        $symptoms = $this->hasMeaningfulSymptoms($entry->symptoms) ? trim((string) $entry->symptoms) : '';
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
        $symptoms = $this->hasMeaningfulSymptoms($entry->symptoms) ? $entry->symptoms : null;

        return "You are a pet health journaling assistant for Snoutiq. You must not diagnose or say the pet is sick.\n"
            ."Analyze one daily check-in and return JSON only with keys: short_summary, pattern_observation, flag_level, recommended_action.\n"
            ."flag_level must be one of None, Watch, Alert. Use safe language like worth monitoring or worth a vet check.\n\n"
            ."Use only these five fields. Do not use pet profile, date, previous entries, FCM token, or any other metadata.\n"
            ."Use the pet name {$petName} in short_summary. Sound warm, simple, informal, and motivating. Congratulate the pet parent for completing today's care update and mention {$loggedDays} days in. Make them feel good about entering the data. Do not use the words pulse, logging, logged, or log.\n"
            ."Treat empty symptoms, none, no symptoms, nil, normal, and no symptom as no symptom. If symptoms is not empty, mention the symptom text naturally in short_summary. If digestion_issue is true, mention the tummy/poop update in a caring, human way. Do not say marked as a concern. If both exist, mention both briefly.\n"
            .'Pulse fields: '.json_encode([
                'food' => $entry->food,
                'energy' => $entry->energy,
                'water' => $entry->water,
                'symptoms' => $symptoms,
                'digestion_issue' => $entry->digestion_issue,
            ]);
    }

    private function fallbackSymptomTrend(string $petName, array $symptomEntries): array
    {
        $entryCount = count($symptomEntries);

        if ($entryCount === 0) {
            return [
                'analysis_text' => "No active symptom pattern is showing for {$petName} right now.",
                'trend_summary' => "The saved entries do not include meaningful symptom notes yet.",
                'latest_symptom_note' => null,
                'repeated_symptoms' => [],
                'possible_pattern' => "No repeated symptom pattern is available from the current notes.",
                'flag_level' => 'None',
                'recommended_action' => "Keep adding any new symptom notes so {$petName}'s changes are easier to follow.",
                'next_steps' => [
                    "Continue daily food, energy, water, symptom, and digestion updates.",
                    "Add a short note if any new symptom appears.",
                ],
                'disclaimer' => "This is not a diagnosis and is based only on the symptom notes entered.",
            ];
        }

        $latest = trim((string) ($symptomEntries[$entryCount - 1]['symptoms'] ?? ''));
        $normalized = array_map(fn ($entry) => strtolower(trim((string) ($entry['symptoms'] ?? ''))), $symptomEntries);
        $repeatCount = count($normalized) - count(array_unique($normalized));
        $flag = $entryCount >= 3 || $repeatCount > 0 ? 'Watch' : 'None';
        $repeatedSymptoms = array_values(array_unique(array_diff_assoc($normalized, array_unique($normalized))));

        return [
            'analysis_text' => $entryCount === 1
                ? "The latest symptom note for {$petName} mentions {$latest}. One note alone is not a pattern yet, but it is useful to track."
                : "{$petName} has {$entryCount} symptom notes so far. The latest mentions {$latest}; watch whether this repeats or changes.",
            'trend_summary' => $entryCount === 1
                ? "One meaningful symptom note is available."
                : "{$entryCount} meaningful symptom notes are available for comparison.",
            'latest_symptom_note' => $latest,
            'repeated_symptoms' => $repeatedSymptoms,
            'possible_pattern' => $repeatCount > 0
                ? "At least one symptom note appears more than once."
                : "No exact repeated symptom text is visible, but the latest note should be compared with the next update.",
            'flag_level' => $flag,
            'recommended_action' => $flag === 'Watch'
                ? "Keep an eye on {$petName}; if the same symptom keeps showing up, gets worse, or feels unusual, a vet check is a good idea."
                : "Check {$petName} again later today or tomorrow and add any change you notice.",
            'next_steps' => $flag === 'Watch'
                ? [
                    "Check whether the symptom is improving, unchanged, or getting worse.",
                    "Note timing, frequency, appetite, energy, water intake, and digestion changes.",
                    "Consider a vet check if it repeats, worsens, or feels unusual.",
                ]
                : [
                    "Keep tracking any change in the next daily update.",
                    "Add a fresh symptom note only if something new appears.",
                ],
            'disclaimer' => "This is not a diagnosis and is based only on the symptom notes entered.",
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
            'trend_summary' => trim((string) ($result['trend_summary'] ?? '')) ?: $fallback['trend_summary'],
            'latest_symptom_note' => $this->nullableString($result['latest_symptom_note'] ?? $fallback['latest_symptom_note']),
            'repeated_symptoms' => $this->stringList($result['repeated_symptoms'] ?? $fallback['repeated_symptoms']),
            'possible_pattern' => trim((string) ($result['possible_pattern'] ?? '')) ?: $fallback['possible_pattern'],
            'flag_level' => $flag,
            'recommended_action' => trim((string) ($result['recommended_action'] ?? '')) ?: $fallback['recommended_action'],
            'next_steps' => $this->stringList($result['next_steps'] ?? $fallback['next_steps']),
            'disclaimer' => trim((string) ($result['disclaimer'] ?? '')) ?: $fallback['disclaimer'],
        ];
    }

    private function specificNotes(HealthPulseEntry $entry): string
    {
        $notes = [];
        $symptoms = trim((string) $entry->symptoms);

        if ($this->hasMeaningfulSymptoms($symptoms)) {
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

    private function hasMeaningfulSymptoms($value): bool
    {
        $text = strtolower(trim((string) $value));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
        $text = trim((string) $text);

        return $text !== '' && !in_array($text, [
            'no',
            'none',
            'nil',
            'na',
            'n a',
            'normal',
            'no symptom',
            'no symptoms',
            'none symptoms',
            'no issues',
            'no issue',
            'nothing',
            'all good',
        ], true);
    }

    private function nullableString($value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function stringList($value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $value
        ), fn ($item) => $item !== ''));
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
