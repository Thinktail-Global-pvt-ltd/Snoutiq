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
            'symptom_status' => $this->hasMeaningfulSymptoms($entry['symptoms'] ?? null) ? 'symptom_note' : 'no_symptom_or_not_applicable',
        ], $symptomEntries);

        $prompt = "You are a pet health journaling assistant for Snoutiq. Do not diagnose, prescribe, or claim the pet is sick.\n"
            ."Analyze every symptom entry in the timeline, including entries that mean no symptoms. Return JSON only with keys: analysis_text, overall_assessment, current_status, timeline_summary, recent_window_summary, key_patterns, watch_points, reassuring_signals, recent_symptom_notes, latest_symptom_note, repeated_symptoms, possible_pattern, flag_level, recommended_action, next_steps, disclaimer.\n"
            ."flag_level must be one of None, Watch, Alert. Use safe language like worth monitoring or worth a vet check.\n"
            ."key_patterns, watch_points, reassuring_signals, recent_symptom_notes, next_steps, and repeated_symptoms must be arrays of short practical strings.\n"
            ."Weight recency heavily: the latest entry and last 3 entries should drive current_status, flag_level, recommended_action, and next_steps. Older entries are background context only.\n"
            ."Interpret No, No symptoms, Na, N/A, none, nil, normal, and no issues as no active symptom, but still use them as reassuring timeline context.\n"
            ."Make the analysis personalized to {$petName}: mention actual logged themes such as skin, nails, toes, paws, licking, panting, poop, or digestion when present. Do not give a generic template.\n"
            ."Use dates only when useful. Keep it detailed but concise enough for an API response.\n\n"
            .'Complete symptom timeline: '.json_encode($payload);

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
        $meaningfulEntries = array_values(array_filter($symptomEntries, fn ($entry) => $this->hasMeaningfulSymptoms($entry['symptoms'] ?? null)));
        $meaningfulCount = count($meaningfulEntries);
        $noSymptomCount = $entryCount - $meaningfulCount;
        $recentEntries = array_slice($symptomEntries, -3);
        $recentMeaningfulEntries = array_values(array_filter($recentEntries, fn ($entry) => $this->hasMeaningfulSymptoms($entry['symptoms'] ?? null)));
        $recentMeaningfulCount = count($recentMeaningfulEntries);
        $recentNoSymptomCount = count($recentEntries) - $recentMeaningfulCount;

        if ($entryCount === 0) {
            return [
                'analysis_text' => "No active symptom pattern is showing for {$petName} right now.",
                'overall_assessment' => "There are no symptom notes to analyze yet.",
                'current_status' => "No symptom timeline has been started.",
                'timeline_summary' => "The saved entries do not include symptom notes yet.",
                'recent_window_summary' => "No recent symptom entries are available yet.",
                'key_patterns' => [],
                'watch_points' => [],
                'reassuring_signals' => [],
                'recent_symptom_notes' => [],
                'latest_symptom_note' => null,
                'repeated_symptoms' => [],
                'possible_pattern' => "No repeated symptom pattern is available from the current notes.",
                'flag_level' => 'None',
                'recommended_action' => "Keep adding any new symptom notes so {$petName}'s changes are easier to follow.",
                'total_symptom_entries' => 0,
                'meaningful_symptom_entry_count' => 0,
                'no_symptom_entry_count' => 0,
                'recent_meaningful_symptom_entry_count' => 0,
                'recent_no_symptom_entry_count' => 0,
                'next_steps' => [
                    "Continue daily food, energy, water, symptom, and digestion updates.",
                    "Add a short note if any new symptom appears.",
                ],
                'disclaimer' => "This is not a diagnosis and is based only on the symptom notes entered.",
            ];
        }

        if ($meaningfulCount === 0) {
            return [
                'analysis_text' => "{$petName}'s symptom timeline currently shows {$entryCount} entries, and they all read like no active symptoms or not-applicable notes.",
                'overall_assessment' => "No active symptom pattern is visible from the saved symptom timeline.",
                'current_status' => "Latest entry does not report an active symptom.",
                'timeline_summary' => "{$entryCount} symptom rows were reviewed; {$noSymptomCount} are reassuring no-symptom/not-applicable entries.",
                'recent_window_summary' => "The latest ".count($recentEntries)." entries do not report an active symptom.",
                'key_patterns' => [],
                'watch_points' => [],
                'reassuring_signals' => [
                    "Recent symptom entries are marked as no symptoms or not applicable.",
                    "No repeated active symptom note is present in the current symptom timeline.",
                ],
                'recent_symptom_notes' => [],
                'latest_symptom_note' => null,
                'repeated_symptoms' => [],
                'possible_pattern' => "No repeated active symptom pattern is available from the current notes.",
                'flag_level' => 'None',
                'recommended_action' => "Keep adding a short note if anything new appears for {$petName}.",
                'total_symptom_entries' => $entryCount,
                'meaningful_symptom_entry_count' => 0,
                'no_symptom_entry_count' => $noSymptomCount,
                'recent_meaningful_symptom_entry_count' => 0,
                'recent_no_symptom_entry_count' => $recentNoSymptomCount,
                'next_steps' => [
                    "Continue daily updates for food, energy, water, symptoms, and digestion.",
                    "Use the symptom field only for a clear change, such as itching, limping, panting, vomiting, stool changes, or skin concerns.",
                ],
                'disclaimer' => "This is not a diagnosis and is based only on the symptom notes entered.",
            ];
        }

        $latest = trim((string) ($meaningfulEntries[$meaningfulCount - 1]['symptoms'] ?? ''));
        $normalized = array_map(fn ($entry) => strtolower(trim((string) ($entry['symptoms'] ?? ''))), $meaningfulEntries);
        $repeatCount = count($normalized) - count(array_unique($normalized));
        $flag = $recentMeaningfulCount >= 2 || ($recentMeaningfulCount >= 1 && $repeatCount > 0) ? 'Watch' : 'None';
        $repeatedSymptoms = array_values(array_unique(array_diff_assoc($normalized, array_unique($normalized))));
        $themes = $this->symptomThemes($meaningfulEntries);
        $recentThemes = $this->symptomThemes($recentMeaningfulEntries);
        $themeText = empty($themes) ? 'the noted changes' : implode(', ', $themes);
        $recentThemeText = empty($recentThemes) ? 'no active symptom theme' : implode(', ', $recentThemes);
        $firstMeaningful = trim((string) ($meaningfulEntries[0]['symptoms'] ?? ''));
        $latestRaw = $symptomEntries[$entryCount - 1] ?? null;
        $latestRawSymptoms = trim((string) ($latestRaw['symptoms'] ?? ''));
        $latestRawIsMeaningful = $this->hasMeaningfulSymptoms($latestRawSymptoms);

        return [
            'analysis_text' => "{$petName}'s full symptom timeline has {$entryCount} logged rows: {$meaningfulCount} active symptom notes and {$noSymptomCount} no-symptom/not-applicable entries. Recent entries carry the most weight: the latest ".count($recentEntries)." rows include {$recentMeaningfulCount} active symptom note(s) and point to {$recentThemeText}. Older notes mainly add background around {$themeText}.",
            'overall_assessment' => $flag === 'Watch'
                ? "{$petName}'s recent symptom notes are worth watching because a current or repeated theme is showing up, but this is not a diagnosis."
                : "{$petName}'s older symptom notes are useful context, but the latest entries do not strongly suggest an active symptom pattern.",
            'current_status' => $latestRawIsMeaningful
                ? "Latest entry reports: {$latestRawSymptoms}."
                : "Latest entry does not report an active symptom.",
            'timeline_summary' => "{$entryCount} rows reviewed from the symptom timeline; {$meaningfulCount} contain active symptom detail and {$noSymptomCount} are no-symptom/not-applicable entries.",
            'recent_window_summary' => "Last ".count($recentEntries)." entries: {$recentMeaningfulCount} active symptom note(s), {$recentNoSymptomCount} no-symptom/not-applicable note(s). Recent theme: {$recentThemeText}.",
            'key_patterns' => $this->patternBullets($themes, $meaningfulEntries),
            'watch_points' => [
                "Whether {$recentThemeText} repeats in the next update.",
                "Whether appetite, energy, water intake, or digestion changes along with the symptom notes.",
                "Whether any note becomes more frequent, more intense, or starts affecting comfort.",
            ],
            'reassuring_signals' => $noSymptomCount > 0
                ? ["{$noSymptomCount} entries are marked as no symptoms or not applicable, which is useful context."]
                : [],
            'recent_symptom_notes' => $this->entryNoteList($recentMeaningfulEntries),
            'latest_symptom_note' => $latest,
            'repeated_symptoms' => $repeatedSymptoms,
            'possible_pattern' => $repeatCount > 0
                ? "At least one symptom note appears more than once."
                : "No exact repeated symptom text is visible; recent themes like {$recentThemeText} should be compared across future updates.",
            'flag_level' => $flag,
            'recommended_action' => $flag === 'Watch'
                ? "Keep an eye on {$petName}; if the same symptom keeps showing up, gets worse, or feels unusual, a vet check is a good idea."
                : "Check {$petName} again later today or tomorrow and add any change you notice.",
            'total_symptom_entries' => $entryCount,
            'meaningful_symptom_entry_count' => $meaningfulCount,
            'no_symptom_entry_count' => $noSymptomCount,
            'recent_meaningful_symptom_entry_count' => $recentMeaningfulCount,
            'recent_no_symptom_entry_count' => $recentNoSymptomCount,
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
            'overall_assessment' => trim((string) ($result['overall_assessment'] ?? '')) ?: $fallback['overall_assessment'],
            'current_status' => trim((string) ($result['current_status'] ?? '')) ?: $fallback['current_status'],
            'timeline_summary' => trim((string) ($result['timeline_summary'] ?? $result['trend_summary'] ?? '')) ?: $fallback['timeline_summary'],
            'recent_window_summary' => trim((string) ($result['recent_window_summary'] ?? '')) ?: $fallback['recent_window_summary'],
            'key_patterns' => $this->stringList($result['key_patterns'] ?? $fallback['key_patterns']),
            'watch_points' => $this->stringList($result['watch_points'] ?? $fallback['watch_points']),
            'reassuring_signals' => $this->stringList($result['reassuring_signals'] ?? $fallback['reassuring_signals']),
            'recent_symptom_notes' => $this->stringList($result['recent_symptom_notes'] ?? $fallback['recent_symptom_notes']),
            'latest_symptom_note' => $this->nullableString($result['latest_symptom_note'] ?? $fallback['latest_symptom_note']),
            'repeated_symptoms' => $this->stringList($result['repeated_symptoms'] ?? $fallback['repeated_symptoms']),
            'possible_pattern' => trim((string) ($result['possible_pattern'] ?? '')) ?: $fallback['possible_pattern'],
            'flag_level' => $flag,
            'recommended_action' => trim((string) ($result['recommended_action'] ?? '')) ?: $fallback['recommended_action'],
            'total_symptom_entries' => $fallback['total_symptom_entries'],
            'meaningful_symptom_entry_count' => $fallback['meaningful_symptom_entry_count'],
            'no_symptom_entry_count' => $fallback['no_symptom_entry_count'],
            'recent_meaningful_symptom_entry_count' => $fallback['recent_meaningful_symptom_entry_count'],
            'recent_no_symptom_entry_count' => $fallback['recent_no_symptom_entry_count'],
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

    private function symptomThemes(array $entries): array
    {
        $themes = [];
        foreach ($entries as $entry) {
            $text = strtolower((string) ($entry['symptoms'] ?? ''));
            if (str_contains($text, 'skin') || str_contains($text, 'nail') || str_contains($text, 'toe')) {
                $themes[] = 'skin/nail/toe changes';
            }
            if (str_contains($text, 'paw') || str_contains($text, 'lick')) {
                $themes[] = 'paw licking or paw discomfort';
            }
            if (str_contains($text, 'pant')) {
                $themes[] = 'panting';
            }
            if (str_contains($text, 'poop') || str_contains($text, 'stool') || str_contains($text, 'motion')) {
                $themes[] = 'poop/digestion changes';
            }
        }

        return array_values(array_unique($themes));
    }

    private function patternBullets(array $themes, array $entries): array
    {
        if (empty($themes)) {
            return array_map(
                fn ($entry) => trim((string) ($entry['entry_date'] ?? 'One entry')).': '.trim((string) ($entry['symptoms'] ?? '')),
                array_slice($entries, -3)
            );
        }

        return array_map(
            fn ($theme) => ucfirst($theme).' appears in the symptom timeline.',
            $themes
        );
    }

    private function entryNoteList(array $entries): array
    {
        return array_values(array_map(
            fn ($entry) => trim((string) ($entry['entry_date'] ?? 'Recent entry')).': '.trim((string) ($entry['symptoms'] ?? '')),
            $entries
        ));
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
