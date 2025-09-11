<?php
// app/Http/Controllers/AgoraController.php
namespace App\Http\Controllers;
 use App\Helpers\RtcTokenBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class AgoraController extends Controller
{
    public function generateToken(Request $request)
    {
        $appID ='b13636f3f07448e2bf6778f5bc2c506f';
        $appCertificate = 'c30ae10e278c490f9b09608b15c353ba';


        $channelName = $request->input('channel');
        $uid = $request->input('uid') ?? rand(1, 999999);
        $role = $request->input('role') ?? 'publisher'; // optional

        if (!$appID || !$appCertificate) {
            return response()->json(['error' => 'Agora credentials missing'], 500);
        }

        if (!$channelName) {
            return response()->json(['error' => 'Channel name required'], 400);
        }

        // Token expiry (1 hour)
        $expireTimeInSeconds = 3600;
        $currentTimestamp = now()->timestamp;
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        try {
            // use agora access token builder
            // $token = \App\Helpers\RtcTokenBuilder::buildTokenWithUid(
            //     $appID,
            //     $appCertificate,
            //     $channelName,
            //     $uid,
            //     $role === 'publisher'
            //         ? \App\Helpers\RtcTokenBuilder::ROLE_PUBLISHER
            //         : \App\Helpers\RtcTokenBuilder::ROLE_SUBSCRIBER,
            //     $privilegeExpiredTs
            // );
           

$token = RtcTokenBuilder::buildTokenWithUid(
    $appID,
    $appCertificate,
    $channelName,
    $uid,
    RtcTokenBuilder::Role_Publisher,
    $privilegeExpiredTs
);

            return response()->json(['token' => $token, 'uid' => $uid]);
        } catch (\Throwable $e) {
            Log::error("Agora token error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to generate token'], 500);
        }
    }

}

