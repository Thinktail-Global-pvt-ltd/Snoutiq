<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthPulseEntry;
use App\Models\HealthPulseSymptomAnalysis;
use App\Models\Pet;
use App\Services\HealthPulseAiService;
use App\Services\HealthPulseNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class HealthPulseController extends Controller
{
    public function __construct(
        private readonly HealthPulseAiService $ai,
        private readonly HealthPulseNotificationService $notifications
    ) {
    }

    public function store(Request $request)
    {
        $this->ensureTable();
        $payload = $this->validatePulsePayload($request);
        $pet = $this->resolvePet((int) $payload['pet_id'], isset($payload['user_id']) ? (int) $payload['user_id'] : null);
        $entryDate = $this->dateString($payload['entry_date'] ?? $payload['care_date'] ?? null);

        $entry = HealthPulseEntry::query()->updateOrCreate(
            ['pet_id' => $pet->id, 'entry_date' => $entryDate],
            [
                'user_id' => $pet->user_id,
                'food' => $this->normalizeSignal($this->firstPayloadValue($payload, ['food', 'appetite', 'food_intake'])),
                'energy' => $this->normalizeSignal($this->firstPayloadValue($payload, ['energy', 'energy_level'])),
                'water' => $this->normalizeSignal($this->firstPayloadValue($payload, ['water', 'water_intake'])),
                'symptoms' => $this->optionalText($payload['symptoms'] ?? null),
                'digestion_issue' => $this->normalizeOptionalBool($this->firstPayloadValue($payload, ['digestion_issue', 'digestion', 'poop_issue'])),
                'digestion_note' => $this->optionalText($payload['digestion_note'] ?? $payload['poop_note'] ?? null),
            ]
        );

        $recent = $this->recentEntryPayloads((int) $pet->id, 6, $entry->id);
        $analysis = $this->ai->analyzeEntry($pet, $entry, $recent);
        $entry->forceFill([
            'ai_flag_level' => $analysis['flag_level'],
            'ai_short_summary' => $analysis['short_summary'],
            'ai_pattern_observation' => $analysis['pattern_observation'],
            'ai_recommended_action' => $analysis['recommended_action'],
            'ai_payload' => $analysis,
            'ai_analyzed_at' => now(),
        ])->save();

        $symptomAnalysis = $this->storeSymptomAnalysis($pet, $entry);

        $this->notifications->sendAiFlagNotification(
            $pet,
            $entry,
            $payload['fcm_token'] ?? null,
            $payload['user_name'] ?? $payload['name'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Health pulse saved successfully.',
            'data' => $this->formatEntry($entry, $symptomAnalysis),
            'streak' => $this->buildStreakPayload((int) $pet->id),
        ]);
    }

    public function entries(Request $request, int $petId)
    {
        $this->ensureTable();
        $pet = $this->resolvePet($petId, $request->integer('user_id') ?: null);
        $limit = min(max((int) $request->query('limit', 30), 1), 100);
        $hasSymptomAnalyses = Schema::hasTable('health_pulse_symptom_analyses');
        $entries = HealthPulseEntry::query()
            ->where('pet_id', $pet->id)
            ->when($hasSymptomAnalyses, fn ($query) => $query->with('symptomAnalysis'))
            ->orderByDesc('entry_date')
            ->limit($limit)
            ->get()
            ->map(fn (HealthPulseEntry $entry) => $this->formatEntry($entry, $hasSymptomAnalyses ? $entry->symptomAnalysis : null))
            ->values();

        return response()->json(['success' => true, 'data' => $entries]);
    }

    public function today(Request $request, int $petId)
    {
        $this->ensureTable();
        $pet = $this->resolvePet($petId, $request->integer('user_id') ?: null);
        $date = Carbon::today()->toDateString();
        $hasSymptomAnalyses = Schema::hasTable('health_pulse_symptom_analyses');
        $entry = HealthPulseEntry::query()
            ->where('pet_id', $pet->id)
            ->whereDate('entry_date', $date)
            ->when($hasSymptomAnalyses, fn ($query) => $query->with('symptomAnalysis'))
            ->first();
        $completed = $entry !== null && $this->isCompleteEntry($entry);

        return response()->json([
            'success' => true,
            'data' => [
                'pet_id' => (int) $pet->id,
                'pet_name' => $pet->name,
                'entry_date' => $date,
                'completed' => $completed,
                'banner_text' => ($pet->name ?: 'Your pet').($completed ? "'s health pulse logged ✓" : "'s daily health check is pending"),
                'entry' => $entry ? $this->formatEntry($entry, $hasSymptomAnalyses ? $entry->symptomAnalysis : null) : null,
            ],
        ]);
    }

    public function streak(Request $request, int $petId)
    {
        $this->ensureTable();
        $pet = $this->resolvePet($petId, $request->integer('user_id') ?: null);

        return response()->json(['success' => true, 'data' => $this->buildStreakPayload((int) $pet->id)]);
    }

    public function report(Request $request, int $petId)
    {
        $this->ensureTable();
        $pet = $this->resolvePet($petId, $request->integer('user_id') ?: null);
        $entries = HealthPulseEntry::query()
            ->where('pet_id', $pet->id)
            ->orderBy('entry_date')
            ->get();
        $entryPayloads = $entries->map(fn (HealthPulseEntry $entry) => $this->formatEntry($entry))->values()->all();
        $entryCount = count($entryPayloads);

        return response()->json([
            'success' => true,
            'data' => [
                'report_unlocked' => $entryCount >= 7,
                'required_entries' => 7,
                'entries_remaining' => max(0, 7 - $entryCount),
                'pet' => $this->formatPet($pet),
                'entry_count' => $entryCount,
                'date_range' => [
                    'start' => $entries->first()?->entry_date?->toDateString(),
                    'end' => $entries->last()?->entry_date?->toDateString(),
                ],
                'charts' => [
                    'appetite' => $this->chartSeries($entryPayloads, 'food'),
                    'energy' => $this->chartSeries($entryPayloads, 'energy'),
                    'water' => $this->chartSeries($entryPayloads, 'water'),
                ],
                'digestion_log' => array_values(array_filter(array_map(fn ($entry) => [
                    'entry_date' => $entry['entry_date'],
                    'digestion_issue' => $entry['digestion_issue'],
                    'digestion_note' => $entry['digestion_note'],
                ], $entryPayloads), fn ($row) => $row['digestion_issue'] || $row['digestion_note'])),
                'symptoms_log' => array_values(array_filter(array_map(fn ($entry) => [
                    'entry_date' => $entry['entry_date'],
                    'symptoms' => $entry['symptoms'],
                ], $entryPayloads), fn ($row) => $row['symptoms'] !== null)),
                'ai_health_summary' => $entryCount >= 7 ? $this->ai->summarizeReport($pet, $entryPayloads) : null,
                'entries' => $entryPayloads,
            ],
        ]);
    }

    public function saveFromDailyCare(Pet $pet, array $pulsePayload, ?string $careDate = null): ?HealthPulseEntry
    {
        $request = Request::create('/api/v1/pulse/entry', 'POST', array_merge($pulsePayload, [
            'pet_id' => $pet->id,
            'user_id' => $pet->user_id,
            'entry_date' => $pulsePayload['entry_date'] ?? $pulsePayload['care_date'] ?? $careDate,
        ]));

        $response = $this->store($request);
        $data = $response->getData(true);
        $entryId = $data['data']['id'] ?? null;

        return $entryId ? HealthPulseEntry::query()->find($entryId) : null;
    }

    private function validatePulsePayload(Request $request): array
    {
        $payload = $request->all();
        foreach (['food' => ['appetite', 'food_intake'], 'energy' => ['energy_level'], 'water' => ['water_intake']] as $target => $aliases) {
            if (!isset($payload[$target])) {
                foreach ($aliases as $alias) {
                    if (isset($payload[$alias])) {
                        $payload[$target] = $payload[$alias];
                        break;
                    }
                }
            }
        }

        validator($payload, [
            'pet_id' => ['required', 'integer', 'exists:pets,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'entry_date' => ['nullable', 'date'],
            'care_date' => ['nullable', 'date'],
            'food' => ['nullable', 'string', 'max:40'],
            'appetite' => ['nullable', 'string', 'max:40'],
            'food_intake' => ['nullable', 'string', 'max:40'],
            'energy' => ['nullable', 'string', 'max:40'],
            'energy_level' => ['nullable', 'string', 'max:40'],
            'water' => ['nullable', 'string', 'max:40'],
            'water_intake' => ['nullable', 'string', 'max:40'],
            'symptoms' => ['nullable', 'string'],
            'digestion_issue' => ['nullable'],
            'digestion' => ['nullable'],
            'poop_issue' => ['nullable'],
            'digestion_note' => ['nullable', 'string', 'max:255'],
            'poop_note' => ['nullable', 'string', 'max:255'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return $payload;
    }

    private function resolvePet(int $petId, ?int $userId = null): Pet
    {
        $pet = Pet::query()->find($petId);
        if (!$pet) {
            throw ValidationException::withMessages(['pet_id' => ['Pet not found.']]);
        }
        if ($userId !== null && (int) $pet->user_id !== $userId) {
            throw ValidationException::withMessages(['user_id' => ['Provided user_id does not belong to this pet.']]);
        }

        return $pet;
    }

    private function ensureTable(): void
    {
        if (!Schema::hasTable('health_pulse_entries')) {
            abort(response()->json([
                'success' => false,
                'message' => 'health_pulse_entries table is missing. Please run migrations.',
            ], 500));
        }
    }

    private function buildStreakPayload(int $petId): array
    {
        $dates = HealthPulseEntry::query()
            ->where('pet_id', $petId)
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();
        $entryCount = $dates->count();
        $today = Carbon::today();
        $cursor = $dates->contains($today->toDateString()) ? $today : $today->copy()->subDay();
        $streak = 0;

        while ($dates->contains($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return [
            'entry_count' => $entryCount,
            'current_streak' => $streak,
            'report_unlocked' => $entryCount >= 7,
            'entries_remaining' => max(0, 7 - $entryCount),
            'today_completed' => $dates->contains($today->toDateString()),
        ];
    }

    private function recentEntryPayloads(int $petId, int $limit, ?int $excludeId = null): array
    {
        return HealthPulseEntry::query()
            ->where('pet_id', $petId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderByDesc('entry_date')
            ->limit($limit)
            ->get()
            ->map(fn (HealthPulseEntry $entry) => $this->formatEntry($entry))
            ->values()
            ->all();
    }

    private function symptomEntryPayloads(int $petId, ?int $limit = null): array
    {
        return HealthPulseEntry::query()
            ->where('pet_id', $petId)
            ->whereNotNull('symptoms')
            ->where('symptoms', '!=', '')
            ->orderBy('entry_date')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get()
            ->map(fn (HealthPulseEntry $entry) => [
                'id' => (int) $entry->id,
                'entry_date' => $entry->entry_date?->toDateString(),
                'symptoms' => $entry->symptoms,
                'is_meaningful_symptom' => $this->hasMeaningfulSymptoms($entry->symptoms),
            ])
            ->values()
            ->all();
    }

    private function storeSymptomAnalysis(Pet $pet, HealthPulseEntry $entry): ?HealthPulseSymptomAnalysis
    {
        if (!Schema::hasTable('health_pulse_symptom_analyses')) {
            return null;
        }

        $symptomEntries = $this->symptomEntryPayloads((int) $pet->id);
        $analysis = $this->ai->analyzeSymptomTrend($pet, $symptomEntries);

        return HealthPulseSymptomAnalysis::query()->updateOrCreate(
            ['health_pulse_entry_id' => $entry->id],
            [
                'user_id' => $pet->user_id,
                'pet_id' => $pet->id,
                'entry_date' => $entry->entry_date?->toDateString(),
                'symptom_entry_count' => count($symptomEntries),
                'symptoms_snapshot' => $symptomEntries,
                'analysis_text' => $analysis['analysis_text'],
                'flag_level' => $analysis['flag_level'],
                'recommended_action' => $analysis['recommended_action'],
                'ai_payload' => $analysis,
                'analyzed_at' => now(),
            ]
        );
    }

    private function formatEntry(HealthPulseEntry $entry, ?HealthPulseSymptomAnalysis $symptomAnalysis = null): array
    {
        return [
            'id' => (int) $entry->id,
            'user_id' => (int) $entry->user_id,
            'pet_id' => (int) $entry->pet_id,
            'entry_date' => $entry->entry_date?->toDateString(),
            'food' => $entry->food,
            'energy' => $entry->energy,
            'water' => $entry->water,
            'symptoms' => $entry->symptoms,
            'digestion_issue' => $entry->digestion_issue,
            'digestion_note' => $entry->digestion_note,
            'is_complete' => $this->isCompleteEntry($entry),
            'ai' => [
                'short_summary' => $entry->ai_short_summary,
                'pattern_observation' => $entry->ai_pattern_observation,
                'flag_level' => $entry->ai_flag_level,
                'recommended_action' => $entry->ai_recommended_action,
                'analyzed_at' => $entry->ai_analyzed_at?->toDateTimeString(),
            ],
            'symptom_analysis' => $symptomAnalysis ? [
                'id' => (int) $symptomAnalysis->id,
                'symptom_entry_count' => (int) $symptomAnalysis->symptom_entry_count,
                'analysis_text' => $symptomAnalysis->analysis_text,
                'details' => [
                    'overall_assessment' => $symptomAnalysis->ai_payload['overall_assessment'] ?? null,
                    'current_status' => $symptomAnalysis->ai_payload['current_status'] ?? null,
                    'timeline_summary' => $symptomAnalysis->ai_payload['timeline_summary'] ?? $symptomAnalysis->ai_payload['trend_summary'] ?? null,
                    'key_patterns' => $symptomAnalysis->ai_payload['key_patterns'] ?? [],
                    'watch_points' => $symptomAnalysis->ai_payload['watch_points'] ?? [],
                    'reassuring_signals' => $symptomAnalysis->ai_payload['reassuring_signals'] ?? [],
                    'latest_symptom_note' => $symptomAnalysis->ai_payload['latest_symptom_note'] ?? null,
                    'repeated_symptoms' => $symptomAnalysis->ai_payload['repeated_symptoms'] ?? [],
                    'possible_pattern' => $symptomAnalysis->ai_payload['possible_pattern'] ?? null,
                    'total_symptom_entries' => $symptomAnalysis->ai_payload['total_symptom_entries'] ?? (int) $symptomAnalysis->symptom_entry_count,
                    'meaningful_symptom_entry_count' => $symptomAnalysis->ai_payload['meaningful_symptom_entry_count'] ?? null,
                    'no_symptom_entry_count' => $symptomAnalysis->ai_payload['no_symptom_entry_count'] ?? null,
                    'next_steps' => $symptomAnalysis->ai_payload['next_steps'] ?? [],
                    'disclaimer' => $symptomAnalysis->ai_payload['disclaimer'] ?? null,
                ],
                'flag_level' => $symptomAnalysis->flag_level,
                'recommended_action' => $symptomAnalysis->recommended_action,
                'analyzed_at' => $symptomAnalysis->analyzed_at?->toDateTimeString(),
            ] : null,
        ];
    }

    private function formatPet(Pet $pet): array
    {
        return [
            'id' => (int) $pet->id,
            'name' => $pet->name,
            'type' => $pet->pet_type ?? $pet->type ?? null,
            'breed' => $pet->breed ?? null,
            'gender' => $pet->pet_gender ?? $pet->gender ?? null,
            'dob' => $this->optionalDate($pet->pet_dob ?? $pet->dob ?? null),
            'age' => $pet->pet_age ?? null,
            'weight' => $pet->weight ?? null,
        ];
    }

    private function chartSeries(array $entries, string $field): array
    {
        return array_map(fn ($entry) => [
            'date' => $entry['entry_date'],
            'label' => $entry[$field],
            'value' => $this->signalScore($entry[$field]),
        ], $entries);
    }

    private function signalScore(?string $value): int
    {
        return match (strtolower(trim((string) $value))) {
            'none', 'poor', 'low', 'less', 'reduced', 'not_eating', 'not_drinking' => 1,
            'partial', 'medium', 'moderate', 'normal' => 2,
            'good', 'high', 'active', 'well', 'ate_well' => 3,
            default => 2,
        };
    }

    private function normalizeSignal($value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : substr($text, 0, 40);
    }

    private function firstPayloadValue(array $payload, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $payload[$key];
            }
        }

        return null;
    }

    private function isCompleteEntry(HealthPulseEntry $entry): bool
    {
        return $this->hasSignal($entry->food)
            && $this->hasSignal($entry->energy)
            && $this->hasSignal($entry->water)
            && $entry->digestion_issue !== null;
    }

    private function hasSignal($value): bool
    {
        return trim((string) $value) !== '';
    }

    private function normalizeOptionalBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->toBool($value);
    }

    private function optionalText($value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
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

    private function dateString($value): string
    {
        if ($value) {
            return Carbon::parse($value)->toDateString();
        }

        return Carbon::today()->toDateString();
    }

    private function optionalDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'issue', 'abnormal'], true);
    }
}
