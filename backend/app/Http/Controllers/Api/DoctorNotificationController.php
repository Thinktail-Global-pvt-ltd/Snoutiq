<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Services\DoctorNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DoctorNotificationController extends Controller
{
    public function __construct(private readonly DoctorNotificationService $notifications)
    {
    }

    public function pendingCall(Request $request): JsonResponse
    {
        $this->guardSecret($request);

        $validated = $request->validate([
            'doctor_id'  => 'required|integer|exists:doctors,id',
            'patient_id' => 'nullable|integer',
            'call_id'    => 'required|string',
            'channel'    => 'nullable|string',
            'message'    => 'nullable|string',
        ]);

        $doctor = Doctor::findOrFail($validated['doctor_id']);

        $patientId = $validated['patient_id'] ?? 'a patient';
        $callId = $validated['call_id'];
        $channel = $validated['channel'] ?? 'video consult';

        $body = $validated['message'] ?? sprintf(
            "Incoming call alert! Patient %s is trying to reach you on SnoutIQ (%s). Call ID: %s",
            $patientId,
            $channel,
            $callId
        );

        try {
            $this->notifications->sendPendingCallAlert($doctor, $body);
        } catch (RuntimeException $exception) {
            Log::error('doctor.pending-call.notification.failed', [
                'doctor_id' => $doctor->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send notification',
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }

    private function guardSecret(Request $request): void
    {
        $expected = config('services.notifications.secret');
        if (!$expected) {
            return;
        }

        $provided = $request->header('X-Notification-Key');
        if (!hash_equals($expected, (string) $provided)) {
            abort(403, 'Invalid notification secret');
        }
    }
}
