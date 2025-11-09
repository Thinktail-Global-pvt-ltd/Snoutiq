<?php

namespace App\Services\Founder;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RevenueService
{
    public function build(string $grouping, Carbon $from, Carbon $to, bool $includeProjections = true): array
    {
        $grouping = $grouping === 'day' ? 'day' : 'month';

        $rows = Transaction::query()
            ->completed()
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get(['amount_paise', 'created_at']);

        $buckets = $grouping === 'day'
            ? $this->bucketByDay($rows, $from, $to)
            : $this->bucketByMonth($rows, $from, $to);

        return [
            'buckets' => array_values($buckets),
            'projectionNextMonthPaise' => $includeProjections
                ? $this->projectNextMonth()
                : null,
        ];
    }

    private function bucketByDay(Collection $rows, Carbon $from, Carbon $to): array
    {
        $bucket = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        while ($cursor <= $end) {
            $key = $cursor->toDateString();
            $bucket[$key] = [
                'periodLabel' => $key,
                'revenuePaise' => 0,
            ];
            $cursor->addDay();
        }

        foreach ($rows as $row) {
            $key = $row->created_at->toDateString();
            if (! isset($bucket[$key])) {
                $bucket[$key] = [
                    'periodLabel' => $key,
                    'revenuePaise' => 0,
                ];
            }
            $bucket[$key]['revenuePaise'] += (int) $row->amount_paise;
        }

        return $bucket;
    }

    private function bucketByMonth(Collection $rows, Carbon $from, Carbon $to): array
    {
        $bucket = [];
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();

        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $bucket[$key] = [
                'periodLabel' => $key,
                'revenuePaise' => 0,
            ];
            $cursor->addMonth();
        }

        foreach ($rows as $row) {
            $key = $row->created_at->format('Y-m');
            if (! isset($bucket[$key])) {
                $bucket[$key] = [
                    'periodLabel' => $key,
                    'revenuePaise' => 0,
                ];
            }
            $bucket[$key]['revenuePaise'] += (int) $row->amount_paise;
        }

        return $bucket;
    }

    private function projectNextMonth(): int
    {
        $start = now()->copy()->subMonths(3)->startOfMonth();

        $rows = Transaction::query()
            ->completed()
            ->where('created_at', '>=', $start)
            ->get(['amount_paise', 'created_at']);

        if ($rows->isEmpty()) {
            return 0;
        }

        $grouped = $rows->groupBy(fn ($row) => $row->created_at->format('Y-m'))
            ->map(fn ($group) => (int) $group->sum('amount_paise'))
            ->values();

        if ($grouped->isEmpty()) {
            return 0;
        }

        return (int) round($grouped->avg());
    }
}

