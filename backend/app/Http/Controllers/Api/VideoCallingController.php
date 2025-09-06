<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoCallingController extends Controller
{
    public function nearbyVets(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'user_id is required'
            ], 422);
        }

        // 1) User lat/lng lao
        $user = DB::table('users')
            ->select('latitude', 'longitude')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if ($user->latitude === null || $user->longitude === null || $user->latitude === '' || $user->longitude === '') {
            return response()->json(['status' => 'error', 'message' => 'User lat/long missing'], 422);
        }

        // 2) Numbers ensure karo
        $lat = (float) $user->latitude;
        $lng = (float) $user->longitude;
        $radiusKm = 5;

        // 3) Haversine with bindings
        $vets = DB::table('vet_registerations_temp')
            ->select('vet_registerations_temp.*')
            ->selectRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(vet_registerations_temp.lat)) *
                    cos(radians(vet_registerations_temp.lng) - radians(?)) +
                    sin(radians(?)) * sin(radians(vet_registerations_temp.lat))
                )) AS distance
            ", [$lat, $lng, $lat])
            ->whereNotNull('vet_registerations_temp.lat')
            ->whereNotNull('vet_registerations_temp.lng')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $vets,
        ]);
    }
}

