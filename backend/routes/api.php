<?php 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App
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
use App\Http\Controllers\Api\CallController;

Route::post('/call/request', [CallController::class, 'requestCall']);




Route::get('/agora/appid', function () {
    return response()->json([
        'appId' => trim(env('AGORA_APP_ID')),
    ]);
});
Route::get('/call/{id}', [CallController::class, 'show']);
// routes/web.php (ya api.php)
Route::get('/debug/pusher', function () {
    return [
        'driver'  => config('broadcasting.default'),
        'key'     => config('broadcasting.connections.pusher.key'),
        'app_id'  => config('broadcasting.connections.pusher.app_id'),
        'cluster' => config('broadcasting.connections.pusher.options.cluster'),
    ];
});


Route::post('/create-order', [PaymentController::class, 'createOrder']);

Route::post('/call/create', [CallController::class, 'createSession']);
Route::post('/call/{id}/accept', [CallController::class, 'acceptCall']);
Route::post('/call/{id}/payment-success', [CallController::class, 'paymentSuccess']);

Route::get('/users', [AdminController::class, 'getUsers']);
Route::get('/vets', [AdminController::class, 'getVets']);

Route::get('/dog-breed/{breed}', [\App\Http\Controllers\Api\DogBreedController::class, 'getBreedImage']);
Route::get('/dog-breeds/all', [\App\Http\Controllers\Api\DogBreedController::class, 'allBreeds']);





use App\Http\Controllers\Api\GeminiChatController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\VideoCallingController;
use App\Http\Controllers\Api\WeatherController;


Route::get('/weather/latest', [WeatherLogController::class, 'latest']);
Route::get('/weather/history', [WeatherLogController::class, 'history']);

Route::get('/weather/by-coords', [WeatherController::class, 'byCoords']);

Route::get('/weather/cities', [WeatherController::class, 'cities']);            // ?cities=Delhi,Mumbai,Bangalore
Route::get('/weather/detail', [WeatherController::class, 'detail']);            // ?city=Delhi
Route::get('/weather/hourly-schedule', [WeatherController::class, 'hourlySchedule']); // ?city=Delhi

// routes/api.php
Route::get('/nearby-vets', [VideoCallingController::class, 'nearbyVets']);
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
Route::get('/chats', [GeminiChatController::class, 'history']);
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





//Route::post('/auth/send-otp',   [AuthController::class, 'send_otp']);
//Route::post('/auth/verify-otp', [AuthController::class, 'verify_otp']);
Route::post('/google-login', [AuthController::class, 'googleLogin']);
Route::post('/auth/register',   [AuthController::class, 'register']);
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
Route::get('/vets', function () {
    $vets = DB::table('vet_registerations_temp')->get();
    return response()->json($vets);
});

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
 Route::post('/service', [GroomerServiceController::class, 'store']);
    Route::get('/service/{id}', [GroomerServiceController::class, 'view']);
    Route::get('/service/{id}/edit', [GroomerServiceController::class, 'edit']);
    Route::post('/service/{id}/update', [GroomerServiceController::class, 'update']);

      Route::get('/employees', [GroomerEmployeeController::class, 'get']);
    Route::post('/employees', [GroomerEmployeeController::class, 'store']);
      Route::get('/employees/{id}', [GroomerEmployeeController::class, 'show']);
    Route::put('/employees/{id}', [GroomerEmployeeController::class, 'update']);

          Route::post('/block_time', [GroomerCalenderController::class, 'store_blockTime']);
          Route::post('/booked_times', [GroomerCalenderController::class, 'booked_times']);
          Route::get('/clients', [GroomerClientController::class, 'get']);
          Route::get('/clients/{id}', [GroomerClientController::class, 'single']);
          Route::put('/clients/{id}', [GroomerClientController::class, 'update']);
          Route::post('/clients', [GroomerClientController::class, 'store']);

          Route::post('/store_booking', [GroomerCalenderController::class, 'store_booking']);
          Route::get('/bookings', [GroomerCalenderController::class, 'bookings']);
          Route::get('/bookings-v2', [GroomerCalenderController::class, 'bookingsV2']);
          Route::get('/bookings/{id}', [GroomerCalenderController::class, 'booking_single']);
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

// routes/api.php
Route::post('/agora/token', [CallController::class, 'generateToken']);
