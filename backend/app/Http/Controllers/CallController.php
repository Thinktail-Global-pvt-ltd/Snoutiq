<?php
// app/Http/Controllers/CallController.php
namespace App\Http\Controllers;

use App\Models\CallSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\RtcTokenBuilder;
class CallController extends Controller
{
public function createSession(Request $request)
{
    $session = CallSession::create([
        'patient_id'   => $request->patient_id,
        'channel_name' => 'call_' . \Illuminate\Support\Str::random(8),
    ]);

    return response()->json([
        'session_id'     => $session->id,        // ✅ session id explicitly return karo
        'patient_id'     => $session->patient_id,
        'channel_name'   => $session->channel_name,
        'status'         => $session->status,
        'payment_status' => $session->payment_status,
        'created_at'     => $session->created_at,
    ]);
}


    // 2. Doctor accepts call
    public function acceptCall(Request $request, $sessionId)
{
    $session = \App\Models\CallSession::findOrFail($sessionId);

    // lock the session if still pending/empty
    if ($session->status !== 'accepted') {
        $session->doctor_id = (int) $request->doctor_id;
        $session->status = 'accepted';
        $session->save();
    }

    \Log::info('ACCEPT: broadcasting CallAccepted on channel call-session-'.$session->id.' with app_key='.config('broadcasting.connections.pusher.key'));

    // 👇 fire event NOW
    event(new \App\Events\CallAccepted($session));

    return response()->json([
        'success' => true,
        'session' => $session,
    ]);
}


    // 3. Payment success
    public function paymentSuccess(Request $request, $sessionId)
    {
        $session = CallSession::findOrFail($sessionId);
        $session->payment_status = 'paid';
        $session->save();

        // Broadcast event -> patient + doctor join call
        event(new \App\Events\PaymentDone($session));

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $session = CallSession::findOrFail($id);

        // React side dono shapes handle karta hai, par clean JSON bhejte hain:
        return response()->json([
            'id'             => $session->id,
            'patient_id'     => $session->patient_id,
            'doctor_id'      => $session->doctor_id,
            'channel_name'   => $session->channel_name,
            'status'         => $session->status,          // 'pending' | 'accepted' | ...
            'payment_status' => $session->payment_status,  // 'unpaid'  | 'paid'
            'created_at'     => $session->created_at,
            'updated_at'     => $session->updated_at,
        ]);
    }







      public function generateToken(Request $request)
    {
        $appId          = "b13636f3f07448e2bf6778f5bc2c506f";
        $appCertificate = "c30ae10e278c490f9b09608b15c353ba";

        // ✅ channel_name optional rakho, default generate ho jaayega
        $channelName = $request->input('channel_name');
        if (empty($channelName)) {
            $channelName = 'call_' . Str::random(8);
        }

        // ✅ uid optional rakho
        $uid = (int) ($request->input('uid') ?? random_int(1000, 999999));

        $role = RtcTokenBuilder::RolePublisher;
        $expireTimeInSeconds = 3600;
        $privilegeExpiredTs = time() + $expireTimeInSeconds;

        $token = RtcTokenBuilder::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            $role,
            $privilegeExpiredTs
        );

        return response()->json([
            'success'     => true,
            'token'       => $token,
            'appId'       => $appId,
            'channelName' => $channelName,
            'uid'         => $uid,
            'expiresIn'   => $expireTimeInSeconds,
        ]);
    }
}



