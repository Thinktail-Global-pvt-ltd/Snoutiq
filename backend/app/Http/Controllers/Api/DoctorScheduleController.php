<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
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

        try {
            $free = $this->buildFreeSlotsForDate((int) $id, $date, $serviceType);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

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
            ->where('doctor_id', (int) $id)
            ->where('is_active', 1)
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if ($serviceType) {
            $q->where('service_type', $serviceType);
        }

        $rows = $q->get();

        return response()->json([
            'success' => true,
            'doctor_id' => (int) $id,
            'service_type' => $serviceType,
            'availability' => $rows,
        ]);
    }

    public function slots(Request $request)
    {
        $payload = $request->validate([
            'doctor_id' => 'required|integer',
            'date' => 'nullable|date_format:Y-m-d',
            'service_type' => 'nullable|string|in:video,in_clinic,home_visit',
        ]);

        $doctorId = (int) $payload['doctor_id'];
        $tz = config('app.timezone') ?? 'UTC';
        $date = $payload['date'] ?? Carbon::now($tz)->toDateString();
        $serviceType = $payload['service_type'] ?? 'in_clinic';

        $exists = DB::table('doctors')->where('id', $doctorId)->exists();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        try {
            $freeSlots = $this->buildFreeSlotsForDate($doctorId, $date, $serviceType);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'doctor_id' => $doctorId,
            'date' => $date,
            'service_type' => $serviceType,
            'free_slots' => $freeSlots,
        ]);
    }

    private function buildFreeSlotsForDate(int $doctorId, string $date, string $serviceType): array
    {
        try {
            $parsed = Carbon::parse($date, config('app.timezone'));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date provided. Use YYYY-MM-DD.');
        }

        $dow = (int) $parsed->dayOfWeek;

        $rows = DB::table('doctor_availability')
            ->where('doctor_id', $doctorId)
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
                if ($bStart !== null && $bEnd !== null && $t >= $bStart && $t < $bEnd) {
                    continue;
                }
                $hh = str_pad((int) floor($t / 60), 2, '0', STR_PAD_LEFT);
                $mm = str_pad($t % 60, 2, '0', STR_PAD_LEFT);
                $allSlots[] = "$hh:$mm:00";
            }
        }

        $booked = DB::table('bookings')
            ->where('assigned_doctor_id', $doctorId)
            ->whereDate('scheduled_for', $parsed->toDateString())
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->pluck('scheduled_for')
            ->map(function ($dt) {
                return date('H:i:00', strtotime($dt));
            })
            ->all();

        return array_values(array_diff($allSlots, $booked));
    }

}
