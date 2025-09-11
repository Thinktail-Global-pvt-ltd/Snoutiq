<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AgoraTokenService;

class AgoraController extends Controller
{
    protected $agora;

    public function __construct(AgoraTokenService $agora)
    {
        $this->agora = $agora;
    }

    public function generateToken(Request $request)
    {
        try {
            $channel = $request->input('channel');
            $uid = (int) ($request->input('uid') ?? rand(1, 999999));
            $role = AgoraTokenService::ROLE_PUBLISHER;

            if (!$channel) {
                return response()->json(['error' => 'Channel name is required'], 400);
            }

            $token = $this->agora->generateToken($channel, $uid, $role);

            return response()->json([
                'token' => $token,
                'uid' => $uid,
            ]);
        } catch (\Throwable $e) {
            Log::error("Agora token error", ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate token'], 500);
        }
    }
}
