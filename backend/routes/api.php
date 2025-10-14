<?php 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
use App\Models\User;
use App\Http\Controllers\Auth\ForgotPasswordSimpleController;

use App\Http\Controllers\AdminController;
// use App\Http\Controllers\CallController;
// routes/api.php
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AgoraController;
use App\Http\Controllers\Api\CallController as ApiCallController; // handles lightweight requestCall
use App\Http\Controllers\CallController as CoreCallController;    // handles sessions + token

Route::post('/call/request', [ApiCallController::class, 'requestCall']);

use App\Http\Controllers\Api\VetController;

Route::get('/vets', [VetController::class, 'index']);        // All vets
Route::get('/vets/{id}', [VetController::class, 'show']);    // Single vet
Route::delete('/vets/{id}', [VetController::class, 'destroy']); // Delete vet


Route::get('/agora/appid', function () {
    return response()->json([
        'appId' => trim(env('AGORA_APP_ID')),
    ]);
});
// Call session info (patient/doctor polling)
Route::get('/call/{id}', [CoreCallController::class, 'show']);
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
Route::post('/call/{id}/accept', [CoreCallController::class, 'acceptCall']);
Route::post('/call/{id}/payment-success', [CoreCallController::class, 'paymentSuccess']);

Route::get('/users', [AdminController::class, 'getUsers']);
// Route::get('/vets', [AdminController::class, 'getVets']);

Route::get('/dog-breed/{breed}', [\App\Http\Controllers\Api\DogBreedController::class, 'getBreedImage']);
Route::get('/dog-breeds/all', [\App\Http\Controllers\Api\DogBreedController::class, 'allBreeds']);





use App\Http\Controllers\Api\GeminiChatController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\VideoCallingController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\DoctorStatusController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\ClinicsController;


Route::get('/weather/latest', [WeatherLogController::class, 'latest']);
Route::get('/weather/history', [WeatherLogController::class, 'history']);

Route::get('/weather/by-coords', [WeatherController::class, 'byCoords']);

Route::get('/weather/cities', [WeatherController::class, 'cities']);            // ?cities=Delhi,Mumbai,Bangalore
Route::get('/weather/detail', [WeatherController::class, 'detail']);            // ?city=Delhi
Route::get('/weather/hourly-schedule', [WeatherController::class, 'hourlySchedule']); // ?city=Delhi

// routes/api.php
Route::get('/nearby-vets', [VideoCallingController::class, 'nearbyVets']);

// ---- Prescriptions ----
Route::get('/prescriptions', [PrescriptionController::class, 'index']);
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
Route::get('/chat-rooms/new', [GeminiChatController::class, 'newRoom']); 
Route::post('/chat/send', [GeminiChatController::class, 'sendMessage']);
Route::get('/chat/listRooms', [GeminiChatController::class, 'listRooms']);
// routes/api.php
Route::get('/chat-rooms/{chat_room_token}/chats', [\App\Http\Controllers\Api\GeminiChatController::class, 'history']);

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

// Route::post('/send-otp', [AuthController::class, 'send_otp']);
// Route::post('/forgot-password', [ForgotPasswordSimpleController::class, 'sendNewPassword']);

// Route::post('/verify-otp', [AuthController::class, 'verify_otp']);
// Route::post('/login', [AuthController::class , 'login']);
// Route::post('/register', [AuthController::class, 'register']);





Route::post('/google-login', [AuthController::class, 'googleLogin']);
Route::post('/auth/register',   [AuthController::class, 'register']);
Route::post('/auth/initial-register', [AuthController::class, 'createInitialRegistration']);
Route::post('/auth/login',      [AuthController::class, 'login']);

Route::get('/auth/me',          [AuthController::class, 'me']);     // session check
Route::post('/auth/logout',     [AuthController::class, 'logout']); // invalidate

Route::get('/fetchNearbyPlaces', [PublicController::class, 'fetchNearbyPlaces']);


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
    Log::info('ðŸš€ Webhook received at ' . now());

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
Route::get('/doctors/{id}/bookings', [\App\Http\Controllers\Api\BookingsController::class, 'doctorBookings']);

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

// Clinics
Route::get('/clinics', [\App\Http\Controllers\Api\ClinicsController::class, 'index']);
Route::get('/clinics/{id}/doctors', [ClinicsController::class, 'doctors']);
Route::post('/clinics/{id}/doctors', [ClinicsController::class, 'storeDoctor']);
Route::get('/clinics/{id}/availability', [\App\Http\Controllers\Api\ClinicsController::class, 'availability']);
Route::get('/doctors/{id}/availability', [\App\Http\Controllers\Api\DoctorScheduleController::class, 'getAvailability']);
// --- Separate video-only scheduling API (new table) ---
Route::put('/video-schedule/doctors/{id}/availability', [\App\Http\Controllers\Api\DoctorVideoScheduleController::class, 'updateAvailability']);
Route::get('/video-schedule/doctors/{id}/availability', [\App\Http\Controllers\Api\DoctorVideoScheduleController::class, 'getAvailability']);
Route::get('/video-schedule/doctors/{id}/free-slots', [\App\Http\Controllers\Api\DoctorVideoScheduleController::class, 'freeSlots']);
// Pets
Route::get('/users/{id}/pets', [\App\Http\Controllers\Api\PetsController::class, 'byUser']);

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
