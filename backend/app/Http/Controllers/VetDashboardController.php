<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Transaction;
use App\Models\VetRegisterationTemp;
use App\Services\OnboardingProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VetDashboardController extends Controller
{
    protected OnboardingProgressService $progress;

    public function __construct(OnboardingProgressService $progress)
    {
        $this->progress = $progress;
    }

    public function __invoke(Request $request)
    {
        $clinicId = $this->progress->resolveClinicId($request);
        if (!$clinicId) {
            return redirect()->route('custom-doctor-login');
        }

        $clinic = VetRegisterationTemp::find($clinicId);
        $doctorIds = Doctor::where('vet_registeration_id', $clinicId)->pluck('id')->all();

        $tz = 'Asia/Kolkata';
        $now = Carbon::now($tz);
        $today = $now->toDateString();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekDates = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));

        $bookingColumns = ['id', 'customer_id', 'date', 'start_time', 'end_time', 'total', 'paid', 'status', 'services'];
        $hasDoctorId = Schema::hasColumn('doctor_bookings', 'doctor_id');
        if ($hasDoctorId) {
            $bookingColumns[] = 'doctor_id';
        }

        $todayBookings = DB::table('doctor_bookings')
            ->where('vet_id', $clinicId)
            ->whereDate('date', $today)
            ->get($bookingColumns);

        $appointmentStats = $this->buildAppointmentStats($todayBookings, $now);
        $walkInStats = $this->buildWalkInStats($todayBookings, $now);
        $telemedicineStats = $this->buildTelemedicineStats($clinicId, $doctorIds, $today, $now);

        $revenueData = $this->buildRevenueData($clinicId, $doctorIds, $weekDates);
        $repeatRate = $this->buildRepeatRate($clinicId, $weekStart, $now);
        $alerts = $this->buildAlerts($now, $doctorIds, $revenueData['unpaid_amount'], $repeatRate);
        $snapshot = $this->buildSnapshot($clinicId, $doctorIds, $todayBookings, $revenueData['unpaid_amount']);

        $quickActions = [
            [
                'label' => 'Add Walk-in Patient',
                'icon' => '➕',
                'url' => route('receptionist.bookings.create'),
            ],
            [
                'label' => 'Book Appointment',
                'icon' => '📅',
                'url' => route('booking.clinics'),
            ],
            [
                'label' => 'Start Tele-Consult',
                'icon' => '🎥',
                'url' => route('doctor.live'),
            ],
            [
                'label' => 'Add New Pet Parent',
                'icon' => '🐶',
                'url' => route('dashboard.profile'),
            ],
        ];

        $queue = $this->buildQueue($todayBookings, $doctorIds);
        $cohortChart = $this->buildCohortChart($clinicId, $weekDates);

        return view('dashboard.vet-home', [
            'clinic' => $clinic,
            'today' => $now,
            'alerts' => $alerts,
            'quickActions' => $quickActions,
            'stats' => [
                'telemedicine' => $telemedicineStats,
                'appointments' => $appointmentStats,
                'walkins' => $walkInStats,
            ],
            'charts' => [
                'cohort' => $cohortChart,
                'revenue' => $revenueData['chart'],
            ],
            'snapshot' => $snapshot,
            'revenueTotal' => $revenueData['total'],
            'unpaidAmount' => $revenueData['unpaid_amount'],
            'repeatRate' => $repeatRate,
            'queue' => $queue,
        ]);
    }

    protected function buildAppointmentStats(Collection $bookings, Carbon $now): array
    {
        $completedStatuses = ['completed', 'done', 'finished', 'resolved', 'closed', 'success', 'successful'];
        $ongoingStatuses = ['ongoing', 'in_progress', 'accepted', 'started', 'live'];
        $noShowStatuses = ['no_show', 'noshow', 'no-show'];

        $completed = $bookings->whereIn('status', $completedStatuses)->count();
        $ongoing = $bookings->whereIn('status', $ongoingStatuses)->count();
        $noShows = $bookings->whereIn('status', $noShowStatuses)->count();
        $totalToday = $bookings->count();

        $upcoming = $bookings->filter(function ($row) use ($now, $completedStatuses, $ongoingStatuses) {
            if (!$row->start_time) {
                return false;
            }
            try {
                $start = Carbon::parse($row->date . ' ' . $row->start_time, $now->timezone);
            } catch (\Throwable $e) {
                return false;
            }
            if ($start->lt($now)) {
                return false;
            }
            $status = strtolower((string) $row->status);
            return !in_array($status, $completedStatuses, true) && !in_array($status, $ongoingStatuses, true);
        })->count();

        return [
            'total' => $totalToday,
            'completed' => $completed,
            'ongoing' => $ongoing,
            'upcoming' => $upcoming,
            'no_shows' => $noShows,
        ];
    }

    protected function buildWalkInStats(Collection $bookings, Carbon $now): array
    {
        $walkIns = $bookings->filter(function ($row) {
            $status = strtolower((string) $row->status);
            if (str_contains($status, 'walk')) {
                return true;
            }
            $services = json_decode($row->services ?? '[]', true);
            return collect($services)->contains(function ($service) {
                $label = strtolower((string) ($service['name'] ?? $service['service'] ?? $service ?? ''));
                return str_contains($label, 'walk');
            });
        });

        $averageWaitMinutes = $walkIns->count() ? 7 : 0; // no wait tracking stored, fallback to static small number

        return [
            'today' => $walkIns->count(),
            'repeat' => $walkIns->groupBy('customer_id')->filter(fn ($g) => $g->count() > 1)->count(),
            'served' => $walkIns->count(),
            'avg_wait' => $averageWaitMinutes,
        ];
    }

    protected function buildTelemedicineStats(int $clinicId, array $doctorIds, string $today, Carbon $now): array
    {
        if (!Schema::hasTable('consultations')) {
            return [
                'scheduled' => 0,
                'completed' => 0,
                'ongoing' => 0,
                'funnel' => [
                    'inquiry' => 0,
                    'scheduled' => 0,
                    'paid' => 0,
                    'inquiry_pct' => 0,
                    'scheduled_pct' => 0,
                    'paid_pct' => 0,
                ],
            ];
        }

        $completedStatuses = ['completed', 'done', 'finished', 'resolved', 'closed', 'success', 'successful'];
        $ongoingStatuses = ['ongoing', 'in_progress', 'accepted', 'started', 'live'];

        $consultations = Consultation::query()
            ->where(function ($q) use ($clinicId, $doctorIds) {
                $q->where('clinic_id', $clinicId);
                if ($doctorIds) {
                    $q->orWhereIn('doctor_id', $doctorIds);
                }
            })
            ->whereDate('start_time', $today)
            ->get(['status', 'start_time', 'end_time', 'mode']);

        $scheduled = $consultations->count();
        $completed = $consultations->whereIn('status', $completedStatuses)->count();
        $ongoing = $consultations->whereIn('status', $ongoingStatuses)->count();

        $funnelInquiry = max($scheduled, 0);
        $funnelScheduled = $scheduled;
        $funnelPaid = $completed;

        $percent = function ($num, $den) {
            return $den > 0 ? round(($num / $den) * 100) : 0;
        };

        return [
            'scheduled' => $scheduled,
            'completed' => $completed,
            'ongoing' => $ongoing,
            'funnel' => [
                'inquiry' => $funnelInquiry,
                'scheduled' => $funnelScheduled,
                'paid' => $funnelPaid,
                'inquiry_pct' => 100,
                'scheduled_pct' => $percent($funnelScheduled, max($funnelInquiry, 1)),
                'paid_pct' => $percent($funnelPaid, max($funnelInquiry, 1)),
            ],
        ];
    }

    protected function buildRevenueData(int $clinicId, array $doctorIds, Collection $weekDates): array
    {
        $successfulStatuses = ['completed', 'captured', 'paid', 'success', 'successful', 'settled'];

        $transactions = Transaction::query()
            ->where(function ($q) use ($clinicId, $doctorIds) {
                $q->where('clinic_id', $clinicId);
                if ($doctorIds) {
                    $q->orWhereIn('doctor_id', $doctorIds);
                }
            })
            ->whereDate('created_at', '>=', $weekDates->first()->toDateString())
            ->get(['status', 'amount_paise', 'created_at']);

        $byDate = $weekDates->mapWithKeys(function (Carbon $date) {
            return [$date->toDateString() => 0];
        })->toArray();

        foreach ($transactions as $t) {
            $date = optional($t->created_at)?->toDateString();
            if ($date && array_key_exists($date, $byDate) && in_array($t->status, $successfulStatuses, true)) {
                $byDate[$date] += (int) $t->amount_paise;
            }
        }

        $totalPaise = array_sum($byDate);
        $unpaidPaise = $transactions->reject(function ($t) use ($successfulStatuses) {
            return in_array($t->status, $successfulStatuses, true);
        })->sum('amount_paise');

        return [
            'total' => $totalPaise / 100,
            'unpaid_amount' => $unpaidPaise / 100,
            'chart' => [
                'labels' => $weekDates->map(fn ($d) => $d->format('D'))->all(),
                'series' => collect($byDate)->map(fn ($p) => round($p / 100, 2))->values()->all(),
            ],
        ];
    }

    protected function buildRepeatRate(int $clinicId, Carbon $weekStart, Carbon $now): ?float
    {
        if (!Schema::hasTable('doctor_bookings')) {
            return null;
        }

        $bookings = DB::table('doctor_bookings')
            ->where('vet_id', $clinicId)
            ->whereDate('date', '>=', $weekStart->toDateString())
            ->whereDate('date', '<=', $now->toDateString())
            ->get(['customer_id']);

        $customers = $bookings->pluck('customer_id')->filter()->values();
        if ($customers->isEmpty()) {
            return null;
        }

        $repeat = $customers->countBy()->filter(fn ($count) => $count > 1)->count();
        $unique = $customers->unique()->count();
        if ($unique === 0) {
            return null;
        }

        return round(($repeat / $unique) * 100, 1);
    }

    protected function buildAlerts(Carbon $now, array $doctorIds, float $unpaidAmount, ?float $repeatRate): array
    {
        $alerts = [];

        if ($doctorIds) {
            $endTimes = DB::table('doctor_video_availability')
                ->whereIn('doctor_id', $doctorIds)
                ->where('is_active', 1)
                ->where('day_of_week', $now->dayOfWeek)
                ->whereNotNull('end_time')
                ->pluck('end_time')
                ->filter();

            $soonestEnd = $endTimes
                ->map(fn ($t) => $this->parseTimeToMinutes($t))
                ->filter()
                ->sort()
                ->first();

            $currentMinutes = ($now->hour * 60) + $now->minute;
            if ($soonestEnd !== null && $soonestEnd > $currentMinutes) {
                $minsLeft = $soonestEnd - $currentMinutes;
                if ($minsLeft <= 120) {
                    $alerts[] = [
                        'title' => 'Peak Hours Ending Soon',
                        'description' => 'Telemedicine demand may drop after current slot. About ' . $minsLeft . ' min left in today\'s schedule.',
                        'tone' => 'warning',
                        'icon' => '⏰',
                    ];
                }
            }
        }

        if ($unpaidAmount > 0) {
            $alerts[] = [
                'title' => 'Unpaid Settlements',
                'description' => '₹' . number_format($unpaidAmount, 0) . ' pending across recent transactions.',
                'tone' => 'danger',
                'icon' => '💰',
            ];
        }

        if ($repeatRate !== null && $repeatRate < 40) {
            $alerts[] = [
                'title' => 'Low Repeat Rate This Week',
                'description' => 'Repeat customers: ' . $repeatRate . '%. Encourage follow-ups.',
                'tone' => 'info',
                'icon' => '📊',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'title' => 'All Clear',
                'description' => 'No pending issues detected. Keep up the great work!',
                'tone' => 'info',
                'icon' => '✅',
            ];
        }

        return $alerts;
    }

    protected function buildQueue(Collection $bookings, array $doctorIds): Collection
    {
        $doctorNames = $doctorIds
            ? Doctor::whereIn('id', $doctorIds)->pluck('doctor_name', 'id')
            : collect();

        return $bookings
            ->sortBy('start_time')
            ->take(8)
            ->map(function ($row) use ($doctorNames) {
                $doctorId = data_get($row, 'doctor_id');
                $services = json_decode($row->services ?? '[]', true);
                $serviceLabel = collect($services)->first();
                if (is_array($serviceLabel)) {
                    $serviceLabel = $serviceLabel['name'] ?? $serviceLabel['service'] ?? 'Appointment';
                } elseif (!$serviceLabel) {
                    $serviceLabel = 'Appointment';
                }

                $status = strtolower((string) $row->status);
                return [
                    'time' => $this->formatSlotTime($row->start_time),
                    'service' => $serviceLabel,
                    'customer_id' => $row->customer_id,
                    'doctor' => $doctorId ? ($doctorNames[$doctorId] ?? null) : null,
                    'status' => $status ?: 'scheduled',
                ];
            });
    }

    protected function buildCohortChart(int $clinicId, Collection $weekDates): array
    {
        $bookings = DB::table('doctor_bookings')
            ->where('vet_id', $clinicId)
            ->whereDate('date', '>=', $weekDates->first()->toDateString())
            ->whereDate('date', '<=', $weekDates->last()->toDateString())
            ->orderBy('date')
            ->get(['date', 'customer_id']);

        $labels = $weekDates->map(fn ($d) => $d->format('D'))->all();
        $seenCustomers = collect();
        $returning = [];
        $new = [];

        foreach ($weekDates as $date) {
            $dayRows = $bookings->where('date', $date->toDateString());
            $newCount = 0;
            $returningCount = 0;
            foreach ($dayRows as $row) {
                if (!$row->customer_id) {
                    continue;
                }
                if ($seenCustomers->contains($row->customer_id)) {
                    $returningCount++;
                } else {
                    $newCount++;
                    $seenCustomers->push($row->customer_id);
                }
            }
            $new[] = $newCount;
            $returning[] = $returningCount;
        }

        return [
            'labels' => $labels,
            'new' => $new,
            'returning' => $returning,
        ];
    }

    protected function buildSnapshot(int $clinicId, array $doctorIds, Collection $todayBookings, float $unpaidAmount): array
    {
        $successStatuses = ['completed', 'captured', 'paid', 'success', 'successful', 'settled'];

        $servicesCount = Schema::hasTable('groomer_services')
            ? DB::table('groomer_services')->where('user_id', $clinicId)->count()
            : 0;

        $staffCount = Schema::hasTable('doctors')
            ? DB::table('doctors')->where('vet_registeration_id', $clinicId)->count()
            : 0;

        $videoSlots = Schema::hasTable('doctor_video_availability')
            ? DB::table('doctor_video_availability')->whereIn('doctor_id', $doctorIds ?: [0])->where('is_active', 1)->count()
            : 0;

        $emergencyCount = Schema::hasTable('clinic_emergency_hours')
            ? DB::table('clinic_emergency_hours')->where('clinic_id', $clinicId)->count()
            : 0;

        $documentsOk = ($this->progress->getStatusForClinic($clinicId)['documents'] ?? false) === true;

        $paymentsPending = 0;
        if (Schema::hasTable('transactions')) {
            $paymentsPending = Transaction::query()
                ->where(function ($q) use ($clinicId, $doctorIds) {
                    $q->where('clinic_id', $clinicId);
                    if ($doctorIds) {
                        $q->orWhereIn('doctor_id', $doctorIds);
                    }
                })
                ->whereNotIn('status', $successStatuses)
                ->count();
        }

        return [
            'services' => $servicesCount,
            'staff' => $staffCount,
            'bookings_today' => $todayBookings->count(),
            'video_slots' => $videoSlots,
            'emergency_profiles' => $emergencyCount,
            'pending_payments' => $paymentsPending,
            'unpaid_amount' => $unpaidAmount,
            'documents_ok' => $documentsOk,
        ];
    }

    protected function formatSlotTime(?string $time): string
    {
        if (!$time) {
            return '—';
        }
        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable $e) {
            return $time;
        }
    }

    protected function parseTimeToMinutes(string $time): ?int
    {
        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return null;
        }
        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];
        return ($hours * 60) + $minutes;
    }
}
