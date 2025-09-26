<?php
// app/Http/Controllers/RazorpayTestController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error as RazorpayError;
use App\Models\Payment;

class PaymentController extends Controller
{
    // ⚠️ TEST ONLY — keys hardcoded as requested
    private string $key    = 'rzp_live_RGBIfjaGxq1Ma4';
    private string $secret = 'WypJ2plLEmScSrVjrLzixWyN';
    // app/Http/Controllers/RazorpayTestController.php

public function testView()
{
    return view('rzp-test'); // resources/views/rzp-test.blade.php
}


    // POST /api/rzp/order  { "amount": 999 }
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'nullable|integer|min:1',
        ]);

        $amountInInr = (int) ($request->input('amount', 500)); // default ₹500

        try {
            $api = new Api($this->key, $this->secret);

            $order = $api->order->create([
                'receipt'  => 'rcpt_' . bin2hex(random_bytes(6)),
                'amount'   => $amountInInr * 100, // paisa
                'currency' => 'INR',
                'notes'    => ['via' => 'laravel-test'],
            ]);

            return response()->json([
                'success'  => true,
                'key'      => $this->key,           // front-end (checkout) ke liye public key
                'order'    => $order->toArray(),    // entity -> array
                'order_id' => $order['id'],
            ]);
        } catch (RazorpayError $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay: '.$e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // POST /api/rzp/verify
    // {
    //   "razorpay_order_id": "...",
    //   "razorpay_payment_id": "...",
    //   "razorpay_signature": "...",
    //   "capture": true
    // }
 public function verifyPayment(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        try {
            $api = new Api($this->key, $this->secret);

            // 1) Signature verify (local HMAC) — NO capture
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);

            // 2) Try to fetch payment for details (optional; safe to skip if network blocks)
            $paymentArr = null;
            $status     = 'verified';   // default if fetch fails
            $amount     = null;
            $currency   = 'INR';
            $method     = null;
            $email      = null;
            $contact    = null;
            $notes      = null;

            try {
                $p = $api->payment->fetch($data['razorpay_payment_id']);
                $paymentArr = $p->toArray();
                $status   = $paymentArr['status']   ?? $status;
                $amount   = $paymentArr['amount']   ?? null;     // paisa
                $currency = $paymentArr['currency'] ?? 'INR';
                $method   = $paymentArr['method']   ?? null;
                $email    = $paymentArr['email']    ?? null;
                $contact  = $paymentArr['contact']  ?? null;
                $notes    = $paymentArr['notes']    ?? null;
            } catch (\Throwable $e) {
                // ignore fetch failure; we still store minimal verified record
            }

            // 3) Upsert into DB (idempotent on razorpay_payment_id)
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
                    'notes'              => $notes,
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
                    'amount'   => $record->amount,   // paisa
                    'currency' => $record->currency,
                ],
            ]);

        } catch (RazorpayError $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay: '.$e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
