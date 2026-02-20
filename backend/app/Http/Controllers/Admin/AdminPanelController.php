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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use App\Services\CallAnalyticsService;
use App\Services\DoctorAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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

    public function excellExportTransactions(Request $request)
    {
        $transactions = $this->excellExportTransactionsQuery()->get();

        if (strtolower((string) $request->query('export')) === 'csv') {
            return $this->streamExcellExportTransactionsCsv($transactions);
        }

        return view('admin.transactions-excell-export', compact('transactions'));
    }

    private function excellExportTransactionsQuery(): Builder
    {
        $petColumns = $this->excellExportPetColumns();

        return Transaction::query()
            ->where('status', 'captured')
            ->where(function ($query) {
                $query->where('type', 'excell_export_campaign')
                    ->orWhere('metadata->order_type', 'excell_export_campaign');
            })
            ->whereHas('clinic') // skip rows whose clinic entry was deleted
            ->with([
                'clinic:id,name',
                'doctor:id,doctor_name,doctor_email,doctor_mobile',
                'user' => function ($query) use ($petColumns) {
                    $query->select('id', 'name', 'email', 'phone')
                        ->with([
                            'pets' => function ($petQuery) use ($petColumns) {
                                $petQuery->select($petColumns)
                                    ->orderByDesc('id');
                            },
                        ]);
                },
                'pet' => function ($query) use ($petColumns) {
                    $query->select($petColumns);
                },
            ])
            ->orderByDesc('created_at');
    }

    private function excellExportPetColumns(): array
    {
        $petColumns = ['id', 'user_id', 'name', 'breed', 'pet_type', 'reported_symptom'];

        if (Schema::hasColumn('pets', 'pet_dob')) {
            $petColumns[] = 'pet_dob';
        }
        if (Schema::hasColumn('pets', 'dob')) {
            $petColumns[] = 'dob';
        }

        return $petColumns;
    }

    private function resolveExcellExportPetRecord(Transaction $transaction): ?Pet
    {
        $userPets = $transaction->user?->pets ?? collect();
        $petFromTransaction = $transaction->pet;
        $fallbackPetWithIssue = $userPets->first(function ($pet) {
            return trim((string) ($pet->reported_symptom ?? '')) !== '';
        });

        return $petFromTransaction ?? $fallbackPetWithIssue ?? $userPets->first();
    }

    private function formatExcellExportPetDob($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function streamExcellExportTransactionsCsv(Collection $transactions)
    {
        $fileName = 'excel-export-transactions-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Transaction ID',
            'Created At',
            'Status',
            'Amount (INR)',
            'Type',
            'Order Type',
            'Payment Method',
            'Reference',
            'Clinic ID',
            'Clinic Name',
            'Doctor ID',
            'Doctor Name',
            'Doctor Email',
            'Doctor Mobile',
            'User ID',
            'User Name',
            'User Email',
            'User Phone',
            'Pet ID',
            'Pet Name',
            'Pet Type',
            'Pet Breed',
            'Pet DOB',
            'Reported Symptom',
        ];

        return response()->streamDownload(function () use ($transactions, $headers) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($transactions as $transaction) {
                $petRecord = $this->resolveExcellExportPetRecord($transaction);
                $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
                $issue = trim((string) ($transaction->pet?->reported_symptom ?? ''));
                if ($issue === '') {
                    $issue = trim((string) ($petRecord->reported_symptom ?? ''));
                }
                $petDob = $this->formatExcellExportPetDob($petRecord->pet_dob ?? $petRecord->dob ?? null);

                fputcsv($output, [
                    $transaction->id,
                    optional($transaction->created_at)->format('Y-m-d H:i:s'),
                    strtoupper((string) ($transaction->status ?? '')),
                    number_format(((int) ($transaction->amount_paise ?? 0)) / 100, 2, '.', ''),
                    $transaction->type,
                    data_get($metadata, 'order_type'),
                    $transaction->payment_method,
                    $transaction->reference,
                    $transaction->clinic_id,
                    $transaction->clinic->name ?? null,
                    $transaction->doctor_id,
                    $transaction->doctor->doctor_name ?? null,
                    $transaction->doctor->doctor_email ?? null,
                    $transaction->doctor->doctor_mobile ?? null,
                    $transaction->user_id,
                    $transaction->user->name ?? null,
                    $transaction->user->email ?? null,
                    $transaction->user->phone ?? null,
                    $petRecord->id ?? $transaction->pet_id,
                    $petRecord->name ?? null,
                    $petRecord->pet_type ?? $petRecord->type ?? $petRecord->breed ?? null,
                    $petRecord->breed ?? null,
                    $petDob,
                    $issue !== '' ? $issue : null,
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function appointmentTransactions(): View
    {
        $petColumns = ['id', 'user_id', 'name', 'breed', 'pet_type', 'reported_symptom'];
        if (Schema::hasColumn('pets', 'pet_dob')) {
            $petColumns[] = 'pet_dob';
        }
        if (Schema::hasColumn('pets', 'dob')) {
            $petColumns[] = 'dob';
        }

        $transactions = $this->appointmentTransactionsQuery()
            ->with([
                'clinic:id,name',
                'doctor:id,vet_registeration_id,doctor_name,doctor_email,doctor_mobile',
                'user' => function ($query) use ($petColumns) {
                    $query->select('id', 'name', 'email', 'phone')
                        ->with([
                            'pets' => function ($petQuery) use ($petColumns) {
                                $petQuery->select($petColumns)
                                    ->orderByDesc('id');
                            },
                        ]);
                },
                'pet' => function ($query) use ($petColumns) {
                    $query->select($petColumns);
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        $allDoctors = Doctor::query()
            ->where('exported_from_excell', 1)
            ->select('id', 'vet_registeration_id', 'doctor_name', 'doctor_email', 'doctor_mobile', 'toggle_availability')
            ->orderBy('doctor_name')
            ->get();

        $latestAssignmentLogs = $this->latestTransactionDoctorAssignmentLogs($transactions);
        $doctorNameLookup = $this->doctorNameLookupForTransactions($transactions, $latestAssignmentLogs);

        return view('admin.appointment-transactions', compact('transactions', 'allDoctors', 'latestAssignmentLogs', 'doctorNameLookup'));
    }

    public function updateAppointmentTransactionDoctor(Request $request, Transaction $transaction): RedirectResponse
    {
        if (! $this->isAppointmentTransaction($transaction)) {
            return redirect()
                ->route('admin.transactions.appointments')
                ->withErrors(['doctor_id' => 'Only video consultation appointment transactions can be reassigned from this page.']);
        }

        $data = $request->validate([
            'doctor_id' => ['required', 'integer'],
        ]);

        $doctor = Doctor::query()
            ->select('id', 'vet_registeration_id', 'doctor_name')
            ->where('exported_from_excell', 1)
            ->find((int) $data['doctor_id']);

        if (! $doctor) {
            return redirect()
                ->route('admin.transactions.appointments')
                ->withErrors(['doctor_id' => 'Please select a valid Excel-export doctor (exported_from_excell = 1).']);
        }

        $previousDoctorId = $transaction->doctor_id ? (int) $transaction->doctor_id : null;
        $previousClinicId = $transaction->clinic_id ? (int) $transaction->clinic_id : null;
        $nextDoctorId = (int) $doctor->id;
        $nextClinicId = $doctor->vet_registeration_id ? (int) $doctor->vet_registeration_id : null;

        if ($previousDoctorId === $nextDoctorId && $previousClinicId === $nextClinicId) {
            return redirect()
                ->route('admin.transactions.appointments')
                ->with('status', sprintf(
                    'No change for transaction #%d. Doctor already assigned: %s (ID: %d).',
                    $transaction->id,
                    $doctor->doctor_name ?? 'N/A',
                    $doctor->id
                ));
        }

        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
        $metadata['doctor_id'] = $nextDoctorId;
        $metadata['clinic_id'] = $nextClinicId;

        $assignmentLogEntry = [
            'changed_at' => now()->toIso8601String(),
            'changed_by_user_id' => optional($request->user())->id,
            'changed_by_name' => optional($request->user())->name,
            'previous_doctor_id' => $previousDoctorId,
            'previous_clinic_id' => $previousClinicId,
            'new_doctor_id' => $nextDoctorId,
            'new_clinic_id' => $nextClinicId,
        ];

        $history = data_get($metadata, 'doctor_assignment_logs', []);
        if (!is_array($history)) {
            $history = [];
        }
        $history[] = $assignmentLogEntry;
        $metadata['doctor_assignment_logs'] = $history;
        $metadata['last_doctor_assignment'] = $assignmentLogEntry;

        $transaction->doctor_id = $nextDoctorId;
        $transaction->clinic_id = $nextClinicId;
        $transaction->metadata = $metadata;
        $transaction->save();

        if (Schema::hasTable('transaction_doctor_assignment_logs')) {
            DB::table('transaction_doctor_assignment_logs')->insert([
                'transaction_id' => $transaction->id,
                'previous_doctor_id' => $previousDoctorId,
                'previous_clinic_id' => $previousClinicId,
                'new_doctor_id' => $nextDoctorId,
                'new_clinic_id' => $nextClinicId,
                'changed_by_user_id' => optional($request->user())->id,
                'changed_by_name' => optional($request->user())->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.transactions.appointments')
            ->with('status', sprintf(
                'Doctor/clinic updated for transaction #%d. Previous Doctor ID: %s, Assigned: %s (ID: %d), Clinic ID: %s.',
                $transaction->id,
                $previousDoctorId ?? 'NULL',
                $doctor->doctor_name ?? 'N/A',
                $doctor->id,
                $transaction->clinic_id ?? 'NULL'
            ));
    }

    private function appointmentTransactionsQuery(): Builder
    {
        return Transaction::query()
            ->where(function (Builder $query) {
                $query->whereIn('type', ['video_consult', 'excell_export_campaign'])
                    ->orWhere('metadata->order_type', 'video_consult')
                    ->orWhere('metadata->order_type', 'excell_export_campaign');
            });
    }

    private function isAppointmentTransaction(Transaction $transaction): bool
    {
        $type = strtolower((string) ($transaction->type ?? ''));
        $orderType = strtolower((string) data_get($transaction->metadata, 'order_type', ''));

        return in_array($type, ['video_consult', 'excell_export_campaign'], true)
            || in_array($orderType, ['video_consult', 'excell_export_campaign'], true);
    }

    private function latestTransactionDoctorAssignmentLogs(Collection $transactions): Collection
    {
        if (!Schema::hasTable('transaction_doctor_assignment_logs') || $transactions->isEmpty()) {
            return collect();
        }

        $transactionIds = $transactions->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        if ($transactionIds->isEmpty()) {
            return collect();
        }

        return DB::table('transaction_doctor_assignment_logs')
            ->whereIn('transaction_id', $transactionIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('transaction_id')
            ->map(fn (Collection $group) => $group->first());
    }

    private function doctorNameLookupForTransactions(Collection $transactions, Collection $latestAssignmentLogs): array
    {
        $doctorIds = collect();

        foreach ($transactions as $transaction) {
            $doctorIds->push($transaction->doctor_id);

            $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
            $lastAssignment = data_get($metadata, 'last_doctor_assignment');
            if (is_array($lastAssignment)) {
                $doctorIds->push($lastAssignment['previous_doctor_id'] ?? null);
                $doctorIds->push($lastAssignment['new_doctor_id'] ?? null);
            }

            $history = data_get($metadata, 'doctor_assignment_logs', []);
            if (is_array($history)) {
                foreach ($history as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $doctorIds->push($entry['previous_doctor_id'] ?? null);
                    $doctorIds->push($entry['new_doctor_id'] ?? null);
                }
            }

            $dbLog = $latestAssignmentLogs->get($transaction->id);
            if ($dbLog) {
                $doctorIds->push($dbLog->previous_doctor_id ?? null);
                $doctorIds->push($dbLog->new_doctor_id ?? null);
            }
        }

        $ids = $doctorIds
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Doctor::query()
            ->whereIn('id', $ids)
            ->pluck('doctor_name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
            ->all();
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
