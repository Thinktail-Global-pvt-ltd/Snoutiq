<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\PetDailyCare;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetDailyCareController extends Controller
{
    private const BUNDLED_TASK_KEY = '__daily_bundle__';

    public function store(Request $request, int $petId)
    {
        if (! Schema::hasTable('pet_daily_cares')) {
            return response()->json([
                'success' => false,
                'message' => 'pet_daily_cares table is missing. Please run migrations.',
            ], 500);
        }

        $pet = Pet::query()->select(['id', 'user_id'])->find($petId);
        if (! $pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found.',
            ], 404);
        }

        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'care_date' => ['nullable', 'date'],
            'replace_for_date' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.task_key' => ['nullable', 'string', 'max:100'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.scheduled_time' => ['nullable', 'string', 'max:40'],
            'items.*.icon' => ['nullable', 'string', 'max:32'],
            'items.*.is_completed' => ['nullable', 'boolean'],
            'items.*.completed_at' => ['nullable', 'date'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $userId = (int) $payload['user_id'];
        if ((int) $pet->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Provided user_id does not belong to this pet.',
            ], 422);
        }

        $careDate = $this->normalizeDate($payload['care_date'] ?? null) ?? Carbon::today()->toDateString();
        $dailyNotes = isset($payload['notes']) && trim((string) $payload['notes']) !== ''
            ? trim((string) $payload['notes'])
            : null;
        $items = $this->normalizeItems($payload['items']);
        $doneCount = count(array_filter($items, fn (array $item): bool => (bool) ($item['is_completed'] ?? false)));
        $totalCount = count($items);
        $allCompleted = $totalCount > 0 && $doneCount === $totalCount;
        $itemsJson = json_encode([
            'notes' => $dailyNotes,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
        if (!is_string($itemsJson)) {
            $itemsJson = '{"notes":null,"items":[]}';
        }

        DB::transaction(function () use ($petId, $userId, $careDate, $itemsJson, $allCompleted) {
            // Serialize writes per pet to avoid overlapping save requests.
            Pet::query()->whereKey($petId)->lockForUpdate()->first();

            $record = PetDailyCare::query()->updateOrCreate([
                'pet_id' => $petId,
                'user_id' => $userId,
                'care_date' => $careDate,
            ], [
                'task_key' => self::BUNDLED_TASK_KEY,
                'title' => 'Daily care',
                'scheduled_time' => null,
                'icon' => null,
                'is_completed' => $allCompleted,
                'completed_at' => $allCompleted ? now() : null,
                'sort_order' => 0,
                'notes' => $itemsJson,
            ]);

            // Keep only one row per (user_id, pet_id, care_date).
            PetDailyCare::query()
                ->where('pet_id', $petId)
                ->where('user_id', $userId)
                ->whereDate('care_date', $careDate)
                ->where('id', '!=', $record->id)
                ->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Daily care saved successfully.',
            'data' => $this->formatCarePayload($petId, $userId, $careDate, $items, $dailyNotes),
        ]);
    }

    private function formatCarePayload(int $petId, int $userId, string $careDate, array $items, ?string $dailyNotes): array
    {
        $doneCount = count(array_filter($items, fn (array $item): bool => (bool) ($item['is_completed'] ?? false)));
        $totalCount = count($items);

        return [
            'pet_id' => $petId,
            'user_id' => $userId,
            'care_date' => $careDate,
            'done_count' => $doneCount,
            'total_count' => $totalCount,
            'progress_text' => "{$doneCount}/{$totalCount} done",
            'notes' => $dailyNotes,
            'items' => array_values($items),
        ];
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            $isCompleted = $this->toBool($item['is_completed'] ?? false);
            $completedAt = $isCompleted
                ? ($this->normalizeDateTime($item['completed_at'] ?? null) ?? now())
                : null;

            $normalized[] = [
                'id' => null,
                'task_key' => isset($item['task_key']) && $item['task_key'] !== ''
                    ? trim((string) $item['task_key'])
                    : null,
                'title' => trim((string) ($item['title'] ?? '')),
                'scheduled_time' => isset($item['scheduled_time']) && $item['scheduled_time'] !== ''
                    ? trim((string) $item['scheduled_time'])
                    : null,
                'icon' => isset($item['icon']) && $item['icon'] !== '' ? trim((string) $item['icon']) : null,
                'is_completed' => $isCompleted,
                'completed_at' => $completedAt?->toDateTimeString(),
                'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : $index,
                'notes' => null,
                '_index' => $index,
            ];
        }

        usort($normalized, function (array $a, array $b): int {
            $sortCompare = ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
            if ($sortCompare !== 0) {
                return $sortCompare;
            }

            return ($a['_index'] ?? 0) <=> ($b['_index'] ?? 0);
        });

        foreach ($normalized as &$item) {
            unset($item['_index']);
        }
        unset($item);

        foreach ($normalized as $index => &$item) {
            $item['id'] = $index + 1;
        }
        unset($item);

        return $normalized;
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        $normalized = strtolower(trim((string) $value, " \t\n\r\0\x0B\"'"));

        return in_array($normalized, ['1', 'true', 'yes', 'y'], true);
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeDateTime($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
