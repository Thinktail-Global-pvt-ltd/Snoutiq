<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CallSession;
use App\Models\Doctor;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AppointmentSubmissionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $petValidation = ['nullable', 'integer'];
        if (Schema::hasTable('pets')) {
            $petValidation[] = 'exists:pets,id';
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'patient_id' => ['nullable', 'integer'],
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:20'],
            'patient_email' => ['nullable', 'email', 'max:191'],
            'pet_name' => ['nullable', 'string', 'max:255'],
            'pet_id' => $petValidation,
            'date' => ['required', 'date'],
            'time_slot' => ['required', 'string', 'max:50'],
            'amount' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'max:10'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:191'],
            'razorpay_order_id' => ['nullable', 'string', 'max:191'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $clinic = VetRegisterationTemp::findOrFail($validated['clinic_id']);
        $doctor = Doctor::findOrFail($validated['doctor_id']);

        $patientId = $request->input('user_id') ?? $request->input('patient_id');
        $user = $patientId ? User::find((int) $patientId) : null;

        if (!$user) {
            $phone = $validated['patient_phone'] ?? null;
            $email = $validated['patient_email'] ?? null;

            if ($phone) {
                $user = User::where('phone', $phone)->first();
            }

            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                $payload = [
                    'name' => $validated['patient_name'],
                    'email' => $email,
                    'phone' => $phone,
                    'password' => Hash::make(Str::random(16)),
                ];

                if (Schema::hasColumn('users', 'role')) {
                    $payload['role'] = 'pet';
                }

                if (Schema::hasColumn('users', 'last_vet_id')) {
                    $payload['last_vet_id'] = $clinic->id;
                }

                $user = User::create($payload);
            }
        } elseif (Schema::hasColumn('users', 'last_vet_id') && (int) $user->last_vet_id !== (int) $clinic->id) {
            $user->last_vet_id = $clinic->id;
            $user->save();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve patient. Please select a patient or provide phone/email.',
            ], 422);
        }

        $clinic = VetRegisterationTemp::findOrFail($validated['clinic_id']);

        $notesPayload = [
            'clinic_name' => $clinic->name,
            'doctor_name' => $doctor->doctor_name ?? $doctor->name ?? null,
            'patient_user_id' => $user->id,
            'patient_email' => $user->email,
            'amount_paise' => $validated['amount'] ?? null,
            'currency' => $validated['currency'] ?? 'INR',
            'razorpay_payment_id' => $validated['razorpay_payment_id'] ?? null,
            'razorpay_order_id' => $validated['razorpay_order_id'] ?? null,
            'razorpay_signature' => $validated['razorpay_signature'] ?? null,
        ];

        if (!empty($validated['notes'])) {
            $notesPayload['text'] = $validated['notes'];
        }

        $appointment = Appointment::create([
            'vet_registeration_id' => $clinic->id,
            'doctor_id' => $doctor->id,
            'pet_id' => $validated['pet_id'] ?? null,
            'name' => $validated['patient_name'],
            'mobile' => $validated['patient_phone'] ?? ($user->phone ?? null),
            'pet_name' => $validated['pet_name'] ?? null,
            'appointment_date' => $validated['date'],
            'appointment_time' => $validated['time_slot'],
            'status' => 'confirmed',
            'notes' => json_encode($notesPayload),
        ]);

        return $this->respondWithAppointment($appointment->fresh(), 201);
    }

    public function edit(Appointment $appointment): JsonResponse
    {
        return $this->respondWithAppointment($appointment);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return $this->respondWithAppointment($appointment);
    }

    public function patientDetails(Appointment $appointment): JsonResponse
    {
        $notes = $this->decodeNotes($appointment->notes);
        $patientUserId = $notes['patient_user_id'] ?? null;
        if (!is_numeric($patientUserId)) {
            $patientUserId = $this->resolvePatientUserId($appointment, $notes);
        }

        if (!$patientUserId) {
            return response()->json([
                'success' => false,
                'message' => 'patient_user_id not found in appointment notes',
            ], 422);
        }

        $user = User::find((int) $patientUserId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found',
            ], 404);
        }

        // Prefer the pet attached to the appointment; fall back to legacy user-pet lookup.
        $appointmentPet = $this->fetchAppointmentPet($appointment);
        $petsPayload = $appointmentPet
            ? ['source' => 'appointment_pet', 'pets' => [$appointmentPet]]
            : $this->fetchPetsForUser($user->id);

        $prescriptions = [];
        if (Schema::hasTable('prescriptions')) {
            $prescriptions = DB::table('prescriptions')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->get()
                ->all();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'appointment' => [
                    'id' => $appointment->id,
                    'date' => $appointment->appointment_date,
                    'time_slot' => $appointment->appointment_time,
                    'status' => $appointment->status,
                ],
                'patient_user_id' => (int) $user->id,
                'user' => $user->toArray(),
                'pet' => $appointmentPet,
                'patient' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role ?? null,
                    'pet_name' => $user->pet_name ?? null,
                    'pet_gender' => $user->pet_gender ?? null,
                    'pet_age' => $user->pet_age ?? null,
                    'breed' => $user->breed ?? null,
                    'latitude' => $user->latitude ?? null,
                    'longitude' => $user->longitude ?? null,
                ],
                'pets_source' => $petsPayload['source'],
                'pets' => $petsPayload['pets'],
                'prescriptions' => $prescriptions,
            ],
        ]);
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $petValidation = ['sometimes', 'nullable', 'integer'];
        if (Schema::hasTable('pets')) {
            $petValidation[] = 'exists:pets,id';
        }

        $validated = $request->validate([
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'clinic_id' => ['sometimes', 'required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['sometimes', 'required', 'integer', 'exists:doctors,id'],
            'patient_name' => ['sometimes', 'required', 'string', 'max:255'],
            'patient_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'pet_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pet_id' => $petValidation,
            'date' => ['sometimes', 'required', 'date'],
            'time_slot' => ['sometimes', 'required', 'string', 'max:50'],
            'status' => ['sometimes', 'required', 'string', 'max:24'],
            'amount' => ['sometimes', 'nullable', 'integer'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:10'],
            'razorpay_payment_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'razorpay_order_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'razorpay_signature' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (empty($validated)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one field must be provided to update the appointment.',
            ], 422);
        }

        $clinic = $appointment->clinic;
        if (array_key_exists('clinic_id', $validated)) {
            $clinic = VetRegisterationTemp::findOrFail($validated['clinic_id']);
            $appointment->vet_registeration_id = $clinic->id;
        }

        $doctor = $appointment->doctor;
        if (array_key_exists('doctor_id', $validated)) {
            $doctor = Doctor::findOrFail($validated['doctor_id']);
            $appointment->doctor_id = $doctor->id;
        }

        $user = null;
        if (array_key_exists('user_id', $validated)) {
            $user = User::findOrFail($validated['user_id']);
        }

        if (array_key_exists('patient_name', $validated)) {
            $appointment->name = $validated['patient_name'];
        }

        if (array_key_exists('patient_phone', $validated)) {
            $appointment->mobile = $validated['patient_phone'];
        }

        if (array_key_exists('pet_name', $validated)) {
            $appointment->pet_name = $validated['pet_name'];
        }

        if (array_key_exists('pet_id', $validated)) {
            $appointment->pet_id = $validated['pet_id'];
        }

        if (array_key_exists('date', $validated)) {
            $appointment->appointment_date = $validated['date'];
        }

        if (array_key_exists('time_slot', $validated)) {
            $appointment->appointment_time = $validated['time_slot'];
        }

        if (array_key_exists('status', $validated)) {
            $appointment->status = $validated['status'];
        }

        $notesPayload = $this->decodeNotes($appointment->notes);

        if ($clinic) {
            $notesPayload['clinic_name'] = $clinic->name;
        }

        if ($doctor) {
            $notesPayload['doctor_name'] = $doctor->doctor_name ?? $doctor->name ?? null;
        }

        if ($user) {
            $notesPayload['patient_user_id'] = $user->id;
            $notesPayload['patient_email'] = $user->email;
            if (!array_key_exists('patient_phone', $validated) && $user->phone) {
                $appointment->mobile = $appointment->mobile ?? $user->phone;
            }
        }

        foreach (['amount' => 'amount_paise', 'currency' => 'currency', 'razorpay_payment_id' => 'razorpay_payment_id', 'razorpay_order_id' => 'razorpay_order_id', 'razorpay_signature' => 'razorpay_signature'] as $inputKey => $noteKey) {
            if (array_key_exists($inputKey, $validated)) {
                $notesPayload[$noteKey] = $validated[$inputKey];
            }
        }

        $appointment->notes = json_encode($notesPayload);
        $appointment->save();

        return $this->respondWithAppointment($appointment->fresh());
    }

    public function listByDoctor(Doctor $doctor): JsonResponse
    {
        $appointments = Appointment::query()
            ->with(['clinic', 'doctor'])
            ->where('doctor_id', $doctor->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();

        // Video call history for the same doctor
        $videoCalls = CallSession::query()
            ->with(['patient'])
            ->where('doctor_id', $doctor->id)
            ->orderByDesc('ended_at')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->doctor_name ?? $doctor->name ?? null,
                ],
                'count' => $appointments->count(),
                'appointments' => $this->formatAppointments($appointments),
                'video_call_count' => $videoCalls->count(),
                'video_calls' => $this->formatVideoCalls($videoCalls),
            ],
        ]);
    }

    public function listByDoctorQueue(Doctor $doctor): JsonResponse
    {
        $appointments = Appointment::query()
            ->with(['clinic', 'doctor'])
            ->where('doctor_id', $doctor->id)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $timezone = config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);
        $waitingRoomUntil = $now->copy()->addMinutes(30);

        $waitingRoom = collect();
        $upcoming = collect();

        foreach ($appointments as $appointment) {
            $slot = $this->appointmentDateTime($appointment, $timezone);
            if (!$slot || $slot->lt($now)) {
                continue;
            }

            if ($slot->lte($waitingRoomUntil)) {
                $waitingRoom->push($appointment);
            } else {
                $upcoming->push($appointment);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->doctor_name ?? $doctor->name ?? null,
                ],
                'current_time' => $now->toDateTimeString(),
                'waiting_room_until' => $waitingRoomUntil->toDateTimeString(),
                'waiting_room_count' => $waitingRoom->count(),
                'upcoming_count' => $upcoming->count(),
                'waiting_room' => $this->formatAppointments($waitingRoom),
                'upcoming' => $this->formatAppointments($upcoming),
            ],
        ]);
    }

    public function listByUser(User $user): JsonResponse
    {
        $pets = collect();
        if (Schema::hasTable('pets')) {
            if (Schema::hasColumn('pets', 'user_id')) {
                $pets = Pet::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('id')
                    ->get();
            } elseif (Schema::hasColumn('pets', 'owner_id')) {
                $pets = Pet::query()
                    ->where('owner_id', $user->id)
                    ->orderByDesc('id')
                    ->get();
            }
        }

        $petIds = $pets->pluck('id')->map(fn ($id) => (int) $id)->all();

        $appointments = collect();
        if (!empty($petIds)) {
            $appointments = Appointment::query()
                ->with(['clinic', 'doctor'])
                ->whereIn('pet_id', $petIds)
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->get();
        }

        $petPayloadById = [];
        foreach ($pets as $petModel) {
            $payload = $petModel->toArray();
            foreach (['vaccine_reminder_status', 'dog_disease_payload', 'medical_history', 'vaccination_log'] as $jsonField) {
                if (array_key_exists($jsonField, $payload)) {
                    $payload[$jsonField] = $this->decodeJsonField($payload[$jsonField]);
                }
            }
            $petPayloadById[(int) $petModel->id] = $payload;
        }

        $primaryPet = $pets->first();
        $primaryPetPayload = $primaryPet ? ($petPayloadById[(int) $primaryPet->id] ?? null) : null;

        $userLookup = $this->buildUserLookup($appointments);
        $appointmentUserIds = [];
        $patientUserByAppointment = [];
        foreach ($appointments as $appointment) {
            $notes = $this->decodeNotes($appointment->notes);
            $patientUserId = $this->resolvePatientUserId($appointment, $notes, $userLookup);
            if ($patientUserId) {
                $appointmentUserIds[] = (int) $patientUserId;
                $patientUserByAppointment[$appointment->id] = (int) $patientUserId;
            }
        }

        $allUserIds = array_values(array_unique(array_filter(array_merge(
            $appointmentUserIds,
            [(int) $user->id]
        ))));

        $usersById = collect();
        if (!empty($allUserIds)) {
            $usersById = User::query()->whereIn('id', $allUserIds)->get()->keyBy('id');
        }

        $appointmentIds = $appointments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $prescriptionsFull = $this->fetchPrescriptionsForPetAppointments($petIds, $allUserIds, $appointmentIds);

        $prescriptionsByAppointment = [];
        foreach ($prescriptionsFull as $prescription) {
            $appointmentId = $prescription['video_appointment_id'] ?? null;
            if (is_numeric($appointmentId)) {
                $prescriptionsByAppointment[(int) $appointmentId][] = $prescription;
            }
        }

        $appointmentsFull = $appointments->map(function (Appointment $appointment) use ($patientUserByAppointment, $usersById, $user, $petPayloadById, $prescriptionsByAppointment) {
            $patientUserId = $patientUserByAppointment[$appointment->id] ?? null;
            $resolvedUser = null;
            if ($patientUserId && $usersById->has($patientUserId)) {
                $resolvedUser = $usersById->get($patientUserId)?->toArray();
            } else {
                $resolvedUser = $user->toArray();
            }

            $appointmentPayload = $appointment->toArray();
            $appointmentPayload['notes_decoded'] = $this->decodeNotes($appointment->notes);

            return [
                'appointment' => $appointmentPayload,
                'pet' => $petPayloadById[(int) $appointment->pet_id] ?? null,
                'user' => $resolvedUser,
                'clinic' => $appointment->clinic?->toArray(),
                'doctor' => $appointment->doctor?->toArray(),
                'prescriptions' => $prescriptionsByAppointment[$appointment->id] ?? [],
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'pet' => $primaryPet ? [
                    'id' => $primaryPet->id,
                    'name' => $primaryPet->name,
                    'breed' => $primaryPet->breed ?? null,
                ] : null,
                'pets' => $pets->map(function (Pet $petModel) {
                    return [
                        'id' => $petModel->id,
                        'name' => $petModel->name,
                        'breed' => $petModel->breed ?? null,
                    ];
                })->values()->all(),
                'owner' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'count' => $appointments->count(),
                'appointments' => $this->formatAppointments($appointments),
                'user_full' => $user->toArray(),
                'pet_full' => $primaryPetPayload,
                'pets_full' => array_values($petPayloadById),
                'owner_full' => $user->toArray(),
                'users_full' => $usersById->map(fn (User $user) => $user->toArray())->values()->all(),
                'prescriptions_full' => $prescriptionsFull,
                'appointments_full' => $appointmentsFull,
            ],
        ]);
    }

    private function respondWithAppointment(Appointment $appointment, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'appointment' => $this->formatAppointment($appointment),
            ],
        ], $status);
    }

    private function formatAppointment(Appointment $appointment, array $userLookup = []): array
    {
        $appointment->loadMissing(['clinic', 'doctor']);
        $notes = $this->decodeNotes($appointment->notes);
        $patientUserId = $this->resolvePatientUserId($appointment, $notes, $userLookup);

        return [
            'id' => $appointment->id,
            'clinic' => [
                'id' => $appointment->clinic?->id ?? $appointment->vet_registeration_id,
                'name' => $appointment->clinic?->name ?? ($notes['clinic_name'] ?? null),
            ],
            'doctor' => [
                'id' => $appointment->doctor?->id ?? $appointment->doctor_id,
                'name' => $appointment->doctor?->doctor_name ?? $appointment->doctor?->name ?? ($notes['doctor_name'] ?? null),
            ],
            'patient' => [
                'user_id' => $patientUserId,
                'name' => $appointment->name,
                'phone' => $appointment->mobile,
                'email' => $notes['patient_email'] ?? null,
            ],
            'pet_id' => $appointment->pet_id,
            'date' => $appointment->appointment_date,
            'time_slot' => $appointment->appointment_time,
            'status' => $appointment->status,
            'amount' => $notes['amount_paise'] ?? null,
            'currency' => $notes['currency'] ?? 'INR',
        ];
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function formatAppointments(Collection $appointments): array
    {
        $userLookup = $this->buildUserLookup($appointments);

        return $appointments->map(function (Appointment $appointment) use ($userLookup) {
            return $this->formatAppointment($appointment, $userLookup);
        })->all();
    }

    private function decodeNotes(?string $notes): array
    {
        $decoded = json_decode($notes ?? '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolvePatientUserId(Appointment $appointment, array $notes = [], array $userLookup = []): ?int
    {
        $fromNotes = $notes['patient_user_id'] ?? null;
        if (is_numeric($fromNotes)) {
            return (int) $fromNotes;
        }

        $fromColumn = $appointment->patient_user_id ?? null;
        if (is_numeric($fromColumn)) {
            return (int) $fromColumn;
        }

        $phoneKey = $this->normalizePhone($appointment->mobile ?? null);
        $emailKey = $this->normalizeEmail($notes['patient_email'] ?? null);

        if ($phoneKey && isset($userLookup['phone'][$phoneKey])) {
            return (int) $userLookup['phone'][$phoneKey];
        }

        if ($emailKey && isset($userLookup['email'][$emailKey])) {
            return (int) $userLookup['email'][$emailKey];
        }

        if (empty($userLookup) && ($phoneKey || $emailKey)) {
            $userId = User::query()
                ->when($phoneKey, fn ($q) => $q->where('phone', $phoneKey))
                ->when($emailKey, function ($q) use ($phoneKey, $emailKey) {
                    return $phoneKey ? $q->orWhere('email', $emailKey) : $q->where('email', $emailKey);
                })
                ->value('id');

            return $userId ? (int) $userId : null;
        }

        return null;
    }

    private function buildUserLookup(Collection $appointments): array
    {
        $phones = [];
        $emails = [];

        foreach ($appointments as $appointment) {
            $phone = $this->normalizePhone($appointment->mobile ?? null);
            if ($phone) {
                $phones[] = $phone;
            }

            $notes = $this->decodeNotes($appointment->notes);
            $email = $this->normalizeEmail($notes['patient_email'] ?? null);
            if ($email) {
                $emails[] = $email;
            }
        }

        $phones = array_values(array_unique(array_filter($phones)));
        $emails = array_values(array_unique(array_filter($emails)));

        if (empty($phones) && empty($emails)) {
            return ['phone' => [], 'email' => []];
        }

        $users = User::query()
            ->select(['id', 'phone', 'email'])
            ->where(function ($q) use ($phones, $emails) {
                if ($phones) {
                    $q->whereIn('phone', $phones);
                }

                if ($emails) {
                    if ($phones) {
                        $q->orWhereIn('email', $emails);
                    } else {
                        $q->whereIn('email', $emails);
                    }
                }
            })
            ->get();

        $lookup = ['phone' => [], 'email' => []];

        foreach ($users as $user) {
            $phone = $this->normalizePhone($user->phone ?? null);
            if ($phone) {
                $lookup['phone'][$phone] = $user->id;
            }

            $email = $this->normalizeEmail($user->email ?? null);
            if ($email) {
                $lookup['email'][$email] = $user->id;
            }
        }

        return $lookup;
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\\D+/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    private function normalizeEmail($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $email = strtolower(trim($value));

        return $email !== '' ? $email : null;
    }

    private function fetchPetsForUser(int $userId): array
    {
        if (Schema::hasTable('pets')) {
            $userColumn = Schema::hasColumn('pets', 'user_id')
                ? 'user_id'
                : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);

            if ($userColumn) {
                $pets = DB::table('pets')
                    ->where($userColumn, $userId)
                    ->orderByDesc('id')
                    ->get()
                    ->all();

                if (!empty($pets)) {
                    return ['source' => 'pets', 'pets' => $pets];
                }
            }
        }

        if (Schema::hasTable('user_pets')) {
            $pets = DB::table('user_pets')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->get()
                ->map(function ($pet) {
                    if (property_exists($pet, 'medical_history')) {
                        $pet->medical_history = $this->decodeJsonField($pet->medical_history);
                    }
                    if (property_exists($pet, 'vaccination_log')) {
                        $pet->vaccination_log = $this->decodeJsonField($pet->vaccination_log);
                    }
                    return $pet;
                })
                ->all();

            return ['source' => 'user_pets', 'pets' => $pets];
        }

        return ['source' => null, 'pets' => []];
    }

    /**
     * Fetch the pet linked directly to the appointment (appointments.pet_id) from the pets table.
     */
    private function fetchAppointmentPet(Appointment $appointment): ?array
    {
        if (!$appointment->pet_id || !Schema::hasTable('pets')) {
            return null;
        }

        $pet = DB::table('pets')
            ->where('id', $appointment->pet_id)
            ->first();

        if (!$pet) {
            return null;
        }

        // Normalize known JSON columns so consumers get structured data.
        foreach (['vaccine_reminder_status', 'dog_disease_payload'] as $jsonField) {
            if (property_exists($pet, $jsonField)) {
                $pet->{$jsonField} = $this->decodeJsonField($pet->{$jsonField});
            }
        }

        return (array) $pet;
    }

    private function decodeJsonField($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function fetchPrescriptionsForPetAppointments(array $petIds, array $userIds, array $appointmentIds): array
    {
        if (!Schema::hasTable('prescriptions')) {
            return [];
        }

        $hasPetId = Schema::hasColumn('prescriptions', 'pet_id');
        $hasUserId = Schema::hasColumn('prescriptions', 'user_id');
        $hasVideoAppointmentId = Schema::hasColumn('prescriptions', 'video_appointment_id');

        if (!$hasPetId && !$hasUserId && !$hasVideoAppointmentId) {
            return [];
        }

        $query = DB::table('prescriptions');
        $query->where(function ($q) use ($hasPetId, $hasUserId, $hasVideoAppointmentId, $petIds, $userIds, $appointmentIds) {
            $hasCondition = false;

            if ($hasPetId && !empty($petIds)) {
                $q->whereIn('pet_id', $petIds);
                $hasCondition = true;
            }

            if ($hasUserId && !empty($userIds)) {
                if ($hasCondition) {
                    $q->orWhereIn('user_id', $userIds);
                } else {
                    $q->whereIn('user_id', $userIds);
                }
                $hasCondition = true;
            }

            if ($hasVideoAppointmentId && !empty($appointmentIds)) {
                if ($hasCondition) {
                    $q->orWhereIn('video_appointment_id', $appointmentIds);
                } else {
                    $q->whereIn('video_appointment_id', $appointmentIds);
                }
            }
        });

        return $query
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                $payload = (array) $row;
                if (array_key_exists('medications_json', $payload)) {
                    $payload['medications_json'] = $this->decodeJsonField($payload['medications_json']);
                }
                return $payload;
            })
            ->values()
            ->all();
    }

    private function appointmentDateTime(Appointment $appointment, string $timezone): ?Carbon
    {
        $date = trim((string) $appointment->appointment_date);
        if ($date === '') {
            return null;
        }

        $time = trim((string) $appointment->appointment_time);
        if ($time === '') {
            $time = '00:00:00';
        }

        $dateTime = $date . ' ' . $time;
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d h:i A',
            'Y-m-d h:i:s A',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateTime, $timezone);
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($dateTime, $timezone);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param  Collection<int, CallSession>  $sessions
     */
    private function formatVideoCalls(Collection $sessions): array
    {
        return $sessions->map(function (CallSession $session) {
            return $this->formatVideoCall($session);
        })->all();
    }

    private function formatVideoCall(CallSession $session): array
    {
        $session->loadMissing('patient');
        $timestamp = $session->ended_at ?? $session->started_at ?? $session->created_at;

        return [
            'id' => $session->id,
            'patient' => [
                'user_id' => $session->patient_id,
                'name' => $session->patient?->name,
                'phone' => $session->patient?->phone,
                'email' => $session->patient?->email,
            ],
            'status' => $session->status,
            'payment_status' => $session->payment_status,
            'amount' => $session->amount_paid,
            'currency' => $session->currency ?? 'INR',
            'started_at' => $session->started_at?->toDateTimeString(),
            'ended_at' => $session->ended_at?->toDateTimeString(),
            'created_at' => $session->created_at?->toDateTimeString(),
            'timestamp' => $timestamp?->toDateTimeString(),
            'duration_seconds' => $session->duration_seconds,
        ];
    }
}
