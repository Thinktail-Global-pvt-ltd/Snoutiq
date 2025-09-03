<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmergencyRequest;
use App\Models\GroomerBooking;
use App\Models\GroomerService;
use App\Models\UserPet;
use Carbon\Carbon;
use Razorpay\Api\Api;

class EmergencyController extends Controller
{
    //
    public function sendRequest(Request $request){
$user = $request->user();
$EmergencyRequest = EmergencyRequest::create(
    [
        'token'=>md5(time().rand(999,9999)),
        'user_id'=>$user->id,
        'amount_tobe_paid'=>0,
        'is_paid'=>0,
        'servicer_id'=>0,'reason'=>$request->reason
    ]
    );
    return response()->json([
        'token'=>$EmergencyRequest->token
    ]);
    }
    public function isAccepted(Request $request){
       $request->validate([
            'token'=>'required'
        ]);
           $EmergencyRequest = EmergencyRequest::where('token',$request->token)->first();
        if(!$EmergencyRequest){
            return response()->json([
                'message'=>'Request does\'t exists'
            ],404);
        }
       return response()->json(
        [
            'data'=>$EmergencyRequest
        ]
        );
    }
    public function amtPaid(Request $request){
         $request->validate([
            'token'=>'required'
        ]);
           $EmergencyRequest = EmergencyRequest::where('token',$request->token)->first();
        if(!$EmergencyRequest){
            return response()->json([
                'message'=>'Request does\'t exists'
            ],404);
        }
         $signature = $request->razorpay_signature;
    $paymentId = $request->razorpay_payment_id;
    $orderId = $request->razorpay_order_id;

    $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
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
        }else{
                    return response()->json([
                'message'=>'Payment not found in bank server'
            ],404); 
        }
          $EmergencyRequest->update([
         
            'is_paid'=>1
        ]);
        // 
/*   */
   $old_booking = GroomerBooking::where('user_id',$EmergencyRequest->servicer_id)->orderBy('serial_number','desc')->first();
$srvc = GroomerService::where('user_id',$EmergencyRequest->servicer_id)->where('main_service','video_call')->first();
  GroomerBooking::create([
         'serial_number'=>$old_booking?$old_booking->serial_number+1:1,
        'customer_type'=>'online',
        'customer_id'=>$EmergencyRequest->user_id,
        'customer_pet_id'=>UserPet::where('user_id',$EmergencyRequest->user_id)->first()->id,
        'date'=>Carbon::now()->toDateString(),
        'start_time'=>Carbon::now()->format('H:i'),
        'end_time'=>Carbon::now()->format('H:i'),
        'services'=>json_encode( 
            [[
            'service_id'=> $srvc->id,'price'=>$payment->amount/100
        ]]
    ),
        'total'=>$payment->amount/100,
        'paid'=>$payment->amount/100,
        'user_id'=>$EmergencyRequest->servicer_id,'emergency_id'=>$EmergencyRequest->id,
        'groomer_employees_id'=>0,//GroomerEmployee::where('user_id',$request->groomer_id)->first()->id,'status'=>'Pending',
          'is_inhome'=>0,
            'location'=>$request->location??[],'status'=>'accepted'
    ]);
        // 
return response()->json(
    [
        'message'=>'Payment Done!'
    ]
    );
    }
    public function searchForRequest(){
       return response()->json([
        'pending'=>EmergencyRequest::where('servicer_id',0)    ->where('created_at', '>=', Carbon::now()->subMinutes(5))
->first()
       ]) ;

    }
    public function acceptEmergancy(Request $request){
        $request->validate([
            'token'=>'required'
        ]);
        $EmergencyRequest = EmergencyRequest::where('token',$request->token)->where('servicer_id',0)->first();
        if(!$EmergencyRequest){
            return response()->json([
                'message'=>'Request does\'t exists'
            ],404);
        }
        // Getting pricing
$GroomerService = GroomerService::where('user_id',$request->user()->id)->where('main_service','video_call')->first();
if(!$GroomerService){
    return response()->json([
        'message'=>'No video call service found!'
    ]);
}
        // 
        $EmergencyRequest->update([
            'servicer_id'=>$request->user()->id,
            'amount_tobe_paid'=>$GroomerService->price
        ]);
        return response()->json([
            'message'=>'Wait for payment to be paid'
        ]);
    }
    
}
