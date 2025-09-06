<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
 use Illuminate\Support\Facades\DB;
class VideoCallingController extends Controller
{
   

public function nearbyVets($userId)
{
    // user ka lat/long fetch karo
    $user = DB::table('users')->select('latitude','longitude')->where('id',$userId)->first();
    if (!$user || !$user->latitude || !$user->longitude) {
        return [];
    }

    $latitude  = $user->latitude;
    $longitude = $user->longitude;
    $radius    = 5; // KM

    $vets = DB::table('vet_registerations_temp')
        ->select(
            'vet_registerations_temp.*',
            DB::raw("(
                6371 * acos(
                    cos(radians($latitude)) * cos(radians(vet_registerations_temp.lat)) 
                    * cos(radians(vet_registerations_temp.lng) - radians($longitude)) 
                    + sin(radians($latitude)) * sin(radians(vet_registerations_temp.lat))
                )
            ) AS distance")
        )
        ->having('distance', '<=', $radius)
        ->orderBy('distance')
        ->get();

    return $vets;
}

}
