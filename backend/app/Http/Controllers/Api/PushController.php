<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use App\Support\DeviceTokenOwnerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'call_id' => ['required'],
            'doctor_id' => ['nullable'],
            'channel' => ['nullable', 'string'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'duration_ms' => ['nullable', 'integer', 'min:1000', 'max:120000'],
            'interval_ms' => ['nullable', 'integer', 'min:500', 'max:10000'],
        ]);

        $token = $this->normalizeToken($validated['token']);
        if (!$this->isLikelyFcmToken($token)) {
            return response()->json([
                'error' => 'The provided token does not look like a valid FCM registration token.',
            ], 422);
        }

        $title = $validated['title'] ?? 'Snoutiq Incoming Call';
        $body = $validated['body'] ?? 'Incoming call alert';

        $data = [
            'type' => 'incoming_call',
            'call_id' => (string) $validated['call_id'],
        ];

        if (isset($validated['doctor_id'])) {
            $data['doctor_id'] = (string) $validated['doctor_id'];
        }

        if (isset($validated['channel'])) {
            $data['channel'] = (string) $validated['channel'];
        }

        // Send a single push (no repeated ring spam)
        $push->sendToToken($token, $title, $body, $data);

        return response()->json([
            'ok' => true,
            'scheduled' => 1,
        ]);
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
