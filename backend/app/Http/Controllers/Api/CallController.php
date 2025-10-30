<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;   // 👈 ye add karo
use Illuminate\Http\Request;
use App\Events\CallRequested;
use App\Models\CallSession;
use App\Support\CallSessionUrlBuilder;

class CallController extends Controller
{
    public function requestCall(Request $request)
    {
        $data = $request->validate([
            'doctor_id'    => 'required|integer',
            'patient_id'   => 'required|integer',
            'channel_name' => 'nullable|string|max:64',
            'call_id'      => 'nullable|string|max:64',
        ]);

        $callIdentifier = CallSessionUrlBuilder::ensureIdentifier($data['call_id'] ?? null);
        $channelName = CallSessionUrlBuilder::ensureChannel($data['channel_name'] ?? null, $callIdentifier);

        $session = CallSession::query()
            ->where('call_identifier', $callIdentifier)
            ->orWhere('channel_name', $channelName)
            ->first();

        if (!$session) {
            $session = new CallSession();
            $session->status = 'pending';
            $session->payment_status = 'unpaid';
            $session->currency = 'INR';
        }

        $session->patient_id = $data['patient_id'];
        $session->doctor_id = $data['doctor_id'];
        $session->channel_name = $channelName;
        $session->call_identifier = $callIdentifier;
        $session->currency = $session->currency ?? 'INR';
        $session->status = $session->status ?? 'pending';
        $session->payment_status = $session->payment_status ?? 'unpaid';

        $session->refreshComputedLinks();
        $session->save();

        // Event fire
        event(new CallRequested($session->doctor_id, $session->patient_id, $session->channel_name, $session->id));

        return response()->json([
            'success' => true,
            'message' => 'Call requested',
            'data' => [
                'doctor_id'   => $session->doctor_id,
                'patient_id'  => $session->patient_id,
                'channel'     => $session->channel_name,
                'call_id'     => $session->call_identifier,
                'doctor_join_url' => $session->doctor_join_url,
                'patient_payment_url' => $session->patient_payment_url,
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

        $callIdentifier = CallSessionUrlBuilder::generateIdentifier();
        $channelName = CallSessionUrlBuilder::defaultChannel($callIdentifier);

        $session = new CallSession([
            'doctor_id'     => $doctorId,
            'patient_id'    => $patientId,
            'channel_name'  => $channelName,
            'call_identifier' => $callIdentifier,
            'currency'      => 'INR',
            'status'        => 'pending',
            'payment_status'=> 'unpaid',
        ]);

        $session->refreshComputedLinks();
        $session->save();

        event(new CallRequested($session->doctor_id, $session->patient_id, $session->channel_name, $session->id));

        return response()->json([
            'success' => true,
            'message' => 'Test call requested',
            'data' => [
                'doctor_id'  => $session->doctor_id,
                'patient_id' => $session->patient_id,
                'channel'    => $session->channel_name,
                'call_id'    => $session->call_identifier,
                'doctor_join_url' => $session->doctor_join_url,
                'patient_payment_url' => $session->patient_payment_url,
                'session_id' => $session->id,
            ],
        ]);
    }
}
