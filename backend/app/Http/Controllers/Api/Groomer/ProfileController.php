<?php

namespace App\Http\Controllers\Api\Groomer;

use Illuminate\Http\Request;
use App\Models\GroomerProfile;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\GroomerBooking;
use App\Models\GroomerClient;
use App\Models\UserProfile;
use App\Models\UserRating;
use Illuminate\Http\Client\ResponseSequence;

class ProfileController extends Controller
{
    //
   public function store(Request $request){
    $request->validate([
        'name'=>'required|max:60',
'bio'=>'required|max:60',
'address'=>'required|max:225',
'coordinates'=>'required|max:60',
'city'=>'required|max:60',
'pincode'=>'required|digits:6',
'license_no'=>'max:60',
'chat_price'=>'required',
'profile_picture'=>'image|mimes:jpeg,png,jpg|max:2048'
    ]);
    $data = [
        'name'=>$request->name,
'bio'=>$request->bio,
'address'=>$request->address,
'coordinates'=>$request->coordinates,
'city'=>$request->city,
'pincode'=>$request->pincode,
'license_no'=>$request->license_no,
'chat_price'=>$request->chat_price,
'user_id'=>$request->user()->id
    ];
// 
  if ($request->hasFile('profile_picture')) {
        $image     = $request->file('profile_picture');
        $imageName = 'photo_' . $request->user()->id . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path      = public_path('photo');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $image->move($path, $imageName);

       $data['profile_picture'] = 'photo/' . $imageName;
    }
$data['inhome_grooming_services']=$request->inhome_grooming_services;
$data['type'] = $request->user()->role;
//    return $request->coordinates;
    $old = GroomerProfile::where('user_id',$request->user()->id)->first();
    if($old){
        $old->update($data);
    }else{
    $GroomerProfile = GroomerProfile::create($data);
    }
    return response()->json([
        'message'=>'Profile updated successfully!'
    ]);
   }
   public function get(Request $request){
    $GroomerProfile = GroomerProfile::where('user_id',$request->user()->id)->first();
    if($GroomerProfile){
// Decode to array
$coords = json_decode($GroomerProfile->coordinates, true);

// Ensure it's an array and cast all values to float
if (is_array($coords)) {
    foreach ($coords as $key => $value) {
        $coords[$key] = floatval($value);
    }
}

// Assign modified array back to the model
$GroomerProfile->coordinates = $coords;

        return response()->json([
        'data'=>$GroomerProfile
    ]);
    }
    return response()->json([
        'message'=>"Profile not completed"
    ],404);
   }
   public function dashboard(Request $request){
    $uid = $request->user()->id;
    $data = [];
    $data['status'] = true;
    $data['data'] = [

    ];
    $fetch_todaysAppointments = GroomerBooking::where('user_id',$uid)->where('date',Carbon::now()->toDateString())->get()->map(function($d) use($uid){
$data = $d;
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
        return [
    'id'=>$d->id,
    'customer'=>$customer_data['name'],
    'time'=>$d->start_time,
    'service'=>''
];
    });
    $data['data']['todaysAppointments'] = $fetch_todaysAppointments;
   $today = Carbon::now();
$startOfWeek = $today->copy()->startOfWeek(); // Monday
$startOfMonth = $today->copy()->startOfMonth();

$data['data']['revenue'] = [
    'daily' => GroomerBooking::where('user_id', $uid)
                ->whereDate('date', $today->toDateString())
                ->sum('total'),

    'weekly' => GroomerBooking::where('user_id', $uid)
                ->whereBetween('date', [$startOfWeek->toDateString(), $today->toDateString()])
                ->sum('total'),

    'monthly' => GroomerBooking::where('user_id', $uid)
                ->whereBetween('date', [$startOfMonth->toDateString(), $today->toDateString()])
                ->sum('total'),
];
$customerActivity = [
    // Count of new bookings created today
    'newBookings' => GroomerBooking::where('user_id', $uid)
                        ->whereDate('created_at', Carbon::today())
                        ->count(),

    // Count of repeat bookings: customer_id that has more than 1 total booking
    'repeatBookings' => GroomerBooking::where('user_id', $uid)
                            ->whereDate('created_at', Carbon::today())
                            ->whereIn('customer_id', function ($query) use ($uid) {
                                $query->select('customer_id')
                                      ->from('groomer_bookings')
                                      ->where('user_id', $uid)
                                      ->groupBy('customer_id')
                                      ->havingRaw('COUNT(*) > 1');
                            })->count(),

    // Count of cancellations done today
    'cancellations' => GroomerBooking::where('user_id', $uid)
                            ->whereDate('updated_at', Carbon::today())
                            ->where('status', 'cancelled')
                            ->count(),
];
$data['data']['customerActivity'] = $customerActivity;
$data['data']['alerts'] = [
    'missedAppointments'=>[],
    'lowRatings'=>[],
    'paymentIssues'=>[]
];
return response()->json($data);
   }
   public function ratings(Request $request){
    $avg_rating = UserRating::where('servicer_id', $request->user()->id)->avg('rating');
    return response()->json([
        'avg_rating'=>$avg_rating,
        'ratings'=>UserRating::where('servicer_id', $request->user()->id)->with("user")->get()
    ]);
   }
}
