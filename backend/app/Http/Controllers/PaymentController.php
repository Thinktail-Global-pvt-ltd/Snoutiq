<?php
// app/Http/Controllers/PaymentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;
use App\Models\Payment;

class PaymentController extends Controller
{
    // Prefer env/config keys; fallback to test keys in dev
    private string $key;
    private string $secret;

    public function __construct()
    {
        $this->key    = trim((string) (config('services.razorpay.key') ?? '')) ?: 'rzp_live_RGBIfjaGxq1Ma4';
        $this->secret = trim((string) (config('services.razorpay.secret') ?? '')) ?: 'WypJ2plLEmScSrVjrLzixWyN';
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
}
