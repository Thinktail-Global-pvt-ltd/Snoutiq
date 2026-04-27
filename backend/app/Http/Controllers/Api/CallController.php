<?php

namespace App\Http\Controllers\Api;

use App\Events\CallStatusUpdated;
use App\Helpers\RtcTokenBuilder;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Services\CallRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CallController extends Controller
{
    public function request(Request $request, CallRoutingService $service): JsonResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|integer',
            'channel' => 'nullable|string',
            'channel_name' => 'nullable|string|max:255',
            'rtc' => 'nullable|array',
        ]);

        $patientId = $data['patient_id'];
        $doctorId = $service->assignDoctor();

        if (! $doctorId) {
            return response()->json([
                'ok' => false,
                'message' => 'No doctors online',
            ], 409);
        }

        $channelName = $this->resolveChannelName(
            $data['channel_name'] ?? null,
            $data['channel'] ?? null
        );

        $doctorToken = $this->buildAgoraToken($channelName, (int) $doctorId);

        $call = $service->createCall(
            $doctorId,
            $patientId,
            $channelName,
            $channelName,
            $data['rtc'] ?? null
        );

        return response()->json([
            'ok' => true,
            'call_id' => $call->id,
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'status' => $call->status,
            'channel' => $channelName,
            'channel_name' => $channelName,
            'channelName' => $channelName,
            'uid' => (int) $doctorId,
            'appId' => $doctorToken['appId'],
            'token' => $doctorToken['token'],
            'agoraToken' => $doctorToken['token'],
            'agora_token' => $doctorToken['token'],
            'expiresIn' => $doctorToken['expiresIn'],
        ]);
    }

    public function accept(Call $call, CallRoutingService $service): JsonResponse
    {
        if ($call->status !== Call::STATUS_RINGING) {
            return response()->json(['ok' => false, 'message' => 'Call not ringing'], 409);
        }

        $service->markAccepted($call);

        $channelName = $this->resolveChannelName($call->channel_name, $call->channel);
        $doctorToken = $this->buildAgoraToken($channelName, (int) $call->doctor_id);

        return response()->json([
            'ok' => true,
            'call_id' => $call->id,
            'doctor_id' => $call->doctor_id,
            'patient_id' => $call->patient_id,
            'status' => $call->status,
            'channel' => $channelName,
            'channel_name' => $channelName,
            'channelName' => $channelName,
            'uid' => (int) $call->doctor_id,
            'appId' => $doctorToken['appId'],
            'token' => $doctorToken['token'],
            'agoraToken' => $doctorToken['token'],
            'agora_token' => $doctorToken['token'],
            'expiresIn' => $doctorToken['expiresIn'],
        ]);
    }

    public function reject(Call $call, CallRoutingService $service): JsonResponse
    {
        if ($call->status !== Call::STATUS_RINGING) {
            return response()->json(['ok' => false, 'message' => 'Call not ringing'], 409);
        }

        $service->markRejected($call);

        return response()->json(['ok' => true, 'status' => $call->status]);
    }

    public function end(Call $call, CallRoutingService $service): JsonResponse
    {
        if (! in_array($call->status, [Call::STATUS_ACCEPTED, Call::STATUS_RINGING, Call::STATUS_PENDING])) {
            return response()->json(['ok' => false, 'message' => 'Call not active'], 409);
        }

        $service->markEnded($call);

        return response()->json(['ok' => true, 'status' => $call->status]);
    }

    public function cancel(Call $call, CallRoutingService $service): JsonResponse
    {
        if (! in_array($call->status, [Call::STATUS_RINGING, Call::STATUS_PENDING])) {
            return response()->json(['ok' => false, 'message' => 'Call not cancelable'], 409);
        }

        $service->markCancelled($call);

        return response()->json(['ok' => true, 'status' => $call->status]);
    }

    private function resolveChannelName(?string $channelName, ?string $channel): string
    {
        $candidate = trim((string) ($channelName ?: $channel));

        if ($candidate === '' || in_array(strtolower($candidate), ['video', 'call', 'agora'], true)) {
            $candidate = 'channel_' . Str::random(12);
        }

        return substr(preg_replace('/[^a-zA-Z0-9_]/', '', $candidate) ?: 'channel_' . Str::random(12), 0, 64);
    }

    private function buildAgoraToken(string $channelName, int $uid): array
    {
        $appId = trim((string) config('services.agora.app_id', ''));
        $appCertificate = trim((string) config('services.agora.certificate', ''));

        abort_if($appId === '' || $appCertificate === '', 500, 'Agora credentials are not configured.');

        $expiresIn = 3600;
        $privilegeExpiredTs = time() + $expiresIn;

        return [
            'token' => RtcTokenBuilder::buildTokenWithUid(
                $appId,
                $appCertificate,
                $channelName,
                $uid,
                RtcTokenBuilder::RolePublisher,
                $privilegeExpiredTs
            ),
            'appId' => $appId,
            'expiresIn' => $expiresIn,
        ];
    }
}
