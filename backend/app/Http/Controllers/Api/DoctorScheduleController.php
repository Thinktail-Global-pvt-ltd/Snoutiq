<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorScheduleController extends Controller
{
    // PUT /api/doctors/{id}/availability
    public function updateAvailability(Request $request, string $id)
    {
        $payload = $request->validate([
            'availability' => 'required|array|min:1',
            'availability.*.service_type' => 'required|string|in:video,in_clinic,home_visit',
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
            DB::table('doctor_availability')->where('doctor_id', (int) $id)->delete();
            foreach ($payload['availability'] as $a) {
                DB::table('doctor_availability')->insert([
                    'doctor_id' => (int) $id,
                    'service_type' => $a['service_type'],
                    'day_of_week' => $a['day_of_week'],
                    'start_time' => $a['start_time'],
                    'end_time' => $a['end_time'],
                    'break_start' => $a['break_start'] ?? null,
                    'break_end' => $a['break_end'] ?? null,
                    'avg_consultation_mins' => $a['avg_consultation_mins'] ?? 20,
                    'max_bookings_per_hour' => $a['max_bookings_per_hour'] ?? 3,
                    'is_active' => 1,
                ]);
            }
        });

        return response()->json(['message' => 'Doctor availability updated', 'success' => true]);
    }

    // GET /api/doctors/{id}/free-slots?date=YYYY-MM-DD&service_type=video
    public function freeSlots(Request $request, string $id)
    {
        $date = $request->query('date');
        $serviceType = $request->query('service_type', 'video');
        if (!$date) {
            return response()->json(['success' => false, 'message' => 'date is required (YYYY-MM-DD)'], 422);
        }

        $dow = (int) date('w', strtotime($date));

        $rows = \Illuminate\Support\Facades\DB::table('doctor_availability')
            ->where('doctor_id', (int) $id)
            ->where('service_type', $serviceType)
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

        // Fetch existing bookings for that date
        $booked = \Illuminate\Support\Facades\DB::table('bookings')
            ->where('assigned_doctor_id', (int) $id)
            ->whereDate('scheduled_for', $date)
            ->whereNotIn('status', ['cancelled','failed'])
            ->pluck('scheduled_for')
            ->map(function($dt){ return date('H:i:00', strtotime($dt)); })
            ->all();

        $free = array_values(array_diff($allSlots, $booked));

        return response()->json([
            'success' => true,
            'doctor_id' => (int) $id,
            'date' => $date,
            'service_type' => $serviceType,
            'free_slots' => $free,
        ]);
    }

    public function getAvailability(Request $request, string $id)
{
    $serviceType = $request->query('service_type'); // optional filter

    $q = DB::table('doctor_availability')
        ->where('doctor_id', (int)$id)
        ->where('is_active', 1)
        ->orderBy('day_of_week')
        ->orderBy('start_time');

    if ($serviceType) {
        $q->where('service_type', $serviceType);
    }

    $rows = $q->get();

    return response()->json([
        'success' => true,
        'doctor_id' => (int)$id,
        'service_type' => $serviceType,
        'availability' => $rows,
    ]);
}

}
