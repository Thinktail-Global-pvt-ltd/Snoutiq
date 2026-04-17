<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\TransactionInvoiceController;
use App\Models\CustomerTicket;
use App\Models\Doctor;
use App\Models\FcmNotification;
use App\Models\GroomerBooking;
use App\Models\GroomerProfile;
use App\Models\Notification;
use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\VideoApointment;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use App\Services\CallAnalyticsService;
use App\Services\ConsultationBookingWhatsAppService;
use App\Services\DoctorAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPanelController extends Controller
{
    public function __construct(
        private readonly DoctorAvailabilityService $doctorAvailabilityService,
        private readonly CallAnalyticsService $callAnalyticsService,
        private readonly ConsultationBookingWhatsAppService $consultationBookingWhatsAppService,
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

    public function usersBulkDelete(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $searchTerm = trim((string) ($validated['q'] ?? ''));

        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'phone', 'role', 'city', 'created_at'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($searchTerm !== '') {
            $usersQuery->where(function (Builder $query) use ($searchTerm): void {
                $searchLike = '%'.$searchTerm.'%';

                $query->where('name', 'like', $searchLike)
                    ->orWhere('email', 'like', $searchLike)
                    ->orWhere('phone', 'like', $searchLike)
                    ->orWhere('role', 'like', $searchLike)
                    ->orWhere('city', 'like', $searchLike);

                if (is_numeric($searchTerm)) {
                    $query->orWhere('id', (int) $searchTerm);
                }
            });
        }

        $users = $usersQuery->get();
        $totalUsers = User::query()->count();

        return view('admin.users-bulk-delete', [
            'users' => $users,
            'searchQuery' => $searchTerm,
            'totalUsers' => $totalUsers,
        ]);
    }

    public function deleteUsersBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $searchTerm = trim((string) ($validated['q'] ?? ''));
        $filters = array_filter([
            'q' => $searchTerm,
        ], static fn ($value) => $value !== null && $value !== '');

        $userIds = collect($validated['user_ids'] ?? [])
            ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return redirect()
                ->route('admin.users.bulk-delete', $filters)
                ->with('error', 'Select at least one user to delete.');
        }

        $users = User::query()
            ->whereIn('id', $userIds->all())
            ->orderBy('id')
            ->get();

        $deletedUserIds = [];
        $failedDeletes = [];

        foreach ($users as $user) {
            $userId = (int) $user->id;

            try {
                $this->deleteLeadManagementUserAndRelatedData($user);
                $deletedUserIds[] = $userId;
            } catch (\Throwable $e) {
                $failedDeletes[] = '#'.$userId.' - '.$e->getMessage();
            }
        }

        if (empty($deletedUserIds)) {
            return redirect()
                ->route('admin.users.bulk-delete', $filters)
                ->with('error', 'No users were deleted. '.implode(' | ', $failedDeletes));
        }

        $statusMessage = count($deletedUserIds).' user(s) deleted successfully.';
        if (!empty($failedDeletes)) {
            $statusMessage .= ' Failed: '.implode(' | ', $failedDeletes);
        }

        return redirect()
            ->route('admin.users.bulk-delete', $filters)
            ->with('status', $statusMessage);
    }

    public function usersDataHub(Request $request): View
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $searchTerm = trim((string) ($validated['q'] ?? ''));

        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'phone', 'role', 'created_at']);

        if ($searchTerm !== '') {
            $usersQuery->where(function (Builder $query) use ($searchTerm): void {
                $searchLike = '%' . $searchTerm . '%';
                $query->where('name', 'like', $searchLike)
                    ->orWhere('email', 'like', $searchLike)
                    ->orWhere('phone', 'like', $searchLike);
            });
        }

        $users = $usersQuery
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $userIds = $users->getCollection()
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        $petsByUser = collect();
        $petsById = collect();
        $transactionsByUser = collect();
        $prescriptionsByUser = collect();
        $appointmentsByUser = collect();
        $videoConsultsByUser = collect();

        $supports = [
            'pets' => Schema::hasTable('pets') && Schema::hasColumn('pets', 'user_id'),
            'transactions' => Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'user_id'),
            'prescriptions' => Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'user_id'),
            'appointments' => Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'pet_id'),
            'video_apointment' => Schema::hasTable('video_apointment') && Schema::hasColumn('video_apointment', 'user_id'),
        ];

        if ($userIds->isNotEmpty()) {
            if ($supports['pets']) {
                $petColumns = ['id', 'user_id', 'name', 'breed', 'created_at'];
                if (Schema::hasColumn('pets', 'pet_type')) {
                    $petColumns[] = 'pet_type';
                }
                if (Schema::hasColumn('pets', 'type')) {
                    $petColumns[] = 'type';
                }
                if (Schema::hasColumn('pets', 'pet_gender')) {
                    $petColumns[] = 'pet_gender';
                }
                if (Schema::hasColumn('pets', 'gender')) {
                    $petColumns[] = 'gender';
                }

                $pets = Pet::query()
                    ->select(array_unique($petColumns))
                    ->whereIn('user_id', $userIds->all())
                    ->orderByDesc('id')
                    ->get();

                $petsByUser = $pets->groupBy(fn (Pet $pet) => (int) $pet->user_id);
                $petsById = $pets->keyBy(fn (Pet $pet) => (int) $pet->id);
            }

            if ($supports['transactions']) {
                $txColumns = ['id', 'user_id', 'pet_id', 'doctor_id', 'clinic_id', 'status', 'type', 'amount_paise', 'reference', 'created_at'];
                if (Schema::hasColumn('transactions', 'metadata')) {
                    $txColumns[] = 'metadata';
                }

                $transactionsByUser = Transaction::query()
                    ->select(array_unique($txColumns))
                    ->whereIn('user_id', $userIds->all())
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy(fn (Transaction $transaction) => (int) $transaction->user_id);
            }

            if ($supports['prescriptions']) {
                $prescriptionColumns = [
                    'id',
                    'user_id',
                    'pet_id',
                    'doctor_id',
                    'diagnosis',
                    'follow_up_date',
                    'video_inclinic',
                    'created_at',
                ];

                $prescriptionsByUser = Prescription::query()
                    ->select(array_unique($prescriptionColumns))
                    ->whereIn('user_id', $userIds->all())
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy(fn (Prescription $prescription) => (int) $prescription->user_id);
            }

            if ($supports['video_apointment']) {
                $videoColumns = [
                    'id',
                    'user_id',
                    'pet_id',
                    'doctor_id',
                    'clinic_id',
                    'order_id',
                    'call_session',
                    'is_completed',
                    'created_at',
                ];

                $videoConsultsByUser = VideoApointment::query()
                    ->select(array_unique($videoColumns))
                    ->whereIn('user_id', $userIds->all())
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy(fn (VideoApointment $videoApointment) => (int) $videoApointment->user_id);
            }

            if ($supports['appointments']) {
                $petOwnerByPetId = $petsById->mapWithKeys(
                    fn (Pet $pet) => [(int) $pet->id => (int) $pet->user_id]
                );

                $petIds = $petOwnerByPetId->keys()->values();
                if ($petIds->isNotEmpty()) {
                    $appointmentColumns = [
                        'id',
                        'pet_id',
                        'doctor_id',
                        'vet_registeration_id',
                        'appointment_date',
                        'appointment_time',
                        'status',
                        'created_at',
                    ];

                    $appointments = Appointment::query()
                        ->select(array_unique($appointmentColumns))
                        ->whereIn('pet_id', $petIds->all())
                        ->orderByDesc('id')
                        ->get();

                    $appointmentsByUser = $appointments
                        ->groupBy(function (Appointment $appointment) use ($petOwnerByPetId) {
                            return (int) ($petOwnerByPetId->get((int) $appointment->pet_id, 0));
                        })
                        ->filter(fn ($_, $userId) => (int) $userId > 0);
                }
            }
        }

        return view('admin.users-data-hub', [
            'users' => $users,
            'supports' => $supports,
            'petsByUser' => $petsByUser,
            'petsById' => $petsById,
            'transactionsByUser' => $transactionsByUser,
            'prescriptionsByUser' => $prescriptionsByUser,
            'appointmentsByUser' => $appointmentsByUser,
            'videoConsultsByUser' => $videoConsultsByUser,
            'detailLimit' => 5,
        ]);
    }

    public function usersDataHubExportCsv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $searchTerm = trim((string) ($validated['q'] ?? ''));

        $supports = [
            'pets' => Schema::hasTable('pets') && Schema::hasColumn('pets', 'user_id'),
            'transactions' => Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'user_id'),
            'prescriptions' => Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'user_id'),
            'appointments' => Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'pet_id'),
            'video_apointment' => Schema::hasTable('video_apointment') && Schema::hasColumn('video_apointment', 'user_id'),
        ];

        $petColumns = ['id', 'user_id', 'name', 'breed', 'created_at'];
        if ($supports['pets']) {
            if (Schema::hasColumn('pets', 'pet_type')) {
                $petColumns[] = 'pet_type';
            }
            if (Schema::hasColumn('pets', 'type')) {
                $petColumns[] = 'type';
            }
            if (Schema::hasColumn('pets', 'pet_gender')) {
                $petColumns[] = 'pet_gender';
            }
            if (Schema::hasColumn('pets', 'gender')) {
                $petColumns[] = 'gender';
            }
        }

        $txColumns = ['id', 'user_id', 'pet_id', 'status', 'type', 'amount_paise', 'reference', 'created_at'];
        if ($supports['transactions'] && Schema::hasColumn('transactions', 'metadata')) {
            $txColumns[] = 'metadata';
        }

        $prescriptionColumns = [
            'id',
            'user_id',
            'pet_id',
            'doctor_id',
            'diagnosis',
            'follow_up_date',
            'video_inclinic',
            'created_at',
        ];

        $appointmentColumns = [
            'id',
            'pet_id',
            'doctor_id',
            'vet_registeration_id',
            'appointment_date',
            'appointment_time',
            'status',
            'created_at',
        ];

        $videoColumns = [
            'id',
            'user_id',
            'pet_id',
            'doctor_id',
            'clinic_id',
            'order_id',
            'call_session',
            'is_completed',
            'created_at',
        ];

        $filename = 'users-data-hub-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use (
            $searchTerm,
            $supports,
            $petColumns,
            $txColumns,
            $prescriptionColumns,
            $appointmentColumns,
            $videoColumns
        ): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            $clean = static function ($value): string {
                if ($value === null) {
                    return '';
                }

                $text = trim((string) $value);
                return preg_replace('/\s+/', ' ', $text) ?? $text;
            };

            $formatInrFromPaise = static function ($paise): string {
                if (!is_numeric($paise)) {
                    return 'n/a';
                }

                return number_format(((int) $paise) / 100, 2);
            };

            fputcsv($handle, [
                'user_id',
                'name',
                'email',
                'phone',
                'role',
                'joined_at',
                'pets_count',
                'pets_details',
                'transactions_count',
                'transactions_details',
                'prescriptions_count',
                'prescriptions_details',
                'appointments_count',
                'appointments_details',
                'video_consults_count',
                'video_consults_details',
            ]);

            $usersQuery = User::query()
                ->select(['id', 'name', 'email', 'phone', 'role', 'created_at']);

            if ($searchTerm !== '') {
                $usersQuery->where(function (Builder $query) use ($searchTerm): void {
                    $searchLike = '%' . $searchTerm . '%';
                    $query->where('name', 'like', $searchLike)
                        ->orWhere('email', 'like', $searchLike)
                        ->orWhere('phone', 'like', $searchLike);
                });
            }

            $usersQuery
                ->orderBy('id')
                ->chunkById(300, function (Collection $usersChunk) use (
                    $handle,
                    $supports,
                    $petColumns,
                    $txColumns,
                    $prescriptionColumns,
                    $appointmentColumns,
                    $videoColumns,
                    $clean,
                    $formatInrFromPaise
                ): void {
                    $userIds = $usersChunk
                        ->pluck('id')
                        ->filter(fn ($id) => is_numeric($id))
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    $petsByUser = collect();
                    $petsById = collect();
                    $transactionsByUser = collect();
                    $prescriptionsByUser = collect();
                    $appointmentsByUser = collect();
                    $videoConsultsByUser = collect();

                    if ($userIds->isNotEmpty()) {
                        if ($supports['pets']) {
                            $pets = Pet::query()
                                ->select(array_unique($petColumns))
                                ->whereIn('user_id', $userIds->all())
                                ->orderByDesc('id')
                                ->get();

                            $petsByUser = $pets->groupBy(fn (Pet $pet) => (int) $pet->user_id);
                            $petsById = $pets->keyBy(fn (Pet $pet) => (int) $pet->id);
                        }

                        if ($supports['transactions']) {
                            $transactionsByUser = Transaction::query()
                                ->select(array_unique($txColumns))
                                ->whereIn('user_id', $userIds->all())
                                ->orderByDesc('id')
                                ->get()
                                ->groupBy(fn (Transaction $transaction) => (int) $transaction->user_id);
                        }

                        if ($supports['prescriptions']) {
                            $prescriptionsByUser = Prescription::query()
                                ->select(array_unique($prescriptionColumns))
                                ->whereIn('user_id', $userIds->all())
                                ->orderByDesc('id')
                                ->get()
                                ->groupBy(fn (Prescription $prescription) => (int) $prescription->user_id);
                        }

                        if ($supports['video_apointment']) {
                            $videoConsultsByUser = VideoApointment::query()
                                ->select(array_unique($videoColumns))
                                ->whereIn('user_id', $userIds->all())
                                ->orderByDesc('id')
                                ->get()
                                ->groupBy(fn (VideoApointment $videoApointment) => (int) $videoApointment->user_id);
                        }

                        if ($supports['appointments']) {
                            $petOwnerByPetId = $petsById->mapWithKeys(
                                fn (Pet $pet) => [(int) $pet->id => (int) $pet->user_id]
                            );

                            $petIds = $petOwnerByPetId->keys()->values();
                            if ($petIds->isNotEmpty()) {
                                $appointments = Appointment::query()
                                    ->select(array_unique($appointmentColumns))
                                    ->whereIn('pet_id', $petIds->all())
                                    ->orderByDesc('id')
                                    ->get();

                                $appointmentsByUser = $appointments
                                    ->groupBy(function (Appointment $appointment) use ($petOwnerByPetId) {
                                        return (int) ($petOwnerByPetId->get((int) $appointment->pet_id, 0));
                                    })
                                    ->filter(fn ($_, $userId) => (int) $userId > 0);
                            }
                        }
                    }

                    foreach ($usersChunk as $user) {
                        $userId = (int) $user->id;

                        $pets = $petsByUser->get($userId, collect());
                        $transactions = $transactionsByUser->get($userId, collect());
                        $prescriptions = $prescriptionsByUser->get($userId, collect());
                        $appointments = $appointmentsByUser->get($userId, collect());
                        $videoConsults = $videoConsultsByUser->get($userId, collect());

                        $petsDetails = $supports['pets']
                            ? $pets->map(function ($pet) use ($clean) {
                                $petType = $pet->pet_type ?? $pet->type ?? 'n/a';
                                $petGender = $pet->pet_gender ?? $pet->gender ?? 'n/a';

                                return '#' . $pet->id
                                    . ':' . $clean($pet->name ?: 'Unnamed')
                                    . ':' . $clean($petType)
                                    . ':' . $clean($pet->breed ?: 'n/a')
                                    . ':' . $clean($petGender);
                            })->implode(' | ')
                            : 'table_missing';

                        $transactionsDetails = $supports['transactions']
                            ? $transactions->map(function ($transaction) use ($clean, $formatInrFromPaise) {
                                $txType = $transaction->type;
                                if (!$txType && is_array($transaction->metadata ?? null)) {
                                    $txType = data_get($transaction->metadata, 'order_type', 'n/a');
                                }
                                $txType = $txType ?: 'n/a';

                                return '#' . $transaction->id
                                    . ':' . $clean($txType)
                                    . ':' . strtoupper($clean($transaction->status ?: 'n/a'))
                                    . ':₹' . $formatInrFromPaise($transaction->amount_paise)
                                    . ':pet_' . ($transaction->pet_id ?: 'n/a')
                                    . ':' . $clean($transaction->reference ?: 'n/a')
                                    . ':' . $clean($transaction->created_at);
                            })->implode(' | ')
                            : 'table_missing';

                        $prescriptionsDetails = $supports['prescriptions']
                            ? $prescriptions->map(function ($prescription) use ($clean) {
                                return '#' . $prescription->id
                                    . ':pet_' . ($prescription->pet_id ?: 'n/a')
                                    . ':doc_' . ($prescription->doctor_id ?: 'n/a')
                                    . ':' . $clean($prescription->diagnosis ?: 'n/a')
                                    . ':follow_up_' . $clean($prescription->follow_up_date ?: 'n/a')
                                    . ':mode_' . $clean($prescription->video_inclinic ?: 'n/a');
                            })->implode(' | ')
                            : 'table_missing';

                        $appointmentsDetails = $supports['appointments']
                            ? $appointments->map(function ($appointment) use ($clean) {
                                return '#' . $appointment->id
                                    . ':pet_' . ($appointment->pet_id ?: 'n/a')
                                    . ':doc_' . ($appointment->doctor_id ?: 'n/a')
                                    . ':' . strtoupper($clean($appointment->status ?: 'n/a'))
                                    . ':at_' . $clean(($appointment->appointment_date ?: 'n/a') . ' ' . ($appointment->appointment_time ?: ''));
                            })->implode(' | ')
                            : 'table_missing';

                        $videoConsultsDetails = $supports['video_apointment']
                            ? $videoConsults->map(function ($videoConsult) use ($clean) {
                                return '#' . $videoConsult->id
                                    . ':pet_' . ($videoConsult->pet_id ?: 'n/a')
                                    . ':order_' . ($videoConsult->order_id ?: 'n/a')
                                    . ':call_' . $clean($videoConsult->call_session ?: 'n/a')
                                    . ':' . ($videoConsult->is_completed ? 'COMPLETED' : 'PENDING');
                            })->implode(' | ')
                            : 'table_missing';

                        fputcsv($handle, [
                            $userId,
                            $clean($user->name ?: ''),
                            $clean($user->email ?: ''),
                            $clean($user->phone ?: ''),
                            $clean($user->role ?: ''),
                            $clean($user->created_at),
                            $supports['pets'] ? $pets->count() : 'N/A',
                            $petsDetails,
                            $supports['transactions'] ? $transactions->count() : 'N/A',
                            $transactionsDetails,
                            $supports['prescriptions'] ? $prescriptions->count() : 'N/A',
                            $prescriptionsDetails,
                            $supports['appointments'] ? $appointments->count() : 'N/A',
                            $appointmentsDetails,
                            $supports['video_apointment'] ? $videoConsults->count() : 'N/A',
                            $videoConsultsDetails,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function deleteUsersDataHubUser(Request $request, User $user): RedirectResponse
    {
        $filters = array_filter([
            'q' => $request->input('q'),
            'per_page' => $request->input('per_page'),
            'page' => $request->input('page'),
        ], static fn ($value) => $value !== null && $value !== '');

        $userId = (int) $user->id;

        try {
            $this->deleteLeadManagementUserAndRelatedData($user);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.users.data-hub', $filters)
                ->with('error', 'Failed to delete user '.$userId.': '.$e->getMessage());
        }

        return redirect()
            ->route('admin.users.data-hub', $filters)
            ->with('status', 'User '.$userId.' and related data deleted successfully.');
    }

    public function userProfileCompletion(): View
    {
        $deviceTokensTableExists = Schema::hasTable('device_tokens');
        $deviceTokensHasUserId = $deviceTokensTableExists && Schema::hasColumn('device_tokens', 'user_id');
        $deviceTokensHasToken = $deviceTokensTableExists && Schema::hasColumn('device_tokens', 'token');
        $deviceTokensHasLastSeenAt = $deviceTokensTableExists && Schema::hasColumn('device_tokens', 'last_seen_at');

        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'phone', 'created_at'])
            ->orderByDesc('created_at');

        if ($deviceTokensHasUserId && $deviceTokensHasToken) {
            $tokenScope = static function (Builder $query): void {
                $query->whereNotNull('token')
                    ->whereRaw("TRIM(token) <> ''");
            };

            $usersQuery
                ->whereHas('deviceTokens', $tokenScope)
                ->withCount(['deviceTokens as app_device_tokens_count' => $tokenScope]);

            if ($deviceTokensHasLastSeenAt) {
                $usersQuery->withMax(['deviceTokens as app_last_seen_at' => $tokenScope], 'last_seen_at');
            }
        } else {
            // device_tokens table/columns are not available, so no app-installed users can be derived.
            $usersQuery->whereRaw('1 = 0');
        }

        $users = $usersQuery->get();

        return view('admin.user-profile-completion', compact('users', 'deviceTokensTableExists', 'deviceTokensHasLastSeenAt'));
    }

    public function leadManagement(Request $request): View
    {
        try {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:25', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
            'lead_filter' => ['nullable', 'string', 'in:all,neutering,video_follow_up,video_follow_up_video,video_follow_up_in_clinic,vaccination,both'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $limit = (int) ($filters['limit'] ?? 250);
        $perPage = (int) ($filters['per_page'] ?? 5);
        $page = max((int) $request->query('page', 1), 1);
        $leadFilter = strtolower((string) ($filters['lead_filter'] ?? 'all'));
        $searchTerm = trim((string) ($filters['q'] ?? ''));

        $hasUsersTable = Schema::hasTable('users');
        $hasUserCity = $hasUsersTable && Schema::hasColumn('users', 'city');
        $hasUserCreatedAt = $hasUsersTable && Schema::hasColumn('users', 'created_at');
        $leadUserBaseColumns = ['id', 'name', 'email', 'phone'];
        if ($hasUserCity) {
            $leadUserBaseColumns[] = 'city';
        }
        $leadUserColumnsWithCreatedAt = $leadUserBaseColumns;
        if ($hasUserCreatedAt) {
            $leadUserColumnsWithCreatedAt[] = 'created_at';
        }

        $allUsers = collect();
        if ($hasUsersTable) {
            $allUsers = User::query()
                ->select($leadUserColumnsWithCreatedAt)
                ->orderByDesc($hasUserCreatedAt ? 'created_at' : 'id')
                ->get();
        }

        $hasPetsTable = Schema::hasTable('pets');
        $hasPetBreed = $hasPetsTable && Schema::hasColumn('pets', 'breed');
        $hasPetCreatedAt = $hasPetsTable && Schema::hasColumn('pets', 'created_at');
        $hasIsNeutered = $hasPetsTable && Schema::hasColumn('pets', 'is_neutered');
        $hasIsNuetered = $hasPetsTable && Schema::hasColumn('pets', 'is_nuetered');

        $hasPetType = $hasPetsTable && Schema::hasColumn('pets', 'pet_type');
        $hasLegacyPetType = $hasPetsTable && Schema::hasColumn('pets', 'type');
        $petLeadBaseColumns = ['id', 'user_id', 'name'];
        if ($hasPetBreed) {
            $petLeadBaseColumns[] = 'breed';
        }
        if ($hasPetCreatedAt) {
            $petLeadBaseColumns[] = 'created_at';
        }
        if ($hasPetType) {
            $petLeadBaseColumns[] = 'pet_type';
        }
        if ($hasLegacyPetType) {
            $petLeadBaseColumns[] = 'type';
        }

        $neuteringLeadCount = 0;
        $neuteringLeads = collect();
        $runtimeWarnings = [];
        $captureLeadManagementError = static function (string $stage, \Throwable $e) use (&$runtimeWarnings): void {
            $message = sprintf(
                '[lead-management][%s] %s (%s:%d)',
                $stage,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            try {
                Log::error('Lead management stage failed', [
                    'stage' => $stage,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            } catch (\Throwable $logError) {
                // Fall through to PHP error log below.
            }

            error_log($message);
            $runtimeWarnings[] = 'Some lead data could not be loaded for "'.$stage.'".';
        };

        if ($hasPetsTable && ($hasIsNeutered || $hasIsNuetered)) {
            try {
                $petColumns = $petLeadBaseColumns;
                if ($hasIsNeutered) {
                    $petColumns[] = 'is_neutered';
                }
                if ($hasIsNuetered) {
                    $petColumns[] = 'is_nuetered';
                }

                $neuteringBaseQuery = Pet::query()
                    ->where(function (Builder $query) use ($hasIsNeutered, $hasIsNuetered): void {
                        $hasCondition = false;

                        if ($hasIsNeutered) {
                            $query->whereRaw("UPPER(TRIM(COALESCE(is_neutered, ''))) = 'N'");
                            $hasCondition = true;
                        }

                        if ($hasIsNuetered) {
                            if ($hasCondition) {
                                $query->orWhereRaw("UPPER(TRIM(COALESCE(is_nuetered, ''))) = 'N'");
                            } else {
                                $query->whereRaw("UPPER(TRIM(COALESCE(is_nuetered, ''))) = 'N'");
                            }
                        }
                    });

                $neuteringLeadCount = (clone $neuteringBaseQuery)->count();

                $neuteringLeads = $neuteringBaseQuery
                    ->select($petColumns)
                    ->with(['owner:' . implode(',', $leadUserBaseColumns)])
                    ->orderByDesc($hasPetCreatedAt ? 'created_at' : 'id')
                    ->limit($limit)
                    ->get();
            } catch (\Throwable $e) {
                $captureLeadManagementError('neutering_leads', $e);
                $neuteringLeadCount = 0;
                $neuteringLeads = collect();
            }
        }

        $hasTransactionsTable = Schema::hasTable('transactions');
        $hasPrescriptionsTable = Schema::hasTable('prescriptions');
        $hasTransactionType = $hasTransactionsTable && Schema::hasColumn('transactions', 'type');
        $hasTransactionMetadata = $hasTransactionsTable && Schema::hasColumn('transactions', 'metadata');
        $hasPrescriptionCallSession = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'call_session');
        $hasPrescriptionCreatedAt = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'created_at');
        $hasPrescriptionDoctorId = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'doctor_id');
        $hasPrescriptionPetId = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'pet_id');
        $hasPrescriptionDiagnosis = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'diagnosis');
        $hasPrescriptionDiseaseName = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'disease_name');
        $hasPrescriptionFollowUpDate = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'follow_up_date');
        $hasPrescriptionFollowUpType = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'follow_up_type');
        $hasPrescriptionVideoInclinic = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'video_inclinic');

        $transactionSessionColumn = null;
        if ($hasTransactionsTable && Schema::hasColumn('transactions', 'call_session')) {
            $transactionSessionColumn = 'call_session';
        } elseif ($hasTransactionsTable && Schema::hasColumn('transactions', 'channel_name')) {
            $transactionSessionColumn = 'channel_name';
        }

        $supportsVideoFollowUpLeads = $hasTransactionType
            && $hasPrescriptionCallSession
            && $hasPrescriptionFollowUpDate
            && is_string($transactionSessionColumn);
        $supportsVideoFollowUpModeSplit = $supportsVideoFollowUpLeads && $hasPrescriptionVideoInclinic;

        $videoFollowUpLeadCount = 0;
        $videoFollowUpVideoLeadCount = 0;
        $videoFollowUpInClinicLeadCount = 0;
        $videoFollowUpLeads = collect();

        if ($supportsVideoFollowUpLeads) {
            try {
                $latestFollowUpPrescriptionBySession = DB::table('prescriptions as p')
                    ->selectRaw('MAX(p.id) as latest_prescription_id, p.call_session as call_session_key')
                    ->whereNotNull('p.call_session')
                    ->where('p.call_session', '!=', '')
                    ->whereNotNull('p.follow_up_date')
                    ->groupBy('p.call_session');

                $videoFollowUpBaseQuery = Transaction::query()
                    ->where(function (Builder $query) use ($hasTransactionMetadata): void {
                        $query->whereIn('transactions.type', ['video_consult', 'excell_export_campaign']);

                        if ($hasTransactionMetadata) {
                            // Avoid DB JSON extraction on malformed legacy payloads.
                            $query->orWhere(function (Builder $metaQuery): void {
                                $metaQuery->where('transactions.metadata', 'like', '%"order_type":"video_consult"%')
                                    ->orWhere('transactions.metadata', 'like', '%"order_type":"excell_export_campaign"%');
                            });
                        }
                    })
                    ->whereNotNull("transactions.{$transactionSessionColumn}")
                    ->where("transactions.{$transactionSessionColumn}", '!=', '')
                    ->joinSub($latestFollowUpPrescriptionBySession, 'latest_follow_up_prescription_by_session', function ($join) use ($transactionSessionColumn): void {
                        $join->on(
                            'latest_follow_up_prescription_by_session.call_session_key',
                            '=',
                            "transactions.{$transactionSessionColumn}"
                        );
                    })
                    ->join(
                        'prescriptions as lead_prescription',
                        'lead_prescription.id',
                        '=',
                        'latest_follow_up_prescription_by_session.latest_prescription_id'
                    );

                $videoFollowUpLeadCount = (clone $videoFollowUpBaseQuery)->count('transactions.id');
                if ($supportsVideoFollowUpModeSplit) {
                    $videoFollowUpVideoLeadCount = (clone $videoFollowUpBaseQuery)
                        ->whereRaw("LOWER(TRIM(COALESCE(lead_prescription.video_inclinic, ''))) IN ('video', 'video_consult', 'video_consultation')")
                        ->count('transactions.id');

                    $videoFollowUpInClinicLeadCount = (clone $videoFollowUpBaseQuery)
                        ->whereRaw("LOWER(TRIM(COALESCE(lead_prescription.video_inclinic, ''))) IN ('in_clinic', 'inclinic', 'in-clinic', 'clinic')")
                        ->count('transactions.id');
                }

                $videoFollowUpRelations = ['user:' . implode(',', $leadUserBaseColumns)];

                $videoFollowUpLeads = $videoFollowUpBaseQuery
                    ->select('transactions.*')
                    ->addSelect([
                        'lead_prescription_id' => DB::raw('lead_prescription.id'),
                        'lead_follow_up_date' => DB::raw('lead_prescription.follow_up_date'),
                        'lead_call_session' => DB::raw('lead_prescription.call_session'),
                        'lead_video_inclinic' => $hasPrescriptionVideoInclinic
                            ? DB::raw('lead_prescription.video_inclinic')
                            : DB::raw('NULL'),
                    ])
                    ->with($videoFollowUpRelations)
                    ->orderBy('lead_prescription.follow_up_date')
                    ->orderByDesc('transactions.id')
                    ->limit($limit)
                    ->get();
            } catch (\Throwable $e) {
                $captureLeadManagementError('video_follow_up_leads', $e);
                $videoFollowUpLeadCount = 0;
                $videoFollowUpVideoLeadCount = 0;
                $videoFollowUpInClinicLeadCount = 0;
                $videoFollowUpLeads = collect();
            }
        }

        $initializeLeadUser = static function (?User $user, int $fallbackUserId = 0): array {
            return [
                'id' => $user?->id ? (int) $user->id : $fallbackUserId,
                'name' => $user?->name,
                'email' => $user?->email,
                'phone' => $user?->phone,
                'city' => $user?->city,
                'user_created_at' => $user?->created_at ? (string) $user->created_at : null,
                'prescription_follow_up_date' => null,
                'prescription_follow_up_type' => null,
                'has_neutering' => false,
                'has_video_follow_up' => false,
                'has_video_follow_up_video' => false,
                'has_video_follow_up_in_clinic' => false,
                'has_vaccination_reminder' => false,
                'neutering_pet_count' => 0,
                'neutering_pet_names' => [],
                'video_follow_up_count' => 0,
                'video_follow_up_video_count' => 0,
                'video_follow_up_in_clinic_count' => 0,
                'next_follow_up_date' => null,
                'next_video_follow_up_date' => null,
                'next_in_clinic_follow_up_date' => null,
                'neutering_notification_count' => 0,
                'notified_neutering_pet_ids' => [],
                'notified_neutering_pet_names' => [],
                'last_neutering_notification_at' => null,
                'vaccination_notification_count' => 0,
                'notified_vaccination_pet_ids' => [],
                'notified_vaccination_pet_names' => [],
                'last_vaccination_notification_at' => null,
                'all_notifications_count' => 0,
                'all_notifications' => [],
                'conversion_captured' => false,
                'conversion_notification_id' => null,
                'conversion_notification_title' => null,
                'conversion_notification_text' => null,
                'conversion_notification_type' => null,
                'conversion_notification_bucket' => null,
                'conversion_notification_at' => null,
                'conversion_transaction_id' => null,
                'conversion_transaction_type' => null,
                'conversion_transaction_status' => null,
                'conversion_transaction_at' => null,
                'conversion_transaction_doctor_id' => null,
                'conversion_transaction_doctor_name' => null,
                'conversion_transaction_clinic_id' => null,
                'conversion_transaction_clinic_name' => null,
                'related_transactions' => [],
                'related_prescriptions' => [],
                'conversion_lag_minutes' => null,
                'crm_activity_logs' => [],
                'crm_next_action' => null,
            ];
        };

        $targetUsers = $allUsers->mapWithKeys(
            static fn (User $user): array => [(int) $user->id => $initializeLeadUser($user, (int) $user->id)]
        );

        $normalizeDateTime = static function ($value): ?string {
            if (empty($value)) {
                return null;
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->toDateTimeString();
            } catch (\Throwable $e) {
                return null;
            }
        };

        $matchesLeadSearch = static function (array $leadUser, string $searchTerm): bool {
            if ($searchTerm === '') {
                return true;
            }

            $needle = strtolower(trim($searchTerm));
            if ($needle === '') {
                return true;
            }

            $searchableValues = [
                $leadUser['id'] ?? '',
                $leadUser['name'] ?? '',
                $leadUser['email'] ?? '',
                $leadUser['phone'] ?? '',
                $leadUser['city'] ?? '',
                $leadUser['prescription_follow_up_type'] ?? '',
                $leadUser['conversion_transaction_type'] ?? '',
            ];

            foreach (['neutering_pet_names', 'notified_vaccination_pet_names'] as $petKey) {
                foreach (($leadUser[$petKey] ?? []) as $petValue) {
                    $searchableValues[] = $petValue;
                }
            }

            $categoryTerms = [];
            if (!empty($leadUser['has_neutering'])) {
                $categoryTerms[] = 'neutering';
            }
            if (!empty($leadUser['has_video_follow_up'])) {
                $categoryTerms[] = 'follow up';
            }
            if (!empty($leadUser['has_video_follow_up_video'])) {
                $categoryTerms[] = 'video follow up';
            }
            if (!empty($leadUser['has_video_follow_up_in_clinic'])) {
                $categoryTerms[] = 'in clinic';
            }
            if (!empty($leadUser['has_vaccination_reminder'])) {
                $categoryTerms[] = 'vaccination';
            }

            $searchableValues = array_merge($searchableValues, $categoryTerms);

            foreach ($searchableValues as $value) {
                $haystack = strtolower(trim((string) $value));
                if ($haystack !== '' && str_contains($haystack, $needle)) {
                    return true;
                }
            }

            return false;
        };

        $normalizeSessionKey = static fn ($value): string => strtolower(trim((string) $value));

        foreach ($neuteringLeads as $petLead) {
            $owner = $petLead->owner;
            $ownerId = is_numeric($petLead->user_id) ? (int) $petLead->user_id : null;
            if ($ownerId === null || $ownerId <= 0) {
                continue;
            }

            if (!$targetUsers->has($ownerId)) {
                $targetUsers->put($ownerId, $initializeLeadUser($owner, $ownerId));
            }

            $leadUser = $targetUsers->get($ownerId);
            $leadUser['has_neutering'] = true;
            $leadUser['neutering_pet_count'] = (int) $leadUser['neutering_pet_count'] + 1;

            $petName = trim((string) ($petLead->name ?? ''));
            if ($petName !== '' && !in_array($petName, $leadUser['neutering_pet_names'], true)) {
                $leadUser['neutering_pet_names'][] = $petName;
            }

            $targetUsers->put($ownerId, $leadUser);
        }

        foreach ($videoFollowUpLeads as $followUpLead) {
            $user = $followUpLead->user;
            $userId = is_numeric($followUpLead->user_id) ? (int) $followUpLead->user_id : null;
            if ($userId === null || $userId <= 0) {
                continue;
            }

            if (!$targetUsers->has($userId)) {
                $targetUsers->put($userId, $initializeLeadUser($user, $userId));
            }

            $leadUser = $targetUsers->get($userId);
            $leadUser['has_video_follow_up'] = true;
            $leadUser['video_follow_up_count'] = (int) $leadUser['video_follow_up_count'] + 1;

            $followUpModeRaw = strtolower(trim((string) ($followUpLead->getAttribute('lead_video_inclinic') ?? '')));
            $isVideoFollowUp = in_array($followUpModeRaw, ['video', 'video_consult', 'video_consultation'], true);
            $isInClinicFollowUp = in_array($followUpModeRaw, ['in_clinic', 'inclinic', 'in-clinic', 'clinic'], true);

            if ($isVideoFollowUp) {
                $leadUser['has_video_follow_up_video'] = true;
                $leadUser['video_follow_up_video_count'] = (int) $leadUser['video_follow_up_video_count'] + 1;
            } elseif ($isInClinicFollowUp) {
                $leadUser['has_video_follow_up_in_clinic'] = true;
                $leadUser['video_follow_up_in_clinic_count'] = (int) $leadUser['video_follow_up_in_clinic_count'] + 1;
            }

            $followUpDate = null;
            $rawFollowUpDate = $followUpLead->getAttribute('lead_follow_up_date');
            if (!empty($rawFollowUpDate)) {
                try {
                    $followUpDate = \Illuminate\Support\Carbon::parse($rawFollowUpDate)->toDateString();
                } catch (\Throwable $e) {
                    $followUpDate = null;
                }
            }

            if ($followUpDate !== null) {
                $existingDate = $leadUser['next_follow_up_date'];
                if ($existingDate === null || strcmp($followUpDate, (string) $existingDate) < 0) {
                    $leadUser['next_follow_up_date'] = $followUpDate;
                }

                if ($isVideoFollowUp) {
                    $existingVideoDate = $leadUser['next_video_follow_up_date'];
                    if ($existingVideoDate === null || strcmp($followUpDate, (string) $existingVideoDate) < 0) {
                        $leadUser['next_video_follow_up_date'] = $followUpDate;
                    }
                } elseif ($isInClinicFollowUp) {
                    $existingInClinicDate = $leadUser['next_in_clinic_follow_up_date'];
                    if ($existingInClinicDate === null || strcmp($followUpDate, (string) $existingInClinicDate) < 0) {
                        $leadUser['next_in_clinic_follow_up_date'] = $followUpDate;
                    }
                }
            }

            $targetUsers->put($userId, $leadUser);
        }

        $supportsFcmNotifications = Schema::hasTable('fcm_notifications')
            && Schema::hasColumn('fcm_notifications', 'user_id');
        $supportsNeuteringNotificationJoin = $supportsFcmNotifications
            && Schema::hasColumn('fcm_notifications', 'data_payload');
        $fcmHasCallSession = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'call_session');
        $supportsFollowUpNotificationJoin = $fcmHasCallSession && $hasPrescriptionCallSession;
        $fcmHasStatus = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'status');
        $fcmHasSentAt = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'sent_at');
        $fcmHasCreatedAt = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'created_at');
        $fcmHasTitle = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'title');
        $fcmHasNotificationText = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'notification_text');
        $fcmHasNotificationType = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'notification_type');
        $fcmHasClicked = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'clicked');
        $fcmHasClickedAt = $supportsFcmNotifications && Schema::hasColumn('fcm_notifications', 'clicked_at');
        $supportsVaccinationNotificationJoin = $supportsFcmNotifications
            && ($fcmHasNotificationType || $supportsNeuteringNotificationJoin);
        $vaccinationReminderType = 'pet_vaccination_upcoming_reminder';
        $vaccinationNotificationTypes = [$vaccinationReminderType, 'vaccination_milestone'];
        $vaccinationTypePlaceholders = implode(',', array_fill(0, count($vaccinationNotificationTypes), '?'));
        $maxFcmScanRows = min(max($limit * 20, 2000), 10000);
        $supportsNotificationRecords = Schema::hasTable('notifications')
            && Schema::hasColumn('notifications', 'user_id');
        $notificationHasPetId = $supportsNotificationRecords && Schema::hasColumn('notifications', 'pet_id');
        $notificationHasType = $supportsNotificationRecords && Schema::hasColumn('notifications', 'type');
        $notificationHasTitle = $supportsNotificationRecords && Schema::hasColumn('notifications', 'title');
        $notificationHasBody = $supportsNotificationRecords && Schema::hasColumn('notifications', 'body');
        $notificationHasPayload = $supportsNotificationRecords && Schema::hasColumn('notifications', 'payload');
        $notificationHasStatus = $supportsNotificationRecords && Schema::hasColumn('notifications', 'status');
        $notificationHasChannel = $supportsNotificationRecords && Schema::hasColumn('notifications', 'channel');
        $notificationHasSentAt = $supportsNotificationRecords && Schema::hasColumn('notifications', 'sent_at');
        $notificationHasCreatedAt = $supportsNotificationRecords && Schema::hasColumn('notifications', 'created_at');

        $resolveNotificationTitle = static function ($fcmRow, array $dataPayload = []) use ($fcmHasTitle): ?string {
            $titleRaw = trim((string) (
                ($fcmHasTitle ? ($fcmRow->title ?? '') : '')
                ?: (data_get($dataPayload, 'title') ?? '')
            ));

            return $titleRaw !== '' ? $titleRaw : null;
        };

        $resolveNotificationText = static function ($fcmRow, array $dataPayload = []) use ($fcmHasNotificationText): ?string {
            $textRaw = trim((string) (
                ($fcmHasNotificationText ? ($fcmRow->notification_text ?? '') : '')
                ?: (data_get($dataPayload, 'notification_text') ?? '')
                ?: (data_get($dataPayload, 'body') ?? '')
                ?: (data_get($dataPayload, 'message') ?? '')
            ));

            return $textRaw !== '' ? $textRaw : null;
        };

        $resolveNotificationType = static function ($fcmRow, array $dataPayload = []) use ($fcmHasNotificationType): string {
            $notificationTypeRaw = trim((string) (
                ($fcmHasNotificationType ? ($fcmRow->notification_type ?? '') : '')
                ?: (data_get($dataPayload, 'type') ?? '')
            ));

            return $notificationTypeRaw !== '' ? $notificationTypeRaw : 'unknown';
        };

        $resolveNotificationBucket = static function (string $notificationType, array $dataPayload = []) use ($vaccinationNotificationTypes): ?string {
            $type = strtolower(trim($notificationType));
            if ($type === '' || $type === 'unknown') {
                $type = strtolower(trim((string) data_get($dataPayload, 'type')));
            }

            if ($type === 'pet_neutering_reminder') {
                return 'neutering';
            }

            if (in_array($type, $vaccinationNotificationTypes, true)) {
                return 'vaccination';
            }

            if ($type === 'pp_user_created') {
                return 'onboarding';
            }

            if ($type === 'profile_completion') {
                return 'profile_completion';
            }

            if (
                str_contains($type, 'follow_up')
                || str_contains($type, 'follow-up')
                || str_contains($type, 'followup')
            ) {
                return 'follow_up';
            }

            return null;
        };

        $resolveOriginNotificationId = static function (array $dataPayload = []): ?int {
            $notificationId = data_get($dataPayload, 'notification_id');
            if (is_numeric($notificationId) && (int) $notificationId > 0) {
                return (int) $notificationId;
            }

            return null;
        };

        $resolveNotificationTimestamp = static function ($fcmRow) use ($fcmHasSentAt, $fcmHasCreatedAt, $normalizeDateTime): ?string {
            $timestamp = $fcmHasSentAt
                ? $normalizeDateTime($fcmRow->sent_at)
                : null;

            if ($timestamp === null && $fcmHasCreatedAt) {
                $timestamp = $normalizeDateTime($fcmRow->created_at);
            }

            return $timestamp;
        };

        $isDeliveredNotification = static function ($fcmRow) use ($fcmHasStatus, $fcmHasSentAt, $normalizeDateTime): bool {
            if ($fcmHasStatus) {
                $status = strtolower(trim((string) ($fcmRow->status ?? '')));
                if ($status === 'sent') {
                    return true;
                }
            }

            return $fcmHasSentAt && $normalizeDateTime($fcmRow->sent_at) !== null;
        };

        if ($supportsFcmNotifications) {
            try {
            // 1) Neutering notifications: data_payload.pet_id -> neutering lead pets.
            if ($supportsNeuteringNotificationJoin && $neuteringLeads->isNotEmpty()) {
                $neuteringPetLookup = $neuteringLeads
                    ->filter(fn (Pet $pet) => is_numeric($pet->id) && (int) $pet->id > 0)
                    ->mapWithKeys(function (Pet $pet): array {
                        $petId = (int) $pet->id;
                        $petName = trim((string) ($pet->name ?? ''));
                        $ownerId = is_numeric($pet->user_id) ? (int) $pet->user_id : 0;

                        return [
                            $petId => [
                                'pet_name' => $petName,
                                'user_id' => $ownerId,
                            ],
                        ];
                    });

                $neuteringUserIds = $neuteringPetLookup
                    ->pluck('user_id')
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->unique()
                    ->values();

                if ($neuteringPetLookup->isNotEmpty() && $neuteringUserIds->isNotEmpty()) {
                    $fcmNeuteringQuery = FcmNotification::query()
                        ->select(['id', 'user_id', 'data_payload']);

                    if ($fcmHasNotificationType) {
                        $fcmNeuteringQuery->addSelect('notification_type');
                    }
                    if ($fcmHasStatus) {
                        $fcmNeuteringQuery->addSelect('status');
                    }
                    if ($fcmHasTitle) {
                        $fcmNeuteringQuery->addSelect('title');
                    }
                    if ($fcmHasNotificationText) {
                        $fcmNeuteringQuery->addSelect('notification_text');
                    }
                    if ($fcmHasSentAt) {
                        $fcmNeuteringQuery->addSelect('sent_at');
                    }
                    if ($fcmHasCreatedAt) {
                        $fcmNeuteringQuery->addSelect('created_at');
                    }
                    if ($fcmHasClicked) {
                        $fcmNeuteringQuery->addSelect('clicked');
                    }
                    if ($fcmHasClickedAt) {
                        $fcmNeuteringQuery->addSelect('clicked_at');
                    }

                    $fcmNeuteringRows = $fcmNeuteringQuery
                        ->whereIn('user_id', $neuteringUserIds->all())
                        ->whereNotNull('data_payload')
                        ->orderByDesc('id')
                        ->limit($maxFcmScanRows)
                        ->get();

                    foreach ($fcmNeuteringRows as $fcmRow) {
                        $dataPayload = is_array($fcmRow->data_payload) ? $fcmRow->data_payload : [];
                        $notificationType = $resolveNotificationType($fcmRow, $dataPayload);
                        $notificationTitle = $resolveNotificationTitle($fcmRow, $dataPayload);
                        $notificationText = $resolveNotificationText($fcmRow, $dataPayload);

                        if (strtolower($notificationType) !== 'pet_neutering_reminder') {
                            continue;
                        }

                        $petIdRaw = data_get($dataPayload, 'pet_id');
                        if (!is_numeric($petIdRaw)) {
                            continue;
                        }

                        $petId = (int) $petIdRaw;
                        if ($petId <= 0 || !$neuteringPetLookup->has($petId)) {
                            continue;
                        }

                        if (!$isDeliveredNotification($fcmRow)) {
                            continue;
                        }

                        $petMeta = $neuteringPetLookup->get($petId);
                        $userIdRaw = $fcmRow->user_id
                            ?? data_get($dataPayload, 'user_id')
                            ?? data_get($petMeta, 'user_id');
                        if (!is_numeric($userIdRaw)) {
                            continue;
                        }

                        $userId = (int) $userIdRaw;
                        if ($userId <= 0) {
                            continue;
                        }

                        if (!$targetUsers->has($userId)) {
                            $targetUsers->put($userId, $initializeLeadUser(null, $userId));
                        }

                        $leadUser = $targetUsers->get($userId);
                        if (!is_array($leadUser)) {
                            continue;
                        }

                        if (!in_array($petId, $leadUser['notified_neutering_pet_ids'], true)) {
                            $leadUser['notified_neutering_pet_ids'][] = $petId;
                            $leadUser['neutering_notification_count'] = (int) $leadUser['neutering_notification_count'] + 1;
                        }

                        $petName = trim((string) (data_get($petMeta, 'pet_name') ?? ''));
                        if ($petName !== '' && !in_array($petName, $leadUser['notified_neutering_pet_names'], true)) {
                            $leadUser['notified_neutering_pet_names'][] = $petName;
                        }

                        $timestamp = $resolveNotificationTimestamp($fcmRow);
                        if ($timestamp !== null) {
                            $currentLastSentAt = $leadUser['last_neutering_notification_at'];
                            if ($currentLastSentAt === null || strcmp($timestamp, (string) $currentLastSentAt) > 0) {
                                $leadUser['last_neutering_notification_at'] = $timestamp;
                            }
                        }

                        $leadUser['all_notifications'][] = [
                            'id' => (int) ($fcmRow->id ?? 0),
                            'origin_notification_id' => $resolveOriginNotificationId($dataPayload),
                            'notification_title' => $notificationTitle,
                            'notification_text' => $notificationText,
                            'notification_type' => $notificationType,
                            'timestamp' => $timestamp,
                            'status' => $fcmHasStatus ? strtolower(trim((string) ($fcmRow->status ?? ''))) : null,
                            'clicked' => $fcmHasClicked ? (bool) ($fcmRow->clicked ?? false) : null,
                            'clicked_at' => $fcmHasClickedAt ? $normalizeDateTime($fcmRow->clicked_at ?? null) : null,
                            'bucket' => 'neutering',
                            'pet_id' => $petId,
                            'call_session' => null,
                            'source' => 'fcm_notifications',
                        ];

                        $targetUsers->put($userId, $leadUser);
                    }
                }
            }

            // 2) Follow-up notifications: fcm_notifications.call_session = prescriptions.call_session.
            if ($supportsFollowUpNotificationJoin && $videoFollowUpLeads->isNotEmpty()) {
                $followUpSessionToUserIds = [];
                $followUpLeadUserIds = [];
                $normalizeSessionKey = static fn ($value): string => strtolower(trim((string) $value));
                $isFollowUpNotificationType = static function (string $type): bool {
                    $type = strtolower(trim($type));
                    if ($type === '') {
                        return false;
                    }

                    return str_contains($type, 'follow_up')
                        || str_contains($type, 'follow-up')
                        || str_contains($type, 'followup');
                };

                foreach ($videoFollowUpLeads as $followUpLead) {
                    $userId = is_numeric($followUpLead->user_id) ? (int) $followUpLead->user_id : 0;
                    if ($userId <= 0) {
                        continue;
                    }

                    $followUpLeadUserIds[$userId] = true;

                    $sessionCandidates = [
                        $followUpLead->getAttribute('lead_call_session'),
                        $followUpLead->getAttribute('channel_name'),
                    ];

                    if (is_string($transactionSessionColumn) && $transactionSessionColumn !== 'channel_name') {
                        $sessionCandidates[] = $followUpLead->getAttribute($transactionSessionColumn);
                    }

                    foreach ($sessionCandidates as $sessionCandidate) {
                        $sessionKey = $normalizeSessionKey($sessionCandidate);
                        if ($sessionKey === '') {
                            continue;
                        }

                        if (!isset($followUpSessionToUserIds[$sessionKey])) {
                            $followUpSessionToUserIds[$sessionKey] = [];
                        }

                        $followUpSessionToUserIds[$sessionKey][$userId] = true;
                    }
                }

                if (!empty($followUpLeadUserIds)) {
                    $fcmFollowUpQuery = FcmNotification::query()
                        ->select(['id', 'user_id', 'call_session']);

                    if ($supportsNeuteringNotificationJoin) {
                        $fcmFollowUpQuery->addSelect('data_payload');
                    }
                    if ($fcmHasNotificationType) {
                        $fcmFollowUpQuery->addSelect('notification_type');
                    }
                    if ($fcmHasStatus) {
                        $fcmFollowUpQuery->addSelect('status');
                    }
                    if ($fcmHasTitle) {
                        $fcmFollowUpQuery->addSelect('title');
                    }
                    if ($fcmHasNotificationText) {
                        $fcmFollowUpQuery->addSelect('notification_text');
                    }
                    if ($fcmHasSentAt) {
                        $fcmFollowUpQuery->addSelect('sent_at');
                    }
                    if ($fcmHasCreatedAt) {
                        $fcmFollowUpQuery->addSelect('created_at');
                    }
                    if ($fcmHasClicked) {
                        $fcmFollowUpQuery->addSelect('clicked');
                    }
                    if ($fcmHasClickedAt) {
                        $fcmFollowUpQuery->addSelect('clicked_at');
                    }

                    $fcmFollowUpRows = $fcmFollowUpQuery
                        ->whereIn('user_id', array_keys($followUpLeadUserIds))
                        ->orderByDesc('id')
                        ->limit($maxFcmScanRows)
                        ->get();

                    foreach ($fcmFollowUpRows as $fcmRow) {
                        if (!$isDeliveredNotification($fcmRow)) {
                            continue;
                        }

                        $dataPayload = ($supportsNeuteringNotificationJoin && is_array($fcmRow->data_payload))
                            ? $fcmRow->data_payload
                            : [];
                        $notificationType = $resolveNotificationType($fcmRow, $dataPayload);
                        $notificationTitle = $resolveNotificationTitle($fcmRow, $dataPayload);
                        $notificationText = $resolveNotificationText($fcmRow, $dataPayload);
                        $payloadType = trim((string) data_get($dataPayload, 'type'));
                        $sessionKey = $normalizeSessionKey($fcmRow->call_session ?? '');

                        $matchedSessionUserIds = [];
                        if ($sessionKey !== '' && isset($followUpSessionToUserIds[$sessionKey])) {
                            $matchedSessionUserIds = array_keys($followUpSessionToUserIds[$sessionKey]);
                        }

                        $isFollowUpRow = !empty($matchedSessionUserIds)
                            || $isFollowUpNotificationType($notificationType)
                            || $isFollowUpNotificationType($payloadType);

                        if (!$isFollowUpRow) {
                            continue;
                        }

                        $timestamp = $resolveNotificationTimestamp($fcmRow);
                        $status = $fcmHasStatus ? strtolower(trim((string) ($fcmRow->status ?? ''))) : null;
                        $fcmCallSession = trim((string) ($fcmRow->call_session ?? ''));

                        $fcmUserId = is_numeric($fcmRow->user_id) ? (int) $fcmRow->user_id : 0;

                        $candidateUserIds = $matchedSessionUserIds;
                        if ($fcmUserId > 0 && isset($followUpLeadUserIds[$fcmUserId])) {
                            $candidateUserIds = [$fcmUserId];
                        } elseif (empty($candidateUserIds) && $fcmUserId > 0) {
                            continue;
                        }

                        if (empty($candidateUserIds)) {
                            continue;
                        }

                        foreach ($candidateUserIds as $userId) {
                            $userId = (int) $userId;
                            if ($userId <= 0) {
                                continue;
                            }

                            if (!$targetUsers->has($userId)) {
                                $targetUsers->put($userId, $initializeLeadUser(null, $userId));
                            }

                            $leadUser = $targetUsers->get($userId);
                            if (!is_array($leadUser)) {
                                continue;
                            }

                            $leadUser['all_notifications'][] = [
                                'id' => (int) ($fcmRow->id ?? 0),
                                'origin_notification_id' => $resolveOriginNotificationId($dataPayload),
                                'notification_title' => $notificationTitle,
                                'notification_text' => $notificationText,
                                'notification_type' => $notificationType,
                                'timestamp' => $timestamp,
                                'status' => $status,
                                'clicked' => $fcmHasClicked ? (bool) ($fcmRow->clicked ?? false) : null,
                                'clicked_at' => $fcmHasClickedAt ? $normalizeDateTime($fcmRow->clicked_at ?? null) : null,
                                'bucket' => 'follow_up',
                                'pet_id' => null,
                                'call_session' => $fcmCallSession !== '' ? $fcmCallSession : trim((string) data_get($dataPayload, 'call_session')),
                                'source' => 'fcm_notifications',
                            ];

                            $targetUsers->put($userId, $leadUser);
                        }
                    }
                }
            }

            // 3) Vaccination notifications: fcm_notifications.user_id + notification type.
            if ($supportsVaccinationNotificationJoin) {
                $fcmVaccinationQuery = FcmNotification::query()
                    ->whereNotNull('user_id')
                    ->where('user_id', '>', 0)
                    ->select(['id', 'user_id']);

                if ($fcmHasNotificationType) {
                    $fcmVaccinationQuery->whereRaw(
                        "LOWER(TRIM(COALESCE(notification_type, ''))) IN ({$vaccinationTypePlaceholders})",
                        $vaccinationNotificationTypes
                    );
                }

                if ($supportsNeuteringNotificationJoin) {
                    $fcmVaccinationQuery->addSelect('data_payload');
                }
                if ($fcmHasNotificationType) {
                    $fcmVaccinationQuery->addSelect('notification_type');
                }
                if ($fcmHasStatus) {
                    $fcmVaccinationQuery->addSelect('status');
                }
                if ($fcmHasTitle) {
                    $fcmVaccinationQuery->addSelect('title');
                }
                if ($fcmHasNotificationText) {
                    $fcmVaccinationQuery->addSelect('notification_text');
                }
                if ($fcmHasSentAt) {
                    $fcmVaccinationQuery->addSelect('sent_at');
                }
                if ($fcmHasCreatedAt) {
                    $fcmVaccinationQuery->addSelect('created_at');
                }
                if ($fcmHasClicked) {
                    $fcmVaccinationQuery->addSelect('clicked');
                }
                if ($fcmHasClickedAt) {
                    $fcmVaccinationQuery->addSelect('clicked_at');
                }

                $fcmVaccinationRows = $fcmVaccinationQuery
                    ->orderByDesc('id')
                    ->limit($maxFcmScanRows)
                    ->get();

                $vaccinationUsers = User::query()
                    ->whereIn(
                        'id',
                        $fcmVaccinationRows->pluck('user_id')
                            ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                            ->map(fn ($userId) => (int) $userId)
                            ->unique()
                            ->values()
                            ->all()
                    )
                    ->get($leadUserBaseColumns)
                    ->keyBy('id');

                foreach ($fcmVaccinationRows as $fcmRow) {
                    if (!$isDeliveredNotification($fcmRow)) {
                        continue;
                    }

                    $dataPayload = ($supportsNeuteringNotificationJoin && is_array($fcmRow->data_payload))
                        ? $fcmRow->data_payload
                        : [];
                    $notificationType = $resolveNotificationType($fcmRow, $dataPayload);
                    $notificationTitle = $resolveNotificationTitle($fcmRow, $dataPayload);
                    $notificationText = $resolveNotificationText($fcmRow, $dataPayload);
                    $payloadType = strtolower(trim((string) data_get($dataPayload, 'type')));

                    if (!in_array(strtolower($notificationType), $vaccinationNotificationTypes, true) && !in_array($payloadType, $vaccinationNotificationTypes, true)) {
                        continue;
                    }

                    $userIdRaw = $fcmRow->user_id ?? data_get($dataPayload, 'user_id');
                    if (!is_numeric($userIdRaw)) {
                        continue;
                    }

                    $userId = (int) $userIdRaw;
                    if ($userId <= 0) {
                        continue;
                    }

                    if (!$targetUsers->has($userId)) {
                        $user = $vaccinationUsers->get($userId);
                        $targetUsers->put($userId, $initializeLeadUser($user instanceof User ? $user : null, $userId));
                    }

                    $leadUser = $targetUsers->get($userId);
                    if (!is_array($leadUser)) {
                        continue;
                    }

                    $leadUser['has_vaccination_reminder'] = true;
                    $leadUser['vaccination_notification_count'] = (int) $leadUser['vaccination_notification_count'] + 1;

                    $petIdRaw = data_get($dataPayload, 'pet_id');
                    if (is_numeric($petIdRaw)) {
                        $petId = (int) $petIdRaw;
                        if ($petId > 0 && !in_array($petId, $leadUser['notified_vaccination_pet_ids'], true)) {
                            $leadUser['notified_vaccination_pet_ids'][] = $petId;
                        }
                    }

                    $petName = trim((string) (data_get($dataPayload, 'pet_name') ?? ''));
                    if ($petName !== '' && !in_array($petName, $leadUser['notified_vaccination_pet_names'], true)) {
                        $leadUser['notified_vaccination_pet_names'][] = $petName;
                    }

                    $timestamp = $resolveNotificationTimestamp($fcmRow);
                    if ($timestamp !== null) {
                        $currentLastSentAt = $leadUser['last_vaccination_notification_at'];
                        if ($currentLastSentAt === null || strcmp($timestamp, (string) $currentLastSentAt) > 0) {
                            $leadUser['last_vaccination_notification_at'] = $timestamp;
                        }
                    }

                    $leadUser['all_notifications'][] = [
                        'id' => (int) ($fcmRow->id ?? 0),
                        'origin_notification_id' => $resolveOriginNotificationId($dataPayload),
                        'notification_title' => $notificationTitle,
                        'notification_text' => $notificationText,
                        'notification_type' => strtolower($notificationType) !== 'unknown' ? $notificationType : $payloadType,
                        'timestamp' => $timestamp,
                        'status' => $fcmHasStatus ? strtolower(trim((string) ($fcmRow->status ?? ''))) : null,
                        'clicked' => $fcmHasClicked ? (bool) ($fcmRow->clicked ?? false) : null,
                        'clicked_at' => $fcmHasClickedAt ? $normalizeDateTime($fcmRow->clicked_at ?? null) : null,
                        'bucket' => 'vaccination',
                        'pet_id' => is_numeric($petIdRaw ?? null) ? (int) $petIdRaw : null,
                        'call_session' => null,
                        'source' => 'fcm_notifications',
                    ];

                    $targetUsers->put($userId, $leadUser);
                }
            }

            // 4) Onboarding/profile completion notifications (user-level).
            $profileNotificationTypes = ['pp_user_created', 'profile_completion'];
            $profileTypePlaceholders = implode(',', array_fill(0, count($profileNotificationTypes), '?'));

            if ($targetUsers->isNotEmpty()) {
                $leadUserIds = $targetUsers
                    ->keys()
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->values()
                    ->all();

                if (!empty($leadUserIds)) {
                    $fcmProfileQuery = FcmNotification::query()
                        ->select(['id', 'user_id']);

                    if ($supportsNeuteringNotificationJoin) {
                        $fcmProfileQuery->addSelect('data_payload');
                    }
                    if ($fcmHasNotificationType) {
                        $fcmProfileQuery->addSelect('notification_type');
                    }
                    if ($fcmHasStatus) {
                        $fcmProfileQuery->addSelect('status');
                    }
                    if ($fcmHasTitle) {
                        $fcmProfileQuery->addSelect('title');
                    }
                    if ($fcmHasNotificationText) {
                        $fcmProfileQuery->addSelect('notification_text');
                    }
                    if ($fcmHasSentAt) {
                        $fcmProfileQuery->addSelect('sent_at');
                    }
                    if ($fcmHasCreatedAt) {
                        $fcmProfileQuery->addSelect('created_at');
                    }
                    if ($fcmHasClicked) {
                        $fcmProfileQuery->addSelect('clicked');
                    }
                    if ($fcmHasClickedAt) {
                        $fcmProfileQuery->addSelect('clicked_at');
                    }

                    $fcmProfileQuery->whereIn('user_id', $leadUserIds);
                    if ($fcmHasNotificationType) {
                        $fcmProfileQuery->whereRaw(
                            "LOWER(TRIM(COALESCE(notification_type, ''))) IN ({$profileTypePlaceholders})",
                            $profileNotificationTypes
                        );
                    }

                    $fcmProfileRows = $fcmProfileQuery
                        ->orderByDesc('id')
                        ->limit($maxFcmScanRows)
                        ->get();

                    foreach ($fcmProfileRows as $fcmRow) {
                        if (!$isDeliveredNotification($fcmRow)) {
                            continue;
                        }

                        $dataPayload = ($supportsNeuteringNotificationJoin && is_array($fcmRow->data_payload))
                            ? $fcmRow->data_payload
                            : [];
                        $notificationType = strtolower(trim($resolveNotificationType($fcmRow, $dataPayload)));
                        $payloadType = strtolower(trim((string) data_get($dataPayload, 'type')));

                        $effectiveType = $notificationType !== '' && $notificationType !== 'unknown'
                            ? $notificationType
                            : $payloadType;

                        if (!in_array($effectiveType, $profileNotificationTypes, true)) {
                            continue;
                        }

                        $userIdRaw = $fcmRow->user_id ?? data_get($dataPayload, 'user_id');
                        if (!is_numeric($userIdRaw)) {
                            continue;
                        }

                        $userId = (int) $userIdRaw;
                        if ($userId <= 0) {
                            continue;
                        }

                        if (!$targetUsers->has($userId)) {
                            $targetUsers->put($userId, $initializeLeadUser(null, $userId));
                        }

                        $leadUser = $targetUsers->get($userId);
                        if (!is_array($leadUser)) {
                            continue;
                        }

                        $bucket = $effectiveType === 'pp_user_created' ? 'onboarding' : 'profile_completion';
                        $notificationTitle = $resolveNotificationTitle($fcmRow, $dataPayload);
                        $notificationText = $resolveNotificationText($fcmRow, $dataPayload);
                        $timestamp = $resolveNotificationTimestamp($fcmRow);

                        $leadUser['all_notifications'][] = [
                            'id' => (int) ($fcmRow->id ?? 0),
                            'origin_notification_id' => $resolveOriginNotificationId($dataPayload),
                            'notification_title' => $notificationTitle,
                            'notification_text' => $notificationText,
                            'notification_type' => $effectiveType,
                            'timestamp' => $timestamp,
                            'status' => $fcmHasStatus ? strtolower(trim((string) ($fcmRow->status ?? ''))) : null,
                            'clicked' => $fcmHasClicked ? (bool) ($fcmRow->clicked ?? false) : null,
                            'clicked_at' => $fcmHasClickedAt ? $normalizeDateTime($fcmRow->clicked_at ?? null) : null,
                            'bucket' => $bucket,
                            'pet_id' => null,
                            'call_session' => null,
                            'source' => 'fcm_notifications',
                        ];

                        $targetUsers->put($userId, $leadUser);
                    }
                }
            }
            } catch (\Throwable $e) {
                $captureLeadManagementError('fcm_notification_joins', $e);
            }
        }

        if ($supportsNotificationRecords && $targetUsers->isNotEmpty()) {
            try {
                $leadUserIds = $targetUsers
                    ->keys()
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->values()
                    ->all();

                if (!empty($leadUserIds)) {
                    $notificationColumns = ['id', 'user_id'];
                    if ($notificationHasPetId) {
                        $notificationColumns[] = 'pet_id';
                    }
                    if ($notificationHasType) {
                        $notificationColumns[] = 'type';
                    }
                    if ($notificationHasTitle) {
                        $notificationColumns[] = 'title';
                    }
                    if ($notificationHasBody) {
                        $notificationColumns[] = 'body';
                    }
                    if ($notificationHasPayload) {
                        $notificationColumns[] = 'payload';
                    }
                    if ($notificationHasStatus) {
                        $notificationColumns[] = 'status';
                    }
                    if ($notificationHasChannel) {
                        $notificationColumns[] = 'channel';
                    }
                    if ($notificationHasSentAt) {
                        $notificationColumns[] = 'sent_at';
                    }
                    if ($notificationHasCreatedAt) {
                        $notificationColumns[] = 'created_at';
                    }

                    $notificationRows = Notification::query()
                        ->select(array_unique($notificationColumns))
                        ->whereIn('user_id', $leadUserIds)
                        ->where(function (Builder $query) use ($notificationHasStatus, $notificationHasSentAt): void {
                            if ($notificationHasStatus) {
                                $query->whereIn('status', [Notification::STATUS_SENT, Notification::STATUS_DELIVERED]);
                                if ($notificationHasSentAt) {
                                    $query->orWhereNotNull('sent_at');
                                }

                                return;
                            }

                            if ($notificationHasSentAt) {
                                $query->whereNotNull('sent_at');
                            }
                        })
                        ->orderByDesc($notificationHasSentAt ? 'sent_at' : ($notificationHasCreatedAt ? 'created_at' : 'id'))
                        ->orderByDesc('id')
                        ->limit($maxFcmScanRows)
                        ->get();

                    foreach ($notificationRows as $notificationRow) {
                        $userId = is_numeric($notificationRow->user_id ?? null) ? (int) $notificationRow->user_id : 0;
                        if ($userId <= 0 || !$targetUsers->has($userId)) {
                            continue;
                        }

                        $payload = ($notificationHasPayload && is_array($notificationRow->payload ?? null))
                            ? $notificationRow->payload
                            : [];
                        $notificationType = trim((string) (
                            ($notificationHasType ? ($notificationRow->type ?? '') : '')
                            ?: data_get($payload, 'type')
                        ));
                        $bucket = $resolveNotificationBucket($notificationType, $payload);
                        if ($bucket === null) {
                            continue;
                        }

                        $timestamp = $notificationHasSentAt
                            ? $normalizeDateTime($notificationRow->sent_at ?? null)
                            : null;
                        if ($timestamp === null && $notificationHasCreatedAt) {
                            $timestamp = $normalizeDateTime($notificationRow->created_at ?? null);
                        }

                        $petIdRaw = $notificationHasPetId
                            ? ($notificationRow->pet_id ?? null)
                            : data_get($payload, 'pet_id');

                        $leadUser = $targetUsers->get($userId);
                        if (!is_array($leadUser)) {
                            continue;
                        }

                        $leadUser['all_notifications'][] = [
                            'id' => (int) ($notificationRow->id ?? 0),
                            'origin_notification_id' => (int) ($notificationRow->id ?? 0),
                            'notification_title' => trim((string) (
                                ($notificationHasTitle ? ($notificationRow->title ?? '') : '')
                                ?: data_get($payload, 'title')
                            )),
                            'notification_text' => trim((string) (
                                ($notificationHasBody ? ($notificationRow->body ?? '') : '')
                                ?: data_get($payload, 'body')
                                ?: data_get($payload, 'message')
                            )),
                            'notification_type' => $notificationType !== '' ? $notificationType : 'unknown',
                            'timestamp' => $timestamp,
                            'status' => $notificationHasStatus ? strtolower(trim((string) ($notificationRow->status ?? ''))) : null,
                            'clicked' => null,
                            'clicked_at' => null,
                            'bucket' => $bucket,
                            'pet_id' => is_numeric($petIdRaw) ? (int) $petIdRaw : null,
                            'call_session' => trim((string) (data_get($payload, 'call_session') ?: data_get($payload, 'channel_name'))),
                            'source' => 'notifications',
                        ];

                        $targetUsers->put($userId, $leadUser);
                    }
                }
            } catch (\Throwable $e) {
                $captureLeadManagementError('notification_records', $e);
            }
        }

        if ($supportsFcmNotifications && $targetUsers->isNotEmpty()) {
            try {
                $leadUserIds = $targetUsers
                    ->keys()
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->values()
                    ->all();

                if (!empty($leadUserIds)) {
                    $fcmColumns = ['id', 'user_id'];
                    if ($supportsNeuteringNotificationJoin) {
                        $fcmColumns[] = 'data_payload';
                    }
                    if ($fcmHasCallSession) {
                        $fcmColumns[] = 'call_session';
                    }
                    if ($fcmHasNotificationType) {
                        $fcmColumns[] = 'notification_type';
                    }
                    if ($fcmHasStatus) {
                        $fcmColumns[] = 'status';
                    }
                    if ($fcmHasTitle) {
                        $fcmColumns[] = 'title';
                    }
                    if ($fcmHasNotificationText) {
                        $fcmColumns[] = 'notification_text';
                    }
                    if ($fcmHasSentAt) {
                        $fcmColumns[] = 'sent_at';
                    }
                    if ($fcmHasCreatedAt) {
                        $fcmColumns[] = 'created_at';
                    }
                    if ($fcmHasClicked) {
                        $fcmColumns[] = 'clicked';
                    }
                    if ($fcmHasClickedAt) {
                        $fcmColumns[] = 'clicked_at';
                    }

                    $fcmRows = FcmNotification::query()
                        ->select(array_unique($fcmColumns))
                        ->whereIn('user_id', $leadUserIds)
                        ->orderByDesc($fcmHasSentAt ? 'sent_at' : ($fcmHasCreatedAt ? 'created_at' : 'id'))
                        ->orderByDesc('id')
                        ->limit($maxFcmScanRows)
                        ->get();

                    foreach ($fcmRows as $fcmRow) {
                        if (!$isDeliveredNotification($fcmRow)) {
                            continue;
                        }

                        $userId = is_numeric($fcmRow->user_id ?? null) ? (int) $fcmRow->user_id : 0;
                        if ($userId <= 0 || !$targetUsers->has($userId)) {
                            continue;
                        }

                        $dataPayload = ($supportsNeuteringNotificationJoin && is_array($fcmRow->data_payload ?? null))
                            ? $fcmRow->data_payload
                            : [];
                        $notificationType = trim((string) $resolveNotificationType($fcmRow, $dataPayload));
                        $payloadType = trim((string) data_get($dataPayload, 'type'));
                        $effectiveType = $notificationType !== '' && strtolower($notificationType) !== 'unknown'
                            ? $notificationType
                            : ($payloadType !== '' ? $payloadType : 'unknown');
                        $bucket = $resolveNotificationBucket($effectiveType, $dataPayload) ?? 'other';
                        $timestamp = $resolveNotificationTimestamp($fcmRow);
                        $petIdRaw = data_get($dataPayload, 'pet_id');
                        $callSession = $fcmHasCallSession
                            ? trim((string) ($fcmRow->call_session ?? ''))
                            : '';

                        if ($callSession === '') {
                            $callSession = trim((string) (
                                data_get($dataPayload, 'call_session')
                                ?: data_get($dataPayload, 'callSession')
                                ?: data_get($dataPayload, 'channel_name')
                            ));
                        }

                        $leadUser = $targetUsers->get($userId);
                        if (!is_array($leadUser)) {
                            continue;
                        }

                        $leadUser['all_notifications'][] = [
                            'id' => (int) ($fcmRow->id ?? 0),
                            'origin_notification_id' => $resolveOriginNotificationId($dataPayload),
                            'notification_title' => $resolveNotificationTitle($fcmRow, $dataPayload),
                            'notification_text' => $resolveNotificationText($fcmRow, $dataPayload),
                            'notification_type' => $effectiveType,
                            'timestamp' => $timestamp,
                            'status' => $fcmHasStatus ? strtolower(trim((string) ($fcmRow->status ?? ''))) : null,
                            'clicked' => $fcmHasClicked ? (bool) ($fcmRow->clicked ?? false) : null,
                            'clicked_at' => $fcmHasClickedAt ? $normalizeDateTime($fcmRow->clicked_at ?? null) : null,
                            'bucket' => $bucket,
                            'pet_id' => is_numeric($petIdRaw) ? (int) $petIdRaw : null,
                            'call_session' => $callSession !== '' ? $callSession : null,
                            'source' => 'fcm_notifications',
                        ];

                        $targetUsers->put($userId, $leadUser);
                    }
                }
            } catch (\Throwable $e) {
                $captureLeadManagementError('fcm_notification_logs', $e);
            }
        }

        $targetUsers = $targetUsers->map(function (array $leadUser): array {
            $notifications = collect($leadUser['all_notifications'] ?? [])
                ->unique(function (array $item): string {
                    $bucket = trim((string) ($item['bucket'] ?? ''));
                    $originNotificationId = is_numeric($item['origin_notification_id'] ?? null)
                        ? (int) $item['origin_notification_id']
                        : 0;

                    if ($originNotificationId > 0) {
                        return 'origin:'.$originNotificationId.'|'.$bucket;
                    }

                    return (string) ((int) ($item['id'] ?? 0))
                        .'|'.$bucket
                        .'|'.trim((string) ($item['notification_type'] ?? ''))
                        .'|'.trim((string) ($item['notification_title'] ?? ''))
                        .'|'.trim((string) ($item['timestamp'] ?? ''));
                })
                ->sort(function (array $left, array $right): int {
                    $leftTs = (string) ($left['timestamp'] ?? '0000-00-00 00:00:00');
                    $rightTs = (string) ($right['timestamp'] ?? '0000-00-00 00:00:00');
                    if ($leftTs !== $rightTs) {
                        return strcmp($rightTs, $leftTs);
                    }

                    return ((int) ($right['id'] ?? 0)) <=> ((int) ($left['id'] ?? 0));
                })
                ->values()
                ->all();

            $leadUser['all_notifications'] = $notifications;
            $leadUser['all_notifications_count'] = count($notifications);
            $leadUser['neutering_notification_count'] = collect($notifications)
                ->filter(fn (array $item): bool => strtolower(trim((string) ($item['bucket'] ?? ''))) === 'neutering')
                ->count();
            $leadUser['vaccination_notification_count'] = collect($notifications)
                ->filter(fn (array $item): bool => strtolower(trim((string) ($item['bucket'] ?? ''))) === 'vaccination')
                ->count();
            $leadUser['last_neutering_notification_at'] = collect($notifications)
                ->filter(fn (array $item): bool => strtolower(trim((string) ($item['bucket'] ?? ''))) === 'neutering')
                ->pluck('timestamp')
                ->filter(fn ($timestamp): bool => trim((string) $timestamp) !== '')
                ->sortDesc()
                ->values()
                ->first();
            $leadUser['last_vaccination_notification_at'] = collect($notifications)
                ->filter(fn (array $item): bool => strtolower(trim((string) ($item['bucket'] ?? ''))) === 'vaccination')
                ->pluck('timestamp')
                ->filter(fn ($timestamp): bool => trim((string) $timestamp) !== '')
                ->sortDesc()
                ->values()
                ->first();
            if ((int) $leadUser['vaccination_notification_count'] > 0) {
                $leadUser['has_vaccination_reminder'] = true;
            }

            return $leadUser;
        });

        $hasPrescriptionUserId = $hasPrescriptionsTable && Schema::hasColumn('prescriptions', 'user_id');

        if ($targetUsers->isNotEmpty()) {
            try {
                $leadUserIds = $targetUsers
                    ->keys()
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->values()
                    ->all();

                if (!empty($leadUserIds)) {
                    $usersById = User::query()
                        ->whereIn('id', $leadUserIds)
                        ->get($leadUserColumnsWithCreatedAt)
                        ->keyBy('id');

                    $latestPrescriptionByUser = collect();
                    if ($hasPrescriptionUserId && ($hasPrescriptionFollowUpDate || $hasPrescriptionFollowUpType)) {
                        $latestPrescriptionIdByUser = DB::table('prescriptions')
                            ->selectRaw('user_id, MAX(id) as latest_prescription_id')
                            ->whereIn('user_id', $leadUserIds)
                            ->groupBy('user_id');

                        $latestPrescriptionQuery = DB::table('prescriptions as p')
                            ->joinSub($latestPrescriptionIdByUser, 'latest_prescription_by_user', function ($join): void {
                                $join->on('latest_prescription_by_user.latest_prescription_id', '=', 'p.id');
                            })
                            ->select('p.user_id');

                        if ($hasPrescriptionFollowUpDate) {
                            $latestPrescriptionQuery->addSelect('p.follow_up_date');
                        }
                        if ($hasPrescriptionFollowUpType) {
                            $latestPrescriptionQuery->addSelect('p.follow_up_type');
                        }

                        $latestPrescriptionByUser = $latestPrescriptionQuery
                            ->get()
                            ->keyBy('user_id');
                    }

                    $targetUsers = $targetUsers->map(function (array $leadUser) use (
                        $usersById,
                        $latestPrescriptionByUser,
                        $hasUserCreatedAt,
                        $hasPrescriptionFollowUpDate,
                        $hasPrescriptionFollowUpType,
                        $normalizeDateTime
                    ): array {
                        $userId = is_numeric($leadUser['id'] ?? null) ? (int) $leadUser['id'] : 0;
                        if ($userId <= 0) {
                            return $leadUser;
                        }

                        $userMeta = $usersById->get($userId);
                        if ($userMeta instanceof User) {
                            if (empty($leadUser['name'])) {
                                $leadUser['name'] = $userMeta->name;
                            }
                            if (empty($leadUser['email'])) {
                                $leadUser['email'] = $userMeta->email;
                            }
                            if (empty($leadUser['phone'])) {
                                $leadUser['phone'] = $userMeta->phone;
                            }
                            if (empty($leadUser['city'])) {
                                $leadUser['city'] = $userMeta->city;
                            }
                            if ($hasUserCreatedAt) {
                                $leadUser['user_created_at'] = $normalizeDateTime($userMeta->created_at ?? null);
                            }
                        }

                        if ($latestPrescriptionByUser->has($userId)) {
                            $latestPrescription = $latestPrescriptionByUser->get($userId);

                            if ($hasPrescriptionFollowUpDate) {
                                $followUpDateRaw = data_get($latestPrescription, 'follow_up_date');
                                if (!empty($followUpDateRaw)) {
                                    try {
                                        $leadUser['prescription_follow_up_date'] = \Illuminate\Support\Carbon::parse($followUpDateRaw)->toDateString();
                                    } catch (\Throwable $e) {
                                        $leadUser['prescription_follow_up_date'] = (string) $followUpDateRaw;
                                    }
                                }
                            }

                            if ($hasPrescriptionFollowUpType) {
                                $followUpTypeRaw = trim((string) data_get($latestPrescription, 'follow_up_type'));
                                if ($followUpTypeRaw !== '') {
                                    $leadUser['prescription_follow_up_type'] = $followUpTypeRaw;
                                }
                            }
                        }

                        return $leadUser;
                    });
                }
            } catch (\Throwable $e) {
                $captureLeadManagementError('lead_user_enrichment', $e);
            }
        }

        if ($hasPrescriptionUserId && $targetUsers->isNotEmpty()) {
            try {
                $leadUserIds = $targetUsers
                    ->keys()
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->values()
                    ->all();

                if (!empty($leadUserIds)) {
                    $prescriptionColumns = ['id', 'user_id'];
                    if ($hasPrescriptionCreatedAt) {
                        $prescriptionColumns[] = 'created_at';
                    }
                    if ($hasPrescriptionDoctorId) {
                        $prescriptionColumns[] = 'doctor_id';
                    }
                    if ($hasPrescriptionPetId) {
                        $prescriptionColumns[] = 'pet_id';
                    }
                    if ($hasPrescriptionDiagnosis) {
                        $prescriptionColumns[] = 'diagnosis';
                    }
                    if ($hasPrescriptionDiseaseName) {
                        $prescriptionColumns[] = 'disease_name';
                    }
                    if ($hasPrescriptionVideoInclinic) {
                        $prescriptionColumns[] = 'video_inclinic';
                    }
                    if ($hasPrescriptionFollowUpDate) {
                        $prescriptionColumns[] = 'follow_up_date';
                    }
                    if ($hasPrescriptionFollowUpType) {
                        $prescriptionColumns[] = 'follow_up_type';
                    }

                    $maxPrescriptionRows = min(max($limit * 20, 3000), 12000);
                    $prescriptionRows = Prescription::query()
                        ->select(array_unique($prescriptionColumns))
                        ->whereIn('user_id', $leadUserIds)
                        ->orderByDesc($hasPrescriptionCreatedAt ? 'created_at' : 'id')
                        ->orderByDesc('id')
                        ->limit($maxPrescriptionRows)
                        ->get();

                    $doctorNameLookup = [];
                    if ($hasPrescriptionDoctorId) {
                        $doctorIds = $prescriptionRows
                            ->pluck('doctor_id')
                            ->filter(fn ($doctorId) => is_numeric($doctorId) && (int) $doctorId > 0)
                            ->map(fn ($doctorId) => (int) $doctorId)
                            ->unique()
                            ->values();

                        if ($doctorIds->isNotEmpty()) {
                            $doctorNameLookup = Doctor::query()
                                ->whereIn('id', $doctorIds->all())
                                ->pluck('doctor_name', 'id')
                                ->mapWithKeys(fn ($doctorName, $doctorId) => [(int) $doctorId => (string) $doctorName])
                                ->all();
                        }
                    }

                    $petNameLookup = [];
                    if ($hasPrescriptionPetId && $hasPetsTable) {
                        $petIds = $prescriptionRows
                            ->pluck('pet_id')
                            ->filter(fn ($petId) => is_numeric($petId) && (int) $petId > 0)
                            ->map(fn ($petId) => (int) $petId)
                            ->unique()
                            ->values();

                        if ($petIds->isNotEmpty()) {
                            $petNameLookup = Pet::query()
                                ->whereIn('id', $petIds->all())
                                ->pluck('name', 'id')
                                ->mapWithKeys(fn ($petName, $petId) => [(int) $petId => (string) $petName])
                                ->all();
                        }
                    }

                    $relatedPrescriptionsByUser = [];

                    foreach ($prescriptionRows as $prescriptionRow) {
                        $userId = is_numeric($prescriptionRow->user_id ?? null) ? (int) $prescriptionRow->user_id : 0;
                        if ($userId <= 0 || !$targetUsers->has($userId)) {
                            continue;
                        }

                        if (!isset($relatedPrescriptionsByUser[$userId])) {
                            $relatedPrescriptionsByUser[$userId] = [];
                        }

                        if (count($relatedPrescriptionsByUser[$userId]) >= 25) {
                            continue;
                        }

                        $createdAt = $hasPrescriptionCreatedAt
                            ? $normalizeDateTime($prescriptionRow->created_at ?? null)
                            : null;

                        $followUpDate = null;
                        if ($hasPrescriptionFollowUpDate && !empty($prescriptionRow->follow_up_date)) {
                            try {
                                $followUpDate = \Illuminate\Support\Carbon::parse($prescriptionRow->follow_up_date)->toDateString();
                            } catch (\Throwable $e) {
                                $followUpDate = (string) $prescriptionRow->follow_up_date;
                            }
                        }

                        $doctorId = $hasPrescriptionDoctorId && is_numeric($prescriptionRow->doctor_id ?? null)
                            ? (int) $prescriptionRow->doctor_id
                            : null;
                        $petId = $hasPrescriptionPetId && is_numeric($prescriptionRow->pet_id ?? null)
                            ? (int) $prescriptionRow->pet_id
                            : null;

                        $relatedPrescriptionsByUser[$userId][] = [
                            'id' => (int) ($prescriptionRow->id ?? 0),
                            'created_at' => $createdAt,
                            'doctor_id' => $doctorId,
                            'doctor_name' => $doctorId ? ($doctorNameLookup[$doctorId] ?? null) : null,
                            'pet_id' => $petId,
                            'pet_name' => $petId ? ($petNameLookup[$petId] ?? null) : null,
                            'diagnosis' => $hasPrescriptionDiagnosis
                                ? trim((string) ($prescriptionRow->diagnosis ?? ''))
                                : '',
                            'disease_name' => $hasPrescriptionDiseaseName
                                ? trim((string) ($prescriptionRow->disease_name ?? ''))
                                : '',
                            'video_inclinic' => $hasPrescriptionVideoInclinic
                                ? trim((string) ($prescriptionRow->video_inclinic ?? ''))
                                : '',
                            'follow_up_date' => $followUpDate,
                            'follow_up_type' => $hasPrescriptionFollowUpType
                                ? trim((string) ($prescriptionRow->follow_up_type ?? ''))
                                : '',
                        ];
                    }

                    $targetUsers = $targetUsers->map(function (array $leadUser) use ($relatedPrescriptionsByUser): array {
                        $userId = is_numeric($leadUser['id'] ?? null) ? (int) $leadUser['id'] : 0;
                        if ($userId <= 0) {
                            return $leadUser;
                        }

                        $leadUser['related_prescriptions'] = collect($relatedPrescriptionsByUser[$userId] ?? [])
                            ->sortByDesc('created_at')
                            ->values()
                            ->all();

                        return $leadUser;
                    });
                }
            } catch (\Throwable $e) {
                $captureLeadManagementError('related_prescriptions', $e);
            }
        }

        $hasTransactionUserId = $hasTransactionsTable && Schema::hasColumn('transactions', 'user_id');
        $hasTransactionCreatedAt = $hasTransactionsTable && Schema::hasColumn('transactions', 'created_at');
        $hasTransactionStatus = $hasTransactionsTable && Schema::hasColumn('transactions', 'status');
        $hasTransactionChannelName = $hasTransactionsTable && Schema::hasColumn('transactions', 'channel_name');
        $hasTransactionCallSession = $hasTransactionsTable && Schema::hasColumn('transactions', 'call_session');
        $hasTransactionPetId = $hasTransactionsTable && Schema::hasColumn('transactions', 'pet_id');
        $hasTransactionFcmNotificationId = $hasTransactionsTable && Schema::hasColumn('transactions', 'fcm_notification_id');
        $hasTransactionDoctorId = $hasTransactionsTable && Schema::hasColumn('transactions', 'doctor_id');
        $hasTransactionClinicId = $hasTransactionsTable && Schema::hasColumn('transactions', 'clinic_id');
        $hasTransactionAmountPaise = $hasTransactionsTable && Schema::hasColumn('transactions', 'amount_paise');
        $supportsConversionTracking = $hasTransactionUserId && $hasTransactionCreatedAt;
        $convertedUsersCount = 0;

        if ($supportsConversionTracking && $targetUsers->isNotEmpty()) {
            try {
                $leadUserIds = $targetUsers
                    ->keys()
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->values()
                    ->all();

                if (!empty($leadUserIds)) {
                    $transactionQuery = Transaction::query()
                        ->select(['id', 'user_id', 'created_at']);

                if ($hasTransactionType) {
                    $transactionQuery->addSelect('type');
                }
                if ($hasTransactionStatus) {
                    $transactionQuery->addSelect('status');
                }
                if ($hasTransactionChannelName) {
                    $transactionQuery->addSelect('channel_name');
                }
                if ($hasTransactionCallSession) {
                    $transactionQuery->addSelect('call_session');
                }
                if ($hasTransactionPetId) {
                    $transactionQuery->addSelect('pet_id');
                }
                if ($hasTransactionFcmNotificationId) {
                    $transactionQuery->addSelect('fcm_notification_id');
                }
                if ($hasTransactionDoctorId) {
                    $transactionQuery->addSelect('doctor_id');
                }
                if ($hasTransactionClinicId) {
                    $transactionQuery->addSelect('clinic_id');
                }
                if ($hasTransactionAmountPaise) {
                    $transactionQuery->addSelect('amount_paise');
                }
                if ($hasTransactionMetadata) {
                    $transactionQuery->addSelect('metadata');
                }

                $successfulStatuses = [
                    'completed',
                    'captured',
                    'paid',
                    'success',
                    'successful',
                    'settled',
                ];

                $transactionsByUser = [];
                $relatedTransactionsByUser = [];
                $leadTransactions = $transactionQuery
                    ->whereIn('user_id', $leadUserIds)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();

                $doctorNameLookup = [];
                $clinicNameLookup = [];
                $petNameLookup = [];

                if ($hasTransactionDoctorId) {
                    $doctorIds = $leadTransactions
                        ->pluck('doctor_id')
                        ->filter(fn ($doctorId) => is_numeric($doctorId) && (int) $doctorId > 0)
                        ->map(fn ($doctorId) => (int) $doctorId)
                        ->unique()
                        ->values();

                    if ($doctorIds->isNotEmpty()) {
                        $doctorNameLookup = Doctor::query()
                            ->whereIn('id', $doctorIds->all())
                            ->pluck('doctor_name', 'id')
                            ->mapWithKeys(fn ($doctorName, $doctorId) => [(int) $doctorId => (string) $doctorName])
                            ->all();
                    }
                }

                if ($hasTransactionClinicId) {
                    $clinicIds = $leadTransactions
                        ->pluck('clinic_id')
                        ->filter(fn ($clinicId) => is_numeric($clinicId) && (int) $clinicId > 0)
                        ->map(fn ($clinicId) => (int) $clinicId)
                        ->unique()
                        ->values();

                    if ($clinicIds->isNotEmpty()) {
                        $clinicNameLookup = VetRegisterationTemp::query()
                            ->whereIn('id', $clinicIds->all())
                            ->pluck('name', 'id')
                            ->mapWithKeys(fn ($clinicName, $clinicId) => [(int) $clinicId => (string) $clinicName])
                            ->all();
                    }
                }

                if ($hasTransactionPetId && $hasPetsTable) {
                    $petIds = $leadTransactions
                        ->pluck('pet_id')
                        ->filter(fn ($petId) => is_numeric($petId) && (int) $petId > 0)
                        ->map(fn ($petId) => (int) $petId)
                        ->unique()
                        ->values();

                    if ($petIds->isNotEmpty()) {
                        $petNameLookup = Pet::query()
                            ->whereIn('id', $petIds->all())
                            ->pluck('name', 'id')
                            ->mapWithKeys(fn ($petName, $petId) => [(int) $petId => (string) $petName])
                            ->all();
                    }
                }

                foreach ($leadTransactions as $leadTransaction) {
                    $userId = is_numeric($leadTransaction->user_id) ? (int) $leadTransaction->user_id : 0;
                    if ($userId <= 0) {
                        continue;
                    }

                    $transactionAt = $normalizeDateTime($leadTransaction->created_at);
                    if ($transactionAt === null) {
                        continue;
                    }

                    $transactionStatus = $hasTransactionStatus
                        ? strtolower(trim((string) ($leadTransaction->status ?? '')))
                        : '';

                    $transactionType = $hasTransactionType
                        ? trim((string) ($leadTransaction->type ?? ''))
                        : '';
                    if ($transactionType === '' && $hasTransactionMetadata) {
                        $transactionType = trim((string) data_get($leadTransaction->metadata, 'order_type'));
                    }
                    if ($transactionType === '') {
                        $transactionType = 'unknown';
                    }

                    $transactionFcmNotificationId = null;
                    if ($hasTransactionFcmNotificationId && is_numeric($leadTransaction->fcm_notification_id)) {
                        $transactionFcmNotificationId = (int) $leadTransaction->fcm_notification_id;
                    } elseif ($hasTransactionMetadata) {
                        $metadataFcmId = data_get($leadTransaction->metadata, 'fcm_notification_id');
                        if (is_numeric($metadataFcmId)) {
                            $transactionFcmNotificationId = (int) $metadataFcmId;
                        }
                    }

                    $sessionKeys = [];
                    $sessionCandidates = [];
                    if ($hasTransactionChannelName) {
                        $sessionCandidates[] = $leadTransaction->channel_name;
                    }
                    if ($hasTransactionCallSession) {
                        $sessionCandidates[] = $leadTransaction->call_session;
                    }
                    if ($hasTransactionMetadata) {
                        $sessionCandidates[] = data_get($leadTransaction->metadata, 'channel_name');
                        $sessionCandidates[] = data_get($leadTransaction->metadata, 'call_session');
                        $sessionCandidates[] = data_get($leadTransaction->metadata, 'call_session_id');
                        $sessionCandidates[] = data_get($leadTransaction->metadata, 'call_id');
                    }

                    foreach ($sessionCandidates as $sessionCandidate) {
                        $sessionKey = $normalizeSessionKey($sessionCandidate);
                        if ($sessionKey !== '') {
                            $sessionKeys[$sessionKey] = true;
                        }
                    }

                    if (!isset($relatedTransactionsByUser[$userId])) {
                        $relatedTransactionsByUser[$userId] = [];
                    }

                    $transactionRow = [
                        'id' => (int) ($leadTransaction->id ?? 0),
                        'created_at' => $transactionAt,
                        'type' => $transactionType,
                        'status' => $transactionStatus !== '' ? $transactionStatus : null,
                        'amount_paise' => $hasTransactionAmountPaise && is_numeric($leadTransaction->amount_paise)
                            ? (int) $leadTransaction->amount_paise
                            : null,
                        'pet_id' => $hasTransactionPetId && is_numeric($leadTransaction->pet_id) ? (int) $leadTransaction->pet_id : null,
                        'pet_name' => $hasTransactionPetId && is_numeric($leadTransaction->pet_id)
                            ? ($petNameLookup[(int) $leadTransaction->pet_id] ?? null)
                            : null,
                        'fcm_notification_id' => $transactionFcmNotificationId,
                        'doctor_id' => $hasTransactionDoctorId && is_numeric($leadTransaction->doctor_id) ? (int) $leadTransaction->doctor_id : null,
                        'doctor_name' => $hasTransactionDoctorId && is_numeric($leadTransaction->doctor_id)
                            ? ($doctorNameLookup[(int) $leadTransaction->doctor_id] ?? null)
                            : null,
                        'clinic_id' => $hasTransactionClinicId && is_numeric($leadTransaction->clinic_id) ? (int) $leadTransaction->clinic_id : null,
                        'clinic_name' => $hasTransactionClinicId && is_numeric($leadTransaction->clinic_id)
                            ? ($clinicNameLookup[(int) $leadTransaction->clinic_id] ?? null)
                            : null,
                        'session_keys' => array_keys($sessionKeys),
                    ];

                    $relatedTransactionsByUser[$userId][] = $transactionRow;

                    if ($hasTransactionStatus && $transactionStatus !== '' && !in_array($transactionStatus, $successfulStatuses, true)) {
                        continue;
                    }

                    if (!isset($transactionsByUser[$userId])) {
                        $transactionsByUser[$userId] = [];
                    }

                    $transactionsByUser[$userId][] = $transactionRow;
                }

                $notificationMatchesTransaction = static function (array $notification, array $transaction) use ($normalizeSessionKey): bool {
                    $bucket = strtolower(trim((string) ($notification['bucket'] ?? '')));
                    $notificationSession = $normalizeSessionKey($notification['call_session'] ?? '');
                    $notificationPetId = is_numeric($notification['pet_id'] ?? null) ? (int) $notification['pet_id'] : 0;
                    $transactionPetId = is_numeric($transaction['pet_id'] ?? null) ? (int) $transaction['pet_id'] : 0;
                    $transactionSessionKeys = is_array($transaction['session_keys'] ?? null)
                        ? $transaction['session_keys']
                        : [];

                    if ($bucket === '' || $bucket === 'other') {
                        return false;
                    }

                    if ($bucket === 'follow_up' && $notificationSession !== '') {
                        return in_array($notificationSession, $transactionSessionKeys, true);
                    }

                    if (in_array($bucket, ['neutering', 'vaccination'], true) && $notificationPetId > 0 && $transactionPetId > 0) {
                        return $notificationPetId === $transactionPetId;
                    }

                    if ($notificationSession !== '') {
                        return in_array($notificationSession, $transactionSessionKeys, true);
                    }

                    if ($notificationPetId > 0 && $transactionPetId > 0) {
                        return $notificationPetId === $transactionPetId;
                    }

                    return true;
                };

                $targetUsers = $targetUsers->map(function (array $leadUser) use ($transactionsByUser, $relatedTransactionsByUser, $notificationMatchesTransaction): array {
                    $userId = is_numeric($leadUser['id'] ?? null) ? (int) $leadUser['id'] : 0;
                    if ($userId <= 0) {
                        return $leadUser;
                    }

                    $leadUser['related_transactions'] = collect($relatedTransactionsByUser[$userId] ?? [])
                        ->sortByDesc('created_at')
                        ->values()
                        ->all();

                    $userTransactions = $transactionsByUser[$userId] ?? [];
                    if (empty($userTransactions)) {
                        return $leadUser;
                    }

                    $allUserNotifications = collect($leadUser['all_notifications'] ?? [])
                        ->map(function (array $item): array {
                            $item['converted'] = (bool) ($item['converted'] ?? false);
                            $item['conversion_transaction_id'] = is_numeric($item['conversion_transaction_id'] ?? null)
                                ? (int) $item['conversion_transaction_id']
                                : null;
                            $item['conversion_transaction_type'] = !empty($item['conversion_transaction_type'])
                                ? (string) $item['conversion_transaction_type']
                                : null;
                            $item['conversion_transaction_status'] = !empty($item['conversion_transaction_status'])
                                ? (string) $item['conversion_transaction_status']
                                : null;
                            $item['conversion_transaction_at'] = !empty($item['conversion_transaction_at'])
                                ? (string) $item['conversion_transaction_at']
                                : null;
                            $item['conversion_lag_minutes'] = is_numeric($item['conversion_lag_minutes'] ?? null)
                                ? (int) $item['conversion_lag_minutes']
                                : null;

                            return $item;
                        })
                        ->values();

                    if ($allUserNotifications->isEmpty()) {
                        return $leadUser;
                    }

                    $notificationsById = [];
                    foreach ($allUserNotifications as $notificationRow) {
                        $notifId = is_numeric($notificationRow['id'] ?? null) ? (int) $notificationRow['id'] : 0;
                        if ($notifId > 0 && !isset($notificationsById[$notifId])) {
                            $notificationsById[$notifId] = $notificationRow;
                        }
                    }

                    $directConversionsByNotificationId = [];
                    foreach ($userTransactions as $userTransaction) {
                        $directFcmId = is_numeric($userTransaction['fcm_notification_id'] ?? null)
                            ? (int) $userTransaction['fcm_notification_id']
                            : 0;
                        if ($directFcmId <= 0 || !isset($notificationsById[$directFcmId])) {
                            continue;
                        }

                        if (isset($directConversionsByNotificationId[$directFcmId])) {
                            continue;
                        }

                        $notificationTs = (string) ($notificationsById[$directFcmId]['timestamp'] ?? '');
                        $transactionTs = (string) ($userTransaction['created_at'] ?? '');

                        $lagMinutes = null;
                        if ($notificationTs !== '' && $transactionTs !== '') {
                            try {
                                $lagMinutes = \Illuminate\Support\Carbon::parse($notificationTs)
                                    ->diffInMinutes(\Illuminate\Support\Carbon::parse($transactionTs));
                            } catch (\Throwable $e) {
                                $lagMinutes = null;
                            }
                        }

                        $directConversionsByNotificationId[$directFcmId] = [
                            'id' => (int) ($userTransaction['id'] ?? 0),
                            'type' => (string) ($userTransaction['type'] ?? 'unknown'),
                            'status' => $userTransaction['status'] ?? null,
                            'created_at' => $transactionTs !== '' ? $transactionTs : null,
                            'lag_minutes' => is_numeric($lagMinutes) ? (int) $lagMinutes : null,
                        ];
                    }

                    if (!empty($directConversionsByNotificationId)) {
                        $allUserNotifications = $allUserNotifications
                            ->map(function (array $notificationRow) use ($directConversionsByNotificationId): array {
                                $notifId = is_numeric($notificationRow['id'] ?? null) ? (int) $notificationRow['id'] : 0;
                                if ($notifId <= 0 || !isset($directConversionsByNotificationId[$notifId])) {
                                    return $notificationRow;
                                }

                                $transactionMeta = $directConversionsByNotificationId[$notifId];
                                $notificationRow['converted'] = true;
                                $notificationRow['conversion_transaction_id'] = (int) ($transactionMeta['id'] ?? 0);
                                $notificationRow['conversion_transaction_type'] = (string) ($transactionMeta['type'] ?? 'unknown');
                                $notificationRow['conversion_transaction_status'] = $transactionMeta['status'] ?? null;
                                $notificationRow['conversion_transaction_at'] = $transactionMeta['created_at'] ?? null;
                                $notificationRow['conversion_lag_minutes'] = is_numeric($transactionMeta['lag_minutes'] ?? null)
                                    ? (int) $transactionMeta['lag_minutes']
                                    : null;

                                return $notificationRow;
                            })
                            ->values();

                        $leadUser['all_notifications'] = $allUserNotifications->all();
                        $leadUser['all_notifications_count'] = $allUserNotifications->count();

                        $notificationsById = [];
                        foreach ($allUserNotifications as $notificationRow) {
                            $notifId = is_numeric($notificationRow['id'] ?? null) ? (int) $notificationRow['id'] : 0;
                            if ($notifId > 0 && !isset($notificationsById[$notifId])) {
                                $notificationsById[$notifId] = $notificationRow;
                            }
                        }
                    }

                    $userNotifications = $allUserNotifications
                        ->filter(fn (array $item): bool => !empty($item['timestamp']))
                        ->sort(function (array $left, array $right): int {
                            $leftTs = (string) ($left['timestamp'] ?? '');
                            $rightTs = (string) ($right['timestamp'] ?? '');
                            if ($leftTs !== $rightTs) {
                                return strcmp($leftTs, $rightTs);
                            }

                            return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
                        })
                        ->values()
                        ->all();

                    if (empty($userNotifications)) {
                        return $leadUser;
                    }

                    $matchedNotification = null;
                    $matchedTransaction = null;

                    foreach ($userTransactions as $userTransaction) {
                        $directFcmId = is_numeric($userTransaction['fcm_notification_id'] ?? null)
                            ? (int) $userTransaction['fcm_notification_id']
                            : 0;

                        if ($directFcmId > 0 && isset($notificationsById[$directFcmId])) {
                            $matchedNotification = $notificationsById[$directFcmId];
                            $matchedTransaction = $userTransaction;
                            break;
                        }

                        $bestForTransaction = null;

                        foreach ($userNotifications as $userNotification) {
                            $notificationAt = (string) ($userNotification['timestamp'] ?? '');
                            if ($notificationAt === '' || strcmp($notificationAt, (string) $userTransaction['created_at']) > 0) {
                                continue;
                            }

                            if (!$notificationMatchesTransaction($userNotification, $userTransaction)) {
                                continue;
                            }

                            if ($bestForTransaction === null || strcmp($notificationAt, (string) ($bestForTransaction['timestamp'] ?? '')) >= 0) {
                                $bestForTransaction = $userNotification;
                            }
                        }

                        if ($bestForTransaction !== null) {
                            $matchedNotification = $bestForTransaction;
                            $matchedTransaction = $userTransaction;
                            break;
                        }
                    }

                    if ($matchedNotification === null || $matchedTransaction === null) {
                        return $leadUser;
                    }

                    $lagMinutes = null;
                    try {
                        $lagMinutes = \Illuminate\Support\Carbon::parse($matchedNotification['timestamp'])
                            ->diffInMinutes(\Illuminate\Support\Carbon::parse($matchedTransaction['created_at']));
                    } catch (\Throwable $e) {
                        $lagMinutes = null;
                    }

                    $leadUser['conversion_captured'] = true;
                    $leadUser['conversion_notification_id'] = (int) ($matchedNotification['id'] ?? 0);
                    $leadUser['conversion_notification_title'] = !empty($matchedNotification['notification_title'])
                        ? (string) $matchedNotification['notification_title']
                        : null;
                    $leadUser['conversion_notification_text'] = !empty($matchedNotification['notification_text'])
                        ? (string) $matchedNotification['notification_text']
                        : null;
                    $leadUser['conversion_notification_type'] = (string) ($matchedNotification['notification_type'] ?? 'unknown');
                    $leadUser['conversion_notification_bucket'] = trim((string) ($matchedNotification['bucket'] ?? ''));
                    $leadUser['conversion_notification_at'] = (string) ($matchedNotification['timestamp'] ?? '');
                    $leadUser['conversion_transaction_id'] = (int) ($matchedTransaction['id'] ?? 0);
                    $leadUser['conversion_transaction_type'] = (string) ($matchedTransaction['type'] ?? 'unknown');
                    $leadUser['conversion_transaction_status'] = $matchedTransaction['status'] ?? null;
                    $leadUser['conversion_transaction_at'] = (string) ($matchedTransaction['created_at'] ?? '');
                    $leadUser['conversion_transaction_doctor_id'] = is_numeric($matchedTransaction['doctor_id'] ?? null)
                        ? (int) $matchedTransaction['doctor_id']
                        : null;
                    $leadUser['conversion_transaction_doctor_name'] = !empty($matchedTransaction['doctor_name'])
                        ? (string) $matchedTransaction['doctor_name']
                        : null;
                    $leadUser['conversion_transaction_clinic_id'] = is_numeric($matchedTransaction['clinic_id'] ?? null)
                        ? (int) $matchedTransaction['clinic_id']
                        : null;
                    $leadUser['conversion_transaction_clinic_name'] = !empty($matchedTransaction['clinic_name'])
                        ? (string) $matchedTransaction['clinic_name']
                        : null;
                    $leadUser['conversion_lag_minutes'] = is_numeric($lagMinutes) ? (int) $lagMinutes : null;

                    $matchedNotificationId = is_numeric($matchedNotification['id'] ?? null)
                        ? (int) $matchedNotification['id']
                        : 0;
                    if ($matchedNotificationId > 0) {
                        $leadUser['all_notifications'] = collect($leadUser['all_notifications'] ?? [])
                            ->map(function (array $notificationRow) use (
                                $matchedNotificationId,
                                $matchedTransaction,
                                $lagMinutes
                            ): array {
                                $rowNotificationId = is_numeric($notificationRow['id'] ?? null)
                                    ? (int) $notificationRow['id']
                                    : 0;

                                if ($rowNotificationId !== $matchedNotificationId) {
                                    return $notificationRow;
                                }

                                $notificationRow['converted'] = true;
                                $notificationRow['conversion_transaction_id'] = (int) ($matchedTransaction['id'] ?? 0);
                                $notificationRow['conversion_transaction_type'] = (string) ($matchedTransaction['type'] ?? 'unknown');
                                $notificationRow['conversion_transaction_status'] = $matchedTransaction['status'] ?? null;
                                $notificationRow['conversion_transaction_at'] = (string) ($matchedTransaction['created_at'] ?? '');
                                $notificationRow['conversion_lag_minutes'] = is_numeric($lagMinutes) ? (int) $lagMinutes : null;

                                return $notificationRow;
                            })
                            ->values()
                            ->all();
                    }

                    return $leadUser;
                });

                    $convertedUsersCount = $targetUsers
                        ->filter(fn (array $leadUser) => (bool) ($leadUser['conversion_captured'] ?? false))
                        ->count();
                }
            } catch (\Throwable $e) {
                $captureLeadManagementError('conversion_tracking', $e);
                $convertedUsersCount = 0;
            }
        }

        $supportsLeadActivityLogs = Schema::hasTable('lead_management_activity_logs')
            && Schema::hasColumn('lead_management_activity_logs', 'user_id');

        if ($supportsLeadActivityLogs && $targetUsers->isNotEmpty()) {
            try {
                $leadUserIds = $targetUsers
                    ->keys()
                    ->filter(fn ($userId) => is_numeric($userId) && (int) $userId > 0)
                    ->map(fn ($userId) => (int) $userId)
                    ->values()
                    ->all();

                if (!empty($leadUserIds)) {
                    $hasLeadActivityEventType = Schema::hasColumn('lead_management_activity_logs', 'event_type');
                    $hasLeadActivityActionType = Schema::hasColumn('lead_management_activity_logs', 'action_type');
                    $hasLeadActivityOutcome = Schema::hasColumn('lead_management_activity_logs', 'outcome');
                    $hasLeadActivityNotes = Schema::hasColumn('lead_management_activity_logs', 'notes');
                    $hasLeadActivityEventAt = Schema::hasColumn('lead_management_activity_logs', 'event_at');
                    $hasLeadActivityDueDate = Schema::hasColumn('lead_management_activity_logs', 'due_date');
                    $hasLeadActivityAssignedTo = Schema::hasColumn('lead_management_activity_logs', 'assigned_to');
                    $hasLeadActivityBlocker = Schema::hasColumn('lead_management_activity_logs', 'blocker');
                    $hasLeadActivityDoneBy = Schema::hasColumn('lead_management_activity_logs', 'done_by');
                    $hasLeadActivityCreatedBy = Schema::hasColumn('lead_management_activity_logs', 'created_by');
                    $hasLeadActivityCreatedAt = Schema::hasColumn('lead_management_activity_logs', 'created_at');

                    $activityColumns = ['id', 'user_id'];
                    if ($hasLeadActivityEventType) {
                        $activityColumns[] = 'event_type';
                    }
                    if ($hasLeadActivityActionType) {
                        $activityColumns[] = 'action_type';
                    }
                    if ($hasLeadActivityOutcome) {
                        $activityColumns[] = 'outcome';
                    }
                    if ($hasLeadActivityNotes) {
                        $activityColumns[] = 'notes';
                    }
                    if ($hasLeadActivityEventAt) {
                        $activityColumns[] = 'event_at';
                    }
                    if ($hasLeadActivityDueDate) {
                        $activityColumns[] = 'due_date';
                    }
                    if ($hasLeadActivityAssignedTo) {
                        $activityColumns[] = 'assigned_to';
                    }
                    if ($hasLeadActivityBlocker) {
                        $activityColumns[] = 'blocker';
                    }
                    if ($hasLeadActivityDoneBy) {
                        $activityColumns[] = 'done_by';
                    }
                    if ($hasLeadActivityCreatedBy) {
                        $activityColumns[] = 'created_by';
                    }
                    if ($hasLeadActivityCreatedAt) {
                        $activityColumns[] = 'created_at';
                    }

                    $maxActivityRows = min(max($limit * 20, 3000), 12000);
                    $activityRows = DB::table('lead_management_activity_logs')
                        ->select(array_unique($activityColumns))
                        ->whereIn('user_id', $leadUserIds)
                        ->orderByDesc($hasLeadActivityEventAt ? 'event_at' : 'id')
                        ->orderByDesc('id')
                        ->limit($maxActivityRows)
                        ->get();

                    $logsPerUser = [];
                    foreach ($activityRows as $activityRow) {
                        $userId = is_numeric($activityRow->user_id ?? null) ? (int) $activityRow->user_id : 0;
                        if ($userId <= 0 || !$targetUsers->has($userId)) {
                            continue;
                        }

                        if (!isset($logsPerUser[$userId])) {
                            $logsPerUser[$userId] = 0;
                        }
                        if ($logsPerUser[$userId] >= 50) {
                            continue;
                        }

                        $eventType = strtolower(trim((string) ($activityRow->event_type ?? 'log_action')));
                        if (!in_array($eventType, ['log_action', 'next_action'], true)) {
                            continue;
                        }

                        $eventAt = $normalizeDateTime($activityRow->event_at ?? null);
                        if ($eventAt === null) {
                            $eventAt = $normalizeDateTime($activityRow->created_at ?? null);
                        }

                        $dueDate = null;
                        if (!empty($activityRow->due_date)) {
                            try {
                                $dueDate = \Illuminate\Support\Carbon::parse($activityRow->due_date)->toDateString();
                            } catch (\Throwable $e) {
                                $dueDate = null;
                            }
                        }

                        $activityPayload = [
                            'id' => (int) ($activityRow->id ?? 0),
                            'event_type' => $eventType,
                            'action_type' => trim((string) ($activityRow->action_type ?? '')),
                            'outcome' => trim((string) ($activityRow->outcome ?? '')),
                            'notes' => trim((string) ($activityRow->notes ?? '')),
                            'event_at' => $eventAt,
                            'due_date' => $dueDate,
                            'assigned_to' => trim((string) ($activityRow->assigned_to ?? '')),
                            'blocker' => trim((string) ($activityRow->blocker ?? '')),
                            'done_by' => trim((string) ($activityRow->done_by ?? '')),
                            'created_by' => trim((string) ($activityRow->created_by ?? '')),
                        ];

                        $leadUser = $targetUsers->get($userId);
                        if (!is_array($leadUser)) {
                            continue;
                        }

                        $leadUser['crm_activity_logs'][] = $activityPayload;
                        $logsPerUser[$userId] = (int) $logsPerUser[$userId] + 1;

                        if ($eventType === 'next_action' && empty($leadUser['crm_next_action'])) {
                            $leadUser['crm_next_action'] = [
                                'id' => (int) ($activityRow->id ?? 0),
                                'action' => trim((string) ($activityRow->action_type ?? '')),
                                'details' => trim((string) ($activityRow->notes ?? '')),
                                'due_date' => $dueDate,
                                'assigned_to' => trim((string) ($activityRow->assigned_to ?? '')),
                                'blocker' => trim((string) ($activityRow->blocker ?? '')),
                                'done_by' => trim((string) ($activityRow->done_by ?? '')),
                                'event_at' => $eventAt,
                            ];
                        }

                        $targetUsers->put($userId, $leadUser);
                    }
                }
            } catch (\Throwable $e) {
                $captureLeadManagementError('lead_activity_logs', $e);
            }
        }

        $latestRelatedTransactionTimestamp = static function (array $leadUser): string {
            $timestamps = collect($leadUser['related_transactions'] ?? [])
                ->pluck('created_at')
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->map(fn ($value) => trim((string) $value))
                ->values()
                ->all();

            $conversionTimestamp = trim((string) ($leadUser['conversion_transaction_at'] ?? ''));
            if ($conversionTimestamp !== '') {
                $timestamps[] = $conversionTimestamp;
            }

            if (empty($timestamps)) {
                return '';
            }

            rsort($timestamps, SORT_STRING);

            return (string) ($timestamps[0] ?? '');
        };

        $filteredTargetUsers = $targetUsers
            ->values()
            ->filter(function (array $leadUser) use ($leadFilter): bool {
                return match ($leadFilter) {
                    'all' => true,
                    'neutering' => (bool) $leadUser['has_neutering'],
                    'video_follow_up' => (bool) $leadUser['has_video_follow_up'],
                    'video_follow_up_video' => (bool) $leadUser['has_video_follow_up_video'],
                    'video_follow_up_in_clinic' => (bool) $leadUser['has_video_follow_up_in_clinic'],
                    'vaccination' => (bool) $leadUser['has_vaccination_reminder'],
                    'both' => (bool) $leadUser['has_neutering'] && (bool) $leadUser['has_video_follow_up'],
                    default => (bool) $leadUser['has_neutering']
                        || (bool) $leadUser['has_video_follow_up']
                        || (bool) $leadUser['has_vaccination_reminder'],
                };
            })
            ->filter(fn (array $leadUser): bool => $matchesLeadSearch($leadUser, $searchTerm))
            ->sort(function (array $left, array $right) use ($latestRelatedTransactionTimestamp): int {
                $leftLatestTransaction = $latestRelatedTransactionTimestamp($left);
                $rightLatestTransaction = $latestRelatedTransactionTimestamp($right);
                if ($leftLatestTransaction !== $rightLatestTransaction) {
                    return strcmp((string) $rightLatestTransaction, (string) $leftLatestTransaction);
                }

                $leftDate = $left['next_follow_up_date'] ?? '9999-12-31';
                $rightDate = $right['next_follow_up_date'] ?? '9999-12-31';
                if ($leftDate !== $rightDate) {
                    return strcmp((string) $leftDate, (string) $rightDate);
                }

                if ((int) $left['video_follow_up_count'] !== (int) $right['video_follow_up_count']) {
                    return (int) $right['video_follow_up_count'] <=> (int) $left['video_follow_up_count'];
                }

                if ((int) $left['video_follow_up_video_count'] !== (int) $right['video_follow_up_video_count']) {
                    return (int) $right['video_follow_up_video_count'] <=> (int) $left['video_follow_up_video_count'];
                }

                if ((int) $left['video_follow_up_in_clinic_count'] !== (int) $right['video_follow_up_in_clinic_count']) {
                    return (int) $right['video_follow_up_in_clinic_count'] <=> (int) $left['video_follow_up_in_clinic_count'];
                }

                if ((int) $left['vaccination_notification_count'] !== (int) $right['vaccination_notification_count']) {
                    return (int) $right['vaccination_notification_count'] <=> (int) $left['vaccination_notification_count'];
                }

                if ((int) $left['neutering_pet_count'] !== (int) $right['neutering_pet_count']) {
                    return (int) $right['neutering_pet_count'] <=> (int) $left['neutering_pet_count'];
                }

                return strcmp(
                    strtolower(trim((string) ($left['name'] ?? ''))),
                    strtolower(trim((string) ($right['name'] ?? '')))
                );
            })
            ->values();

        $totalFilteredUsers = $filteredTargetUsers->count();
        $lastPage = max((int) ceil($totalFilteredUsers / max($perPage, 1)), 1);
        $page = min($page, $lastPage);

        $filteredTargetUsers = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredTargetUsers->forPage($page, $perPage)->values(),
            $totalFilteredUsers,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ]
        );

        $neuteringNotifiedUsersCount = $targetUsers
            ->filter(fn (array $leadUser) => (bool) ($leadUser['has_neutering'] ?? false))
            ->filter(fn (array $leadUser) => (int) ($leadUser['neutering_notification_count'] ?? 0) > 0)
            ->count();

        $vaccinationNotifiedUsersCount = $targetUsers
            ->filter(fn (array $leadUser) => (int) ($leadUser['vaccination_notification_count'] ?? 0) > 0)
            ->count();

        return view('admin.lead-management', [
            'filteredTargetUsers' => $filteredTargetUsers,
            'leadFilter' => $leadFilter,
            'runtimeWarnings' => array_values(array_unique($runtimeWarnings)),
            'transactionDoctorOptions' => Doctor::query()
                ->where('exported_from_excell', 1)
                ->select('id', 'vet_registeration_id', 'doctor_name')
                ->orderBy('doctor_name')
                ->get(),
            'summary' => [
                'neutering_leads' => $neuteringLeadCount,
                'video_follow_up_leads' => $videoFollowUpLeadCount,
                'video_follow_up_video_leads' => $videoFollowUpVideoLeadCount,
                'video_follow_up_in_clinic_leads' => $videoFollowUpInClinicLeadCount,
                'target_users' => $targetUsers->count(),
                'filtered_users' => $totalFilteredUsers,
                'neutering_notified_users' => $neuteringNotifiedUsersCount,
                'vaccination_notified_users' => $vaccinationNotifiedUsersCount,
                'converted_users' => $convertedUsersCount,
            ],
            'limit' => $limit,
            'perPage' => $perPage,
            'leadConfig' => [
                'supports_neutering' => $hasIsNeutered || $hasIsNuetered,
                'supports_video_follow_up' => $supportsVideoFollowUpLeads,
                'supports_video_follow_up_mode_split' => $supportsVideoFollowUpModeSplit,
                'supports_neutering_notification_join' => $supportsNeuteringNotificationJoin,
                'supports_follow_up_notification_join' => $supportsFollowUpNotificationJoin,
                'supports_vaccination_notification_join' => $supportsVaccinationNotificationJoin,
                'supports_conversion_tracking' => $supportsConversionTracking,
                'transaction_session_column' => $transactionSessionColumn,
                'neutering_columns' => [
                    'is_neutered' => $hasIsNeutered,
                    'is_nuetered' => $hasIsNuetered,
                ],
            ],
        ]);
        } catch (\Throwable $e) {
            Log::error('Lead management failed with unhandled exception', [
                'stage' => 'lead_management_unhandled',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    public function storeLeadManagementLog(Request $request, User $user): JsonResponse
    {
        if (!Schema::hasTable('lead_management_activity_logs')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead activity storage table is missing. Please run migrations.',
            ], 422);
        }

        $validated = $request->validate([
            'action_type' => ['required', 'string', 'max:120'],
            'outcome' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'action_at' => ['nullable', 'date'],
            'done_by' => ['nullable', 'string', 'max:120'],
        ]);

        $eventAt = now();
        if (!empty($validated['action_at'])) {
            try {
                $eventAt = \Illuminate\Support\Carbon::parse($validated['action_at']);
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid action date/time.',
                ], 422);
            }
        }

        $doneBy = trim((string) ($validated['done_by'] ?? ''));
        if ($doneBy === '') {
            $doneBy = trim((string) (session('admin_email', config('admin.email', 'Admin'))));
            if ($doneBy === '') {
                $doneBy = 'Admin';
            }
        }

        try {
            $activityId = DB::table('lead_management_activity_logs')->insertGetId([
                'user_id' => (int) $user->id,
                'event_type' => 'log_action',
                'action_type' => trim((string) ($validated['action_type'] ?? '')),
                'outcome' => trim((string) ($validated['outcome'] ?? '')),
                'notes' => trim((string) ($validated['notes'] ?? '')),
                'event_at' => $eventAt->toDateTimeString(),
                'done_by' => $doneBy,
                'created_by' => trim((string) session('admin_email', config('admin.email', 'Admin'))),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save action log.',
            ], 500);
        }

        $activityRow = DB::table('lead_management_activity_logs')
            ->where('id', $activityId)
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Action log saved.',
            'activity' => $this->serializeLeadManagementActivity($activityRow),
        ]);
    }

    public function storeLeadManagementNextAction(Request $request, User $user): JsonResponse
    {
        if (!Schema::hasTable('lead_management_activity_logs')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead activity storage table is missing. Please run migrations.',
            ], 422);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:4000'],
            'due_date' => ['required', 'date'],
            'assigned_to' => ['nullable', 'string', 'max:120'],
            'blocker' => ['nullable', 'string', 'max:4000'],
        ]);

        $dueDate = null;
        try {
            $dueDate = \Illuminate\Support\Carbon::parse($validated['due_date'])->toDateString();
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid due date.',
            ], 422);
        }

        $assignedTo = trim((string) ($validated['assigned_to'] ?? ''));
        if ($assignedTo === '') {
            $assignedTo = trim((string) (session('admin_email', config('admin.email', 'Admin'))));
            if ($assignedTo === '') {
                $assignedTo = 'Admin';
            }
        }

        try {
            $activityId = DB::table('lead_management_activity_logs')->insertGetId([
                'user_id' => (int) $user->id,
                'event_type' => 'next_action',
                'action_type' => trim((string) ($validated['action'] ?? '')),
                'notes' => trim((string) ($validated['details'] ?? '')),
                'due_date' => $dueDate,
                'assigned_to' => $assignedTo,
                'blocker' => trim((string) ($validated['blocker'] ?? '')),
                'done_by' => $assignedTo,
                'created_by' => trim((string) session('admin_email', config('admin.email', 'Admin'))),
                'event_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save next action.',
            ], 500);
        }

        $activityRow = DB::table('lead_management_activity_logs')
            ->where('id', $activityId)
            ->first();

        $activityPayload = $this->serializeLeadManagementActivity($activityRow);

        return response()->json([
            'status' => 'success',
            'message' => 'Next action saved.',
            'activity' => $activityPayload,
            'next_action' => [
                'id' => (int) ($activityPayload['id'] ?? 0),
                'action' => (string) ($activityPayload['action_type'] ?? ''),
                'details' => (string) ($activityPayload['notes'] ?? ''),
                'due_date' => (string) ($activityPayload['due_date'] ?? ''),
                'assigned_to' => (string) ($activityPayload['assigned_to'] ?? ''),
                'blocker' => (string) ($activityPayload['blocker'] ?? ''),
                'done_by' => (string) ($activityPayload['done_by'] ?? ''),
                'event_at' => (string) ($activityPayload['event_at'] ?? ''),
            ],
        ]);
    }

    private function serializeLeadManagementActivity(?object $row): array
    {
        if ($row === null) {
            return [];
        }

        $eventAt = null;
        if (!empty($row->event_at)) {
            try {
                $eventAt = \Illuminate\Support\Carbon::parse($row->event_at)->toDateTimeString();
            } catch (\Throwable $e) {
                $eventAt = null;
            }
        }
        if ($eventAt === null && !empty($row->created_at)) {
            try {
                $eventAt = \Illuminate\Support\Carbon::parse($row->created_at)->toDateTimeString();
            } catch (\Throwable $e) {
                $eventAt = null;
            }
        }

        $dueDate = null;
        if (!empty($row->due_date)) {
            try {
                $dueDate = \Illuminate\Support\Carbon::parse($row->due_date)->toDateString();
            } catch (\Throwable $e) {
                $dueDate = null;
            }
        }

        return [
            'id' => (int) ($row->id ?? 0),
            'event_type' => strtolower(trim((string) ($row->event_type ?? 'log_action'))),
            'action_type' => trim((string) ($row->action_type ?? '')),
            'outcome' => trim((string) ($row->outcome ?? '')),
            'notes' => trim((string) ($row->notes ?? '')),
            'event_at' => $eventAt,
            'due_date' => $dueDate,
            'assigned_to' => trim((string) ($row->assigned_to ?? '')),
            'blocker' => trim((string) ($row->blocker ?? '')),
            'done_by' => trim((string) ($row->done_by ?? '')),
            'created_by' => trim((string) ($row->created_by ?? '')),
        ];
    }

    public function deleteLeadManagementUser(Request $request, User $user): RedirectResponse
    {
        $filters = array_filter([
            'lead_filter' => $request->input('lead_filter'),
            'limit' => $request->input('limit'),
            'per_page' => $request->input('per_page'),
            'page' => $request->input('page'),
        ], static fn ($value) => $value !== null && $value !== '');
        $userId = (int) $user->id;

        try {
            $this->deleteLeadManagementUserAndRelatedData($user);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.lead-management', $filters)
                ->with('error', 'Failed to delete user '.$userId.': '.$e->getMessage());
        }

        return redirect()
            ->route('admin.lead-management', $filters)
            ->with('status', 'User '.$userId.' and related data deleted successfully.');
    }

    public function deleteLeadManagementUserApi(Request $request, User $user): JsonResponse
    {
        $userId = (int) $user->id;

        try {
            $this->deleteLeadManagementUserAndRelatedData($user);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user '.$userId.': '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User '.$userId.' and related data deleted successfully.',
            'user_id' => $userId,
        ]);
    }

    private function deleteLeadManagementUserAndRelatedData(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $petIds = collect();
            if (Schema::hasTable('pets') && Schema::hasColumn('pets', 'user_id')) {
                $petIds = DB::table('pets')
                    ->where('user_id', $user->id)
                    ->pluck('id')
                    ->filter(fn ($id) => is_numeric($id))
                    ->map(fn ($id) => (int) $id)
                    ->values();
            }

            $deleteByUserId = static function (string $table) use ($user): void {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'user_id')) {
                    return;
                }

                DB::table($table)
                    ->where('user_id', $user->id)
                    ->delete();
            };

            $deleteByPetId = static function (string $table) use ($petIds): void {
                if ($petIds->isEmpty() || !Schema::hasTable($table) || !Schema::hasColumn($table, 'pet_id')) {
                    return;
                }

                DB::table($table)
                    ->whereIn('pet_id', $petIds->all())
                    ->delete();
            };

            // Core entities requested by admin.
            $deleteByUserId('transactions');
            $deleteByPetId('transactions');

            $deleteByUserId('prescriptions');
            $deleteByPetId('prescriptions');

            $deleteByUserId('video_apointment');
            $deleteByPetId('video_apointment');

            $deleteByPetId('appointments');

            $deleteByPetId('home_service_required_by_pet');
            $deleteByPetId('home_service_required_by_pets');

            $deleteByUserId('medical_records');
            $deleteByPetId('medical_records');

            $deleteByUserId('pet_daily_cares');
            $deleteByPetId('pet_daily_cares');

            $deleteByUserId('user_observations');
            $deleteByPetId('user_observations');

            // Common linked operational rows.
            foreach ([
                'notifications',
                'fcm_notifications',
                'vet_response_reminder_logs',
                'device_tokens',
                'call_sessions',
                'payments',
                'user_ai_chats',
                'user_ai_chat_histories',
                'groomer_bookings',
                'whatsapp_notifications',
                'user_pets',
                'lead_management_activity_logs',
            ] as $table) {
                $deleteByUserId($table);
            }

            if ($petIds->isNotEmpty() && Schema::hasTable('pets') && Schema::hasColumn('pets', 'id')) {
                DB::table('pets')
                    ->whereIn('id', $petIds->all())
                    ->delete();
            }

            $user->delete();
        });
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

    public function consultationLifecycleAnalytics(Request $request): View
    {
        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'type' => ['nullable', 'string', 'in:all,video_consult,excell_export_campaign'],
        ]);

        $limit = (int) ($filters['limit'] ?? 200);
        $typeFilter = strtolower((string) ($filters['type'] ?? 'all'));

        $supportsTransactionChannel = Schema::hasTable('transactions')
            && Schema::hasColumn('transactions', 'channel_name');

        $joinAvailability = [
            'call_sessions' => $supportsTransactionChannel
                && Schema::hasTable('call_sessions')
                && Schema::hasColumn('call_sessions', 'channel_name'),
            'calls' => $supportsTransactionChannel
                && Schema::hasTable('calls')
                && (Schema::hasColumn('calls', 'channel_name') || Schema::hasColumn('calls', 'channel')),
            'prescriptions' => $supportsTransactionChannel
                && Schema::hasTable('prescriptions')
                && Schema::hasColumn('prescriptions', 'call_session'),
            'whatsapp_notifications' => Schema::hasTable('whatsapp_notifications')
                && Schema::hasColumn('whatsapp_notifications', 'channel_name'),
            'fcm_notifications' => Schema::hasTable('fcm_notifications')
                && Schema::hasColumn('fcm_notifications', 'user_id'),
            'reviews' => Schema::hasTable('vet_feedback')
                || Schema::hasTable('pet_feedback'),
        ];

        $query = $this->appointmentTransactionsQuery()
            ->select('transactions.*')
            ->with([
                'clinic:id,name',
                'doctor:id,doctor_name,doctor_email,doctor_mobile',
                'user:id,name,email,phone,city,created_at',
                'pet:id,user_id,name,pet_type,breed,created_at',
            ]);

        if ($typeFilter !== 'all') {
            $query->where(function (Builder $builder) use ($typeFilter) {
                $builder->where('transactions.type', $typeFilter)
                    ->orWhere('transactions.metadata->order_type', $typeFilter);
            });
        }

        if ($joinAvailability['call_sessions']) {
            $latestCallSessionsByChannel = DB::table('call_sessions as cs')
                ->selectRaw('MAX(cs.id) as latest_call_session_id, cs.channel_name, COUNT(*) as call_session_count')
                ->whereNotNull('cs.channel_name')
                ->where('cs.channel_name', '!=', '')
                ->groupBy('cs.channel_name');

            $query
                ->leftJoinSub($latestCallSessionsByChannel, 'latest_call_session_by_channel', function ($join) {
                    $join->on('latest_call_session_by_channel.channel_name', '=', 'transactions.channel_name');
                })
                ->leftJoin('call_sessions as joined_call_session', 'joined_call_session.id', '=', 'latest_call_session_by_channel.latest_call_session_id')
                ->addSelect([
                    'latest_call_session_by_channel.call_session_count as joined_call_session_count',
                ]);

            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'id', 'joined_call_session_id');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'doctor_id', 'joined_call_session_doctor_id');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'patient_id', 'joined_call_session_patient_id');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'status', 'joined_call_session_status');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'payment_status', 'joined_call_session_payment_status');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'accepted_at', 'joined_call_session_accepted_at');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'started_at', 'joined_call_session_started_at');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'ended_at', 'joined_call_session_ended_at');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'duration_seconds', 'joined_call_session_duration_seconds');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'payment_id', 'joined_call_session_payment_id');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'amount_paid', 'joined_call_session_amount_paid');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'currency', 'joined_call_session_currency');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'call_identifier', 'joined_call_session_call_identifier');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'doctor_join_url', 'joined_call_session_doctor_join_url');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'patient_payment_url', 'joined_call_session_patient_payment_url');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'is_completed', 'joined_call_session_is_completed');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'created_at', 'joined_call_session_created_at');
            $this->addJoinedSelectIfColumnExists($query, 'call_sessions', 'joined_call_session', 'updated_at', 'joined_call_session_updated_at');
        }

        if ($joinAvailability['calls']) {
            $callChannelColumn = Schema::hasColumn('calls', 'channel_name') ? 'channel_name' : 'channel';

            $latestCallsByChannel = DB::table('calls as c')
                ->selectRaw("MAX(c.id) as latest_call_id, c.{$callChannelColumn} as call_channel_key, COUNT(*) as call_count")
                ->whereNotNull("c.{$callChannelColumn}")
                ->where("c.{$callChannelColumn}", '!=', '')
                ->groupBy("c.{$callChannelColumn}");

            $query
                ->leftJoinSub($latestCallsByChannel, 'latest_call_by_channel', function ($join) {
                    $join->on('latest_call_by_channel.call_channel_key', '=', 'transactions.channel_name');
                })
                ->leftJoin('calls as joined_call', 'joined_call.id', '=', 'latest_call_by_channel.latest_call_id')
                ->addSelect([
                    'latest_call_by_channel.call_count as joined_call_count',
                    'latest_call_by_channel.call_channel_key as joined_call_channel_key',
                ]);

            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'id', 'joined_call_id');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'doctor_id', 'joined_call_doctor_id');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'patient_id', 'joined_call_patient_id');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'status', 'joined_call_status');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'channel_name', 'joined_call_channel_name');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'channel', 'joined_call_channel');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'accepted_at', 'joined_call_accepted_at');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'rejected_at', 'joined_call_rejected_at');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'ended_at', 'joined_call_ended_at');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'cancelled_at', 'joined_call_cancelled_at');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'missed_at', 'joined_call_missed_at');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'rtc', 'joined_call_rtc');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'created_at', 'joined_call_created_at');
            $this->addJoinedSelectIfColumnExists($query, 'calls', 'joined_call', 'updated_at', 'joined_call_updated_at');
        }

        if ($joinAvailability['prescriptions']) {
            $latestPrescriptionsByChannel = DB::table('prescriptions as p')
                ->selectRaw('MAX(p.id) as latest_prescription_id, p.call_session as call_session_key, COUNT(*) as prescription_count')
                ->whereNotNull('p.call_session')
                ->where('p.call_session', '!=', '')
                ->groupBy('p.call_session');

            $query
                ->leftJoinSub($latestPrescriptionsByChannel, 'latest_prescription_by_channel', function ($join) {
                    $join->on('latest_prescription_by_channel.call_session_key', '=', 'transactions.channel_name');
                })
                ->leftJoin('prescriptions as joined_prescription', 'joined_prescription.id', '=', 'latest_prescription_by_channel.latest_prescription_id')
                ->addSelect([
                    'latest_prescription_by_channel.prescription_count as joined_prescription_count',
                    'latest_prescription_by_channel.call_session_key as joined_prescription_call_session_key',
                ]);

            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'id', 'joined_prescription_id');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'doctor_id', 'joined_prescription_doctor_id');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'user_id', 'joined_prescription_user_id');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'pet_id', 'joined_prescription_pet_id');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'call_session', 'joined_prescription_call_session');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'video_inclinic', 'joined_prescription_video_inclinic');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'visit_category', 'joined_prescription_visit_category');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'case_severity', 'joined_prescription_case_severity');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'diagnosis', 'joined_prescription_diagnosis');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'disease_name', 'joined_prescription_disease_name');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'prognosis', 'joined_prescription_prognosis');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'follow_up_required', 'joined_prescription_follow_up_required');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'follow_up_date', 'joined_prescription_follow_up_date');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'follow_up_notification_sent_at', 'joined_prescription_follow_up_notification_sent_at');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'image_path', 'joined_prescription_image_path');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'created_at', 'joined_prescription_created_at');
            $this->addJoinedSelectIfColumnExists($query, 'prescriptions', 'joined_prescription', 'updated_at', 'joined_prescription_updated_at');
        }

        if ($supportsTransactionChannel && $joinAvailability['whatsapp_notifications']) {
            $latestSentWhatsAppByChannel = DB::table('whatsapp_notifications as wn')
                ->selectRaw('MAX(wn.id) as latest_whatsapp_notification_id, wn.channel_name, COUNT(*) as whatsapp_notification_count')
                ->whereNotNull('wn.channel_name')
                ->where('wn.channel_name', '!=', '');

            if (Schema::hasColumn('whatsapp_notifications', 'status')) {
                $latestSentWhatsAppByChannel->where('wn.status', 'sent');
            } elseif (Schema::hasColumn('whatsapp_notifications', 'sent_at')) {
                $latestSentWhatsAppByChannel->whereNotNull('wn.sent_at');
            }

            $latestSentWhatsAppByChannel->groupBy('wn.channel_name');

            $query
                ->leftJoinSub($latestSentWhatsAppByChannel, 'latest_whatsapp_notification_by_channel', function ($join) {
                    $join->on('latest_whatsapp_notification_by_channel.channel_name', '=', 'transactions.channel_name');
                })
                ->leftJoin('whatsapp_notifications as joined_whatsapp_notification', 'joined_whatsapp_notification.id', '=', 'latest_whatsapp_notification_by_channel.latest_whatsapp_notification_id')
                ->addSelect([
                    'latest_whatsapp_notification_by_channel.whatsapp_notification_count as joined_whatsapp_notification_count',
                ]);

            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'id', 'joined_whatsapp_notification_id');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'channel_name', 'joined_whatsapp_notification_channel_name');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'status', 'joined_whatsapp_notification_status');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'template_name', 'joined_whatsapp_notification_template_name');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'message_type', 'joined_whatsapp_notification_message_type');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'recipient', 'joined_whatsapp_notification_recipient');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'http_status', 'joined_whatsapp_notification_http_status');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'provider_message_id', 'joined_whatsapp_notification_provider_message_id');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'error_message', 'joined_whatsapp_notification_error_message');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'sent_at', 'joined_whatsapp_notification_sent_at');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'created_at', 'joined_whatsapp_notification_created_at');
            $this->addJoinedSelectIfColumnExists($query, 'whatsapp_notifications', 'joined_whatsapp_notification', 'updated_at', 'joined_whatsapp_notification_updated_at');
        }

        $transactions = $query
            ->orderByDesc('transactions.created_at')
            ->orderByDesc('transactions.id')
            ->limit($limit)
            ->get();

        $latestAssignmentLogs = $this->latestTransactionDoctorAssignmentLogs($transactions);
        $notificationLookup = $this->notificationLookupsForTransactions($transactions);
        $feedbackLookup = $this->feedbackSubmissionLookupForTransactions($transactions);

        $enriched = $transactions->map(function (Transaction $transaction) use ($latestAssignmentLogs, $notificationLookup, $feedbackLookup) {
            $createdAt = $this->normalizeLifecycleTimestamp($transaction->created_at);
            $assignmentLog = $latestAssignmentLogs->get($transaction->id);
            $assignmentLoggedAt = $assignmentLog?->created_at ?? null;

            $assignedCandidates = [
                [
                    'timestamp' => $assignmentLoggedAt,
                    'source' => 'transaction_doctor_assignment_logs.created_at',
                    'secure' => true,
                ],
                [
                    'timestamp' => ($transaction->doctor_id ? $transaction->created_at : null),
                    'source' => 'transactions.doctor_id + transactions.created_at',
                    'secure' => true,
                ],
                [
                    'timestamp' => ($transaction->getAttribute('joined_call_session_doctor_id')
                        ? $transaction->getAttribute('joined_call_session_created_at')
                        : null),
                    'source' => 'call_sessions.doctor_id + call_sessions.created_at',
                    'secure' => true,
                ],
                [
                    'timestamp' => ($transaction->getAttribute('joined_call_doctor_id')
                        ? $transaction->getAttribute('joined_call_created_at')
                        : null),
                    'source' => 'calls.doctor_id + calls.created_at',
                    'secure' => true,
                ],
            ];

            $consultationAssignedAt = null;
            $consultationAssignedSource = null;
            $consultationAssignedSecure = false;
            foreach ($assignedCandidates as $candidate) {
                $timestamp = $this->normalizeLifecycleTimestamp($candidate['timestamp']);
                if (!$timestamp) {
                    continue;
                }
                $consultationAssignedAt = $timestamp;
                $consultationAssignedSource = $candidate['source'];
                $consultationAssignedSecure = (bool) $candidate['secure'];
                break;
            }

            $callStartedAt = $this->firstLifecycleTimestamp([
                $transaction->getAttribute('joined_call_session_started_at'),
                $transaction->getAttribute('joined_call_accepted_at'),
            ]);
            $callStartedSource = $callStartedAt
                ? ($this->normalizeLifecycleTimestamp($transaction->getAttribute('joined_call_session_started_at'))
                    ? 'call_sessions.started_at'
                    : 'calls.accepted_at')
                : null;

            $callCompletedAt = $this->firstLifecycleTimestamp([
                $transaction->getAttribute('joined_call_session_ended_at'),
                $transaction->getAttribute('joined_call_ended_at'),
            ]);
            $callCompletedSource = $callCompletedAt
                ? ($this->normalizeLifecycleTimestamp($transaction->getAttribute('joined_call_session_ended_at'))
                    ? 'call_sessions.ended_at'
                    : 'calls.ended_at')
                : null;

            if (!$callCompletedAt) {
                $callSessionStatus = strtolower((string) ($transaction->getAttribute('joined_call_session_status') ?? ''));
                $callStatus = strtolower((string) ($transaction->getAttribute('joined_call_status') ?? ''));
                $callSessionCompleted = filter_var($transaction->getAttribute('joined_call_session_is_completed'), FILTER_VALIDATE_BOOLEAN);

                if ($callSessionStatus === 'ended' || $callSessionCompleted) {
                    $callCompletedAt = $this->firstLifecycleTimestamp([
                        $transaction->getAttribute('joined_call_session_updated_at'),
                        $transaction->getAttribute('joined_call_session_created_at'),
                    ]);
                    $callCompletedSource = $callSessionCompleted
                        ? 'call_sessions.is_completed + call_sessions.updated_at'
                        : 'call_sessions.status=ended + call_sessions.updated_at';
                } elseif ($callStatus === 'ended') {
                    $callCompletedAt = $this->firstLifecycleTimestamp([
                        $transaction->getAttribute('joined_call_updated_at'),
                        $transaction->getAttribute('joined_call_created_at'),
                    ]);
                    $callCompletedSource = 'calls.status=ended + calls.updated_at';
                }
            }

            $prescriptionUploadedAt = $this->normalizeLifecycleTimestamp(
                $transaction->getAttribute('joined_prescription_created_at')
            );
            $prescriptionUploadedSource = $prescriptionUploadedAt ? 'prescriptions.created_at' : null;

            $channelKey = $this->normalizedLifecycleChannelKey($transaction->channel_name);
            $whatsAppRows = $channelKey !== null
                ? ($notificationLookup['whatsapp_rows_by_channel'][$channelKey] ?? [])
                : [];
            $joinedWhatsAppSentAt = $this->latestLifecycleTimestamp([
                $transaction->getAttribute('joined_whatsapp_notification_sent_at'),
                $transaction->getAttribute('joined_whatsapp_notification_created_at'),
            ]);
            $lookupWhatsAppSentAt = $channelKey !== null
                ? ($notificationLookup['whatsapp_sent_by_channel'][$channelKey] ?? null)
                : null;
            $whatsAppNotificationSentAt = $this->latestLifecycleTimestamp([
                $joinedWhatsAppSentAt,
                $lookupWhatsAppSentAt,
            ]);
            $whatsAppLastAttemptAt = $channelKey !== null
                ? ($notificationLookup['whatsapp_last_attempt_by_channel'][$channelKey] ?? null)
                : null;
            $doctorFeedbackSubmittedAt = $channelKey !== null
                ? ($feedbackLookup['doctor_feedback_by_channel'][$channelKey] ?? null)
                : null;
            $petFeedbackSubmittedAt = $channelKey !== null
                ? ($feedbackLookup['pet_feedback_by_channel'][$channelKey] ?? null)
                : null;
            $doctorFeedbackExists = $channelKey !== null
                ? (bool) ($feedbackLookup['doctor_feedback_exists_by_channel'][$channelKey] ?? false)
                : false;
            $petFeedbackExists = $channelKey !== null
                ? (bool) ($feedbackLookup['pet_feedback_exists_by_channel'][$channelKey] ?? false)
                : false;
            $whatsAppStatusSummary = collect($whatsAppRows)
                ->countBy(fn (array $row) => strtolower(trim((string) ($row['status'] ?? 'unknown'))))
                ->all();
            $whatsAppLastStatus = !empty($whatsAppRows)
                ? strtolower(trim((string) ($whatsAppRows[0]['status'] ?? '')))
                : null;
            $fcmNotificationSentAt = is_numeric($transaction->user_id)
                ? ($notificationLookup['fcm_by_user'][(int) $transaction->user_id] ?? null)
                : null;

            $notificationSentAt = $this->latestLifecycleTimestampOnOrAfter(
                [
                    $transaction->getAttribute('joined_prescription_follow_up_notification_sent_at'),
                    $whatsAppNotificationSentAt,
                    $fcmNotificationSentAt,
                ],
                $transaction->created_at
            );

            $notificationSentSource = null;
            if ($notificationSentAt) {
                if ($this->normalizeLifecycleTimestampOnOrAfter($transaction->getAttribute('joined_prescription_follow_up_notification_sent_at'), $transaction->created_at) === $notificationSentAt) {
                    $notificationSentSource = 'prescriptions.follow_up_notification_sent_at';
                } elseif ($this->normalizeLifecycleTimestampOnOrAfter($joinedWhatsAppSentAt, $transaction->created_at) === $notificationSentAt) {
                    $notificationSentSource = 'transactions.channel_name = whatsapp_notifications.channel_name (joined sent row)';
                } elseif ($this->normalizeLifecycleTimestampOnOrAfter($whatsAppNotificationSentAt, $transaction->created_at) === $notificationSentAt) {
                    $notificationSentSource = 'whatsapp_notifications.channel_name lookup + status=sent + sent_at';
                } else {
                    $notificationSentSource = 'fcm_notifications.sent_at';
                }
            }

            $reviewRequestedAt = $this->normalizeLifecycleTimestamp($doctorFeedbackSubmittedAt);
            if (!$reviewRequestedAt && $doctorFeedbackExists) {
                $reviewRequestedAt = $createdAt;
            }
            $reviewRequestedSource = null;
            if ($reviewRequestedAt) {
                $reviewRequestedSource = $doctorFeedbackSubmittedAt
                    ? 'vet_feedback.channel_name + feedback timestamp'
                    : 'vet_feedback.channel_name (timestamp fallback: transactions.created_at)';
            }

            $reviewSubmittedAt = $this->normalizeLifecycleTimestamp($petFeedbackSubmittedAt);
            if (!$reviewSubmittedAt && $petFeedbackExists) {
                $reviewSubmittedAt = $createdAt;
            }
            $reviewSubmittedSource = null;
            if ($reviewSubmittedAt) {
                $reviewSubmittedSource = $petFeedbackSubmittedAt
                    ? 'pet_feedback.channel_name + feedback timestamp'
                    : 'pet_feedback.channel_name (timestamp fallback: transactions.created_at)';
            }

            $reviewRequestedCaptured = $reviewRequestedAt !== null || $doctorFeedbackExists;
            $reviewRequestedSecure = $doctorFeedbackExists;
            $reviewSubmittedCaptured = $reviewSubmittedAt !== null || $petFeedbackExists;
            $reviewSubmittedSecure = $petFeedbackExists;

            $userCreatedAt = $this->normalizeLifecycleTimestamp(optional($transaction->user)->created_at);
            $petAddedAt = $this->normalizeLifecycleTimestamp(optional($transaction->pet)->created_at);

            $transaction->setAttribute('event_user_created_at', $userCreatedAt);
            $transaction->setAttribute('event_user_created_source', $userCreatedAt ? 'users.created_at' : null);
            $transaction->setAttribute('event_user_created_captured', $userCreatedAt !== null);
            $transaction->setAttribute('event_user_created_secure', $userCreatedAt !== null);

            $transaction->setAttribute('event_pet_added_at', $petAddedAt);
            $transaction->setAttribute('event_pet_added_source', $petAddedAt ? 'pets.created_at' : null);
            $transaction->setAttribute('event_pet_added_captured', $petAddedAt !== null);
            $transaction->setAttribute('event_pet_added_secure', $petAddedAt !== null);

            $transaction->setAttribute('event_consultation_created_at', $createdAt);
            $transaction->setAttribute('event_consultation_created_source', 'transactions.created_at');
            $transaction->setAttribute('event_consultation_created_captured', $createdAt !== null);
            $transaction->setAttribute('event_consultation_created_secure', $createdAt !== null);

            $transaction->setAttribute('event_consultation_assigned_to_vet_at', $consultationAssignedAt);
            $transaction->setAttribute('event_consultation_assigned_to_vet_source', $consultationAssignedSource);
            $transaction->setAttribute('event_consultation_assigned_to_vet_captured', $consultationAssignedAt !== null);
            $transaction->setAttribute('event_consultation_assigned_to_vet_secure', $consultationAssignedSecure && $consultationAssignedAt !== null);

            $transaction->setAttribute('event_call_started_at', $callStartedAt);
            $transaction->setAttribute('event_call_started_source', $callStartedSource);
            $transaction->setAttribute('event_call_started_captured', $callStartedAt !== null);
            $transaction->setAttribute('event_call_started_secure', $callStartedAt !== null);

            $transaction->setAttribute('event_call_completed_at', $callCompletedAt);
            $transaction->setAttribute('event_call_completed_source', $callCompletedSource);
            $transaction->setAttribute('event_call_completed_captured', $callCompletedAt !== null);
            $transaction->setAttribute('event_call_completed_secure', $callCompletedAt !== null);

            $transaction->setAttribute('event_prescription_uploaded_at', $prescriptionUploadedAt);
            $transaction->setAttribute('event_prescription_uploaded_source', $prescriptionUploadedSource);
            $transaction->setAttribute('event_prescription_uploaded_captured', $prescriptionUploadedAt !== null);
            $transaction->setAttribute('event_prescription_uploaded_secure', $prescriptionUploadedAt !== null);

            $transaction->setAttribute('event_notification_sent_at', $notificationSentAt);
            $transaction->setAttribute('event_notification_sent_source', $notificationSentSource);
            $transaction->setAttribute('event_notification_sent_captured', $notificationSentAt !== null);
            $transaction->setAttribute('event_notification_sent_secure', $notificationSentAt !== null);
            $transaction->setAttribute('whatsapp_notifications_for_channel', $whatsAppRows);
            $transaction->setAttribute('whatsapp_notification_status_summary', $whatsAppStatusSummary);
            $transaction->setAttribute('whatsapp_notification_last_status', $whatsAppLastStatus);
            $transaction->setAttribute('whatsapp_notification_last_attempt_at', $whatsAppLastAttemptAt);
            $transaction->setAttribute('whatsapp_notification_sent_at', $whatsAppNotificationSentAt);

            $transaction->setAttribute('event_review_requested_at', $reviewRequestedAt);
            $transaction->setAttribute('event_review_requested_source', $reviewRequestedSource);
            $transaction->setAttribute('event_review_requested_captured', $reviewRequestedCaptured);
            $transaction->setAttribute('event_review_requested_secure', $reviewRequestedSecure);

            $transaction->setAttribute('event_review_submitted_at', $reviewSubmittedAt);
            $transaction->setAttribute('event_review_submitted_source', $reviewSubmittedSource);
            $transaction->setAttribute('event_review_submitted_captured', $reviewSubmittedCaptured);
            $transaction->setAttribute('event_review_submitted_secure', $reviewSubmittedSecure);

            $transaction->setAttribute(
                'joined_call_session_details',
                $this->joinedAttributesForPrefix($transaction, 'joined_call_session_')
            );
            $transaction->setAttribute(
                'joined_call_details',
                $this->joinedAttributesForPrefix($transaction, 'joined_call_')
            );
            $transaction->setAttribute(
                'joined_prescription_details',
                $this->joinedAttributesForPrefix($transaction, 'joined_prescription_')
            );

            return $transaction;
        });

        $eventDefinitions = [
            'user_created' => 'User Created',
            'pet_added' => 'Pet Added',
            'consultation_created' => 'Consultation Created',
            'consultation_assigned_to_vet' => 'Consultation Assigned To Vet',
            'call_started' => 'Call Started',
            'call_completed' => 'Call Completed',
            'prescription_uploaded' => 'Prescription Uploaded',
            'notification_sent' => 'Notification Sent',
            'review_requested' => 'Doctor Feedback',
            'review_submitted' => 'Pet Feedback',
        ];

        $eventSummary = [];
        foreach ($eventDefinitions as $eventKey => $label) {
            $captured = $enriched->filter(fn (Transaction $transaction) => (bool) $transaction->getAttribute("event_{$eventKey}_captured"))->count();
            $secure = $enriched->filter(fn (Transaction $transaction) => (bool) $transaction->getAttribute("event_{$eventKey}_secure"))->count();

            $eventSummary[$eventKey] = [
                'label' => $label,
                'captured' => $captured,
                'secure' => $secure,
            ];
        }

        return view('admin.consultation-lifecycle-analytics', [
            'transactions' => $enriched,
            'eventSummary' => $eventSummary,
            'eventDefinitions' => $eventDefinitions,
            'joinAvailability' => $joinAvailability,
            'filters' => [
                'limit' => $limit,
                'type' => $typeFilter,
            ],
        ]);
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
        $transactions = $this->appendExcellExportInvoiceFlags(
            $this->attachLatestPrescriptionIds(
            $this->excellExportTransactionsQuery()->get()
            )
        );

        if (strtolower((string) $request->query('export')) === 'csv') {
            return $this->streamExcellExportTransactionsCsv($transactions);
        }

        $statusConversionLogs = $this->transactionStatusConversionLogs($transactions);

        return view('admin.transactions-excell-export', compact('transactions', 'statusConversionLogs'));
    }

    public function downloadExcellExportInvoice(
        Request $request,
        Transaction $transaction,
        TransactionInvoiceController $invoiceController
    ) {
        if (! $this->isExcellExportTransaction($transaction)) {
            abort(404);
        }

        if (! $this->isExcellExportInvoiceStatusEligible($transaction)) {
            return redirect()
                ->route('admin.transactions.excell-export')
                ->withErrors([
                    'invoice' => sprintf(
                        'Transaction #%d status is %s. Invoice can be generated only when payment is captured.',
                        $transaction->id,
                        strtoupper(trim((string) ($transaction->status ?? 'n/a')))
                    ),
                ]);
        }

        return $invoiceController->show($request, $transaction);
    }

    public function markExcellExportTransactionCaptured(Request $request, Transaction $transaction): RedirectResponse
    {
        if (! $this->isExcellExportTransaction($transaction)) {
            return redirect()
                ->route('admin.transactions.excell-export')
                ->withErrors(['transaction' => 'Only Excel export campaign transactions can be updated from this page.']);
        }

        $currentStatus = strtolower(trim((string) ($transaction->status ?? '')));
        if ($currentStatus !== 'pending') {
            return redirect()
                ->route('admin.transactions.excell-export')
                ->with('status', "Transaction #{$transaction->id} is already {$transaction->status}.");
        }

        $previousStatus = (string) ($transaction->status ?? 'pending');
        $newStatus = 'captured';
        $changedAt = now();

        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
        $logEntry = [
            'changed_at' => $changedAt->toIso8601String(),
            'changed_by_user_id' => optional($request->user())->id,
            'changed_by_name' => optional($request->user())->name,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'source' => 'admin.transactions.excell-export',
        ];

        $history = data_get($metadata, 'status_conversion_logs', []);
        if (!is_array($history)) {
            $history = [];
        }
        $history[] = $logEntry;
        $metadata['status_conversion_logs'] = $history;
        $metadata['last_status_conversion'] = $logEntry;

        DB::transaction(function () use ($transaction, $metadata, $previousStatus, $newStatus, $request, $changedAt): void {
            $transaction->status = $newStatus;
            $transaction->metadata = $metadata;
            $transaction->save();

            if (Schema::hasTable('transaction_status_conversion_logs')) {
                DB::table('transaction_status_conversion_logs')->insert([
                    'transaction_id' => $transaction->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'changed_by_user_id' => optional($request->user())->id,
                    'changed_by_name' => optional($request->user())->name,
                    'source' => 'admin.transactions.excell-export',
                    'created_at' => $changedAt,
                    'updated_at' => $changedAt,
                ]);
            }
        });

        return redirect()
            ->route('admin.transactions.excell-export')
            ->with('status', "Transaction #{$transaction->id} updated from {$previousStatus} to {$newStatus}.");
    }

    public function deleteExcellExportTransaction(Transaction $transaction): RedirectResponse
    {
        if (! $this->isExcellExportTransaction($transaction)) {
            return redirect()
                ->route('admin.transactions.excell-export')
                ->withErrors(['transaction' => 'Only Excel export campaign transactions can be deleted from this page.']);
        }

        $transactionId = (int) $transaction->id;
        $transaction->delete();

        return redirect()
            ->route('admin.transactions.excell-export')
            ->with('status', "Transaction #{$transactionId} deleted.");
    }

    private function attachLatestPrescriptionIds(Collection $transactions): Collection
    {
        if ($transactions->isEmpty()) {
            return $transactions;
        }

        $latestPrescriptionIdByPetId = collect();

        if (Schema::hasTable('prescriptions') && Schema::hasColumn('prescriptions', 'pet_id')) {
            $petIds = $transactions->pluck('pet_id')
                ->filter(fn ($petId) => is_numeric($petId) && (int) $petId > 0)
                ->map(fn ($petId) => (int) $petId)
                ->unique()
                ->values();

            if ($petIds->isNotEmpty()) {
                $latestPrescriptionIdByPetId = Prescription::query()
                    ->whereIn('pet_id', $petIds->all())
                    ->selectRaw('MAX(id) as id, pet_id')
                    ->groupBy('pet_id')
                    ->pluck('id', 'pet_id');
            }
        }

        $transactions->each(function (Transaction $transaction) use ($latestPrescriptionIdByPetId): void {
            $prescriptionId = data_get($transaction->metadata, 'notes.prescription_id')
                ?? data_get($transaction->metadata, 'prescription_id');

            if (!is_numeric($prescriptionId)) {
                $petId = is_numeric($transaction->pet_id) ? (int) $transaction->pet_id : null;
                $prescriptionId = $petId ? $latestPrescriptionIdByPetId->get($petId) : null;
            }

            $transaction->setAttribute(
                'prescription_id',
                is_numeric($prescriptionId) ? (int) $prescriptionId : null
            );
        });

        return $transactions;
    }

    private function excellExportTransactionsQuery(): Builder
    {
        return Transaction::query()
            ->where('type', 'excell_export_campaign')
            ->whereHas('clinic') // skip rows whose clinic entry was deleted
            ->with([
                'clinic:id,name',
                'doctor:id,doctor_name,doctor_email,doctor_mobile',
                'user' => function ($query) {
                    $query->with([
                        'pets' => function ($petQuery) {
                            $petQuery->orderByDesc('id');
                        },
                    ]);
                },
                'pet',
            ])
            ->orderByDesc('created_at');
    }

    private function appendExcellExportInvoiceFlags(Collection $transactions): Collection
    {
        return $transactions->each(function (Transaction $transaction): void {
            $amountInRupees = $this->resolveExcellExportAmountInRupees($transaction);
            $statusEligible = $this->isExcellExportInvoiceStatusEligible($transaction);
            $transaction->setAttribute('invoice_amount_inr', $amountInRupees);
            $transaction->setAttribute('invoice_amount_eligible', true);
            $transaction->setAttribute('invoice_status_eligible', $statusEligible);
            $transaction->setAttribute(
                'invoice_eligible',
                $statusEligible
            );
        });
    }

    private function resolveExcellExportAmountInRupees(Transaction $transaction): int
    {
        $amountPaise = null;

        if (is_numeric($transaction->amount_paise ?? null) && (int) $transaction->amount_paise > 0) {
            $amountPaise = (int) $transaction->amount_paise;
        } elseif (is_numeric($transaction->actual_amount_paid_by_consumer_paise ?? null) && (int) $transaction->actual_amount_paid_by_consumer_paise > 0) {
            $amountPaise = (int) $transaction->actual_amount_paid_by_consumer_paise;
        } else {
            $rawAmount = $transaction->getAttribute('amount');
            if (is_numeric($rawAmount)) {
                $numericAmount = (float) $rawAmount;
                if ($numericAmount > 1000) {
                    $amountPaise = (int) round($numericAmount);
                } else {
                    return max((int) round($numericAmount), 0);
                }
            }
        }

        if (! is_numeric($amountPaise) || (int) $amountPaise <= 0) {
            return 0;
        }

        return max((int) round(((int) $amountPaise) / 100), 0);
    }

    private function isExcellExportInvoiceStatusEligible(Transaction $transaction): bool
    {
        return strtolower(trim((string) ($transaction->status ?? ''))) === 'captured';
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

    private function transactionStatusConversionLogs(Collection $transactions): Collection
    {
        if (!Schema::hasTable('transaction_status_conversion_logs') || $transactions->isEmpty()) {
            return collect();
        }

        $transactionIds = $transactions->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($transactionIds->isEmpty()) {
            return collect();
        }

        return DB::table('transaction_status_conversion_logs')
            ->whereIn('transaction_id', $transactionIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('transaction_id');
    }

    private function formatNonNullAttributesForCsv($model, array $exclude = []): array
    {
        if (!$model) {
            return [];
        }

        $excluded = array_flip($exclude);
        $result = [];

        foreach ($model->getAttributes() as $key => $value) {
            if (isset($excluded[$key])) {
                continue;
            }
            if ($value === null) {
                continue;
            }
            if (is_string($value) && trim($value) === '') {
                continue;
            }
            if (is_array($value) && empty($value)) {
                continue;
            }

            if (is_bool($value)) {
                $result[$key] = $value ? 'Yes' : 'No';
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $result[$key] = $json !== false ? $json : (string) $value;
                continue;
            }

            $result[$key] = (string) $value;
        }

        return $result;
    }

    private function streamExcellExportTransactionsCsv(Collection $transactions)
    {
        $fileName = 'excel-export-transactions-' . now()->format('Ymd-His') . '.csv';
        $baseHeaders = [
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
            'User City',
            'Pet ID',
            'Pet Name',
            'Pet Type',
            'Pet Breed',
            'Pet DOB',
            'Reported Symptom',
        ];

        $userFieldExclude = [
            'password',
            'remember_token',
            'api_token_hash',
            'google_token',
            'pet_doc2_blob',
            'pet_doc2_mime',
        ];
        $petFieldExclude = ['pet_doc2_blob'];

        $userFieldKeysMap = [];
        $petFieldKeysMap = [];

        foreach ($transactions as $transaction) {
            $petRecord = $this->resolveExcellExportPetRecord($transaction);
            $userDetails = $this->formatNonNullAttributesForCsv($transaction->user, $userFieldExclude);
            $petDetails = $this->formatNonNullAttributesForCsv($petRecord, $petFieldExclude);

            foreach (array_keys($userDetails) as $key) {
                $userFieldKeysMap[$key] = true;
            }
            foreach (array_keys($petDetails) as $key) {
                $petFieldKeysMap[$key] = true;
            }
        }

        $userFieldKeys = array_keys($userFieldKeysMap);
        $petFieldKeys = array_keys($petFieldKeysMap);
        sort($userFieldKeys);
        sort($petFieldKeys);

        $headers = array_merge(
            $baseHeaders,
            array_map(fn (string $key): string => "User Details: {$key}", $userFieldKeys),
            array_map(fn (string $key): string => "Pet Details: {$key}", $petFieldKeys),
        );

        return response()->streamDownload(function () use ($transactions, $headers, $userFieldKeys, $petFieldKeys, $userFieldExclude, $petFieldExclude) {
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
                $userDetails = $this->formatNonNullAttributesForCsv($transaction->user, $userFieldExclude);
                $petDetails = $this->formatNonNullAttributesForCsv($petRecord, $petFieldExclude);

                $row = [
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
                    $transaction->user->city ?? null,
                    $petRecord->id ?? $transaction->pet_id,
                    $petRecord->name ?? null,
                    $petRecord->pet_type ?? $petRecord->type ?? $petRecord->breed ?? null,
                    $petRecord->breed ?? null,
                    $petDob,
                    $issue !== '' ? $issue : null,
                ];

                foreach ($userFieldKeys as $key) {
                    $row[] = $userDetails[$key] ?? null;
                }
                foreach ($petFieldKeys as $key) {
                    $row[] = $petDetails[$key] ?? null;
                }

                fputcsv($output, $row);
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
                    $query->select('id', 'name', 'email', 'phone', 'city')
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

    public function updateAppointmentTransactionDoctor(Request $request, Transaction $transaction): RedirectResponse|JsonResponse
    {
        $allowsLeadManagementReassignment = $request->expectsJson()
            && is_numeric($transaction->user_id ?? null)
            && (int) $transaction->user_id > 0;

        if (! $this->isAppointmentTransaction($transaction) && ! $allowsLeadManagementReassignment) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only appointment transactions or lead-linked transactions can be reassigned from this page.',
                ], 422);
            }

            return redirect()
                ->route('admin.transactions.appointments')
                ->withErrors(['doctor_id' => 'Only video consultation appointment transactions can be reassigned from this page.']);
        }

        $data = $request->validate([
            'doctor_id' => ['required', 'integer'],
        ]);

        $doctor = Doctor::query()
            ->with('clinic:id,name')
            ->select('id', 'vet_registeration_id', 'doctor_name')
            ->where('exported_from_excell', 1)
            ->find((int) $data['doctor_id']);

        if (! $doctor) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please select a valid Excel-export doctor (exported_from_excell = 1).',
                ], 422);
            }

            return redirect()
                ->route('admin.transactions.appointments')
                ->withErrors(['doctor_id' => 'Please select a valid Excel-export doctor (exported_from_excell = 1).']);
        }

        $previousDoctorId = $transaction->doctor_id ? (int) $transaction->doctor_id : null;
        $previousClinicId = $transaction->clinic_id ? (int) $transaction->clinic_id : null;
        $nextDoctorId = (int) $doctor->id;
        $nextClinicId = $doctor->vet_registeration_id ? (int) $doctor->vet_registeration_id : null;

        if ($previousDoctorId === $nextDoctorId && $previousClinicId === $nextClinicId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => sprintf(
                        'No change for transaction #%d. Doctor already assigned: %s (ID: %d).',
                        $transaction->id,
                        $doctor->doctor_name ?? 'N/A',
                        $doctor->id
                    ),
                    'transaction' => [
                        'id' => (int) $transaction->id,
                        'doctor_id' => $nextDoctorId,
                        'doctor_name' => $doctor->doctor_name,
                        'clinic_id' => $nextClinicId,
                        'clinic_name' => $doctor->clinic?->name,
                    ],
                ]);
            }

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

        $whatsAppMeta = null;
        if ($this->isExcellExportTransaction($transaction)) {
            try {
                $whatsAppMeta = $this->consultationBookingWhatsAppService
                    ->sendExcelExportAssignmentNotifications($transaction);
            } catch (\Throwable $e) {
                report($e);
                Log::warning('admin.transaction.doctor_assignment.whatsapp_failed', [
                    'transaction_id' => $transaction->id,
                    'doctor_id' => $nextDoctorId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $statusMessage = sprintf(
            'Doctor/clinic updated for transaction #%d. Previous Doctor ID: %s, Assigned: %s (ID: %d), Clinic ID: %s.',
            $transaction->id,
            $previousDoctorId ?? 'NULL',
            $doctor->doctor_name ?? 'N/A',
            $doctor->id,
            $transaction->clinic_id ?? 'NULL'
        );

        $whatsAppMessage = $this->doctorAssignmentWhatsAppStatusMessage($whatsAppMeta);
        if ($whatsAppMessage !== '') {
            $statusMessage .= ' ' . $whatsAppMessage;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $statusMessage,
                'transaction' => [
                    'id' => (int) $transaction->id,
                    'doctor_id' => $nextDoctorId,
                    'doctor_name' => $doctor->doctor_name,
                    'clinic_id' => $nextClinicId,
                    'clinic_name' => $doctor->clinic?->name,
                ],
                'notifications' => $whatsAppMeta,
            ]);
        }

        return redirect()
            ->route('admin.transactions.appointments')
            ->with('status', $statusMessage);
    }

    public function deleteAppointmentTransaction(Transaction $transaction): RedirectResponse
    {
        if (! $this->isAppointmentTransaction($transaction)) {
            return redirect()
                ->route('admin.transactions.appointments')
                ->withErrors(['transaction' => 'Only appointment transactions can be deleted from this page.']);
        }

        $transactionId = (int) $transaction->id;
        $transaction->delete();

        return redirect()
            ->route('admin.transactions.appointments')
            ->with('status', "Transaction #{$transactionId} deleted.");
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

    private function isExcellExportTransaction(Transaction $transaction): bool
    {
        $type = $this->normalizeAppointmentTransactionType($transaction->type ?? null);
        $orderType = $this->normalizeAppointmentTransactionType(data_get($transaction->metadata, 'order_type'));

        return $type === 'excell_export_campaign' || $orderType === 'excell_export_campaign';
    }

    private function isAppointmentTransaction(Transaction $transaction): bool
    {
        $type = $this->normalizeAppointmentTransactionType($transaction->type ?? null);
        $orderType = $this->normalizeAppointmentTransactionType(data_get($transaction->metadata, 'order_type'));

        return in_array($type, ['video_consult', 'excell_export_campaign'], true)
            || in_array($orderType, ['video_consult', 'excell_export_campaign'], true);
    }

    private function doctorAssignmentWhatsAppStatusMessage(?array $whatsAppMeta): string
    {
        if (! is_array($whatsAppMeta)) {
            return '';
        }

        $parentSent = (bool) data_get($whatsAppMeta, 'parent_whatsapp.sent', false);
        $vetSent = (bool) data_get($whatsAppMeta, 'vet_whatsapp.sent', false);

        if ($parentSent && $vetSent) {
            return 'WhatsApp sent to the pet parent and assigned doctor.';
        }

        if ($parentSent) {
            return 'WhatsApp sent to the pet parent.';
        }

        if ($vetSent) {
            return 'WhatsApp sent to the assigned doctor.';
        }

        $parentReason = trim((string) data_get($whatsAppMeta, 'parent_whatsapp.reason', ''));
        $vetReason = trim((string) data_get($whatsAppMeta, 'vet_whatsapp.reason', ''));
        $parts = [];

        if ($parentReason !== '') {
            $parts[] = 'pet parent: ' . str_replace('_', ' ', $parentReason);
        }

        if ($vetReason !== '') {
            $parts[] = 'doctor: ' . str_replace('_', ' ', $vetReason);
        }

        if (empty($parts)) {
            return '';
        }

        return 'WhatsApp not sent (' . implode('; ', $parts) . ').';
    }

    private function normalizeAppointmentTransactionType(?string $type): string
    {
        if (! is_string($type)) {
            return '';
        }

        $normalized = strtolower(trim(str_replace(['-', ' '], '_', $type)));

        return match ($normalized) {
            'excel_export_campaign' => 'excell_export_campaign',
            default => $normalized,
        };
    }

    private function addJoinedSelectIfColumnExists(
        Builder $query,
        string $tableName,
        string $tableAlias,
        string $column,
        string $asAlias
    ): void {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $column)) {
            return;
        }

        $query->addSelect(DB::raw("{$tableAlias}.{$column} as {$asAlias}"));
    }

    private function notificationLookupsForTransactions(Collection $transactions): array
    {
        $lookup = [
            'whatsapp_sent_by_channel' => [],
            'whatsapp_feedback_by_channel' => [],
            'whatsapp_last_attempt_by_channel' => [],
            'whatsapp_rows_by_channel' => [],
            'fcm_by_user' => [],
        ];

        if ($transactions->isEmpty()) {
            return $lookup;
        }

        $channels = $transactions
            ->pluck('channel_name')
            ->filter(fn ($channel) => is_string($channel) && trim($channel) !== '')
            ->map(fn (string $channel) => trim($channel))
            ->unique()
            ->values();
        $normalizedChannels = $channels
            ->map(fn (string $channel) => strtolower($channel))
            ->unique()
            ->values();

        if (
            $channels->isNotEmpty()
            && Schema::hasTable('whatsapp_notifications')
            && Schema::hasColumn('whatsapp_notifications', 'channel_name')
        ) {
            $query = DB::table('whatsapp_notifications')
                ->where(function ($inner) use ($channels, $normalizedChannels) {
                    $inner->whereIn('channel_name', $channels->all());
                    if ($normalizedChannels->isNotEmpty()) {
                        $inner->orWhereIn(DB::raw('LOWER(channel_name)'), $normalizedChannels->all());
                    }
                })
                ->select(['id', 'channel_name']);

            if (Schema::hasColumn('whatsapp_notifications', 'status')) {
                $query->addSelect('status');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'template_name')) {
                $query->addSelect('template_name');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'message_type')) {
                $query->addSelect('message_type');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'recipient')) {
                $query->addSelect('recipient');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'http_status')) {
                $query->addSelect('http_status');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'provider_message_id')) {
                $query->addSelect('provider_message_id');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'error_message')) {
                $query->addSelect('error_message');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'sent_at')) {
                $query->addSelect('sent_at');
            }
            if (Schema::hasColumn('whatsapp_notifications', 'created_at')) {
                $query->addSelect('created_at');
            }

            $rows = $query
                ->orderByDesc('id')
                ->get();

            foreach ($rows as $row) {
                $channel = trim((string) ($row->channel_name ?? ''));
                $channelKey = $this->normalizedLifecycleChannelKey($channel);
                if ($channelKey === null) {
                    continue;
                }

                $rawStatus = strtolower(trim((string) (data_get($row, 'status') ?? '')));
                $template = strtolower(trim((string) (data_get($row, 'template_name') ?? '')));
                $attemptedAt = $this->latestLifecycleTimestamp([
                    data_get($row, 'sent_at'),
                    data_get($row, 'created_at'),
                ]);
                $isSent = $rawStatus === 'sent'
                    || ($rawStatus === '' && $this->normalizeLifecycleTimestamp(data_get($row, 'sent_at')) !== null);
                $status = $rawStatus !== '' ? $rawStatus : ($isSent ? 'sent' : 'unknown');

                $entry = [
                    'id' => (int) ($row->id ?? 0),
                    'status' => $status !== '' ? $status : 'unknown',
                    'template_name' => data_get($row, 'template_name'),
                    'message_type' => data_get($row, 'message_type'),
                    'recipient' => data_get($row, 'recipient'),
                    'http_status' => data_get($row, 'http_status'),
                    'provider_message_id' => data_get($row, 'provider_message_id'),
                    'error_message' => data_get($row, 'error_message'),
                    'sent_at' => $this->normalizeLifecycleTimestamp(data_get($row, 'sent_at')),
                    'created_at' => $this->normalizeLifecycleTimestamp(data_get($row, 'created_at')),
                    'attempted_at' => $attemptedAt,
                ];

                $lookup['whatsapp_rows_by_channel'][$channelKey][] = $entry;

                $lookup['whatsapp_last_attempt_by_channel'][$channelKey] = $this->latestLifecycleTimestamp([
                    $lookup['whatsapp_last_attempt_by_channel'][$channelKey] ?? null,
                    $attemptedAt,
                ]);

                if ($isSent) {
                    $lookup['whatsapp_sent_by_channel'][$channelKey] = $this->latestLifecycleTimestamp([
                        $lookup['whatsapp_sent_by_channel'][$channelKey] ?? null,
                        $attemptedAt,
                    ]);
                }

                if ($isSent && ($template === 'pp_consultation_feedback' || str_contains($template, 'feedback'))) {
                    $lookup['whatsapp_feedback_by_channel'][$channelKey] = $this->latestLifecycleTimestamp([
                        $lookup['whatsapp_feedback_by_channel'][$channelKey] ?? null,
                        $attemptedAt,
                    ]);
                }
            }

            foreach ($lookup['whatsapp_rows_by_channel'] as $channel => $channelRows) {
                usort($channelRows, function (array $a, array $b): int {
                    $aTs = strtotime((string) ($a['attempted_at'] ?? '1970-01-01 00:00:00'));
                    $bTs = strtotime((string) ($b['attempted_at'] ?? '1970-01-01 00:00:00'));

                    if ($aTs === $bTs) {
                        return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
                    }

                    return $bTs <=> $aTs;
                });
                $lookup['whatsapp_rows_by_channel'][$channel] = array_values($channelRows);
            }
        }

        if (Schema::hasTable('fcm_notifications') && Schema::hasColumn('fcm_notifications', 'user_id')) {
            $userIds = $transactions->pluck('user_id')
                ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($userIds->isNotEmpty()) {
                $fcmQuery = DB::table('fcm_notifications')
                    ->whereIn('user_id', $userIds->all())
                    ->select(['user_id']);

                if (Schema::hasColumn('fcm_notifications', 'status')) {
                    $fcmQuery->where('status', 'sent');
                    $fcmQuery->addSelect('status');
                }

                $fcmTimestampColumns = [];
                if (Schema::hasColumn('fcm_notifications', 'sent_at')) {
                    $fcmTimestampColumns[] = 'sent_at';
                    $fcmQuery->addSelect('sent_at');
                }
                if (Schema::hasColumn('fcm_notifications', 'created_at')) {
                    $fcmTimestampColumns[] = 'created_at';
                    $fcmQuery->addSelect('created_at');
                }

                $fcmRows = $fcmQuery->get();
                foreach ($fcmRows as $row) {
                    $userId = (int) ($row->user_id ?? 0);
                    if ($userId <= 0) {
                        continue;
                    }

                    $timeCandidate = null;
                    foreach ($fcmTimestampColumns as $column) {
                        $timeCandidate = $this->latestLifecycleTimestamp([$timeCandidate, data_get($row, $column)]);
                    }
                    if (!$timeCandidate) {
                        continue;
                    }

                    $lookup['fcm_by_user'][$userId] = $this->latestLifecycleTimestamp([
                        $lookup['fcm_by_user'][$userId] ?? null,
                        $timeCandidate,
                    ]);
                }
            }
        }

        return $lookup;
    }

    private function feedbackSubmissionLookupForTransactions(Collection $transactions): array
    {
        $lookup = [
            'doctor_feedback_by_channel' => [],
            'pet_feedback_by_channel' => [],
            'doctor_feedback_exists_by_channel' => [],
            'pet_feedback_exists_by_channel' => [],
        ];

        if ($transactions->isEmpty()) {
            return $lookup;
        }

        $channels = $transactions->pluck('channel_name')
            ->filter(fn ($channel) => is_string($channel) && trim($channel) !== '')
            ->map(fn (string $channel) => trim($channel))
            ->unique()
            ->values();
        $normalizedChannels = $channels
            ->map(fn (string $channel) => strtolower($channel))
            ->unique()
            ->values();

        if ($channels->isEmpty()) {
            return $lookup;
        }

        $tableMap = [
            'vet_feedback' => [
                'timestamp_lookup' => 'doctor_feedback_by_channel',
                'exists_lookup' => 'doctor_feedback_exists_by_channel',
            ],
            'pet_feedback' => [
                'timestamp_lookup' => 'pet_feedback_by_channel',
                'exists_lookup' => 'pet_feedback_exists_by_channel',
            ],
        ];

        foreach ($tableMap as $table => $lookupKeys) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'channel_name')) {
                continue;
            }

            $timestampLookupKey = (string) data_get($lookupKeys, 'timestamp_lookup');
            $existsLookupKey = (string) data_get($lookupKeys, 'exists_lookup');
            if ($timestampLookupKey === '' || $existsLookupKey === '') {
                continue;
            }

            $hasMetaColumn = Schema::hasColumn($table, 'meta');
            $dbDriver = DB::connection()->getDriverName();
            $supportsJsonExtract = in_array($dbDriver, ['mysql', 'mariadb'], true);

            $timestampColumns = collect(['submitted_at', 'created_at', 'updated_at'])
                ->filter(fn (string $column) => Schema::hasColumn($table, $column))
                ->values()
                ->all();

            if (empty($timestampColumns)) {
                continue;
            }

            $query = DB::table($table)
                ->where(function ($inner) use ($channels, $normalizedChannels, $hasMetaColumn, $supportsJsonExtract) {
                    $inner->whereIn('channel_name', $channels->all());
                    if ($normalizedChannels->isNotEmpty()) {
                        $inner->orWhereIn(DB::raw('LOWER(channel_name)'), $normalizedChannels->all());
                    }
                    if ($hasMetaColumn && $supportsJsonExtract) {
                        $inner->orWhereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channel_name'))"), $channels->all());
                        $inner->orWhereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channelName'))"), $channels->all());
                        if ($normalizedChannels->isNotEmpty()) {
                            $inner->orWhereIn(DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channel_name')))"), $normalizedChannels->all());
                            $inner->orWhereIn(DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.channelName')))"), $normalizedChannels->all());
                        }
                    }
                })
                ->select(['channel_name']);

            if ($hasMetaColumn) {
                $query->addSelect('meta');
            }

            foreach ($timestampColumns as $timestampColumn) {
                $query->addSelect($timestampColumn);
            }

            $rows = $query->get();
            foreach ($rows as $row) {
                $rowMeta = data_get($row, 'meta');
                if (is_string($rowMeta)) {
                    $decodedMeta = json_decode($rowMeta, true);
                    $rowMeta = is_array($decodedMeta) ? $decodedMeta : [];
                }
                if (!is_array($rowMeta)) {
                    $rowMeta = [];
                }

                $channelName = trim((string) (
                    $row->channel_name
                    ?? data_get($rowMeta, 'channel_name')
                    ?? data_get($rowMeta, 'channelName')
                    ?? ''
                ));
                $channelKey = $this->normalizedLifecycleChannelKey($channelName);
                if ($channelKey === null) {
                    continue;
                }
                $lookup[$existsLookupKey][$channelKey] = true;

                $timeCandidate = null;
                foreach ($timestampColumns as $timestampColumn) {
                    $timeCandidate = $this->latestLifecycleTimestamp([
                        $timeCandidate,
                        data_get($row, $timestampColumn),
                    ]);
                }
                if (!$timeCandidate) {
                    continue;
                }

                $lookup[$timestampLookupKey][$channelKey] = $this->latestLifecycleTimestamp([
                    $lookup[$timestampLookupKey][$channelKey] ?? null,
                    $timeCandidate,
                ]);
            }
        }

        return $lookup;
    }

    private function joinedAttributesForPrefix(Transaction $transaction, string $prefix): array
    {
        $details = [];
        $prefixLength = strlen($prefix);

        foreach ($transaction->getAttributes() as $key => $value) {
            if (!str_starts_with($key, $prefix)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }

            $normalizedKey = substr($key, $prefixLength);
            if ($normalizedKey === false || $normalizedKey === '') {
                continue;
            }

            if (is_bool($value)) {
                $details[$normalizedKey] = $value ? true : false;
                continue;
            }

            if (is_numeric($value)) {
                $details[$normalizedKey] = $value + 0;
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $details[$normalizedKey] = $value;
                continue;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $details[$normalizedKey] = $decoded;
                    continue;
                }
            }

            $details[$normalizedKey] = (string) $value;
        }

        ksort($details);

        return $details;
    }

    private function normalizePhoneForLookup(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }

        return $digits;
    }

    private function phoneLookupCandidates(string $phone): array
    {
        $raw = trim($phone);
        $digits = preg_replace('/\D+/', '', $raw);
        if (!is_string($digits)) {
            $digits = '';
        }

        $normalized = $this->normalizePhoneForLookup($raw);
        $candidates = collect([$raw, $digits]);

        if ($normalized) {
            $candidates->push($normalized);
            $candidates->push('+91' . $normalized);
            $candidates->push('91' . $normalized);
            $candidates->push('0' . $normalized);
        }

        return $candidates
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizedLifecycleChannelKey(mixed $channel): ?string
    {
        if (!is_string($channel)) {
            return null;
        }

        $normalized = trim($channel);
        if ($normalized === '') {
            return null;
        }

        return strtolower($normalized);
    }

    private function normalizeLifecycleTimestamp(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return \Illuminate\Support\Carbon::instance($value)->toDateTimeString();
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeLifecycleTimestampOnOrAfter(mixed $value, mixed $baseline): ?string
    {
        $normalized = $this->normalizeLifecycleTimestamp($value);
        if (!$normalized) {
            return null;
        }

        $baselineNormalized = $this->normalizeLifecycleTimestamp($baseline);
        if (!$baselineNormalized) {
            return $normalized;
        }

        return strtotime($normalized) >= strtotime($baselineNormalized)
            ? $normalized
            : null;
    }

    private function firstLifecycleTimestamp(array $values): ?string
    {
        foreach ($values as $value) {
            $normalized = $this->normalizeLifecycleTimestamp($value);
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function latestLifecycleTimestamp(array $values): ?string
    {
        $latest = null;
        $latestTs = null;

        foreach ($values as $value) {
            $normalized = $this->normalizeLifecycleTimestamp($value);
            if (!$normalized) {
                continue;
            }

            $ts = strtotime($normalized);
            if ($latestTs === null || $ts > $latestTs) {
                $latest = $normalized;
                $latestTs = $ts;
            }
        }

        return $latest;
    }

    private function latestLifecycleTimestampOnOrAfter(array $values, mixed $baseline): ?string
    {
        $normalizedBaseline = $this->normalizeLifecycleTimestamp($baseline);
        $baselineTs = $normalizedBaseline ? strtotime($normalizedBaseline) : null;

        $latest = null;
        $latestTs = null;

        foreach ($values as $value) {
            $normalized = $this->normalizeLifecycleTimestamp($value);
            if (!$normalized) {
                continue;
            }

            $ts = strtotime($normalized);
            if ($baselineTs !== null && $ts < $baselineTs) {
                continue;
            }

            if ($latestTs === null || $ts > $latestTs) {
                $latest = $normalized;
                $latestTs = $ts;
            }
        }

        return $latest;
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
