<?php


use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Http\Controllers\VetLandingController;
use App\Http\Controllers\Admin\AdminOnboardingStatusPageController;
use App\Http\Controllers\Admin\AdminVideoSlotOverviewController;
use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use App\Models\Payment;
use App\Http\Middleware\EnsureSessionUser;
use App\Http\Controllers\VideoSchedulePageController;
use App\Http\Controllers\VideoScheduleTestPageController;
use App\Http\Controllers\EmergencyHoursPageController;
use App\Http\Controllers\Api\ClinicEmergencyHoursController;
use App\Http\Controllers\VetDocumentsPageController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\SalesDraftClinicPageController;
use App\Http\Controllers\Admin\LegacyQrRedirectAdminController;
use App\Http\Controllers\LegacyQrRedirectController;
use App\Http\Controllers\SalesCrmController;
use App\Http\Controllers\Api\SalesDashboardController;
use App\Http\Middleware\EnsureSalesAuthenticated;
use App\Http\Controllers\QrTrackingController;
use App\Http\Controllers\PushSchedulerController;
use App\Models\LegacyQrRedirect;


// Public routes

Route::redirect('/', '/admin/login');

Route::get('/clinics/drafts/create', SalesDraftClinicPageController::class)->name('backend.clinics.drafts.create');
Route::get('/backend/legacy-qr/{code}', LegacyQrRedirectController::class)->name('legacy-qr.redirect');
Route::get('/legacy-qr/{code}', function (Request $request, string $code) {
    return redirect()->route('legacy-qr.redirect', ['code' => $code] + $request->query());
});
Route::get('/sales/login', function (Request $request) {
    if ($request->session()->get('sales_authenticated')) {
        return redirect()->route('sales.crm');
    }

    return view('backend.sales.login');
})->name('sales.login');

Route::post('/sales/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $email = strtolower(trim($credentials['email']));
    $password = trim($credentials['password']);

    $expectedEmail = 'sales@admin.com';
    $expectedPassword = 'Sales@12345';

    if ($email !== $expectedEmail || ! hash_equals($expectedPassword, $password)) {
        return back()
            ->withErrors(['email' => 'Invalid sales credentials.'])
            ->withInput(['email' => $email]);
    }

    $request->session()->regenerate();
    $request->session()->put('sales_authenticated', true);

    return redirect()->route('sales.crm');
})->name('sales.login.attempt');

Route::post('/sales/logout', function (Request $request) {
    $request->session()->forget('sales_authenticated');
    $request->session()->regenerateToken();

    return redirect()->route('sales.login')->with('status', 'Logged out successfully.');
})->name('sales.logout');

Route::middleware([EnsureSalesAuthenticated::class])->group(function () {
    Route::get('/sales', [SalesCrmController::class, 'index'])->name('sales.crm');
    Route::get('/sales/dashboard', [SalesDashboardController::class, 'page'])->name('sales.dashboard');
    Route::post('/sales/legacy-qr', [SalesCrmController::class, 'storeLegacyQr'])->name('sales.legacy-qr.store');
    Route::delete('/sales/legacy-qr/{legacyQrRedirect}', [SalesCrmController::class, 'destroyLegacyQr'])->name('sales.legacy-qr.destroy');
});

// Developer utilities
Route::view('/dev/fcm-test', 'fcm.test')->name('dev.fcm-test');
Route::get('/dev/api-documentation', function () {
    $docPath = resource_path('docs/api_documentation.md');

    if (! File::exists($docPath)) {
        abort(404, 'API documentation file not found.');
    }

    $markdown = File::get($docPath);
    $documentationHtml = Str::markdown($markdown, [
        'html_input' => 'allow',
        'allow_unsafe_links' => false,
    ]);
    $lastUpdated = Carbon::createFromTimestamp(File::lastModified($docPath));

    return view('documentation.api', [
        'documentationHtml' => $documentationHtml,
        'lastUpdatedHuman' => $lastUpdated->copy()->timezone('Asia/Kolkata')->toDayDateTimeString(),
        'lastUpdatedIso' => $lastUpdated->toIso8601String(),
        'docSourcePath' => 'resources/docs/api_documentation.md',
    ]);
})->name('dev.api-documentation');
Route::get('/dev/push-scheduler', [PushSchedulerController::class, 'index'])->name('dev.push-scheduler');
Route::post('/dev/push-scheduler', [PushSchedulerController::class, 'store'])->name('dev.push-scheduler.store');
Route::post('/dev/push-scheduler/run-now', [PushSchedulerController::class, 'runNow'])->name('dev.push-scheduler.run-now');
Route::get('/dev/push-scheduler/logs/{run}', [PushSchedulerController::class, 'showLog'])->name('dev.push-scheduler.log');
Route::post('/dev/push-scheduler/{notification}', [PushSchedulerController::class, 'update'])->name('dev.push-scheduler.update');

Route::get('/custom-doctor-login', function () { return view('custom-doctor-login'); })->name('custom-doctor-login');
Route::get('/logout', function (\Illuminate\Http\Request $request) {
    $request->session()->flush();
    return redirect()->route('custom-doctor-login');
})->name('logout');
Route::prefix('admin')->group(function () {
    Route::middleware([EnsureAdminAuthenticated::class])->get('/', function () {
        return redirect()->route('admin.dashboard');
    })->name('admin.index');
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.attempt');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::middleware([EnsureAdminAuthenticated::class])->group(function () {
        Route::get('/onboarding', [AdminOnboardingStatusPageController::class, 'panel'])->name('admin.onboarding.panel');
        Route::get('/dashboard', [AdminPanelController::class, 'index'])->name('admin.dashboard');
        Route::get('/users', [AdminPanelController::class, 'users'])->name('admin.users');
        Route::get('/pets', [AdminPanelController::class, 'pets'])->name('admin.pets');
        Route::get('/doctors', [AdminPanelController::class, 'doctors'])->name('admin.doctors');
        Route::get('/online-doctors', [AdminPanelController::class, 'onlineDoctors'])->name('admin.online-doctors');
        Route::get('/vet-registrations', [AdminPanelController::class, 'vetRegistrations'])->name('admin.vet-registrations');
        Route::get('/bookings', [AdminPanelController::class, 'bookings'])->name('admin.bookings');
        Route::get('/analytics/video', [AdminPanelController::class, 'videoAnalytics'])->name('admin.analytics.video');
        Route::get('/supports', [AdminPanelController::class, 'supports'])->name('admin.supports');
        Route::get('/sp/{user}', [AdminPanelController::class, 'serviceProviderProfile'])->name('admin.sp.profile');

        Route::prefix('onboarding')->group(function () {
            Route::redirect('/services', '/admin/onboarding#services')->name('admin.onboarding.services');
            Route::redirect('/video', '/admin/onboarding#video')->name('admin.onboarding.video');
            Route::redirect('/clinic-hours', '/admin/onboarding#clinicHours')->name('admin.onboarding.clinic-hours');
            Route::redirect('/emergency', '/admin/onboarding#emergency')->name('admin.onboarding.emergency');
            Route::redirect('/documents', '/admin/onboarding#documents')->name('admin.onboarding.documents');
        });

        Route::get('/legacy-qr', [LegacyQrRedirectAdminController::class, 'index'])->name('admin.legacy-qr.index');
        Route::post('/legacy-qr', [LegacyQrRedirectAdminController::class, 'store'])->name('admin.legacy-qr.store');
        Route::delete('/legacy-qr/{legacyQrRedirect}', [LegacyQrRedirectAdminController::class, 'destroy'])->name('admin.legacy-qr.destroy');
    });
});
Route::get('/c/{publicId}', [VetLandingController::class, 'redirectByPublicId'])->name('clinics.shortlink');
Route::get('/backend/c/{publicId}', function (Request $request, string $publicId) {
    $legacyScanner = LegacyQrRedirect::findByPublicId($publicId)
        ?? LegacyQrRedirect::where('code', $publicId)->first();

    if ($legacyScanner && $request->query('via') !== 'legacy-qr') {
        return redirect()->to($legacyScanner->scan_url);
    }

    return app(VetLandingController::class)->redirectByPublicId($request, $publicId);
});
Route::get('/vets/{slug}', [VetLandingController::class, 'show']);

// QR scan tracking beacon (1x1 GIF). Counts as a fallback on page load.
Route::get('/qr/beacon.gif', [QrTrackingController::class, 'beacon'])->name('qr.beacon');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/category/{category}', [BlogController::class, 'category'])->name('category');
    Route::get('/tag/{tag}', [BlogController::class, 'tag'])->name('tag');
    Route::get('/feed', [BlogController::class, 'feed'])->name('feed');
    Route::get('/feed.xml', [BlogController::class, 'feed']);
    Route::get('/sitemap.xml', [BlogController::class, 'sitemap'])->name('sitemap');
    Route::get('/{post}', [BlogController::class, 'show'])->name('post');
});

Route::middleware([EnsureAdminAuthenticated::class])->group(function () {
    Route::get('/admin/posts', [PostController::class, 'index'])->name('admin.posts.index');
    Route::get('/admin/posts/create', [PostController::class, 'create'])->name('admin.posts.create');
    Route::post('/admin/posts', [PostController::class, 'store'])->name('admin.posts.store');
    Route::get('/admin/posts/{post}/edit', [PostController::class, 'edit'])->name('admin.posts.edit');
    Route::match(['put', 'patch'], '/admin/posts/{post}', [PostController::class, 'update'])->name('admin.posts.update');
    Route::delete('/admin/posts/{post}', [PostController::class, 'destroy'])->name('admin.posts.destroy');

    Route::get('/admin/categories', [CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/admin/categories/create', [CategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/admin/categories/{category}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::match(['put', 'patch'], '/admin/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    Route::get('/admin/tags', [TagController::class, 'index'])->name('admin.tags.index');
    Route::get('/admin/tags/create', [TagController::class, 'create'])->name('admin.tags.create');
    Route::post('/admin/tags', [TagController::class, 'store'])->name('admin.tags.store');
    Route::get('/admin/tags/{tag}/edit', [TagController::class, 'edit'])->name('admin.tags.edit');
    Route::match(['put', 'patch'], '/admin/tags/{tag}', [TagController::class, 'update'])->name('admin.tags.update');
    Route::delete('/admin/tags/{tag}', [TagController::class, 'destroy'])->name('admin.tags.destroy');
});

Route::middleware([EnsureAdminAuthenticated::class])->get('/founder/dashboard', function () {
    return view('founder.dashboard');
})->name('founder.dashboard');

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

Route::match(['get', 'post'], '/rag-snoutic-symptom-checker', function (Request $request) {
    $apiUrl = 'http://82.25.104.75:5050/query';
    $defaultPayload = [
        'name' => 'Bruno',
        'species' => 'Dog',
        'breed' => 'Labrador',
        'age' => '3 years',
        'weight' => '28 kg',
        'sex' => 'Male',
        'vaccination_summary' => 'All core vaccines up to date',
        'medical_history' => 'No known issues',
        'query' => 'He is vomiting yellow foam and not eating since morning, kind of weak',
    ];

    $formValues = $defaultPayload;
    $responseData = null;
    $requestPayload = null;
    $error = null;

    if ($request->isMethod('post')) {
        $formValues = array_merge($defaultPayload, $request->only(array_keys($defaultPayload)));
        $requestPayload = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $formValues);

        try {
            $response = Http::timeout(10)->post($apiUrl, $requestPayload);

            $responseData = $response->json();

            if (! $response->successful()) {
                $error = 'Unable to fetch symptom checker data right now.';
            }
        } catch (\Throwable $th) {
            $error = 'Failed to contact the symptom checker service.';
        }
    }

    return view('snoutiq.rag-snoutic-symptom-checker', [
        'responseData' => $responseData,
        'requestPayload' => $requestPayload,
        'formValues' => $formValues,
        'error' => $error,
    ]);
})->name('snoutiq.rag-snoutic-symptom-checker');

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
        $sessionRole = session('role')
            ?? data_get(session('auth_full'), 'role')
            ?? data_get(session('user'), 'role');

        $candidates = [
            session('clinic_id'),
            session('vet_registerations_temp_id'),
            session('vet_registeration_id'),
            session('vet_id'),
            data_get(session('user'), 'clinic_id'),
            data_get(session('user'), 'vet_registeration_id'),
            data_get(session('auth_full'), 'clinic_id'),
            data_get(session('auth_full'), 'user.clinic_id'),
            data_get(session('auth_full'), 'user.vet_registeration_id'),
        ];

        if ($sessionRole !== 'doctor') {
            array_unshift(
                $candidates,
                session('user_id'),
                data_get(session('user'), 'id')
            );
        }

        $vetId = null;
        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            $num = (int) $candidate;
            if ($num > 0) {
                $vetId = $num;
                break;
            }
        }

        $doctors = collect();
        if ($vetId) {
            $doctors = Doctor::where('vet_registeration_id', $vetId)
                ->orderBy('doctor_name')
                ->get(['id','doctor_name','doctors_price']);
        }
        return view('snoutiq.provider-schedule', compact('doctors', 'vetId'));
    })->name('doctor.schedule');

    Route::get('/doctor/emergency-hours', [EmergencyHoursPageController::class, 'editor'])->name('doctor.emergency-hours');
    Route::get('/doctor/documents', [VetDocumentsPageController::class, 'index'])->name('doctor.documents');
    Route::post('/doctor/documents', [VetDocumentsPageController::class, 'updateClinic'])->name('doctor.documents.update');
    Route::post('/doctor/documents/doctors/{doctor}', [VetDocumentsPageController::class, 'updateDoctor'])->name('doctor.documents.doctor');

    Route::get('/api/clinic/emergency-hours', [ClinicEmergencyHoursController::class, 'show'])->name('api.clinic.emergency-hours.show');
    Route::post('/api/clinic/emergency-hours', [ClinicEmergencyHoursController::class, 'upsert'])->name('api.clinic.emergency-hours.upsert');

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
        $sessionRole = session('role')
            ?? data_get(session('auth_full'), 'role')
            ?? data_get(session('user'), 'role');

        $candidates = [
            session('clinic_id'),
            session('vet_registerations_temp_id'),
            session('vet_registeration_id'),
            session('vet_id'),
            data_get(session('user'), 'clinic_id'),
            data_get(session('user'), 'vet_registeration_id'),
            data_get(session('auth_full'), 'clinic_id'),
            data_get(session('auth_full'), 'user.clinic_id'),
            data_get(session('auth_full'), 'user.vet_registeration_id'),
        ];

        if ($sessionRole !== 'doctor') {
            array_unshift(
                $candidates,
                session('user_id'),
                data_get(session('user'), 'id')
            );
        }

        $vetId = null;
        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            $num = (int) $candidate;
            if ($num > 0) {
                $vetId = $num;
                break;
            }
        }

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
    $session = $request->session();
    $sessionRole = $session->get('role')
        ?? data_get($session->get('auth_full'), 'role')
        ?? data_get($session->get('user'), 'role');

    $sessionCandidates = [
        $session->get('clinic_id'),
        $session->get('vet_registerations_temp_id'),
        $session->get('vet_registeration_id'),
        $session->get('vet_id'),
        data_get($session->get('user'), 'clinic_id'),
        data_get($session->get('user'), 'vet_registeration_id'),
        data_get($session->get('auth_full'), 'clinic_id'),
        data_get($session->get('auth_full'), 'user.clinic_id'),
        data_get($session->get('auth_full'), 'user.vet_registeration_id'),
    ];

    if ($sessionRole !== 'doctor') {
        array_unshift(
            $sessionCandidates,
            $session->get('user_id'),
            data_get($session->get('user'), 'id')
        );
    }

    $sessionClinicId = null;
    foreach ($sessionCandidates as $candidate) {
        if ($candidate === null || $candidate === '') {
            continue;
        }
        $num = (int) $candidate;
        if ($num > 0) {
            $sessionClinicId = $num;
            break;
        }
    }

    $userId = $request->query('user_id')
        ?: $request->header('X-User-Id')
        ?: $sessionClinicId;
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

Route::middleware('web')->get('/admin/video/slot-overview', AdminVideoSlotOverviewController::class)
    ->name('admin.video.slot-overview');

Route::middleware('web')->get('/api/video/coverage/pincode', function (\Illuminate\Http\Request $request) {
    $dateParam = $request->query('date');
    $date = $dateParam && strtotime($dateParam) ? date('Y-m-d', strtotime($dateParam)) : date('Y-m-d');
    $dow = (int) date('w', strtotime($date));
    $doctorId = (int) $request->query('doctor_id', 0);

    $toMinutes = static function (?string $time): ?int {
        if (!$time) {
            return null;
        }
        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return null;
        }
        return ((int) $parts[0]) * 60 + ((int) $parts[1]);
    };

    $rows = DB::table('doctor_video_availability as dva')
        ->join('doctors as d', 'dva.doctor_id', '=', 'd.id')
        ->join('vet_registerations_temp as vrt', 'd.vet_registeration_id', '=', 'vrt.id')
        ->select(
            'vrt.pincode',
            'vrt.clinic_profile',
            'vrt.name as vet_name',
            'dva.start_time',
            'dva.end_time',
            'dva.break_start',
            'dva.break_end'
        )
        ->where('dva.is_active', 1)
        ->where('dva.day_of_week', $dow)
        ->whereNotNull('vrt.pincode')
        ->when($doctorId > 0, function ($query) use ($doctorId) {
            $query->where('dva.doctor_id', $doctorId);
        })
        ->get();

    $matrix = [];
    foreach ($rows as $row) {
        $code = trim((string) $row->pincode);
        if ($code === '') {
            $code = 'Unknown';
        }

        if (!isset($matrix[$code])) {
            $hours = [];
            for ($h = 0; $h < 24; $h++) {
                $hours[$h] = [];
            }
            $matrix[$code] = [
                'pincode' => $code,
                'clinics' => [],
                'hours' => $hours,
                'slot_labels' => [],
            ];
        }

        $clinicName = $row->clinic_profile ?: $row->vet_name;
        if ($clinicName) {
            $matrix[$code]['clinics'][$clinicName] = true;
        }

        $segments = [
            [$row->start_time, $row->end_time],
        ];
        if ($row->break_start && $row->break_end) {
            $segments = [
                [$row->start_time, $row->break_start],
                [$row->break_end, $row->end_time],
            ];
        }

        foreach ($segments as [$segStart, $segEnd]) {
            if (!$segStart || !$segEnd) {
                continue;
            }
            $startMin = $toMinutes($segStart);
            $endMin = $toMinutes($segEnd);
            if ($startMin === null || $endMin === null) {
                continue;
            }
            if ($endMin <= $startMin) {
                $endMin += 1440;
            }
            $label = substr($segStart, 0, 5) . '-' . substr($segEnd, 0, 5);
            $matrix[$code]['slot_labels'][$label] = true;
            for ($m = $startMin; $m < $endMin; $m += 60) {
                $hour = (int) floor($m / 60) % 24;
                $matrix[$code]['hours'][$hour][] = $label;
            }
        }
    }

    $rowsOut = array_map(function (array $entry) {
        $clinics = array_keys($entry['clinics']);
        sort($clinics, SORT_NATURAL);
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $labels = $entry['hours'][$h] ?? [];
            $hours[$h] = array_values(array_unique($labels));
        }
        return [
            'pincode' => $entry['pincode'],
            'clinics' => $clinics,
            'hours' => $hours,
            'slots' => array_keys($entry['slot_labels']),
        ];
    }, array_values($matrix));

    usort($rowsOut, static function ($a, $b) {
        return strcmp($a['pincode'], $b['pincode']);
    });

    return response()->json([
        'date' => $date,
        'day_of_week' => $dow,
        'rows' => $rowsOut,
    ]);
});

Route::middleware('web')->get('/api/admin/video/pincode-slots', function (\Illuminate\Http\Request $request) {
    $tz = 'Asia/Kolkata';
    $dateQuery = $request->query('date');
    try {
        $date = $dateQuery ? \Carbon\CarbonImmutable::parse($dateQuery, $tz) : \Carbon\CarbonImmutable::now($tz);
    } catch (\Throwable $e) {
        $date = \Carbon\CarbonImmutable::now($tz);
    }
    $dateStr = $date->toDateString();
    $dow = (int) $date->dayOfWeek;

    $doctorId = (int) $request->query('doctor_id', 0);
    $pincodeFilter = trim((string) $request->query('pincode', ''));

    $toMinutes = static function (?string $time): ?int {
        if (!$time) {
            return null;
        }
        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return null;
        }
        return (int)$parts[0] * 60 + (int)$parts[1];
    };

    $matrix = [];
    $overallDoctorIds = [];

    $availability = DB::table('doctor_video_availability as dva')
        ->join('doctors as d', 'dva.doctor_id', '=', 'd.id')
        ->join('vet_registerations_temp as vrt', 'd.vet_registeration_id', '=', 'vrt.id')
        ->select(
            'dva.doctor_id',
            'vrt.pincode',
            'vrt.clinic_profile',
            'vrt.name as vet_name',
            'dva.start_time',
            'dva.end_time',
            'dva.break_start',
            'dva.break_end',
            'dva.max_bookings_per_hour'
        )
        ->where('dva.is_active', 1)
        ->where('dva.day_of_week', $dow)
        ->whereNotNull('vrt.pincode')
        ->when($doctorId > 0, fn($q) => $q->where('dva.doctor_id', $doctorId))
        ->when($pincodeFilter !== '', fn($q) => $q->where('vrt.pincode', $pincodeFilter))
        ->get();

    foreach ($availability as $row) {
        $code = trim((string) $row->pincode);
        if ($code === '') {
            $code = 'Unknown';
        }
        if (!isset($matrix[$code])) {
            $matrix[$code] = [
                'pincode' => $code,
                'clinics' => [],
                'hours' => array_fill(0, 24, ['doctors' => []]),
                'doctor_ids' => [],
            ];
        }
        $clinicName = $row->clinic_profile ?: $row->vet_name;
        if ($clinicName) {
            $matrix[$code]['clinics'][$clinicName] = true;
        }

        $startMin = $toMinutes($row->start_time);
        $endMin = $toMinutes($row->end_time);
        if ($startMin === null || $endMin === null) {
            continue;
        }
        if ($endMin <= $startMin) {
            $endMin += 1440; // wrap to next day
        }
        $segments = [[$startMin, $endMin]];
        $breakStart = $toMinutes($row->break_start);
        $breakEnd = $toMinutes($row->break_end);
        if ($breakStart !== null && $breakEnd !== null && $breakEnd > $breakStart) {
            $segments = [];
            if ($breakStart > $startMin) {
                $segments[] = [$startMin, min($breakStart, $endMin)];
            }
            if ($breakEnd < $endMin) {
                $segments[] = [max($breakEnd, $startMin), $endMin];
            }
        }

        foreach ($segments as [$segStart, $segEnd]) {
            if ($segEnd <= $segStart) {
                continue;
            }
            $startBlock = (int) floor($segStart / 60);
            $endBlock = (int) floor(($segEnd - 1) / 60);
            for ($block = $startBlock; $block <= $endBlock; $block++) {
                $hourIndex = $block % 24;
                if ($hourIndex < 0 || $hourIndex > 23) {
                    continue;
                }
                $matrix[$code]['hours'][$hourIndex]['doctors'][$row->doctor_id] = true;
                $matrix[$code]['doctor_ids'][$row->doctor_id] = true;
                $overallDoctorIds[$row->doctor_id] = true;
            }
        }
    }

    $totalDoctorHours = 0;
    foreach ($matrix as $code => $entry) {
        $clinics = array_keys($entry['clinics']);
        sort($clinics, SORT_NATURAL);
        $matrix[$code]['clinics'] = $clinics;
        $rowTotals = ['doctor_hours' => 0, 'unique_doctors' => count($entry['doctor_ids'])];
        foreach ($entry['hours'] as $hour => $cell) {
            $count = isset($cell['doctors']) ? count($cell['doctors']) : 0;
            $matrix[$code]['hours'][$hour] = ['count' => $count];
            $rowTotals['doctor_hours'] += $count;
        }
        $matrix[$code]['totals'] = $rowTotals;
        $totalDoctorHours += $rowTotals['doctor_hours'];
    }

    $rowsOut = array_values($matrix);
    usort($rowsOut, static function ($a, $b) {
        return strcmp($a['pincode'], $b['pincode']);
    });

    return response()->json([
        'date' => $dateStr,
        'rows' => $rowsOut,
        'summary' => [
            'doctor_hours' => $totalDoctorHours,
            'unique_doctors' => count($overallDoctorIds),
        ],
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
