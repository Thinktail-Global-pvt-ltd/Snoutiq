<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VetLandingController;
use App\Models\Doctor;

// Only keep routes referenced by the dashboard layout,
// plus vets/{slug} and custom-login. Everything else removed.

// doctor.dashboard
Route::get('/doctor', function () {
    $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
    $doctorId  = auth()->id() ?? 301;
    return view('doctor-dashboard', compact('socketUrl','doctorId'));
})->name('doctor.dashboard');

// groomer.services.index
Route::view('/dashboard/services', 'groomer.services.index')->name('groomer.services.index');

// booking.clinics
Route::view('/booking/clinics', 'booking.clinics')->name('booking.clinics');

// booking.clinic.doctors
Route::get('/booking/clinic/{id}/doctors', function (int $id) {
    return view('booking.clinic-doctors', ['clinicId' => $id]);
})->name('booking.clinic.doctors');

// booking.clinic.book -> opens unified schedule with preset clinic/doctor
Route::get('/booking/clinic/{id}/book', function (\Illuminate\Http\Request $request, int $id) {
    $doctorId = (int) $request->query('doctorId', 0);
    return view('booking.schedule', [
        'presetClinicId' => $id,
        'presetDoctorId' => $doctorId,
    ]);
})->name('booking.clinic.book');

// user.bookings
Route::get('/user/bookings', function () { return view('user.bookings'); })->name('user.bookings');

// doctor.bookings
Route::get('/doctor/bookings', function () { return view('doctor.bookings'); })->name('doctor.bookings');

// doctor.booking.detail
Route::get('/doctor/booking/{id}', function (int $id) {
    return view('doctor.booking-detail', ['bookingId' => $id]);
})->name('doctor.booking.detail');

// doctor.schedule
Route::get('/doctor/schedule', function () {
    // Optional: load doctors for the logged-in vet if session is present
    $vetId = session('user_id') ?? data_get(session('user'), 'id');
    $doctors = collect();
    if ($vetId) {
        $doctors = Doctor::where('vet_registeration_id', $vetId)
            ->orderBy('doctor_name')
            ->get(['id','doctor_name']);
    }
    return view('snoutiq.provider-schedule', compact('doctors', 'vetId'));
})->name('doctor.schedule');

// custom-login (custom-doctor-login view)
Route::get('/custom-doctor-login', function () { return view('custom-doctor-login'); })->name('custom-doctor-login');

// vets/{slug}
Route::get('/vets/{slug}', [VetLandingController::class, 'show']);
