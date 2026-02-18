<?php 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
// use App
use App\Http\Controllers\Api\UnifiedIntelligenceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleLoginController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\Groomer\ProfileController as GroomerProfileController;
use App\Http\Controllers\Api\Groomer\ServiceCategoryController as GroomerServiceCategoryController;
use App\Http\Controllers\Api\Groomer\ServiceController as GroomerServiceController;
use App\Http\Controllers\Api\Groomer\GroomerEmployeeController as GroomerEmployeeController;
use App\Http\Controllers\Api\Groomer\CalenderController as GroomerCalenderController;
use App\Http\Controllers\Api\Groomer\ClientController as GroomerClientController;
use App\Http\Controllers\Api\Groomer\ClinicReelController;
use App\Http\Controllers\Api\Groomer\MarketingController as GroomerMarketingController;
use App\Http\Controllers\Api\Groomer\DashboardController;
use App\Http\Controllers\Api\EmergencyController;
use App\Http\Controllers\Api\VetRegisterationTempController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RazorpayController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\UserAiController;
use App\Http\Controllers\Api\UserFeedbackController;
use App\Http\Controllers\Api\UserObservationController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\SalesDashboardController;
use App\Http\Controllers\Api\AppointmentSubmissionController;
use App\Http\Controllers\Api\DashboardProfileController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\ClinicServicePresetController;
use App\Http\Controllers\Api\VetRegistrationReportController;
use App\Http\Controllers\Api\VaccinationBookingController;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use App\Models\Pet;
use App\Models\UserObservation;
use App\Models\Transaction;
use App\Models\Otp;
use App\Support\DeviceTokenOwnerResolver;
use App\Http\Controllers\Auth\ForgotPasswordSimpleController;
use App\Services\WhatsAppService;

use App\Http\Controllers\AdminController;
// use App\Http\Controllers\CallController;
// routes/api.php
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AgoraController;
use App\Http\Controllers\Api\CallController as ApiCallController; // handles lightweight requestCall
use App\Http\Controllers\CallController as CoreCallController;    // handles sessions + token
use App\Http\Controllers\Api\CallRecordingController;
use App\Http\Controllers\Api\RecordingUploadController;
use App\Http\Controllers\Api\RealtimeController;
use App\Http\Controllers\Api\CallController as NewCallController;
use App\Http\Controllers\Api\CsvUploadController;
use App\Http\Controllers\Api\DocumentUploadController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\ReceptionistBookingController;
use App\Http\Controllers\Api\PetVaccinationRecordController;
use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\SocketServerController;
use App\Http\Controllers\Api\WhatsAppMessageController;
use App\Http\Controllers\Api\CallSessionCrudController;
use App\Http\Controllers\Api\V1\OtpController as V1OtpController;
use App\Http\Controllers\Api\V1\VaccinationController as V1VaccinationController;
use App\Http\Controllers\Api\V1\VaccinationDoctorController as V1VaccinationDoctorController;
use App\Http\Controllers\Api\V1\VaccinationSlotController as V1VaccinationSlotController;
use App\Http\Controllers\Api\V1\VaccinationBookingController as V1VaccinationBookingController;
use App\Http\Controllers\Api\V1\VaccinationPaymentController as V1VaccinationPaymentController;
use App\Http\Controllers\Api\ClinicFinancialsController;
use App\Http\Controllers\Api\PetConsultTimelineController;

Route::post('/call/request', [ApiCallController::class, 'requestCall']);
Route::post('/call/test', [ApiCallController::class, 'requestTestCall']);
Route::get('/call-sessions', [CallSessionCrudController::class, 'index']);
Route::post('/call-sessions', [CallSessionCrudController::class, 'store']);
Route::get('/call-sessions/{callSession}', [CallSessionCrudController::class, 'show'])->whereNumber('callSession');
Route::post('/call-recordings/upload', [RecordingUploadController::class, 'store']);
Route::post('/csv/upload', [CsvUploadController::class, 'store']);
Route::post('/error-logs', [ErrorLogController::class, 'store'])->name('api.error-logs.store');
Route::post('/documents/upload', [DocumentUploadController::class, 'store'])->name('api.documents.upload');
Route::get('/users/{userId}/document-uploads', [DocumentUploadController::class, 'index'])->name('api.documents.index');
Route::post('/whatsapp/send', [WhatsAppMessageController::class, 'send']);
Route::post('/whatsapp/temp-send', [WhatsAppMessageController::class, 'tempSend']);
Route::post('/whatsapp/template-test', [WhatsAppMessageController::class, 'templateTest']);
Route::post('/whatsapp/broadcast/users', [WhatsAppMessageController::class, 'broadcastToUsers']);
Route::post('/whatsapp/send/new-year', [WhatsAppMessageController::class, 'sendNewYearTemplate']);
Route::post('/whatsapp/broadcast/new-year', [WhatsAppMessageController::class, 'broadcastNewYearTemplate']);
Route::post('/whatsapp/vet-opened-case', [WhatsAppMessageController::class, 'vetOpenedCase']);
Route::post('/whatsapp/vet-video-consult-test', [WhatsAppMessageController::class, 'vetVideoConsultTest']);

Route::prefix('socket')->group(function () {
    Route::get('/health', [SocketServerController::class, 'health']);
    Route::get('/active-doctors', [SocketServerController::class, 'activeDoctors']);
    Route::post('/call-sessions', [SocketServerController::class, 'storeCallSession']);
    Route::get('/call-sessions', [SocketServerController::class, 'getCallSession']);
});

Route::post('/realtime/heartbeat', [RealtimeController::class, 'heartbeat']);

Route::prefix('calls')->group(function () {
    Route::post('/request', [NewCallController::class, 'request']);
    Route::post('/{call}/accept', [NewCallController::class, 'accept']);
    Route::post('/{call}/reject', [NewCallController::class, 'reject']);
    Route::post('/{call}/end', [NewCallController::class, 'end']);
    Route::post('/{call}/cancel', [NewCallController::class, 'cancel']);
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/otp/request', [V1OtpController::class, 'request']);
    Route::post('/auth/otp/verify', [V1OtpController::class, 'verify']);

    Route::middleware('auth.api_token')->group(function () {
        Route::get('/vaccinations', [V1VaccinationController::class, 'index']);
        Route::get('/doctors', [V1VaccinationDoctorController::class, 'index']);
        Route::get('/cities', [V1VaccinationDoctorController::class, 'cities']);
        Route::get('/slots', [V1VaccinationSlotController::class, 'index']);
        Route::post('/bookings', [V1VaccinationBookingController::class, 'store']);
        Route::get('/bookings/{bookingId}', [V1VaccinationBookingController::class, 'show']);
        Route::post('/bookings/{bookingId}/cancel', [V1VaccinationBookingController::class, 'cancel']);
        Route::post('/payments/init', [V1VaccinationPaymentController::class, 'init']);
    });

    Route::post('/payments/webhook', [V1VaccinationPaymentController::class, 'webhook']);
});

use App\Http\Controllers\Api\VetController;
use App\Http\Controllers\Api\AdminOnboardingStatusController;
use App\Http\Controllers\Api\VetLeadController;
use App\Http\Controllers\Api\ClinicDetailsController;
// (imports already declared above)

Route::get('/vets', [VetController::class, 'index']);        // All vets
Route::get('/vets/by-referral/{code}', [VetController::class, 'showByReferral'])->name('api.vets.by-referral');
Route::get('/vets/{id}', [VetController::class, 'show']);    // Single vet
Route::delete('/vets/{id}', [VetController::class, 'destroy']); // Delete vet
Route::post('/vet-leads', [VetLeadController::class, 'store']);

// Clinic profile + doctors
Route::get('/clinics/{clinic}/details', [ClinicDetailsController::class, 'show'])
    ->whereNumber('clinic')
    ->name('api.clinics.details');
Route::get('/clinics/details', [ClinicDetailsController::class, 'show'])
    ->name('api.clinics.details.query');

Route::prefix('admin/onboarding')->group(function () {
    Route::get('/services', [AdminOnboardingStatusController::class, 'services']);
    Route::get('/video', [AdminOnboardingStatusController::class, 'video']);
    Route::get('/clinic-hours', [AdminOnboardingStatusController::class, 'clinicHours']);
    Route::get('/emergency', [AdminOnboardingStatusController::class, 'emergency']);
});

Route::get('/admin/vet-registrations/report', [VetRegistrationReportController::class, 'summary'])
    ->name('api.admin.vet-registrations.report');
// Public alias for the same report (no admin guard)
Route::get('/vet-registrations/report', [VetRegistrationReportController::class, 'summary'])
    ->name('api.vet-registrations.report.public');

// Financials (clinic dashboard KPIs + charts + transactions)
Route::get('/financials', [ClinicFinancialsController::class, 'show'])->name('api.clinic.financials');
Route::get('/pets/consult-timeline', [PetConsultTimelineController::class, 'index'])->name('api.pets.consult-timeline');


Route::get('/agora/appid', function () {
    return response()->json([
        'appId' => trim(env('AGORA_APP_ID')),
    ]);
});

Route::get('/device-tokens/issue', function (Request $request) {
    $data = $request->validate([
        'user_id' => ['required', 'integer'],
        'device_id' => ['nullable', 'string', 'max:255'],
    ]);

    $query = DeviceToken::query()
        ->where('user_id', $data['user_id']);

    if (!empty($data['device_id'])) {
        $query->where('device_id', $data['device_id']);
    }

    $records = $query
        ->orderByDesc('last_seen_at')
        ->orderByDesc('id')
        ->get();

    if ($records->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No device tokens found for the provided user_id.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'tokens' => $records->map(function (DeviceToken $record) {
                return [
                    'id' => $record->id,
                    'token' => $record->token,
                    'user_id' => $record->user_id,
                    'owner_model' => $record->meta['owner_model'] ?? null,
                    'platform' => $record->platform,
                    'device_id' => $record->device_id,
                    'meta' => $record->meta,
                    'last_seen_at' => $record->last_seen_at?->toIso8601String(),
                    'created_at' => $record->created_at?->toIso8601String(),
                    'updated_at' => $record->updated_at?->toIso8601String(),
                ];
            }),
        ],
    ]);
})->name('api.device-tokens.issue');

// Simple doctor profile read/update via query param doctor_id (no auth)
Route::get('/doctor/profile', function (Request $request) {
    $doctorId = $request->query('doctor_id');
    if (!$doctorId) {
        return response()->json(['message' => 'doctor_id is required'], 422);
    }

    $doctor = Doctor::find($doctorId);
    if (!$doctor) {
        return response()->json(['message' => 'Doctor not found'], 404);
    }

    $clinic = null;
    if (!empty($doctor->vet_registeration_id)) {
        $clinicRow = VetRegisterationTemp::find($doctor->vet_registeration_id);
        if ($clinicRow) {
            $clinic = [
                'id' => $clinicRow->id,
                'name' => $clinicRow->name ?? null,
                'slug' => $clinicRow->slug ?? null,
                'email' => $clinicRow->email ?? null,
                'mobile' => $clinicRow->mobile ?? null,
                'address' => $clinicRow->address ?? null,
                'city' => $clinicRow->city ?? null,
                'pincode' => $clinicRow->pincode ?? null,
                'image' => $clinicRow->image ?? null,
                'clinic_profile' => $clinicRow->clinic_profile ?? null,
                'hospital_profile' => $clinicRow->hospital_profile ?? null,
            ];
        }
    }

    return response()->json([
        'data' => [
            'id' => $doctor->id,
            'doctor_name' => $doctor->doctor_name,
            'doctor_email' => $doctor->doctor_email,
            'doctor_mobile' => $doctor->doctor_mobile,
            'doctor_license' => $doctor->doctor_license,
            'doctor_image' => $doctor->doctor_image,
            'doctor_document' => $doctor->doctor_document,
            'toggle_availability' => $doctor->toggle_availability,
            'doctors_price' => $doctor->doctors_price,
            'vet_registeration_id' => $doctor->vet_registeration_id,
            'staff_role' => $doctor->staff_role,
            'clinic' => $clinic,
        ],
    ]);
});

// Exported-from-excel vets with doctors
Route::get('/doctors/{doctor}/blob-image', function (Doctor $doctor) {
    if (empty($doctor->doctor_image_blob)) {
        return response()->json([
            'success' => false,
            'message' => 'Doctor blob image not found.',
        ], 404);
    }

    return response($doctor->doctor_image_blob, 200, [
        'Content-Type' => $doctor->doctor_image_mime ?: 'image/jpeg',
        'Content-Disposition' => 'inline; filename="doctor-' . $doctor->id . '.jpg"',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->whereNumber('doctor')->name('api.doctors.blob-image');

Route::get('/exported_from_excell_doctors', function () {
    $vets = VetRegisterationTemp::query()
        ->where('exported_from_excell', 1)
        ->with(['doctors' => function ($q) {
            $q->where('exported_from_excell', 1);
        }])
        ->get([
            'id',
            'name',
            'email',
            'mobile',
            'exported_from_excell',
        ]);

    $baseAppUrl = rtrim((string) config('app.url'), '/');
    if ($baseAppUrl === '') {
        $baseAppUrl = rtrim(url('/'), '/');
    }

    $data = $vets->map(function (VetRegisterationTemp $vet) use ($baseAppUrl) {
        $payload = $vet->toArray();
        $payload['doctors'] = $vet->doctors->map(function (Doctor $doctor) use ($baseAppUrl) {
            $doctorPayload = $doctor->toArray();

            $imagePath = ltrim((string) ($doctor->doctor_image ?? ''), '/');
            $doctorPayload['doctor_image_url'] = $imagePath !== ''
                ? $baseAppUrl . '/' . $imagePath
                : null;

            $doctorPayload['doctor_image_blob_url'] = !empty($doctor->doctor_image_blob)
                ? route('api.doctors.blob-image', ['doctor' => $doctor->id])
                : null;

            return $doctorPayload;
        })->values();

        return $payload;
    })->values();

    return response()->json([
        'success' => true,
        'data' => $data,
    ]);
})->name('exported_from_excell_doctors');

// Bulk update video rates for doctors imported from excel
Route::match(['get', 'post'], '/excell-export/doctors/update-video-rates', function (Request $request) {
    $configuredSecret = trim((string) (config('services.notifications.secret') ?? ''));
    if ($configuredSecret !== '') {
        $incomingSecret = trim((string) ($request->header('X-Admin-Secret') ?? $request->input('secret') ?? ''));
        if (!hash_equals($configuredSecret, $incomingSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
    }

    if (!Schema::hasTable('doctors')) {
        return response()->json([
            'success' => false,
            'message' => 'doctors table not found',
        ], 500);
    }
    if (!Schema::hasColumn('doctors', 'exported_from_excell')) {
        return response()->json([
            'success' => false,
            'message' => 'doctors.exported_from_excell column not found',
        ], 500);
    }
    if (!Schema::hasColumn('doctors', 'video_day_rate') || !Schema::hasColumn('doctors', 'video_night_rate')) {
        return response()->json([
            'success' => false,
            'message' => 'Rate columns missing on doctors table',
        ], 500);
    }

    $payload = $request->validate([
        'day_rate' => ['nullable', 'numeric', 'min:0'],
        'night_rate' => ['nullable', 'numeric', 'min:0'],
        'dry_run' => ['nullable', 'boolean'],
    ]);

    $dayRate = (float) ($payload['day_rate'] ?? 500);
    $nightRate = (float) ($payload['night_rate'] ?? 650);
    $dryRun = filter_var($payload['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $query = Doctor::query()
        ->where(function ($q) {
            $q->where('exported_from_excell', 1)
                ->orWhere('exported_from_excell', '1');
        })
        ->orderBy('id');

    $matched = (clone $query)->count();
    if ($matched === 0) {
        return response()->json([
            'success' => true,
            'message' => 'No doctors found with exported_from_excell = 1',
            'matched' => 0,
            'updated' => 0,
            'day_rate' => $dayRate,
            'night_rate' => $nightRate,
            'dry_run' => $dryRun,
        ]);
    }

    $updated = 0;
    $processed = 0;
    $sample = [];

    $query->chunkById(200, function ($rows) use (&$updated, &$processed, &$sample, $dayRate, $nightRate, $dryRun) {
        foreach ($rows as $doctor) {
            $processed++;
            if (count($sample) < 10) {
                $sample[] = [
                    'id' => $doctor->id,
                    'doctor_name' => $doctor->doctor_name,
                    'old_day_rate' => $doctor->video_day_rate,
                    'old_night_rate' => $doctor->video_night_rate,
                ];
            }

            if ($dryRun) {
                continue;
            }

            $doctor->video_day_rate = $dayRate;
            $doctor->video_night_rate = $nightRate;
            $doctor->save();
            $updated++;
        }
    });

    return response()->json([
        'success' => true,
        'message' => $dryRun ? 'Dry run complete' : 'Video rates updated successfully',
        'matched' => $matched,
        'processed' => $processed,
        'updated' => $dryRun ? 0 : $updated,
        'day_rate' => $dayRate,
        'night_rate' => $nightRate,
        'dry_run' => $dryRun,
        'sample' => $sample,
    ]);
})->name('excell_export.doctors.update_video_rates');

// Create user + pet + observation
Route::post('/user-pet-observation', function (Request $request) {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:20'],
        'breed' => ['required', 'string', 'max:255'],
        'dob' => ['nullable', 'date'],
        'type' => ['required', 'string', 'max:100'],
        'gender' => ['nullable', 'string', 'max:50'],
        'pet_name' => ['nullable', 'string', 'max:255'],
        'reported_symptom' => ['nullable', 'string'],
        'pet_doc2' => ['nullable', 'string'],
        'file' => ['nullable', 'file', 'max:10240'], // optional upload (10 MB)
        'appetite' => ['nullable', 'string'],
        'enery' => ['nullable', 'string'], // input typo handled
        'energy' => ['nullable', 'string'],
        'mood' => ['nullable', 'string'],
        'is_neutered' => ['nullable', 'boolean'],
        'vaccenated_yes_no' => ['nullable', 'boolean'],
        'vaccinated_yes_no' => ['nullable', 'boolean'],
    ]);

    $uploadedFile = $request->file('file');
    $petDoc2BlobColumnsReady = Schema::hasTable('pets')
        && Schema::hasColumn('pets', 'pet_doc2_blob')
        && Schema::hasColumn('pets', 'pet_doc2_mime');

    if ($uploadedFile && ! $petDoc2BlobColumnsReady) {
        return response()->json([
            'success' => false,
            'message' => 'pet_doc2 blob columns are missing. Please run migrations.',
        ], 500);
    }

    $result = DB::transaction(function () use ($data, $uploadedFile, $petDoc2BlobColumnsReady) {
        $phoneDigits = preg_replace('/\\D+/', '', $data['phone']);

        // Find existing user by phone (normalized) or exact match
        $existingUser = User::query()
            ->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$phoneDigits])
            ->orWhere('phone', $data['phone'])
            ->first();

        if ($existingUser) {
            $user = $existingUser;
            // Update basic fields, keep existing email unless missing
            $user->name = $data['name'];
            $user->phone = $data['phone'];
            if (empty($user->email)) {
                $user->email = 'pp_'.$phoneDigits.'@snoutiq.local';
            }
            $user->role = 'pet_parent';
            $user->save();

            // Replace existing pets with a fresh record
            $existingPetIds = $user->pets()->pluck('id');
            if ($existingPetIds->isNotEmpty()) {
                UserObservation::whereIn('pet_id', $existingPetIds)->delete();
                $user->pets()->delete();
            }
        } else {
            $baseEmail = 'pp_'.$phoneDigits.'@snoutiq.local';
            $email = $baseEmail;
            $suffix = 1;
            while (User::where('email', $email)->exists()) {
                $email = 'pp_'.$phoneDigits.'_'.$suffix.'@snoutiq.local';
                $suffix++;
            }

            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $email,
                'role' => 'pet_parent',
                'password' => Hash::make('123456'),
            ]);
        }

        $pet = new Pet();
        $pet->user_id = $user->id;
        $pet->name = $data['pet_name'] ?? ($data['name']."'s pet");
        $pet->breed = $data['breed'];
        $pet->reported_symptom = $data['reported_symptom'] ?? null;
        $pet->pet_doc2 = $data['pet_doc2'] ?? null;

        if (Schema::hasColumn('pets', 'pet_type')) {
            $pet->pet_type = $data['type'];
        }
        if (Schema::hasColumn('pets', 'type')) {
            $pet->type = $data['type'];
        }
        if (!empty($data['gender'])) {
            if (Schema::hasColumn('pets', 'pet_gender')) {
                $pet->pet_gender = $data['gender'];
            }
            if (Schema::hasColumn('pets', 'gender')) {
                $pet->gender = $data['gender'];
            }
        }
        if (!empty($data['dob'])) {
            if (Schema::hasColumn('pets', 'pet_dob')) {
                $pet->pet_dob = $data['dob'];
            }
            if (Schema::hasColumn('pets', 'dob')) {
                $pet->dob = $data['dob'];
            }
        }
        if (array_key_exists('is_neutered', $data) && Schema::hasColumn('pets', 'is_neutered')) {
            $pet->is_neutered = (int) ((bool) $data['is_neutered']);
        }
        $vaccinatedYesNo = $data['vaccenated_yes_no'] ?? $data['vaccinated_yes_no'] ?? null;
        if ($vaccinatedYesNo !== null && Schema::hasColumn('pets', 'vaccenated_yes_no')) {
            $pet->vaccenated_yes_no = (int) ((bool) $vaccinatedYesNo);
        }

        // Handle optional file upload and set pet_doc2
        if ($uploadedFile) {
            $file = $uploadedFile;
            if ($file->isValid()) {
                $storedPath = $file->store('pet-docs', 'public');
                $publicBase = rtrim((string) config('app.url'), '/');
                if (! str_ends_with($publicBase, '/backend')) {
                    $publicBase .= '/backend';
                }
                $publicUrl = $publicBase.'/'.ltrim($storedPath, '/');
                $pet->pet_doc2 = $publicUrl;

                if ($petDoc2BlobColumnsReady) {
                    $pet->pet_doc2_blob = $file->get();
                    $pet->pet_doc2_mime = $file->getMimeType() ?: ($file->getClientMimeType() ?: 'application/octet-stream');
                }
            }
        }

        $pet->save();

        $petDoc2BlobUrl = null;
        if ($petDoc2BlobColumnsReady) {
            $blob = $pet->getRawOriginal('pet_doc2_blob');
            if ($blob !== null && $blob !== '') {
                $petDoc2BlobUrl = route('api.pets.pet-doc2-blob', ['pet' => $pet->id], true);
            }
        }

        $petPayload = $pet->only([
            'id',
            'user_id',
            'name',
            'breed',
            'pet_type',
            'type',
            'pet_gender',
            'gender',
            'pet_dob',
            'dob',
            'reported_symptom',
            'pet_doc2',
        ]);
        $petPayload['pet_doc2_blob_url'] = $petDoc2BlobUrl;
        if (Schema::hasColumn('pets', 'is_neutered')) {
            $petPayload['is_neutered'] = $pet->is_neutered;
        }
        if (Schema::hasColumn('pets', 'vaccenated_yes_no')) {
            $petPayload['vaccenated_yes_no'] = $pet->vaccenated_yes_no;
        }

        $observation = new UserObservation();
        $observation->user_id = $user->id;
        $observation->pet_id = $pet->id;
        $observation->appetite = $data['appetite'] ?? null;
        $observation->energy = $data['energy'] ?? ($data['enery'] ?? null);
        $observation->mood = $data['mood'] ?? null;
        $observation->observed_at = now();
        $observation->save();

        return [
            'user' => $user->only(['id', 'name', 'phone', 'email']),
            'pet' => $petPayload,
            'observation' => $observation->only(['id', 'user_id', 'pet_id', 'appetite', 'energy', 'mood', 'observed_at']),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $result,
    ], 201);
})->name('user_pet_observation.store');

// Upload a pet document and optionally attach to pet_doc2
Route::post('/pet-doc/upload', function (Request $request) {
    $data = $request->validate([
        'file' => ['required', 'file', 'max:10240'], // 10 MB
        'pet_id' => ['required', 'integer', 'exists:pets,id'],
        'user_id' => ['nullable', 'integer', 'exists:users,id'],
    ]);

    $file = $request->file('file');
    if (! $file->isValid()) {
        return response()->json([
            'success' => false,
            'message' => $file->getErrorMessage() ?: 'Invalid upload.',
        ], 422);
    }

    $storedPath = $file->store('pet-docs', 'public');
    if (! $storedPath) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to store file.',
        ], 500);
    }

    $publicBase = rtrim((string) config('app.url'), '/');
    if (! str_ends_with($publicBase, '/backend')) {
        $publicBase .= '/backend';
    }
    $publicUrl = $publicBase.'/'.ltrim($storedPath, '/');

    // Attach to pet_doc2
    $pet = Pet::find($data['pet_id']);
    if ($pet) {
        $pet->pet_doc2 = $publicUrl;
        $pet->save();
    }

    return response()->json([
        'success' => true,
        'data' => [
            'pet_id' => $pet?->id,
            'pet_doc2' => $pet?->pet_doc2,
            'stored_path' => $storedPath,
            'url' => Storage::disk('public')->url($storedPath),
            'public_url' => $publicUrl,
        ],
    ], 201);
})->name('pet_doc.upload');

// Upload vet profile photo and return public URL
Route::post('/vet-photo/upload', function (Request $request) {
    $data = $request->validate([
        'file' => ['required', 'file', 'image', 'max:5120'], // 5 MB
    ]);

    $file = $request->file('file');
    if (! $file->isValid()) {
        return response()->json([
            'success' => false,
            'message' => $file->getErrorMessage() ?: 'Invalid upload.',
        ], 422);
    }

    $storedPath = $file->store('vet-photos', 'public');
    if (! $storedPath) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to store file.',
        ], 500);
    }

    $publicBase = rtrim((string) config('app.url'), '/');
    if (! str_ends_with($publicBase, '/backend')) {
        $publicBase .= '/backend';
    }
    $publicUrl = $publicBase.'/'.ltrim($storedPath, '/');

    return response()->json([
        'success' => true,
        'data' => [
            'stored_path' => $storedPath,
            'url' => Storage::disk('public')->url($storedPath),
            'public_url' => $publicUrl,
        ],
    ], 201);
})->name('vet_photo.upload');

// Doctor OTP: send
Route::post('/doctor/otp/request', function (Request $request, WhatsAppService $whatsApp) {
    $payload = $request->validate([
        'phone' => ['required', 'string'],
    ]);

    $phone = preg_replace('/\\D+/', '', $payload['phone']);
    if (! $phone) {
        return response()->json(['success' => false, 'message' => 'Invalid phone'], 422);
    }

    $doctor = Doctor::whereRaw("REGEXP_REPLACE(doctor_mobile, '[^0-9]', '') = ?", [$phone])->first()
        ?: Doctor::where('doctor_mobile', $payload['phone'])->first();
    if (! $doctor) {
        return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
    }

    $otp = (string) random_int(100000, 999999);
    $token = (string) \Illuminate\Support\Str::uuid();
    $expiresAt = now()->addMinutes(10);

    if ($whatsApp->isConfigured()) {
        try {
            $whatsApp->sendOtpTemplate($phone, $otp);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Unable to send OTP'], 503);
        }
    }

    Otp::create([
        'token' => $token,
        'type' => 'whatsapp',
        'value' => $phone,
        'otp' => $otp,
        'expires_at' => $expiresAt,
        'is_verified' => 0,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'OTP sent',
        'request_id' => $token,
        'expires_in' => 600,
        'otp' => config('app.debug') ? $otp : 'hidden',
    ]);
})->name('doctor.otp.request');

// Doctor OTP: verify
Route::post('/doctor/otp/verify', function (Request $request) {
    $payload = $request->validate([
        'phone' => ['required', 'string'],
        'otp' => ['required', 'string'],
        'request_id' => ['nullable', 'string'],
    ]);

    $phone = preg_replace('/\\D+/', '', $payload['phone']);
    if (! $phone) {
        return response()->json(['success' => false, 'message' => 'Invalid phone'], 422);
    }

    $doctor = Doctor::whereRaw("REGEXP_REPLACE(doctor_mobile, '[^0-9]', '') = ?", [$phone])->first()
        ?: Doctor::where('doctor_mobile', $payload['phone'])->first();
    if (! $doctor) {
        return response()->json(['success' => false, 'message' => 'Doctor not found'], 404);
    }

    $otpQuery = Otp::query()
        ->where('type', 'whatsapp')
        ->where('value', $phone)
        ->where('otp', $payload['otp'])
        ->where('expires_at', '>', now());

    if (! empty($payload['request_id'])) {
        $otpQuery->where('token', $payload['request_id']);
    }

    $otpEntry = $otpQuery->latest()->first();
    if (! $otpEntry) {
        return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 401);
    }

    $otpEntry->update(['is_verified' => 1]);

    return response()->json([
        'success' => true,
        'message' => 'OTP verified',
        'doctor_id' => $doctor->id,
        'doctor' => $doctor->only(['id', 'doctor_name', 'doctor_email', 'doctor_mobile']),
    ]);
})->name('doctor.otp.verify');

// Transactions count for excell export campaign by doctor & clinic
Route::get('/excell-export/transactions', function (Request $request) {
    $data = $request->validate([
        'doctor_id' => ['required', 'integer'],
        'clinic_id' => ['required', 'integer'],
    ]);

    $query = Transaction::query()
        ->where('doctor_id', $data['doctor_id'])
        ->where('clinic_id', $data['clinic_id'])
        ->where(function ($q) {
            if (Schema::hasColumn('transactions', 'type')) {
                $q->where('type', 'excell_export_campaign');
            }
            $q->orWhere('metadata->order_type', 'excell_export_campaign');
        })
        ->selectRaw('count(*) as total, coalesce(sum(amount_paise),0) as total_paise');

    $row = $query->first();

    $deductionRate = 0.25;
    $count = (int) ($row->total ?? 0);
    $totalPaise = (int) ($row->total_paise ?? 0);
    $totalPaiseAfterDeduction = (int) max(round($totalPaise * (1 - $deductionRate)), 0);

    // Detailed list
    $transactions = Transaction::query()
        ->where('doctor_id', $data['doctor_id'])
        ->where('clinic_id', $data['clinic_id'])
        ->where(function ($q) {
            if (Schema::hasColumn('transactions', 'type')) {
                $q->where('type', 'excell_export_campaign');
            }
            $q->orWhere('metadata->order_type', 'excell_export_campaign');
        })
        ->with([
            'user:id,name,phone,email',
            'doctor:id,doctor_name,doctor_email,doctor_mobile',
            'pet' => function ($q) {
                $cols = ['id', 'user_id', 'name', 'breed'];
                if (Schema::hasColumn('pets', 'pet_type')) { $cols[] = 'pet_type'; }
                if (Schema::hasColumn('pets', 'type')) { $cols[] = 'type'; }
                if (Schema::hasColumn('pets', 'pet_dob')) { $cols[] = 'pet_dob'; }
                if (Schema::hasColumn('pets', 'dob')) { $cols[] = 'dob'; }
                if (Schema::hasColumn('pets', 'reported_symptom')) { $cols[] = 'reported_symptom'; }
                if (Schema::hasColumn('pets', 'pet_doc1')) { $cols[] = 'pet_doc1'; }
                if (Schema::hasColumn('pets', 'pet_doc2')) { $cols[] = 'pet_doc2'; }
                $q->select($cols);
            },
        ])
        ->orderByDesc('id')
        ->limit(200)
        ->get()
        ->map(function (Transaction $t) {
            $grossPaise = (int) ($t->amount_paise ?? 0);
            $netPaise = (int) max(round($grossPaise * 0.75), 0);
            return [
                'id' => $t->id,
                'reference' => $t->reference,
                'status' => $t->status,
                'amount_paise' => $grossPaise,
                // Keep amount_inr net so existing frontends auto-show post-deduction value.
                'amount_inr' => $netPaise / 100,
                'gross_amount_inr' => $grossPaise / 100,
                'amount_after_deduction_paise' => $netPaise,
                'amount_after_deduction_inr' => $netPaise / 100,
                'net_amount_inr' => $netPaise / 100,
                'payment_method' => $t->payment_method,
                'type' => $t->type ?? ($t->metadata['order_type'] ?? null),
                'metadata' => $t->metadata,
                'created_at' => optional($t->created_at)->toIso8601String(),
                'updated_at' => optional($t->updated_at)->toIso8601String(),
                'user' => $t->user,
                'pet' => $t->pet,
                'doctor' => $t->doctor,
            ];
        });

    return response()->json([
        'success' => true,
        'doctor_id' => $data['doctor_id'],
        'clinic_id' => $data['clinic_id'],
        'order_type' => 'excell_export_campaign',
        'deduction_rate' => $deductionRate,
        'total_transactions' => $count,
        'total_amount_paise' => $totalPaise,
        // Keep total_amount_inr net so existing frontends auto-show post-deduction value.
        'total_amount_inr' => $totalPaiseAfterDeduction / 100,
        'gross_total_amount_inr' => $totalPaise / 100,
        'total_amount_after_deduction_paise' => $totalPaiseAfterDeduction,
        'total_amount_after_deduction_inr' => $totalPaiseAfterDeduction / 100,
        'transactions' => $transactions,
    ]);
})->name('excell_export.transactions');

// Create vet + doctor for excel export campaign (standalone API)
Route::post('/excell-export/import', function (Request $request) {
    $data = $request->validate([
        // Vet fields
        'vet_name' => ['required', 'string', 'max:255'],
        'vet_email' => ['nullable', 'email', 'max:255'],
        'vet_mobile' => ['nullable', 'string', 'max:30'],
        'vet_city' => ['nullable', 'string', 'max:150'],

        // Doctor fields
        'doctor_name' => ['required', 'string', 'max:255'],
        'doctor_email' => ['nullable', 'email', 'max:255'],
        'doctor_mobile' => ['nullable', 'string', 'max:30'],
        'doctor_license' => ['nullable', 'string', 'max:255'],
        'doctor_image' => ['nullable', 'string', 'max:500'],
        'doctor_image_file' => ['nullable', 'file', 'image', 'max:5120'],
        'doctor_image_base64' => ['nullable', 'string'],
        'bio' => ['nullable', 'string', 'max:5000'],
        'degree' => ['nullable'],
        'degree.*' => ['nullable', 'string', 'max:255'],
        'years_of_experience' => ['nullable', 'string', 'max:50'],
        'specialization_select_all_that_apply' => ['nullable', 'array'],
        'specialization_select_all_that_apply.*' => ['nullable', 'string'],
        'languages_spoken' => ['nullable', 'array'],
        'languages_spoken.*' => ['nullable', 'string', 'max:255'],
        'response_time_for_online_consults_day' => ['nullable', 'string', 'max:255'],
        'response_time_for_online_consults_night' => ['nullable', 'string', 'max:255'],
        'break_do_not_disturb_time_example_2_4_pm' => ['nullable', 'array'],
        'break_do_not_disturb_time_example_2_4_pm.*' => ['nullable', 'string', 'max:255'],
        'do_you_offer_a_free_follow_up_within_3_days_after_a_consulta' => ['nullable', 'string', 'max:255'],
        'commission_and_agreement' => ['nullable', 'string', 'max:255'],
        'video_day_rate' => ['nullable', 'numeric'],
        'video_night_rate' => ['nullable', 'numeric'],
    ]);

    $doctorImageUrl = $data['doctor_image'] ?? null;
    $doctorImageBlob = null;
    $doctorImageMime = null;

    $extractBlobFromDataUri = static function (?string $value): array {
        if (!$value || !str_starts_with($value, 'data:image')) {
            return [null, null];
        }

        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.*)$/s', $value, $matches)) {
            return [null, null];
        }

        $mime = strtolower(trim($matches[1]));
        $rawBase64 = str_replace(' ', '+', $matches[2]);
        $binary = base64_decode($rawBase64, true);

        if ($binary === false) {
            return [null, null];
        }

        return [$binary, $mime];
    };

    if ($request->hasFile('doctor_image_file')) {
        $file = $request->file('doctor_image_file');
        if (! $file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => $file->getErrorMessage() ?: 'Invalid upload.',
            ], 422);
        }

        $doctorImageBlob = $file->get();
        $doctorImageMime = $file->getMimeType() ?: ($file->getClientMimeType() ?: 'image/jpeg');

        $storedPath = $file->store('vet-photos', 'public');
        if (! $storedPath) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to store file.',
            ], 500);
        }

        $publicBase = rtrim((string) config('app.url'), '/');
        if (! str_ends_with($publicBase, '/backend')) {
            $publicBase .= '/backend';
        }
        $doctorImageUrl = $publicBase.'/'.ltrim($storedPath, '/');
    } elseif (!empty($data['doctor_image_base64'])) {
        [$doctorImageBlob, $doctorImageMime] = $extractBlobFromDataUri((string) $data['doctor_image_base64']);
        if (!$doctorImageBlob || !$doctorImageMime) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid doctor_image_base64 data URI.',
            ], 422);
        }
    } elseif (is_string($doctorImageUrl) && str_starts_with($doctorImageUrl, 'data:image')) {
        [$doctorImageBlob, $doctorImageMime] = $extractBlobFromDataUri($doctorImageUrl);
        if (!$doctorImageBlob || !$doctorImageMime) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid doctor_image data URI.',
            ], 422);
        }
        // Avoid storing data URI string in the doctor_image path column.
        $doctorImageUrl = null;
    }

    $result = DB::transaction(function () use ($data, $doctorImageUrl, $doctorImageBlob, $doctorImageMime) {
        $vet = new VetRegisterationTemp();
        $vet->name = $data['vet_name'];
        $vet->email = $data['vet_email'] ?? null;
        $vet->mobile = $data['vet_mobile'] ?? null;
        if (Schema::hasColumn('vet_registerations_temp', 'city')) {
            $vet->city = $data['vet_city'] ?? null;
        }

        if (Schema::hasColumn('vet_registerations_temp', 'password') && empty($vet->password)) {
            $vet->password = '123456';
        }
        if (Schema::hasColumn('vet_registerations_temp', 'exported_from_excell')) {
            $vet->exported_from_excell = 1;
        }
        $vet->save();

        $degreeInput = $data['degree'] ?? null;
        $degreeCsv = null;
        if (is_array($degreeInput)) {
            $degreeParts = array_map(fn ($item) => trim((string) $item), $degreeInput);
            $degreeParts = array_values(array_filter($degreeParts, fn ($item) => $item !== ''));
            $degreeParts = array_values(array_unique($degreeParts));
            $degreeCsv = !empty($degreeParts) ? implode(', ', $degreeParts) : null;
        } elseif (is_string($degreeInput)) {
            $degreeParts = preg_split('/\s*,\s*/', $degreeInput);
            $degreeParts = is_array($degreeParts) ? $degreeParts : [$degreeInput];
            $degreeParts = array_map(fn ($item) => trim((string) $item), $degreeParts);
            $degreeParts = array_values(array_filter($degreeParts, fn ($item) => $item !== ''));
            $degreeParts = array_values(array_unique($degreeParts));
            $degreeCsv = !empty($degreeParts) ? implode(', ', $degreeParts) : null;
        }

        $doctor = new Doctor();
        $doctor->vet_registeration_id = $vet->id;
        $doctor->doctor_name = $data['doctor_name'];
        $doctor->doctor_email = $data['doctor_email'] ?? null;
        $doctor->doctor_mobile = $data['doctor_mobile'] ?? null;
        $doctor->doctor_license = $data['doctor_license'] ?? null;
        $doctor->doctor_image = $doctorImageUrl;
        if ($doctorImageBlob && $doctorImageMime && Schema::hasColumn('doctors', 'doctor_image_blob') && Schema::hasColumn('doctors', 'doctor_image_mime')) {
            $doctor->doctor_image_blob = $doctorImageBlob;
            $doctor->doctor_image_mime = $doctorImageMime;
        }
        if (Schema::hasColumn('doctors', 'bio')) {
            $doctor->bio = $data['bio'] ?? null;
        }
        $doctor->degree = $degreeCsv;
        $doctor->years_of_experience = $data['years_of_experience'] ?? null;
        $doctor->specialization_select_all_that_apply = isset($data['specialization_select_all_that_apply'])
            ? json_encode(array_values(array_filter($data['specialization_select_all_that_apply'])))
            : null;
        if (Schema::hasColumn('doctors', 'languages_spoken')) {
            $doctor->languages_spoken = isset($data['languages_spoken'])
                ? json_encode(array_values(array_filter($data['languages_spoken'])))
                : null;
        }
        $doctor->response_time_for_online_consults_day = $data['response_time_for_online_consults_day'] ?? null;
        $doctor->response_time_for_online_consults_night = $data['response_time_for_online_consults_night'] ?? null;
        $doctor->break_do_not_disturb_time_example_2_4_pm = isset($data['break_do_not_disturb_time_example_2_4_pm'])
            ? json_encode(array_values(array_filter($data['break_do_not_disturb_time_example_2_4_pm'])))
            : null;
        $doctor->do_you_offer_a_free_follow_up_within_3_days_after_a_consulta = $data['do_you_offer_a_free_follow_up_within_3_days_after_a_consulta'] ?? null;
        $doctor->commission_and_agreement = $data['commission_and_agreement'] ?? null;
        $doctor->video_day_rate = $data['video_day_rate'] ?? null;
        $doctor->video_night_rate = $data['video_night_rate'] ?? null;

        if (Schema::hasColumn('doctors', 'exported_from_excell')) {
            $doctor->exported_from_excell = 1;
        }
        if (Schema::hasColumn('doctors', 'password') && empty($doctor->password)) {
            $doctor->password = '123456';
        }
        if (Schema::hasColumn('doctors', 'doctor_password') && empty($doctor->doctor_password)) {
            $doctor->doctor_password = '123456';
        }

        $doctor->save();

        return [
            'vet' => $vet->only(['id', 'name', 'email', 'mobile', 'exported_from_excell']),
            'doctor' => $doctor->only([
                'id',
                'vet_registeration_id',
                'doctor_name',
                'doctor_email',
                'doctor_mobile',
                'doctor_license',
                'doctor_image',
                'doctor_image_mime',
                'bio',
                'degree',
                'years_of_experience',
                'specialization_select_all_that_apply',
                'languages_spoken',
                'response_time_for_online_consults_day',
                'response_time_for_online_consults_night',
                'break_do_not_disturb_time_example_2_4_pm',
                'do_you_offer_a_free_follow_up_within_3_days_after_a_consulta',
                'commission_and_agreement',
                'video_day_rate',
                'video_night_rate',
                'exported_from_excell',
            ]),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $result,
    ], 201);
})->name('excell_export.import');

Route::match(['put', 'patch'], '/doctor/profile', function (Request $request) {
    $doctorId = $request->query('doctor_id');
    if (!$doctorId) {
        return response()->json(['message' => 'doctor_id is required'], 422);
    }

    $doctor = Doctor::find($doctorId);
    if (!$doctor) {
        return response()->json(['message' => 'Doctor not found'], 404);
    }

    $validated = $request->validate([
        'doctor_name' => 'sometimes|required|string|max:255',
        'doctor_email' => 'sometimes|nullable|email|max:255',
        'doctor_mobile' => 'sometimes|nullable|string|max:25',
        'doctor_license' => 'sometimes|nullable|string|max:150',
        'staff_role' => 'sometimes|nullable|string|max:100',
        'doctor_image' => 'sometimes|nullable|string|max:500',
        'doctor_document' => 'sometimes|nullable|string|max:500',
        'toggle_availability' => 'sometimes|boolean',
        'doctors_price' => 'sometimes|nullable|numeric|min:0|max:1000000',
        'vet_registeration_id' => 'sometimes|nullable|integer|exists:vet_registerations_temp,id',
    ]);

    $doctor->fill($validated);
    $doctor->save();

    return response()->json([
        'message' => 'Doctor profile updated successfully.',
        'data' => [
            'id' => $doctor->id,
            'doctor_name' => $doctor->doctor_name,
            'doctor_email' => $doctor->doctor_email,
            'doctor_mobile' => $doctor->doctor_mobile,
            'doctor_license' => $doctor->doctor_license,
            'doctor_image' => $doctor->doctor_image,
            'doctor_document' => $doctor->doctor_document,
            'toggle_availability' => $doctor->toggle_availability,
            'doctors_price' => $doctor->doctors_price,
            'vet_registeration_id' => $doctor->vet_registeration_id,
            'staff_role' => $doctor->staff_role,
        ],
    ]);
});

Route::get('/doctors/featured', function (Request $request) {
    $userId = $request->query('user_id');
    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is required',
        ], 422);
    }

    $user = User::find($userId);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found',
        ], 404);
    }

    if (empty($user->last_vet_id)) {
        return response()->json([
            'success' => true,
            'data' => [
                'clinic' => null,
                'doctors' => [],
            ],
        ]);
    }

    $clinic = \App\Models\VetRegisterationTemp::with('doctors')
        ->where('id', $user->last_vet_id)
        ->first();

    if (!$clinic) {
        return response()->json([
            'success' => true,
            'data' => [
                'clinic' => null,
                'doctors' => [],
            ],
        ]);
    }

    $clinicData = [
        'id' => $clinic->id,
        'name' => $clinic->name,
        'slug' => $clinic->slug,
        'city' => $clinic->city,
        'address' => $clinic->formatted_address ?? $clinic->address,
        'phone' => $clinic->mobile,
        'image' => $clinic->image,
    ];

    $doctorsData = $clinic->doctors->map(function (Doctor $doc) {
        return [
            'id' => $doc->id,
            'name' => $doc->doctor_name,
            'email' => $doc->doctor_email,
            'phone' => $doc->doctor_mobile,
            'license' => $doc->doctor_license,
            'image' => $doc->doctor_image,
            'price' => $doc->doctors_price,
        ];
    })->values();

    return response()->json([
        'success' => true,
        'data' => [
            'clinic' => $clinicData,
            'doctors' => $doctorsData,
        ],
    ]);
});

Route::get('/users/last-vet-details', function (Request $request) {
    $payload = $request->validate([
        'user_id' => ['required', 'integer'],
    ]);

    $user = User::query()->select('id', 'last_vet_id')->find($payload['user_id']);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found',
        ], 404);
    }

    if (empty($user->last_vet_id)) {
        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'last_vet_id' => null,
                'clinic' => null,
                'doctors' => [],
            ],
        ]);
    }

    $clinic = VetRegisterationTemp::query()->find($user->last_vet_id);

    if (!$clinic) {
        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'last_vet_id' => $user->last_vet_id,
                'clinic' => null,
                'doctors' => [],
            ],
        ]);
    }

    $doctors = Doctor::query()
        ->where('vet_registeration_id', $clinic->id)
        ->get();

    return response()->json([
        'success' => true,
        'data' => [
            'user_id' => $user->id,
            'last_vet_id' => $user->last_vet_id,
            'clinic' => $clinic,
            'doctors' => $doctors->values(),
        ],
    ]);
});

Route::post('/users/last-vet', function (Request $request) {
    $payload = $request->validate([
        'user_id' => ['required', 'integer', 'exists:users,id'],
        'vet_id' => ['required', 'integer', 'exists:vet_registerations_temp,id'],
    ]);

    if (! Schema::hasColumn('users', 'last_vet_id')) {
        return response()->json([
            'success' => false,
            'message' => 'last_vet_id column missing on users table',
        ], 500);
    }

    $user = User::find($payload['user_id']);
    $user->last_vet_id = $payload['vet_id'];
    $user->save();

    return response()->json([
        'success' => true,
        'data' => [
            'user_id' => $user->id,
            'last_vet_id' => $payload['vet_id'],
        ],
    ]);
});

Route::post('/appointments/submit', [AppointmentSubmissionController::class, 'store'])
    ->name('api.appointments.submit');
Route::get('/appointments/by-doctor/{doctor}', [AppointmentSubmissionController::class, 'listByDoctor'])
    ->name('api.appointments.by-doctor');
Route::get('/appointments/by-doctor/{doctor}/queue', [AppointmentSubmissionController::class, 'listByDoctorQueue'])
    ->name('api.appointments.by-doctor-queue');
Route::get('/appointments/by-user/{user}', [AppointmentSubmissionController::class, 'listByUser'])
    ->name('api.appointments.by-user');
Route::get('/appointments/{appointment}/patient-details', [AppointmentSubmissionController::class, 'patientDetails'])
    ->name('api.appointments.patient-details');
Route::get('/appointments/{appointment}', [AppointmentSubmissionController::class, 'show'])
    ->name('api.appointments.show');
Route::get('/appointments/{appointment}/edit', [AppointmentSubmissionController::class, 'edit'])
    ->name('api.appointments.edit');
Route::match(['put', 'patch'], '/appointments/{appointment}', [AppointmentSubmissionController::class, 'update'])
    ->name('api.appointments.update');
// Call session info (patient/doctor polling)
Route::get('/call/{id}', [CoreCallController::class, 'show'])->whereNumber('id');
// routes/web.php (ya api.php)
Route::get('/debug/pusher', function () {
    return [
        'driver'  => config('broadcasting.default'),
        'key'     => config('broadcasting.connections.pusher.key'),
        'app_id'  => config('broadcasting.connections.pusher.app_id'),
        'cluster' => config('broadcasting.connections.pusher.options.cluster'),
    ];
});




// Create/accept/payment flow for video consult
Route::post('/call/create', [CoreCallController::class, 'createSession']);
Route::post('/call/{id}/accept', [CoreCallController::class, 'acceptCall'])->whereNumber('id');
Route::post('/call/{id}/payment-success', [CoreCallController::class, 'paymentSuccess'])->whereNumber('id');
Route::post('/call/{id}/start', [CoreCallController::class, 'markStarted'])->whereNumber('id');
Route::post('/call/{id}/end', [CoreCallController::class, 'markEnded'])->whereNumber('id');
Route::post('/call/{id}/recordings/start', [CallRecordingController::class, 'start'])->whereNumber('id');
Route::post('/call/{id}/recordings/stop', [CallRecordingController::class, 'stop'])->whereNumber('id');
Route::get('/call/{id}/recordings/status', [CallRecordingController::class, 'status'])->whereNumber('id');
Route::post('/call/{id}/recordings/transcript', [CallRecordingController::class, 'requestTranscript'])->whereNumber('id');
Route::post('/internal/doctor-call-alert', [\App\Http\Controllers\Api\DoctorNotificationController::class, 'pendingCall']);

Route::get('/users', [AdminController::class, 'getUsers']);
// Route::get('/vets', [AdminController::class, 'getVets']);

Route::get('/dog-breed/{breed}', [\App\Http\Controllers\Api\DogBreedController::class, 'getBreedImage']);
Route::get('/dog-breeds/all', [\App\Http\Controllers\Api\DogBreedController::class, 'allBreeds']);
Route::get('/cat-breeds/with-indian', function () {
    $baseUrl = rtrim((string) config('services.catapi.base_url', 'https://api.thecatapi.com/v1'), '/');
    $apiKey = trim((string) config('services.catapi.key', ''));

    if ($apiKey === '') {
        return response()->json([
            'success' => false,
            'message' => 'Cat API key missing',
        ], 500);
    }

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'x-api-key' => $apiKey,
        'Accept' => 'application/json',
    ])->timeout(20)->get($baseUrl . '/breeds');

    if (! $response->successful()) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch breeds from TheCatAPI',
            'status' => $response->status(),
            'error' => $response->json('message') ?? $response->body(),
        ], 502);
    }

    $breeds = $response->json();
    if (!is_array($breeds)) {
        $breeds = [];
    }

    $indianBreed = [
        'id' => 'indian_cat',
        'name' => 'Indian Cat',
        'origin' => 'India',
        'country_code' => 'IN',
        'temperament' => 'Adaptable, Intelligent, Social',
        'description' => 'Indian domestic cat breed profile (custom addition by SnoutIQ).',
        'life_span' => '12 - 16',
    ];

    $exists = collect($breeds)->contains(function ($breed) {
        $id = strtolower(trim((string) data_get($breed, 'id', '')));
        $name = strtolower(trim((string) data_get($breed, 'name', '')));
        return $id === 'indian_cat' || $name === 'indian cat';
    });

    if (! $exists) {
        $breeds[] = $indianBreed;
    }

    return response()->json([
        'success' => true,
        'source' => 'thecatapi',
        'custom_added' => ! $exists,
        'count' => count($breeds),
        'data' => $breeds,
    ]);
})->name('cat_breeds.with_indian');





// use App\Http\Controllers\Api\ActiveDoctorController;
use App\Http\Controllers\Api\GeminiChatController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\VideoCallingController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\WeatherLogController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\DoctorStatusController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\ClinicsController;
use App\Http\Controllers\Api\DraftClinicController;
use App\Http\Controllers\Api\Founder\AlertController as FounderAlertController;
use App\Http\Controllers\Api\Founder\ClinicController as FounderClinicController;
use App\Http\Controllers\Api\Founder\DashboardController as FounderDashboardController;
use App\Http\Controllers\Api\Founder\RevenueController as FounderRevenueController;
use App\Http\Controllers\Api\Founder\SalesController as FounderSalesController;
use App\Http\Controllers\Api\Founder\SettingController as FounderSettingController;



Route::get('/weather/latest', [WeatherLogController::class, 'latest']);
Route::get('/weather/history', [WeatherLogController::class, 'history']);

Route::get('/weather/by-coords', [WeatherController::class, 'byCoords']);

Route::get('/weather/cities', [WeatherController::class, 'cities']);            // ?cities=Delhi,Mumbai,Bangalore
Route::get('/weather/detail', [WeatherController::class, 'detail']);            // ?city=Delhi
Route::get('/weather/hourly-schedule', [WeatherController::class, 'hourlySchedule']); // ?city=Delhi

// routes/api.php
Route::get('/nearby-vets', [VideoCallingController::class, 'nearbyVets']);
Route::get('/nearby-doctors', [VideoCallingController::class, 'nearbyDoctors']);
Route::get('/nearby-plus-featured', [VideoCallingController::class, 'nearbyPlusFeatured']);
// Route::get('/active-doctors', ActiveDoctorController::class);

// ---- Prescriptions ----
Route::get('/prescriptions', [PrescriptionController::class, 'index']);
Route::get('/users/medical-summary', [PrescriptionController::class, 'userData']); // ?user_id=
Route::get('/users/pets-prescriptions', [PrescriptionController::class, 'userPetsAndPrescriptions']); // ?user_id=
Route::get('/doctors/{doctor_id}/prescriptions', [PrescriptionController::class, 'forDoctor'])->whereNumber('doctor_id');
Route::get('/prescriptions/{id}', [PrescriptionController::class, 'show']);
Route::post('/prescriptions', [PrescriptionController::class, 'store']);
Route::get('/chat-rooms/{chat_room_token}/chats', [GeminiChatController::class, 'getRoomChats']);
Route::get('/test-dd', function () {
    dd("highest in the room ~travis scott g g g g");
});
Route::get('/test-cors', function (Request $request) {
    return response()->json([
        'message' => 'CORS test',
        'headers' => $request->headers->all()
    ]);
});

Route::get('/chats', [GeminiChatController::class, 'history']); 
Route::post('/contact-request', [ContactRequestController::class, 'store']);
Route::post('/referrals/download', [ReferralController::class, 'sendDownloadLink'])->name('api.referrals.download');
Route::get('/referrals/{code}', [ReferralController::class, 'showByCode'])->name('api.referrals.lookup');
Route::post('/downloads/track', [ReferralController::class, 'trackDownload'])->name('api.downloads.track');
Route::get('/chat-rooms/new', [GeminiChatController::class, 'newRoom']); 
Route::post('/chat/send', [GeminiChatController::class, 'sendMessage']);
Route::post('/chat/dog-disease', [GeminiChatController::class, 'dogDisease']);
Route::put('/chat/dog-disease/question', [GeminiChatController::class, 'updateDogDiseaseQuestion']);
Route::get('/chat/listRooms', [GeminiChatController::class, 'listRooms']);
// routes/api.php
Route::get('/chat-rooms/{chat_room_token}/chats', [\App\Http\Controllers\Api\GeminiChatController::class, 'history']);

Route::post('/clinics/drafts', [DraftClinicController::class, 'store']);
Route::get('/clinics/drafts', [DraftClinicController::class, 'index']);

// Summarize a chat room and save to chat_rooms.summary
Route::post('/chat-rooms/{chat_room_token}/summarize', [GeminiChatController::class, 'summarizeRoom']);

// Shortcut endpoint: POST /api/summary { user_id, chat_room_token }
Route::post('/summary', [GeminiChatController::class, 'summary']);

// ---- Reviews ----
Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);

// ---- Doctor status/availability updates by vet_registerations_temp.id ----
Route::post('/doctor/update-status', [DoctorStatusController::class, 'updateByVet']);
Route::patch('/doctors/{doctor}/status', [DoctorStatusController::class, 'updateDoctor']);

// One-off: insert vet + doctor from "Video Consult Pricing and active doctors" sheet (Dr Parvinder Singh Rathee)
Route::post('/dev/seed-parvinder-rathee', function () {
    $vetData = [
        'name' => 'Dr Parvinder Singh Rathee Clinic',
        'email' => 'parvinder.singh.rathee@example.com',
        'mobile' => '9876543210',
        'city' => 'Gurgaon',
        'pincode' => '122001',
        'exported_from_excell' => 1,
        'password' => '123456',
    ];

    $doctorData = [
        'doctor_name' => 'Dr Parvinder Singh Rathee',
        'doctor_email' => 'parvinder.singh.rathee@example.com',
        'doctor_mobile' => '9876543210',
        'degree' => 'BVSc & AH',
        'years_of_experience' => '10+',
        'specialization_select_all_that_apply' => 'General Medicine, Surgery',
        'video_day_rate' => 499,
        'video_night_rate' => 699,
        'exported_from_excell' => 1,
        'password' => '123456',
        'doctor_password' => '123456',
    ];

    return DB::transaction(function () use ($vetData, $doctorData) {
        $vet = VetRegisterationTemp::create($vetData);

        $doctorData['vet_registeration_id'] = $vet->id;
        $doctor = Doctor::create($doctorData);

        return response()->json([
            'success' => true,
            'message' => 'Seeded vet and doctor for Dr Parvinder Singh Rathee',
            'vet' => $vet,
            'doctor' => $doctor,
        ], 201);
    });
});

Route::delete('/chat-rooms/{chat_room_token}', [GeminiChatController::class, 'deleteRoom']);
Route::get('/gemini/describe-pet', [AuthController::class, 'describePetImage']);

Route::post('/vet-registerations/store', [VetRegisterationTempController::class, 'store']);
Route::get('/vet-registerations/{vet}', [VetRegisterationTempController::class, 'show']);

Route::get('/ai-stats', function (Request $Request) {
            
    return response()->json([
      'totalUser'=>User::count(),
      'totalQuery'=>App\Models\UserAiChat::count()
    ]);
});
Route::get('/user/data', function (Request $Request) {
            $onboarding = false;
               if($Request->user()->role=="pet_owner"){
        // 
if(App\Models\UserProfile::where('user_id', $Request->user()->id)->count() == 0) {
           $onboarding = true;
        }
        // 
    }
    return response()->json(['data' =>array_merge($Request->user()->toArray(),['onboarding' => $onboarding])]);
});

// Push Notifications using FCM
use App\Http\Controllers\Api\PushController; // late import is okay in routes
Route::prefix('push')->group(function () {
    Route::post('/register-token', [PushController::class, 'registerToken']);
    Route::put('/edit-token', [PushController::class, 'editToken']);
    Route::delete('/register-token', [PushController::class, 'unregisterToken']);
    Route::post('/test', [PushController::class, 'testToToken']);
    Route::post('/ring', [PushController::class, 'ring']);
});

Route::post('/send-otp', [AuthController::class, 'send_otp']);
// Route::post('/forgot-password', [ForgotPasswordSimpleController::class, 'sendNewPassword']);

Route::post('/verify-otp', [AuthController::class, 'verify_otp']);
// Route::post('/login', [AuthController::class , 'login']);
// Route::post('/register', [AuthController::class, 'register']);





Route::post('/google-login', [AuthController::class, 'googleLogin']);
Route::post('/auth/register',   [AuthController::class, 'register']);
Route::get('/auth/users/{user}/pet-doc2-blob', [AuthController::class, 'userPetDoc2Blob'])
    ->whereNumber('user')
    ->name('api.users.pet-doc2-blob');
Route::get('/auth/pets/{pet}/pet-doc2-blob', [AuthController::class, 'petDoc2Blob'])
    ->whereNumber('pet')
    ->name('api.pets.pet-doc2-blob');
Route::post('/auth/register-via-mobile', [AuthController::class, 'registerViaMobile']);
Route::post('/auth/initial-register', [AuthController::class, 'createInitialRegistration']);
Route::post('/auth/initial-register-mobile', [AuthController::class, 'createInitialRegistrationMobile']);
Route::post('/auth/login',      [AuthController::class, 'login']);
Route::post('/auth/role-login', [RoleLoginController::class, 'login']);
Route::post('/auth/pet-summary', [AuthController::class, 'generatePetSummary']);
Route::get('/clinics/{clinic}/payments', [AuthController::class, 'clinicPayments']);
Route::get('/clinics/payments', [AuthController::class, 'clinicPayments']);

Route::get('/auth/me',          [AuthController::class, 'me']);     // session check
Route::post('/auth/logout',     [AuthController::class, 'logout']); // invalidate

Route::get('/fetchNearbyPlaces', [PublicController::class, 'fetchNearbyPlaces']);

Route::prefix('sales')->group(function () {
    Route::get('/dashboard', [SalesDashboardController::class, 'dashboard']);
    Route::get('/qr-scanners', [SalesDashboardController::class, 'qrScanners']);
    Route::get('/qr-scanners/{scanner}', [SalesDashboardController::class, 'scannerMetrics']);
    Route::post('/qr-scanners/{scanner}/notify', [SalesDashboardController::class, 'notifyDormantPetParents']);
    Route::get('/vet-registrations', [SalesDashboardController::class, 'vetRegistrations']);
});


// using Query Builder
Route::get('/users-db', function () {
      //  dd('hi');
    $users = DB::table('users')->get();
    return response()->json($users);
});
 

// using Eloquent Model
Route::get('/users', function () {

    $users = User::all();
    return response()->json($users);
});
// Route::get('/vets', function () {
//     $vets = DB::table('vet_registerations_temp')->get();
//     return response()->json($vets);
// });
  Route::post('/store_booking', [GroomerCalenderController::class, 'store_booking']);
          Route::get('/bookings', [GroomerCalenderController::class, 'bookings']);
          Route::get('/bookings-v2', [GroomerCalenderController::class, 'bookingsV2']);
          Route::get('/bookings/{id}', [GroomerCalenderController::class, 'booking_single']);
Route::prefix('groomer')->group(function () {
Route::post('/profile', [GroomerProfileController::class, 'store']);
Route::get('/ratings', [GroomerProfileController::class, 'ratings']);

Route::get('/onboarding-tasks', [DashboardController::class, 'onboarding_tasks']);
Route::get('/dashboard', [GroomerProfileController::class, 'dashboard']);
Route::get('/dashboard-v2', [DashboardController::class, 'dashboardv2']);

Route::get('/profile', [GroomerProfileController::class, 'get']);
Route::post('/service_categroy', [GroomerServiceCategoryController::class, 'store']);
Route::get('/service_categroy', [GroomerServiceCategoryController::class, 'get']);
Route::post('/service_categroy/{id}', [GroomerServiceCategoryController::class, 'update']);
Route::post('/service_categroy/{id}/delete', [GroomerServiceCategoryController::class, 'delete']);
    

 Route::get('/services', [GroomerServiceController::class, 'get']);

    Route::get('/service/{id}', [GroomerServiceController::class, 'view']);
    Route::get('/service/{id}/edit', [GroomerServiceController::class, 'edit']);
    Route::post('/service/{id}/update', [GroomerServiceController::class, 'update']);

      Route::get('/employees', [GroomerEmployeeController::class, 'get']);
    Route::post('/employees', [GroomerEmployeeController::class, 'store']);
      Route::get('/employees/{id}', [GroomerEmployeeController::class, 'show']);
    Route::put('/employees/{id}', [GroomerEmployeeController::class, 'update']);

          Route::post('/booked_times', [GroomerCalenderController::class, 'booked_times']);
          Route::get('/clients', [GroomerClientController::class, 'get']);
          Route::get('/clients/{id}', [GroomerClientController::class, 'single']);
          Route::put('/clients/{id}', [GroomerClientController::class, 'update']);
          Route::post('/clients', [GroomerClientController::class, 'store']);

        
          Route::post('/bookings/{id}/delete', [GroomerCalenderController::class, 'booking_single_delete']);
          Route::post('/bookings/{id}/payment', [GroomerCalenderController::class, 'booking_single_payment']);
          Route::post('/bookings/{id}/status', [GroomerCalenderController::class, 'booking_single_status']);
          Route::post('/bookings/{id}/prescription', [GroomerCalenderController::class, 'booking_single_prescription']);
          Route::post('/bookings/{id}/assignEmployee', [GroomerCalenderController::class, 'booking_single_assignEmployee']);
 
/*
NAVEEN WORKING ON MARKETING MODULE

*/
   Route::get('/offers', [GroomerMarketingController::class, 'getOffers']);
    Route::post('/offers', [GroomerMarketingController::class, 'storeOffer']);
    Route::get('/coupons', [GroomerMarketingController::class, 'getCoupons']);
    Route::post('/coupons', [GroomerMarketingController::class, 'storeCoupon']);
    Route::get('/notifications', [GroomerMarketingController::class, 'getNotifications']);
    Route::post('/notifications', [GroomerMarketingController::class, 'storeNotification']);
    Route::get('/featured-services', [GroomerMarketingController::class, 'getFeaturedServices']);
    Route::post('/featured-services', [GroomerMarketingController::class, 'storeFeaturedServices']); 

/*

NAVEEN MARKETING MODULE
*/
});


Route::prefix('user')->group(function () {




    
    Route::get('/profile/completion', [UserController::class, 'profileCompletion']);
    Route::get('/profile', [UserController::class, 'profile']);
  Route::get('/my_bookings', [UserController::class, 'my_bookings']);
    Route::get('/my_booking/{id}', [UserController::class, 'my_booking']);
     Route::post('/profile', [UserController::class, 'profile_update']);
     Route::post('/add_pet', [UserController::class, 'add_pet']);
     Route::get('/my_pets', [UserController::class, 'my_pets']);
     Route::get('/pet/{id}', [UserController::class, 'pet_profile']);
     Route::post('/pet/{id}', [UserController::class, 'pet_update']);
     Route::put('/pets/{id}/extras', [UserController::class, 'petExtrasUpdate']);

     Route::post('/ai/start',[UserAiController::class,'start']);
     Route::get('/ai/chats',[UserAiController::class,'history']);
     Route::get('/ai/chats/{token}',[UserAiController::class,'chats']);
          Route::post('/ai/chats/{token}',[UserAiController::class,'postChat']);
          Route::post('/ai/rated/{hist_id}',[UserAiController::class,'rated']);

          Route::post('/razorpay/create-order', [RazorpayController::class, 'createOrder']);
          Route::post('/book_grooming', [RazorpayController::class, 'book_grooming']);

          /* --------- EMERGENCY Routes ---------*/
  Route::post('/emergency/sendRequest', [EmergencyController::class, 'sendRequest']);
  Route::post('/emergency/isAccepted', [EmergencyController::class, 'isAccepted']);
  Route::post('/emergency/amtPaid', [EmergencyController::class, 'amtPaid']);
  Route::post('/emergency/searchForRequest', [EmergencyController::class, 'searchForRequest']);
  Route::post('/emergency/acceptEmergancy', [EmergencyController::class, 'acceptEmergancy']);
          /* --------- EMERGENCY Routes ---------*/

  Route::post('/booking/getRatings', [RatingController::class, 'getRatings']);
          Route::post('/booking/postBooking', [RatingController::class, 'postBooking']);




          
          
});
Route::prefix('public')->group(function(){

    Route::get('/groomers', [PublicController::class, 'groomers']);
    Route::get('/single_groomer/{id}', [PublicController::class, 'single_groomer']);

      Route::post('/chats/startChat', [ChatController::class, 'startChat']);
      Route::post('/chats/myMessages', [ChatController::class, 'myMessages']);
      Route::post('/chats/chatHistory', [ChatController::class, 'chatHistory']);
      Route::post('/chats/sendMessage', [ChatController::class, 'sendMessage']);



    Route::post('/support/sendMessage', [SupportController::class, 'store']);
    Route::post('/support/mydata', [SupportController::class, 'mydata']);

});

Route::prefix('user')->group(function () {
    Route::get('/observations', [UserObservationController::class, 'index']);
    Route::post('/observations', [UserObservationController::class, 'store']);
    Route::get('/observations/{observation}/image', [UserObservationController::class, 'image'])
        ->whereNumber('observation')
        ->name('api.user.observations.image');
});

// Backward/alternate endpoint naming used by client integrations
Route::get('/user-per-observationss', [UserObservationController::class, 'index'])
    ->name('api.user-per-observationss.index');
Route::post('/user-per-observationss', [UserObservationController::class, 'store'])
    ->name('api.user-per-observationss.store');
Route::get('/user-per-observationss/{observation}/image', [UserObservationController::class, 'image'])
    ->whereNumber('observation')
    ->name('api.user-per-observationss.image');


// Agora RTC token for joining the call
Route::post('/agora/token', [CoreCallController::class, 'generateToken']);

use App\Http\Controllers\Api\PetParentController;

Route::get('/petparents/{id}', [PetParentController::class, 'show']);     // Single pet parent
Route::delete('/petparents/{id}', [PetParentController::class, 'destroy']); // Delete pet parent

Route::get('/razorpay-ping', function () {
    $api = new \Razorpay\Api\Api(trim(config('services.razorpay.key')), trim(config('services.razorpay.secret')));
    try {
        $api->order->all(['count' => 1]);
        return ['auth' => 'ok'];
    } catch (\Razorpay\Api\Errors\Error $e) {
        return response(['auth' => 'fail', 'msg' => $e->getMessage()], 401);
    }
});

  Route::post('/rzp/verify', [PaymentController::class, 'verifyPayment']);

  Route::get('/rzp-test', [PaymentController::class, 'testView']); // view render

  Route::post('/create-order', [PaymentController::class, 'createOrder']);

  Route::get('/user-chats/{user_id}', [GeminiChatController::class, 'getUserChats']);

  Route::post('/chats/{chat_id}/feedback', [GeminiChatController::class, 'setFeedback']);
  Route::post('/users/feedback', [UserFeedbackController::class, 'store']);


Route::prefix('unified')->group(function () {
    Route::post('/process', [UnifiedIntelligenceController::class, 'process']);
    Route::get('/status',   [UnifiedIntelligenceController::class, 'status']);
    Route::post('/reset',   [UnifiedIntelligenceController::class, 'reset']);
});

Route::view('/prompt-test-v2', 'prompt-test-v2');



// Old chat endpoint (kept as-is)
Route::post('/chat/send', [GeminiChatController::class, 'sendMessage']);

// New Unified Intelligence endpoints
Route::post('/chat/unified', [GeminiChatController::class, 'unifiedProcess']);
Route::get('/chat/unified/status', [GeminiChatController::class, 'unifiedStatus']);
Route::post('/chat/unified/reset', [GeminiChatController::class, 'unifiedReset']);

Route::prefix('groomer')->group(function () {
              // list
  //  Route::post('/service', [GroomerServiceController::class, 'store']);          // create
    Route::get('/service/{id}', [GroomerServiceController::class, 'view']);       // view single
    Route::post('/service/{id}/update', [GroomerServiceController::class, 'update']); // update
    Route::delete('/service/{id}', [GroomerServiceController::class, 'destroy']); // <-- NEW: delete
});
 Route::get('groomer/services', [GroomerServiceController::class, 'get']); 
Route::post('groomer/service', [GroomerServiceController::class, 'store']);
    Route::delete('groomer/service/{id}', [GroomerServiceController::class, 'destroy']);

Route::get('/clinic-service-presets', [ClinicServicePresetController::class, 'index']);
Route::post('/clinic-service-presets', [ClinicServicePresetController::class, 'store']);

Route::prefix('staff')->group(function () {
    Route::get('/', [StaffController::class, 'index']);
    Route::post('/receptionists', [StaffController::class, 'storeReceptionist']);
    Route::patch('/{type}/{id}/role', [StaffController::class, 'updateRole'])
        ->whereIn('type', ['doctor', 'receptionist']);
    Route::patch('/{type}/{id}', [StaffController::class, 'update'])
        ->whereIn('type', ['doctor', 'receptionist']);
    Route::delete('/{type}/{id}', [StaffController::class, 'destroy'])
        ->whereIn('type', ['doctor', 'receptionist']);
});

Route::prefix('receptionist')->group(function () {
    Route::get('/bookings', [ReceptionistBookingController::class, 'bookings']);
    Route::post('/bookings', [ReceptionistBookingController::class, 'storeBooking']);
    Route::get('/patients', [ReceptionistBookingController::class, 'patients']);
    Route::post('/patients', [ReceptionistBookingController::class, 'storePatient']);
    Route::get('/patients/{user}/pets', [ReceptionistBookingController::class, 'patientPets']);
    Route::get('/doctors', [ReceptionistBookingController::class, 'doctors']);
    Route::get('/doctors/available', [ReceptionistBookingController::class, 'availableDoctors']);
    Route::get('/appointments/today', [ReceptionistBookingController::class, 'appointmentsToday']);
    Route::get('/vaccination-records', [PetVaccinationRecordController::class, 'index']);
    Route::post('/vaccination-records', [PetVaccinationRecordController::class, 'store']);
});


   // Clinic Reels CRUD routes (for admin panel)
Route::get('/backend/groomer/reels',            [ClinicReelController::class, 'get']);            // list reels ?user_id= or ?vet_slug=
Route::get('/backend/groomer/reel/{id}',        [ClinicReelController::class, 'show']);           // view single
Route::post('/backend/groomer/reel',            [ClinicReelController::class, 'store']);          // create
Route::post('/backend/groomer/reel/{id}/update',[ClinicReelController::class, 'update']);         // update
Route::delete('/backend/groomer/reel/{id}',     [ClinicReelController::class, 'destroy']);        // delete



          Route::post('/block_time', [GroomerCalenderController::class, 'store_blockTime']);



          Route::prefix('doctor')->group(function () {
    Route::post('/bookings', [GroomerCalenderController::class, 'store_doctor_booking']);
    Route::get('/bookings', [GroomerCalenderController::class, 'doctor_bookings']);
});


Route::get('/users',            [AdminController::class, 'getUsers']);
Route::delete('/users/by-phone', [AdminController::class, 'deleteUserByPhone']);
Route::get('/users/{id}',       [AdminController::class, 'getUser']);
Route::put('/users/{id}',       [AdminController::class, 'updateUser']);
Route::delete('/users/{id}',    [AdminController::class, 'deleteUser']);

Route::get('/vets',             [AdminController::class, 'getVets']);

Route::get('/users/{userId}/pets',  [AdminController::class, 'listPets']);
Route::post('/users/{userId}/pets', [AdminController::class, 'addPet']);
Route::get('/pets/{petId}',         [AdminController::class, 'getPet']);
Route::post('/pets/{petId}/dog-disease', [AdminController::class, 'suggestDogDisease']);
Route::post('/pets/{petId}/summary', [AdminController::class, 'summarizePet']);
Route::get('/pets/{petId}/overview', [\App\Http\Controllers\Api\PetOverviewController::class, 'show']);
Route::put('/pets/{petId}',         [AdminController::class, 'updatePet']);
Route::delete('/pets/{petId}',      [AdminController::class, 'deletePet']);


Route::post('/doctor/availability', [GroomerCalenderController::class,'doctor_availability_store']);
Route::get('/doctor/availability/suggestions', [GroomerCalenderController::class,'doctor_availability_suggestions']);
Route::post('/users/phone', [UserController::class, 'updatePhone']);

// --- Snoutiq Healthcare API (scaffold) ---

// Provider endpoints
Route::post('/providers/register', [\App\Http\Controllers\Api\ProvidersController::class, 'register']);
Route::post('/providers/complete-profile', [\App\Http\Controllers\Api\ProvidersController::class, 'completeProfile']);
Route::get('/providers/{id}/status', [\App\Http\Controllers\Api\ProvidersController::class, 'status']);
Route::put('/providers/{id}/availability', [\App\Http\Controllers\Api\ProvidersController::class, 'updateAvailability']);

// Booking endpoints
Route::post('/bookings/create', [\App\Http\Controllers\Api\BookingsController::class, 'create']);
Route::get('/bookings/details/{id}', [\App\Http\Controllers\Api\BookingsController::class, 'details']);
Route::put('/bookings/{id}/status', [\App\Http\Controllers\Api\BookingsController::class, 'updateStatus']);
Route::post('/bookings/{id}/rate', [\App\Http\Controllers\Api\BookingsController::class, 'rate']);
Route::post('/bookings/{id}/verify-payment', [\App\Http\Controllers\Api\BookingsController::class, 'verifyPayment']);
Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/slots', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'slots']);
Route::get('/doctors/{id}', [DoctorController::class, 'show']);
Route::put('/doctors/{id}', [DoctorController::class, 'update']);
Route::get('/doctors/{id}/bookings', [\App\Http\Controllers\Api\BookingsController::class, 'doctorBookings']);
Route::get('/socket/doctors/{doctor}', [\App\Http\Controllers\Api\SocketDoctorController::class, 'show']);

Route::prefix('vaccination')->group(function () {
    Route::get('/clinics', [VaccinationBookingController::class, 'clinics']);
    Route::get('/clinics/{clinicId}/slots', [VaccinationBookingController::class, 'slots']);
    Route::post('/bookings', [VaccinationBookingController::class, 'store']);
    Route::patch('/bookings/{id}', [VaccinationBookingController::class, 'update']);
    Route::post('/bookings/{id}/pay', [VaccinationBookingController::class, 'pay']);
    Route::get('/bookings/{id}', [VaccinationBookingController::class, 'show']);
});

// Coverage endpoints
Route::get('/coverage/dashboard', [\App\Http\Controllers\Api\CoverageController::class, 'dashboard']);
Route::get('/coverage/zone/{id}', [\App\Http\Controllers\Api\CoverageController::class, 'zone']);

// Admin endpoints
Route::get('/admin/tasks', [\App\Http\Controllers\Api\AdminController::class, 'tasks']);
Route::get('/admin/alerts', [\App\Http\Controllers\Api\AdminController::class, 'alerts']);
Route::post('/admin/resolve-alert/{id}', [\App\Http\Controllers\Api\AdminController::class, 'resolveAlert']);
Route::get('/admin/providers-queue', [\App\Http\Controllers\Api\AdminController::class, 'providersQueue']);
Route::get('/admin/analytics', [\App\Http\Controllers\Api\AdminController::class, 'analytics']);

// ML endpoints
Route::post('/ml/train', [\App\Http\Controllers\Api\MLController::class, 'train']);
Route::get('/ml/provider-performance/{id}', [\App\Http\Controllers\Api\MLController::class, 'providerPerformance']);
Route::get('/ml/demand-prediction', [\App\Http\Controllers\Api\MLController::class, 'demandPrediction']);

// Doctor availability (schedule)
Route::put('/doctors/{id}/availability', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'updateAvailability']);
Route::get('/doctors/{id}/free-slots', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'freeSlots']);
Route::get('/doctors/{id}/slots', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'slotsByDoctor']);
Route::get('/doctors/{id}/slots/summary', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'slotsSummary']);
Route::post('/doctors/slots', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'slots']);
Route::post('/doctors/{id}/price', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'updatePrice']);
Route::put('/doctors/{id}/price', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'updatePrice']);
Route::get('/doctors/{id}/schedules/combined', [\App\Http\Controllers\Api\DoctorScheduleSummaryController::class, 'show']);

// Clinics
Route::get('/clinics', [\App\Http\Controllers\Api\ClinicsController::class, 'index']);
Route::get('/clinics/services', [ClinicsController::class, 'servicesByClinicId']);
Route::get('/clinics/patients', [ClinicsController::class, 'patientsByClinicId']);
Route::get('/clinics/{id}/services', [ClinicsController::class, 'services']);
Route::get('/clinics/{id}/doctors', [ClinicsController::class, 'doctors']);
Route::get('/clinics/{id}/patients', [ClinicsController::class, 'patients']);
Route::post('/clinics/{id}/doctors', [ClinicsController::class, 'storeDoctor']);
Route::get('/clinics/{id}/availability', [\App\Http\Controllers\Api\ClinicsController::class, 'availability']);
Route::get('/doctors/{id}/availability', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'getAvailability']);
// --- Separate video-only scheduling API (new table) ---
Route::put('/video-schedule/doctors/{id}/availability', [\App\Http\Controllers\Api\DoctorVideoScheduleController::class, 'updateAvailability']);
Route::get('/video-schedule/doctors/{id}/availability', [\App\Http\Controllers\Api\DoctorVideoScheduleController::class, 'getAvailability']);
Route::get('/video-schedule/doctors/{id}/free-slots', [\App\Http\Controllers\Api\DoctorVideoScheduleController::class, 'freeSlots']);
// Pets
Route::get('/users/{id}/pets', [\App\Http\Controllers\Api\PetsController::class, 'byUser']);
Route::get('/users/{user}/medical-records', [MedicalRecordController::class, 'index']);
Route::get('/medical-records/user/{user}', [MedicalRecordController::class, 'userRecords']);
Route::post('/medical-records', [MedicalRecordController::class, 'store']);
Route::match(['put','patch'], '/medical-records/{record}', [MedicalRecordController::class, 'update']);
Route::get('/medical-records/slug/{slug}', [MedicalRecordController::class, 'indexBySlug']);

  // AI Summary from pets
  // Apply 'web' middleware to enable session access from browser-based calls
  Route::middleware('web')->get('/ai/summary', [\App\Http\Controllers\Api\AiSummaryController::class, 'summary']);
  // POST keeps session (web) but skips CSRF so it can be called via fetch()
  Route::middleware('web')
      ->post('/ai/send-summary', [\App\Http\Controllers\Api\AiSummaryController::class, 'sendToDoctor'])
      ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Frontend session helpers (attach web middleware to enable session store)
Route::middleware('web')->group(function () {
    Route::get('/session/get', [\App\Http\Controllers\Api\SessionController::class, 'get']);
    Route::get('/session/login', [\App\Http\Controllers\Api\SessionController::class, 'loginWithUserIdGet']);

    
    // Allow POST without CSRF for programmatic use
    Route::post('/session/login', [\App\Http\Controllers\Api\SessionController::class, 'loginWithUserId'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/session/save', [\App\Http\Controllers\Api\SessionController::class, 'save'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/session/clear', [\App\Http\Controllers\Api\SessionController::class, 'clear'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    // User bookings via session (no Sanctum token required)
    Route::get('/my_bookings', [\App\Http\Controllers\Api\UserController::class, 'my_bookings']);
    Route::get('/my_booking/{id}', [\App\Http\Controllers\Api\UserController::class, 'my_booking']);
});

// Open pet extras update (no auth)
Route::put('/pets/{id}/extras', [\App\Http\Controllers\Api\UserController::class, 'petExtrasUpdate']);

use App\Http\Controllers\Api\UserOrdersController;
use App\Http\Controllers\Api\FeedbackWhatsAppController;
use App\Http\Controllers\Api\PrescriptionShareController;

Route::get('/users/{id}/orders', [UserOrdersController::class, 'index']);
Route::post('/consultation/feedback/send', [FeedbackWhatsAppController::class, 'send']);
Route::post('/consultation/feedback/send-by-pet-vet', [FeedbackWhatsAppController::class, 'sendByPetVet']);
Route::post('/consultation/prescription/send-doc', [PrescriptionShareController::class, 'sendLatest']);
Route::get('/consultation/prescription/pdf', [PrescriptionShareController::class, 'downloadLatest']);

// Night Video Coverage APIs
use App\Http\Controllers\Api\Video\DoctorVideoScheduleController as VDoctorVideoScheduleController;
use App\Http\Controllers\Api\Video\VideoSlotController as VVideoSlotController;
use App\Http\Controllers\Api\Video\CoverageController as VCoverageController;
use App\Http\Controllers\Api\Video\PincodeCoverageController as VPincodeCoverageController;
use App\Http\Controllers\Api\Video\RoutingController as VRoutingController;
use App\Http\Controllers\Api\Video\AdminController as VAdminController;

// routes/api.php
Route::get('/geo/pincodes', [\App\Http\Controllers\Api\GeoController::class, 'pincodes']);
use App\Http\Controllers\Api\LocationSlotsController;
use App\Http\Controllers\Api\GeoController;

Route::get('/geo/pincodes',        [GeoController::class, 'pincodes']);

// Dashboard profile APIs (session-aware)
Route::middleware('web')->prefix('dashboard/profile')->group(function () {
    Route::get('/', [DashboardProfileController::class, 'show'])->name('api.dashboard.profile.show');
    Route::put('/clinic', [DashboardProfileController::class, 'updateClinic'])
        ->name('api.dashboard.profile.clinic')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::put('/doctor/{doctor}', [DashboardProfileController::class, 'updateDoctor'])
        ->name('api.dashboard.profile.doctor')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::put('/password', [DashboardProfileController::class, 'updatePassword'])
        ->name('api.dashboard.profile.password')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
});

// These rely on session (user_id), so attach 'web' middleware to enable session cookies
Route::middleware('web')->group(function(){
    Route::get('/geo/nearest-pincode', [LocationSlotsController::class, 'nearestPincode']);
    Route::get('/video/slots/nearby',  [LocationSlotsController::class, 'openSlotsNear']);
    // New: pincode-based open slots (does not use geo_strips table)
    Route::get('/video/slots/nearby/pincode',  [LocationSlotsController::class, 'openSlotsByPincode']);
    Route::get('/debug/user-location', [LocationSlotsController::class, 'dumpUserLocation']);
    Route::get('/video/strip-for-pincode', [LocationSlotsController::class, 'stripForPincode']);
    Route::get('/video/nearest-strip', [LocationSlotsController::class, 'nearestStrip']);
});

// Public read-only endpoints for admin dashboard integration
Route::prefix('video')->group(function () {
    Route::get('/slots/open', [VVideoSlotController::class, 'openSlots']);
    Route::get('/slots/doctor', [VVideoSlotController::class, 'doctorSlots']);
    Route::get('/coverage', [VCoverageController::class, 'matrix']);
    Route::get('/pincode-coverage', [VPincodeCoverageController::class, 'matrix']);
    Route::post('/route', [VRoutingController::class, 'assign']);
});

// Protected write endpoints
Route::prefix('video')->group(function () {
    Route::get('/schedule/{doctor}', [VDoctorVideoScheduleController::class, 'show']);
    Route::post('/schedule/{doctor}', [VDoctorVideoScheduleController::class, 'storeOrUpdate']);
    Route::post('/schedule/{doctor}/toggle-247', [VDoctorVideoScheduleController::class, 'toggle247']);

    Route::post('/slots/{slot}/commit', [VVideoSlotController::class, 'commit']);
    Route::delete('/slots/{slot}/release', [VVideoSlotController::class, 'release']);
    Route::post('/slots/{slot}/checkin', [VVideoSlotController::class, 'checkin']);

    // Admin helper to publish tonight's slots
    Route::post('/admin/publish', [VAdminController::class, 'publish']);
    // Admin helper to reset video slot data
    Route::post('/admin/reset', [VAdminController::class, 'reset']);
});

Route::middleware('web')->get('/geo/strips', [\App\Http\Controllers\Api\GeoController::class, 'strips']);

Route::middleware('web')->get('/app/video/heatmap', function () {
    $doctors = \App\Models\Doctor::select('id','doctor_name')->orderBy('doctor_name')->get();
    return view('snoutiq.app-video-heatmap', compact('doctors'));
});

use App\Models\VideoSlot;
use Carbon\CarbonImmutable;

Route::get('/video/slots/doctor-test', function (\Illuminate\Http\Request $request) {
    $doctorId = (int) $request->query('doctor_id');
    $date     = (string) $request->query('date', now('Asia/Kolkata')->toDateString());
    $tz       = strtoupper((string) $request->query('tz', 'IST'));

    if (!$doctorId || !$date) {
        return response()->json(['error' => 'doctor_id and date are required'], 422);
    }

    // IST night hours ? UTC 13..23 + 0..6
    $utcNightHours = array_merge(range(13, 23), range(0, 6));

    $rows = VideoSlot::query()
        ->where('committed_doctor_id', $doctorId)
        ->where('slot_date', $date)
        ->whereIn('hour_24', $utcNightHours)
        ->whereIn('status', ['committed','in_progress','done'])
        ->orderBy('hour_24')
        ->orderBy('strip_id')
        ->orderByRaw("FIELD(role,'primary','bench')")
        ->get();

    $mapped = $rows->map(function ($r) {
        $istHour = ($r->hour_24 + 6) % 24;
        // ? safe parse (handles 'YYYY-MM-DD' or full timestamps)
        $istDate = CarbonImmutable::parse($r->slot_date, 'Asia/Kolkata');
        if ($istHour <= 6) {
            $istDate = $istDate->addDay();
        }

        return [
            'id' => $r->id,
            'strip_id' => $r->strip_id,
            'slot_date' => $r->slot_date,
            'hour_24' => $r->hour_24,
            'role' => $r->role,
            'status' => $r->status,
            'committed_doctor_id' => $r->committed_doctor_id,
            'ist_hour' => $istHour,
            'ist_datetime' => $istDate->setTime($istHour, 0)->format('Y-m-d H:i:s'),
        ];
    });

    return response()->json([
        'doctor_id' => $doctorId,
        'date'      => $date,
        'tz'        => $tz,
        'count'     => $mapped->count(),
        'slots'     => $mapped,
    ]);
});

// Transactions with user/device/pet info
Route::get('/transactions/with-user-data', [\App\Http\Controllers\Api\TransactionController::class, 'index']);
Route::get('/transactions/by-user', [\App\Http\Controllers\Api\TransactionController::class, 'byUser']);

Route::middleware([\App\Http\Middleware\FounderRequestLogger::class])->prefix('founder')->group(function () {
    Route::get('dashboard', [FounderDashboardController::class, 'index']);
    Route::get('clinics', [FounderClinicController::class, 'index']);
    Route::get('clinics/{clinic}', [FounderClinicController::class, 'show']);
    Route::get('sales', [FounderSalesController::class, 'index']);
    Route::get('revenue', [FounderRevenueController::class, 'index']);
    Route::get('alerts', [FounderAlertController::class, 'index']);
    Route::patch('alerts/{alert}/read', [FounderAlertController::class, 'markRead']);
    Route::patch('alerts/read-all', [FounderAlertController::class, 'markAllRead']);
    Route::get('settings', [FounderSettingController::class, 'show']);
    Route::patch('settings', [FounderSettingController::class, 'update']);
});
