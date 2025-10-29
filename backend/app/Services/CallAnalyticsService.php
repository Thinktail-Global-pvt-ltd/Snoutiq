<?php

namespace App\Services;

use App\Models\CallSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CallAnalyticsService
{
    public function summary(): array
    {
        $aggregate = CallSession::query()
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw('SUM(CASE WHEN status = "ended" THEN 1 ELSE 0 END) as completed_sessions')
            ->selectRaw('SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_sessions')
            ->selectRaw('COALESCE(SUM(CASE WHEN ended_at IS NOT NULL THEN duration_seconds ELSE 0 END), 0) as total_duration_seconds')
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_status = "paid" THEN amount_paid ELSE 0 END), 0) as total_revenue_paise')
            ->first();

        $totalDurationSeconds = (int) ($aggregate->total_duration_seconds ?? 0);
        $completedSessions = (int) ($aggregate->completed_sessions ?? 0);
        $totalRevenuePaise = (int) ($aggregate->total_revenue_paise ?? 0);

        $averageDurationSeconds = $completedSessions > 0
            ? (int) round($totalDurationSeconds / $completedSessions)
            : 0;

        return [
            'total_sessions'          => (int) ($aggregate->total_sessions ?? 0),
            'completed_sessions'      => $completedSessions,
            'paid_sessions'           => (int) ($aggregate->paid_sessions ?? 0),
            'total_duration_seconds'  => $totalDurationSeconds,
            'total_duration_human'    => $this->formatDuration($totalDurationSeconds),
            'average_duration_seconds'=> $averageDurationSeconds,
            'average_duration_human'  => $this->formatDuration($averageDurationSeconds),
            'total_revenue_paise'     => $totalRevenuePaise,
            'total_revenue_rupees'    => $totalRevenuePaise / 100,
        ];
    }

    public function recentSessions(int $limit = 5): Collection
    {
        return CallSession::with([
                'patient:id,name',
                'doctor:id,doctor_name',
                'payment:id,razorpay_payment_id,amount,currency',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }

        return Carbon::now()
            ->subSeconds($seconds)
            ->diffForHumans([ 'parts' => 3, 'short' => true, 'syntax' => Carbon::DIFF_ABSOLUTE ]);
    }
}
