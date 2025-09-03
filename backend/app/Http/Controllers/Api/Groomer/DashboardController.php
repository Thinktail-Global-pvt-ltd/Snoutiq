<?php

namespace App\Http\Controllers\Api\Groomer;
use App\Http\Controllers\Controller;

use App\Models\GroomerClient;
use App\Models\GroomerClientPet;
use Illuminate\Http\Request;
use App\Models\GroomerBooking;
use App\Models\GroomerEmployee;
use App\Models\GroomerProfile;
use App\Models\UserProfile;
use App\Models\GroomerService;
use App\Models\UserPet;
use App\Models\UserRating;

class DashboardController extends Controller
{
    public function dashboardv2(Request $request){
        $uid = $request->user()->id;
        $data = array();
        $data['earnings'] = GroomerBooking::where('user_id', $uid)
                ->sum('total');
        $data['total_bookings']= GroomerBooking::where('user_id', $uid)
                        ->count();
        $data['avg_rating'] = UserRating::where('servicer_id', $uid)->avg('rating'); ; //Using demo for now
        $data['growth_rate'] = '--' ; //Using demo for now
        $data['booking_requests'] = self::getBooking($uid,'pending');
        $data['booking_accepted'] = self::getBooking($uid,'accepted');
        return response()->json(['data'=>$data]);
       
    }

    public function getBooking($uid,$type){
        return   GroomerBooking::where('user_id',$uid)->with("groomerEmployee")
->orderBy('id','desc')->where('status',$type)->get()->map(function($data) use($uid){
     $services = [];
        // return ['s'=>$data->services];
        foreach(json_decode($data->services) as $ddd){
            // return $ddd;
            $service = GroomerService::where('user_id',$uid)->where('id',$ddd->service_id)->first();
$services[]=['name'=>$service?->name??'---','price'=>$ddd->price];
        }

    $dataRes = [
        'id'=> $data->id,
     
      'time'=> $data->start_time,
    'date'=> $data->date,
    'services'=>$services ,
    'visitType'=> $data->is_inhome == 1?'At Home':'At Clinic',
    'price'=> $data->total,
    'status'=> $data->status
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
    }

    public function onboarding_tasks(Request $request){
        $tasks = [];
        $GroomerProfile = GroomerProfile::where('user_id',$request->user()->id)->first();
        if(!$GroomerProfile){
$tasks[] = [
    'title'=>'Complete your profile',
    'cta_text'=>'Profile',
    'action'=>'/create-profile'
];
        }
        $GroomerService = GroomerService::where('user_id',$request->user()->id)->count();
        if($GroomerService==0){
               $tasks[] = [
    'title'=>'Create your first service',
    'cta_text'=>'Create Service',
    'action'=>'/dashboard/add-service'
]; 
        }
        $GroomerEmployee = GroomerEmployee::where('user_id',$request->user()->id)->count();
        if($GroomerEmployee==0){
           $tasks[] = [
    'title'=>'Create your first employee',
    'cta_text'=>'Create Employee',
    'action'=>'/dashboard/employee/create'
]; 
        }

return response()->json(
    ['tasks'=>$tasks]
);
        // $data['profile_built'] 
    }
}