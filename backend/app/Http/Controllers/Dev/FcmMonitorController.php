<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\PushRunDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FcmMonitorController extends Controller
{
    /**
     * Page to monitor a specific FCM token's delivery attempts.
     */
    public function index(): View
    {
        return view('dev.fcm-monitor');
    }

    /**
     * Return the latest delivery attempts for the supplied token.
     */
    public function status(Request $request): JsonResponse
    {
        $token = trim((string) $request->query('token', ''));

        if ($token === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Token parameter is required.',
            ], 422);
        }

        $deviceToken = DeviceToken::query()
            ->select(['id', 'user_id', 'token', 'platform', 'device_id', 'last_seen_at', 'updated_at'])
            ->where('token', $token)
            ->first();

        if (! $deviceToken) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No device found with that FCM token.',
            ]);
        }

        $deviceLabel = $deviceToken->device_id ?: 'device-token-'.$deviceToken->id;

        $deliveries = PushRunDelivery::query()
            ->with(['run:id,title,trigger,started_at'])
            ->where('device_id', $deviceLabel)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (PushRunDelivery $delivery) => [
                'id' => $delivery->id,
                'status' => $delivery->status,
                'error_code' => $delivery->error_code,
                'error_message' => $delivery->error_message,
                'run' => $delivery->run ? [
                    'id' => $delivery->run->id,
                    'title' => $delivery->run->title,
                    'trigger' => $delivery->run->trigger,
                    'started_at' => optional($delivery->run->started_at)->toIso8601String(),
                ] : null,
                'created_at' => optional($delivery->created_at)->toIso8601String(),
            ]);

        return response()->json([
            'status' => 'ok',
            'device' => [
                'id' => $deviceToken->id,
                'label' => $deviceLabel,
                'platform' => $deviceToken->platform,
                'user_id' => $deviceToken->user_id,
                'last_seen_at' => optional($deviceToken->last_seen_at)->toIso8601String(),
                'updated_at' => optional($deviceToken->updated_at)->toIso8601String(),
            ],
            'deliveries' => $deliveries,
        ]);
    }
}
