<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Controllers\TestControlelr;
use App\Http\Controllers\VetLandingController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BusinessHourController;
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



// placeholder: dynamic vet landing route moved below test-store

// Static services & pricing JSON (served via Blade view)
Route::get('/vet/services-pricing/static', function () {
    $data = [
        'chat_price' => 399,
        'video_consultation_price' => 499,
        'business_hours' => [
            'Monday'    => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'Tuesday'   => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'Wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'Thursday'  => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'Friday'    => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'Saturday'  => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
            'Sunday'    => ['open' => '00:00', 'close' => '00:00', 'closed' => true],
        ],
    ];

    return response()
        ->view('vet.services-pricing-static', ['data' => $data])
        ->header('Content-Type', 'application/json');
})->name('vet.services_pricing.static');

// Static clinic hours view (UI only)
Route::view('/clinic/hours', 'clinic.business-hours')->name('clinic.hours');
Route::post('/clinic/hours/save', [BusinessHourController::class, 'save'])->name('clinic.hours.save');

// Appointment booking (view + submit)
Route::view('/appointments/book', 'appointments.book')->name('appointments.book');
Route::post('/appointments/book', [AppointmentController::class, 'store'])->name('appointments.store');

// Quick test page to create a vet and follow redirect to its slug
Route::get('/vet/test-store', function () {
    return view('vet.test-store');
})->name('vet.test-store');
Route::post('/vet/test-store', [\App\Http\Controllers\Api\VetRegisterationTempController::class, 'store'])
    ->name('vet.test-store.submit');



Route::get('/custom-doctor-register', function () {
    return view('custom-register-doctor');
})->name('custom-doctor-register');

Route::get('/custom-doctor-login', function () {
    return view('custom-doctor-login');
})->name('custom-doctor-login');


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

Route::get('/pet-dashboard', fn () => view('pet-dashboard'))->name('pet-dashboard');
Route::get('/clinic-dashboard', fn () => view('clinic-dashboard'))
    ->name('clinic-dashboard');





    Route::view('/dashboard/services', 'groomer.services.index')->name('groomer.services.index');

     use App\Http\Controllers\Api\Groomer\ClinicReelController;


Route::get('/backend/groomer/reels',            [ClinicReelController::class, 'get']);            // list reels ?user_id= or ?vet_slug=
Route::get('/backend/groomer/reel/{id}',        [ClinicReelController::class, 'show']);           // view single
Route::post('/backend/groomer/reel',            [ClinicReelController::class, 'store']);          // create
Route::post('/backend/groomer/reel/{id}/update',[ClinicReelController::class, 'update']);         // update
Route::delete('/backend/groomer/reel/{id}',     [ClinicReelController::class, 'destroy']);        // delete


use App\Http\Controllers\BlogController;
use App\Http\Controllers\Admin\PostController as AdminPost;
use App\Http\Controllers\Admin\CategoryController as AdminCategory;
use App\Http\Controllers\Admin\TagController as AdminTag;
use App\Http\Controllers\Admin\UploadController;

// Everything under /blogs — ALL PUBLIC
Route::prefix('blogs')->group(function () {
    // Frontend
    Route::get('/', [BlogController::class,'index'])->name('blog.index');
    Route::get('/post/{post:slug}', [BlogController::class,'show'])->name('blog.post');
    Route::get('/category/{category:slug}', [BlogController::class,'category'])->name('blog.category');
    Route::get('/tag/{tag:slug}', [BlogController::class,'tag'])->name('blog.tag');
    Route::get('/sitemap.xml', [BlogController::class,'sitemap']);
    Route::get('/feed', [BlogController::class,'feed']);

    // “Admin” CRUD — now PUBLIC
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('posts', AdminPost::class);
        Route::resource('categories', AdminCategory::class)->except('show');
        Route::resource('tags', AdminTag::class)->except('show');
        Route::post('upload', [UploadController::class,'store'])->name('upload.store');
    });
});

// Dynamic vet landing route (kept near end to avoid conflicts with specific routes)
Route::get('/vet/{slug}', [VetLandingController::class, 'show'])
     ->name('vet.landing');

// Optional: redirect root to /blogs
Route::redirect('/', '/blogs');

// Fallback static file server for storage if symlink is missing (dev convenience)
// Access: /storage/{path} -> storage/app/public/{path}
Route::get('/storage/{path}', function ($path) {
    $full = storage_path('app/public/' . $path);
    if (!file_exists($full)) {
        abort(404);
    }
    return response()->file($full);
})->where('path', '.*');
