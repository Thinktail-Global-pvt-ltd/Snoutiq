<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Models\LegacyQrRedirect;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesDashboardController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    public function dashboard(Request $request): JsonResponse
    {
        $recentDays = max(1, (int) $request->input('recent_days', 30));

        return response()->json($this->buildSummary($recentDays));
    }

    public function page(Request $request): View
    {
        $recentDays = max(1, (int) $request->input('recent_days', 30));
        $scannerStatus = $request->input('scanner_status', 'all');
        $scannerSearch = trim((string) $request->input('scanner_search', ''));
        $scannerPage = max(1, (int) $request->input('scanner_page', 1));

        $clinicStatus = $request->input('clinic_status', 'all');
        $clinicSearch = trim((string) $request->input('clinic_search', ''));

        $summary = $this->buildSummary($recentDays);
        $scannerListing = $this->buildScannerListing($scannerStatus, $scannerSearch, 10, $scannerPage, $recentDays);
        $clinicListing = $this->buildClinicListing($clinicStatus, $clinicSearch, 1000, 1);

        return view('backend.sales.dashboard', [
            'summary' => $summary,
            'scannerPaginator' => $scannerListing['paginator'],
            'scannerRecentWindow' => $scannerListing['recent_days_window'],
            'scannerFilters' => [
                'status' => $scannerStatus,
                'search' => $scannerSearch,
            ],
            'clinicPaginator' => $clinicListing['paginator'],
            'clinicFilters' => [
                'status' => $clinicStatus,
                'search' => $clinicSearch,
            ],
        ]);
    }

    public function qrScanners(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $page = max(1, (int) $request->input('page', 1));
        $recentDays = max(1, (int) $request->input('recent_days', 30));

        $listing = $this->buildScannerListing($status, $search, $perPage, $page, $recentDays);
        $paginator = $listing['paginator'];

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'recent_days_window' => $listing['recent_days_window'],
        ]);
    }

    private function buildSummary(int $recentDays): array
    {
        $recentCutoff = Carbon::now()->subDays($recentDays);

        $totalScanners = LegacyQrRedirect::count();
        $activeScanners = LegacyQrRedirect::where('status', 'active')->count();
        $inactiveScanners = $totalScanners - $activeScanners;
        $totalScanCount = (int) LegacyQrRedirect::sum('scan_count');
        $recentlyScannedCodes = LegacyQrRedirect::whereNotNull('last_scanned_at')
            ->where('last_scanned_at', '>=', $recentCutoff)
            ->count();

        $totalClinics = VetRegisterationTemp::count();
        $activeClinics = VetRegisterationTemp::where('status', 'active')->count();
        $recentClinics = VetRegisterationTemp::where('created_at', '>=', $recentCutoff)->count();

        $petParentsViaScanners = User::whereNotNull('qr_scanner_id')->count();
        $petParentsRecent = User::whereNotNull('qr_scanner_id')
            ->where('created_at', '>=', $recentCutoff)
            ->count();

        $transactionsViaScanner = CallSession::whereNotNull('qr_scanner_id')->count();
        $transactionsRecent = CallSession::whereNotNull('qr_scanner_id')
            ->whereRaw('COALESCE(ended_at, started_at, created_at) >= ?', [$recentCutoff])
            ->count();

        return [
            'scanners' => [
                'total' => $totalScanners,
                'active' => $activeScanners,
                'inactive' => $inactiveScanners,
            ],
            'clinics' => [
                'total' => $totalClinics,
                'active' => $activeClinics,
                'recent' => $recentClinics,
            ],
            'pet_parents' => [
                'total' => $petParentsViaScanners,
                'recent' => $petParentsRecent,
            ],
            'transactions' => [
                'total' => $transactionsViaScanner,
                'recent' => $transactionsRecent,
            ],
            'scanner_scans' => [
                'total' => $totalScanCount,
                'recent_codes' => $recentlyScannedCodes,
            ],
            'recent_days_window' => $recentDays,
        ];
    }

    private function buildScannerListing(?string $status, string $search, int $perPage, int $page, int $recentDays): array
    {
        $recentCutoff = Carbon::now()->subDays($recentDays);

        $query = LegacyQrRedirect::query()
            ->with(['clinic:id,name,status,city,pincode,public_id,claimed_at,created_at'])
            ->orderByDesc(DB::raw('COALESCE(last_scanned_at, created_at)'));

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', '%'.$search.'%')
                    ->orWhere('public_id', 'LIKE', '%'.$search.'%')
                    ->orWhereHas('clinic', function ($clinicQuery) use ($search) {
                        $clinicQuery->where('name', 'LIKE', '%'.$search.'%');
                    });
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $scannerIds = $paginator->getCollection()->pluck('id');
        $clinicIds = $paginator->getCollection()->pluck('clinic_id')->filter()->unique();

        $petParentStats = User::query()
            ->selectRaw('qr_scanner_id, COUNT(*) as total, MAX(created_at) as last_at')
            ->whereIn('qr_scanner_id', $scannerIds)
            ->groupBy('qr_scanner_id')
            ->get()
            ->keyBy('qr_scanner_id');

        $petParentRecentCounts = User::query()
            ->selectRaw('qr_scanner_id, COUNT(*) as total_recent')
            ->whereIn('qr_scanner_id', $scannerIds)
            ->where('created_at', '>=', $recentCutoff)
            ->groupBy('qr_scanner_id')
            ->get()
            ->keyBy('qr_scanner_id');

        $scannerTransactions = CallSession::query()
            ->selectRaw('qr_scanner_id, COUNT(*) as total, MAX(COALESCE(ended_at, started_at, created_at)) as last_at')
            ->whereIn('qr_scanner_id', $scannerIds)
            ->whereNotNull('qr_scanner_id')
            ->groupBy('qr_scanner_id')
            ->get()
            ->keyBy('qr_scanner_id');

        $clinicTransactions = $clinicIds->isNotEmpty()
            ? CallSession::query()
                ->selectRaw('doctors.vet_registeration_id as clinic_id, COUNT(*) as total, MAX(COALESCE(call_sessions.ended_at, call_sessions.started_at, call_sessions.created_at)) as last_at')
                ->join('doctors', 'call_sessions.doctor_id', '=', 'doctors.id')
                ->whereIn('doctors.vet_registeration_id', $clinicIds)
                ->whereNull('call_sessions.qr_scanner_id')
                ->groupBy('doctors.vet_registeration_id')
                ->get()
                ->keyBy('clinic_id')
            : collect();

        $paginator->getCollection()->transform(function (LegacyQrRedirect $scanner) use (
            $petParentStats,
            $petParentRecentCounts,
            $scannerTransactions,
            $clinicTransactions
        ) {
            $petStats = $petParentStats->get($scanner->id);
            $petRecent = $petParentRecentCounts->get($scanner->id);
            $scannerTxn = $scannerTransactions->get($scanner->id);
            $clinicTxn = $scanner->clinic_id ? $clinicTransactions->get($scanner->clinic_id) : null;

            $directTransactions = (int) ($scannerTxn->total ?? 0);
            $clinicTransactionsCount = (int) ($clinicTxn->total ?? 0);
            $totalTransactions = $directTransactions + $clinicTransactionsCount;

            $lastTxnCandidates = collect([
                $scanner->last_transaction_at?->toDateTimeString(),
                $scannerTxn->last_at ?? null,
                $clinicTxn->last_at ?? null,
            ])->filter()->map(fn ($value) => Carbon::parse($value));

            $lastTxn = $lastTxnCandidates->isEmpty() ? null : $lastTxnCandidates->max();

            $lastRegistration = null;
            if ($scanner->last_registration_at) {
                $lastRegistration = $scanner->last_registration_at;
            }
            if ($petStats && $petStats->last_at) {
                $candidate = Carbon::parse($petStats->last_at);
                $lastRegistration = $lastRegistration?->greaterThan($candidate) ? $lastRegistration : $candidate;
            }

            return [
                'id' => $scanner->id,
                'code' => $scanner->code,
                'public_id' => $scanner->public_id,
                'status' => $scanner->status,
                'scan_count' => (int) ($scanner->scan_count ?? 0),
                'last_scanned_at' => $scanner->last_scanned_at?->toIso8601String(),
                'last_registration_at' => $lastRegistration?->toIso8601String(),
                'last_transaction_at' => $lastTxn?->toIso8601String(),
                'pet_parent_count' => (int) ($petStats->total ?? 0),
                'pet_parent_recent_count' => (int) ($petRecent->total_recent ?? 0),
                'transactions_count' => $totalTransactions,
                'direct_transactions_count' => $directTransactions,
                'clinic_transactions_count' => $clinicTransactionsCount,
                'clinic' => $scanner->clinic ? [
                    'id' => $scanner->clinic->id,
                    'name' => $scanner->clinic->name,
                    'status' => $scanner->clinic->status,
                    'city' => $scanner->clinic->city,
                    'pincode' => $scanner->clinic->pincode,
                    'public_id' => $scanner->clinic->public_id,
                    'claimed_at' => $scanner->clinic->claimed_at?->toIso8601String(),
                    'created_at' => $scanner->clinic->created_at?->toIso8601String(),
                ] : null,
            ];
        });

        return [
            'paginator' => $paginator,
            'recent_days_window' => $recentDays,
        ];
    }

    private function buildClinicListing(?string $status, string $search, int $perPage, int $page): array
    {
        $query = VetRegisterationTemp::query()
            ->withCount('doctors')
            ->orderByDesc('created_at');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%'.$search.'%')
                    ->orWhere('city', 'LIKE', '%'.$search.'%')
                    ->orWhere('pincode', 'LIKE', '%'.$search.'%')
                    ->orWhere('public_id', 'LIKE', '%'.$search.'%');
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $clinicIds = $paginator->getCollection()->pluck('id');

        $scannerCounts = LegacyQrRedirect::query()
            ->selectRaw('clinic_id, COUNT(*) as total')
            ->whereIn('clinic_id', $clinicIds)
            ->groupBy('clinic_id')
            ->get()
            ->keyBy('clinic_id');

        $scannerPetParents = User::query()
            ->selectRaw('legacy_qr_redirects.clinic_id as clinic_id, COUNT(users.id) as total')
            ->join('legacy_qr_redirects', 'users.qr_scanner_id', '=', 'legacy_qr_redirects.id')
            ->whereIn('legacy_qr_redirects.clinic_id', $clinicIds)
            ->groupBy('legacy_qr_redirects.clinic_id')
            ->get()
            ->keyBy('clinic_id');

        $clinicTransactions = $clinicIds->isNotEmpty()
            ? CallSession::query()
                ->selectRaw('doctors.vet_registeration_id as clinic_id, COUNT(*) as total')
                ->join('doctors', 'call_sessions.doctor_id', '=', 'doctors.id')
                ->whereIn('doctors.vet_registeration_id', $clinicIds)
                ->groupBy('doctors.vet_registeration_id')
                ->get()
                ->keyBy('clinic_id')
            : collect();

        $paginator->getCollection()->transform(function (VetRegisterationTemp $clinic) use (
            $scannerCounts,
            $scannerPetParents,
            $clinicTransactions
        ) {
            return [
                'id' => $clinic->id,
                'public_id' => $clinic->public_id,
                'name' => $clinic->name,
                'status' => $clinic->status,
                'city' => $clinic->city,
                'pincode' => $clinic->pincode,
                'doctors_count' => $clinic->doctors_count,
                'scanner_count' => (int) ($scannerCounts->get($clinic->id)->total ?? 0),
                'pet_parents_count' => (int) ($scannerPetParents->get($clinic->id)->total ?? 0),
                'transactions_count' => (int) ($clinicTransactions->get($clinic->id)->total ?? 0),
                'created_at' => $clinic->created_at?->toIso8601String(),
                'claimed_at' => $clinic->claimed_at?->toIso8601String(),
            ];
        });

        return [
            'paginator' => $paginator,
        ];
    }

    public function scannerMetrics(Request $request, LegacyQrRedirect $scanner): JsonResponse
    {
        $recentDays = max(1, (int) $request->input('recent_days', 30));
        $recentCutoff = Carbon::now()->subDays($recentDays);

        $petParents = $scanner->petParents()
            ->orderByDesc('created_at')
            ->limit((int) $request->input('pet_parent_limit', 25))
            ->get(['id', 'name', 'phone', 'email', 'created_at']);

        $transactions = $scanner->callSessions()
            ->orderByDesc(DB::raw('COALESCE(ended_at, started_at, created_at)'))
            ->limit((int) $request->input('transaction_limit', 25))
            ->get(['id', 'patient_id', 'doctor_id', 'status', 'payment_status', 'amount_paid', 'currency', 'started_at', 'ended_at', 'created_at']);

        $clinicTransactions = $scanner->clinic_id
            ? CallSession::query()
                ->select(['id', 'patient_id', 'doctor_id', 'status', 'payment_status', 'amount_paid', 'currency', 'started_at', 'ended_at', 'created_at'])
                ->join('doctors', 'call_sessions.doctor_id', '=', 'doctors.id')
                ->where('doctors.vet_registeration_id', $scanner->clinic_id)
                ->whereNull('call_sessions.qr_scanner_id')
                ->orderByDesc(DB::raw('COALESCE(call_sessions.ended_at, call_sessions.started_at, call_sessions.created_at)'))
                ->limit((int) $request->input('clinic_transaction_limit', 10))
                ->get()
            : collect();

        $latestTransactions = collect([$transactions, $clinicTransactions])->filter()->flatten(1);

        $latestTransactionAt = $latestTransactions->map(function ($session) {
            return Carbon::parse($session->ended_at ?? $session->started_at ?? $session->created_at);
        })->max();

        $latestPetParentAt = $petParents->isEmpty()
            ? null
            : $petParents->max('created_at');

        $petParentIds = $petParents->pluck('id');
        $latestPatientSessions = $petParentIds->isNotEmpty()
            ? CallSession::query()
                ->selectRaw('patient_id, MAX(COALESCE(ended_at, started_at, created_at)) as last_at')
                ->whereIn('patient_id', $petParentIds)
                ->groupBy('patient_id')
                ->get()
                ->keyBy('patient_id')
            : collect();

        $petParentsPayload = $petParents->map(function (User $user) use ($latestPatientSessions, $recentCutoff) {
            $lastSession = $latestPatientSessions->get($user->id);
            $lastContact = $lastSession?->last_at ? Carbon::parse($lastSession->last_at) : null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'onboarded_at' => $user->created_at?->toIso8601String(),
                'last_transaction_at' => $lastContact?->toIso8601String(),
                'is_recent' => $user->created_at && $user->created_at->greaterThanOrEqualTo($recentCutoff),
            ];
        });

        $transactionsPayload = $transactions->map(fn (CallSession $session) => $this->formatSession($session));
        $clinicTransactionsPayload = $clinicTransactions->map(fn ($session) => $this->formatSession($session));

        return response()->json([
            'scanner' => [
                'id' => $scanner->id,
                'code' => $scanner->code,
                'public_id' => $scanner->public_id,
                'status' => $scanner->status,
                'scan_count' => (int) ($scanner->scan_count ?? 0),
                'last_scanned_at' => $scanner->last_scanned_at?->toIso8601String(),
                'last_registration_at' => $scanner->last_registration_at?->toIso8601String(),
                'last_transaction_at' => $latestTransactionAt?->toIso8601String(),
                'recent_days_window' => $recentDays,
                'clinic' => $scanner->clinic ? [
                    'id' => $scanner->clinic->id,
                    'name' => $scanner->clinic->name,
                    'status' => $scanner->clinic->status,
                    'city' => $scanner->clinic->city,
                    'pincode' => $scanner->clinic->pincode,
                    'public_id' => $scanner->clinic->public_id,
                ] : null,
            ],
            'pet_parents' => [
                'total' => $scanner->petParents()->count(),
                'recent' => $scanner->petParents()->where('created_at', '>=', $recentCutoff)->count(),
                'latest_onboarded_at' => $latestPetParentAt?->toIso8601String(),
                'entries' => $petParentsPayload,
            ],
            'transactions' => [
                'direct' => [
                    'total' => $scanner->callSessions()->count(),
                    'recent' => $scanner->callSessions()
                        ->whereRaw('COALESCE(ended_at, started_at, created_at) >= ?', [$recentCutoff])
                        ->count(),
                    'entries' => $transactionsPayload,
                ],
                'clinic' => [
                    'total' => $scanner->clinic_id ? $this->clinicTransactionCount($scanner->clinic_id) : 0,
                    'recent' => $scanner->clinic_id
                        ? $this->clinicTransactionCount($scanner->clinic_id, $recentCutoff)
                        : 0,
                    'entries' => $clinicTransactionsPayload,
                ],
            ],
        ]);
    }

    public function vetRegistrations(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $page = max(1, (int) $request->input('page', 1));

        $listing = $this->buildClinicListing($status, $search, $perPage, $page);
        $paginator = $listing['paginator'];

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function notifyDormantPetParents(Request $request, LegacyQrRedirect $scanner): JsonResponse
    {
        $validated = $request->validate([
            'days_without_transaction' => 'nullable|integer|min:1|max:365',
            'dry_run' => 'nullable|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        $days = $validated['days_without_transaction'] ?? 14;
        $dryRun = array_key_exists('dry_run', $validated)
            ? (bool) $validated['dry_run']
            : true;

        $message = $validated['message'] ?? sprintf(
            "Hi from SnoutIQ! We noticed you haven't tried a consult after scanning %s. Need help booking your first visit?",
            $scanner->code
        );

        $petParents = $scanner->petParents()
            ->whereNotNull('phone')
            ->get(['id', 'name', 'phone', 'email', 'created_at']);

        if ($petParents->isEmpty()) {
            return response()->json([
                'scanner_id' => $scanner->id,
                'dry_run' => $dryRun,
                'message' => 'No pet parents linked to this scanner yet.',
                'targets' => [],
            ]);
        }

        $cutoff = Carbon::now()->subDays($days);

        $latestSessions = CallSession::query()
            ->selectRaw('patient_id, MAX(COALESCE(ended_at, started_at, created_at)) as last_at')
            ->whereIn('patient_id', $petParents->pluck('id'))
            ->groupBy('patient_id')
            ->get()
            ->keyBy('patient_id');

        $targets = $petParents->filter(function (User $user) use ($latestSessions, $cutoff) {
            $lastSession = $latestSessions->get($user->id);
            if (! $lastSession || empty($lastSession->last_at)) {
                return true;
            }

            return Carbon::parse($lastSession->last_at)->lessThan($cutoff);
        });

        if ($targets->isEmpty()) {
            return response()->json([
                'scanner_id' => $scanner->id,
                'dry_run' => $dryRun,
                'message' => 'All pet parents linked to this scanner have recent transactions.',
                'targets' => [],
            ]);
        }

        if (! $dryRun && ! $this->whatsApp->isConfigured()) {
            return response()->json([
                'scanner_id' => $scanner->id,
                'dry_run' => $dryRun,
                'message' => 'WhatsApp credentials are not configured; cannot send notifications.',
                'targets' => [],
            ], 503);
        }

        $results = [];

        foreach ($targets as $user) {
            $lastSession = $latestSessions->get($user->id);
            $result = [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'name' => $user->name,
                'last_transaction_at' => $lastSession && $lastSession->last_at
                    ? Carbon::parse($lastSession->last_at)->toIso8601String()
                    : null,
                'status' => $dryRun ? 'pending' : 'sent',
            ];

            if (! $dryRun) {
                try {
                    $this->whatsApp->sendText($user->phone, $message);
                } catch (\Throwable $exception) {
                    $result['status'] = 'failed';
                    $result['error'] = $exception->getMessage();
                }
            }

            $results[] = $result;
        }

        return response()->json([
            'scanner_id' => $scanner->id,
            'dry_run' => $dryRun,
            'targets_notified' => count($results),
            'results' => $results,
        ]);
    }

    private function formatSession(CallSession $session): array
    {
        $timestamp = $session->ended_at ?? $session->started_at ?? $session->created_at;

        return [
            'id' => $session->id,
            'patient_id' => $session->patient_id,
            'doctor_id' => $session->doctor_id,
            'status' => $session->status,
            'payment_status' => $session->payment_status,
            'amount_paid' => $session->amount_paid,
            'currency' => $session->currency,
            'timestamp' => $timestamp?->toIso8601String(),
        ];
    }

    private function clinicTransactionCount(int $clinicId, ?Carbon $cutoff = null): int
    {
        $query = CallSession::query()
            ->join('doctors', 'call_sessions.doctor_id', '=', 'doctors.id')
            ->where('doctors.vet_registeration_id', $clinicId);

        if ($cutoff) {
            $query->whereRaw('COALESCE(call_sessions.ended_at, call_sessions.started_at, call_sessions.created_at) >= ?', [$cutoff]);
        }

        return (int) $query->count();
    }
}
