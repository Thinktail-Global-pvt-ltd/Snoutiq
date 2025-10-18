<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VetLandingController;
use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use App\Models\Payment;
use App\Http\Middleware\EnsureSessionUser;
use App\Http\Controllers\VideoSchedulePageController;
use App\Http\Controllers\VideoScheduleTestPageController;

// Public routes
Route::get('/custom-doctor-login', function () { return view('custom-doctor-login'); })->name('custom-doctor-login');
Route::get('/logout', function (\Illuminate\Http\Request $request) {
    $request->session()->flush();
    return redirect()->route('custom-doctor-login');
})->name('logout');
Route::get('/vets/{slug}', [VetLandingController::class, 'show']);

// Video consult entry points (public views)
// Patient-facing lobby to pick a doctor and place a call
Route::get('/chat', function () {
    return view('chat');
})->name('video.chat');

// Friendly alias used from clinic landing: /video?vet_slug=...
Route::get('/video', function () {
    return view('chat');
})->name('video.alias');

// Actual call room (Agora join page). Channel param is optional to allow manual testing
Route::get('/call-page/{channel?}', function () {
    return view('call-page');
})->name('video.call');

// Payment page for video calls (public)
// Example: /payment/{callId}?doctorId=2&channel=channel_xxx&patientId=4&amount=499
Route::get('/payment/{callId}', function (string $callId) {
    $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
    return view('payment', compact('callId','socketUrl'));
})->name('video.payment');

// Protected application routes (requires session user)
Route::middleware([EnsureSessionUser::class])->group(function(){
    // Dashboards
    Route::get('/doctor', function () {
        $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
        $doctorId  = auth()->id() ?? 301;
        return view('doctor-dashboard', compact('socketUrl','doctorId'));
    })->name('doctor.dashboard');
    // Lightweight live console (single view) for receiving calls
    Route::view('/doctor/live', 'doctor.live-console')->name('doctor.live');
    // Clinic dashboard shell (links to doctor console)
    Route::view('/clinic-dashboard', 'clinic-dashboard')->name('clinic.dashboard');
    Route::view('/clinic/doctors', 'clinic.doctors')->name('clinic.doctors');
    Route::view('/dashboard/services', 'groomer.services.index')->name('groomer.services.index');

    // Booking flow
    Route::view('/booking/clinics', 'booking.clinics')->name('booking.clinics');
    Route::get('/booking/clinic/{id}/doctors', function (int $id) {
        return view('booking.clinic-doctors', ['clinicId' => $id]);
    })->name('booking.clinic.doctors');
    Route::get('/booking/clinic/{id}/book', function (\Illuminate\Http\Request $request, int $id) {
        $doctorId = (int) $request->query('doctorId', 0);
        return view('booking.schedule', [
            'presetClinicId' => $id,
            'presetDoctorId' => $doctorId,
            // Default service for clinic booking flow
            'presetServiceType' => 'in_clinic',
        ]);
    })->name('booking.clinic.book');

    // User + doctor pages
    Route::get('/user/bookings', function () { return view('user.bookings'); })->name('user.bookings');
    Route::get('/my-bookings', function () { return view('user.bookings'); })->name('user.mybookings');
    Route::get('/doctor/bookings', function () { return view('doctor.bookings'); })->name('doctor.bookings');
    Route::get('/doctor/booking/{id}', function (int $id) {
        return view('doctor.booking-detail', ['bookingId' => $id]);
    })->name('doctor.booking.detail');

    // Weekly schedule (existing, secure)
    Route::get('/doctor/schedule', function () {
        $vetId = session('user_id') ?? data_get(session('user'), 'id');
        $doctors = collect();
        if ($vetId) {
            $doctors = Doctor::where('vet_registeration_id', $vetId)
                ->orderBy('doctor_name')
                ->get(['id','doctor_name']);
        }
        return view('snoutiq.provider-schedule', compact('doctors', 'vetId'));
    })->name('doctor.schedule');

    // New: Pet parent page using separate table & API (read-only)
    Route::get('/pet/video-calling-schedule', [VideoSchedulePageController::class, 'petIndex'])
        ->name('pet.video.schedule');
    // Test flow (read-only)
    Route::get('/pet/video-calling-test', [VideoScheduleTestPageController::class, 'petIndex'])
        ->name('pet.video.schedule.test');
    // Optional editor (write-enabled) using separate table; not linked in sidebar by default
    Route::get('/doctor/video-calling-schedule/manage', [VideoSchedulePageController::class, 'editor'])
        ->name('doctor.video.schedule.manage');
    // Test flow editor (write-enabled)
    Route::get('/doctor/video-calling-test/manage', [VideoScheduleTestPageController::class, 'editor'])
        ->name('doctor.video.schedule.test.manage');

    // Clinic order history (aggregates bookings across doctors of this clinic)
    Route::view('/clinic/orders', 'clinic.order-history')->name('clinic.orders');
    // Clinic payments view (lists Razorpay payments linked via vet_slug in notes)
    Route::get('/clinic/payments', function () {
        $vetId = session('user_id') ?? data_get(session('user'), 'id');
        $vet   = null; $slug = null;
        if ($vetId) {
            $vet = VetRegisterationTemp::find($vetId);
            $slug = $vet?->slug;
        }

        $payments = Payment::query()
            ->when($slug, function ($q) use ($slug) {
                $q->where('notes->vet_slug', $slug);
            })
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        return view('clinic.payments', compact('payments','vet','slug','vetId'));
    })->name('clinic.payments');

    // Booking payments from bookings table (filtered by this clinic)
    Route::view('/clinic/booking-payments', 'clinic.booking-payments')->name('clinic.booking.payments');
});

use Illuminate\Support\Facades\DB;


// Route::get('/debug/user-location', function () {
//     // Get session user_id
//     $userId = session('user_id');
//     if (!$userId) {
//         return response()->json(['error' => 'No session user_id found'], 401);
//     }

//     // Fetch coordinates from vet_registerations_temp
//     $row = DB::table('vet_registerations_temp')
//         ->where('id', $userId)
//         ->select('lat', 'lng')
//         ->first();

//     if (!$row) {
//         return response()->json(['error' => 'User not found in vet_registerations_temp'], 404);
//     }

//     // Optional: find nearest pincode
//     $nearest = DB::selectOne("
//         SELECT pincode AS code, label AS name, lat, lon,
//         (6371 * ACOS(
//            LEAST(1, COS(RADIANS(?)) * COS(RADIANS(lat)) * COS(RADIANS(lon - ?))
//                    + SIN(RADIANS(?)) * SIN(RADIANS(lat)))
//         )) AS km
//         FROM geo_pincodes
//         WHERE active = 1 AND city = 'Gurugram'
//         ORDER BY km ASC
//         LIMIT 1
//     ", [$row->lat, $row->lng, $row->lat]);

//     // Dump in JSON
//     return response()->json([
//         'user_id' => $userId,
//         'lat'     => $row->lat,
//         'lng'     => $row->lng,
//         'nearest_pincode' => $nearest,
//         'timestamp' => now()->toDateTimeString(),
//     ]);
// });

  Route::middleware(['web'])->get('/api/geo/nearest-pincode', function (\Illuminate\Http\Request $request) {
    // Prefer frontend-provided user_id (query/header), fallback to PHP session
    $userId = $request->query('user_id')
        ?: $request->header('X-User-Id')
        ?: $request->session()->get('user_id');
    if (!$userId) {
        return response()->json(['error' => 'No session user_id found'], 401);
    }

    $row = DB::table('vet_registerations_temp')
        ->where('id', $userId)
        ->select('lat', 'lng')
        ->first();

    if (!$row) {
        return response()->json(['error' => 'User not found in vet_registerations_temp'], 404);
    }

    // nearest pincode in Gurugram
    $nearest = DB::selectOne("
        SELECT pincode AS code, label AS name, lat, lon,
        (6371 * ACOS(
          LEAST(1, COS(RADIANS(?)) * COS(RADIANS(lat)) * COS(RADIANS(lon - ?))
                 + SIN(RADIANS(?)) * SIN(RADIANS(lat)))
        )) AS km
        FROM geo_pincodes
        WHERE active = 1 AND city = 'Gurugram'
        ORDER BY km ASC
        LIMIT 1
    ", [$row->lat, $row->lng, $row->lat]);

    return response()->json([
        'coords'  => ['lat' => $row->lat, 'lon' => $row->lng],
        'pincode' => $nearest,
    ]);
});

// Simple UI to test /api/video/slots/doctor
Route::middleware('web')->get('/dev/api-test/doctor-slots', function () {
    $doctors = DB::table('doctors')->select('id','doctor_name')->orderBy('doctor_name')->get();
    return view('snoutiq.api-test-doctor-slots', compact('doctors'));
})->name('dev.api.test.doctor_slots');

Route::middleware('web')->get('/video/app/night-coverage', function () {
    // Pull a lightweight list of doctors. Adjust the source if your table/model differs.
    $doctors = DB::table('doctors')
        ->select('id', 'doctor_name')
        ->orderBy('doctor_name')
        ->get();

    // Readonly UI; we just need the doctor select visible.
    return view('snoutiq.app-video-night-coverage', [
        'doctors'  => $doctors,
        'readonly' => true,
        'page_title' => 'Night Video Coverage',
    ]);
});
// routes/web.php
Route::get('/backend/video/night/edit', function (\Illuminate\Http\Request $req) {
    return view('snoutiq.video-calling-night-edit', [
        'doctorId' => (int) $req->query('doctor_id', auth()->id() ?? 0),
        'userId'   => (int) $req->query('user_id',   auth()->id() ?? 0),
        'date'     => $req->query('date'),
        // 'doctors' => \App\Models\Doctor::select('id','name')->get(), // optional
    ]);
})->name('video.night.edit');
