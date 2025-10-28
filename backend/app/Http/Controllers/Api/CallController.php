<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;   // 👈 ye add karo
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Events\CallRequested;

class CallController extends Controller
{
    public function requestCall(Request $request)
    {
        $doctorId = $request->doctor_id;
        $patientId = $request->patient_id;

        // Random channel generate
        $channelName = 'call_' . Str::random(10);

        // Event fire
        event(new CallRequested($doctorId, $patientId, $channelName));

        return response()->json([
            'success' => true,
            'message' => 'Call requested',
            'data' => [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'channel' => $channelName,
            ]
        ]);
    }

    public function requestTestCall(Request $request)
    {
        $validated = $request->validate([
            'doctor_id'  => 'required|integer',
            'patient_id' => 'nullable|integer',
        ]);

        $doctorId = $validated['doctor_id'];
        $patientId = $validated['patient_id'] ?? 99999;

        $channelName = 'test_call_' . Str::random(10);

        event(new CallRequested($doctorId, $patientId, $channelName));

        return response()->json([
            'success' => true,
            'message' => 'Test call requested',
            'data' => [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'channel' => $channelName,
            ],
        ]);
    }
}
