<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// ❌ WRONG: use App\Http\Controllers\Api;
// ✅ RIGHT:
use Razorpay\Api\Api as RazorpayApi;
use Razorpay\Api\Errors\Error as RazorpayError; // base SDK error (optional but useful)

class PaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $data = $request->validate([
            'amount' => 'nullable|integer|min:1'
        ]);

        $amountInInr = (int)($data['amount'] ?? 500);

        // For testing (move to .env for prod)
        $key    = 'rzp_test_1nhE9190sR3rkP';
        $secret = 'L6CPZlUwrKQpdC9N3TRX8gIh';

        if ($key === '' || $secret === '') {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay key/secret missing',
            ], 500);
        }

        try {
            // ✅ Use SDK class
            $api = new RazorpayApi($key, $secret);

            $order = $api->order->create([
                'receipt'  => 'rcpt_' . bin2hex(random_bytes(6)),
                'amount'   => $amountInInr * 100, // paise
                'currency' => 'INR',
                'notes'    => ['via' => 'laravel'],
            ]);

            return response()->json([
                'success'  => true,
                'key'      => $key,                 // front-end ke liye public key
                'order'    => $order->toArray(),    // ⬅️ entity ko array banao
                'order_id' => $order['id'],
            ]);

        } catch (RazorpayError $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Razorpay: '.$e->getMessage(),
                'type'    => class_basename($e),
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
