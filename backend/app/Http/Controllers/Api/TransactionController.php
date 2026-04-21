<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Models\DeviceToken;
use App\Models\Prescription;
use App\Models\Transaction;
use App\Models\VideoApointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => 'required|integer',
            'date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 50);
        $date = (string) ($data['date'] ?? now()->toDateString());
        $petColumns = ['id', 'name'];
        if (Schema::hasTable('pets')) {
            if (Schema::hasColumn('pets', 'pet_doc1')) {
                $petColumns[] = 'pet_doc1';
            }
            if (Schema::hasColumn('pets', 'pet_doc2')) {
                $petColumns[] = 'pet_doc2';
            }
            if (Schema::hasColumn('pets', 'pet_doc2_blob')) {
                $petColumns[] = 'pet_doc2_blob';
            }
            if (Schema::hasColumn('pets', 'pet_doc2_mime')) {
                $petColumns[] = 'pet_doc2_mime';
            }
            if (Schema::hasColumn('pets', 'pic_link')) {
                $petColumns[] = 'pic_link';
            }
            if (Schema::hasColumn('pets', 'breed')) {
                $petColumns[] = 'breed';
            }
            if (Schema::hasColumn('pets', 'pet_age')) {
                $petColumns[] = 'pet_age';
            }
            if (Schema::hasColumn('pets', 'pet_gender')) {
                $petColumns[] = 'pet_gender';
            }
            if (Schema::hasColumn('pets', 'gender')) {
                $petColumns[] = 'gender';
            }
            if (Schema::hasColumn('pets', 'weight')) {
                $petColumns[] = 'weight';
            }
        }

        $supportsCallsJoin = Schema::hasTable('transactions')
            && Schema::hasColumn('transactions', 'channel_name')
            && Schema::hasTable('calls')
            && Schema::hasColumn('calls', 'channel_name');

        $transactionsQuery = Transaction::query()
            ->select('transactions.*')
            ->whereIn('transactions.type', ['video_consult', 'video_call', 'video call', 'appointment', 'appointments', 'continuety_subscription'])
            ->where('transactions.doctor_id', $data['doctor_id'])
            ->whereDate('transactions.created_at', $date)
            ->where(function ($query) {
                $query->whereNull('transactions.status')
                    ->orWhere('transactions.status', '!=', 'pending');
            })
            ->with([
                'user' => fn ($q) => $q->select('id', 'name'),
                'user.deviceTokens:id,user_id,token',
                'pet' => fn ($q) => $q->select($petColumns),
                'doctor:id,doctor_name',
            ]);

        if ($supportsCallsJoin) {
            $latestCallsByChannel = DB::table('calls as c')
                ->selectRaw('MAX(c.id) as latest_call_id, c.channel_name, c.doctor_id')
                ->whereNotNull('c.channel_name')
                ->where('c.channel_name', '!=', '')
                ->groupBy('c.channel_name', 'c.doctor_id');

            $transactionsQuery
                ->leftJoinSub($latestCallsByChannel, 'latest_call_by_channel', function ($join) {
                    $join->on('latest_call_by_channel.channel_name', '=', 'transactions.channel_name')
                        ->on('latest_call_by_channel.doctor_id', '=', 'transactions.doctor_id');
                })
                ->leftJoin('calls as joined_call', 'joined_call.id', '=', 'latest_call_by_channel.latest_call_id')
                ->addSelect([
                    'joined_call.id as joined_call_id',
                    'joined_call.status as joined_call_status',
                    'joined_call.channel_name as joined_call_channel_name',
                    'joined_call.channel as joined_call_channel',
                    'joined_call.accepted_at as joined_call_accepted_at',
                    'joined_call.rejected_at as joined_call_rejected_at',
                    'joined_call.ended_at as joined_call_ended_at',
                    'joined_call.cancelled_at as joined_call_cancelled_at',
                    'joined_call.missed_at as joined_call_missed_at',
                ]);
        }

        $transactions = $transactionsQuery
            ->orderByDesc('transactions.id')
            ->limit($limit)
            ->get();

        $prescriptionChannelSet = $this->prescriptionChannelSetForTransactions($transactions);

        $latestSessions = $this->latestCallSessionsForTransactionChannels(
            doctorId: (int) $data['doctor_id'],
            channels: $transactions
                ->pluck('channel_name')
                ->filter(fn ($channel) => is_string($channel) && trim($channel) !== '')
                ->map(fn (string $channel) => trim($channel))
                ->unique()
        );
        $latestVideoApointments = $this->latestVideoApointmentsForUsers(
            doctorId: (int) $data['doctor_id'],
            userIds: $transactions->pluck('user_id')->filter()->unique()
        );

        $deviceTokensByUser = $this->deviceTokensForUsers($transactions, $latestSessions);

        $payload = $transactions->map(function (Transaction $tx) use ($latestSessions, $latestVideoApointments, $deviceTokensByUser, $prescriptionChannelSet) {
            $user = $tx->user;
            $pet = $tx->pet;
            $transactionChannel = is_string($tx->channel_name ?? null) ? trim((string) $tx->channel_name) : '';
            $callSession = $transactionChannel !== '' ? $latestSessions->get($transactionChannel) : null;
            $videoApointment = $latestVideoApointments->get((int) $tx->user_id);
            $deviceTokens = $deviceTokensByUser->get((int) $tx->user_id)
                ?: ($callSession ? $deviceTokensByUser->get((int) $callSession->patient_id, []) : []);

            // Fallback to eager-loaded relationship if the direct lookup missed
            if (empty($deviceTokens) && $user) {
                $deviceTokens = $user->deviceTokens
                    ? $user->deviceTokens->pluck('token')->filter()->unique()->values()->all()
                    : [];
            }

            $petBlobUrl = $pet ? $this->petDoc2BlobUrl($pet) : null;
            $petDoc1Url = $pet ? $this->absolutePetDoc2Url($pet->pet_doc1 ?? null) : null;
            $petDoc2Url = $pet ? $this->absolutePetDoc2Url($pet->pet_doc2 ?? null) : null;
            $petPicLinkUrl = $pet ? $this->absolutePetDoc2Url($pet->pic_link ?? null) : null;
            $petImageUrl = $petBlobUrl ?: $petDoc1Url ?: $petDoc2Url ?: $petPicLinkUrl;
            $requiresPrescription = $this->transactionRequiresPrescription((string) ($tx->type ?? ''));
            $hasPrescription = $requiresPrescription
                ? $this->hasMatchingPrescriptionForTransaction($tx, $prescriptionChannelSet)
                : true;

            return [
                'id' => $tx->id,
                'user_id' => $tx->user_id,
                'doctor_id' => $tx->doctor_id,
                'amount_paise' => (int) ($tx->amount_paise ?? 0),
                'actual_amount_paid_by_consumer_paise' => $tx->actual_amount_paid_by_consumer_paise !== null
                    ? (int) $tx->actual_amount_paid_by_consumer_paise
                    : null,
                'payment_to_snoutiq_paise' => $tx->payment_to_snoutiq_paise !== null
                    ? (int) $tx->payment_to_snoutiq_paise
                    : null,
                'payment_to_doctor_paise' => $tx->payment_to_doctor_paise !== null
                    ? (int) $tx->payment_to_doctor_paise
                    : null,
                'status' => $tx->status,
                'type' => $tx->type,
                'payment_method' => $tx->payment_method,
                'reference' => $tx->reference,
                'prescription_send' => $hasPrescription,
                'created_at' => optional($tx->created_at)->toIso8601String(),
                'updated_at' => optional($tx->updated_at)->toIso8601String(),
                'user_name' => $user->name ?? null,
                'doctor_name' => $tx->doctor->doctor_name ?? null,
                'device_tokens' => $deviceTokens,
                'pet_doc2_blob_url' => $petBlobUrl,
                'pet_image_url' => $petImageUrl,
                'pet' => $pet ? [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'gender' => $pet->pet_gender ?? $pet->gender ?? null,
                    'breed' => $pet->breed ?? null,
                    'age' => $pet->pet_age ?? null,
                    'weight' => $pet->weight ?? null,
                    'pet_doc2' => $pet->pet_doc2 ?? null,
                    'pet_doc2_blob_url' => $petBlobUrl,
                    'pet_doc2_url' => $petDoc2Url,
                    'pet_image_url' => $petImageUrl,
                ] : null,
                'call' => $this->formatJoinedCall($tx),
                'call_session' => $callSession ? $this->formatCallSession($callSession) : null,
                'call_session_is_completed' => $callSession ? (bool) ($callSession->is_completed ?? false) : null,
                'video_appointment' => $videoApointment ? $this->formatVideoApointment($videoApointment) : null,
                'video_appointment_is_completed' => $videoApointment ? (bool) ($videoApointment->is_completed ?? false) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $payload->count(),
            'data' => $payload,
        ]);
    }

    protected function prescriptionChannelSetForTransactions(Collection $transactions): Collection
    {
        if (
            $transactions->isEmpty()
            || !Schema::hasTable('prescriptions')
            || !Schema::hasColumn('prescriptions', 'call_session')
            || !Schema::hasTable('transactions')
            || !Schema::hasColumn('transactions', 'channel_name')
        ) {
            return collect();
        }

        $channels = $transactions
            ->filter(fn (Transaction $tx) => $this->transactionRequiresPrescription((string) ($tx->type ?? '')))
            ->pluck('channel_name')
            ->filter(fn ($channel) => is_string($channel) && trim($channel) !== '')
            ->map(fn (string $channel) => trim($channel))
            ->unique()
            ->values();

        if ($channels->isEmpty()) {
            return collect();
        }

        return Prescription::query()
            ->whereIn('call_session', $channels)
            ->pluck('call_session')
            ->filter(fn ($channel) => is_string($channel) && trim($channel) !== '')
            ->mapWithKeys(fn (string $channel) => [trim($channel) => true]);
    }

    protected function transactionRequiresPrescription(string $type): bool
    {
        $normalized = strtolower(trim($type));

        return in_array($normalized, ['video_consult', 'video_call', 'video call', 'appointment', 'appointments', 'continuety_subscription'], true);
    }

    protected function hasMatchingPrescriptionForTransaction(Transaction $tx, Collection $prescriptionChannelSet): bool
    {
        $channel = is_string($tx->channel_name ?? null) ? trim((string) $tx->channel_name) : '';
        if ($channel === '') {
            return false;
        }

        return $prescriptionChannelSet->has($channel);
    }

    /**
     * GET /api/transactions/by-user?user_id=123[&limit=50][&type=video_consult]
     * Returns raw transaction rows for a given user, filtered by type.
     * Defaults to excell_export_campaign and maps legacy video_consult requests to excell_export_campaign.
     */
    public function byUser(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 50);
        $requestedType = strtolower(trim((string) ($data['type'] ?? 'excell_export_campaign')));
        $type = $requestedType === 'video_consult' ? 'excell_export_campaign' : $requestedType;
        $hasTransactionChannelName = Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'channel_name');
        $selectColumns = [
            'id',
            'user_id',
            'pet_id',
            'doctor_id',
            'clinic_id',
            'amount_paise',
            'status',
            'type',
            'payment_method',
            'reference',
            'metadata',
            'created_at',
            'updated_at',
        ];
        if ($hasTransactionChannelName) {
            $selectColumns[] = 'channel_name';
        }
        if (Schema::hasColumn('transactions', 'actual_amount_paid_by_consumer_paise')) {
            $selectColumns[] = 'actual_amount_paid_by_consumer_paise';
        }
        if (Schema::hasColumn('transactions', 'payment_to_snoutiq_paise')) {
            $selectColumns[] = 'payment_to_snoutiq_paise';
        }
        if (Schema::hasColumn('transactions', 'payment_to_doctor_paise')) {
            $selectColumns[] = 'payment_to_doctor_paise';
        }

        $tx = Transaction::query()
            ->where('user_id', $data['user_id'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderByDesc('id')
            ->limit($limit)
            ->get($selectColumns);

        $prescriptionsByChannel = $this->prescriptionsByTransactionChannels($tx);
        $payload = $tx->map(function (Transaction $transaction) use ($prescriptionsByChannel, $hasTransactionChannelName) {
            $row = $transaction->toArray();
            $channel = $hasTransactionChannelName && is_string($transaction->channel_name ?? null)
                ? trim((string) $transaction->channel_name)
                : '';
            $row['prescriptions'] = $channel !== '' ? ($prescriptionsByChannel[$channel] ?? []) : [];

            return $row;
        })->values();

        return response()->json([
            'success' => true,
            'count' => $payload->count(),
            'data' => $payload,
        ]);
    }

    /**
     * GET /api/transactions/video-consult/by-pet-user?pet_id=1&user_id=2[&limit=50]
     * Returns video_consult transaction rows joined with users, pets and doctors.
     */
    public function videoConsultByPetUser(Request $request)
    {
        $data = $request->validate([
            'pet_id' => 'required|integer',
            'user_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        if (
            ! Schema::hasTable('transactions')
            || ! Schema::hasTable('users')
            || ! Schema::hasTable('pets')
            || ! Schema::hasTable('doctors')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Required tables are missing.',
            ], 500);
        }

        $requiredTransactionColumns = ['id', 'type', 'user_id', 'pet_id', 'doctor_id'];
        foreach ($requiredTransactionColumns as $column) {
            if (! Schema::hasColumn('transactions', $column)) {
                return response()->json([
                    'success' => false,
                    'message' => "transactions.{$column} column is missing.",
                ], 500);
            }
        }

        $limit = (int) ($data['limit'] ?? 50);

        $rows = DB::table('transactions as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('pets as p', 'p.id', '=', 't.pet_id')
            ->leftJoin('doctors as d', 'd.id', '=', 't.doctor_id')
            ->where('t.type', 'video_consult')
            ->where('t.user_id', (int) $data['user_id'])
            ->where('t.pet_id', (int) $data['pet_id'])
            ->orderByDesc('t.id')
            ->limit($limit)
            ->select([
                't.*',
                'u.name as user_name',
                'u.phone as user_phone',
                'u.email as user_email',
                'p.name as pet_name',
                'p.pet_type as pet_type',
                'p.breed as pet_breed',
                'p.pet_age as pet_age',
                'p.pet_gender as pet_gender',
                'd.doctor_name as doctor_name',
                'd.doctor_mobile as doctor_mobile',
                'd.doctor_email as doctor_email',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'count' => $rows->count(),
            'filters' => [
                'pet_id' => (int) $data['pet_id'],
                'user_id' => (int) $data['user_id'],
                'type' => 'video_consult',
            ],
            'data' => $rows,
        ]);
    }

    protected function prescriptionsByTransactionChannels(Collection $transactions): array
    {
        if (
            $transactions->isEmpty()
            || !Schema::hasTable('transactions')
            || !Schema::hasColumn('transactions', 'channel_name')
            || !Schema::hasTable('prescriptions')
            || !Schema::hasColumn('prescriptions', 'call_session')
        ) {
            return [];
        }

        $channels = $transactions
            ->pluck('channel_name')
            ->filter(fn ($channel) => is_string($channel) && trim($channel) !== '')
            ->map(fn (string $channel) => trim($channel))
            ->unique()
            ->values();

        if ($channels->isEmpty()) {
            return [];
        }

        return Prescription::query()
            ->whereIn('call_session', $channels)
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (Prescription $prescription) => trim((string) ($prescription->call_session ?? '')))
            ->map(fn (Collection $group) => $group->values()->map(fn (Prescription $prescription) => $prescription->toArray())->all())
            ->toArray();
    }

    /**
     * Fetch the latest call session for each channel for a given doctor.
     */
    protected function latestCallSessionsForTransactionChannels(int $doctorId, Collection $channels): Collection
    {
        if ($channels->isEmpty()) {
            return collect();
        }

        $sessions = CallSession::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('channel_name', $channels)
            ->orderByDesc('id')
            ->get();

        return $sessions
            ->groupBy(fn (CallSession $session) => trim((string) ($session->channel_name ?? '')))
            ->map(fn ($group) => $group->first());
    }

    /**
     * Fetch the latest video_apointment row for each user for a given doctor.
     */
    protected function latestVideoApointmentsForUsers(int $doctorId, $userIds)
    {
        if ($userIds->isEmpty() || !Schema::hasTable('video_apointment')) {
            return collect();
        }

        $rows = VideoApointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('user_id', $userIds)
            ->orderByDesc('id')
            ->get();

        return $rows
            ->groupBy(fn (VideoApointment $row) => (int) $row->user_id)
            ->map(fn ($group) => $group->first());
    }

    protected function deviceTokensForUsers($transactions, $latestSessions)
    {
        $userIds = collect()
            ->merge($transactions->pluck('user_id'))
            ->merge($latestSessions->pluck('patient_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        $tokens = DeviceToken::query()
            ->select(['user_id', 'token'])
            ->whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->get()
            ->groupBy(fn (DeviceToken $token) => (int) $token->user_id)
            ->map(fn ($group) => $group->pluck('token')->filter()->unique()->values()->all());

        return $tokens;
    }

    protected function formatCallSession(CallSession $session): array
    {
        return [
            'id' => $session->id,
            'doctor_id' => $session->doctor_id,
            'patient_id' => $session->patient_id,
            'call_session' => $session->resolveIdentifier(),
            'channel_name' => $session->channel_name,
            'status' => $session->status,
            'payment_status' => $session->payment_status,
            'currency' => $session->currency,
            'is_completed' => $session->is_completed ?? null,
            'created_at' => optional($session->created_at)->toIso8601String(),
            'updated_at' => optional($session->updated_at)->toIso8601String(),
        ];
    }

    protected function formatVideoApointment(VideoApointment $videoApointment): array
    {
        return [
            'id' => $videoApointment->id,
            'order_id' => $videoApointment->order_id,
            'pet_id' => $videoApointment->pet_id,
            'user_id' => $videoApointment->user_id,
            'doctor_id' => $videoApointment->doctor_id,
            'clinic_id' => $videoApointment->clinic_id,
            'call_session' => $videoApointment->call_session,
            'is_completed' => (bool) ($videoApointment->is_completed ?? false),
            'created_at' => optional($videoApointment->created_at)->toIso8601String(),
            'updated_at' => optional($videoApointment->updated_at)->toIso8601String(),
        ];
    }

    protected function formatJoinedCall(Transaction $transaction): ?array
    {
        $callId = $transaction->getAttribute('joined_call_id');
        if ($callId === null) {
            return null;
        }

        return [
            'id' => (int) $callId,
            'status' => $transaction->getAttribute('joined_call_status'),
            'channel_name' => $transaction->getAttribute('joined_call_channel_name'),
            'channel' => $transaction->getAttribute('joined_call_channel'),
            'accepted_at' => $transaction->getAttribute('joined_call_accepted_at'),
            'rejected_at' => $transaction->getAttribute('joined_call_rejected_at'),
            'ended_at' => $transaction->getAttribute('joined_call_ended_at'),
            'cancelled_at' => $transaction->getAttribute('joined_call_cancelled_at'),
            'missed_at' => $transaction->getAttribute('joined_call_missed_at'),
        ];
    }

    protected function petDoc2BlobUrl($pet): ?string
    {
        if (! $pet || ! Schema::hasTable('pets') || ! Schema::hasColumn('pets', 'pet_doc2_blob')) {
            return null;
        }

        $blob = $pet->getRawOriginal('pet_doc2_blob');
        if ($blob === null || $blob === '') {
            return null;
        }

        return route('api.pets.pet-doc2-blob', ['pet' => $pet->id]);
    }

    protected function absolutePetDoc2Url(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');
        $base = rtrim(url('/'), '/');

        if (str_starts_with($path, 'backend/') && str_ends_with($base, '/backend')) {
            $path = substr($path, strlen('backend/'));
        }

        return $base . '/' . $path;
    }
}
