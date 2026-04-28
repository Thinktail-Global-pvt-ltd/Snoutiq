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

    public function userNearbyClinics(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'location' => ['nullable', 'string', 'max:120'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $user = User::find((int) $data['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $location = trim((string) ($data['location'] ?? $user->city ?? ''));
        $location = $location !== '' ? $location : null;
        $latitude = $request->filled('lat')
            ? (float) $request->input('lat')
            : ($request->filled('latitude') ? (float) $request->input('latitude') : ($user->latitude !== null && $user->latitude !== '' ? (float) $user->latitude : null));
        $longitude = $request->filled('lng')
            ? (float) $request->input('lng')
            : ($request->filled('longitude') ? (float) $request->input('longitude') : ($user->longitude !== null && $user->longitude !== '' ? (float) $user->longitude : null));
        $limit = max(1, min((int) ($data['limit'] ?? 5), 10));

        if (($latitude === null || $longitude === null) && $location === null) {
            return response()->json([
                'success' => false,
                'message' => 'User location is missing. Save latitude/longitude, city, or pass location in the request.',
            ], 422);
        }

        /** @var GooglePlacesLookupService $places */
        $places = app(GooglePlacesLookupService::class);
        $result = $places->search('clinic', $location, $latitude, $longitude, $limit);
        $hasCoordinates = $latitude !== null && $longitude !== null;

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'user_id' => (int) $user->id,
            'clinics' => $result['places'] ?? [],
            'count' => (int) ($result['count'] ?? 0),
            'source' => $result['source'] ?? null,
            'location' => $result['location'] ?? $location,
            'range_km' => $hasCoordinates ? 5 : null,
            'range_note' => $hasCoordinates
                ? 'Google Nearby Search uses a 5 km radius from the saved/requested latitude and longitude.'
                : 'Google Text Search is used because only a city/location string is available; it does not enforce a fixed km radius.',
            'message' => ($result['success'] ?? false)
                ? 'Clinics loaded successfully.'
                : ($result['error'] ?? 'Clinics could not be loaded.'),
        ], ($result['success'] ?? false) ? 200 : (($result['requires_location'] ?? false) ? 422 : 500));
    }

    public function updateUserLocation(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'location' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'long' => ['nullable', 'numeric', 'between:-180,180'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $user = User::find((int) $data['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $city = trim((string) ($data['location'] ?? $data['city'] ?? ''));
        $latitude = $request->filled('lat')
            ? (float) $request->input('lat')
            : ($request->filled('latitude') ? (float) $request->input('latitude') : null);
        $longitude = $request->filled('lng')
            ? (float) $request->input('lng')
            : ($request->filled('longitude')
                ? (float) $request->input('longitude')
                : ($request->filled('long')
                    ? (float) $request->input('long')
                    : ($request->filled('lon') ? (float) $request->input('lon') : null)));

        if (($latitude === null) xor ($longitude === null)) {
            return response()->json([
                'success' => false,
                'message' => 'Both latitude and longitude are required when saving coordinates.',
            ], 422);
        }

        if ($city === '' && $latitude === null && $longitude === null) {
            return response()->json([
                'success' => false,
                'message' => 'Send city/location or latitude and longitude.',
            ], 422);
        }

        if ($city !== '') {
            $user->city = $city;
        }
        if ($latitude !== null && $longitude !== null) {
            $user->latitude = $latitude;
            $user->longitude = $longitude;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User location updated successfully.',
            'data' => [
                'user_id' => (int) $user->id,
                'city' => $user->city,
                'latitude' => $user->latitude !== null && $user->latitude !== '' ? (float) $user->latitude : null,
                'longitude' => $user->longitude !== null && $user->longitude !== '' ? (float) $user->longitude : null,
            ],
        ]);
    }

}
