<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class DogBreedController extends Controller
{
    public function getBreedImage($breed)
    {
        // convert underscores to empty string (like your JS replace)
        $breedSlug = str_replace('_', '', strtolower($breed));

        $url = "https://dog.ceo/api/breed/{$breedSlug}/images/random";

        $resp = Http::get($url);

        if (!$resp->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Breed not found or API error'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'breed'  => $breed,
            'image'  => $resp->json('message'),
        ]);
    }

    public function allBreeds()
{
    $resp = Http::get("https://dog.ceo/api/breeds/list/all");

    if (!$resp->successful()) {
        return response()->json(['status' => 'error'], 500);
    }

    return response()->json([
        'status' => 'success',
        'breeds' => $resp->json('message')
    ]);
}

}
