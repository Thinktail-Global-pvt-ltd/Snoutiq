<?php

namespace App\Http\Controllers\Api;

use App\Events\CallStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Services\CallRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function request(Request $request, CallRoutingService $service): JsonResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|integer',
            'channel' => 'nullable|string',
            'rtc' => 'nullable|array',
        ]);

        $patientId = $data['patient_id'];
        $doctorId = $service->assignDoctor();

        if (! $doctorId) {
            return response()->json([
                'ok' => false,
                'message' => 'No doctors online',
            ], 409);
        }

        $call = $service->createCall($doctorId, $patientId, $data['channel'] ?? null, $data['rtc'] ?? null);

        return response()->json([
            'ok' => true,
            'call_id' => $call->id,
            'doctor_id' => $doctorId,
            'status' => $call->status,
        ]);
    }

    public function accept(Call $call, CallRoutingService $service): JsonResponse
    {
        if ($call->status !== Call::STATUS_RINGING) {
            return response()->json(['ok' => false, 'message' => 'Call not ringing'], 409);
        }

        $service->markAccepted($call);

        return response()->json(['ok' => true, 'status' => $call->status]);
    }

    public function reject(Call $call, CallRoutingService $service): JsonResponse
    {
        if ($call->status !== Call::STATUS_RINGING) {
            return response()->json(['ok' => false, 'message' => 'Call not ringing'], 409);
        }

        $service->markRejected($call);

        return response()->json(['ok' => true, 'status' => $call->status]);
    }

    public function end(Call $call, CallRoutingService $service): JsonResponse
    {
        if (! in_array($call->status, [Call::STATUS_ACCEPTED, Call::STATUS_RINGING, Call::STATUS_PENDING])) {
            return response()->json(['ok' => false, 'message' => 'Call not active'], 409);
        }

        $service->markEnded($call);

        return response()->json(['ok' => true, 'status' => $call->status]);
    }

    public function cancel(Call $call, CallRoutingService $service): JsonResponse
    {
        if (! in_array($call->status, [Call::STATUS_RINGING, Call::STATUS_PENDING])) {
            return response()->json(['ok' => false, 'message' => 'Call not cancelable'], 409);
        }

        $service->markCancelled($call);

        return response()->json(['ok' => true, 'status' => $call->status]);
    }
}
