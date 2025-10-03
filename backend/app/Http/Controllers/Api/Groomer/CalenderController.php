<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerBlockTime;
use App\Models\GroomerBooking;
use Illuminate\Http\Request;
use App\Models\GroomerClient;
use App\Models\GroomerClientPet;
use App\Models\DoctorBooking;

use App\Models\VetRegisterationTemp;
use App\Models\GroomerService;
use App\Models\UserPet;
use App\Models\UserProfile;
use App\Models\GroomerEmployee;

class CalenderController extends Controller
{
 public function store_doctor_booking(Request $request)
{
    $request->validate([
        'customer_id' => 'required|integer',
        'date' => 'required|date',
        'start_time' => 'required',
        'end_time' => 'required',
        'services' => 'required|array',
        'vet_id' => 'required|integer',
        'user_id' => 'required|integer'
    ]);

    $total = 0;
    foreach ($request->services as $srv) {
        $total += $srv['price'];
    }

    $old_booking = DoctorBooking::where('user_id', $request->user_id)
        ->orderBy('serial_number', 'desc')
        ->first();

    DoctorBooking::create([
        'serial_number' => $old_booking ? $old_booking->serial_number + 1 : 1,
        'customer_id'   => $request->customer_id,
        'date'          => $request->date,
        'start_time'    => $request->start_time,
        'end_time'      => $request->end_time,
        'services'      => json_encode($request->services),
        'total'         => $total,
        'paid'          => 0,
        'user_id'       => $request->user_id,
        'vet_id'        => $request->vet_id,
        'status'        => 'Pending'
    ]);

    return response()->json(['message' => 'Doctor booking created successfully!']);
}

// public function doctor_bookings(Request $request)
// {
//     $request->validate([
//         'user_id' => 'required|integer'
//     ]);
//     $uid = $request->user_id;

//     $bookings = DoctorBooking::where('user_id', $uid)
//         ->with('vet')
//         ->orderBy('id', 'desc')
//         ->get()
//         ->map(function ($data) {
//             $services = [];
//             foreach (json_decode($data->services) as $srv) {
//                 $services[] = [
//                     'service_id' => $srv->service_id,
//                     'price'      => $srv->price
//                 ];
//             }

//             return array_merge($data->toArray(), [
//                 'vet_data' => $data->vet,
//                 'services_list' => $services
//             ]);
//         });

//     return response()->json(['data' => $bookings]);
// }

public function doctor_bookings(Request $request)
{
    try {
        $bookings = \DB::table('doctor_bookings as db')
            ->join('vet_registerations_temp as v', 'db.vet_id', '=', 'v.id')
            ->select(
                'db.id',
                'db.serial_number',
                'db.customer_id',
                'db.date',
                'db.start_time',
                'db.end_time',
                'db.total',
                'db.paid',
                'db.status',
                'db.services',
                'v.id as vet_id',
                'v.name as vet_name',
                'v.email as vet_email',
             
                'v.address as vet_address'
            )
            ->where('db.user_id', $request->user_id)
            ->orderBy('db.id', 'desc')
            ->get();

        return response()->json([
            'data' => $bookings
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error'   => true,
            'message' => 'Something went wrong while fetching doctor bookings',
            'details' => $e->getMessage(),
        ], 500);
    }
}




public function store_booking(Request $request)
{
    try {
       // // Debug incoming request
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

        $blockedTime = self::blockedTime($request->user_id, $request->date);
       // dd( $blockedTime);

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

        $old_booking = GroomerBooking::where('user_id', $request->user_id)
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
            'user_id'             => $request->user_id,
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

public function bookings(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer'
    ]);
    $uid = $request->user_id;

    $GroomerBooking = GroomerBooking::where('user_id', $uid)
        ->with("groomerEmployee");

    if (filled($request->date)) {
        $GroomerBooking->where('date', $request->date);
    }
    if (filled($request->customer_id)) {
        $GroomerBooking->where('customer_id', $request->customer_id);
    }

    $GroomerBooking = $GroomerBooking->orderBy('id', 'desc')->get()->map(function ($data) use ($uid) {
        // doctor data
        $doctor = GroomerEmployee::find($data->groomer_employees_id);
        $customer_data = $doctor ? [
            'id' => $doctor->id,
            'name' => $doctor->name,
            'email' => $doctor->email,
            'phone' => $doctor->phone,
            'job_title' => $doctor->job_title
        ] : null;

        // services
        $services = [];
        foreach (json_decode($data->services) as $ddd) {
            $service = GroomerService::where('user_id', $uid)
                ->where('id', $ddd->service_id)->first();

            if ($service) {
                $services[] = [
                    'name'  => $service->name,
                    'price' => $ddd->price
                ];
            }
        }

        return array_merge($data->toArray(), [
            'customer_data'    => $customer_data, // doctor info
            'pet_data'         => null,           // no pets for doctors
            'service_withnames'=> $services
        ]);
    });

    return response()->json(['data' => $GroomerBooking], 200);
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