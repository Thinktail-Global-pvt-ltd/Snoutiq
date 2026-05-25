<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrescriptionsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('dog_disease_payload')->nullable();
            $table->string('is_neutered')->nullable();
            $table->boolean('vaccenated_yes_no')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_image')->nullable();
            $table->binary('doctor_image_blob')->nullable();
            $table->string('doctor_image_mime', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->text('image_path')->nullable();
            $table->json('medications_json')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
    }

    public function test_prescriptions_index_uses_doctor_blob_get_url_as_doctor_image(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'dog_disease_payload' => json_encode(['dog_disease' => null]),
            'is_neutered' => 'N',
            'vaccenated_yes_no' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'doctor_name' => 'Dr. Rao',
            'doctor_image' => null,
            'doctor_image_blob' => 'binary-image',
            'doctor_image_mime' => 'image/png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prescriptions')->insert([
            'id' => 70,
            'doctor_id' => 40,
            'user_id' => 1479,
            'pet_id' => 1357,
            'image_path' => null,
            'medications_json' => json_encode([]),
            'treatment_plan' => null,
            'seen' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imageUrl = route('api.doctors.blob-image', ['doctor' => 40]);

        $response = $this->getJson('/api/prescriptions?pet_id=1357');

        $response->assertOk()
            ->assertJsonPath('data.0.doctor.id', 40)
            ->assertJsonPath('data.0.doctor.doctor_image', $imageUrl)
            ->assertJsonPath('data.0.doctor.doctor_image_blob_url', $imageUrl)
            ->assertJsonMissingPath('data.0.doctor.doctor_image_blob');
    }
}
