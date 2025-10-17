<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorVideoScheduleController extends Controller
{
    // PUT /api/video-schedule/doctors/{id}/availability
    public function updateAvailability(Request $request, string $id)
    {
        $payload = $request->validate([
            'availability' => 'required|array|min:1',
            'availability.*.day_of_week' => 'required|integer|min:0|max:6',
            'availability.*.start_time' => 'required',
            'availability.*.end_time' => 'required',
            'availability.*.break_start' => 'nullable',
            'availability.*.break_end' => 'nullable',
            'availability.*.avg_consultation_mins' => 'nullable|integer',
            'availability.*.max_bookings_per_hour' => 'nullable|integer',
        ]);

        // Ensure doctor exists
        $exists = DB::table('doctors')->where('id', (int) $id)->exists();
        if (!$exists) {
            return response()->json(['success' => false, 'error' => 'Doctor not found'], 404);
        }

        DB::transaction(function () use ($id, $payload) {
            DB::table('doctor_video_availability')->where('doctor_id', (int) $id)->delete();
            foreach ($payload['availability'] as $a) {
                DB::table('doctor_video_availability')->insert([
                    'doctor_id' => (int) $id,
                    'day_of_week' => $a['day_of_week'],
                    'start_time' => $a['start_time'],
                    'end_time' => $a['end_time'],
                    'break_start' => $a['break_start'] ?? null,
                    'break_end' => $a['break_end'] ?? null,
                    'avg_consultation_mins' => $a['avg_consultation_mins'] ?? 20,
                    'max_bookings_per_hour' => $a['max_bookings_per_hour'] ?? 3,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json(['message' => 'Doctor video availability updated', 'success' => true]);
    }

    // GET /api/video-schedule/doctors/{id}/free-slots?date=YYYY-MM-DD[&days=N]
    // If `days` > 1, returns `free_slots_by_date` for a rolling window using the
    // weekly repeatable availability (by day_of_week), excluding already booked slots.
    public function freeSlots(Request $request, string $id)
    {
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['success' => false, 'message' => 'date is required (YYYY-MM-DD)'], 422);
        }
        $days = (int) $request->query('days', 1);
        if ($days < 1) { $days = 1; }
        if ($days > 60) { $days = 60; } // safety cap

        // Helper: build slots for one date using weekly template for that DOW
        $buildForDate = function (string $yMd) use ($id) {
            $dow = (int) date('w', strtotime($yMd));

            $rows = DB::table('doctor_video_availability')
                ->where('doctor_id', (int) $id)
                ->where('day_of_week', $dow)
                ->where('is_active', 1)
                ->orderBy('start_time')
                ->get();

            $allSlots = [];
            foreach ($rows as $r) {
                $step = max(5, (int) ($r->avg_consultation_mins ?? 20));
                $start = (int) substr($r->start_time, 0, 2) * 60 + (int) substr($r->start_time, 3, 2);
                $end   = (int) substr($r->end_time, 0, 2) * 60 + (int) substr($r->end_time, 3, 2);
                $bStart = $r->break_start ? ((int) substr($r->break_start, 0, 2) * 60 + (int) substr($r->break_start, 3, 2)) : null;
                $bEnd   = $r->break_end   ? ((int) substr($r->break_end, 0, 2) * 60 + (int) substr($r->break_end, 3, 2))   : null;
                for ($t = $start; $t + $step <= $end; $t += $step) {
                    if ($bStart !== null && $bEnd !== null && $t >= $bStart && $t < $bEnd) continue;
                    $hh = str_pad((int) floor($t / 60), 2, '0', STR_PAD_LEFT);
                    $mm = str_pad($t % 60, 2, '0', STR_PAD_LEFT);
                    $allSlots[] = "$hh:$mm:00";
                }
            }

            // Exclude already booked slots (video service type only, if column exists)
            $booked = DB::table('bookings')
                ->where('assigned_doctor_id', (int) $id)
                ->whereDate('scheduled_for', $yMd)
                ->where('service_type', 'video')
                ->whereNotIn('status', ['cancelled','failed'])
                ->pluck('scheduled_for')
                ->map(function($dt){ return date('H:i:00', strtotime($dt)); })
                ->all();

            return array_values(array_diff($allSlots, $booked));
        };

        if ($days === 1) {
            $free = $buildForDate($date);
            return response()->json([
                'success' => true,
                'doctor_id' => (int) $id,
                'date' => $date,
                'free_slots' => $free,
            ]);
        }

        // Build rolling window using repeatable weekly template
        $byDate = [];
        $startTs = strtotime($date . ' 00:00:00');
        for ($i = 0; $i < $days; $i++) {
            $dYmd = date('Y-m-d', $startTs + ($i * 86400));
            $byDate[$dYmd] = $buildForDate($dYmd);
        }

        return response()->json([
            'success' => true,
            'doctor_id' => (int) $id,
            'start_date' => $date,
            'days' => $days,
            'free_slots_by_date' => $byDate,
        ]);
    }

    // GET /api/video-schedule/doctors/{id}/availability
    public function getAvailability(Request $request, string $id)
    {
        $rows = DB::table('doctor_video_availability')
            ->where('doctor_id', (int) $id)
            ->where('is_active', 1)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'doctor_id' => (int)$id,
            'availability' => $rows,
        ]);
    }
}
