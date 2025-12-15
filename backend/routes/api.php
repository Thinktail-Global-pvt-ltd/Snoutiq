<?php 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
// use App
use App\Http\Controllers\Api\UnifiedIntelligenceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\Groomer\ProfileController as GroomerProfileController;
use App\Http\Controllers\Api\Groomer\ServiceCategoryController as GroomerServiceCategoryController;
use App\Http\Controllers\Api\Groomer\ServiceController as GroomerServiceController;
use App\Http\Controllers\Api\Groomer\GroomerEmployeeController as GroomerEmployeeController;
use App\Http\Controllers\Api\Groomer\CalenderController as GroomerCalenderController;
use App\Http\Controllers\Api\Groomer\ClientController as GroomerClientController;
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
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\SalesDashboardController;
use App\Http\Controllers\Api\AppointmentSubmissionController;
use App\Http\Controllers\Api\DashboardProfileController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use App\Support\DeviceTokenOwnerResolver;
use App\Http\Controllers\Auth\ForgotPasswordSimpleController;

use App\Http\Controllers\AdminController;
// use App\Http\Controllers\CallController;
// routes/api.php
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AgoraController;
use App\Http\Controllers\Api\CallController as ApiCallController; // handles lightweight requestCall
use App\Http\Controllers\CallController as CoreCallController;    // handles sessions + token
use App\Http\Controllers\Api\CallRecordingController;
use App\Http\Controllers\Api\RecordingUploadController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\ReceptionistBookingController;
use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\WhatsAppMessageController;

Route::post('/call/request', [ApiCallController::class, 'requestCall']);
Route::post('/call/test', [ApiCallController::class, 'requestTestCall']);
Route::post('/call-recordings/upload', [RecordingUploadController::class, 'store']);
Route::post('/error-logs', [ErrorLogController::class, 'store'])->name('api.error-logs.store');
Route::post('/whatsapp/send', [WhatsAppMessageController::class, 'send']);

use App\Http\Controllers\Api\VetController;
use App\Http\Controllers\Api\AdminOnboardingStatusController;
use App\Http\Controllers\Api\VetLeadController;

Route::get('/vets', [VetController::class, 'index']);        // All vets
Route::get('/vets/by-referral/{code}', [VetController::class, 'showByReferral'])->name('api.vets.by-referral');
Route::get('/vets/{id}', [VetController::class, 'show']);    // Single vet
Route::delete('/vets/{id}', [VetController::class, 'destroy']); // Delete vet
Route::post('/vet-leads', [VetLeadController::class, 'store']);

Route::prefix('admin/onboarding')->group(function () {
    Route::get('/services', [AdminOnboardingStatusController::class, 'services']);
    Route::get('/video', [AdminOnboardingStatusController::class, 'video']);
    Route::get('/clinic-hours', [AdminOnboardingStatusController::class, 'clinicHours']);
    Route::get('/emergency', [AdminOnboardingStatusController::class, 'emergency']);
});


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
        ],
    ]);
});

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

Route::post('/appointments/submit', [AppointmentSubmissionController::class, 'store'])
    ->name('api.appointments.submit');
Route::get('/appointments/by-doctor/{doctor}', [AppointmentSubmissionController::class, 'listByDoctor'])
    ->name('api.appointments.by-doctor');
Route::get('/appointments/by-user/{user}', [AppointmentSubmissionController::class, 'listByUser'])
    ->name('api.appointments.by-user');
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





// use App\Http\Controllers\Api\ActiveDoctorController;
use App\Http\Controllers\Api\GeminiChatController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\VideoCallingController;
use App\Http\Controllers\Api\WeatherController;
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
// Route::get('/active-doctors', ActiveDoctorController::class);

// ---- Prescriptions ----
Route::get('/prescriptions', [PrescriptionController::class, 'index']);
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
Route::middleware('auth:sanctum')->get('/user/data', function (Request $Request) {
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
});

Route::post('/send-otp', [AuthController::class, 'send_otp']);
// Route::post('/forgot-password', [ForgotPasswordSimpleController::class, 'sendNewPassword']);

Route::post('/verify-otp', [AuthController::class, 'verify_otp']);
// Route::post('/login', [AuthController::class , 'login']);
// Route::post('/register', [AuthController::class, 'register']);





Route::post('/google-login', [AuthController::class, 'googleLogin']);
Route::post('/auth/register',   [AuthController::class, 'register']);
Route::post('/auth/initial-register', [AuthController::class, 'createInitialRegistration']);
Route::post('/auth/login',      [AuthController::class, 'login']);
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
Route::prefix('groomer')->middleware('auth:sanctum')->group(function () {
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


Route::prefix('user')->middleware('auth:sanctum')->group(function () {




    
    Route::get('/profile', [UserController::class, 'profile']);
  Route::get('/my_bookings', [UserController::class, 'my_bookings']);
    Route::get('/my_booking/{id}', [UserController::class, 'my_booking']);
     Route::post('/profile', [UserController::class, 'profile_update']);
     Route::post('/add_pet', [UserController::class, 'add_pet']);
     Route::get('/my_pets', [UserController::class, 'my_pets']);
     Route::get('/pet/{id}', [UserController::class, 'pet_profile']);
     Route::post('/pet/{id}', [UserController::class, 'pet_update']);

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

      Route::middleware('auth:sanctum')->post('/chats/startChat', [ChatController::class, 'startChat']);
      Route::middleware('auth:sanctum')->post('/chats/myMessages', [ChatController::class, 'myMessages']);
      Route::middleware('auth:sanctum')->post('/chats/chatHistory', [ChatController::class, 'chatHistory']);
      Route::middleware('auth:sanctum')->post('/chats/sendMessage', [ChatController::class, 'sendMessage']);



    Route::middleware('auth:sanctum')->post('/support/sendMessage', [SupportController::class, 'store']);
    Route::middleware('auth:sanctum')->post('/support/mydata', [SupportController::class, 'mydata']);

});


use Illuminate\Support\Facades\Log;

Route::post('/webhook/deploy', function () {
    Log::info('🚀 Webhook received at ' . now());

    exec('bash /var/www/deploy.sh 2>&1', $output, $returnCode);

    Log::info('Webhook Output:', $output);
    Log::info('Webhook Exit Code: ' . $returnCode);

    return response()->json(['status' => 'ok']);
});

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

Route::prefix('staff')->group(function () {
    Route::get('/', [StaffController::class, 'index']);
    Route::post('/receptionists', [StaffController::class, 'storeReceptionist']);
    Route::patch('/{type}/{id}/role', [StaffController::class, 'updateRole'])
        ->whereIn('type', ['doctor', 'receptionist']);
});

Route::prefix('receptionist')->group(function () {
    Route::get('/bookings', [ReceptionistBookingController::class, 'bookings']);
    Route::post('/bookings', [ReceptionistBookingController::class, 'storeBooking']);
    Route::get('/patients', [ReceptionistBookingController::class, 'patients']);
    Route::post('/patients', [ReceptionistBookingController::class, 'storePatient']);
    Route::get('/patients/{user}/pets', [ReceptionistBookingController::class, 'patientPets']);
    Route::get('/doctors', [ReceptionistBookingController::class, 'doctors']);
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
Route::get('/users/{id}',       [AdminController::class, 'getUser']);
Route::put('/users/{id}',       [AdminController::class, 'updateUser']);
Route::delete('/users/{id}',    [AdminController::class, 'deleteUser']);

Route::get('/vets',             [AdminController::class, 'getVets']);

Route::get('/users/{userId}/pets',  [AdminController::class, 'listPets']);
Route::post('/users/{userId}/pets', [AdminController::class, 'addPet']);
Route::get('/pets/{petId}',         [AdminController::class, 'getPet']);
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
Route::get('/doctors/{id}/schedules/combined', [\App\Http\Controllers\Api\DoctorScheduleSummaryController::class, 'show']);

// Clinics
Route::get('/clinics', [\App\Http\Controllers\Api\ClinicsController::class, 'index']);
Route::get('/clinics/services', [ClinicsController::class, 'servicesByClinicId']);
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
Route::get('/medical-records/slug/{slug}', [MedicalRecordController::class, 'indexBySlug']);

  // AI Summary from chats
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

use App\Http\Controllers\Api\UserOrdersController;

Route::get('/users/{id}/orders', [UserOrdersController::class, 'index']);

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
