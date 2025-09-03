<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserPet;
 use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\GroomerProfile;
use App\Models\GroomerService;
use App\Models\UserRating;
use Illuminate\Support\Facades\Http;

class PublicController extends Controller
{
    //
    public function groomers(Request $request){
          $limit = 1000000;
        if(filled($request->limit)){
        $limit = $request->limit;

        }
        $GroomerProfile = GroomerProfile::where('status',1)->limit($limit)->get()->map(function($data){
            return array_merge($data->toArray(),[
'grooming_services'=>GroomerService::where('user_id',$data->user_id)->where('status','Active')->with("category")->get()
,'rating'=>UserRating::where('servicer_id', $data->user_id)->avg('rating')
            ]);
        });
        return response()->json([
            'groomers'=>$GroomerProfile
        ]);
    }
    public function single_groomer($id){
                $GroomerProfile = GroomerProfile::where('status',1)->where('user_id',$id)->first();
                if(!$GroomerProfile){
                    return response()->json([
                        'message'=>'Profile not found!'
                    ],404);
                }
                $GroomerProfile->grooming_services = GroomerService::where('user_id',$GroomerProfile->user_id)->where('status','Active')->with("category")->get();
                $GroomerProfile->coordinates = json_decode($GroomerProfile->coordinates);
                return response()->json([
                    'data'=>$GroomerProfile
                ]);
    }

    public function fetchNearbyPlaces(Request $request)
{
    $lat = $request->input('lat'); // e.g., 28.6139
    $lng = $request->input('lng'); // e.g., 77.2090
    $radius = 5000; // in meters
    $apiKey = env('GOOGLE_API_KEY');
// dd($apiKey);
    // Fetch vets (official type)
    $vetsResponse = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
        'location' => "$lat,$lng",
        'radius' => $radius,
        'type' => 'veterinary_care',
        'key' => $apiKey,
    ]);

    // Fetch groomers (using keyword, as there's no official type)
    $groomersResponse = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
        'location' => "$lat,$lng",
        'radius' => $radius,
        'keyword' => 'pet grooming',
        'key' => $apiKey,
    ]);
// dd($vetsResponse->json(), $groomersResponse->json());

    return response()->json([
        'vets' => $vetsResponse->successful() ? $vetsResponse->json()['results'] : [],
        'groomers' => $groomersResponse->successful() ? $groomersResponse->json()['results'] : [],
    ]);
}
}
