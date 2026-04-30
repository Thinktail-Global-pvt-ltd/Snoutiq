<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserProfileCompletionVaccinationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('pet_type')->nullable();
            $table->string('type')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_gender')->nullable();
            $table->string('gender')->nullable();
            $table->unsignedInteger('pet_age')->nullable();
            $table->unsignedInteger('pet_age_months')->nullable();
            $table->date('pet_dob')->nullable();
            $table->date('dob')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->boolean('deworming_yes_no')->nullable();
            $table->string('is_neutered')->nullable();
            $table->string('is_nuetered')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pic_link')->nullable();
            $table->json('dog_disease_payload')->nullable();
            $table->timestamps();
        });
    }

    public function test_profile_completion_marks_vaccination_pending_when_payload_has_no_vaccination_key(): void
    {
        DB::table('users')->insert([
            'id' => 1246,
            'name' => 'Pet Parent',
            'email' => 'parent@example.com',
            'phone' => '9876543210',
            'latitude' => 28.6139000,
            'longitude' => 77.2090000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1285,
            'user_id' => 1246,
            'name' => 'Bruno',
            'pet_type' => 'dog',
            'breed' => 'indian pariah dog',
            'pet_gender' => 'male',
            'pet_age' => 2,
            'pet_dob' => '2024-05-01',
            'weight' => 12.5,
            'deworming_yes_no' => true,
            'is_neutered' => 'no',
            'dog_disease_payload' => json_encode(['question' => 'Fhh']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/user/profile/completion?pet_id=1285');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'label' => 'Vaccination payload',
                'column' => 'dog_disease_payload.vaccination',
                'filled' => false,
                'value' => null,
            ]);
    }
}
