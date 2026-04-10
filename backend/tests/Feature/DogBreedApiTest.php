<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DogBreedApiTest extends TestCase
{
    public function test_dog_breeds_endpoint_includes_manual_breeds_in_existing_map_shape(): void
    {
        Http::fake([
            'https://dogapi.dog/api/v2/breeds' => Http::response([
                'data' => [
                    [
                        'attributes' => [
                            'name' => 'Labrador Retriever',
                        ],
                    ],
                    [
                        'attributes' => [
                            'name' => 'Beagle',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/dog-breeds/all');

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $breeds = $response->json('breeds');

        $this->assertIsArray($breeds);
        $this->assertSame([], $breeds['labrador retriever'] ?? null);
        $this->assertSame([], $breeds['beagle'] ?? null);

        foreach ([
            'rajapalayam',
            'chippiparai',
            'mudhol hound',
            'changkhi',
            'gaddi kutta',
            'indian pariah dog',
            'kombai',
            'kanni',
            'rampur hound',
            'caravan hound',
            'bakharwal dog',
            'himalayan sheepdog',
            'bhutia dog',
            'bully kutta',
            'jonangi',
            'kaikadi',
            'pandikona',
            'haofa tangkhul hui',
            'meitei hui',
            'indian spitz',
            'janwal pashmi',
        ] as $breedName) {
            $this->assertArrayHasKey($breedName, $breeds);
            $this->assertSame([], $breeds[$breedName]);
        }
    }
}
