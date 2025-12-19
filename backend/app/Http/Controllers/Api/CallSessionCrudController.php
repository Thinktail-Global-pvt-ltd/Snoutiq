<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Support\CallSessionUrlBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallSessionCrudController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CallSession::query();

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', (int) $request->input('doctor_id'));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', (int) $request->input('patient_id'));
        }

        if ($request->filled('call_session')) {
            $sessionKey = $request->input('call_session');

            $query->where(function ($inner) use ($sessionKey) {
                $inner->where('channel_name', $sessionKey);

                if (CallSession::supportsColumn('call_identifier')) {
                    $inner->orWhere('call_identifier', $sessionKey);
                }
            });
        }

        $sessions = $query
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (CallSession $session) => $this->formatSession($session));

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'doctor_id'    => ['required', 'integer'],
            'patient_id'   => ['required', 'integer'],
            'call_session' => ['required', 'string', 'max:' . CallSessionUrlBuilder::CHANNEL_MAX_LENGTH],
        ]);

        $identifier = CallSessionUrlBuilder::ensureIdentifier($data['call_session']);
        $channelName = CallSessionUrlBuilder::ensureChannel($data['call_session'], $identifier);

        $session = new CallSession();
        $session->doctor_id = $data['doctor_id'];
        $session->patient_id = $data['patient_id'];
        $session->channel_name = $channelName;
        $session->useCallIdentifier($identifier);

        // Ensure required defaults while leaving everything else nullable.
        $session->status = $session->status ?? 'pending';
        $session->payment_status = $session->payment_status ?? 'unpaid';
        $session->currency = $session->currency ?? 'INR';
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Call session created.',
            'data' => $this->formatSession($session),
        ], 201);
    }

    public function show(CallSession $callSession): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatSession($callSession),
        ]);
    }

    private function formatSession(CallSession $session): array
    {
        return [
            'id' => $session->id,
            'doctor_id' => $session->doctor_id,
            'patient_id' => $session->patient_id,
            'call_session' => $session->resolveIdentifier(),
            'channel_name' => $session->channel_name,
            'status' => $session->status,
            'payment_status' => $session->payment_status,
            'currency' => $session->currency,
            'created_at' => optional($session->created_at)->toIso8601String(),
            'updated_at' => optional($session->updated_at)->toIso8601String(),
        ];
    }
}
