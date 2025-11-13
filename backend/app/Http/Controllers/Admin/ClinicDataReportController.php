<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessHour;
use App\Models\ClinicEmergencyHour;
use App\Models\Transaction;
use App\Models\VetRegisterationTemp;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClinicDataReportController extends Controller
{
    public function index(): View
    {
        $reportRows = $this->getClinicReportRows();

        return view('admin.clinic-data-report', compact('reportRows'));
    }

    public function export()
    {
        $reportRows = $this->getClinicReportRows();
        $filename = 'clinic-data-report-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($reportRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Clinic ID',
                'Clinic Name',
                'City',
                'Doctors',
                'In-Clinic Availability',
                'Video Availability',
                'Clinic Business Hours',
                'Emergency Coverage',
                'Payments (₹)',
                'Transactions',
                'Last Updated',
            ]);

            foreach ($reportRows as $row) {
                fputcsv($handle, [
                    $row['clinic']->id,
                    $row['clinic']->name,
                    $row['clinic']->city ?? '',
                    $row['doctors_count'],
                    $row['in_clinic_summary'],
                    $row['video_summary'],
                    $row['business_hours_summary'],
                    $row['emergency_summary'],
                    $row['payment_total_display'],
                    $row['transaction_count'],
                    optional($row['clinic']->updated_at)?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function getClinicReportRows(): Collection
    {
        $clinics = VetRegisterationTemp::select('id', 'name', 'city', 'created_at', 'updated_at')
            ->withCount('doctors')
            ->orderBy('name')
            ->get();

        if ($clinics->isEmpty()) {
            return collect();
        }

        $clinicIds = $clinics->pluck('id')->filter()->values()->all();

        $businessHours = BusinessHour::whereIn('vet_registeration_id', $clinicIds)
            ->orderBy('day_of_week')
            ->get()
            ->groupBy('vet_registeration_id');

        $inClinicRows = collect(DB::table('doctor_availability as da')
            ->select('d.vet_registeration_id', 'da.day_of_week', 'da.start_time', 'da.end_time', 'da.break_start', 'da.break_end', 'da.max_bookings_per_hour')
            ->join('doctors as d', 'da.doctor_id', '=', 'd.id')
            ->where('da.service_type', 'in_clinic')
            ->where('da.is_active', 1)
            ->whereIn('d.vet_registeration_id', $clinicIds)
            ->get());

        $videoRows = collect(DB::table('doctor_video_availability as dva')
            ->select('d.vet_registeration_id', 'dva.day_of_week', 'dva.start_time', 'dva.end_time', 'dva.break_start', 'dva.break_end', 'dva.avg_consultation_mins', 'dva.max_bookings_per_hour')
            ->join('doctors as d', 'dva.doctor_id', '=', 'd.id')
            ->where('dva.is_active', 1)
            ->whereIn('d.vet_registeration_id', $clinicIds)
            ->get());

        $inClinicByClinic = $inClinicRows->groupBy('vet_registeration_id');
        $videoByClinic = $videoRows->groupBy('vet_registeration_id');

        $emergencyHours = ClinicEmergencyHour::whereIn('clinic_id', $clinicIds)->get()->keyBy('clinic_id');

        $payments = Transaction::completed()
            ->whereIn('clinic_id', $clinicIds)
            ->selectRaw('clinic_id, COUNT(*) as transaction_count, SUM(amount_paise) as total_paise')
            ->groupBy('clinic_id')
            ->get()
            ->keyBy('clinic_id');

        return $clinics->map(function (VetRegisterationTemp $clinic) use (
            $inClinicByClinic,
            $videoByClinic,
            $businessHours,
            $emergencyHours,
            $payments
        ) {
            $inClinic = $inClinicByClinic->get($clinic->id, collect());
            $video = $videoByClinic->get($clinic->id, collect());
            $business = $businessHours->get($clinic->id, collect());
            $emergency = $emergencyHours->get($clinic->id);
            $paymentRow = $payments->get($clinic->id);

            $totalPaise = $paymentRow->total_paise ?? 0;

            return [
                'clinic' => $clinic,
                'doctors_count' => $clinic->doctors_count,
                'in_clinic_summary' => $this->formatAvailabilitySummary($inClinic, 'availability'),
                'video_summary' => $this->formatAvailabilitySummary($video, 'video'),
                'business_hours_summary' => $this->formatBusinessHours($business),
                'emergency_summary' => $this->formatEmergencySummary($emergency),
                'payment_total_display' => $this->formatCurrency($totalPaise),
                'payment_total_paise' => $totalPaise,
                'transaction_count' => $paymentRow->transaction_count ?? 0,
            ];
        })->values();
    }

    private function formatAvailabilitySummary(Collection $rows, string $type): string
    {
        if ($rows->isEmpty()) {
            return 'Not configured';
        }

        $normalized = $rows->map(fn ($row) => (array) $row);
        $grouped = $normalized->groupBy('day_of_week');

        $parts = $grouped->map(function (Collection $dayRows, $dow) use ($type) {
            $label = $this->formatDayLabel((int) $dow, $type);
            $ranges = $dayRows->map(fn ($entry) => $this->describeRange($entry))
                ->unique()
                ->values()
                ->all();

            return $label.': '.implode(' / ', $ranges);
        });

        return $parts->values()->implode(' | ');
    }

    private function formatBusinessHours(Collection $rows): string
    {
        if ($rows->isEmpty()) {
            return 'Not configured';
        }

        return $rows->map(function ($row) {
            $label = $this->formatDayLabel($row->day_of_week, 'business');
            $start = $this->formatTime($row->open_time);
            $end = $this->formatTime($row->close_time);

            if ($start && $end) {
                $range = "$start - $end";
            } elseif ($start) {
                $range = "$start onwards";
            } elseif ($end) {
                $range = "Until $end";
            } else {
                $range = 'Closed';
            }

            return $label.': '.$range;
        })->implode(' | ');
    }

    private function formatEmergencySummary(?ClinicEmergencyHour $emergency): string
    {
        if (! $emergency) {
            return 'Not configured';
        }

        $parts = [];

        if ($emergency->doctor_ids) {
            $ids = $this->normalizeJson($emergency->doctor_ids);
            if (is_array($ids) && count($ids)) {
                $parts[] = 'Doctors: '.count($ids);
            }
        }

        if ($emergency->night_slots) {
            $slots = $this->normalizeJson($emergency->night_slots);
            if (is_array($slots) && count($slots)) {
                $parts[] = 'Night slots: '.implode(', ', $slots);
            }
        }

        if ($emergency->consultation_price !== null) {
            $parts[] = 'Consult ₹'.number_format($emergency->consultation_price, 2);
        }

        return count($parts) ? implode(' • ', $parts) : 'Details pending';
    }

    private function formatDayLabel(int $dow, string $context): string
    {
        $availability = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $business = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        if ($context === 'business') {
            return $business[$dow - 1] ?? 'Day '.$dow;
        }

        return $availability[$dow % 7];
    }

    private function describeRange(array $entry): string
    {
        $start = $this->formatTime($entry['start_time'] ?? null);
        $end = $this->formatTime($entry['end_time'] ?? null);

        if ($start && $end) {
            $range = "$start - $end";
        } elseif ($start) {
            $range = "$start onwards";
        } elseif ($end) {
            $range = "Until $end";
        } else {
            $range = 'Time TBD';
        }

        $extras = [];

        if (! empty($entry['max_bookings_per_hour'])) {
            $extras[] = 'max '.$entry['max_bookings_per_hour'].'/hr';
        }

        if (! empty($entry['avg_consultation_mins'])) {
            $extras[] = $entry['avg_consultation_mins'].' min';
        }

        if (! empty($entry['break_start']) && ! empty($entry['break_end'])) {
            $breakStart = $this->formatTime($entry['break_start']);
            $breakEnd = $this->formatTime($entry['break_end']);
            if ($breakStart && $breakEnd) {
                $extras[] = 'break '.$breakStart.'–'.$breakEnd;
            }
        }

        if ($extras) {
            $range .= ' ('.implode(', ', $extras).')';
        }

        return $range;
    }

    private function formatTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return substr($value, 0, 5);
    }

    private function formatCurrency(int $paise): string
    {
        $rupees = $paise / 100;
        return '₹'.number_format($rupees, 2);
    }

    private function normalizeJson(mixed $payload): mixed
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }
}
