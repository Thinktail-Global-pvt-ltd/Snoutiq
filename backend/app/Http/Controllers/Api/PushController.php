<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendFcmMessage;
use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

class PushController extends Controller
{
    public function registerToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'string', Rule::in(['android', 'ios', 'web'])],
            'device_id' => ['nullable', 'string', 'max:128'],
            'meta' => ['nullable', 'array'],
        ]);

        try {
            $token = DeviceToken::updateOrCreate(
                ['token' => $validated['token']],
                [
                    'user_id' => Auth::id(),
                    'platform' => $validated['platform'] ?? null,
                    'device_id' => $validated['device_id'] ?? null,
                    'meta' => $validated['meta'] ?? null,
                    'last_seen_at' => now(),
                ]
            );

            return response()->json([
                'ok' => true,
                'id' => $token->id,
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
