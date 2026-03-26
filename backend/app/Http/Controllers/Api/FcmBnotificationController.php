<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\FcmNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FcmBnotificationController extends Controller
{
    /**
     * POST /api/fcm-notifications/click
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fcm_notification_id' => ['nullable', 'integer'],
            'notification_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'device_token_id' => ['nullable', 'integer'],
            'token' => ['nullable', 'string', 'max:500'],
            'clicked' => ['nullable', 'boolean'],
            'clicked_at' => ['nullable', 'date'],
            'app_state' => ['nullable', 'string', 'max:32'],
            'action' => ['nullable', 'string', 'max:191'],
            'payload' => ['nullable', 'array'],
        ]);

        if (!Schema::hasTable('fcm_notifications') || !Schema::hasColumn('fcm_notifications', 'clicked')) {
            return response()->json([
                'success' => false,
                'message' => 'fcm_notifications.click columns are missing. Run migrations.',
            ], 500);
        }

        $deviceTokenId = $validated['device_token_id'] ?? null;
        $userId = $validated['user_id'] ?? null;

        if (empty($deviceTokenId) && !empty($validated['token'])) {
            $token = trim(trim((string) $validated['token']), "\"'");
            if ($token !== '') {
                $deviceToken = DeviceToken::query()->where('token', $token)->first();
                if ($deviceToken) {
                    $deviceTokenId = $deviceToken->id;
                    if (empty($userId)) {
                        $userId = $deviceToken->user_id;
                    }
                }
            }
        }

        $clicked = array_key_exists('clicked', $validated) ? (bool) $validated['clicked'] : true;
        $clickedAt = null;
        if ($clicked) {
            $clickedAt = !empty($validated['clicked_at'])
                ? \Carbon\Carbon::parse($validated['clicked_at'])
                : now();
        }

        $fcmRow = null;
        if (!empty($validated['fcm_notification_id'])) {
            $fcmRow = FcmNotification::query()->whereKey((int) $validated['fcm_notification_id'])->first();
        }

        if (!$fcmRow && !empty($validated['notification_id'])) {
            $notifId = (int) $validated['notification_id'];
            $query = FcmNotification::query()
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data_payload, '$.notification_id')) = ?", [(string) $notifId]);
            if (!empty($userId)) {
                $query->where('user_id', (int) $userId);
            }
            $fcmRow = $query->orderByDesc('id')->first();
        }

        if (!$fcmRow && !empty($userId)) {
            $fcmRow = FcmNotification::query()
                ->where('user_id', (int) $userId)
                ->orderByDesc('id')
                ->first();
        }

        if (!$fcmRow) {
            return response()->json([
                'success' => false,
                'message' => 'FCM notification not found',
            ], 404);
        }

        try {
            $fcmRow->clicked = $clicked;
            $fcmRow->clicked_at = $clickedAt;
            $fcmRow->save();

            return response()->json([
                'success' => true,
                'fcm_notification_id' => $fcmRow->id,
                'clicked' => (bool) $fcmRow->clicked,
                'clicked_at' => optional($fcmRow->clicked_at)->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('fcm_notification.click_update_failed', [
                'error' => $e->getMessage(),
                'payload' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update click event',
            ], 500);
        }
    }
}
