<?php

namespace App\Http\Controllers;

use App\Events\CallAccepted;
use App\Events\PaymentDone;
use App\Helpers\RtcTokenBuilder;
use App\Models\CallSession;
use App\Models\Payment;
use App\Support\CallSessionUrlBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CallController extends Controller
{
    public function createSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id'   => 'required|integer',
            'doctor_id'    => 'nullable|integer',
            'channel_name' => 'nullable|string|max:64',
            'call_id'      => 'nullable|string|max:64',
        ]);

        $callIdentifier = CallSessionUrlBuilder::ensureIdentifier($request->input('call_id'));
        $requestedChannel = $data['channel_name'] ?? null;
        $channelName = CallSessionUrlBuilder::ensureChannel($requestedChannel, $callIdentifier);

        $sessionQuery = CallSession::query()
            ->where('channel_name', $channelName);

        if (CallSession::supportsColumn('call_identifier')) {
            $sessionQuery->orWhere('call_identifier', $callIdentifier);
        }

        $session = $sessionQuery->first();

        if (!$session) {
            $session = new CallSession();
            $session->status = 'pending';
            $session->payment_status = 'unpaid';
        }

        $session->patient_id = $data['patient_id'];
        if (array_key_exists('doctor_id', $data)) {
            $session->doctor_id = $data['doctor_id'];
        }

        $session->channel_name = $channelName;
        $session->useCallIdentifier($callIdentifier);
        $session->currency = $session->currency ?? 'INR';
        $session->status = $session->status ?? 'pending';
        $session->payment_status = $session->payment_status ?? 'unpaid';

        $session->refreshComputedLinks();
        $session->save();

        return response()->json($this->sessionPayload($session));
    }

    public function acceptCall(Request $request, int $sessionId): JsonResponse
    {
        $data = $request->validate([
            'doctor_id' => 'required|integer',
        ]);

        $session = CallSession::findOrFail($sessionId);
        $session->doctor_id = $data['doctor_id'];
        $session->status = 'accepted';
        $session->accepted_at ??= Carbon::now();
        $session->refreshComputedLinks();
        $session->save();

        Log::info('Call session accepted', [
            'session_id' => $session->id,
            'doctor_id'  => $session->doctor_id,
        ]);

        event(new CallAccepted($session->fresh()));

        return response()->json($this->sessionPayload($session));
    }

    public function paymentSuccess(Request $request, int $sessionId): JsonResponse
    {
        $data = $request->validate([
            'payment_db_id'        => 'nullable|integer',
            'payment_id'           => 'nullable|string',
            'razorpay_payment_id'  => 'nullable|string',
            'razorpay_order_id'    => 'nullable|string|max:191',
            'order_id'             => 'nullable|string|max:191',
            'razorpay_signature'   => 'nullable|string|max:191',
            'signature'            => 'nullable|string|max:191',
            'amount'               => 'nullable|integer|min:0',
            'currency'             => 'nullable|string|max:10',
            'status'               => 'nullable|string|max:50',
            'method'               => 'nullable|string|max:50',
            'email'                => 'nullable|string|max:191',
            'contact'              => 'nullable|string|max:30',
            'notes'                => 'nullable|array',
            'raw_response'         => 'nullable|array',
        ]);

        $session = CallSession::findOrFail($sessionId);

        $payment = null;
        if (!empty($data['payment_db_id'])) {
            $payment = Payment::find($data['payment_db_id']);
        }

        $paymentIdentifier = $data['payment_id'] ?? $data['razorpay_payment_id'] ?? null;
        if (!$payment && $paymentIdentifier) {
            $payment = Payment::where('razorpay_payment_id', $paymentIdentifier)->first();
        }

        $currency = strtoupper($data['currency'] ?? $session->currency ?? 'INR');
        $orderId = $data['razorpay_order_id'] ?? $data['order_id'] ?? null;
        $signature = $data['razorpay_signature'] ?? $data['signature'] ?? null;

        $paymentPayload = [];
        if (array_key_exists('amount', $data) && $data['amount'] !== null) {
            $paymentPayload['amount'] = $data['amount'];
        }
        $paymentPayload['currency'] = $currency;

        foreach (['status', 'method', 'email', 'contact'] as $field) {
            if (!empty($data[$field])) {
                $paymentPayload[$field] = $data[$field];
            }
        }

        $notes = [];
        if (!empty($data['notes']) && is_array($data['notes'])) {
            $notes = $data['notes'];
        }
        $notes['call_session_id'] = (string) $session->id;
        $paymentPayload['notes'] = $notes;

        if (!empty($data['raw_response']) && is_array($data['raw_response'])) {
            $paymentPayload['raw_response'] = $data['raw_response'];
        }

        if ($payment) {
            $payment->fill($paymentPayload);
            if ($orderId) {
                $payment->razorpay_order_id = $orderId;
            }
            if ($signature) {
                $payment->razorpay_signature = $signature;
            }
            $payment->save();
        } elseif ($paymentIdentifier && $orderId && $signature) {
            $payment = Payment::updateOrCreate(
                ['razorpay_payment_id' => $paymentIdentifier],
                array_merge($paymentPayload, [
                    'razorpay_order_id'  => $orderId,
                    'razorpay_signature' => $signature,
                ])
            );
        }

        if ($payment) {
            $session->payment_id = $payment->id;
            $session->amount_paid = $payment->amount;
            $session->currency = $payment->currency ?? $session->currency;
        }

        if (array_key_exists('amount', $data) && $data['amount'] !== null) {
            $session->amount_paid = $data['amount'];
        }

        if (!empty($data['currency'])) {
            $session->currency = $currency;
        }

        $session->payment_status = 'paid';
        $session->save();

        event(new PaymentDone($session->fresh()));

        return response()->json($this->sessionPayload($session));
    }

    public function show(int $id): JsonResponse
    {
        $session = CallSession::findOrFail($id);

        return response()->json($this->sessionPayload($session));
    }

    public function markStarted(Request $request, int $sessionId): JsonResponse
    {
        $data = $request->validate([
            'started_at' => 'nullable|date',
        ]);

        $session = CallSession::findOrFail($sessionId);
        $startedAt = !empty($data['started_at']) ? Carbon::parse($data['started_at']) : Carbon::now();

        $session->started_at = $session->started_at ?? $startedAt;
        $session->accepted_at = $session->accepted_at ?? $session->started_at;
        if ($session->status === 'pending') {
            $session->status = 'accepted';
        }
        $session->save();

        return response()->json($this->sessionPayload($session));
    }

    public function markEnded(Request $request, int $sessionId): JsonResponse
    {
        $data = $request->validate([
            'ended_at'          => 'nullable|date',
            'started_at'        => 'nullable|date',
            'duration_seconds'  => 'nullable|integer|min:0',
        ]);

        $session = CallSession::findOrFail($sessionId);

        if (!empty($data['started_at'])) {
            $session->started_at = Carbon::parse($data['started_at']);
        }

        $session->ended_at = !empty($data['ended_at'])
            ? Carbon::parse($data['ended_at'])
            : Carbon::now();

        if (!$session->started_at) {
            $session->started_at = $session->ended_at;
        }

        if (!empty($data['duration_seconds'])) {
            $session->duration_seconds = $data['duration_seconds'];
        } elseif ($session->started_at) {
            $session->duration_seconds = max(0, $session->started_at->diffInSeconds($session->ended_at));
        }

        $session->status = 'ended';
        $session->save();

        return response()->json($this->sessionPayload($session));
    }

    public function generateToken(Request $request): JsonResponse
    {
        $appId = config('services.agora.app_id', 'b13636f3f07448e2bf6778f5bc2c506f');
        $appCertificate = config('services.agora.certificate', 'c30ae10e278c490f9b09608b15c353ba');

        $channelName = $request->input('channel_name');
        if (empty($channelName)) {
            $channelName = 'call_' . Str::random(8);
        }

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

    protected function sessionPayload(CallSession $session, array $extra = []): array
    {
        $session = $session->fresh(['patient:id,name', 'doctor:id,doctor_name', 'payment:id,razorpay_payment_id,amount,currency']);

        return array_merge([
            'success'         => true,
            'session_id'      => $session->id,
            'patient_id'      => $session->patient_id,
            'doctor_id'       => $session->doctor_id,
            'channel_name'    => $session->channel_name,
            'call_identifier' => $session->resolveIdentifier(),
            'status'          => $session->status,
            'payment_status'  => $session->payment_status,
            'accepted_at'     => $session->accepted_at?->toIso8601String(),
            'started_at'      => $session->started_at?->toIso8601String(),
            'ended_at'        => $session->ended_at?->toIso8601String(),
            'duration_seconds'=> $session->duration_seconds,
            'amount_paid'     => $session->amount_paid,
            'currency'        => $session->currency,
            'doctor_join_url' => $session->resolvedDoctorJoinUrl(),
            'patient_payment_url' => $session->resolvedPatientPaymentUrl(),
            'created_at'      => $session->created_at?->toIso8601String(),
            'updated_at'      => $session->updated_at?->toIso8601String(),
            'session'         => $session,
        ], $extra);
    }
}
