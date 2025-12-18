<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Models\UserPet;
use App\Models\Receptionist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReceptionistBookingController extends Controller
{
    private const PATIENT_ROLES = ['pet', 'pet_owner', 'patient', 'user'];

    private function resolveClinicId(Request $request): ?int
    {
        $directKeys = ['clinic_id', 'vet_registeration_id', 'vet_id', 'user_id'];
        foreach ($directKeys as $key) {
            $value = $request->input($key) ?? $request->query($key);
            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }

        $headers = [
            $request->header('X-Clinic-Id'),
            $request->header('X-Vet-Id'),
            $request->header('X-User-Id'),
            $request->header('X-Acting-User'),
            $request->header('X-Session-User'),
        ];
        foreach ($headers as $value) {
            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }

        $slug = $request->input('vet_slug')
            ?? $request->query('vet_slug')
            ?? $request->query('clinic_slug');
        if ($slug) {
            $row = DB::table('vet_registerations_temp')
                ->select('id')
                ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                ->first();
            if ($row) {
                return (int) $row->id;
            }
        }

        $role = session('role')
            ?? data_get(session('user'), 'role')
            ?? data_get(session('auth_full'), 'role');

        $receptionistId = session('receptionist_id')
            ?? $request->input('receptionist_id')
            ?? $request->header('X-Receptionist-Id');
        if ($receptionistId) {
            $receptionist = Receptionist::find((int) $receptionistId);
            if ($receptionist?->vet_registeration_id) {
                return (int) $receptionist->vet_registeration_id;
            }
        }

        if (in_array($role, ['doctor', 'receptionist'], true)) {
            $clinicId = session('clinic_id')
                ?? session('vet_registerations_temp_id')
                ?? session('vet_registeration_id')
                ?? session('vet_id')
                ?? data_get(session('user'), 'clinic_id')
                ?? data_get(session('auth_full'), 'clinic_id')
                ?? data_get(session('auth_full'), 'user.clinic_id');
            if ($clinicId) {
                return (int) $clinicId;
            }
        }

        $fallback = session('user_id') ?? data_get(session('user'), 'id');
        return $fallback ? (int) $fallback : null;
    }

    public function bookings(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json(['success' => false, 'message' => 'clinic_id or vet_slug required'], 422);
        }

        $rows = DB::table('bookings as b')
            ->leftJoin('users as u', 'b.user_id', '=', 'u.id')
            ->leftJoin('user_pets as p', 'b.pet_id', '=', 'p.id')
            ->leftJoin('doctors as d', 'b.assigned_doctor_id', '=', 'd.id')
            ->select(
                'b.*',
                DB::raw('COALESCE(u.name, "") as patient_name'),
                DB::raw('COALESCE(u.email, "") as patient_email'),
                DB::raw('COALESCE(u.phone, "") as patient_phone'),
                DB::raw('COALESCE(p.name, "") as pet_name'),
                DB::raw('COALESCE(p.type, "") as pet_type'),
                DB::raw('COALESCE(p.breed, "") as pet_breed'),
                DB::raw('COALESCE(d.doctor_name, "") as doctor_name')
            )
            ->where('b.clinic_id', $clinicId)
            ->orderByRaw('COALESCE(b.scheduled_for, b.booking_created_at) DESC')
            ->limit(200)
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function patients(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $selectColumns = ['id', 'name', 'email', 'phone'];
        $filterByRole = false;

        if (Schema::hasColumn('users', 'role')) {
            $selectColumns[] = 'role';
            $filterByRole = true;
        }

        $builder = DB::table('users')->select($selectColumns);

        if ($filterByRole) {
            $builder->whereIn('role', self::PATIENT_ROLES);
        }

        if ($query !== '') {
            $builder->where(function ($sub) use ($query) {
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            });
        } else {
            $builder->orderByDesc('id');
        }

        $patients = $builder->get();
        $petMap = collect();

        if ($patients->isNotEmpty()) {
            $petMap = DB::table('user_pets')
                ->select('id', 'name', 'type', 'breed', 'gender', 'user_id')
                ->whereIn('user_id', $patients->pluck('id'))
                ->orderBy('name')
                ->get()
                ->groupBy('user_id');
        }

        $payload = $patients->map(function ($patient) use ($petMap) {
            $patient->pets = ($petMap[$patient->id] ?? collect())->values();
            return $patient;
        });

        return response()->json(['success' => true, 'data' => $payload]);
    }

    public function patientPets(int $userId)
    {
        $pets = DB::table('user_pets')
            ->select('id', 'name', 'type', 'breed', 'gender')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $pets]);
    }

    public function doctors(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json(['success' => false, 'message' => 'clinic_id or vet_slug required'], 422);
        }

        $doctors = Doctor::query()
            ->where('vet_registeration_id', $clinicId)
            ->orderBy('doctor_name')
            ->get(['id', 'doctor_name', 'doctor_email', 'doctor_mobile']);

        return response()->json([
            'success' => true,
            'data' => $doctors,
        ]);
    }

    /**
     * GET /api/receptionist/doctors/available
     * Returns doctors for the clinic who have at least one free slot for the given date
     * (default: today) and flags whether the current time hits a free slot.
     */
    public function availableDoctors(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json(['success' => false, 'message' => 'clinic_id or vet_slug required'], 422);
        }

        $tz = config('app.timezone') ?? 'UTC';
        // Always derive date/time on the server so clients don't need to send them.
        $now = now($tz);
        $date = $now->toDateString();
        $timeNow = $now->format('H:i:s');
        $serviceType = $request->query('service_type', 'in_clinic');

        try {
            $parsedDate = Carbon::parse($date, $tz);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid date. Use YYYY-MM-DD'], 422);
        }

        $doctors = Doctor::query()
            ->where('vet_registeration_id', $clinicId)
            ->orderBy('doctor_name')
            ->get([
                'id',
                'doctor_name',
                'doctor_email',
                'doctor_mobile',
                'doctor_image',
                'doctors_price',
                'toggle_availability',
            ]);

        $payload = [];
        foreach ($doctors as $doctor) {
            try {
                $freeSlots = $this->buildFreeSlotsForDate((int) $doctor->id, $parsedDate->toDateString(), $serviceType);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            if (empty($freeSlots)) {
                continue; // no availability for this doctor today
            }

            $availableNow = $this->isTimeAvailable($timeNow, $freeSlots);
            $nextSlot = $this->nextSlotAfter($timeNow, $freeSlots);

            $payload[] = [
                'id' => (int) $doctor->id,
                'name' => $doctor->doctor_name,
                'email' => $doctor->doctor_email,
                'phone' => $doctor->doctor_mobile,
                'image' => $doctor->doctor_image,
                'price' => $doctor->doctors_price !== null ? (float) $doctor->doctors_price : null,
                'toggle_availability' => (bool) $doctor->toggle_availability,
                'available_now' => $availableNow,
                'next_available_slot' => $nextSlot,
                'available_count' => count($freeSlots),
                'free_slots' => $freeSlots,
            ];
        }

        return response()->json([
            'success' => true,
            'date' => $parsedDate->toDateString(),
            'time_checked' => $timeNow,
            'service_type' => $serviceType,
            'available_doctors' => $payload,
        ]);
    }

    public function storePatient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:25|unique:users,phone',
            'pet_name' => 'nullable|string|max:120',
            'pet_type' => 'nullable|string|max:120',
            'pet_breed' => 'nullable|string|max:120',
            'pet_gender' => 'nullable|string|max:50',
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Email or phone is required for a patient record.',
            ], 422);
        }

        $userPayload = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make(Str::random(16)),
        ];
        if (Schema::hasColumn('users', 'role')) {
            $userPayload['role'] = 'pet';
        }

        $user = User::create($userPayload);

        $pet = null;
        if (!empty($data['pet_name'])) {
            $pet = UserPet::create([
                'user_id' => $user->id,
                'name' => $data['pet_name'],
                'type' => $data['pet_type'] ?? 'dog',
                'breed' => $data['pet_breed'] ?? 'Unknown',
                'dob' => now()->format('Y-m-d'),
                'gender' => $data['pet_gender'] ?? 'unknown',
                'pic_link' => null,
                'medical_history' => '[]',
                'vaccination_log' => '[]',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'pet' => $pet ? [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'type' => $pet->type,
                    'breed' => $pet->breed,
                ] : null,
            ],
        ], 201);
    }

    public function storeBooking(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json(['success' => false, 'message' => 'clinic_id or vet_slug required'], 422);
        }

        $data = $request->validate([
            'patient_id' => 'required|integer|exists:users,id',
            'pet_id' => 'nullable|integer',
            'pet_name' => 'nullable|string|max:120',
            'pet_type' => 'nullable|string|max:120',
            'pet_breed' => 'nullable|string|max:120',
            'pet_gender' => 'nullable|string|max:50',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'service_type' => 'required|string|in:video,in_clinic,home_visit',
            'urgency' => 'nullable|string|in:low,medium,high,emergency',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable',
            'notes' => 'nullable|string',
            'quoted_price' => 'nullable|numeric',
        ]);

        $patient = User::find($data['patient_id']);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $doctorId = $data['doctor_id'] ?? null;
        if ($doctorId) {
            $doctor = Doctor::where('vet_registeration_id', $clinicId)->find($doctorId);
            if (!$doctor) {
                return response()->json(['success' => false, 'message' => 'Doctor not found for this clinic'], 404);
            }
        }

        $petId = $data['pet_id'] ?? null;
        if ($petId) {
            $petExists = DB::table('user_pets')
                ->where('id', $petId)
                ->where('user_id', $patient->id)
                ->exists();
            if (!$petExists) {
                return response()->json(['success' => false, 'message' => 'Selected pet does not belong to the patient'], 422);
            }
        } elseif (!empty($data['pet_name'])) {
            $pet = UserPet::create([
                'user_id' => $patient->id,
                'name' => $data['pet_name'],
                'type' => $data['pet_type'] ?? 'dog',
                'breed' => $data['pet_breed'] ?? 'Unknown',
                'dob' => now()->format('Y-m-d'),
                'gender' => $data['pet_gender'] ?? 'unknown',
                'pic_link' => null,
                'medical_history' => '[]',
                'vaccination_log' => '[]',
            ]);
            $petId = $pet->id;
        } else {
            return response()->json(['success' => false, 'message' => 'Select a pet or provide pet details'], 422);
        }

        $scheduledFor = $this->combineSchedule(
            $data['scheduled_date'] ?? null,
            $data['scheduled_time'] ?? null
        );

        $now = now();
        $bookingId = DB::table('bookings')->insertGetId([
            'user_id' => $patient->id,
            'pet_id' => $petId,
            'service_type' => $data['service_type'],
            'urgency' => $data['urgency'] ?? 'medium',
            'ai_summary' => $data['notes'] ?? null,
            'ai_urgency_score' => null,
            'symptoms' => null,
            'user_latitude' => null,
            'user_longitude' => null,
            'user_address' => null,
            'status' => 'scheduled',
            'clinic_id' => $clinicId,
            'assigned_doctor_id' => $doctorId,
            'scheduled_for' => $scheduledFor,
            'quoted_price' => $data['quoted_price'] ?? null,
            'final_price' => $data['quoted_price'] ?? null,
            'payment_status' => 'pending',
            'booking_created_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'booking_id' => $bookingId,
            'message' => 'Booking created successfully.',
        ], 201);
    }

    private function combineSchedule(?string $date, ?string $time): ?string
    {
        if (!$date) {
            return null;
        }

        if ($time) {
            return $date . ' ' . $time;
        }

        return $date . ' 10:00:00';
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
            $start = $this->timeToMinutes($r->start_time);
            $end   = $this->timeToMinutes($r->end_time);
            $bStart = $r->break_start ? $this->timeToMinutes($r->break_start) : null;
            $bEnd   = $r->break_end   ? $this->timeToMinutes($r->break_end)   : null;
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

    private function timeToMinutes(string $time): int
    {
        $hh = (int) substr($time, 0, 2);
        $mm = (int) substr($time, 3, 2);
        return $hh * 60 + $mm;
    }

    private function isTimeAvailable(string $time, array $freeSlots): bool
    {
        $normalized = $this->normalizeSlotTime($time);
        return $normalized ? in_array($normalized, $freeSlots, true) : false;
    }

    private function nextSlotAfter(string $time, array $freeSlots): ?string
    {
        $normalized = $this->normalizeSlotTime($time);
        if (!$normalized || empty($freeSlots)) {
            return null;
        }
        $currentMinutes = $this->timeToMinutes($normalized);
        $next = null;
        foreach ($freeSlots as $slot) {
            $slotMinutes = $this->timeToMinutes($slot);
            if ($slotMinutes >= $currentMinutes) {
                if ($next === null || $slotMinutes < $this->timeToMinutes($next)) {
                    $next = $slot;
                }
            }
        }
        return $next;
    }
}
