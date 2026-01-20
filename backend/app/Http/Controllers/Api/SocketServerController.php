<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Socket\SocketStateService;

class SocketServerController extends Controller
{
    public function __construct(private readonly SocketStateService $socketState)
    {
    }

    public function health()
    {
        return response()->json($this->socketState->health());
    }

    public function activeDoctors()
    {
        return response()->json($this->socketState->activeDoctors());
    }

    public function storeCallSession(Request $request)
    {
        $data = $request->validate([
            'call_session' => 'required_without:callId|string|max:128',
            'callId' => 'nullable|string|max:128',
            'doctor_id' => 'nullable|integer',
            'patient_id' => 'nullable|integer',
            'channel' => 'nullable|string|max:128',
        ]);

        $session = $this->socketState->storeCallSession($data);

        return response()->json([
            'success' => true,
            'callId' => $session['callId'],
            'message' => 'Call session updated',
        ]);
    }

    public function getCallSession(Request $request)
    {
        $callId = $request->query('call_id');

        if ($callId) {
            $session = $this->socketState->getCallSession($callId);
        } else {
            $doctorId = $request->query('doctor_id');
            $patientId = $request->query('patient_id');
            $session = $this->socketState->findCallSession(
                $doctorId ? (int) $doctorId : null,
                $patientId ? (int) $patientId : null
            );
        }

        return response()->json($session);
    }
}
