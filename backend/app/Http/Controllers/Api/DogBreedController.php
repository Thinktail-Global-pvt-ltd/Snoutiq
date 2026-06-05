<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DogBreedController extends Controller
{
    private const ALL_BREEDS_URL = 'https://dog.ceo/api/breeds/list/all';

    private const MANUAL_DOG_BREEDS = [
        'American Bully',
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
        $breeds = [];

        try {
            $resp = Http::acceptJson()
                ->timeout(10)
                ->get(self::ALL_BREEDS_URL);

            if ($resp->successful()) {
                $apiBreeds = $resp->json('message');
                if (is_array($apiBreeds)) {
                    foreach ($apiBreeds as $breed => $subBreeds) {
                        $this->appendBreed($breeds, (string) $breed, is_array($subBreeds) ? $subBreeds : []);
                    }
                }
            }
        } catch (ConnectionException $e) {
            report($e);
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

    private function appendBreed(array &$breeds, string $name, array $subBreeds = []): void
    {
        if ($name === '') {
            return;
        }

        $key = preg_replace('/\s+/', ' ', strtolower(trim($name)));
        if ($key === null || $key === '') {
            return;
        }

        if (!array_key_exists($key, $breeds)) {
            $breeds[$key] = array_values(array_filter(array_map(
                fn ($subBreed) => strtolower(trim((string) $subBreed)),
                $subBreeds
            )));
        }
    }

}
