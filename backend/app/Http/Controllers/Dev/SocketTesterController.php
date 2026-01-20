<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocketTesterController extends Controller
{
    public function index()
    {
        return view('dev.socket-tester');
    }

    public function doctor(Request $request)
    {
        return $this->peer('doctor', $request, 1, 2);
    }

    public function patient(Request $request)
    {
        return $this->peer('patient', $request, 2, 1);
    }

    protected function peer(string $role, Request $request, int $defaultSelfId, int $defaultPeerId)
    {
        return view('dev.socket-peer', [
            'role' => $role,
            'selfId' => $request->integer('id', $defaultSelfId),
            'peerId' => $request->integer('peer', $defaultPeerId),
            'callSession' => $request->input('call', 'call_test'),
            'channel' => $request->input('channel', 'video'),
        ]);
    }
}
