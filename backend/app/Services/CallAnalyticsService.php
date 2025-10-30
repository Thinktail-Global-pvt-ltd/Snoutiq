<?php

namespace App\Services;

use App\Models\CallSession;
use App\Models\Doctor;
use App\Models\User;
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

    public function lifecycleOverview(): array
    {
        $totalUsers = User::count();
        $activeDoctors = Doctor::where('toggle_availability', 1)->count();

        $totalSessions = CallSession::count();
        $completedSessions = CallSession::where('status', 'ended')->count();

        return [
            'users' => [
                'total' => $totalUsers,
            ],
            'doctors' => [
                'active' => $activeDoctors,
            ],
            'video_sessions' => [
                'total' => $totalSessions,
                'completed' => $completedSessions,
            ],
            'meta' => [
                'refreshed_at' => Carbon::now()->format('d M Y H:i'),
                'window_label' => 'Last 30 days',
            ],
        ];
    }

    public function userLifecycleSteps(): array
    {
        $accountUsers = User::count();
        $consultUsers = CallSession::distinct('patient_id')->count('patient_id');
        $paymentUsers = CallSession::where('payment_status', 'paid')->distinct('patient_id')->count('patient_id');
        $startedUsers = CallSession::whereNotNull('started_at')->distinct('patient_id')->count('patient_id');
        $completedUsers = CallSession::whereNotNull('ended_at')->distinct('patient_id')->count('patient_id');

        $steps = [
            [
                'label' => 'Account Created',
                'users' => $accountUsers,
            ],
            [
                'label' => 'Consultation Scheduled',
                'users' => $consultUsers,
                'top_reason' => 'Users who have scheduled at least one call.',
            ],
            [
                'label' => 'Payment Completed',
                'users' => $paymentUsers,
                'top_reason' => 'Completed Razorpay flow for a session.',
            ],
            [
                'label' => 'Call Started',
                'users' => $startedUsers,
            ],
            [
                'label' => 'Call Completed',
                'users' => $completedUsers,
            ],
        ];

        return $this->applyLifecycleConversions($steps);
    }

    public function doctorLifecycleSteps(): array
    {
        $registeredDoctors = Doctor::count();
        $videoReadyDoctors = Doctor::where('toggle_availability', 1)->count();
        $acceptedDoctors = CallSession::whereNotNull('accepted_at')->distinct('doctor_id')->count('doctor_id');
        $startedDoctors = CallSession::whereNotNull('started_at')->distinct('doctor_id')->count('doctor_id');
        $completedDoctors = CallSession::whereNotNull('ended_at')->distinct('doctor_id')->count('doctor_id');

        $steps = [
            [
                'label' => 'Registered',
                'doctors' => $registeredDoctors,
                'note' => 'Total doctors onboarded to the platform.',
            ],
            [
                'label' => 'Went Live',
                'doctors' => $videoReadyDoctors,
                'note' => 'Availability toggled on for video consults.',
            ],
            [
                'label' => 'Accepted a Call',
                'doctors' => $acceptedDoctors,
                'note' => 'Accepted at least one session request.',
            ],
            [
                'label' => 'Started a Consultation',
                'doctors' => $startedDoctors,
            ],
            [
                'label' => 'Completed a Consultation',
                'doctors' => $completedDoctors,
            ],
        ];

        return $this->applyLifecycleConversions($steps, 'doctors');
    }

    public function conversionBenchmarks(): array
    {
        $userSteps = $this->userLifecycleSteps();
        $doctorSteps = $this->doctorLifecycleSteps();

        $lookupStepPercent = static function (array $steps, string $label): ?float {
            foreach ($steps as $step) {
                if (data_get($step, 'label') === $label) {
                    return data_get($step, 'conversion_total');
                }
            }

            return null;
        };

        return [
            [
                'label' => 'User Payment Completion',
                'current' => $lookupStepPercent($userSteps, 'Payment Completed'),
                'target' => 65.0,
            ],
            [
                'label' => 'User Consultation Completion',
                'current' => $lookupStepPercent($userSteps, 'Call Completed'),
                'target' => 55.0,
            ],
            [
                'label' => 'Doctor Activation',
                'current' => $lookupStepPercent($doctorSteps, 'Went Live'),
                'target' => 70.0,
            ],
            [
                'label' => 'Doctor Consultation Completion',
                'current' => $lookupStepPercent($doctorSteps, 'Completed a Consultation'),
                'target' => 50.0,
            ],
        ];
    }

    public function dropOffBreakdown(): array
    {
        $steps = $this->userLifecycleSteps();

        $breakdown = [];
        for ($i = 1; $i < count($steps); $i++) {
            $previous = $steps[$i - 1];
            $current = $steps[$i];

            $prevCount = (int) data_get($previous, 'users', 0);
            $currentCount = (int) data_get($current, 'users', 0);
            $lost = max($prevCount - $currentCount, 0);

            if ($lost === 0 || $prevCount === 0) {
                continue;
            }

            $breakdown[] = [
                'label' => sprintf('%s → %s', data_get($previous, 'label'), data_get($current, 'label')),
                'users' => $lost,
                'share' => ($lost / $prevCount) * 100,
                'reason' => data_get($current, 'top_reason', 'Follow-up required'),
            ];
        }

        return $breakdown;
    }

    public function recentUserTimeline(int $limit = 8): array
    {
        return $this->recentSessions($limit)
            ->map(function (CallSession $session) {
                $patient = $session->patient;

                return [
                    'id' => $patient?->id,
                    'name' => $patient?->name,
                    'description' => $this->describeUserEvent($session),
                    'step' => ucfirst($session->status ?? 'pending'),
                    'time' => optional($session->updated_at)->format('d M Y H:i'),
                ];
            })
            ->all();
    }

    public function recentDoctorTimeline(int $limit = 8): array
    {
        return $this->recentSessions($limit)
            ->filter(fn (CallSession $session) => $session->doctor)
            ->map(function (CallSession $session) {
                $doctor = $session->doctor;

                return [
                    'id' => $doctor?->id,
                    'name' => $doctor?->doctor_name,
                    'description' => $this->describeDoctorEvent($session),
                    'stage' => ucfirst($session->status ?? 'pending'),
                    'time' => optional($session->updated_at)->format('d M Y H:i'),
                ];
            })
            ->all();
    }

    private function applyLifecycleConversions(array $steps, string $countKey = 'users'): array
    {
        $baseline = (float) max(data_get($steps[0] ?? [], $countKey, 0), 0.0);

        foreach ($steps as $index => &$step) {
            $currentCount = (float) max(data_get($step, $countKey, 0), 0.0);
            $previousCount = $index === 0
                ? $currentCount
                : (float) max(data_get($steps[$index - 1], $countKey, 0), 0.0);

            $step['conversion_step'] = $previousCount > 0
                ? round(($currentCount / $previousCount) * 100, 1)
                : null;

            $step['conversion_total'] = $baseline > 0
                ? round(($currentCount / $baseline) * 100, 1)
                : null;

            $step['avg_time_minutes'] = null;

            if (!isset($step['top_reason'])) {
                $step['top_reason'] = null;
            }

            if (!isset($step['quality_score']) && $countKey === 'doctors') {
                $step['quality_score'] = null;
            }
        }

        unset($step);

        return $steps;
    }

    private function describeUserEvent(CallSession $session): string
    {
        return match ($session->status) {
            'accepted' => 'Doctor accepted the consultation request.',
            'ended' => $session->payment_status === 'paid'
                ? 'Consultation completed and payment captured.'
                : 'Consultation ended without payment.',
            default => $session->payment_status === 'paid'
                ? 'Payment received. Awaiting call start.'
                : 'Call session created.',
        };
    }

    private function describeDoctorEvent(CallSession $session): string
    {
        return match ($session->status) {
            'accepted' => 'Accepted an incoming video consult.',
            'ended' => 'Marked the session as completed.',
            default => $session->payment_status === 'paid'
                ? 'Awaiting doctor to join the paid session.'
                : 'New session pending assignment.',
        };
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
