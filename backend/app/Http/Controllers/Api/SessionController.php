<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    // POST /api/session/login
    // Body: { user_id: int }
    public function loginWithUserId(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
        ]);

        $uid = (int) $validated['user_id'];
        session(['user_id' => $uid]);

        return response()->json([
            'success'    => true,
            'message'    => 'User ID stored in session',
            'user_id'    => session('user_id'),
            'session_id' => $request->session()->getId(),
        ]);
    }

    // GET /api/session/login?user_id=101
    public function loginWithUserIdGet(Request $request)
    {
        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'user_id query param is required and must be > 0',
            ], 422);
        }
        session(['user_id' => $userId]);
        return response()->json([
            'success'    => true,
            'message'    => 'User ID stored in session (GET)',
            'user_id'    => session('user_id'),
            'session_id' => $request->session()->getId(),
        ]);
    }

    // POST /api/session/save
    // Body: { key?: string (default: "auth_full"), data: object }
    public function save(Request $request)
    {
        $validated = $request->validate([
            'key'  => 'nullable|string',
            'data' => 'required|array',
        ]);

        $key = $validated['key'] ?? 'auth_full';
        session([$key => $validated['data']]);

        return response()->json([
            'success' => true,
            'message' => 'Session value saved',
            'key'     => $key,
            'data'    => session($key),
            'session_id' => $request->session()->getId(),
        ]);
    }

    // GET /api/session/get?key=auth_full
    public function get(Request $request)
    {
        $key = $request->query('key');
        if ($key) {
            return response()->json([
                'success' => true,
                'key'     => $key,
                'data'    => session($key),
            ]);
        }

        // Return a safe subset of the session
        $whitelist = ['auth_full', 'user', 'role', 'token', 'chat_room'];
        $payload = [];
        foreach ($whitelist as $k) {
            if (session()->has($k)) {
                $payload[$k] = session($k);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $payload,
        ]);
    }

    // POST /api/session/clear (body: { key?: string })
    public function clear(Request $request)
    {
        $key = $request->input('key');
        if ($key) {
            $request->session()->forget($key);
            return response()->json(['success' => true, 'message' => "Cleared key '{$key}'"]);
        }
        $request->session()->flush();
        return response()->json(['success' => true, 'message' => 'Session cleared']);
    }
}
