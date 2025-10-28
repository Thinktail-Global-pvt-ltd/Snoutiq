<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerTicket;
use App\Models\Doctor;
use App\Models\GroomerBooking;
use App\Models\GroomerProfile;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use Illuminate\Contracts\View\View;
use App\Services\DoctorAvailabilityService;

class AdminPanelController extends Controller
{
    public function __construct(
        private readonly DoctorAvailabilityService $doctorAvailabilityService,
    ) {
    }

    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_bookings' => GroomerBooking::count(),
            'total_supports' => CustomerTicket::count(),
        ];

        $activeDoctorIds = $this->doctorAvailabilityService->getActiveDoctorIds();

        $onlineClinics = $activeDoctorIds->isEmpty()
            ? collect()
            : VetRegisterationTemp::query()
                ->whereHas('doctors', function ($query) use ($activeDoctorIds) {
                    $query->where('toggle_availability', 1)
                        ->whereIn('id', $activeDoctorIds->all());
                })
                ->withCount([
                    'doctors as available_doctors_count' => function ($query) use ($activeDoctorIds) {
                        $query->where('toggle_availability', 1)
                            ->whereIn('id', $activeDoctorIds->all());
                    },
                ])
                ->orderBy('name')
                ->get();

        return view('admin.dashboard', compact('stats', 'onlineClinics'));
    }

    public function users(): View
    {
        $users = User::orderByDesc('created_at')->get();

        return view('admin.users', compact('users'));
    }

    public function bookings(): View
    {
        $bookings = GroomerBooking::with(['groomerEmployee', 'user'])->orderByDesc('created_at')->get();

        return view('admin.bookings', compact('bookings'));
    }

    public function supports(): View
    {
        $supports = CustomerTicket::with('user')->orderByDesc('created_at')->get();

        return view('admin.supports', compact('supports'));
    }

    public function serviceProviderProfile(User $user): View
    {
        $profile = GroomerProfile::where('user_id', $user->id)->first();

        return view('admin.sp_profile', compact('profile'));
    }

    public function pets(): View
    {
        $pets = Pet::with('owner')->orderByDesc('created_at')->get();

        return view('admin.pets', compact('pets'));
    }

    public function doctors(): View
    {
        $doctors = Doctor::with('clinic')->orderBy('doctor_name')->get();

        return view('admin.doctors', compact('doctors'));
    }

    public function onlineDoctors(): View
    {
        $activeDoctorIds = $this->doctorAvailabilityService->getActiveDoctorIds();

        $onlineDoctors = $activeDoctorIds->isEmpty()
            ? collect()
            : Doctor::query()
                ->with('clinic')
                ->where('toggle_availability', 1)
                ->whereIn('id', $activeDoctorIds->all())
                ->orderBy('doctor_name')
                ->get();

        return view('admin.online-doctors', compact('onlineDoctors'));
    }

    public function vetRegistrations(): View
    {
        $clinics = VetRegisterationTemp::withCount('doctors')->orderByDesc('created_at')->get();

        return view('admin.vet-registrations', compact('clinics'));
    }
}
