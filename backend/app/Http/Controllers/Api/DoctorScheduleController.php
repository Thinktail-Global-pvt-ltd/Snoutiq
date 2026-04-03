<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $doctor = Doctor::select('id', 'doctors_price')->find((int) $id);
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        try {
            $free = $this->buildFreeSlotsForDate((int) $doctor->id, $date, $serviceType);
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
            'doctor_price' => $doctor->doctors_price !== null ? (float) $doctor->doctors_price : null,
        ]);
    }

    public function getAvailability(Request $request, string $id)
    {
        $serviceType = $request->query('service_type'); // optional filter
        $doctor = Doctor::select('id', 'doctors_price')->find((int) $id);

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
            'doctor_price' => ($doctor && $doctor->doctors_price !== null)
                ? (float) $doctor->doctors_price
                : null,
        ]);
    }

    // GET /api/clinics/{id}/doctor-availability
    public function getClinicAvailability(Request $request, string $id)
    {
        $clinicId = (int) $id;
        if ($clinicId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid clinic id',
            ], 422);
        }

        $serviceType = $request->query('service_type'); // optional filter
        if ($serviceType && !in_array($serviceType, ['video', 'in_clinic', 'home_visit'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid service_type',
            ], 422);
        }

        $tz = config('app.timezone') ?: 'Asia/Kolkata';
        $anchorDateInput = $request->query('date');
        $days = (int) $request->query('days', $anchorDateInput ? 1 : 7);
        $days = max(1, min($days, 30));
        $effectiveServiceType = $serviceType ?: 'in_clinic';

        try {
            $anchorDate = $anchorDateInput
                ? Carbon::parse((string) $anchorDateInput, $tz)->startOfDay()
                : Carbon::now($tz)->startOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date. Use YYYY-MM-DD.',
            ], 422);
        }

        $doctorIds = DB::table('doctors')
            ->where('vet_registeration_id', $clinicId)
            ->pluck('id')
            ->map(fn ($doctorId) => (int) $doctorId)
            ->all();

        if (empty($doctorIds)) {
            return response()->json([
                'success' => true,
                'clinic_id' => $clinicId,
                'service_type' => $serviceType,
                'availability' => [],
                'doctor_ids' => [],
                'doctors_count' => 0,
                'date' => $anchorDate->toDateString(),
                'days' => $days,
                'effective_service_type' => $effectiveServiceType,
                'available_dates' => [],
                'slots' => [],
                'unique_slots' => [],
                'slot_capacity' => [],
                'slots_by_date' => (object) [],
                'unique_slots_by_date' => (object) [],
                'slot_capacity_by_date' => (object) [],
            ]);
        }

        $rows = DB::table('doctor_availability')
            ->whereIn('doctor_id', $doctorIds)
            ->where('is_active', 1)
            ->when($serviceType, function ($q) use ($serviceType) {
                $q->where('service_type', $serviceType);
            })
            ->select([
                'service_type',
                'day_of_week',
                'start_time',
                'end_time',
                'break_start',
                'break_end',
                'avg_consultation_mins',
                'max_bookings_per_hour',
                DB::raw('COUNT(DISTINCT doctor_id) as doctors_covered'),
            ])
            ->groupBy([
                'service_type',
                'day_of_week',
                'start_time',
                'end_time',
                'break_start',
                'break_end',
                'avg_consultation_mins',
                'max_bookings_per_hour',
            ])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $now = Carbon::now($tz);
        $slotsByDate = [];
        $uniqueSlotsByDate = [];
        $slotCapacityByDate = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $slotDate = $anchorDate->copy()->addDays($offset);
            $slotDateString = $slotDate->toDateString();

            $timeToDoctorIds = [];

            foreach ($doctorIds as $doctorId) {
                try {
                    $doctorSlots = $this->buildFreeSlotsForDate($doctorId, $slotDateString, $effectiveServiceType);
                } catch (\InvalidArgumentException $e) {
                    $doctorSlots = [];
                }

                foreach ($doctorSlots as $slotTime) {
                    $normalizedSlotTime = $this->normalizeSlotTime($slotTime);
                    if (!$normalizedSlotTime) {
                        continue;
                    }

                    // For today's date, keep only present/future times.
                    if ($slotDate->isSameDay($now)) {
                        $slotDateTime = Carbon::parse($slotDateString.' '.$normalizedSlotTime, $tz);
                        if ($slotDateTime->lt($now)) {
                            continue;
                        }
                    }

                    if (!isset($timeToDoctorIds[$normalizedSlotTime])) {
                        $timeToDoctorIds[$normalizedSlotTime] = [];
                    }
                    $timeToDoctorIds[$normalizedSlotTime][] = (int) $doctorId;
                }
            }

            ksort($timeToDoctorIds);

            $expandedSlots = [];
            $uniqueSlots = [];
            $capacityRows = [];

            foreach ($timeToDoctorIds as $time => $doctorIdsForTime) {
                $doctorIdsForTime = array_values(array_unique(array_map('intval', $doctorIdsForTime)));
                $capacity = count($doctorIdsForTime);
                if ($capacity <= 0) {
                    continue;
                }

                $uniqueSlots[] = $time;
                for ($i = 0; $i < $capacity; $i++) {
                    $expandedSlots[] = $time;
                }

                $capacityRows[] = [
                    'time' => $time,
                    'available_doctors' => $capacity,
                    'doctor_ids' => $doctorIdsForTime,
                ];
            }

            $slotsByDate[$slotDateString] = $expandedSlots;
            $uniqueSlotsByDate[$slotDateString] = $uniqueSlots;
            $slotCapacityByDate[$slotDateString] = $capacityRows;
        }

        $availableDates = [];
        foreach ($uniqueSlotsByDate as $dateKey => $slotsForDate) {
            if (!empty($slotsForDate)) {
                $availableDates[] = $dateKey;
            }
        }

        $primaryDate = $anchorDate->toDateString();

        return response()->json([
            'success' => true,
            'clinic_id' => $clinicId,
            'service_type' => $serviceType,
            'availability' => $rows,
            'doctor_ids' => $doctorIds,
            'doctors_count' => count($doctorIds),
            'date' => $primaryDate,
            'days' => $days,
            'effective_service_type' => $effectiveServiceType,
            'available_dates' => $availableDates,
            // Expanded slot list: same time can appear multiple times when multiple doctors are free.
            'slots' => $slotsByDate[$primaryDate] ?? [],
            // Unique times for UI grouping.
            'unique_slots' => $uniqueSlotsByDate[$primaryDate] ?? [],
            // Capacity metadata per time for the selected date.
            'slot_capacity' => $slotCapacityByDate[$primaryDate] ?? [],
            // Date-wise maps for multi-day view.
            'slots_by_date' => (object) $slotsByDate,
            'unique_slots_by_date' => (object) $uniqueSlotsByDate,
            'slot_capacity_by_date' => (object) $slotCapacityByDate,
        ]);
    }

    // PUT /api/clinics/{id}/doctor-availability
    public function updateClinicAvailability(Request $request, string $id)
    {
        $clinicId = (int) $id;
        if ($clinicId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid clinic id',
            ], 422);
        }

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

        $doctorIds = DB::table('doctors')
            ->where('vet_registeration_id', $clinicId)
            ->pluck('id')
            ->map(fn ($doctorId) => (int) $doctorId)
            ->all();

        if (empty($doctorIds)) {
            return response()->json([
                'success' => false,
                'error' => 'No doctors found for this clinic',
            ], 404);
        }

        DB::transaction(function () use ($doctorIds, $payload) {
            $serviceTypes = collect($payload['availability'])
                ->pluck('service_type')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $deleteQuery = DB::table('doctor_availability')
                ->whereIn('doctor_id', $doctorIds);

            if (!empty($serviceTypes)) {
                $deleteQuery->whereIn('service_type', $serviceTypes);
            }

            $deleteQuery->delete();

            $insertRows = [];
            foreach ($doctorIds as $doctorId) {
                foreach ($payload['availability'] as $a) {
                    $insertRows[] = [
                        'doctor_id' => $doctorId,
                        'service_type' => $a['service_type'],
                        'day_of_week' => $a['day_of_week'],
                        'start_time' => $a['start_time'],
                        'end_time' => $a['end_time'],
                        'break_start' => $a['break_start'] ?? null,
                        'break_end' => $a['break_end'] ?? null,
                        'avg_consultation_mins' => $a['avg_consultation_mins'] ?? 20,
                        'max_bookings_per_hour' => $a['max_bookings_per_hour'] ?? 3,
                        'is_active' => 1,
                    ];
                }
            }

            if (!empty($insertRows)) {
                DB::table('doctor_availability')->insert($insertRows);
            }
        });

        return response()->json([
            'success' => true,
            'clinic_id' => $clinicId,
            'doctors_updated' => count($doctorIds),
            'message' => 'Clinic availability updated for all doctors',
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

        $doctor = Doctor::select('id', 'doctors_price')->find($doctorId);
        if (!$doctor) {
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
            'doctor_price' => $doctor->doctors_price !== null ? (float) $doctor->doctors_price : null,
        ]);
    }

    /**
     * GET /api/doctors/{id}/slots
     *
     * Wrapper around slots() so consumers can hit a RESTful doctor-specific URL.
     */
    public function slotsByDoctor(Request $request, string $id)
    {
        $request->merge(['doctor_id' => (int) $id]);
        return $this->slots($request);
    }

    /**
     * GET /api/doctors/{id}/slots/summary
     *
     * Returns both free slots (derived from availability) and the booked slots
     * stored via appointments/legacy bookings for a given doctor & date.
     */
    public function slotsSummary(Request $request, string $id)
    {
        $payload = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'service_type' => 'nullable|string|in:video,in_clinic,home_visit',
        ]);

        $doctorId = (int) $id;
        $tz = config('app.timezone') ?? 'UTC';
        $date = $payload['date'] ?? Carbon::now($tz)->toDateString();
        $serviceType = $payload['service_type'] ?? 'in_clinic';

        $doctor = Doctor::select('id', 'doctor_name', 'doctors_price')->find($doctorId);
        if (!$doctor) {
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

        $bookedSlots = $this->getBookedSlotDetails($doctorId, $date, $serviceType);

        return response()->json([
            'success' => true,
            'doctor' => [
                'id' => (int) $doctor->id,
                'name' => $doctor->doctor_name,
                'price' => $doctor->doctors_price !== null ? (float) $doctor->doctors_price : null,
            ],
            'date' => $date,
            'service_type' => $serviceType,
            'free_slots' => $freeSlots,
            'booked_slots' => $bookedSlots,
        ]);
    }

    public function updatePrice(Request $request, string $id)
    {
        $doctor = Doctor::find((int) $id);
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        $payload = $request->validate([
            'price' => 'nullable|numeric|min:0|required_without:doctor_price',
            'doctor_price' => 'nullable|numeric|min:0|required_without:price',
        ]);

        $doctor->doctors_price = array_key_exists('price', $payload)
            ? $payload['price']
            : $payload['doctor_price'];
        $doctor->save();

        return response()->json([
            'success' => true,
            'doctor_id' => (int) $doctor->id,
            'doctor_price' => (float) $doctor->doctors_price,
            'message' => 'Doctor price updated.',
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

        $booked = $this->getBookedTimesForDate($doctorId, $parsed, $serviceType);

        return array_values(array_diff($allSlots, $booked));
    }

    private function getBookedTimesForDate(int $doctorId, Carbon $parsed, string $serviceType): array
    {
        $times = [];

        if (Schema::hasTable('bookings')) {
            $bookingsQuery = DB::table('bookings')
                ->where('assigned_doctor_id', $doctorId)
                ->whereDate('scheduled_for', $parsed->toDateString())
                ->whereNotNull('scheduled_for')
                ->whereNotIn('status', ['cancelled', 'failed']);

            if ($serviceType) {
                $bookingsQuery->where('service_type', $serviceType);
            }

            $times = array_merge($times, $bookingsQuery
                ->pluck('scheduled_for')
                ->map(function ($dt) {
                    return date('H:i:00', strtotime($dt));
                })
                ->all());
        }

        if ($serviceType === 'in_clinic' && Schema::hasTable('appointments')) {
            $times = array_merge($times, DB::table('appointments')
                ->where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $parsed->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->pluck('appointment_time')
                ->map(function ($time) {
                    return $this->normalizeSlotTime($time);
                })
                ->filter()
                ->all());
        }

        return array_values(array_unique(array_filter($times)));
    }

    private function getBookedSlotDetails(int $doctorId, string $date, string $serviceType): array
    {
        $slots = [];

        if ($serviceType === 'in_clinic' && Schema::hasTable('appointments')) {
            $appointments = DB::table('appointments as a')
                ->leftJoin('vet_registerations_temp as v', 'a.vet_registeration_id', '=', 'v.id')
                ->select(
                    'a.id',
                    'a.appointment_date',
                    'a.appointment_time',
                    'a.status',
                    'a.name',
                    'a.mobile',
                    'a.pet_name',
                    'a.notes',
                    'a.vet_registeration_id',
                    'v.name as clinic_name'
                )
                ->where('a.doctor_id', $doctorId)
                ->whereDate('a.appointment_date', $date)
                ->whereNotIn('a.status', ['cancelled'])
                ->orderBy('a.appointment_time')
                ->get();

            foreach ($appointments as $appt) {
                $slots[] = [
                    'source' => 'appointment',
                    'reference_id' => (int) $appt->id,
                    'date' => $appt->appointment_date,
                    'time' => $this->normalizeSlotTime($appt->appointment_time),
                    'status' => $appt->status,
                    'patient_name' => $appt->name,
                    'patient_phone' => $appt->mobile,
                    'pet_name' => $appt->pet_name,
                    'clinic_id' => $appt->vet_registeration_id ? (int) $appt->vet_registeration_id : null,
                    'clinic_name' => $appt->clinic_name,
                    'notes' => $appt->notes,
                    'service_type' => 'in_clinic',
                ];
            }
        }

        if (Schema::hasTable('bookings')) {
            $bookingsQuery = DB::table('bookings')
                ->select(
                    'id',
                    'scheduled_for',
                    'status',
                    'service_type',
                    'user_id',
                    'pet_id',
                    'clinic_id'
                )
                ->where('assigned_doctor_id', $doctorId)
                ->whereDate('scheduled_for', $date)
                ->whereNotNull('scheduled_for')
                ->whereNotIn('status', ['cancelled', 'failed']);

            if ($serviceType) {
                $bookingsQuery->where('service_type', $serviceType);
            }

            $bookings = $bookingsQuery->orderBy('scheduled_for')->get();

            foreach ($bookings as $booking) {
                $slots[] = [
                    'source' => 'booking',
                    'reference_id' => (int) $booking->id,
                    'date' => $booking->scheduled_for ? date('Y-m-d', strtotime($booking->scheduled_for)) : $date,
                    'time' => $booking->scheduled_for ? date('H:i:00', strtotime($booking->scheduled_for)) : null,
                    'status' => $booking->status,
                    'service_type' => $booking->service_type,
                    'user_id' => $booking->user_id ? (int) $booking->user_id : null,
                    'pet_id' => $booking->pet_id ? (int) $booking->pet_id : null,
                    'clinic_id' => $booking->clinic_id ? (int) $booking->clinic_id : null,
                ];
            }
        }

        usort($slots, function ($a, $b) {
            return strcmp($a['time'] ?? '', $b['time'] ?? '');
        });

        return $slots;
    }

    private function normalizeSlotTime(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        $ts = strtotime($time);
        return $ts ? date('H:i:00', $ts) : null;
    }
}
