<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DoctorScheduleSummaryController extends Controller
{
    /**
     * GET /api/doctors/{id}/schedules/combined
     *
     * Returns both in-clinic availability and video calling availability
     * using the same data as the existing schedule management pages.
     */
    public function show(Request $request, string $id)
    {
        $doctorId = (int) $id;
        if ($doctorId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'doctor_id must be a positive integer',
            ], 422);
        }

        $doctor = DB::table('doctors')
            ->select('id', 'doctor_name', 'vet_registeration_id', 'video_day_rate', 'video_night_rate')
            ->where('id', $doctorId)
            ->first();

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        $clinicAvailability = DB::table('doctor_availability')
            ->where('doctor_id', $doctorId)
            ->where('service_type', 'in_clinic')
            ->where('is_active', 1)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $videoAvailability = DB::table('doctor_video_availability')
            ->where('doctor_id', $doctorId)
            ->where('is_active', 1)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Group identical video slots across days so applied-to-all-days setups
        // return one logical slot with a days array instead of 7 duplicates.
        $videoAggregated = $this->aggregateSlotsByTime($videoAvailability);
        $completion = $this->buildThreeWayCompletionStatus($doctorId, (int) ($doctor->vet_registeration_id ?? 0), false);

        return response()->json([
            'success' => true,
            'doctor_id' => $doctorId,
            'doctor_name' => $doctor->doctor_name,
            'clinic_id' => (int) ($doctor->vet_registeration_id ?? 0),
            'clinic_schedule' => [
                'service_type' => 'in_clinic',
                'availability' => $clinicAvailability,
            ],
            'video_calling_schedule' => [
                'availability' => $videoAggregated,
                'availability_raw' => $videoAvailability,
                'day_rate' => $doctor->video_day_rate === null ? null : (float) $doctor->video_day_rate,
                'night_rate' => $doctor->video_night_rate === null ? null : (float) $doctor->video_night_rate,
            ],
            'completion' => $completion,
        ]);
    }

    /**
     * GET /api/doctors/{id}/schedules/completion
     *
     * Returns completion status of the three setup blocks:
     * 1) doctor video schedule (doctor_id scoped)
     * 2) clinic in-clinic schedule (clinic scoped via doctors.vet_registeration_id)
     * 3) clinic services (clinic scoped via doctors.vet_registeration_id)
     */
    public function completion(Request $request, string $id)
    {
        $doctorId = (int) $id;
        if ($doctorId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'doctor_id must be a positive integer',
            ], 422);
        }

        $doctor = DB::table('doctors')
            ->select('id', 'doctor_name', 'vet_registeration_id')
            ->where('id', $doctorId)
            ->first();

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        $clinicId = (int) ($doctor->vet_registeration_id ?? 0);
        $completion = $this->buildThreeWayCompletionStatus($doctorId, $clinicId, true);

        return response()->json([
            'success' => true,
            'doctor_id' => $doctorId,
            'doctor_name' => $doctor->doctor_name,
            'clinic_id' => $clinicId,
            'completion' => $completion,
        ]);
    }

    /**
     * Collapse repeated slots (same timing + settings) across days into one row with a days list.
     */
    private function aggregateSlotsByTime($rows): array
    {
        $grouped = [];

        foreach ($rows as $r) {
            $keyParts = [
                $r->start_time,
                $r->end_time,
                $r->break_start ?? '',
                $r->break_end ?? '',
                $r->avg_consultation_mins ?? '',
                $r->max_bookings_per_hour ?? '',
            ];
            $key = implode('|', $keyParts);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'start_time' => $r->start_time,
                    'end_time' => $r->end_time,
                    'break_start' => $r->break_start,
                    'break_end' => $r->break_end,
                    'avg_consultation_mins' => $r->avg_consultation_mins,
                    'max_bookings_per_hour' => $r->max_bookings_per_hour,
                    'days' => [],
                ];
            }

            $grouped[$key]['days'][] = (int) $r->day_of_week;
        }

        foreach ($grouped as &$entry) {
            sort($entry['days']);
            $entry['day_count'] = count($entry['days']);
            $entry['applies_to_all_days'] = $entry['day_count'] === 7;
        }
        unset($entry);

        // Keep a stable order by start time
        usort($grouped, function ($a, $b) {
            if ($a['start_time'] === $b['start_time']) {
                return $a['end_time'] <=> $b['end_time'];
            }
            return $a['start_time'] <=> $b['start_time'];
        });

        return array_values($grouped);
    }

    private function buildThreeWayCompletionStatus(int $doctorId, int $clinicId, bool $withRows = false): array
    {
        $videoRows = 0;
        $videoSource = 'doctor_video_availability';
        $videoData = collect();

        if (Schema::hasTable('doctor_video_availability')) {
            $videoQuery = DB::table('doctor_video_availability')
                ->where('doctor_id', $doctorId);
            if (Schema::hasColumn('doctor_video_availability', 'is_active')) {
                $videoQuery->where('is_active', 1);
            }

            if ($withRows) {
                $videoData = $videoQuery
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get();
                $videoRows = $videoData->count();
            } else {
                $videoRows = (int) $videoQuery->count();
            }
        }

        // Fallback for legacy storage where video rows could be inside doctor_availability.
        if ($videoRows === 0 && Schema::hasTable('doctor_availability')) {
            $videoFallback = DB::table('doctor_availability')
                ->where('doctor_id', $doctorId)
                ->where('service_type', 'video');
            if (Schema::hasColumn('doctor_availability', 'is_active')) {
                $videoFallback->where('is_active', 1);
            }

            if ($withRows) {
                $videoData = $videoFallback
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get();
                $videoRows = $videoData->count();
            } else {
                $videoRows = (int) $videoFallback->count();
            }
            $videoSource = 'doctor_availability';
        }

        $clinicDoctorIds = [];
        if ($clinicId > 0 && Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'vet_registeration_id')) {
            $clinicDoctorIds = DB::table('doctors')
                ->where('vet_registeration_id', $clinicId)
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->values()
                ->all();
        }

        $inClinicRows = 0;
        $inClinicData = collect();
        if (!empty($clinicDoctorIds) && Schema::hasTable('doctor_availability')) {
            $inClinicQuery = DB::table('doctor_availability')
                ->whereIn('doctor_id', $clinicDoctorIds)
                ->where('service_type', 'in_clinic');
            if (Schema::hasColumn('doctor_availability', 'is_active')) {
                $inClinicQuery->where('is_active', 1);
            }

            if ($withRows) {
                $inClinicData = $inClinicQuery
                    ->orderBy('doctor_id')
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get();
                $inClinicRows = $inClinicData->count();
            } else {
                $inClinicRows = (int) $inClinicQuery->count();
            }
        }

        $serviceRows = 0;
        $servicesData = collect();
        if ($clinicId > 0 && Schema::hasTable('groomer_services') && Schema::hasColumn('groomer_services', 'user_id')) {
            $serviceQuery = DB::table('groomer_services')
                ->where('user_id', $clinicId);
            if (Schema::hasColumn('groomer_services', 'name')) {
                $serviceQuery->whereNotNull('name')->whereRaw("TRIM(name) <> ''");
            }

            if ($withRows) {
                $servicesData = $serviceQuery
                    ->orderByDesc('id')
                    ->get();
                $serviceRows = $servicesData->count();
            } else {
                $serviceRows = (int) $serviceQuery->count();
            }
        }

        $videoCompleted = $videoRows > 0;
        $inClinicCompleted = $inClinicRows > 0;
        $servicesCompleted = $serviceRows > 0;

        $result = [
            'video_schedule' => [
                'completed' => $videoCompleted,
                'rows' => $videoRows,
                'scope' => 'doctor',
                'table' => $videoSource,
            ],
            'in_clinic_schedule' => [
                'completed' => $inClinicCompleted,
                'rows' => $inClinicRows,
                'scope' => 'clinic',
                'table' => 'doctor_availability',
                'clinic_doctors_count' => count($clinicDoctorIds),
            ],
            'services' => [
                'completed' => $servicesCompleted,
                'rows' => $serviceRows,
                'scope' => 'clinic',
                'table' => 'groomer_services',
            ],
            'all_completed' => $videoCompleted && $inClinicCompleted && $servicesCompleted,
        ];

        if ($withRows) {
            $result['video_schedule']['data'] = $videoData->map(fn ($row) => (array) $row)->values();
            $result['in_clinic_schedule']['data'] = $inClinicData->map(fn ($row) => (array) $row)->values();
            $result['services']['data'] = $servicesData->map(fn ($row) => (array) $row)->values();
        }

        return $result;
    }
}
