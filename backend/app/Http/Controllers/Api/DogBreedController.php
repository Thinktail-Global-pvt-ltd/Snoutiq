<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class DogBreedController extends Controller
{
    private const MANUAL_DOG_BREEDS = [
        'Rajapalayam',
        'Chippiparai',
        'Mudhol Hound',
        'Changkhi',
        'Gaddi Kutta',
        'Indian Pariah Dog',
        'Kombai',
        'Kanni',
        'Rampur Hound',
        'Caravan Hound',
        'Bakharwal Dog',
        'Himalayan Sheepdog',
        'Bhutia Dog',
        'Bully Kutta',
        'Jonangi',
        'Kaikadi',
        'Pandikona',
        'Haofa Tangkhul Hui',
        'Meitei Hui',
        'Indian Spitz',
        'Janwal Pashmi',
    ];

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
            $this->appendBreed($breeds, $name);
        }

        foreach (self::MANUAL_DOG_BREEDS as $breedName) {
            $this->appendBreed($breeds, $breedName);
        }

        ksort($breeds);

        return response()->json([
            'status' => 'success',
            'breeds' => $breeds,
        ]);
    }

    private function appendBreed(array &$breeds, string $name): void
    {
        if ($name === '') {
            return;
        }

        $key = preg_replace('/\s+/', ' ', strtolower(trim($name)));
        if ($key === null || $key === '') {
            return;
        }

        if (!array_key_exists($key, $breeds)) {
            $breeds[$key] = [];
        }
    }

}
