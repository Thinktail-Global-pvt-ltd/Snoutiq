<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerBlockTime;
use App\Models\GroomerBooking;
use Illuminate\Http\Request;
use App\Models\GroomerClient;
use App\Models\GroomerClientPet;
use App\Models\GroomerService;
use App\Models\UserPet;
use App\Models\UserProfile;
use App\Models\GroomerEmployee;

class CalenderController extends Controller
{
 public function store_blockTime(Request $request){
    $request->validate([
        'title'=>'string|required',
        'date'=>'required',
        'start_time'=>'string|required',
        'end_time'=>'string|required',
        'groomer_employees_id'=>'required',
    ]);
    $data = $request->only([
         'title',
        'date',
        'start_time',
        'end_time',
        'groomer_employees_id'
    ]);
    $data['user_id']= $request->user()->id;
    $blockedTime = self::blockedTime($data['user_id'],$request->date);
    if(!self::isSlotAvailable($request->start_time, $request->end_time, $request->groomer_employees_id, $blockedTime)){
 return response()->json([
        'message'=>'Time slot not available! It\'s confliting with other'
    ],500);
    }
    GroomerBlockTime::create($data);
    return response()->json([
        'message'=>'Block time added successfull!'
    ]);
 }
public function store_booking(Request $request)
{
    try {
        // Debug incoming request
        // dd($request->all());

        $request->validate([
            'customer_type'       => 'required',
            'customer_id'         => 'required',
            'customer_pet_id'     => 'required',
            'date'                => 'required',
            'start_time'          => 'required',
            'end_time'            => 'required',
            'services'            => 'required|array',
            'groomer_employees_id'=> 'required'
        ]);

        $total = 0;
        foreach ($request->services as $dd) {
            if ($dd['service_id'] == 0 || $dd['service_id'] == "") {
                return response()->json([
                    'message' => 'Please don\'t keep service empty'
                ], 500);
            }
            $total += $dd['price'];
        }

        $blockedTime = self::blockedTime($request->user()->id, $request->date);

        if (!self::isSlotAvailable(
            $request->start_time,
            $request->end_time,
            $request->groomer_employees_id,
            $blockedTime
        )) {
            return response()->json([
                'message' => 'Time slot not available! It\'s conflicting with another booking'
            ], 500);
        }

        $old_booking = GroomerBooking::where('user_id', $request->user()->id)
            ->orderBy('serial_number', 'desc')
            ->first();

        GroomerBooking::create([
            'serial_number'       => $old_booking ? $old_booking->serial_number + 1 : 1,
            'customer_type'       => $request->customer_type,
            'customer_id'         => $request->customer_id,
            'customer_pet_id'     => $request->customer_pet_id,
            'date'                => $request->date,
            'start_time'          => $request->start_time,
            'end_time'            => $request->end_time,
            'services'            => json_encode($request->services),
            'total'               => $total,
            'paid'                => 0,
            'user_id'             => $request->user()->id,
            'groomer_employees_id'=> $request->groomer_employees_id,
            'status'              => 'Pending'
        ]);

        return response()->json([
            'message' => 'Booking booked successfully!'
        ], 200);

    } catch (\Throwable $e) {
        // Debug the error in local
        dd([
            'error_message' => $e->getMessage(),
            'file'          => $e->getFile(),
            'line'          => $e->getLine(),
            'trace'         => $e->getTraceAsString()
        ]);

        // Or if you want JSON in production instead of dd:
        // return response()->json([
        //     'error' => $e->getMessage()
        // ], 500);
    }
}

 public function bookings(Request $request){
    $uid = $request->user()->id;
    $GroomerBooking = GroomerBooking::where('user_id',$uid)->with("groomerEmployee");
    if(filled($request->date)){
$GroomerBooking->where('date',$request->date);
    }
    if(filled($request->customer_id)){
$GroomerBooking->where('customer_id',$request->customer_id);
    }
    $GroomerBooking = $GroomerBooking->orderBy('id','desc')->get()->map(function($data) use($uid){
        if($data->customer_type=="walkin"){
            $customer_data = ['name'=>'Walkin'];
        }elseif($data->customer_type=="Groomer" || $data->customer_type=="groomer"){
            $cust = GroomerClient::where('id',$data->customer_id)->where('user_id',$uid)->first();
            if($cust){
                $customer_data = $cust;
            }
        }else{
             $cust = UserProfile::where('user_id',$data->customer_id)->first();
            if($cust){
                $customer_data = $cust;
            }
        }
        if($data->customer_type=="walkin"){
            $pet_data = ['name'=>'Not added'];
        }elseif($data->customer_type=="Groomer" || $data->customer_type=="groomer"){
         $GroomerClientPet= GroomerClientPet::where('user_id',$uid)->where('id',$data->customer_pet_id)->first();
         $pet_data = $GroomerClientPet;
        }else{
             $pett= UserPet::where('user_id',$data->customer_id)->where('id',$data->customer_pet_id)->first();
         $pet_data = $pett;
        }
        $services = [];
        // return ['s'=>$data->services];
        foreach(json_decode($data->services) as $ddd){
            // return $ddd;
            $service = GroomerService::where('user_id',$uid)->where('id',$ddd->service_id)->first();
$services[]=['name'=>$service->name,'price'=>$ddd->price];
        }

        return array_merge($data->toArray(),[
'customer_data'=>$customer_data,
'service_withnames'=>$services
        ]);
    });
    
    return response()->json([
'data'=>$GroomerBooking
    ]);
 }
 public function bookingsV2(Request $request){
        $uid = $request->user()->id;
$GroomerBooking = GroomerBooking::where('user_id',$uid)->with("groomerEmployee")
->orderBy('id','desc')->get()->map(function($data) use($uid){
     $services = [];
        // return ['s'=>$data->services];
        $is_videoCall = false;
        foreach(json_decode($data->services) as $ddd){
            // return $ddd;
            $service = GroomerService::where('user_id',$uid)->where('id',$ddd->service_id)->first();
$services[]=['name'=>$service?->name??'---','price'=>$ddd->price];
if($service->main_service == "video_call"){
         $is_videoCall = true;
   
}
        }

    $dataRes = [
        'id'=> $data->id,
     
      'time'=> $data->start_time,
    'date'=> $data->date,
    'services'=>$services ,
    'visitType'=> $data->is_inhome == 1?'At Home':'At Clinic',
    'price'=> $data->total,
    'status'=> $data->status,'is_videoCall'=>$is_videoCall
    ];
   if($data->customer_type=="Groomer" || $data->customer_type=="groomer"){
         $GroomerClientPet= GroomerClientPet::where('user_id',$uid)->where('id',$data->customer_pet_id)->first();
         $pet_data = $GroomerClientPet;
    $cust = GroomerClient::where('id',$data->customer_id)->where('user_id',$uid)->first();
$dataRes['ownerName'] = $cust?->name;
$dataRes['ownerMobile'] = $cust?->phone;

         $dataRes['petName']=$GroomerClientPet->name;
         $dataRes['petType']=$GroomerClientPet->type .' - '. $GroomerClientPet->breed;
        }else{
                         $cust = UserProfile::where('user_id',$data->customer_id)->first();

             $pett= UserPet::where('user_id',$data->customer_id)->where('id',$data->customer_pet_id)->first();
           $dataRes['petName']=$pett?->name??'---';
         $dataRes['petType']=$pett?->type .' - '. $pett?->breed;
$dataRes['ownerName'] = $cust?->name??'---';
    $dataRes['ownerMobile'] = $cust?->user?->mobile;

        }
            if($data->groomer_employees_id==0){
             $dataRes['employee']= 'Not assigned!'; 
        }else{
             $dataRes['employee'] = GroomerEmployee::find($data->groomer_employees_id)->name;
        }
        return $dataRes;
 });
 return response()->json([
    'data'=>$GroomerBooking
 ]);
 }
public function booking_single($id, Request $request)
{
    $uid = $request->user()->id;

    $data = GroomerBooking::where('user_id', $uid)
        ->with("groomerEmployee")
        ->where('id', $id)
        ->first();

    if (!$data) {
        return response()->json(['error' => 'Booking not found'], 404);
    }

    // Customer data
   if($data->customer_type=="walkin"){
            $customer_data = ['name'=>'Walkin'];
        }elseif($data->customer_type=="Groomer" || $data->customer_type=="groomer"){
            $cust = GroomerClient::where('id',$data->customer_id)->where('user_id',$uid)->first();
            if($cust){
                $customer_data = $cust;
            }
        }else{
             $cust = UserProfile::where('user_id',$data->customer_id)->first();
            if($cust){
                $customer_data = $cust;
            }
        }

    // Pet data
       if($data->customer_type=="walkin"){
            $pet_data = ['name'=>'Not added'];
        }elseif($data->customer_type=="Groomer" || $data->customer_type=="groomer"){
         $GroomerClientPet= GroomerClientPet::where('user_id',$uid)->where('id',$data->customer_pet_id)->first();
         $pet_data = $GroomerClientPet;
        }else{
             $pett= UserPet::where('user_id',$data->customer_id)->where('id',$data->customer_pet_id)->first();
        $pett->medicalHistory = $pett->medical_history;
        $pett->vaccinationLog = $pett->vaccination_log;

             $pet_data = $pett;
        }

    // Services with names and prices
    $services = [];
    foreach (json_decode($data->services) as $ddd) {
        $service = GroomerService::where('user_id', $uid)
            ->where('id', $ddd->service_id)
            ->first();

        $services[] = [
            'name' => $service ? $service->name : 'Unknown Service',
            'price' => $ddd->price
        ];
    }

    // Merge data
    $response = array_merge($data->toArray(), [
        'customer_data' => $customer_data,
        'pet_data' => $pet_data,
        'service_withnames' => $services
    ]);

    return response()->json(['data' => $response]);
}
public function booking_single_delete($id,Request $request){
     $uid = $request->user()->id;

    $data = GroomerBooking::where('user_id', $uid)
        ->with("groomerEmployee")
        ->where('id', $id)
        ->first();

    if (!$data) {
        return response()->json(['error' => 'Booking not found'], 404);
    }
    $data->delete();
    return response()->json([
        'message'=>'Booking deleted'
    ]);
}
public function booking_single_payment($id,Request $request){
     $uid = $request->user()->id;

    $data = GroomerBooking::where('user_id', $uid)
        ->with("groomerEmployee")
        ->where('id', $id)
        ->first();

    if (!$data) {
        return response()->json(['error' => 'Booking not found'], 404);
    }
    $data->update(['paid'=>$request->amount]);
    return response()->json([
        'message'=>'Booking payment updated'
    ]);
}
public function booking_single_prescription($id,Request $request){
     $uid = $request->user()->id;

    $data = GroomerBooking::where('user_id', $uid)
        ->with("groomerEmployee")
        ->where('id', $id)
        ->first();

    if (!$data) {
        return response()->json(['error' => 'Booking not found'], 404);
    }
    $data->update(['prescription'=>$request->prescription]);
    return response()->json([
        'message'=>'Booking prescription updated'
    ]);
}
public function booking_single_assignEmployee($id,Request $request){
     $uid = $request->user()->id;

    $data = GroomerBooking::where('user_id', $uid)
        ->with("groomerEmployee")
        ->where('id', $id)
        ->first();

    if (!$data) {
        return response()->json(['error' => 'Booking not found'], 404);
    }
    $data->update(['groomer_employees_id'=>$request->groomer_employees_id]);
    return response()->json([
        'message'=>'Booking employee updated'
    ]);
}

public function booking_single_status($id,Request $request){
     $uid = $request->user()->id;

    $data = GroomerBooking::where('user_id', $uid)
        ->with("groomerEmployee")
        ->where('id', $id)
        ->first();

    if (!$data) {
        return response()->json(['error' => 'Booking not found'], 404);
    }
    $data->update(['status'=>$request->status]);
    return response()->json([
        'message'=>'Booking status updated'
    ]);
}

 public function booked_times(Request $request){
    $uid = $request->user()->id;
   
    return response()->json([
        'data'=>self::blockedTime($uid,$request->date,$request->month)
    ]);
 }
 public function blockedTime($uid,$date,$month=""){

    if(filled($month)){
    list($year, $monthNum) = explode('-', $month);

            $GroomerBlockTime = GroomerBlockTime::where('user_id', $uid)
                ->whereYear('date', $year)
                ->whereMonth('date', $monthNum)
                ->get();
  
    }else{
       $GroomerBlockTime = GroomerBlockTime::where('user_id',$uid)->where('date',$date)->get();
    }
       $blocked = [];
    foreach($GroomerBlockTime as $d){
        $start_minute = explode(':',$d->start_time)[0]*60 + explode(':',$d->start_time)[1];
        $end_minute = explode(':',$d->end_time)[0]*60 + explode(':',$d->end_time)[1];
      $blocked []=[
        'type'=>'blocked',
        'title'=>$d->title,
        'start_time'=>$d->start_time,
        'start_minute'=>$start_minute,
        'end_minute'=>$end_minute,
'end_time'=>$d->end_time,
'groomer_employees_id'=>$d->groomer_employees_id,'date'=>$d->date

      ] ; 

    }
        if(filled($month)){
       $GroomerBooking = GroomerBooking::where('user_id',$uid)->with("groomerEmployee")
        ->whereYear('date', $year)
                ->whereMonth('date', $monthNum)
       ->orderBy('id','desc')->get();

        }else{
       $GroomerBooking = GroomerBooking::where('user_id',$uid)->with("groomerEmployee")->where('date',$date)->orderBy('id','desc')->get();
        }
       foreach($GroomerBooking as $d2){
        $data = $d2 ;
           $start_minute = explode(':',$d2->start_time)[0]*60 + explode(':',$d2->start_time)[1];
        $end_minute = explode(':',$d2->end_time)[0]*60 + explode(':',$d2->end_time)[1];
           if($data->customer_type=="walkin"){
            $customer_data ='Walkin';
        }elseif($data->customer_type=="Groomer" || $data->customer_type=="groomer"){
            $cust = GroomerClient::where('id',$data->customer_id)->where('user_id',$uid)->first();
            if($cust){
                $customer_data = $cust->name;
            }
        }else{
             $cust = UserProfile::where('user_id',$data->customer_id)->first();
            if($cust){
                $customer_data =  $cust->name;
            }
        }
        $blocked []=[
        'type'=>'Booking',
        'title'=>'B# '.$d2->serial_number.' - '.$customer_data,
        'start_time'=>$d2->start_time,
        'start_minute'=>$start_minute,
        'end_minute'=>$end_minute,
'end_time'=>$d2->end_time,
'groomer_employees_id'=>$d2->groomer_employees_id,'date'=>$d2->date
      ]; 
       }
    return $blocked;
 }
 function isSlotAvailable($start_time_str, $end_time_str, $employeeId, $SlotUsed) {
    // Convert start and end times to minutes
    list($startHour, $startMin) = explode(':', $start_time_str);
    list($endHour, $endMin) = explode(':', $end_time_str);
    $startTime = $startHour * 60 + $startMin;
    $endTime = $endHour * 60 + $endMin;

    foreach ($SlotUsed as $slot) {
        if ($slot['groomer_employees_id'] != $employeeId) {
            continue;
        }

        if (
            $startTime < $slot['end_minute'] &&
            $endTime > $slot['start_minute']
        ) {
            return false; // Conflict found
        }
    }

    return true; // No conflict, slot is available
}
}