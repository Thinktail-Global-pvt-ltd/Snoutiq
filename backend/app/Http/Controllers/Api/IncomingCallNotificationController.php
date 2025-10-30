<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Models\DoctorPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomingCallNotificationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
        ]);

        $subscription = DoctorPushSubscription::query()
            ->where('endpoint', $validated['endpoint'])
            ->first();

        if (!$subscription) {
            return response()->json(['call' => null]);
        }

        $session = CallSession::query()
            ->with('patient')
            ->where('doctor_id', $subscription->doctor_id)
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        if (!$session || !$session->push_notified_at) {
            return response()->json(['call' => null]);
        }

        $expiresAt = $session->push_notified_at->copy()->addMinutes(5);

        if ($expiresAt->isPast()) {
            return response()->json(['call' => null]);
        }

        $patientName = optional($session->patient)->name ?? 'Incoming caller';

        return response()->json([
            'call' => [
                'session_id' => $session->id,
                'call_identifier' => $session->call_identifier,
                'channel_name' => $session->channel_name,
                'doctor_join_url' => $session->doctor_join_url,
                'patient_id' => $session->patient_id,
                'patient_name' => $patientName,
                'expires_at' => $expiresAt->toIso8601String(),
                'ringtone_url' => url('/ringtone.mp3'),
            ],
        ]);
    }
}
