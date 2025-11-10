<?php
// app/Http/Controllers/PaymentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Clinic;

class PaymentController extends Controller
{
    // Prefer env/config keys; fallback to test keys in dev
    private string $key;
    private string $secret;

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
        ]);

        $amountInInr = (int) ($request->input('amount', 500));

        try {
            $api = new Api($this->key, $this->secret);

            $order = $api->order->create([
                'receipt'  => 'rcpt_' . bin2hex(random_bytes(6)),
                'amount'   => $amountInInr * 100, // paisa
                'currency' => 'INR',
                'notes'    => ['via' => 'snoutiq'],
            ]);

            return response()->json([
                'success'  => true,
                'key'      => $this->key,
                'order'    => $order->toArray(),
                'order_id' => $order['id'],
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
            if ($request->filled('vet_slug')) {
                $notes['vet_slug'] = (string) $request->input('vet_slug');
            }
            if ($request->filled('service_id')) {
                $notes['service_id'] = (string) $request->input('service_id');
            }
            if ($request->filled('call_session_id')) {
                $notes['call_session_id'] = (string) $request->input('call_session_id');
            }

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
                contact: $contact
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

    protected function recordTransaction(Request $request, Payment $payment, ?int $amount, ?string $status, ?string $method, array $notes, ?string $currency, ?string $email, ?string $contact): void
    {
        $clinicId = null;

        try {
            $clinicId = $this->resolveClinicId($request, $notes);
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            Transaction::updateOrCreate(
                ['reference' => $payment->razorpay_payment_id],
                [
                    'clinic_id' => $clinicId,
                    'amount_paise' => (int) ($amount ?? 0),
                    'status' => $status ?? 'pending',
                    'type' => $notes['service_id'] ?? 'payment',
                    'payment_method' => $method,
                    'metadata' => [
                        'order_id' => $payment->razorpay_order_id,
                        'currency' => $currency,
                        'email' => $email,
                        'contact' => $contact,
                        'notes' => $notes,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function resolveClinicId(Request $request, array $notes): ?int
    {
        $directId = $request->input('clinic_id') ?? $notes['clinic_id'] ?? null;
        if ($directId) {
            $clinic = Clinic::find($directId);
            if ($clinic) {
                return $clinic->id;
            }
        }

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
    }
}
