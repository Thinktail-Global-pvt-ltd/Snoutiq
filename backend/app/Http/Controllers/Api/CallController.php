<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;   // 👈 ye add karo
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Events\CallRequested;
use App\Models\CallSession;

class CallController extends Controller
{
    public function requestCall(Request $request)
    {
        $data = $request->validate([
            'doctor_id'    => 'required|integer',
            'patient_id'   => 'required|integer',
            'channel_name' => 'nullable|string|max:64',
        ]);

        $channelName = $data['channel_name'] ?? null;
        if (is_string($channelName)) {
            $channelName = substr(preg_replace('/[^A-Za-z0-9_\-]/', '', $channelName), 0, 63);
            if ($channelName === '') {
                $channelName = null;
            }
        }

        if (!$channelName) {
            $channelName = 'call_' . Str::random(10);
        }

        $session = CallSession::where('channel_name', $channelName)->first();

        if (!$session) {
            $session = CallSession::create([
                'patient_id'     => $data['patient_id'],
                'doctor_id'      => $data['doctor_id'],
                'channel_name'   => $channelName,
                'currency'       => 'INR',
                'status'         => 'pending',
                'payment_status' => 'unpaid',
            ]);
        } else {
            $session->fill([
                'patient_id' => $data['patient_id'],
                'doctor_id'  => $data['doctor_id'],
            ]);

            if (!$session->currency) {
                $session->currency = 'INR';
            }

            if (!$session->status) {
                $session->status = 'pending';
            }

            if (!$session->payment_status) {
                $session->payment_status = 'unpaid';
            }

            $session->save();
        }

        // Event fire
        event(new CallRequested($session->doctor_id, $session->patient_id, $session->channel_name, $session->id));

        return response()->json([
            'success' => true,
            'message' => 'Call requested',
            'data' => [
                'doctor_id'   => $session->doctor_id,
                'patient_id'  => $session->patient_id,
                'channel'     => $session->channel_name,
                'session_id'  => $session->id,
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

        $session = CallSession::create([
            'doctor_id'     => $doctorId,
            'patient_id'    => $patientId,
            'channel_name'  => $channelName,
            'currency'      => 'INR',
            'status'        => 'pending',
            'payment_status'=> 'unpaid',
        ]);

        event(new CallRequested($session->doctor_id, $session->patient_id, $session->channel_name, $session->id));

        return response()->json([
            'success' => true,
            'message' => 'Test call requested',
            'data' => [
                'doctor_id'  => $session->doctor_id,
                'patient_id' => $session->patient_id,
                'channel'    => $session->channel_name,
                'session_id' => $session->id,
            ],
        ]);
    }
}
