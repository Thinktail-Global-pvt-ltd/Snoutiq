<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $this->createClinicSchema();
    }

    public function test_user_nearby_clinics_returns_only_database_clinics(): void
    {
        DB::table('users')->insert([
            'id' => 1388,
            'name' => 'Pet Parent',
            'city' => 'Gurgaon',
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vet_registerations_temp')->insert([
            [
                'id' => 25,
                'name' => 'Snoutiq Partner Clinic',
                'mobile' => '9999999999',
                'city' => 'Gurgaon',
                'address' => 'Sector 45',
                'formatted_address' => 'Sector 45, Gurgaon',
                'place_id' => 'db_place_25',
                'business_status' => 'OPERATIONAL',
                'lat' => 28.4595,
                'lng' => 77.0266,
                'open_now' => true,
                'rating' => 4.7,
                'user_ratings_total' => 44,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 26,
                'name' => 'Other City Clinic',
                'mobile' => null,
                'city' => 'Delhi',
                'address' => null,
                'formatted_address' => null,
                'place_id' => null,
                'business_status' => null,
                'lat' => null,
                'lng' => null,
                'open_now' => null,
                'rating' => null,
                'user_ratings_total' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1388');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_id', 1388)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('source', 'snoutiq_database')
            ->assertJsonPath('clinics.0.name', 'Snoutiq Partner Clinic')
            ->assertJsonPath('clinics.0.phone', '9999999999')
            ->assertJsonMissingPath('nearby_clinics')
            ->assertJsonMissingPath('vet_clinics')
            ->assertJsonMissingPath('structured_data');
    }

    public function test_user_nearby_clinics_returns_clinics_even_without_saved_location(): void
    {
        DB::table('users')->insert([
            'id' => 1389,
            'name' => 'Pet Parent',
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vet_registerations_temp')->insert([
            'id' => 27,
            'name' => 'Available Clinic',
            'city' => 'Mumbai',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1389');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('clinics.0.name', 'Available Clinic');
    }

    public function test_user_nearby_clinics_sorts_by_distance_when_coordinates_are_available(): void
    {
        DB::table('users')->insert([
            'id' => 1392,
            'name' => 'Pet Parent',
            'city' => null,
            'latitude' => 28.4595,
            'longitude' => 77.0266,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vet_registerations_temp')->insert([
            [
                'id' => 28,
                'name' => 'Far Clinic',
                'lat' => 28.7041,
                'lng' => 77.1025,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 29,
                'name' => 'Near Clinic',
                'lat' => 28.4596,
                'lng' => 77.0267,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/users/nearby-clinics?user_id=1392');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('clinics.0.name', 'Near Clinic');
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

    private function createClinicSchema(): void
    {
        Schema::dropIfExists('vet_registerations_temp');
        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('clinic_profile')->nullable();
            $table->string('hospital_profile')->nullable();
            $table->string('mobile')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('formatted_address')->nullable();
            $table->string('place_id')->nullable();
            $table->string('business_status')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('open_now')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('user_ratings_total')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
}
