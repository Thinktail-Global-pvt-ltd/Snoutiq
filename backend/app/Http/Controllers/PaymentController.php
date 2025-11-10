<?php
// app/Http/Controllers/PaymentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\CallSession;

class PaymentController extends Controller
{
    // Prefer env/config keys; fallback to test keys in dev
    private string $key;
    private string $secret;
    private array $doctorClinicCache = [];

    public function __construct()
    {
        $this->key    = trim((string) (config('services.razorpay.key') ?? '')) ?: 'rzp_test_1nhE9190sR3rkP';
        $this->secret = trim((string) (config('services.razorpay.secret') ?? '')) ?: 'L6CPZlUwrKQpdC9N3TRX8gIh';


    }

    public function testView()
    {
        return view('rzp-test'); // resources/views/rzp-test.blade.php
    }

    // POST /api/create-order  { "amount": 999 }
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'nullable|integer|min:1',
            'clinic_id' => 'nullable|integer',
            'service_id' => 'nullable|string',
            'vet_slug' => 'nullable|string',
            'call_session_id' => 'nullable|string',
        ]);

        $amountInInr = (int) ($request->input('amount', 500));
        $notes = $this->mergeClientNotes($request, [
            'via' => 'snoutiq',
        ]);
        $context = $this->resolveTransactionContext($request, $notes);

        try {
            $api = new Api($this->key, $this->secret);

            $order = $api->order->create([
                'receipt'  => 'rcpt_' . bin2hex(random_bytes(6)),
                'amount'   => $amountInInr * 100, // paisa
                'currency' => 'INR',
                'notes'    => $notes,
            ]);
            $orderArr = $order->toArray();

            $this->recordPendingTransaction(
                request: $request,
                order: $orderArr,
                notes: $notes,
                context: $context
            );

            return response()->json([
                'success'  => true,
                'key'      => $this->key,
                'order'    => $orderArr,
                'order_id' => $orderArr['id'],
            ]);
        } catch (RazorpayError $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay: ' . $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // POST /api/rzp/verify
    // { razorpay_order_id, razorpay_payment_id, razorpay_signature }
    public function verifyPayment(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        try {
            $api = new Api($this->key, $this->secret);

            // Signature verify (local HMAC)
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);

            // Try to fetch payment details (optional)
            $paymentArr = null;
            $status   = 'verified';
            $amount   = null;
            $currency = 'INR';
            $method   = null;
            $email    = null;
            $contact  = null;
            $notes    = [];

            try {
                $p = $api->payment->fetch($data['razorpay_payment_id']);
                $paymentArr = $p->toArray();
                $status   = $paymentArr['status']   ?? $status;
                $amount   = $paymentArr['amount']   ?? null;
                $currency = $paymentArr['currency'] ?? 'INR';
                $method   = $paymentArr['method']   ?? null;
                $email    = $paymentArr['email']    ?? null;
                $contact  = $paymentArr['contact']  ?? null;
                $fetchedNotes = $paymentArr['notes'] ?? [];
                if (is_array($fetchedNotes)) { $notes = $fetchedNotes; }
            } catch (\Throwable $e) {
                // ignore network failure
            }

            // Merge client-provided tags to ensure clinic linkage even if fetch fails
            $notes = $this->mergeClientNotes($request, $notes);
            $context = $this->resolveTransactionContext($request, $notes);

            // Upsert into DB (idempotent on payment_id)
            $record = Payment::updateOrCreate(
                ['razorpay_payment_id' => $data['razorpay_payment_id']],
                [
                    'razorpay_order_id'  => $data['razorpay_order_id'],
                    'razorpay_signature' => $data['razorpay_signature'],
                    'amount'             => $amount,
                    'currency'           => $currency,
                    'status'             => $status,
                    'method'             => $method,
                    'email'              => $email,
                    'contact'            => $contact,
                    'notes'              => $notes ?: null,
                    'raw_response'       => $paymentArr,
                ]
            );

            $this->recordTransaction(
                request: $request,
                payment: $record,
                amount: $amount,
                status: $status,
                method: $method,
                notes: $notes,
                currency: $currency,
                email: $email,
                contact: $contact,
                context: $context
            );

            return response()->json([
                'success'  => true,
                'verified' => true,
                'stored'   => true,
                'payment'  => [
                    'id'       => $record->id,
                    'rzp_pid'  => $record->razorpay_payment_id,
                    'status'   => $record->status,
                    'amount'   => $record->amount,
                    'currency' => $record->currency,
                    'db_id'    => $record->id,
                ],
            ]);

        } catch (RazorpayError $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay: ' . $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    protected function recordPendingTransaction(Request $request, array $order, array $notes, array $context): void
    {
        $orderId = $order['id'] ?? null;
        if (! $orderId) {
            return;
        }

        $clinicId = $context['clinic_id'] ?? null;

        try {
            $clinicId = $this->resolveClinicId($request, $notes, $context);
        } catch (\Throwable $e) {
            report($e);
        }

        $context['clinic_id'] = $clinicId;
        $doctorId = $context['doctor_id'] ?? null;
        $userId = $context['user_id'] ?? null;

        try {
            Transaction::updateOrCreate(
                ['reference' => $orderId],
                [
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctorId,
                    'user_id' => $userId,
                    'amount_paise' => (int) ($order['amount'] ?? 0),
                    'status' => 'pending',
                    'type' => $notes['service_id'] ?? 'payment',
                    'payment_method' => null,
                    'reference' => $orderId,
                    'metadata' => [
                        'order_id' => $orderId,
                        'currency' => $order['currency'] ?? 'INR',
                        'notes' => $notes,
                        'receipt' => $order['receipt'] ?? null,
                        'call_id' => $context['call_identifier'] ?? null,
                        'doctor_id' => $doctorId,
                        'clinic_id' => $clinicId,
                        'user_id' => $userId,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function recordTransaction(Request $request, Payment $payment, ?int $amount, ?string $status, ?string $method, array $notes, ?string $currency, ?string $email, ?string $contact, array $context = []): void
    {
        $clinicId = $context['clinic_id'] ?? null;

        try {
            $clinicId = $this->resolveClinicId($request, $notes, $context);
        } catch (\Throwable $e) {
            report($e);
        }

        $context['clinic_id'] = $clinicId;
        $doctorId = $context['doctor_id'] ?? null;
        $userId = $context['user_id'] ?? null;
        $callId = $context['call_identifier'] ?? null;

        try {
            $reference = $payment->razorpay_payment_id ?? $payment->razorpay_order_id;
            if (! $reference) {
                return;
            }

            $payload = [
                'clinic_id' => $clinicId,
                'doctor_id' => $doctorId,
                'user_id' => $userId,
                'amount_paise' => (int) ($amount ?? 0),
                'status' => $status ?? 'pending',
                'type' => $notes['service_id'] ?? 'payment',
                'payment_method' => $method,
                'reference' => $reference,
                'metadata' => [
                    'order_id' => $payment->razorpay_order_id,
                    'payment_id' => $payment->razorpay_payment_id,
                    'currency' => $currency,
                    'email' => $email,
                    'contact' => $contact,
                    'notes' => $notes,
                    'call_id' => $callId,
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                    'user_id' => $userId,
                ],
            ];

            $transaction = null;

            if ($payment->razorpay_payment_id) {
                $transaction = Transaction::where('reference', $payment->razorpay_payment_id)->first();
            }

            if (! $transaction && $payment->razorpay_order_id) {
                $transaction = Transaction::where('reference', $payment->razorpay_order_id)->first();
            }

            if ($transaction) {
                $transaction->fill($payload);
                $transaction->save();
            } else {
                Transaction::create($payload);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function resolveClinicId(Request $request, array $notes, array $context = []): ?int
    {
        $directId = $context['clinic_id']
            ?? $request->input('clinic_id')
            ?? $request->input('clinicId')
            ?? ($notes['clinic_id'] ?? null);

        if ($directId !== null && $directId !== '') {
            return (int) $directId;
        }

        $doctorId = $context['doctor_id'] ?? null;
        if (! $doctorId && $request->filled('doctor_id')) {
            $doctorId = (int) $request->input('doctor_id');
        } elseif (! $doctorId && $request->filled('doctorId')) {
            $doctorId = (int) $request->input('doctorId');
        } elseif (! $doctorId && isset($notes['doctor_id'])) {
            $doctorId = (int) $notes['doctor_id'];
        }

        if ($doctorId) {
            $clinicFromDoctor = $this->lookupDoctorClinicId($doctorId);
            if ($clinicFromDoctor) {
                return $clinicFromDoctor;
            }
        }

        try {
            $clinic = Clinic::query()->first();
            if ($clinic) {
                return $clinic->id;
            }

            $clinic = Clinic::create([
                'name' => 'General Clinic',
                'status' => 'active',
                'city' => 'Unknown',
                'state' => 'Unknown',
                'country' => 'India',
            ]);

            return $clinic->id;
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    protected function mergeClientNotes(Request $request, array $notes = []): array
    {
        $mapping = [
            'vet_slug' => ['vet_slug'],
            'service_id' => ['service_id'],
            'call_session_id' => ['call_session_id', 'callSessionId', 'call_id', 'callId'],
            'clinic_id' => ['clinic_id', 'clinicId'],
            'doctor_id' => ['doctor_id', 'doctorId'],
            'user_id' => ['user_id', 'userId', 'patient_id', 'patientId'],
        ];

        foreach ($mapping as $noteKey => $keys) {
            foreach ($keys as $key) {
                if ($request->filled($key)) {
                    $notes[$noteKey] = (string) $request->input($key);
                    break;
                }
            }
        }

        return $notes;
    }

    protected function resolveTransactionContext(Request $request, array $notes = []): array
    {
        $context = [
            'call_identifier' => $this->firstFilled($request, ['call_session_id', 'callSessionId', 'call_id', 'callId'], $notes),
            'clinic_id' => $this->toNullableInt($this->firstFilled($request, ['clinic_id', 'clinicId'], $notes)),
            'doctor_id' => $this->toNullableInt($this->firstFilled($request, ['doctor_id', 'doctorId'], $notes)),
            'user_id' => $this->toNullableInt($this->firstFilled($request, ['user_id', 'userId', 'patient_id', 'patientId'], $notes)),
        ];

        if (! $context['user_id'] && $request->user()) {
            $context['user_id'] = (int) $request->user()->getAuthIdentifier();
        }

        $session = $this->findCallSession($context['call_identifier']);

        if ($session) {
            $context['doctor_id'] ??= $session->doctor_id ? (int) $session->doctor_id : null;
            $context['user_id'] ??= $session->patient_id ? (int) $session->patient_id : null;

            if ($session->relationLoaded('doctor') && $session->doctor) {
                $context['clinic_id'] ??= $session->doctor->vet_registeration_id
                    ? (int) $session->doctor->vet_registeration_id
                    : null;
            }
        }

        if (! $context['clinic_id'] && $context['doctor_id']) {
            $context['clinic_id'] = $this->lookupDoctorClinicId($context['doctor_id']);
        }

        return $context;
    }

    protected function firstFilled(Request $request, array $keys, array $notes = [])
    {
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                return $request->input($key);
            }

            if (array_key_exists($key, $notes) && $notes[$key] !== null && $notes[$key] !== '') {
                return $notes[$key];
            }
        }

        return null;
    }

    protected function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function lookupDoctorClinicId(?int $doctorId): ?int
    {
        if (! $doctorId) {
            return null;
        }

        if (array_key_exists($doctorId, $this->doctorClinicCache)) {
            return $this->doctorClinicCache[$doctorId];
        }

        try {
            $doctor = Doctor::find($doctorId);
            $clinicId = $doctor && $doctor->vet_registeration_id
                ? (int) $doctor->vet_registeration_id
                : null;
        } catch (\Throwable $e) {
            report($e);
            $clinicId = null;
        }

        return $this->doctorClinicCache[$doctorId] = $clinicId;
    }

    protected function findCallSession($identifier): ?CallSession
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        try {
            return CallSession::query()
                ->with('doctor')
                ->where(function ($query) use ($identifier) {
                    $query->where('call_identifier', $identifier);
                    if (is_numeric($identifier)) {
                        $query->orWhere('id', (int) $identifier);
                    }
                })
                ->latest('id')
                ->first();
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }
}
