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
use App\Services\CallAnalyticsService;
use App\Services\DoctorAvailabilityService;
use Illuminate\Support\Collection;

class AdminPanelController extends Controller
{
    public function __construct(
        private readonly DoctorAvailabilityService $doctorAvailabilityService,
        private readonly CallAnalyticsService $callAnalyticsService,
    ) {
    }

    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_bookings' => GroomerBooking::count(),
            'total_supports' => CustomerTicket::count(),
        ];

        $onlineClinics = $this->getOnlineClinics();
        $activeDoctors = $this->formatActiveDoctorLabels();
        $callMetrics   = $this->callAnalyticsService->summary();
        $recentCalls   = $this->callAnalyticsService->recentSessions();

        return view('admin.dashboard', compact('stats', 'onlineClinics', 'activeDoctors', 'callMetrics', 'recentCalls'));
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
        $onlineClinics = $this->getOnlineClinics();
        $activeDoctors = $this->formatActiveDoctorLabels();

        return view('admin.online-doctors', compact('onlineClinics', 'activeDoctors'));
    }

    public function vetRegistrations(): View
    {
        $clinics = VetRegisterationTemp::withCount('doctors')->orderByDesc('created_at')->get();

        return view('admin.vet-registrations', compact('clinics'));
    }

    private function getOnlineClinics(?Collection $activeClinicIds = null): Collection
    {
        $activeClinicIds = $activeClinicIds ?? $this->doctorAvailabilityService->getActiveClinicIds();

        if ($activeClinicIds->isEmpty()) {
            return collect();
        }

        return VetRegisterationTemp::query()
            ->whereIn('id', $activeClinicIds->all())
            ->withCount([
                'doctors as available_doctors_count' => function ($query) {
                    $query->where('toggle_availability', 1);
                },
            ])
            ->orderBy('name')
            ->get();
    }

    private function formatActiveDoctorLabels(): Collection
    {
        return $this->doctorAvailabilityService
            ->getActiveDoctorSummaries()
            ->map(static function (array $doctor) {
                $id = $doctor['id'];
                $name = $doctor['name'] ?? null;

                if ($name) {
                    return sprintf("%d (%s)", $id, $name);
                }

                return (string) $id;
            })
            ->values();
    }
}
