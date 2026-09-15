<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ClinicQueueRotationService
{
    private const MANUAL_OFFSET_KEY = 'clinic_queue_rotation_manual_offset';
    private const START_DATE = '2026-05-10';

    public function rotate(Collection $items): Collection
    {
        $count = $items->count();
        if ($count <= 1) {
            return $items->values();
        }

        $offset = $this->currentOffset() % $count;
        if ($offset === 0) {
            return $items->values();
        }

        return $items->slice($offset)
            ->concat($items->slice(0, $offset))
            ->values();
    }

    public function shiftManually(int $steps = 1): int
    {
        $offset = $this->manualOffset() + max(1, $steps);
        Cache::forever(self::MANUAL_OFFSET_KEY, $offset);

        return $offset;
    }

    public function status(?int $itemCount = null): array
    {
        $offset = $this->currentOffset();

        return [
            'daily_offset' => $this->dailyOffset(),
            'manual_offset' => $this->manualOffset(),
            'current_offset' => $offset,
            'effective_offset' => $itemCount && $itemCount > 0 ? $offset % $itemCount : 0,
        ];
    }

    private function currentOffset(): int
    {
        return $this->dailyOffset() + $this->manualOffset();
    }

    private function dailyOffset(): int
    {
        $start = Carbon::parse(self::START_DATE, 'Asia/Kolkata')->startOfDay();
        $today = Carbon::now('Asia/Kolkata')->startOfDay();

        return max(0, $start->diffInDays($today));
    }

    private function manualOffset(): int
    {
        return (int) Cache::get(self::MANUAL_OFFSET_KEY, 0);
    }
}
