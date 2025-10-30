<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorPushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|integer|exists:doctors,id',
            'subscription.endpoint' => 'required|url',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string',
            'subscription.expirationTime' => 'nullable',
            'user_agent' => 'nullable|string',
            'platform' => 'nullable|string',
        ]);

        $subscriptionData = $validated['subscription'];

        $record = DoctorPushSubscription::updateOrCreate(
            [
                'doctor_id' => $validated['doctor_id'],
                'endpoint' => $subscriptionData['endpoint'],
            ],
            [
                'public_key' => $subscriptionData['keys']['p256dh'],
                'auth_token' => $subscriptionData['keys']['auth'],
                'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
                'platform' => $validated['platform'] ?? $request->input('platform'),
            ],
        );

        return response()->json([
            'status' => 'ok',
            'subscription_id' => $record->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|integer',
            'endpoint' => 'required|url',
        ]);

        DoctorPushSubscription::query()
            ->where('doctor_id', $validated['doctor_id'])
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['status' => 'ok']);
    }
}
