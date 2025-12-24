<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CallSession;
use App\Models\Doctor;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AppointmentSubmissionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'patient_id' => ['nullable', 'integer'],
            'clinic_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:20'],
            'patient_email' => ['nullable', 'email', 'max:191'],
            'pet_name' => ['nullable', 'string', 'max:255'],
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

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'clinic_id' => ['sometimes', 'required', 'integer', 'exists:vet_registerations_temp,id'],
            'doctor_id' => ['sometimes', 'required', 'integer', 'exists:doctors,id'],
            'patient_name' => ['sometimes', 'required', 'string', 'max:255'],
            'patient_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'pet_name' => ['sometimes', 'nullable', 'string', 'max:255'],
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

    public function listByUser(User $user): JsonResponse
    {
        $appointments = Appointment::query()
            ->with(['clinic', 'doctor'])
            ->where(function ($query) use ($user) {
                $jsonPath = 'notes->patient_user_id';
                $query->whereJsonContains($jsonPath, $user->id)
                    ->orWhere('notes', 'like', '%"patient_user_id":'.$user->id.'%');
            })
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'count' => $appointments->count(),
                'appointments' => $this->formatAppointments($appointments),
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
