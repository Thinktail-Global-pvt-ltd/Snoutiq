<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Services\CallRecordingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CallRecordingController extends Controller
{
    public function __construct(private readonly CallRecordingManager $recordingManager)
    {
    }

    public function start(Request $request, int $sessionId): JsonResponse
    {
        $data = $request->validate([
            'uid' => 'nullable|string|max:32',
            'token' => 'nullable|string',
            'resource_expire_hours' => 'nullable|integer|min:1|max:168',
        ]);

        $session = CallSession::findOrFail($sessionId);

        if (!$session->channel_name) {
            throw ValidationException::withMessages([
                'channel_name' => 'Channel name is required on the call session before starting recording.',
            ]);
        }

        if ($session->hasActiveRecording()) {
            return response()->json([
                'success' => true,
                'session' => $session->fresh(),
                'message' => 'Recording already active for this session.',
            ], 200);
        }

        $result = $this->recordingManager->start($session, $data);

        return response()->json(array_merge(['success' => true], $result), $result['status'] === 'recording' ? 201 : 200);
    }

    public function stop(Request $request, int $sessionId): JsonResponse
    {
        $session = CallSession::findOrFail($sessionId);

        $options = [];
        if ($request->filled('uid')) {
            $options['uid'] = $request->input('uid');
        }
        if ($request->has('transcribe')) {
            $options['transcribe'] = $request->boolean('transcribe');
        }

        $result = $this->recordingManager->stop($session, $options);

        return response()->json(array_merge(['success' => true], $result));
    }

    public function status(Request $request, int $sessionId): JsonResponse
    {
        $session = CallSession::findOrFail($sessionId);

        if (!$session->recording_resource_id || !$session->recording_sid) {
            return response()->json([
                'success' => false,
                'message' => 'Recording has not been started for this session.',
            ], 404);
        }

        $options = [];
        if ($request->filled('uid')) {
            $options['uid'] = $request->input('uid');
        }

        $result = $this->recordingManager->status($session, $options);

        return response()->json(array_merge(['success' => true], $result));
    }

    public function requestTranscript(Request $request, int $sessionId): JsonResponse
    {
        $data = $request->validate([
            'recording_url' => 'nullable|string',
        ]);

        $session = CallSession::findOrFail($sessionId);

        $this->recordingManager->queueTranscript($session, $data['recording_url'] ?? $session->recording_url);

        return response()->json([
            'success' => true,
            'session' => $session->fresh(),
        ]);
    }
}
