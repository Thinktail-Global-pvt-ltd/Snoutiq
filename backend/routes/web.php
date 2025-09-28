<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Controllers\TestControlelr;
use App\Http\Controllers\VetLandingController;
Route::get('/import-vets', [TestControlelr::class, 'importPdfData']);


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

Route::get('/', function () {
 
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/admin/users', [App\Http\Controllers\HomeController::class, 'users'])->name('admin.users');
Route::get('/admin/sp/{id}', [App\Http\Controllers\HomeController::class, 'sp_profile'])->name('admin.sp_profile');
Route::get('/admin/bookings', [App\Http\Controllers\HomeController::class, 'bookings'])->name('admin.bookings');


Route::get('/admin/supports', [App\Http\Controllers\HomeController::class, 'supports'])->name('admin.supports');


;



Route::get('/vet/{slug}', [VetLandingController::class, 'show'])
     ->name('vet.landing');



Route::get('/custom-doctor-register', function () {
    return view('custom-register-doctor');
})->name('custom-doctor-register');


Route::get('/dashboard', function () {
    return view('dashboard'); // yeh resources/views/dashboard.blade.php ko load karega
})->name('dashboard');



Route::get('/vet/register', function () {
    return view('register'); 
})->name('register');


use App\Http\Controllers\ChatController;




// If you want to test the socket separately
Route::get('/socket-test', function () {
    return view('socket-test');
});

// --- Patient (chat) ---
Route::get('/chat', function () {
    $patientId = auth()->id() ?? 101;

    // Demo doctors (replace with DB query if you have one)
    $nearbyDoctors = collect([
        (object)['id' => 501, 'name' => 'Dr. Demo One'],
        (object)['id' => 502, 'name' => 'Dr. Demo Two'],
    ]);

    $nearbyDoctorsForJs = $nearbyDoctors->map(fn($d) => [
        'id' => $d->id, 'name' => $d->name
    ])->values();

    $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');

    return view('chat', compact('patientId', 'nearbyDoctors', 'nearbyDoctorsForJs', 'socketUrl'));
})->name('patient.chat');

// --- Doctor dashboard ---
Route::get('/doctor', function (\Illuminate\Http\Request $request) {
    $doctorId  = (int) $request->query('doctorId', 501);
    $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
    return view('doctor-dashboard', compact('socketUrl','doctorId'));
})->name('doctor.dashboard');

// --- Payment + Call page (shared) ---
Route::get('/payment/{callId}', function (string $callId) {
    $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
    return view('payment', compact('callId','socketUrl'));
})->name('payment.show');

Route::get('/call-page/{channel}', function (\Illuminate\Http\Request $request, string $channel) {
    $uid    = $request->query('uid');
    $role   = $request->query('role');
    $callId = $request->query('callId');
    return view('call-page', compact('channel','uid','role','callId'));
})->name('call.show');





