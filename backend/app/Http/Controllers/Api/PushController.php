<?php

namespace App\Http\Controllers\Api;

use App\Events\CallSessionUpdated;
use App\Events\CallStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallSession;
use App\Models\Doctor;
use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use App\Support\DeviceTokenOwnerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;

class PushController extends Controller
{
    public function editToken(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => ['required', 'integer'],
            'token'      => ['required', 'string', 'max:500'], // new / desired token
            'old_token'  => ['nullable', 'string', 'max:500'], // optional old token to replace
            'platform'   => ['nullable', 'string', Rule::in(['android', 'ios', 'web'])],
            'device_id'  => ['nullable', 'string', 'max:128'],
            'meta'       => ['nullable', 'array'],
            'owner_model'=> ['nullable', 'string'],
        ]);

        try {
            $ownerModel = DeviceTokenOwnerResolver::resolve($validated['owner_model'] ?? \App\Models\User::class);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Ensure the owner exists in the resolved model (user / doctor / clinic etc.)
        if ($ownerModel::query()->whereKey($validated['user_id'])->doesntExist()) {
            return response()->json(['error' => 'Owner record not found'], 404);
        }

        try {
            $normalizedToken = $this->normalizeToken($validated['token']);
            $oldToken = isset($validated['old_token']) ? $this->normalizeToken($validated['old_token']) : null;

            if (!$this->isLikelyFcmToken($normalizedToken)) {
                return response()->json([
                    'error' => 'The provided token does not look like a valid FCM registration token.',
                ], 422);
            }

            // Upsert strictly on user_id (and device_id if provided to scope per device)
            $lookup = ['user_id' => $validated['user_id']];
            if (!empty($validated['device_id'])) {
                $lookup['device_id'] = $validated['device_id'];
            }

            $meta = $validated['meta'] ?? [
                'app' => 'snoutiq',
                'env' => app()->environment(),
            ];
            $meta['owner_model'] = $ownerModel;

            if ($oldToken) {
                DeviceToken::where('token', $oldToken)->delete();
            }

            $tokenToUpdate = DeviceToken::updateOrCreate(
                $lookup,
                [
                    'token' => $normalizedToken,
                    'platform' => $validated['platform'] ?? null,
                    'device_id' => $validated['device_id'] ?? null,
                    'meta' => $meta,
                    'last_seen_at' => now(),
                ]
            );

            return response()->json([
                'ok'       => true,
                'id'       => $tokenToUpdate->id,
                'user_id'  => $tokenToUpdate->user_id,
                'token'    => $tokenToUpdate->token,
            ]);
        } catch (Throwable $e) {
            \Log::error('Failed to edit FCM token', [
                'user_id' => $validated['user_id'],
                'token'   => $validated['token'],
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to edit token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function registerToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'string', Rule::in(['android', 'ios', 'web'])],
            'device_id' => ['nullable', 'string', 'max:128'],
            'meta' => ['nullable', 'array'],
            'user_id' => ['nullable', 'integer'],
            'owner_model' => ['nullable', 'string'],
        ]);

        $metaPayload = $validated['meta'] ?? [
            'app' => 'snoutiq',
            'env' => app()->environment(),
        ];

        $normalizedToken = $this->normalizeToken($validated['token']);
        if (!$this->isLikelyFcmToken($normalizedToken)) {
            return response()->json([
                'error' => 'The provided token does not look like a valid FCM registration token.',
            ], 422);
        }

        $resolvedUserId = Auth::id() ?? ($validated['user_id'] ?? null);
        if (!$resolvedUserId && isset($metaPayload['user_id'])) {
            $metaUserId = filter_var($metaPayload['user_id'], FILTER_VALIDATE_INT);
            $resolvedUserId = $metaUserId !== false ? $metaUserId : null;
        }

        $metaOwnerModel = $metaPayload['owner_model'] ?? null;
        $ownerModelExplicit = $validated['owner_model'] ?? (is_string($metaOwnerModel) ? $metaOwnerModel : null);
        $ownerModelHint = Auth::id()
            ? \App\Models\User::class
            : $ownerModelExplicit;
        $ownerModelProvidedExplicitly = $ownerModelExplicit !== null;

        try {
            $ownerModel = DeviceTokenOwnerResolver::resolve($ownerModelHint);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }

        if ($resolvedUserId && $ownerModel::query()->whereKey($resolvedUserId)->doesntExist()) {
            // If no explicit owner model was provided, try to auto-detect one using the id
            if (! $ownerModelProvidedExplicitly) {
                $detectedModel = DeviceTokenOwnerResolver::detectOwnerModelForId($resolvedUserId);
                if ($detectedModel) {
                    $ownerModel = $detectedModel;
                } else {
                    return response()->json([
                        'error' => 'Owner record not found for the provided identifier.',
                    ], 422);
                }
            } else {
                return response()->json([
                    'error' => 'Owner record not found for the provided identifier.',
                ], 422);
            }
        }

        if ($resolvedUserId) {
            $metaPayload['user_id'] = $resolvedUserId;
        }
        $metaPayload['owner_model'] = $ownerModel;

        try {
            $token = DeviceToken::updateOrCreate(
                ['token' => $normalizedToken],
                [
                    'user_id' => $resolvedUserId,
                    'platform' => $validated['platform'] ?? null,
                    'device_id' => $validated['device_id'] ?? null,
                    'meta' => $metaPayload,
                    'last_seen_at' => now(),
                ]
            );

            return response()->json([
                'ok' => true,
                'id' => $token->id,
                'user_id' => $token->user_id,
            ]);
        } catch (Throwable $e) {
            \Log::error('Failed to register FCM token', [
                'token' => $validated['token'],
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to register token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function unregisterToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $normalizedToken = $this->normalizeToken($validated['token']);

        try {
            DeviceToken::where('token', $normalizedToken)->delete();

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            \Log::error('Failed to unregister FCM token', [
                'token' => $normalizedToken,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to unregister token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function testToToken(Request $request, FcmService $push)
    {
        $validated = $request->validate([
            'token' => ['nullable', 'string'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
            'data.*' => ['nullable'],
        ]);

        $title = $validated['title'] ?? 'Snoutiq Alert';
        $body = $validated['body'] ?? 'Test push from API';
        $data = $this->normalizeData($request->input('data', []));
        if (!isset($data['type'])) {
            $data['type'] = 'test';
        }

        \Log::info('PushController@testToToken received', [
            'has_token' => !empty($validated['token']),
            'token' => isset($validated['token']) ? $this->maskToken($validated['token']) : null,
            'title' => $title,
            'body_len' => strlen($body),
            'data_keys' => array_keys($data),
        ]);

        try {
            if (!empty($validated['token'])) {
                $normalizedToken = $this->normalizeToken($validated['token']);
                if (!$this->isLikelyFcmToken($normalizedToken)) {
                    return response()->json([
                        'error' => 'The provided token does not look like a valid FCM registration token.',
                    ], 422);
                }
                // Send immediately for testing
                $push->sendToToken($normalizedToken, $title, $body, $data);
            } else {
                // If token not provided, try sending to the current user's registered devices
                $userId = Auth::id();
                if (!$userId) {
                    return response()->json(['error' => 'Login or pass token'], 422);
                }
                $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();
                foreach ($tokens as $t) {
                    $push->sendToToken($t, $title, $body, $data);
                }
            }

            return response()->json([
                'sent' => true,
                'success' => true,
            ]);
        } catch (MessagingException $e) {
            \Log::error('FCM test push failed', [
                'token' => $validated['token'] ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'FCM send failed',
                'details' => $e->getMessage(),
            ], 500);
        } catch (Throwable $e) {
            \Log::error('FCM test push failed', [
                'token' => $validated['token'] ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'FCM send failed',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function ring(Request $request, FcmService $push)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'force' => ['nullable', 'boolean'],
            'data' => ['nullable', 'array'],
            'data.call_id' => ['nullable', 'string'],
            'data.doctor_id' => ['nullable'],
            'data.patient_id' => ['nullable'],
            'data.channel' => ['nullable', 'string'],
            'data.channel_name' => ['nullable', 'string'],
            'data.expires_at' => ['nullable'],
            // Legacy fields (backward compatibility)
            'call_id' => ['nullable', 'string'],
            'doctor_id' => ['nullable'],
            'patient_id' => ['nullable'],
            'channel' => ['nullable', 'string'],
            'channel_name' => ['nullable', 'string'],
            'expires_at' => ['nullable'],
        ]);

        $token = $this->normalizeToken($validated['token']);
        if (!$this->isLikelyFcmToken($token)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided token does not look like a valid FCM registration token.',
            ], 422);
        }

        $dataBlock = $validated['data'] ?? [];
        $callId = $dataBlock['call_id'] ?? $validated['call_id'] ?? null;
        $doctorId = $dataBlock['doctor_id'] ?? $validated['doctor_id'] ?? null;
        $patientId = $dataBlock['patient_id'] ?? $validated['patient_id'] ?? null;
        $channel = $dataBlock['channel'] ?? $validated['channel'] ?? null;
        $channelName = $dataBlock['channel_name'] ?? $validated['channel_name'] ?? null;
        $expiresAt = $dataBlock['expires_at'] ?? $validated['expires_at'] ?? null;

        $validator = validator([
            'call_id' => $callId,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'channel' => $channel,
        ], [
            'call_id' => ['required', 'string'],
            'doctor_id' => ['required'],
            'patient_id' => ['required'],
            'channel' => ['required', 'string'],
        ], [
            'call_id.required' => 'Provide call_id either at top-level or inside data.call_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doctorName = $request->input('data.doctor_name')
            ?? $request->input('data.doctorName')
            ?? $request->input('data.callerName')
            ?? $request->input('doctor_name')
            ?? $request->input('doctorName')
            ?? $request->input('callerName');

        if (is_string($doctorName)) {
            $doctorName = trim($doctorName);
            if ($doctorName === '') {
                $doctorName = null;
            }
        } else {
            $doctorName = null;
        }

        if ($doctorName === null) {
            $doctorIdLookup = is_numeric((string) $doctorId) ? (int) $doctorId : null;
            if ($doctorIdLookup) {
                $doctorName = Doctor::query()
                    ->whereKey($doctorIdLookup)
                    ->value('doctor_name');
                if (is_string($doctorName)) {
                    $doctorName = trim($doctorName);
                    if ($doctorName === '') {
                        $doctorName = null;
                    }
                }
            }
        }

        if ($expiresAt !== null && !ctype_digit((string) $expiresAt)) {
            return response()->json([
                'success' => false,
                'message' => 'expires_at must be a millisecond epoch string',
            ], 422);
        }

        $nowMs = now()->valueOf();
        $expiresAtMs = $expiresAt !== null ? (int) $expiresAt : now()->addSeconds(90)->valueOf();
        if ($expiresAtMs < $nowMs) {
            \Log::warning('FCM ring push expires_at was in the past, overriding', [
                'call_id' => $callId,
                'doctor_id' => $doctorId,
                'expires_at' => $expiresAtMs,
                'now_ms' => $nowMs,
            ]);
            $expiresAtMs = now()->addSeconds(90)->valueOf();
        }

        $normalizedCallId = trim((string) $callId);
        $isForce = (bool) ($validated['force'] ?? false);
        if (!$isForce && $this->isTokenRingStopped($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Ringing is temporarily blocked for this token. Trigger force=1 to override.',
                'token_blocked' => true,
            ], 409);
        }
        if ($normalizedCallId !== '') {
            if (!$isForce && $this->isRingStopped($normalizedCallId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ringing is stopped for this call_id. Trigger force=1 to override.',
                    'call_id' => $normalizedCallId,
                    'stopped' => true,
                ], 409);
            }

            if ($isForce) {
                $this->clearRingStopped($normalizedCallId);
            }
        }
        if ($isForce) {
            $this->clearTokenRingStopped($token);
        }

        $channelName = $channelName ?: "agora_channel_{$callId}";
        $title = $validated['title'] ?? 'Snoutiq Incoming Call';
        $body = $validated['body'] ?? 'Incoming call alert';

        $data = [
            'type' => 'incoming_call',
            'call_id' => (string) $callId,
            'doctor_id' => (string) $doctorId,
            'patient_id' => (string) $patientId,
            'doctor_name' => $doctorName ?? '',
            'doctorName' => $doctorName ?? '',
            'callerName' => $doctorName ?? '',
            'channel' => (string) $channel,
            'channel_name' => (string) $channelName,
            'expires_at' => (string) $expiresAtMs,
            // Data-only keeps FCM high priority for Doze bypass; app must display UI
            'data_only' => '1',
        ];

        $tokenLast8 = strlen($token) >= 8 ? substr($token, -8) : $token;

        \Log::info('FCM ring push attempt', [
            'call_id' => $callId,
            'doctor_id' => $doctorId,
            'token_last8' => $tokenLast8,
            'payload' => [
                'token_last8' => $tokenLast8,
                'data' => $data,
                'android' => ['priority' => 'high', 'ttl' => '90s'],
                'apns' => ['headers' => ['apns-priority' => '10']],
            ],
        ]);

        $push->sendToToken($token, $title, $body, $data);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function stopRing(Request $request, FcmService $push)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'missed_title' => ['nullable', 'string'],
            'missed_body' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
            'data.call_id' => ['nullable', 'string'],
            'data.doctor_id' => ['nullable'],
            'data.patient_id' => ['nullable'],
            'data.channel' => ['nullable', 'string'],
            'data.channel_name' => ['nullable', 'string'],
            // Legacy fields (backward compatibility)
            'call_id' => ['nullable', 'string'],
            'doctor_id' => ['nullable'],
            'patient_id' => ['nullable'],
            'channel' => ['nullable', 'string'],
            'channel_name' => ['nullable', 'string'],
        ]);

        $token = $this->normalizeToken($validated['token']);
        if (!$this->isLikelyFcmToken($token)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided token does not look like a valid FCM registration token.',
            ], 422);
        }

        $dataBlock = $validated['data'] ?? [];
        $callId = $dataBlock['call_id'] ?? $validated['call_id'] ?? null;
        $doctorId = $dataBlock['doctor_id'] ?? $validated['doctor_id'] ?? null;
        $patientId = $dataBlock['patient_id'] ?? $validated['patient_id'] ?? null;
        $channel = $dataBlock['channel'] ?? $validated['channel'] ?? null;
        $channelName = $dataBlock['channel_name'] ?? $validated['channel_name'] ?? null;

        $title = $validated['title'] ?? 'Snoutiq Call Ended';
        $body = $validated['body'] ?? 'Incoming call cancelled';
        $normalizedCallId = trim((string) ($callId ?? ''));
        $doctorIdInt = is_numeric((string) $doctorId) ? (int) $doctorId : null;
        $patientIdInt = is_numeric((string) $patientId) ? (int) $patientId : null;
        $this->rememberTokenRingStopped($token);
        if ($normalizedCallId !== '') {
            $this->rememberRingStopped($normalizedCallId);
        }

        $data = [
            'type' => 'incoming_call_end',
            'action' => 'stop_ringing',
            'event' => 'stop_ringing',
            'status' => 'ended',
            'call_status' => 'ended',
            'should_ring' => '0',
            'ringing' => '0',
            'stop_ringing' => '1',
            'call_id' => (string) ($callId ?? ''),
            'doctor_id' => (string) ($doctorId ?? ''),
            'patient_id' => (string) ($patientId ?? ''),
            'channel' => (string) ($channel ?? ''),
            'channel_name' => (string) ($channelName ?? ''),
            'expires_at' => (string) now()->valueOf(),
            // Force data-only high priority so app can stop ringtone immediately.
            'data_only' => '1',
        ];

        $tokenLast8 = strlen($token) >= 8 ? substr($token, -8) : $token;

        \Log::info('FCM stop ring push attempt', [
            'call_id' => $callId,
            'doctor_id' => $doctorId,
            'token_last8' => $tokenLast8,
            'payload' => [
                'token_last8' => $tokenLast8,
                'data' => $data,
            ],
        ]);

        $push->sendToToken($token, $title, $body, $data);
        // Compatibility stop push for clients that only listen to incoming_call payloads.
        $compatStopData = array_merge($data, [
            'type' => 'incoming_call',
            'event' => 'incoming_call',
            'callId' => (string) ($callId ?? ''),
            'doctorId' => (string) ($doctorId ?? ''),
            'patientId' => (string) ($patientId ?? ''),
            'channelName' => (string) ($channelName ?? ''),
            'shouldRing' => '0',
            'stopRinging' => '1',
            'is_stop' => '1',
        ]);
        try {
            $push->sendToToken($token, $title, $body, $compatStopData);
        } catch (Throwable $e) {
            \Log::warning('FCM compatibility stop ring push failed', [
                'call_id' => $callId,
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);
        }
        // Visible stop event for clients that ignore data-only callbacks in background.
        $visibleStopData = $compatStopData;
        $visibleStopData['type'] = 'incoming_call_end';
        $visibleStopData['event'] = 'stop_ringing';
        $visibleStopData['action'] = 'stop_ringing';
        $visibleStopData['data_only'] = '0';
        try {
            $push->sendToToken($token, $title, $body, $visibleStopData);
        } catch (Throwable $e) {
            \Log::warning('FCM visible stop ring push failed', [
                'call_id' => $callId,
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);
        }
        $secondaryTokens = $this->sendStopToDoctorTokens(
            $push,
            $token,
            $doctorIdInt,
            $title,
            $body,
            $data
        );
        $reverbSync = $this->syncStopRingState(
            $normalizedCallId,
            $doctorIdInt,
            $patientIdInt,
            trim((string) ($channelName ?: $channel ?: ''))
        );
        $missedTitle = $validated['missed_title'] ?? 'Missed Consultation Call';
        $missedBody = $validated['missed_body'] ?? 'The incoming call was ended.';
        $missedData = [
            'type' => 'missed_call',
            'action' => 'show_missed_notification',
            'event' => 'missed_call',
            'status' => 'missed',
            'call_status' => 'missed',
            'should_ring' => '0',
            'ringing' => '0',
            'stop_ringing' => '1',
            'call_id' => (string) ($callId ?? ''),
            'doctor_id' => (string) ($doctorId ?? ''),
            'patient_id' => (string) ($patientId ?? ''),
            'channel' => (string) ($channel ?? ''),
            'channel_name' => (string) ($channelName ?? ''),
            'ended_at' => (string) now()->valueOf(),
            // Keep this as notification+data so user gets a clear "missed" alert.
            'data_only' => '0',
        ];
        $missedNotificationSent = false;
        try {
            $push->sendToToken($token, $missedTitle, $missedBody, $missedData);
            $missedNotificationSent = true;
        } catch (Throwable $e) {
            \Log::warning('FCM missed call notification send failed', [
                'call_id' => $callId,
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'ring_blocked' => $normalizedCallId !== '' ? $this->isRingStopped($normalizedCallId) : false,
            'token_blocked' => $this->isTokenRingStopped($token),
            'stop_sent_count' => 1 + count($secondaryTokens),
            'missed_notification_sent' => $missedNotificationSent,
            'reverb_sync' => $reverbSync,
            'data' => $data,
            'missed_data' => $missedData,
        ]);
    }

    /**
     * @param array<string,string> $data
     * @return array<int,string>
     */
    private function sendStopToDoctorTokens(
        FcmService $push,
        string $primaryToken,
        ?int $doctorId,
        string $title,
        string $body,
        array $data
    ): array {
        if (!$doctorId || $doctorId <= 0) {
            return [];
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $doctorId)
            ->pluck('token')
            ->filter()
            ->map(fn ($value) => $this->normalizeToken((string) $value))
            ->unique()
            ->values()
            ->all();

        $tokens = array_values(array_filter(
            $tokens,
            fn (string $candidate) => $candidate !== '' && $candidate !== $primaryToken
        ));

        foreach ($tokens as $extraToken) {
            try {
                $push->sendToToken($extraToken, $title, $body, $data);
            } catch (Throwable $e) {
                \Log::warning('FCM stop ring secondary token send failed', [
                    'doctor_id' => $doctorId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $tokens;
    }

    private function syncStopRingState(
        string $callId,
        ?int $doctorId,
        ?int $patientId,
        ?string $channelName
    ): array {
        $result = [
            'call_updated' => false,
            'call_session_updated' => false,
            'call_status_broadcast' => false,
            'call_session_broadcast' => false,
        ];

        $now = now();
        $normalizedChannel = trim((string) ($channelName ?? ''));

        if ($callId !== '' && ctype_digit($callId)) {
            try {
                /** @var Call|null $call */
                $call = Call::query()->find((int) $callId);
                if ($call) {
                    $terminalStatuses = [
                        Call::STATUS_CANCELLED,
                        Call::STATUS_ENDED,
                        Call::STATUS_REJECTED,
                        Call::STATUS_MISSED,
                    ];

                    if (!in_array($call->status, $terminalStatuses, true)) {
                        $call->status = Call::STATUS_CANCELLED;
                        $call->cancelled_at = $now;
                        $call->save();
                        $result['call_updated'] = true;
                    }

                    event(new CallStatusUpdated($call->fresh()));
                    $result['call_status_broadcast'] = true;
                }
            } catch (Throwable $e) {
                \Log::warning('stopRing call sync failed', [
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $channelCandidates = array_values(array_unique(array_filter([
            $normalizedChannel !== '' ? $normalizedChannel : null,
            $callId !== '' ? 'channel_' . $callId : null,
            $callId !== '' ? 'agora_channel_' . $callId : null,
            $callId !== '' ? 'agora_channel_call_' . preg_replace('/^call_/', '', $callId) : null,
        ])));

        $canLookupByIdentifier = $callId !== '' && CallSession::supportsColumn('call_identifier');
        if ($canLookupByIdentifier || !empty($channelCandidates)) {
            try {
                $sessionQuery = CallSession::query();
                $sessionQuery->where(function ($query) use ($canLookupByIdentifier, $callId, $channelCandidates) {
                    if ($canLookupByIdentifier) {
                        $query->where('call_identifier', $callId);
                        if (!empty($channelCandidates)) {
                            $query->orWhereIn('channel_name', $channelCandidates);
                        }
                        return;
                    }

                    $query->whereIn('channel_name', $channelCandidates);
                });

                /** @var CallSession|null $session */
                $session = $sessionQuery->orderByDesc('id')->first();
                if ($session) {
                    $updates = ['status' => 'ended'];
                    if (CallSession::supportsColumn('ended_at')) {
                        $updates['ended_at'] = $now;
                    }
                    if ($doctorId && (int) ($session->doctor_id ?? 0) <= 0) {
                        $updates['doctor_id'] = $doctorId;
                    }
                    if ($patientId && (int) ($session->patient_id ?? 0) <= 0) {
                        $updates['patient_id'] = $patientId;
                    }

                    $session->fill($updates);
                    $session->save();
                    $result['call_session_updated'] = true;

                    if (!$doctorId && (int) ($session->doctor_id ?? 0) > 0) {
                        $doctorId = (int) $session->doctor_id;
                    }
                    if (!$patientId && (int) ($session->patient_id ?? 0) > 0) {
                        $patientId = (int) $session->patient_id;
                    }
                    if ($normalizedChannel === '' && !empty($session->channel_name)) {
                        $normalizedChannel = (string) $session->channel_name;
                    }
                }
            } catch (Throwable $e) {
                \Log::warning('stopRing call session sync failed', [
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($callId !== '') {
            try {
                event(new CallSessionUpdated([
                    'callId' => $callId,
                    'doctorId' => $doctorId,
                    'patientId' => $patientId,
                    'channel' => $normalizedChannel !== '' ? $normalizedChannel : null,
                    'status' => 'ended',
                    'endedAt' => $now->toIso8601String(),
                    'event' => 'stop_ringing',
                    'action' => 'stop_ringing',
                    'ringing' => false,
                    'shouldRing' => false,
                ], 'status_update'));

                $result['call_session_broadcast'] = true;
            } catch (Throwable $e) {
                \Log::warning('stopRing reverb broadcast failed', [
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    private function ringStopCacheKey(string $callId): string
    {
        return 'push:ring:stopped:' . trim($callId);
    }

    private function tokenRingStopCacheKey(string $token): string
    {
        return 'push:ring:token-stopped:' . md5($this->normalizeToken($token));
    }

    private function rememberRingStopped(string $callId): void
    {
        $normalized = trim($callId);
        if ($normalized === '') {
            return;
        }

        // Keep stop flag for 15 min to block accidental re-rings on the same call_id.
        Cache::put($this->ringStopCacheKey($normalized), now()->toIso8601String(), now()->addMinutes(15));
    }

    private function rememberTokenRingStopped(string $token): void
    {
        $normalized = $this->normalizeToken($token);
        if ($normalized === '') {
            return;
        }

        // Block re-ringing this token briefly to prevent continuous loops.
        Cache::put($this->tokenRingStopCacheKey($normalized), now()->toIso8601String(), now()->addMinutes(5));
    }

    private function isRingStopped(string $callId): bool
    {
        $normalized = trim($callId);
        if ($normalized === '') {
            return false;
        }

        return Cache::has($this->ringStopCacheKey($normalized));
    }

    private function isTokenRingStopped(string $token): bool
    {
        $normalized = $this->normalizeToken($token);
        if ($normalized === '') {
            return false;
        }

        return Cache::has($this->tokenRingStopCacheKey($normalized));
    }

    private function clearRingStopped(string $callId): void
    {
        $normalized = trim($callId);
        if ($normalized === '') {
            return;
        }

        Cache::forget($this->ringStopCacheKey($normalized));
    }

    private function clearTokenRingStopped(string $token): void
    {
        $normalized = $this->normalizeToken($token);
        if ($normalized === '') {
            return;
        }

        Cache::forget($this->tokenRingStopCacheKey($normalized));
    }

    private function normalizeToken(string $token): string
    {
        return trim(trim($token), "\"'");
    }

    private function maskToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 12) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 6).'…'.substr($token, -6);
    }

    /**
     * @param mixed $data
     * @return array<string,string>
     */
    private function normalizeData(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $normalized = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $normalized[$key] = json_encode($value);
                continue;
            }

            if ($value === null) {
                continue;
            }

            $normalized[$key] = (string) $value;
        }

        return $normalized;
    }

    private function isLikelyFcmToken(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        // FCM tokens are long and should not contain whitespace
        if (strlen($token) < 80 || preg_match('/\\s/', $token)) {
            return false;
        }

        return true;
    }
}
