<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CallRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function heartbeat(Request $request, CallRoutingService $service): JsonResponse
    {
        // Allow unauthenticated usage for dev: prefer auth user, else accept doctor_id in body/query.
        $doctorId = optional($request->user())->id ?? (int) $request->input('doctor_id', $request->input('doctorId', 0));
        if (! $doctorId) {
            return response()->json(['message' => 'Missing doctor id'], 422);
        }

        $service->markDoctorOnline((int) $doctorId);

        return response()->json([
            'ok' => true,
            'doctor_id' => (int) $doctorId,
            'ttl' => (int) config('calls.presence_ttl', 70),
        ]);
    }
}
