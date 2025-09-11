<?php
// app/Http/Controllers/PaymentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $keyId = env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_SECRET');

        $api = new \Razorpay\Api\Api($keyId, $keySecret);

        // 500 INR test order
        $order = $api->order->create([
            'receipt' => uniqid(),
            'amount' => 500 * 100, // in paisa
            'currency' => 'INR'
        ]);

        return response()->json($order);
    }
}
