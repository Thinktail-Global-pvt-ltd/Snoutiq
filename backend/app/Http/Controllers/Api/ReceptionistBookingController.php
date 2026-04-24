<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Pet;
use App\Models\User;
use App\Models\UserPet;
use App\Models\Receptionist;
use App\Models\Appointment;
use App\Models\RazorpayPaymentLink;
use App\Models\Transaction;
use App\Services\ConsultationShareSessionService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ReceptionistBookingController extends Controller
{
    private const PATIENT_ROLES = ['pet', 'pet_owner', 'patient', 'user'];

    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly ConsultationShareSessionService $consultSessions
    )
    {
    }

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
        $clinicId = $this->resolveClinicId($request);
        $hasLastVetColumn = Schema::hasColumn('users', 'last_vet_id');

        if ($hasLastVetColumn && !$clinicId) {
            return response()->json([
                'success' => false,
                'message' => 'clinic_id or vet context is required to list patients.',
            ], 422);
        }

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

        if ($hasLastVetColumn && $clinicId) {
            $builder->where('last_vet_id', $clinicId);
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
                'doctor_status',
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
                'toggle_availability' => (bool) $doctor->toggle_availability, // deprecated: prefer doctor_status
                'doctor_status' => $doctor->doctor_status ?? null,
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

    /**
     * GET /api/receptionist/appointments/today
     * Lists today's appointments for the clinic (ordered by time).
     */
    public function appointmentsToday(Request $request)
    {
        // Keep this endpoint strictly clinic-id driven; avoid unrelated fallback contexts.
        $clinicIdRaw = $request->input('clinic_id')
            ?? $request->query('clinic_id')
            ?? $request->input('vet_registeration_id')
            ?? $request->query('vet_registeration_id')
            ?? $request->input('vet_id')
            ?? $request->query('vet_id');

        $clinicId = ($clinicIdRaw !== null && $clinicIdRaw !== '') ? (int) $clinicIdRaw : null;
        if (!$clinicId) {
            return response()->json(['success' => false, 'message' => 'clinic_id required'], 422);
        }

        $date = trim((string) $request->query('date', ''));
        if ($date !== '') {
            try {
                $date = Carbon::parse($date)->toDateString();
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Invalid date. Use YYYY-MM-DD'], 422);
            }
        }

        $latestPrescriptionByAppointment = DB::table('prescriptions')
            ->select('in_clinic_appointment_id', DB::raw('MAX(id) as latest_prescription_id'))
            ->whereNotNull('in_clinic_appointment_id')
            ->groupBy('in_clinic_appointment_id');

        $hasAppointmentPetIdColumn = Schema::hasColumn('appointments', 'pet_id');

        $query = DB::table('appointments as a')
            ->leftJoin('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->leftJoinSub($latestPrescriptionByAppointment, 'lp', function ($join) {
                $join->on('lp.in_clinic_appointment_id', '=', 'a.id');
            })
            ->leftJoin('prescriptions as p', 'p.id', '=', 'lp.latest_prescription_id')
            ->where('a.vet_registeration_id', $clinicId)
            ->orderBy('a.appointment_date')
            ->orderBy('a.appointment_time')
            ->select(
                'a.id',
                'a.name as patient_name',
                'a.mobile as patient_phone',
                'a.pet_name',
                DB::raw($hasAppointmentPetIdColumn ? 'a.pet_id as appointment_pet_id' : 'NULL as appointment_pet_id'),
                'a.appointment_date',
                'a.appointment_time',
                'a.status',
                'a.doctor_id',
                'a.notes',
                DB::raw('COALESCE(d.doctor_name, "") as doctor_name'),
                'p.id as prescription_id',
                'p.in_clinic_appointment_id',
                'p.diagnosis as prescription_diagnosis',
                'p.follow_up_date as prescription_follow_up_date',
                'p.follow_up_type as prescription_follow_up_type',
                'p.follow_up_notes as prescription_follow_up_notes'
            );

        if ($date !== '') {
            $query->whereDate('a.appointment_date', $date);
        }

        $rows = $query->get();

        $rows = $rows->map(function ($row) {
            $row->notes_payment = $this->extractNotesPaymentStatus($row->notes ?? null);
            $row->patient_id = $this->extractPatientId($row->notes ?? null);
            unset($row->notes);
            return $row;
        });

        $userBlobUrlById = collect();
        $petBlobUrlById = collect();
        $petDetailsById = collect();
        $petByUserAndName = collect();
        $latestPetByUserId = collect();

        if ($this->userPetDoc2BlobColumnsReady()) {
            $patientIds = $rows->pluck('patient_id')
                ->filter(fn ($patientId) => is_numeric($patientId) && (int) $patientId > 0)
                ->map(fn ($patientId) => (int) $patientId)
                ->unique()
                ->values();

            if ($patientIds->isNotEmpty()) {
                $userIdsWithBlob = DB::table('users')
                    ->whereIn('id', $patientIds->all())
                    ->whereNotNull('pet_doc2_blob')
                    ->where('pet_doc2_blob', '!=', '')
                    ->pluck('id')
                    ->map(fn ($userId) => (int) $userId);

                $userBlobUrlById = $userIdsWithBlob->mapWithKeys(
                    fn (int $userId) => [$userId => route('api.users.pet-doc2-blob', ['user' => $userId], true)]
                );
            }
        }

        if (Schema::hasTable('pets')) {
            $directPetIds = $rows->pluck('appointment_pet_id')
                ->filter(fn ($petId) => is_numeric($petId) && (int) $petId > 0)
                ->map(fn ($petId) => (int) $petId)
                ->unique()
                ->values();

            $patientIds = $rows->pluck('patient_id')
                ->filter(fn ($patientId) => is_numeric($patientId) && (int) $patientId > 0)
                ->map(fn ($patientId) => (int) $patientId)
                ->unique()
                ->values();

            if ($directPetIds->isNotEmpty() || $patientIds->isNotEmpty()) {
                $petColumns = ['id', 'user_id', 'name'];
                foreach (['pet_doc1', 'pet_doc2', 'pic_link'] as $column) {
                    if (Schema::hasColumn('pets', $column)) {
                        $petColumns[] = $column;
                    }
                }

                $petQuery = DB::table('pets')
                    ->select($petColumns)
                    ->orderByDesc('id');

                $petQuery->where(function ($builder) use ($directPetIds, $patientIds) {
                    if ($directPetIds->isNotEmpty()) {
                        $builder->whereIn('id', $directPetIds->all());
                    }
                    if ($patientIds->isNotEmpty()) {
                        $method = $directPetIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $builder->{$method}('user_id', $patientIds->all());
                    }
                });

                $petRecords = $petQuery->get();

                $petDetailsById = $petRecords->keyBy(fn ($pet) => (int) $pet->id);
                $petByUserAndName = $petRecords->groupBy(function ($pet) {
                    $name = Str::lower(trim((string) ($pet->name ?? '')));
                    return ((int) ($pet->user_id ?? 0)).'|'.$name;
                });
                $latestPetByUserId = $petRecords
                    ->groupBy(fn ($pet) => (int) ($pet->user_id ?? 0))
                    ->map(fn ($pets) => $pets->first());

                if ($this->petDoc2BlobColumnsReady()) {
                    $petIdsWithBlob = DB::table('pets')
                        ->whereIn('id', $petRecords->pluck('id')->all())
                        ->whereNotNull('pet_doc2_blob')
                        ->where('pet_doc2_blob', '!=', '')
                        ->pluck('id')
                        ->map(fn ($petId) => (int) $petId);

                    $petBlobUrlById = $petIdsWithBlob->mapWithKeys(
                        fn (int $petId) => [$petId => route('api.pets.pet-doc2-blob', ['pet' => $petId], true)]
                    );
                }
            }
        }

        $rows = $rows->map(function ($row) use ($userBlobUrlById, $petDetailsById, $petByUserAndName, $latestPetByUserId, $petBlobUrlById) {
            $patientId = is_numeric($row->patient_id ?? null) ? (int) $row->patient_id : null;
            $appointmentPetId = is_numeric($row->appointment_pet_id ?? null) ? (int) $row->appointment_pet_id : null;

            $pet = $appointmentPetId ? $petDetailsById->get($appointmentPetId) : null;
            if (!$pet && $patientId) {
                $petNameKey = Str::lower(trim((string) ($row->pet_name ?? '')));
                if ($petNameKey !== '') {
                    $pet = $petByUserAndName->get($patientId.'|'.$petNameKey)?->first();
                }
                if (!$pet) {
                    $pet = $latestPetByUserId->get($patientId);
                }
            }

            $resolvedPetId = $pet ? (int) $pet->id : $appointmentPetId;
            $petBlobUrl = $resolvedPetId ? $petBlobUrlById->get($resolvedPetId) : null;
            $petImageUrl = $petBlobUrl
                ?: $this->absolutePetDocumentUrl($pet->pet_doc1 ?? null)
                ?: $this->absolutePetDocumentUrl($pet->pet_doc2 ?? null)
                ?: $this->absolutePetDocumentUrl($pet->pic_link ?? null);

            $row->user_pet_doc2_blob_url = $patientId ? $userBlobUrlById->get($patientId) : null;
            $row->user_image_url = $row->user_pet_doc2_blob_url;
            $row->pet_id = $resolvedPetId ?: null;
            $row->pet_doc2_blob_url = $petBlobUrl;
            $row->pet_image_url = $petImageUrl;
            unset($row->appointment_pet_id);
            return $row;
        });

        return response()->json([
            'success' => true,
            'date' => $date !== '' ? $date : 'all',
            'count' => $rows->count(),
            'appointments' => $rows,
        ]);
    }

    private function extractPatientId(?string $notes): ?int
    {
        if (!$notes) {
            return null;
        }

        $decoded = json_decode($notes, true);
        if (is_array($decoded)) {
            foreach (['patient_user_id', 'patient_id', 'user_id'] as $key) {
                if (isset($decoded[$key]) && is_numeric($decoded[$key])) {
                    $value = (int) $decoded[$key];
                    return $value > 0 ? $value : null;
                }
            }
        }

        if (preg_match('/patient[_-]?id\\s*[:=]\\s*(\\d+)/i', $notes, $m)) {
            $value = (int) $m[1];
            return $value > 0 ? $value : null;
        }

        return null;
    }

    private function extractNotesPaymentStatus(?string $notes): bool
    {
        if (!$notes) {
            return false;
        }

        $decoded = json_decode($notes, true);
        if (!is_array($decoded)) {
            return false;
        }

        $amountPaise = isset($decoded['amount_paise']) && is_numeric($decoded['amount_paise'])
            ? (int) $decoded['amount_paise']
            : 0;
        $paymentId = trim((string) ($decoded['razorpay_payment_id'] ?? ''));
        $orderId = trim((string) ($decoded['razorpay_order_id'] ?? ''));
        $signature = trim((string) ($decoded['razorpay_signature'] ?? ''));

        // Treat mocked payment ids like pay_new as unpaid.
        $isMockPayment = in_array(strtolower($paymentId), ['pay_new', 'new', 'test'], true);
        $hasValidPaymentId = $paymentId !== '' && ! $isMockPayment;
        $hasOrderAndSignature = $orderId !== '' && $signature !== '';

        return $amountPaise > 0 && $hasValidPaymentId && $hasOrderAndSignature;
    }

    public function storePatient(Request $request)
    {
        if ($request->filled('lastVetId') && !$request->filled('last_vet_id')) {
            $request->merge(['last_vet_id' => $request->input('lastVetId')]);
        }

        if ($request->has('phone')) {
            $phone = trim((string) $request->input('phone'));
            $request->merge(['phone' => $phone !== '' ? $phone : null]);
        }

        if ($request->filled('pet_weight') && !$request->filled('weight')) {
            $request->merge(['weight' => $request->input('pet_weight')]);
        }

        $hasRoleColumn = Schema::hasColumn('users', 'role');
        $hasLastVetIdColumn = Schema::hasColumn('users', 'last_vet_id');
        $existingUser = null;

        $phone = $request->input('phone');
        if ($phone !== null && $phone !== '') {
            $existingUserQuery = User::query()->where('phone', $phone);
            if ($hasRoleColumn) {
                $existingUserQuery->whereIn('role', self::PATIENT_ROLES);
            }

            $existingUser = $existingUserQuery->first();
        }

        $phoneRules = ['nullable', 'string', 'max:25'];
        $phoneUniqueRule = Rule::unique('users', 'phone');
        if ($existingUser) {
            $phoneUniqueRule = $phoneUniqueRule->ignore($existingUser->id);
        }

        if ($hasRoleColumn) {
            $phoneRules[] = $phoneUniqueRule->where(function ($query) {
                $query->whereIn('role', self::PATIENT_ROLES);
            });
        } else {
            $phoneRules[] = $phoneUniqueRule;
        }

        $emailRules = ['nullable', 'email'];
        $emailUniqueRule = Rule::unique('users', 'email');
        if ($existingUser) {
            $emailUniqueRule = $emailUniqueRule->ignore($existingUser->id);
        }
        $emailRules[] = $emailUniqueRule;

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => $emailRules,
            'phone' => $phoneRules,
            'pet_name' => 'nullable|string|max:120',
            'pet_type' => 'nullable|string|max:120',
            'pet_breed' => 'nullable|string|max:120',
            'pet_gender' => 'nullable|string|max:50',
            'pet_age' => 'nullable|integer|min:0|max:255',
            'pet_age_months' => 'nullable|integer|min:0|max:255',
            'pet_dob' => 'nullable|date',
            'weight' => 'nullable|numeric|min:0',
            'pet_doc1' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'pet_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'pet_pic' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'pet_doc2' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'last_vet_id' => 'nullable|integer|exists:vet_registerations_temp,id',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'amount' => 'nullable|numeric|min:1|max:1000000',
            'amount_paise' => 'nullable|integer|min:100|max:100000000',
            'response_time_minutes' => 'nullable|integer|min:1|max:1440',
        ], [
            'phone.unique' => 'A patient with this phone number already exists.',
        ]);

        $clinicId = $this->resolveClinicId($request);

        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Email or phone is required for a patient record.',
            ], 422);
        }

        $patientName = trim((string) ($data['name'] ?? ''));

        $userPayload = [];
        if (array_key_exists('name', $data)) {
            $userPayload['name'] = $patientName !== '' ? $patientName : null;
        }
        if (array_key_exists('email', $data)) {
            $userPayload['email'] = $data['email'] ?? null;
        }
        if (array_key_exists('phone', $data)) {
            $userPayload['phone'] = $data['phone'] ?? null;
        }
        if ($hasRoleColumn && (!$existingUser || empty($existingUser->role))) {
            $userPayload['role'] = 'pet';
        }
        if ($hasLastVetIdColumn) {
            $lastVetId = $data['last_vet_id'] ?? null;

            if ($clinicId) {
                if (Schema::hasTable('vet_registerations_temp')) {
                    $clinicExists = DB::table('vet_registerations_temp')->where('id', $clinicId)->exists();
                    if ($clinicExists) {
                        $lastVetId = $clinicId;
                    }
                } else {
                    $lastVetId = $clinicId;
                }
            }

            if (!$existingUser || $clinicId || array_key_exists('last_vet_id', $data)) {
                $userPayload['last_vet_id'] = $lastVetId;
            }
        }

        if ($existingUser) {
            $existingUser->fill($userPayload);
            $existingUser->save();
            $user = $existingUser->fresh();
        } else {
            $userPayload['password'] = Hash::make(Str::random(16));
            $user = User::create($userPayload);
        }

        $pet = null;
        if (!empty($data['pet_name'])) {
            if (!Schema::hasTable('pets')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pets table is not available.',
                ], 500);
            }

            $userColumn = Schema::hasColumn('pets', 'user_id')
                ? 'user_id'
                : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);

            if (!$userColumn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pets table is missing user reference column.',
                ], 500);
            }

            $petPayload = [
                $userColumn => $user->id,
                'name' => $data['pet_name'],
                'breed' => $data['pet_breed'] ?? 'Unknown',
            ];

            if (Schema::hasColumn('pets', 'pet_age') && array_key_exists('pet_age', $data) && $data['pet_age'] !== null) {
                $petPayload['pet_age'] = (int) $data['pet_age'];
            }
            if (Schema::hasColumn('pets', 'pet_age_months') && array_key_exists('pet_age_months', $data) && $data['pet_age_months'] !== null) {
                $petPayload['pet_age_months'] = (int) $data['pet_age_months'];
            }
            if (array_key_exists('pet_dob', $data) && $data['pet_dob']) {
                if (Schema::hasColumn('pets', 'pet_dob')) {
                    $petPayload['pet_dob'] = $data['pet_dob'];
                } elseif (Schema::hasColumn('pets', 'dob')) {
                    $petPayload['dob'] = $data['pet_dob'];
                }
            }
            if (Schema::hasColumn('pets', 'weight') && array_key_exists('weight', $data) && $data['weight'] !== null) {
                $petPayload['weight'] = (float) $data['weight'];
            }

            if (Schema::hasColumn('pets', 'type')) {
                $petPayload['type'] = $data['pet_type'] ?? 'dog';
            } elseif (Schema::hasColumn('pets', 'pet_type')) {
                $petPayload['pet_type'] = $data['pet_type'] ?? 'dog';
            }

            if (Schema::hasColumn('pets', 'pet_gender')) {
                $petPayload['pet_gender'] = $data['pet_gender'] ?? 'unknown';
            } elseif (Schema::hasColumn('pets', 'gender')) {
                $petPayload['gender'] = $data['pet_gender'] ?? 'unknown';
            }

            $uploadedPetFile = $this->resolvePetUploadFile($request);
            $blobColumnsReady = $this->petDoc2BlobColumnsReady();
            if ($uploadedPetFile && ! $blobColumnsReady) {
                return response()->json([
                    'success' => false,
                    'message' => 'pet_doc2 blob columns are missing. Please run migrations.',
                ], 500);
            }

            $petDocBlob = null;
            $petDocMime = null;
            if ($uploadedPetFile && $blobColumnsReady) {
                $petDocBlob = $uploadedPetFile->get();
                $petDocMime = $uploadedPetFile->getMimeType() ?: ($uploadedPetFile->getClientMimeType() ?: 'application/octet-stream');
            }

            $petDocPath = $this->storePetDocument($uploadedPetFile);
            if ($petDocPath) {
                if (Schema::hasColumn('pets', 'pet_doc1')) {
                    $petPayload['pet_doc1'] = $petDocPath;
                } elseif (Schema::hasColumn('pets', 'pic_link')) {
                    $petPayload['pic_link'] = $petDocPath;
                }
            }
            if ($petDocBlob !== null && Schema::hasColumn('pets', 'pet_doc2_blob')) {
                $petPayload['pet_doc2_blob'] = $petDocBlob;
                if (Schema::hasColumn('pets', 'pet_doc2_mime')) {
                    $petPayload['pet_doc2_mime'] = $petDocMime;
                }
            }

            $now = now();
            if (Schema::hasColumn('pets', 'created_at')) {
                $petPayload['created_at'] = $now;
            }
            if (Schema::hasColumn('pets', 'updated_at')) {
                $petPayload['updated_at'] = $now;
            }

            DB::table('pets')
                ->where($userColumn, $user->id)
                ->delete();

            $petId = DB::table('pets')->insertGetId($petPayload);
            $pet = (object) [
                'id' => $petId,
                'name' => $data['pet_name'],
                'type' => $data['pet_type'] ?? 'dog',
                'breed' => $data['pet_breed'] ?? 'Unknown',
            ];
            if (isset($petPayload['weight'])) {
                $pet->weight = $petPayload['weight'];
            }
            if (isset($petPayload['pet_doc1'])) {
                $pet->pet_doc1 = url($petPayload['pet_doc1']);
            }
            if (isset($petPayload['pic_link'])) {
                $pet->pic_link = url($petPayload['pic_link']);
            }
            if ($petDocBlob !== null) {
                $pet->pet_doc2_blob_url = route('api.pets.pet-doc2-blob', ['pet' => $petId], true);
            }
        }

        $consultSession = $this->consultSessions->createForPatient(
            user: $user,
            pet: $pet,
            data: $data,
            clinicId: $clinicId,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'last_vet_id' => $hasLastVetIdColumn ? $user->last_vet_id : null,
                ],
                'pet' => $pet ? [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'type' => $pet->type,
                    'breed' => $pet->breed,
                    'pet_doc2_blob_url' => $pet->pet_doc2_blob_url ?? null,
                    'pet_image_url' => $pet->pet_doc2_blob_url ?? $pet->pet_doc1 ?? $pet->pic_link ?? null,
                ] : null,
                'consult_session' => $this->consultSessions->formatForResponse($consultSession),
                'payment_link_whatsapp' => [
                    'sent' => false,
                    'skipped' => true,
                    'reason' => 'awaiting_parent_initiation',
                ],
            ],
        ], 201);
    }

    public function sendExistingPatientPaymentLink(Request $request)
    {
        $data = $request->validate([
            'clinic_id' => 'nullable|integer|exists:vet_registerations_temp,id',
            'user_id' => 'required|integer|exists:users,id',
            'pet_id' => 'required|integer|min:1',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'amount' => 'nullable|numeric|min:1|max:1000000',
            'amount_paise' => 'nullable|integer|min:100|max:100000000',
            'response_time_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $clinicId = $this->resolveClinicId($request);
        $user = User::query()->find((int) $data['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $pet = $this->resolveExistingPatientPet($user, (int) $data['pet_id']);
        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found for this user.',
            ], 404);
        }

        if (trim((string) $user->phone) === '') {
            return response()->json([
                'success' => false,
                'message' => 'User phone is required to send payment link.',
            ], 422);
        }

        $paymentData = array_merge($data, [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'pet_name' => $pet->name ?? null,
            'pet_type' => $pet->type ?? $pet->pet_type ?? null,
            'pet_breed' => $pet->breed ?? null,
        ]);

        $consultSession = $this->consultSessions->createForPatient(
            user: $user,
            pet: $pet,
            data: $paymentData,
            clinicId: $clinicId,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role ?? null,
                    'last_vet_id' => Schema::hasColumn('users', 'last_vet_id') ? $user->last_vet_id : null,
                ],
                'pet' => $this->formatExistingPatientPet($pet),
                'consult_session' => $this->consultSessions->formatForResponse($consultSession),
                'payment_link_whatsapp' => [
                    'sent' => false,
                    'skipped' => true,
                    'reason' => 'awaiting_parent_initiation',
                ],
            ],
        ], 201);
    }

    public function consultSessionStatus(string $sessionToken)
    {
        $session = $this->consultSessions->findByToken($sessionToken);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Consult session not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->consultSessions->formatForResponse($session),
        ]);
    }

    private function resolveExistingPatientPet(User $user, int $petId): ?object
    {
        if ($petId <= 0) {
            return null;
        }

        if (Schema::hasTable('pets')) {
            $query = Pet::query()
                ->select($this->existingPetSelectColumns('pets'))
                ->where('id', $petId);

            if (Schema::hasColumn('pets', 'user_id')) {
                $query->where('user_id', $user->id);
            } elseif (Schema::hasColumn('pets', 'owner_id')) {
                $query->where('owner_id', $user->id);
            }

            $pet = $query->first();
            if ($pet) {
                $payload = (object) $pet->toArray();
                $payload->source_table = 'pets';
                $payload->type = data_get($payload, 'type') ?: data_get($payload, 'pet_type');
                $payload->gender = data_get($payload, 'pet_gender') ?: data_get($payload, 'gender');
                if ($this->existingPetHasBlob('pets', $petId)) {
                    $payload->pet_doc2_blob_url = route('api.pets.pet-doc2-blob', ['pet' => $petId], true);
                }

                return $payload;
            }
        }

        if (Schema::hasTable('user_pets')) {
            $query = UserPet::query()
                ->select($this->existingPetSelectColumns('user_pets'))
                ->where('id', $petId);

            if (Schema::hasColumn('user_pets', 'user_id')) {
                $query->where('user_id', $user->id);
            }

            $pet = $query->first();
            if ($pet) {
                $payload = (object) $pet->toArray();
                $payload->source_table = 'user_pets';
                $payload->type = data_get($payload, 'type') ?: data_get($payload, 'pet_type');
                $payload->gender = data_get($payload, 'pet_gender') ?: data_get($payload, 'gender');

                return $payload;
            }
        }

        return null;
    }

    private function existingPetSelectColumns(string $table): array
    {
        $columns = [];
        foreach ([
            'id',
            'user_id',
            'owner_id',
            'name',
            'type',
            'pet_type',
            'breed',
            'pet_gender',
            'gender',
            'pet_doc1',
            'pet_doc2',
            'pic_link',
            'weight',
        ] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $columns[] = $column;
            }
        }

        return $columns ?: ['id'];
    }

    private function existingPetHasBlob(string $table, int $petId): bool
    {
        return Schema::hasColumn($table, 'pet_doc2_blob')
            && DB::table($table)
                ->where('id', $petId)
                ->whereNotNull('pet_doc2_blob')
                ->exists();
    }

    private function formatExistingPatientPet(object $pet): array
    {
        $petDoc2BlobUrl = data_get($pet, 'pet_doc2_blob_url');
        $petDoc1 = data_get($pet, 'pet_doc1');
        $petDoc2 = data_get($pet, 'pet_doc2');
        $picLink = data_get($pet, 'pic_link');

        return [
            'id' => data_get($pet, 'id'),
            'source_table' => data_get($pet, 'source_table'),
            'name' => data_get($pet, 'name'),
            'type' => data_get($pet, 'type') ?: data_get($pet, 'pet_type'),
            'breed' => data_get($pet, 'breed'),
            'gender' => data_get($pet, 'gender') ?: data_get($pet, 'pet_gender'),
            'weight' => data_get($pet, 'weight'),
            'pet_doc2_blob_url' => $petDoc2BlobUrl,
            'pet_image_url' => $petDoc2BlobUrl ?: $petDoc1 ?: $petDoc2 ?: $picLink,
        ];
    }

    private function maybeSendPaymentLinkWhatsApp(User $user, ?object $pet, array $data, ?int $clinicId): array
    {
        $parentName = trim((string) ($data['name'] ?? $user->name ?? ''));
        $phone = trim((string) ($user->phone ?? $data['phone'] ?? ''));
        $petName = trim((string) ($data['pet_name'] ?? data_get($pet, 'name') ?? ''));
        $petBreed = trim((string) ($data['pet_breed'] ?? data_get($pet, 'breed') ?? ''));

        if ($phone === '') {
            return [
                'sent' => false,
                'skipped' => true,
                'reason' => 'phone is required',
            ];
        }

        try {
            $useFullTemplate = $parentName !== '' && $petName !== '' && $petBreed !== '';
            $amountPaise = $this->resolveConsultationAmountPaise($data);
            $paymentLink = $this->createRazorpayPaymentLink($user, $pet, $data, $clinicId, $amountPaise);
            $shortUrl = trim((string) ($paymentLink['short_url'] ?? ''));
            $shortCode = $this->extractRazorpayShortCode($shortUrl);
            $buttonUrl = $shortCode !== '' ? 'https://rzp.io/rzp/'.$shortCode : '';

            if ($shortCode === '') {
                return [
                    'sent' => false,
                    'skipped' => false,
                    'reason' => 'Razorpay payment link did not return a usable short URL',
                    'payment_link' => $shortUrl ?: null,
                ];
            }

            $doctorName = $this->resolveTemplateDoctorName($data, $clinicId);
            $responseTime = (string) ((int) ($data['response_time_minutes'] ?? 10));
            $amountRupees = $this->formatRupeesForTemplate($amountPaise);
            $to = $this->normalizeWhatsAppPhone($phone);

            if ($useFullTemplate) {
                $template = config('services.whatsapp.templates.cf_payment_link_full', 'cf_payment_link_full');
                $language = config('services.whatsapp.templates.cf_payment_link_full_language', 'en');
                $bodyParameters = [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $responseTime],
                    ['type' => 'text', 'text' => $amountRupees],
                ];
            } else {
                $template = config('services.whatsapp.templates.cf_payment_link_mini', 'cf_payment_link_mini');
                $language = config('services.whatsapp.templates.cf_payment_link_mini_language', 'en');
                $bodyParameters = [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $responseTime],
                    ['type' => 'text', 'text' => $amountRupees],
                ];
            }

            $components = [
                [
                    'type' => 'body',
                    'parameters' => $bodyParameters,
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'text', 'text' => $shortCode],
                    ],
                ],
            ];

            $whatsAppResponse = $this->whatsApp->sendTemplateWithResult(
                to: $to,
                template: $template,
                components: $components,
                language: $language,
                channelName: 'receptionist_patient_payment_link'
            );

            return [
                'sent' => true,
                'template' => $template,
                'template_variant' => $useFullTemplate ? 'full' : 'mini',
                'to' => $to,
                'amount' => $amountRupees,
                'amount_paise' => $amountPaise,
                'payment_link' => $shortUrl,
                'payment_link_slug' => $shortCode,
                'book_now_url' => $buttonUrl,
                'button_parameter_sent' => $shortCode,
                'payment_link_id' => $paymentLink['id'] ?? null,
                'whatsapp' => $whatsAppResponse,
            ];
        } catch (\Throwable $e) {
            Log::warning('receptionist.patient.payment_link_whatsapp_failed', [
                'user_id' => $user->id ?? null,
                'pet_id' => $pet->id ?? null,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'skipped' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    private function resolveConsultationAmountPaise(array $data): int
    {
        if (!empty($data['amount_paise'])) {
            return (int) $data['amount_paise'];
        }

        $amountRupees = (float) ($data['amount'] ?? 499);

        return (int) round($amountRupees * 100);
    }

    private function createRazorpayPaymentLink(User $user, ?object $pet, array $data, ?int $clinicId, int $amountPaise): array
    {
        $key = trim((string) (config('services.razorpay.key') ?? ''));
        $secret = trim((string) (config('services.razorpay.secret') ?? ''));

        if ($key === '' || $secret === '') {
            throw new \RuntimeException('Razorpay credentials missing');
        }

        $referenceId = 'SNOUTIQ_CONSULT_'.$user->id.'_'.($pet?->id ?? 'PET').'_'.Str::upper(Str::random(8));
        $phone = $this->normalizeWhatsAppPhone((string) ($user->phone ?? $data['phone'] ?? ''));

        $payload = [
            'amount' => $amountPaise,
            'currency' => 'INR',
            'reference_id' => $referenceId,
            'description' => 'Snoutiq - Veterinary Consultation',
            'customer' => array_filter([
                'name' => $user->name,
                'contact' => $phone ? '+'.$phone : null,
                'email' => $user->email,
            ]),
            'notify' => [
                'sms' => true,
                'email' => !empty($user->email),
            ],
            'reminder_enable' => true,
            'notes' => array_filter([
                'service' => 'Veterinary Consultation',
                'source' => 'receptionist_patients',
                'clinic_id' => $clinicId,
                'patient_id' => $user->id,
                'pet_id' => $pet?->id,
                'doctor_id' => !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null,
                'pet_name' => $pet?->name,
                'pet_breed' => $pet?->breed,
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        $response = Http::withBasicAuth($key, $secret)
            ->acceptJson()
            ->asJson()
            ->post('https://api.razorpay.com/v1/payment_links', $payload);

        $body = $response->json();
        if (!$response->successful()) {
            $message = data_get($body, 'error.description')
                ?? data_get($body, 'error.reason')
                ?? $response->body()
                ?? 'Unable to create Razorpay payment link';
            throw new \RuntimeException('Razorpay payment link failed: '.$message);
        }

        if (is_array($body)) {
            $this->storeRazorpayPaymentLink(
                paymentLink: $body,
                user: $user,
                pet: $pet,
                data: $data,
                clinicId: $clinicId,
                amountPaise: $amountPaise,
                referenceId: $referenceId
            );
        }

        return is_array($body) ? $body : [];
    }

    private function storeRazorpayPaymentLink(
        array $paymentLink,
        User $user,
        ?object $pet,
        array $data,
        ?int $clinicId,
        int $amountPaise,
        string $referenceId
    ): void {
        if (!Schema::hasTable('razorpay_payment_links')) {
            return;
        }

        $paymentLinkId = trim((string) ($paymentLink['id'] ?? ''));
        if ($paymentLinkId === '') {
            return;
        }

        try {
            $shortUrl = trim((string) ($paymentLink['short_url'] ?? ''));

            RazorpayPaymentLink::updateOrCreate(
                ['payment_link_id' => $paymentLinkId],
                [
                    'short_url' => $shortUrl ?: null,
                    'short_code' => $this->extractRazorpayShortCode($shortUrl) ?: null,
                    'reference_id' => $paymentLink['reference_id'] ?? $referenceId,
                    'source' => 'receptionist_patients',
                    'user_id' => $user->id,
                    'pet_id' => $pet?->id,
                    'clinic_id' => $clinicId,
                    'doctor_id' => !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null,
                    'amount_paise' => $amountPaise,
                    'currency' => $paymentLink['currency'] ?? 'INR',
                    'status' => $paymentLink['status'] ?? 'created',
                    'raw_response' => $paymentLink,
                ]
            );

            $this->storePaymentLinkTransaction(
                paymentLinkId: $paymentLinkId,
                shortUrl: $shortUrl,
                referenceId: (string) ($paymentLink['reference_id'] ?? $referenceId),
                user: $user,
                pet: $pet,
                data: $data,
                clinicId: $clinicId,
                amountPaise: $amountPaise,
                status: (string) ($paymentLink['status'] ?? 'created')
            );
        } catch (\Throwable $e) {
            Log::warning('receptionist.patient.payment_link_store_failed', [
                'payment_link_id' => $paymentLinkId,
                'user_id' => $user->id,
                'pet_id' => $pet?->id ?? null,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function storePaymentLinkTransaction(
        string $paymentLinkId,
        string $shortUrl,
        string $referenceId,
        User $user,
        ?object $pet,
        array $data,
        ?int $clinicId,
        int $amountPaise,
        string $status
    ): void {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        try {
            $transactionType = 'excell_export_campaign';
            $transactionStatus = $this->normalizePaymentLinkTransactionStatus($status);
            $payload = [
                'clinic_id' => $clinicId,
                'doctor_id' => !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null,
                'user_id' => $user->id,
                'pet_id' => $pet?->id,
                'amount_paise' => $amountPaise,
                'status' => $transactionStatus,
                'type' => $transactionType,
                'payment_method' => 'razorpay_payment_link',
                'reference' => $paymentLinkId,
                'metadata' => [
                    'order_type' => $transactionType,
                    'payment_provider' => 'razorpay',
                    'payment_flow' => 'payment_link',
                    'payment_link_id' => $paymentLinkId,
                    'payment_link_url' => $shortUrl ?: null,
                    'reference_id' => $referenceId ?: null,
                    'source' => 'receptionist_patients',
                    'clinic_id' => $clinicId,
                    'doctor_id' => !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null,
                    'user_id' => $user->id,
                    'pet_id' => $pet?->id,
                    'gateway_status' => $status,
                ],
            ];

            $payoutBreakup = $this->buildExcelExportPayoutBreakup($amountPaise);
            if ($payoutBreakup) {
                $payload['metadata']['payout_breakup'] = $payoutBreakup;
                foreach ([
                    'actual_amount_paid_by_consumer_paise',
                    'payment_to_snoutiq_paise',
                    'payment_to_doctor_paise',
                ] as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $payload[$column] = (int) $payoutBreakup[$column];
                    }
                }
            }

            Transaction::updateOrCreate(['reference' => $paymentLinkId], $payload);
        } catch (\Throwable $e) {
            Log::warning('receptionist.patient.payment_link_transaction_store_failed', [
                'payment_link_id' => $paymentLinkId,
                'user_id' => $user->id,
                'pet_id' => $pet?->id ?? null,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePaymentLinkTransactionStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        if ($normalized === '') {
            return 'pending';
        }

        if (in_array($normalized, ['paid', 'captured', 'success', 'successful'], true)) {
            return 'captured';
        }

        if (in_array($normalized, ['created', 'issued'], true)) {
            return 'pending';
        }

        return $normalized;
    }

    private function buildExcelExportPayoutBreakup(int $grossPaise): array
    {
        $grossPaise = max(0, $grossPaise);
        $amountBeforeGstPaise = (int) round($grossPaise / 1.18);
        $gstPaise = max(0, $grossPaise - $amountBeforeGstPaise);
        $doctorSharePaise = $this->resolveExcelDoctorSharePaise($amountBeforeGstPaise, $grossPaise);
        $snoutiqSharePaise = max(0, $amountBeforeGstPaise - $doctorSharePaise);

        return [
            'actual_amount_paid_by_consumer_paise' => $grossPaise,
            'gst_paise' => $gstPaise,
            'amount_after_gst_paise' => $amountBeforeGstPaise,
            'amount_before_gst_paise' => $amountBeforeGstPaise,
            'gst_deducted_from_amount' => true,
            'payment_to_snoutiq_paise' => $snoutiqSharePaise,
            'payment_to_doctor_paise' => $doctorSharePaise,
        ];
    }

    private function resolveExcelDoctorSharePaise(int $amountBeforeGstPaise, int $grossPaise): int
    {
        $amountBeforeGstPaise = max(0, $amountBeforeGstPaise);
        $grossPaise = max(0, $grossPaise);

        if (
            abs($amountBeforeGstPaise - 39900) <= 400
            || abs($grossPaise - 47100) <= 500
            || abs($grossPaise - 39900) <= 400
            || abs($amountBeforeGstPaise - 50000) <= 400
            || abs($grossPaise - 59000) <= 500
        ) {
            return min($amountBeforeGstPaise, 35000);
        }

        if (
            abs($amountBeforeGstPaise - 54900) <= 400
            || abs($grossPaise - 64800) <= 500
            || abs($grossPaise - 54900) <= 400
            || abs($amountBeforeGstPaise - 65000) <= 400
            || abs($grossPaise - 76700) <= 500
        ) {
            return min($amountBeforeGstPaise, 45000);
        }

        return min($amountBeforeGstPaise, 45000);
    }

    private function resolveTemplateDoctorName(array $data, ?int $clinicId): string
    {
        $doctorId = $data['doctor_id'] ?? null;
        $doctor = null;

        if ($doctorId) {
            $doctor = Doctor::query()
                ->when($clinicId, fn ($query) => $query->where('vet_registeration_id', $clinicId))
                ->find((int) $doctorId);
        }

        if (!$doctor && $clinicId) {
            $doctor = Doctor::query()
                ->where('vet_registeration_id', $clinicId)
                ->orderBy('id')
                ->first();
        }

        $name = trim((string) ($doctor?->doctor_name ?? 'Snoutiq'));
        $name = preg_replace('/^\s*dr\.?\s+/i', '', $name) ?: $name;

        return $name ?: 'Snoutiq';
    }

    private function normalizeWhatsAppPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (strlen($digits) === 10) {
            return '91'.$digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }

        return $digits;
    }

    private function extractRazorpayShortCode(string $shortUrl): string
    {
        $path = parse_url($shortUrl, PHP_URL_PATH);
        if (!is_string($path) || trim($path, '/') === '') {
            return '';
        }

        return basename(trim($path, '/'));
    }

    private function formatRupeesForTemplate(int $amountPaise): string
    {
        $amount = $amountPaise / 100;

        return floor($amount) === $amount
            ? (string) (int) $amount
            : number_format($amount, 2, '.', '');
    }

    private function resolvePetUploadFile(Request $request): ?UploadedFile
    {
        foreach (['pet_doc2', 'pet_doc1', 'pet_image', 'pet_pic'] as $candidate) {
            if ($request->hasFile($candidate)) {
                return $request->file($candidate);
            }
        }

        return null;
    }

    private function storePetDocument(?UploadedFile $file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $uploadPath = public_path('uploads/pet_docs');
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        $docName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
        $file->move($uploadPath, $docName);

        return 'backend/uploads/pet_docs/'.$docName;
    }

    private function petDoc2BlobColumnsReady(): bool
    {
        return Schema::hasTable('pets')
            && Schema::hasColumn('pets', 'pet_doc2_blob')
            && Schema::hasColumn('pets', 'pet_doc2_mime');
    }

    private function userPetDoc2BlobColumnsReady(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'pet_doc2_blob')
            && Schema::hasColumn('users', 'pet_doc2_mime');
    }

    private function absolutePetDocumentUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');
        $base = rtrim(url('/'), '/');

        if (str_starts_with($path, 'backend/') && str_ends_with($base, '/backend')) {
            $path = substr($path, strlen('backend/'));
        }

        return $base.'/'.$path;
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
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'notes' => 'nullable|string',
            'quoted_price' => 'nullable|numeric',
        ]);

        $hasLastVetIdColumn = Schema::hasColumn('users', 'last_vet_id');
        $patient = User::find($data['patient_id']);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        if ($hasLastVetIdColumn && (int) $patient->last_vet_id !== (int) $clinicId) {
            $patient->last_vet_id = $clinicId;
            $patient->save();
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
            $petName = $pet->name;
        } else {
            return response()->json(['success' => false, 'message' => 'Select a pet or provide pet details'], 422);
        }

        $scheduledFor = $this->combineSchedule(
            $data['scheduled_date'] ?? null,
            $data['scheduled_time'] ?? null
        );
        if (!$scheduledFor) {
            return response()->json(['success' => false, 'message' => 'scheduled_date and scheduled_time are required'], 422);
        }

        $appointmentDate = $data['scheduled_date'];
        $appointmentTime = $this->normalizeSlotTime($data['scheduled_time']) ?? $data['scheduled_time'];

        $now = now();
        $petName = $petName
            ?? DB::table('user_pets')->where('id', $petId)->value('name')
            ?? $data['pet_name']
            ?? null;

        $mobile = $patient->phone
            ?? DB::table('users')->where('id', $patient->id)->value('phone')
            ?? 'N/A';

        $appointmentId = DB::table('appointments')->insertGetId([
            'vet_registeration_id' => $clinicId,
            'doctor_id' => $doctorId,
            'name' => $patient->name ?? 'Patient',
            'mobile' => $mobile,
            'pet_name' => $petName,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'appointment_id' => $appointmentId,
            'message' => 'Appointment created successfully.',
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
