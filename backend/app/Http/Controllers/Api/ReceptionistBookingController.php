<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use App\Models\UserPet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        $builder = DB::table('users')
            ->select('id', 'name', 'email', 'phone', 'role')
            ->whereIn('role', self::PATIENT_ROLES);

        if ($query !== '') {
            $builder->where(function ($sub) use ($query) {
                $sub->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            });
        } else {
            $builder->orderByDesc('id');
        }

        $patients = $builder->limit(25)->get();
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

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => 'pet',
            'password' => Hash::make(Str::random(16)),
        ]);

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
}
