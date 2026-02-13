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
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use App\Services\CallAnalyticsService;
use App\Services\DoctorAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    public function excellExportDoctors(): View
    {
        $doctors = Doctor::query()
            ->where('exported_from_excell', 1)
            ->with('clinic')
            ->orderBy('doctor_name')
            ->get();

        return view('admin.excell-export-doctors', compact('doctors'));
    }

    public function updateDoctorImage(Request $request, Doctor $doctor)
    {
        if ($doctor->exported_from_excell != 1) {
            abort(404);
        }

        if (!Schema::hasColumn('doctors', 'doctor_image_blob') || !Schema::hasColumn('doctors', 'doctor_image_mime')) {
            return back()->withErrors(['doctor_image' => 'Please run migrations first: blob image columns are missing.']);
        }

        $validated = $request->validate([
            'doctor_image' => ['nullable', 'file', 'image'],
            'doctor_image_base64' => ['nullable', 'string'],
        ]);

        $binary = null;
        $mime = null;

        if ($request->hasFile('doctor_image') && $request->file('doctor_image')->isValid()) {
            $file = $request->file('doctor_image');
            $binary = $file->get();
            $mime = $file->getMimeType() ?: 'image/png';
        } elseif (!empty($validated['doctor_image_base64']) && str_starts_with($validated['doctor_image_base64'], 'data:image')) {
            if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.*)$/s', $validated['doctor_image_base64'], $matches)) {
                $mime = strtolower(trim($matches[1]));
                $rawBase64 = str_replace(' ', '+', $matches[2]);
                $decoded = base64_decode($rawBase64, true);

                if ($decoded !== false) {
                    $binary = $decoded;
                }
            }
        }

        if (!$binary || !$mime) {
            return back()->withErrors(['doctor_image' => 'Invalid image provided.']);
        }

        $doctor->doctor_image_blob = $binary;
        $doctor->doctor_image_mime = $mime;
        $doctor->save();

        return back()->with('status', 'Doctor image updated.');
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

    public function videoAnalytics(): View
    {
        $overviewMetrics = $this->callAnalyticsService->lifecycleOverview();
        $userLifecycleSteps = $this->callAnalyticsService->userLifecycleSteps();
        $doctorLifecycleSteps = $this->callAnalyticsService->doctorLifecycleSteps();
        $conversionBenchmarks = $this->callAnalyticsService->conversionBenchmarks();
        $dropOffBreakdown = $this->callAnalyticsService->dropOffBreakdown();
        $recentUserTimeline = $this->callAnalyticsService->recentUserTimeline();
        $recentDoctorTimeline = $this->callAnalyticsService->recentDoctorTimeline();

        return view('admin.video-analytics', compact(
            'overviewMetrics',
            'userLifecycleSteps',
            'doctorLifecycleSteps',
            'conversionBenchmarks',
            'dropOffBreakdown',
            'recentUserTimeline',
            'recentDoctorTimeline'
        ));
    }

    public function pincodeHeatmap(): View
    {
        return view('admin.pincode-heatmap');
    }

    public function whatsappTemplates(): View
    {
        $templates = [
            [
                'key' => 'pp_booking_confirmed',
                'audience' => 'Pet parent',
                'description' => 'Sent to pet parent after booking / Excel export campaign payment.',
                'placeholders' => [
                    '1' => 'Pet parent name',
                    '2' => 'Pet name',
                    '3' => 'Pet type',
                    '4' => 'Vet name',
                    '5' => 'Response time (minutes)',
                    '6' => 'Amount paid (INR)',
                    '7' => 'Vet name (repeat)',
                ],
                'body' => "Hi {{1}}, your {{3}} {{2}} is booked with {{4}}. They'll respond within {{5}} minutes. Amount paid ₹{{6}}. Vet: {{7}}. - SnoutIQ",
            ],
            [
                'key' => 'vet_new_consultation_assigned',
                'audience' => 'Vet',
                'description' => 'Sent to assigned vet with consultation details.',
                'placeholders' => [
                    '1' => 'Vet name',
                    '2' => 'Pet name',
                    '3' => 'Pet type/breed',
                    '4' => 'Pet parent name',
                    '5' => 'Parent WhatsApp',
                    '6' => 'Issue/concern',
                    '7' => 'Prescription/PDF link',
                    '8' => 'Response time (minutes)',
                ],
                'body' => "Hi Dr. {{1}}, a new consultation is assigned. Pet: {{2}} ({{3}}). Parent: {{4}} ({{5}}). Issue: {{6}}. Prescription: {{7}}. Please respond within {{8}} mins. - SnoutIQ",
            ],
        ];

        return view('admin.whatsapp-templates', compact('templates'));
    }

    public function excellExportTransactions(): View
    {
        $transactions = Transaction::query()
            ->where('status', 'captured')
            ->where(function ($query) {
                $query->where('type', 'excell_export_campaign')
                    ->orWhere('metadata->order_type', 'excell_export_campaign');
            })
            ->whereHas('clinic') // skip rows whose clinic entry was deleted
            ->with([
                'clinic:id,name',
                'doctor:id,doctor_name,doctor_email,doctor_mobile',
                'user' => function ($query) {
                    $query->select('id', 'name', 'email', 'phone')
                        ->with([
                            'pets' => function ($petQuery) {
                                $petQuery->select('id', 'user_id', 'name', 'breed', 'pet_type', 'reported_symptom')
                                    ->orderByDesc('id');
                            },
                        ]);
                },
                'pet:id,user_id,name,breed,pet_type,reported_symptom',
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.transactions-excell-export', compact('transactions'));
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
