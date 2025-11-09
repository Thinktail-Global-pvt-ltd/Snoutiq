<?php

namespace App\Services\Founder;

use App\Models\Alert;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_TTL_MINUTES = 5;

    public function build(?User $user, string $mode, string $period): array
    {
        $cacheKey = $this->cacheKey($user, $mode, $period);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($mode, $period) {
            [$rangeStart, $rangeEnd] = $this->resolveRange($period);
            $kpis = $this->calculateKpis();

            return [
                'summary' => [
                    'mode' => $mode,
                    'period' => $period,
                    'rangeStart' => $rangeStart->toIso8601String(),
                    'rangeEnd' => $rangeEnd->toIso8601String(),
                    'generatedAt' => now()->toIso8601String(),
                ],
                'kpis' => $kpis,
                'charts' => $this->buildCharts($rangeStart, $rangeEnd),
                'alerts' => [
                    'summary' => $this->alertSummary(),
                    'recent' => $this->recentAlerts(),
                ],
                'financialHealth' => $this->financialHealth($kpis),
            ];
        });
    }

    private function cacheKey(?User $user, string $mode, string $period): string
    {
        $identifier = $user?->getAuthIdentifier();
        $actor = $identifier ? 'user:'.$identifier : 'guest';

        return "founder:dashboard:{$actor}:{$mode}:{$period}";
    }

    private function resolveRange(string $period): array
    {
        $end = now();
        $start = match ($period) {
            '1y' => $end->copy()->subYear(),
            'all' => $this->firstTransactionDate() ?? $end->copy()->subMonths(6),
            default => $end->copy()->subMonths(6),
        };

        return [$start->startOfDay(), $end];
    }

    private function firstTransactionDate(): ?Carbon
    {
        $first = Transaction::query()->min('created_at');

        return $first ? Carbon::parse($first) : null;
    }

    private function calculateKpis(): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();
        $previousMonthEnd = $monthStart->copy()->subSecond();

        $monthlyRevenue = (int) Transaction::query()
            ->completed()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('amount_paise');

        $mtdRevenue = (int) Transaction::query()
            ->completed()
            ->where('created_at', '>=', $monthStart)
            ->sum('amount_paise');

        $last30Start = $now->copy()->subDays(30);

        $last30Transactions = (int) Transaction::query()
            ->whereBetween('created_at', [$last30Start, $now])
            ->count();

        $failedLast30 = (int) Transaction::query()
            ->where('status', 'failed')
            ->whereBetween('created_at', [$last30Start, $now])
            ->count();

        $failedRate = $last30Transactions > 0
            ? round($failedLast30 / $last30Transactions, 3)
            : 0;

        $activeStatuses = ['active', 'approved', 'claimed', 'published'];

        $totalClinics = (int) VetRegisterationTemp::count();
        $activeClinics = (int) VetRegisterationTemp::query()
            ->whereIn('status', $activeStatuses)
            ->count();

        return [
            'totalClinics' => $totalClinics,
            'activeClinics' => $activeClinics,
            'monthlyRevenuePaise' => $monthlyRevenue,
            'mtdRevenuePaise' => $mtdRevenue,
            'last30dTransactions' => $last30Transactions,
            'failedTxnRate' => $failedRate,
        ];
    }

    private function buildCharts(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $revenueRows = Transaction::query()
            ->completed()
            ->whereBetween('created_at', [$rangeStart->copy()->startOfMonth(), $rangeEnd])
            ->get(['amount_paise', 'created_at']);

        $monthly = $this->groupByMonth($revenueRows, $rangeStart, $rangeEnd);

        $last30Start = now()->subDays(30);
        $transactionRows = Transaction::query()
            ->whereBetween('created_at', [$last30Start, now()])
            ->get(['id', 'created_at']);

        $daily = $this->groupByDay($transactionRows, $last30Start, now());

        return [
            'revenueByMonth' => array_values($monthly),
            'transactionsByDay' => array_values($daily),
        ];
    }

    private function groupByMonth(Collection $rows, Carbon $start, Carbon $end): array
    {
        $bucket = [];
        $cursor = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();

        while ($cursor <= $endMonth) {
            $key = $cursor->format('Y-m');
            $bucket[$key] = [
                'period' => $key,
                'label' => $cursor->format('M'),
                'revenuePaise' => 0,
            ];
            $cursor->addMonth();
        }

        foreach ($rows as $row) {
            $key = $row->created_at->format('Y-m');
            $bucket[$key]['revenuePaise'] += (int) $row->amount_paise;
        }

        return $bucket;
    }

    private function groupByDay(Collection $rows, Carbon $start, Carbon $end): array
    {
        $bucket = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor <= $end) {
            $key = $cursor->toDateString();
            $bucket[$key] = [
                'date' => $key,
                'count' => 0,
            ];
            $cursor->addDay();
        }

        foreach ($rows as $row) {
            $key = $row->created_at->toDateString();
            if (! isset($bucket[$key])) {
                $bucket[$key] = [
                    'date' => $key,
                    'count' => 0,
                ];
            }
            $bucket[$key]['count']++;
        }

        return $bucket;
    }

    private function alertSummary(): array
    {
        $counts = Alert::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        $summary = [];
        foreach (Alert::TYPES as $type) {
            $summary[$type] = (int) ($counts[$type] ?? 0);
        }

        return $summary;
    }

    private function recentAlerts(): array
    {
        return Alert::query()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (Alert $alert) {
                return [
                    'id' => (string) $alert->id,
                    'type' => $alert->type,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'timestamp' => optional($alert->created_at)->toIso8601String(),
                    'isRead' => (bool) $alert->is_read,
                ];
            })
            ->values()
            ->all();
    }

    private function financialHealth(array $kpis): array
    {
        $active = (int) ($kpis['activeClinics'] ?? 0);
        $total = (int) ($kpis['totalClinics'] ?? 0);
        $activeRatio = $total > 0 ? $active / $total : 0;

        $mtdRevenue = (int) ($kpis['mtdRevenuePaise'] ?? 0);
        $failedRate = $kpis['failedTxnRate'] ?? 0;

        $score = (int) round(
            min(1, $activeRatio) * 40 +
            min(1, $mtdRevenue / 50_000_000) * 40 +
            (1 - min(1, $failedRate)) * 20
        );

        $notes = [];
        if ($activeRatio < 0.7) {
            $notes[] = 'Activate more clinics to improve coverage.';
        }
        if ($failedRate > 0.05) {
            $notes[] = 'Failed transaction rate exceeds 5%. Investigate payment channels.';
        }
        if ($mtdRevenue < 25_000_000) {
            $notes[] = 'MTD revenue is below ₹2.5 Cr target.';
        }

        return [
            'score' => max(10, min(100, $score)),
            'notes' => $notes,
        ];
    }
}
