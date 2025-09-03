<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroomerBooking;
use App\Models\UserRating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    //
    public function getRatings(Request $request){
        $booking = UserRating::where('groomer_booking_id',$request->id)->first();
        if(!$booking){
            return response()->json([
'review'=>'',
'rating'=>''
            ],200);
        }
        return response()->json($booking);
    }
    public function postBooking(Request $request){
        $request->validate([
            'review'=>'required',
            'rating'=>'required',
            'id'=>'required'
        ]);
        $groomerbooking = GroomerBooking::where('id',$request->id)->first();
        if(!$groomerbooking){
            return response()->json([
                'message'=>'Booking not found'
            ],400);
        }
                $booking = UserRating::where('groomer_booking_id',$request->id)->first();
if($booking){
    $booking->update($request->only([
       'review','rating'
    ]));
}else{
    UserRating::create([
        'review'=>$request->review,
        'rating'=>$request->rating,
            'user_id'=>$request->user()->id,
        'servicer_id'=>$groomerbooking->user_id,
        'groomer_booking_id'=>$request->id,
    ]);
}
return response()->json([
    'message'=>'Rating Done Successfully!'
]);
    }
}
