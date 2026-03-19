<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\PetDailyCare;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetDailyCareController extends Controller
{
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.task_key' => ['nullable', 'string', 'max:100'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.scheduled_time' => ['nullable', 'string', 'max:40'],
            'items.*.icon' => ['nullable', 'string', 'max:32'],
            'items.*.is_completed' => ['nullable', 'boolean'],
            'items.*.completed_at' => ['nullable', 'date'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $userId = (int) $payload['user_id'];
        if ((int) $pet->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Provided user_id does not belong to this pet.',
            ], 422);
        }

        $careDate = $this->normalizeDate($payload['care_date'] ?? null) ?? Carbon::today()->toDateString();
        // `replace_for_date` is accepted for backward compatibility, but this
        // endpoint now always treats the payload as the full daily state.
        $records = collect();

        DB::transaction(function () use ($payload, $petId, $userId, $careDate, &$records) {
            // Serialize writes per pet to avoid overlapping save requests.
            Pet::query()->whereKey($petId)->lockForUpdate()->first();

            PetDailyCare::query()
                ->where('pet_id', $petId)
                ->where('user_id', $userId)
                ->whereDate('care_date', $careDate)
                ->delete();

            foreach ($payload['items'] as $index => $item) {
                $title = trim((string) ($item['title'] ?? ''));
                $scheduledTime = isset($item['scheduled_time']) && $item['scheduled_time'] !== ''
                    ? trim((string) $item['scheduled_time'])
                    : null;
                $taskKey = isset($item['task_key']) && $item['task_key'] !== ''
                    ? trim((string) $item['task_key'])
                    : null;
                $isCompleted = $this->toBool($item['is_completed'] ?? false);
                $completedAt = $isCompleted
                    ? ($this->normalizeDateTime($item['completed_at'] ?? null) ?? now())
                    : null;

                PetDailyCare::query()->create([
                    'pet_id' => $petId,
                    'user_id' => $userId,
                    'care_date' => $careDate,
                    'task_key' => $taskKey,
                    'title' => $title,
                    'scheduled_time' => $scheduledTime,
                    'icon' => isset($item['icon']) && $item['icon'] !== '' ? trim((string) $item['icon']) : null,
                    'is_completed' => $isCompleted,
                    'completed_at' => $completedAt,
                    'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : $index,
                    'notes' => isset($item['notes']) && $item['notes'] !== '' ? trim((string) $item['notes']) : null,
                ]);
            }

            $records = PetDailyCare::query()
                ->where('pet_id', $petId)
                ->where('user_id', $userId)
                ->whereDate('care_date', $careDate)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });

        return response()->json([
            'success' => true,
            'message' => 'Daily care saved successfully.',
            'data' => $this->formatCarePayload($petId, $userId, $careDate, $records),
        ]);
    }

    private function formatCarePayload(int $petId, int $userId, string $careDate, Collection $records): array
    {
        $doneCount = (int) $records->where('is_completed', true)->count();
        $totalCount = (int) $records->count();

        return [
            'pet_id' => $petId,
            'user_id' => $userId,
            'care_date' => $careDate,
            'done_count' => $doneCount,
            'total_count' => $totalCount,
            'progress_text' => "{$doneCount}/{$totalCount} done",
            'items' => $records->map(function (PetDailyCare $row) {
                return [
                    'id' => $row->id,
                    'task_key' => $row->task_key,
                    'title' => $row->title,
                    'scheduled_time' => $row->scheduled_time,
                    'icon' => $row->icon,
                    'is_completed' => (bool) $row->is_completed,
                    'completed_at' => optional($row->completed_at)->toDateTimeString(),
                    'sort_order' => (int) $row->sort_order,
                    'notes' => $row->notes,
                ];
            })->values(),
        ];
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
