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

    try {
        $api = new \Razorpay\Api\Api($keyId, $keySecret);

        // 500 INR test order
        $order = $api->order->create([
            'receipt'  => uniqid(),
            'amount'   => 500 * 100, // in paisa
            'currency' => 'INR'
        ]);

        return response()->json([
            'success' => true,
            'order'   => $order
        ]);

    } catch (\Exception $e) {
        // Log the error
        \Log::error('Razorpay Order Creation Failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Order creation failed. Please try again later.',
            'error'   => $e->getMessage() // ⚠️ for production, avoid sending raw error
        ], 500);
    }
}

}
