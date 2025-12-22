<?php

namespace App\Services;

use App\Models\VetRegisterationTemp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VetRegistrationReportService
{
    /**
     * Return month-wise aggregates for vet_registerations_temp.
     */
    public function monthlySummary(): Collection
    {
        return DB::table('vet_registerations_temp')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(status = 'active') as active_count")
            ->selectRaw("SUM(status = 'draft') as draft_count")
            ->selectRaw("SUM(owner_user_id IS NULL) as free_count")
            ->selectRaw("SUM(owner_user_id IS NOT NULL) as claimed_count")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month')
            ->get()
            ->map(function ($row) {
                return [
                    'month' => $row->month,
                    'total' => (int) $row->total,
                    'active_count' => (int) $row->active_count,
                    'draft_count' => (int) $row->draft_count,
                    'free_count' => (int) $row->free_count,
                    'claimed_count' => (int) $row->claimed_count,
                ];
            });
    }

    /**
     * Resolve the month to show: prefer the requested one, otherwise latest.
     */
    public function resolveMonth(?string $requested, Collection $summary): ?string
    {
        $normalized = $this->normalizeMonth($requested);
        if (! $normalized) {
            return null;
        }

        $exists = $summary->firstWhere('month', $normalized);

        return $exists ? $normalized : null;
    }

    /**
     * Return detailed rows for a specific month (all + free/active).
     */
    public function monthDetails(?string $month): array
    {
        $normalized = $this->normalizeMonth($month);
        $baseQuery = VetRegisterationTemp::query()
            ->select([
                'id',
                'name',
                'status',
                'owner_user_id',
                'claimed_at',
                'created_at',
                'city',
                'pincode',
                'chat_price',
            ])
            ->orderByDesc('created_at');

        if ($normalized) {
            $start = Carbon::createFromFormat('Y-m', $normalized)->startOfMonth();
            $end = (clone $start)->addMonth();
            $baseQuery->whereBetween('created_at', [$start, $end]);
        }

        $all = (clone $baseQuery)->get();
        $free = (clone $baseQuery)
            ->whereNull('owner_user_id')
            ->where('status', 'active')
            ->get();

        return [
            'month' => $normalized,
            'all' => $all,
            'free_activations' => $free,
        ];
    }

    private function normalizeMonth(?string $month): ?string
    {
        if (! $month) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m', $month)->format('Y-m');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
