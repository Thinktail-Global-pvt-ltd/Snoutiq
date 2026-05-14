<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthPulseEntry;
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
                'food' => $this->normalizeSignal($payload['food'] ?? $payload['appetite'] ?? $payload['food_intake']),
                'energy' => $this->normalizeSignal($payload['energy'] ?? $payload['energy_level']),
                'water' => $this->normalizeSignal($payload['water'] ?? $payload['water_intake']),
                'symptoms' => $this->optionalText($payload['symptoms'] ?? null),
                'digestion_issue' => $this->toBool($payload['digestion_issue'] ?? $payload['digestion'] ?? $payload['poop_issue'] ?? false),
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

        $this->notifications->sendAiFlagNotification($pet, $entry, $payload['fcm_token'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Health pulse saved successfully.',
            'data' => $this->formatEntry($entry),
            'streak' => $this->buildStreakPayload((int) $pet->id),
        ]);
    }

    public function entries(Request $request, int $petId)
    {
        $this->ensureTable();
        $pet = $this->resolvePet($petId, $request->integer('user_id') ?: null);
        $limit = min(max((int) $request->query('limit', 30), 1), 100);
        $entries = HealthPulseEntry::query()
            ->where('pet_id', $pet->id)
            ->orderByDesc('entry_date')
            ->limit($limit)
            ->get()
            ->map(fn (HealthPulseEntry $entry) => $this->formatEntry($entry))
            ->values();

        return response()->json(['success' => true, 'data' => $entries]);
    }

    public function today(Request $request, int $petId)
    {
        $this->ensureTable();
        $pet = $this->resolvePet($petId, $request->integer('user_id') ?: null);
        $date = Carbon::today()->toDateString();
        $entry = HealthPulseEntry::query()
            ->where('pet_id', $pet->id)
            ->whereDate('entry_date', $date)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'pet_id' => (int) $pet->id,
                'pet_name' => $pet->name,
                'entry_date' => $date,
                'completed' => $entry !== null,
                'banner_text' => ($pet->name ?: 'Your pet').($entry ? "'s health pulse logged ✓" : "'s daily health check is pending"),
                'entry' => $entry ? $this->formatEntry($entry) : null,
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
            'food' => ['required', 'string', 'max:40'],
            'energy' => ['required', 'string', 'max:40'],
            'water' => ['required', 'string', 'max:40'],
            'symptoms' => ['nullable', 'string'],
            'digestion_issue' => ['nullable'],
            'digestion' => ['nullable'],
            'poop_issue' => ['nullable'],
            'digestion_note' => ['nullable', 'string', 'max:255'],
            'poop_note' => ['nullable', 'string', 'max:255'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
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

    private function formatEntry(HealthPulseEntry $entry): array
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
            'digestion_issue' => (bool) $entry->digestion_issue,
            'digestion_note' => $entry->digestion_note,
            'ai' => [
                'short_summary' => $entry->ai_short_summary,
                'pattern_observation' => $entry->ai_pattern_observation,
                'flag_level' => $entry->ai_flag_level,
                'recommended_action' => $entry->ai_recommended_action,
                'analyzed_at' => $entry->ai_analyzed_at?->toDateTimeString(),
            ],
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

    private function normalizeSignal($value): string
    {
        return substr(trim((string) $value), 0, 40);
    }

    private function optionalText($value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
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
