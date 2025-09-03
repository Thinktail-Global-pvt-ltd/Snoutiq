<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\GroomerBooking;
use App\Models\GroomerEmployee;
use App\Models\GroomerService;
use App\Models\UserPet;
   use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
  use Razorpay\Api\Api;


class RazorpayController  extends Controller
{
    public function createOrder(Request $request)
    {
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $order = $api->order->create([
            'receipt' => 'rcptid_' . time(),
            'amount' => $request->amount * 100, // Amount in paisa
            'currency' => 'INR',
        ]);

        return response()->json([
            'order_id' => $order->id,
            'razorpay_key' => env('RAZORPAY_KEY'),
            'amount' => $request->amount * 100,
            'currency' => 'INR',
        ]);
    }
    




  public function book_grooming(Request $request)
{
    $signature = $request->razorpay_signature;
    $paymentId = $request->razorpay_payment_id;
    $orderId = $request->razorpay_order_id;

    $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

    // try {
        // Verify payment signature
        $attributes = [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ];
        $api->utility->verifyPaymentSignature($attributes);

        // Now capture the payment (for manual capture flow)
        $payment = $api->payment->fetch($paymentId);

        if ($payment->status === 'authorized') {
            $captured = $payment->capture([
                'amount' => $payment->amount, // in paisa
                'currency' => 'INR'
            ]);
        }

        // Save consultation details
      /*  UserGroomingBooking::create([
            'pg_payment_id' => $paymentId,
            'service_name'=>$request->service_name,
             'consultation_date' => Carbon::parse($request->date)->format('Y-m-d'),
            'start_time' =>explode('-',$request->time_slot)[0],
            'end_time' => explode('-',$request->time_slot)[1],
            'paid_amt' => $payment->amount/100,
            'consultation_status' => 'booked',
            'is_inhome'=>$request->is_inhome=="true"?1:0,
            'location'=>$request->location??[],
            'pet_id' => $request->pet_id,
            'user_id' => $request->user()->id,
            'groomer_user_id' => $request->groomer_id,
        ]); */
   $old_booking = GroomerBooking::where('user_id',$request->groomer_id)->orderBy('serial_number','desc')->first();
   $time = $request->time_slot;

$service = explode(",",$request->service_id);
$services = [];
$timeDur=0;
foreach($service as $ds){
//    return $ds;
$timeDur+=GroomerService::find($ds)->duration;
$services[]=['service_id'=>$ds,'price'=>GroomerService::find($ds)->price];
}
$dateTime = \DateTime::createFromFormat('H:i', $time);
// $timeDur = GroomerService::find($request->service_id)->duration;
$dateTime->modify('+'.$timeDur.' minutes');
$newTime = $dateTime->format('H:i');

// echo $newTime; // Outputs: 15:17
    GroomerBooking::create([
         'serial_number'=>$old_booking?$old_booking->serial_number+1:1,
        'customer_type'=>'online',
        'customer_id'=>$request->user()->id,
        'customer_pet_id'=>$request->pet_id,
        'date'=>$request->date,
        'start_time'=>$request->time_slot,
        'end_time'=>$newTime,
        'services'=>json_encode($services
        //     [[
        //     'service_id'=>$request->service_id,'price'=>$payment->amount/100
        // ]]
    ),
        'total'=>$payment->amount/100,
        'paid'=>$payment->amount/100,
        'user_id'=>$request->groomer_id,
        'groomer_employees_id'=>0,//GroomerEmployee::where('user_id',$request->groomer_id)->first()->id,'status'=>'Pending',
          'is_inhome'=>$request->is_inhome=="true"?1:0,
            'location'=>$request->location??[],'status'=>'pending'
    ]);
        return response()->json(['status' => 'success', 'message' => 'Goomer booked successfully.']);
    // } catch (\Exception $e) {
    //     return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
    // }
}



}
