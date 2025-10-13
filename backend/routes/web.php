<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VetLandingController;
use App\Models\Doctor;
use App\Http\Middleware\EnsureSessionUser;
use App\Http\Controllers\VideoSchedulePageController;

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
    // Optional editor (write-enabled) using separate table; not linked in sidebar by default
    Route::get('/doctor/video-calling-schedule/manage', [VideoSchedulePageController::class, 'editor'])
        ->name('doctor.video.schedule.manage');

    // Clinic order history (aggregates bookings across doctors of this clinic)
    Route::view('/clinic/orders', 'clinic.order-history')->name('clinic.orders');
});
