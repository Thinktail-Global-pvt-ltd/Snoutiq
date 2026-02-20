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
use Illuminate\Support\Collection;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 50);
        $petColumns = ['id', 'name'];
        if (Schema::hasTable('pets')) {
            if (Schema::hasColumn('pets', 'pet_doc2')) {
                $petColumns[] = 'pet_doc2';
            }
            if (Schema::hasColumn('pets', 'pet_doc2_blob')) {
                $petColumns[] = 'pet_doc2_blob';
            }
            if (Schema::hasColumn('pets', 'pet_doc2_mime')) {
                $petColumns[] = 'pet_doc2_mime';
            }
        }

        $transactions = Transaction::query()
            ->whereIn('type', ['video_consult', 'video_call', 'video call', 'appointment'])
            ->where('doctor_id', $data['doctor_id'])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'pending');
            })
            ->with([
                'user' => fn ($q) => $q->select('id', 'name'),
                'user.deviceTokens:id,user_id,token',
                'pet' => fn ($q) => $q->select($petColumns),
                'doctor:id,doctor_name',
            ])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $prescriptionChannelSet = $this->prescriptionChannelSetForTransactions($transactions);

        $latestSessions = $this->latestCallSessionsForUsers(
            doctorId: (int) $data['doctor_id'],
            userIds: $transactions->pluck('user_id')->filter()->unique()
        );
        $latestVideoApointments = $this->latestVideoApointmentsForUsers(
            doctorId: (int) $data['doctor_id'],
            userIds: $transactions->pluck('user_id')->filter()->unique()
        );

        $deviceTokensByUser = $this->deviceTokensForUsers($transactions, $latestSessions);

        $payload = $transactions->map(function (Transaction $tx) use ($latestSessions, $latestVideoApointments, $deviceTokensByUser, $prescriptionChannelSet) {
            $user = $tx->user;
            $pet = $tx->pet;
            $callSession = $latestSessions->get($tx->user_id);
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
            $petDoc2Url = $pet ? $this->absolutePetDoc2Url($pet->pet_doc2 ?? null) : null;
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
                'pet' => $pet ? [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'pet_doc2' => $pet->pet_doc2 ?? null,
                    'pet_doc2_blob_url' => $petBlobUrl,
                    'pet_doc2_url' => $petDoc2Url,
                    'pet_image_url' => $petBlobUrl ?: $petDoc2Url,
                ] : null,
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

        return in_array($normalized, ['video_consult', 'video_call', 'video call', 'appointment'], true);
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
     * Returns raw transaction rows for a given user, filtered by type (defaults to video_consult).
     */
    public function byUser(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 50);
        $type = $data['type'] ?? 'video_consult';
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
     * Fetch the latest call session for each user for a given doctor.
     */
    protected function latestCallSessionsForUsers(int $doctorId, $userIds)
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        $sessions = CallSession::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('patient_id', $userIds)
            ->orderByDesc('id')
            ->get();

        return $sessions
            ->groupBy('patient_id')
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
