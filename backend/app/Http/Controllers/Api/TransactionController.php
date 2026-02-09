<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Models\DeviceToken;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 50);

        $transactions = Transaction::query()
            ->where('type', 'video_consult')
            ->where('doctor_id', $data['doctor_id'])
            ->with([
                'user' => fn ($q) => $q->select('id', 'name'),
                'user.deviceTokens:id,user_id,token',
                'pet:id,name',
                'doctor:id,doctor_name',
            ])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $latestSessions = $this->latestCallSessionsForUsers(
            doctorId: (int) $data['doctor_id'],
            userIds: $transactions->pluck('user_id')->filter()->unique()
        );

        $deviceTokensByUser = $this->deviceTokensForUsers($transactions, $latestSessions);

        $payload = $transactions->map(function (Transaction $tx) use ($latestSessions, $deviceTokensByUser) {
            $user = $tx->user;
            $pet = $tx->pet;
            $callSession = $latestSessions->get($tx->user_id);
            $deviceTokens = $deviceTokensByUser->get((int) $tx->user_id)
                ?: ($callSession ? $deviceTokensByUser->get((int) $callSession->patient_id, []) : []);

            // Fallback to eager-loaded relationship if the direct lookup missed
            if (empty($deviceTokens) && $user) {
                $deviceTokens = $user->deviceTokens
                    ? $user->deviceTokens->pluck('token')->filter()->unique()->values()->all()
                    : [];
            }

            return [
                'id' => $tx->id,
                'user_id' => $tx->user_id,
                'doctor_id' => $tx->doctor_id,
                'amount_paise' => $tx->amount_paise,
                'status' => $tx->status,
                'type' => $tx->type,
                'payment_method' => $tx->payment_method,
                'reference' => $tx->reference,
                'created_at' => optional($tx->created_at)->toIso8601String(),
                'updated_at' => optional($tx->updated_at)->toIso8601String(),
                'user_name' => $user->name ?? null,
                'doctor_name' => $tx->doctor->doctor_name ?? null,
                'device_tokens' => $deviceTokens,
                'pet' => $pet ? [
                    'id' => $pet->id,
                    'name' => $pet->name,
                ] : null,
                'call_session' => $callSession ? $this->formatCallSession($callSession) : null,
                'call_session_is_completed' => $callSession ? (bool) ($callSession->is_completed ?? false) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $payload->count(),
            'data' => $payload,
        ]);
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

        $tx = Transaction::query()
            ->where('user_id', $data['user_id'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
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
            ]);

        return response()->json([
            'success' => true,
            'count' => $tx->count(),
            'data' => $tx,
        ]);
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
}
