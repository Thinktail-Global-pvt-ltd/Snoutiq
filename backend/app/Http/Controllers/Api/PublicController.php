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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
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
        $limit = max(1, min((int) ($data['limit'] ?? 20), 50));

        $result = $this->fallbackClinicPlacesFromDatabase($location, $latitude, $longitude, $limit);

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'user_id' => (int) $user->id,
            'clinics' => $result['places'] ?? [],
            'count' => (int) ($result['count'] ?? 0),
            'source' => $result['source'] ?? null,
            'location' => $result['location'] ?? $location,
            'message' => ($result['success'] ?? false)
                ? 'Clinics loaded successfully.'
                : ($result['error'] ?? 'Clinics could not be loaded.'),
        ], ($result['success'] ?? false) ? 200 : 500);
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

    private function fallbackClinicPlacesFromDatabase(?string $location, ?float $latitude, ?float $longitude, int $limit): array
    {
        if (!Schema::hasTable('vet_registerations_temp')) {
            return [
                'success' => false,
                'kind' => 'nearby_places',
                'type' => 'clinic',
                'error' => 'Google Places is unavailable and local clinic table is missing.',
            ];
        }

        $limit = max(1, min($limit, 50));
        $query = DB::table('vet_registerations_temp')
            ->select('vet_registerations_temp.*')
            ->limit($limit);

        $hasLat = Schema::hasColumn('vet_registerations_temp', 'lat');
        $hasLng = Schema::hasColumn('vet_registerations_temp', 'lng');
        $hasCoordinates = Schema::hasColumn('vet_registerations_temp', 'coordinates');
        $canSortByDistance = $latitude !== null && $longitude !== null && ($hasLat || $hasCoordinates);

        if (Schema::hasColumn('vet_registerations_temp', 'status')) {
            $query->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'draft');
            });
        }

        if ($canSortByDistance) {
            $clinicLatExpr = $hasLat
                ? 'vet_registerations_temp.lat'
                : "CASE WHEN JSON_VALID(vet_registerations_temp.coordinates) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(vet_registerations_temp.coordinates, '$[0]')) AS DECIMAL(10,7)) ELSE NULL END";
            $clinicLngExpr = $hasLng
                ? 'vet_registerations_temp.lng'
                : "CASE WHEN JSON_VALID(vet_registerations_temp.coordinates) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(vet_registerations_temp.coordinates, '$[1]')) AS DECIMAL(10,7)) ELSE NULL END";

            if ($hasLat && $hasCoordinates) {
                $clinicLatExpr = "COALESCE(vet_registerations_temp.lat, CASE WHEN JSON_VALID(vet_registerations_temp.coordinates) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(vet_registerations_temp.coordinates, '$[0]')) AS DECIMAL(10,7)) ELSE NULL END)";
            }
            if ($hasLng && $hasCoordinates) {
                $clinicLngExpr = "COALESCE(vet_registerations_temp.lng, CASE WHEN JSON_VALID(vet_registerations_temp.coordinates) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(vet_registerations_temp.coordinates, '$[1]')) AS DECIMAL(10,7)) ELSE NULL END)";
            }
            $distanceScoreExpr = "
                cos(radians(?)) * cos(radians({$clinicLatExpr})) *
                cos(radians({$clinicLngExpr}) - radians(?)) +
                sin(radians(?)) * sin(radians({$clinicLatExpr}))
            ";

            $query->selectRaw("{$clinicLatExpr} AS resolved_lat")
                ->selectRaw("{$clinicLngExpr} AS resolved_lng")
                ->selectRaw("
                    (6371 * acos(
                        CASE
                            WHEN ({$distanceScoreExpr}) > 1 THEN 1
                            WHEN ({$distanceScoreExpr}) < -1 THEN -1
                            ELSE ({$distanceScoreExpr})
                        END
                    )) AS distance_km
                ", [
                    $latitude, $longitude, $latitude,
                    $latitude, $longitude, $latitude,
                    $latitude, $longitude, $latitude,
                ])
                ->whereRaw("{$clinicLatExpr} IS NOT NULL")
                ->whereRaw("{$clinicLngExpr} IS NOT NULL")
                ->orderBy('distance_km');
        } elseif ($location !== null && Schema::hasColumn('vet_registerations_temp', 'city')) {
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', '%' . $location . '%');
                if (Schema::hasColumn('vet_registerations_temp', 'address')) {
                    $q->orWhere('address', 'like', '%' . $location . '%');
                }
                if (Schema::hasColumn('vet_registerations_temp', 'formatted_address')) {
                    $q->orWhere('formatted_address', 'like', '%' . $location . '%');
                }
            })->orderBy('name');
        } else {
            $query->orderBy('name');
        }

        $clinics = $query->get();
        $places = $clinics
            ->map(fn ($clinic) => $this->mapDatabaseClinicToPlace((object) $clinic))
            ->filter()
            ->values()
            ->all();

        return [
            'success' => true,
            'kind' => 'nearby_places',
            'type' => 'clinic',
            'label' => 'Clinic',
            'query' => 'veterinary clinic',
            'location' => $location ?? ($latitude !== null && $longitude !== null ? sprintf('%.5f, %.5f', $latitude, $longitude) : null),
            'count' => count($places),
            'places' => $places,
            'supported_types' => ['clinic'],
            'source' => 'snoutiq_database',
            'note' => count($places) === 0 ? 'No nearby clinic results found in local database.' : null,
        ];
    }

    private function mapDatabaseClinicToPlace(object $clinic): ?array
    {
        $name = trim((string) ($clinic->name ?? $clinic->clinic_profile ?? $clinic->hospital_profile ?? ''));
        if ($name === '') {
            return null;
        }

        $latitude = isset($clinic->resolved_lat) && is_numeric($clinic->resolved_lat)
            ? (float) $clinic->resolved_lat
            : (isset($clinic->lat) && is_numeric($clinic->lat) ? (float) $clinic->lat : null);
        $longitude = isset($clinic->resolved_lng) && is_numeric($clinic->resolved_lng)
            ? (float) $clinic->resolved_lng
            : (isset($clinic->lng) && is_numeric($clinic->lng) ? (float) $clinic->lng : null);
        $placeId = trim((string) ($clinic->place_id ?? ''));

        return [
            'name' => $name,
            'address' => trim((string) ($clinic->formatted_address ?? $clinic->address ?? '')),
            'rating' => isset($clinic->rating) && is_numeric($clinic->rating) ? (float) $clinic->rating : null,
            'user_ratings_total' => isset($clinic->user_ratings_total) && is_numeric($clinic->user_ratings_total)
                ? (int) $clinic->user_ratings_total
                : null,
            'open_now' => isset($clinic->open_now) ? (bool) $clinic->open_now : null,
            'available_hours' => null,
            'active_hours_today' => null,
            'active_hours_status' => null,
            'place_id' => $placeId !== '' ? $placeId : 'snoutiq_clinic_' . (int) ($clinic->id ?? 0),
            'maps_link' => $placeId !== ''
                ? 'https://www.google.com/maps/place/?q=place_id:' . rawurlencode($placeId)
                : ($latitude !== null && $longitude !== null ? sprintf('https://www.google.com/maps/search/?api=1&query=%.7F,%.7F', $latitude, $longitude) : null),
            'business_status' => $clinic->business_status ?? null,
            'latitude' => $latitude !== null ? round($latitude, 7) : null,
            'longitude' => $longitude !== null ? round($longitude, 7) : null,
            'distance_km' => isset($clinic->distance_km) && is_numeric($clinic->distance_km) ? round((float) $clinic->distance_km, 2) : null,
            'suggested_slots' => [],
            'slot_source' => null,
            'phone' => trim((string) ($clinic->mobile ?? '')) ?: null,
            'website' => null,
            'clinic_id' => isset($clinic->id) ? (int) $clinic->id : null,
            'source' => 'snoutiq_database',
        ];
    }
}
