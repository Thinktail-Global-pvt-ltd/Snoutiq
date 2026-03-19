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
    $resp = Http::acceptJson()
        ->timeout(20)
        ->get('https://dogapi.dog/api/v2/breeds');

    if (!$resp->successful()) {
        return response()->json(['status' => 'error'], 500);
    }

    $rows = $resp->json('data');
    if (!is_array($rows)) {
        $rows = [];
    }

    // Keep response structure exactly same as existing endpoint:
    // { status: "success", breeds: { "<breed>": [] } }
    $breeds = [];
    foreach ($rows as $row) {
        $name = trim((string) data_get($row, 'attributes.name', ''));
        if ($name === '') {
            continue;
        }

        $key = preg_replace('/\s+/', ' ', strtolower($name));
        if ($key === null || $key === '') {
            continue;
        }

        if (!array_key_exists($key, $breeds)) {
            $breeds[$key] = [];
        }
    }

    ksort($breeds);

    return response()->json([
        'status' => 'success',
        'breeds' => $breeds
    ]);
}

}
