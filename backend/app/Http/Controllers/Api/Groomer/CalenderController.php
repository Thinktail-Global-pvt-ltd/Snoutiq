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
use Illuminate\Support\Facades\DB;


use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalenderController extends Controller
{
    


public function store_doctor_booking(Request $request)
{
    $request->validate([
        'customer_id' => 'required|integer',
        'date'        => 'required|date',
        'start_time'  => 'required',     // 'HH:mm'
        'end_time'    => 'required',     // 'HH:mm'
        'services'    => 'required|array',
        'user_id'     => 'required|integer',
        // optional: if you send these, we'll respect them, else we auto-pick
        'vet_id'      => 'nullable|integer',
        'doctor_id'   => 'nullable|integer',
        'radius_km'   => 'nullable|numeric',   // default 5km
    ]);

    // sum total
    $total = 0;
    foreach ($request->services as $s) { $total += (float) ($s['price'] ?? 0); }

    return DB::transaction(function () use ($request, $total) {

        $chosenVetId    = $request->vet_id;
        $chosenDoctorId = $request->doctor_id;

        // -------------------------------
        // Auto-pick clinic & doctor if missing
        // -------------------------------
        if (!$chosenVetId || !$chosenDoctorId) {

            // 1) get user lat/lng
            $user = DB::table('users')
                ->select('latitude','longitude')
                ->where('id', $request->user_id)
                ->first();

            if (!$user || $user->latitude === null || $user->longitude === null || $user->latitude === '' || $user->longitude === '') {
                return response()->json([
                    'message' => 'User latitude/longitude missing—cannot find nearby clinics.'
                ], 422);
            }

            $lat = (float) $user->latitude;
            $lng = (float) $user->longitude;

            // we’ll try 5km, then expand to 10km, then 25km
            $radii = [(float)($request->radius_km ?? 5), 10.0, 25.0];

            $found = false;

            foreach ($radii as $radiusKm) {
                // 2) nearest clinics within radius (your Haversine)
                $clinics = DB::table('vet_registerations_temp as c')
                    ->select('c.*')
                    ->selectRaw("
                        (6371 * acos(
                            cos(radians(?)) * cos(radians(c.lat)) *
                            cos(radians(c.lng) - radians(?)) +
                            sin(radians(?)) * sin(radians(c.lat))
                        )) AS distance
                    ", [$lat, $lng, $lat])
                    ->whereNotNull('c.lat')
                    ->whereNotNull('c.lng')
                    ->having('distance','<=',$radiusKm)
                    ->orderBy('distance','asc')
                    ->limit(50) // cap
                    ->get();

                // iterate clinics by proximity
                foreach ($clinics as $clinic) {

                    // 3) doctors of this clinic
                    $doctors = DB::table('doctors')
                        ->where('vet_registeration_id', $clinic->id)
                        ->orderBy('id')
                        ->lockForUpdate()   // prevent two threads picking same free doctor simultaneously
                        ->get();

                    if ($doctors->isEmpty()) {
                        // no doctors at this clinic, try next clinic
                        continue;
                    }

                    // 4) check each doctor for overlap (first free wins)
                    foreach ($doctors as $doc) {
                        $busy = DB::table('doctor_bookings')
                            ->where('doctor_id', $doc->id)
                            ->where('date', $request->date)
                            ->where('start_time', '<', $request->end_time)
                            ->where('end_time',   '>', $request->start_time)
                            ->lockForUpdate()
                            ->exists();

                        if (!$busy) {
                            $chosenVetId    = $clinic->id;
                            $chosenDoctorId = $doc->id;
                            $found = true;
                            break 2; // break doctors + clinics
                        }
                    }
                    // if all doctors busy at this clinic, continue to next clinic
                }

                if ($found) break; // booked doctor found in this radius
            }

            if (!$found) {
                return response()->json([
                    'message' => 'No doctors available in nearby clinics for the selected time.'
                ], 422);
            }
        }

        // -------------------------------
        // serial number (per user)
        // -------------------------------
        $old = DB::table('doctor_bookings')
            ->where('user_id', $request->user_id)
            ->orderByDesc('serial_number')
            ->first();
        $serial = $old ? ($old->serial_number + 1) : 1;

        // final safety: ensure selected doctor still free in this TX
        $stillBusy = DB::table('doctor_bookings')
            ->where('doctor_id', $chosenDoctorId)
            ->where('date', $request->date)
            ->where('start_time', '<', $request->end_time)
            ->where('end_time',   '>', $request->start_time)
            ->lockForUpdate()
            ->exists();

        if ($stillBusy) {
            return response()->json([
                'message' => 'Selected doctor just got booked. Please try again.'
            ], 409);
        }

        // create booking
        DB::table('doctor_bookings')->insert([
            'serial_number' => $serial,
            'customer_id'   => $request->customer_id,
            'date'          => $request->date,
            'start_time'    => $request->start_time,
            'end_time'      => $request->end_time,
            'services'      => json_encode($request->services),
            'total'         => $total,
            'paid'          => 0,
            'user_id'       => $request->user_id,
            'vet_id'        => $chosenVetId,
            'doctor_id'     => $chosenDoctorId,
            'status'        => 'Pending',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'message'   => 'Doctor booking created successfully!',
            'vet_id'    => $chosenVetId,
            'doctor_id' => $chosenDoctorId
        ], 200);
    });
}


 public function store_doctor_booking_old(Request $request)
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
    $request->validate(['user_id' => 'required|integer']);

    $rows = DB::table('doctor_bookings as db')
        ->join('vet_registerations_temp as v', 'db.vet_id', '=', 'v.id')
        ->join('doctors as d', 'db.doctor_id', '=', 'd.id')
        ->select(
            'db.id','db.serial_number','db.customer_id','db.date',
            'db.start_time','db.end_time','db.total','db.paid','db.status','db.services',
            'v.id as vet_id',
            'v.name as vet_name',                // ✅ clinic_name -> name
            'v.email as vet_email',
     
            'v.address as vet_address',
            'd.id as doctor_id',
            'd.doctor_name','d.doctor_email','d.doctor_mobile'
        )
        ->where('db.user_id', $request->user_id)
        ->when($request->filled('date'), fn($q) => $q->where('db.date', $request->date))
        ->orderByDesc('db.id')
        ->get();

    $rows = $rows->map(function ($r) {
        return [
            'id'            => $r->id,
            'serial_number' => $r->serial_number,
            'customer_id'   => $r->customer_id,
            'date'          => $r->date,
            'start_time'    => $r->start_time,
            'end_time'      => $r->end_time,
            'total'         => $r->total,
            'paid'          => $r->paid,
            'status'        => $r->status,
            'clinic' => [
                'id'      => $r->vet_id,
                'name'    => $r->vet_name,       // ✅ correct
                'email'   => $r->vet_email,
                    // uses contact_number
                'address' => $r->vet_address,
            ],
            'doctor' => [
                'id'     => $r->doctor_id,
                'name'   => $r->doctor_name,
                'email'  => $r->doctor_email,
                'mobile' => $r->doctor_mobile,
            ],
            'services_list' => json_decode($r->services, true) ?: [],
        ];
    });

    return response()->json(['data' => $rows], 200);
}



public function doctor_bookings_old(Request $request)
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






















////////////////////////////////////////////////////////////////////////
// A) Save / update availability (single window)
////////////////////////////////////////////////////////////////////////
public function doctor_availability_store(Request $request)
{
    $request->validate([
        'doctor_id'    => 'required|integer',
        'vet_id'       => 'required|integer',
        'type'         => 'required|in:recurring,one_off',
        'weekday'      => 'nullable|integer|min:0|max:6',
        'date'         => 'nullable|date',
        'start_time'   => 'required',   // HH:mm
        'end_time'     => 'required',
        'slot_minutes' => 'nullable|integer|min:5|max:180'
    ]);

    $slot = $request->integer('slot_minutes', 30);

    // sanity for recurring / one_off
    if ($request->type === 'recurring' && $request->weekday === null) {
        return response()->json(['message' => 'weekday is required for recurring'], 422);
    }
    if ($request->type === 'one_off' && !$request->date) {
        return response()->json(['message' => 'date is required for one_off'], 422);
    }

    DB::table('doctor_availabilities')->insert([
        'doctor_id'    => $request->doctor_id,
        'vet_id'       => $request->vet_id,
        'type'         => $request->type,
        'weekday'      => $request->type === 'recurring' ? $request->weekday : null,
        'date'         => $request->type === 'one_off'   ? $request->date    : null,
        'start_time'   => $request->start_time,
        'end_time'     => $request->end_time,
        'slot_minutes' => $slot,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    return response()->json(['message' => 'Availability saved'], 200);
}

////////////////////////////////////////////////////////////////////////
// B) Smart suggestions for a doctor on a given date
////////////////////////////////////////////////////////////////////////
public function doctor_availability_suggestions(Request $request)
{
    $request->validate([
        'doctor_id'  => 'required|integer',
        'date'       => 'required|date',
        'radius_km'  => 'nullable|numeric',   // default 10
        'slot'       => 'nullable|integer|min:5|max:180', // default 30
        'day_start'  => 'nullable',  // HH:mm optional (default 08:00)
        'day_end'    => 'nullable',  // HH:mm optional (default 22:00)
    ]);

    $date       = Carbon::parse($request->date)->toDateString();
    $slotMin    = $request->integer('slot', 30);
    $radiusKm   = (float)($request->radius_km ?? 10);
    $dayStart   = $request->input('day_start', '08:00');
    $dayEnd     = $request->input('day_end',   '22:00');

    // doctor + clinic
    $doctor = DB::table('doctors')->where('id', $request->doctor_id)->first();
    if (!$doctor) return response()->json(['message' => 'Doctor not found'], 404);

    $clinic = DB::table('vet_registerations_temp')->where('id', $doctor->vet_registeration_id)->first();
    if (!$clinic || $clinic->lat === null || $clinic->lng === null) {
        return response()->json(['message' => 'Doctor clinic lat/lng missing'], 422);
    }

    // 1) Nearby clinics within radius (Haversine)
    $nearbyClinics = DB::table('vet_registerations_temp as c')
        ->select('c.id','c.lat','c.lng','c.name')
        ->selectRaw("
            (6371 * acos(
                cos(radians(?)) * cos(radians(c.lat)) *
                cos(radians(c.lng) - radians(?)) +
                sin(radians(?)) * sin(radians(c.lat))
            )) AS distance
        ", [$clinic->lat, $clinic->lng, $clinic->lat])
        ->whereNotNull('c.lat')->whereNotNull('c.lng')
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance','asc')
        ->get();

    if ($nearbyClinics->isEmpty()) {
        return response()->json(['message' => 'No clinics found in radius'], 422);
    }

    $clinicIds = $nearbyClinics->pluck('id')->all();

    // 2) Doctors in those clinics
    $doctorIds = DB::table('doctors')
        ->whereIn('vet_registeration_id', $clinicIds)
        ->pluck('id')
        ->all();

    // 3) Build a day grid (HH:mm) in slotMin steps
    $grid = [];
    $start = Carbon::parse("$date $dayStart");
    $end   = Carbon::parse("$date $dayEnd");
    foreach (CarbonPeriod::create($start, "{$slotMin} minutes", $end->copy()->subMinutes($slotMin)) as $t) {
        $key = $t->format('H:i');
        $grid[$key] = 0; // availability count
    }

    // 4) For each doctor in area, mark slots they are AVAILABLE on that date
    $weekday = Carbon::parse($date)->dayOfWeek; // 0..6

    foreach ($doctorIds as $docId) {
        // availability intervals (recurring + one-off)
        $avail = DB::table('doctor_availabilities')
            ->where('doctor_id', $docId)
            ->where(function($q) use ($date, $weekday){
                $q->where(function($qq) use ($weekday){
                    $qq->where('type','recurring')->where('weekday', $weekday);
                })
                ->orWhere(function($qq) use ($date){
                    $qq->where('type','one_off')->where('date', $date);
                });
            })
            ->get();

        if ($avail->isEmpty()) continue;

        // the doctor’s bookings (busy intervals) on that date
        $busy = DB::table('doctor_bookings')
            ->where('doctor_id', $docId)
            ->where('date', $date)
            ->select('start_time','end_time')
            ->get();

        // time-offs on that date
        $offs = DB::table('doctor_time_offs')
            ->where('doctor_id', $docId)
            ->where('date', $date)
            ->select('start_time','end_time')
            ->get();

        // mark slots covered by availability but not intersecting busy/off
        foreach ($avail as $a) {
            $aStart = Carbon::parse("$date {$a->start_time}");
            $aEnd   = Carbon::parse("$date {$a->end_time}");

            foreach ($grid as $hhmm => $count) {
                $slotStart = Carbon::parse("$date $hhmm");
                $slotEnd   = $slotStart->copy()->addMinutes($slotMin);

                // slot within availability
                $inAvail = $slotStart >= $aStart && $slotEnd <= $aEnd;

                // slot overlaps any booking?
                $overlapsBooking = false;
                foreach ($busy as $b) {
                    $bStart = Carbon::parse("$date {$b->start_time}");
                    $bEnd   = Carbon::parse("$date {$b->end_time}");
                    if ($slotStart < $bEnd && $slotEnd > $bStart) { $overlapsBooking = true; break; }
                }

                // slot overlaps any time-off?
                $overlapsOff = false;
                foreach ($offs as $o) {
                    $oStart = Carbon::parse("$date {$o->start_time}");
                    $oEnd   = Carbon::parse("$date {$o->end_time}");
                    if ($slotStart < $oEnd && $slotEnd > $oStart) { $overlapsOff = true; break; }
                }

                if ($inAvail && !$overlapsBooking && !$overlapsOff) {
                    $grid[$hhmm] = $grid[$hhmm] + 1; // one more doctor available here
                }
            }
        }
    }

    // 5) Remove current doctor’s own busy/off slots from suggestions, and de-dupe times he already offers
    $currBookings = DB::table('doctor_bookings')
        ->where('doctor_id', $request->doctor_id)->where('date', $date)
        ->select('start_time','end_time')->get();

    $currAvail = DB::table('doctor_availabilities')
        ->where('doctor_id', $request->doctor_id)
        ->where(function($q) use ($date,$weekday){
            $q->where(function($qq) use ($weekday){ $qq->where('type','recurring')->where('weekday',$weekday); })
              ->orWhere(function($qq) use ($date){ $qq->where('type','one_off')->where('date',$date); });
        })->get();

    $currOffs = DB::table('doctor_time_offs')
        ->where('doctor_id', $request->doctor_id)->where('date', $date)
        ->select('start_time','end_time')->get();

    // 6) Rank suggestions by LOWEST neighborhood availability
    $suggestions = [];
    foreach ($grid as $hhmm => $count) {
        $slotStart = Carbon::parse("$date $hhmm");
        $slotEnd   = $slotStart->copy()->addMinutes($slotMin);

        // skip if current doctor already offers this slot
        $alreadyOffered = false;
        foreach ($currAvail as $a) {
            $aStart = Carbon::parse("$date {$a->start_time}");
            $aEnd   = Carbon::parse("$date {$a->end_time}");
            if ($slotStart >= $aStart && $slotEnd <= $aEnd) { $alreadyOffered = true; break; }
        }
        if ($alreadyOffered) continue;

        // skip if doctor booked or off
        $conflict = false;
        foreach ($currBookings as $b) {
            $bStart = Carbon::parse("$date {$b->start_time}");
            $bEnd   = Carbon::parse("$date {$b->end_time}");
            if ($slotStart < $bEnd && $slotEnd > $bStart) { $conflict = true; break; }
        }
        if ($conflict) continue;

        foreach ($currOffs as $o) {
            $oStart = Carbon::parse("$date {$o->start_time}");
            $oEnd   = Carbon::parse("$date {$o->end_time}");
            if ($slotStart < $oEnd && $slotEnd > $oStart) { $conflict = true; break; }
        }
        if ($conflict) continue;

        $suggestions[] = [
            'start_time' => $hhmm,
            'end_time'   => $slotEnd->format('H:i'),
            'neighbor_available_doctors' => $count,     // lower is better
        ];
    }

    // sort by ascending availability (valleys), then by time
    usort($suggestions, function($a,$b){
        if ($a['neighbor_available_doctors'] === $b['neighbor_available_doctors']) {
            return strcmp($a['start_time'], $b['start_time']);
        }
        return $a['neighbor_available_doctors'] <=> $b['neighbor_available_doctors'];
    });

    // top N (e.g., 10)
    $top = array_slice($suggestions, 0, 10);

    return response()->json([
        'date'        => $date,
        'slot_minutes'=> $slotMin,
        'clinic_ids_considered' => $clinicIds,
        'suggestions' => $top
    ], 200);
}

}