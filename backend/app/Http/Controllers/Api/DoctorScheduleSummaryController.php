<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->select('id', 'doctor_name', 'video_day_rate', 'video_night_rate')
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

        return response()->json([
            'success' => true,
            'doctor_id' => $doctorId,
            'doctor_name' => $doctor->doctor_name,
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
}
