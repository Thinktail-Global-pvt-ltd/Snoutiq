<?php

namespace Tests\Feature;

use App\Services\GooglePlacesLookupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class UserNearbyClinicsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function test_user_nearby_clinics_returns_google_clinics_only(): void
    {
        DB::table('users')->insert([
            'id' => 1388,
            'name' => 'Pet Parent',
            'city' => 'Gurgaon',
            'latitude' => 28.4595,
            'longitude' => 77.0266,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $places = Mockery::mock(GooglePlacesLookupService::class);
        $places->shouldReceive('search')
            ->once()
            ->with('clinic', 'Gurgaon', 28.4595, 77.0266, 5)
            ->andReturn([
                'success' => true,
                'location' => 'Gurgaon',
                'count' => 1,
                'source' => 'google_places',
                'places' => [
                    [
                        'name' => 'Google Vet Clinic',
                        'address' => 'Sector 45, Gurgaon',
                        'rating' => 4.8,
                        'place_id' => 'google_place_123',
                    ],
                ],
            ]);
        $this->app->instance(GooglePlacesLookupService::class, $places);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1388');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_id', 1388)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('source', 'google_places')
            ->assertJsonPath('range_km', 5)
            ->assertJsonPath('clinics.0.name', 'Google Vet Clinic')
            ->assertJsonMissingPath('nearby_clinics')
            ->assertJsonMissingPath('vet_clinics')
            ->assertJsonMissingPath('structured_data');
    }

    public function test_user_nearby_clinics_uses_google_text_search_when_only_city_is_saved(): void
    {
        DB::table('users')->insert([
            'id' => 1389,
            'name' => 'Pet Parent',
            'city' => 'Gurgaon',
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $places = Mockery::mock(GooglePlacesLookupService::class);
        $places->shouldReceive('search')
            ->once()
            ->with('clinic', 'Gurgaon', null, null, 5)
            ->andReturn([
                'success' => true,
                'location' => 'Gurgaon',
                'count' => 1,
                'source' => 'google_places',
                'places' => [
                    ['name' => 'Google City Clinic'],
                ],
            ]);
        $this->app->instance(GooglePlacesLookupService::class, $places);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1389');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('range_km', null)
            ->assertJsonPath('source', 'google_places')
            ->assertJsonPath('clinics.0.name', 'Google City Clinic');
    }

    public function test_user_nearby_clinics_includes_google_photo_proxy_url(): void
    {
        config(['services.google_maps.api_key' => 'test-google-key']);

        DB::table('users')->insert([
            'id' => 1394,
            'name' => 'Pet Parent',
            'city' => 'Gurgaon',
            'latitude' => 28.4595,
            'longitude' => 77.0266,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://maps.googleapis.com/maps/api/place/nearbysearch/json*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'name' => 'Google Vet Clinic',
                        'vicinity' => 'Sector 45, Gurgaon',
                        'place_id' => 'google_place_123',
                        'rating' => 4.8,
                        'geometry' => [
                            'location' => [
                                'lat' => 28.4601,
                                'lng' => 77.0271,
                            ],
                        ],
                        'opening_hours' => [
                            'open_now' => true,
                            'weekday_text' => ['Monday: 10:00 AM - 5:00 PM'],
                        ],
                        'photos' => [
                            ['photo_reference' => 'google_photo_ref_123'],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1394');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('clinics.0.google_photo_reference', 'google_photo_ref_123')
            ->assertJsonPath('clinics.0.google_photo_url', url('/api/places/photo?photo_reference=google_photo_ref_123'));

        $this->assertStringNotContainsString('test-google-key', $response->json('clinics.0.google_photo_url'));
    }

    public function test_google_place_photo_endpoint_proxies_google_image(): void
    {
        config(['services.google_maps.api_key' => 'test-google-key']);

        Http::fake([
            'https://maps.googleapis.com/maps/api/place/photo*' => Http::response('fake-image-bytes', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $response = $this->get('/api/places/photo?photo_reference=google_photo_ref_123');

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertSee('fake-image-bytes', false);
    }

    public function test_user_nearby_clinics_requires_saved_or_request_location(): void
    {
        DB::table('users')->insert([
            'id' => 1392,
            'name' => 'Pet Parent',
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1392');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_nearby_clinics_reports_missing_google_key_as_configuration_error(): void
    {
        DB::table('users')->insert([
            'id' => 1393,
            'name' => 'Pet Parent',
            'city' => 'Gurgaon',
            'latitude' => 28.4595,
            'longitude' => 77.0266,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $places = Mockery::mock(GooglePlacesLookupService::class);
        $places->shouldReceive('search')
            ->once()
            ->with('clinic', 'Gurgaon', 28.4595, 77.0266, 5)
            ->andReturn([
                'success' => false,
                'error' => 'Google Places API key is missing. Set GOOGLE_API_KEY or GOOGLE_MAPS_API_KEY.',
            ]);
        $this->app->instance(GooglePlacesLookupService::class, $places);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1393');

        $response->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('range_km', null)
            ->assertJsonPath('configuration_required.accepted_env_keys.0', 'GOOGLE_MAPS_API_KEY');
    }

    public function test_user_location_can_be_saved_for_later_nearby_clinic_lookup(): void
    {
        DB::table('users')->insert([
            'id' => 1390,
            'name' => 'Pet Parent',
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/users/location', [
            'user_id' => 1390,
            'location' => 'Gurgaon',
            'lat' => 28.4595,
            'lng' => 77.0266,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', 1390)
            ->assertJsonPath('data.city', 'Gurgaon')
            ->assertJsonPath('data.latitude', 28.4595)
            ->assertJsonPath('data.longitude', 77.0266);

        $this->assertDatabaseHas('users', [
            'id' => 1390,
            'city' => 'Gurgaon',
            'latitude' => 28.4595,
            'longitude' => 77.0266,
        ]);
    }

    public function test_user_location_requires_a_complete_location_payload(): void
    {
        DB::table('users')->insert([
            'id' => 1391,
            'name' => 'Pet Parent',
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/users/location', [
            'user_id' => 1391,
            'lat' => 28.4595,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
