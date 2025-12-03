<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendFcmMessage;
use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use App\Support\DeviceTokenOwnerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

            $tokenToUpdate = DeviceToken::updateOrCreate(
                $lookup,
                [
                    'token' => $validated['token'],
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
                ['token' => $validated['token']],
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

        try {
            DeviceToken::where('token', $validated['token'])->delete();

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            \Log::error('Failed to unregister FCM token', [
                'token' => $validated['token'],
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
        ]);

        $title = $validated['title'] ?? 'Snoutiq Alert';
        $body = $validated['body'] ?? 'Test push from API';

        try {
            if (!empty($validated['token'])) {
                // Send immediately for testing
                $push->sendToToken($validated['token'], $title, $body, ['type' => 'test']);
            } else {
                // If token not provided, try sending to the current user's registered devices
                $userId = Auth::id();
                if (!$userId) {
                    return response()->json(['error' => 'Login or pass token'], 422);
                }
                $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();
                foreach ($tokens as $t) {
                    $push->sendToToken($t, $title, $body, ['type' => 'test']);
                }
            }

            return response()->json(['sent' => true]);
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
}
