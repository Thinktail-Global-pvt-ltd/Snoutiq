<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\GooglePlacesLookupService;

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
    $service = app(GooglePlacesLookupService::class);
    $placeType = trim((string) $request->input('place_type', ''));
    $location = trim((string) $request->input('location', ''));
    $location = $location !== '' ? $location : null;
    $latitude = $request->filled('lat') ? (float) $request->input('lat') : ($request->filled('latitude') ? (float) $request->input('latitude') : null);
    $longitude = $request->filled('lng') ? (float) $request->input('lng') : ($request->filled('longitude') ? (float) $request->input('longitude') : null);
    $limit = max(1, min((int) $request->input('limit', 5), 10));

    if ($placeType !== '') {
        $result = $service->search($placeType, $location, $latitude, $longitude, $limit);
        $status = ($result['success'] ?? false)
            ? 200
            : (($result['requires_location'] ?? false) ? 422 : 500);

        return response()->json($result, $status);
    }

    if (($latitude === null || $longitude === null) && $location === null) {
        return response()->json([
            'success' => false,
            'message' => 'location or lat/lng is required',
            'supported_types' => $service->supportedTypes(),
        ], 422);
    }

    $clinicResult = $service->search('clinic', $location, $latitude, $longitude, $limit);
    $groomerResult = $service->search('groomer', $location, $latitude, $longitude, $limit);

    return response()->json([
        'vets' => $clinicResult['places'] ?? [],
        'groomers' => $groomerResult['places'] ?? [],
        'supported_types' => $service->supportedTypes(),
        'location' => $clinicResult['location'] ?? $groomerResult['location'] ?? $location,
    ]);
}
}
