<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroomerProfile;
use App\Models\UserChat;
use Illuminate\Http\Request;
  use Razorpay\Api\Api;

class ChatController extends Controller
{
    //
    public function startChat(Request $request){
        $request->validate([
            'groomer_user_id'=>'required'
          
        ]);
        $GroomerProfile = GroomerProfile::where("user_id", $request->groomer_user_id)->firstOrFail();
        // Use $GroomerProfile or return it, for example:
$price = $GroomerProfile->chat_price;
$paid = 0;
if($price!=0){
      $signature = $request->razorpay_signature;
    $paymentId = $request->razorpay_payment_id;
    $orderId = $request->razorpay_order_id;

    $api = new Api(
        config('services.razorpay.key'),
        config('services.razorpay.secret')
    );
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
            $paid = $payment->amount/100;
        }else{
                    return response()->json([
                'message'=>'Payment not found in bank server'
            ],404); 
        }
}
// Means paid
UserChat::create(
    [
          'user_id'=>$request->user()->id,
        'servicer_id'=>$request->groomer_user_id,
        'type'=>'servicer',
        'message'=>'Hello, How may I help you?','is_first'=>1,'paid_amt'=>$paid
    ]
    );
    return response()->json(
        [
            'message'=>'Chat successfully initiated!'
        ]
        );
        }

        public function myMessages(Request $request){
            $request->validate([
                'type'=>'required'
            ]);
            if ($request->type == "user") {
    $chats = UserChat::where('user_id', $request->user()->id)
        ->selectRaw('MAX(id) as id, servicer_id')->with("servicer")->with("user")
        ->groupBy('servicer_id')
        ->get();
} else {
    $chats = UserChat::where('servicer_id', $request->user()->id)
        ->selectRaw('MAX(id) as id, user_id')->with("servicer")->with("user")
        ->groupBy('user_id')
        ->get();
}

            return response()->json($chats);
        }
        public function chatHistory(Request $request){
         $request->validate([
                'type'=>'required'
            ]);
                            if($request->type == "user"){
                $chats = UserChat::where('user_id',$request->user()->id)->with("servicer")->with("user")->where('servicer_id',$request->servicer_id)->get();

                            }else{
                               $chats = UserChat::where('servicer_id',$request->user()->id)->with("servicer")->with("user")->where('user_id',$request->user_id)->get();

                            }
                            return response()->json($chats);
        }
        public function sendMessage(Request $request){
             $request->validate([
                'type'=>'required','message'=>'required|max:225'
            ]);
               if($request->type == "user"){
                $chats = UserChat::create([
                      'user_id'=>$request->user()->id,
        'servicer_id'=>$request->servicer_id,
        'type'=>'user',
        'message'=>$request->message,'is_first'=>0,'paid_amt'=>0
                ]);

                            }else{
 $chats = UserChat::create([
                      'servicer_id'=>$request->user()->id,
        'user_id'=>$request->user_id,
        'type'=>'servicer',
        'message'=>$request->message,'is_first'=>0,'paid_amt'=>0
                ]);

                            }
                            return response()->json([
                                'message'=>'Message sent successfull!'
                            ]);
        }
 }
