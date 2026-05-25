<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentsByUserApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('appointments');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_image')->nullable();
            $table->binary('doctor_image_blob')->nullable();
            $table->string('doctor_image_mime', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('pet_name')->nullable();
            $table->string('appointment_type')->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('appointment_time')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_appointments_by_user_uses_doctor_blob_get_url_as_doctor_image(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'phone' => '9999999999',
            'email' => 'parent@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 20,
            'user_id' => 1479,
            'name' => 'Milo',
            'breed' => 'Indie',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 313,
            'name' => 'Asarva Railway station Ahmedabad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 313,
            'doctor_name' => 'Dr. Rao',
            'doctor_image' => null,
            'doctor_image_blob' => 'binary-image',
            'doctor_image_mime' => 'image/png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('appointments')->insert([
            'id' => 70,
            'vet_registeration_id' => 313,
            'doctor_id' => 40,
            'pet_id' => 20,
            'name' => 'Pet Parent',
            'mobile' => '9999999999',
            'pet_name' => 'Milo',
            'appointment_type' => null,
            'appointment_date' => '2026-05-25',
            'appointment_time' => '10:00 AM',
            'status' => 'confirmed',
            'notes' => json_encode(['patient_user_id' => 1479]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imageUrl = route('api.doctors.blob-image', ['doctor' => 40]);

        $response = $this->getJson('/api/appointments/by-user/1479');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointments.0.doctor.doctor_image', $imageUrl)
            ->assertJsonPath('data.appointments_full.0.doctor.doctor_image', $imageUrl)
            ->assertJsonPath('data.appointments_full.0.doctor.doctor_image_blob_url', $imageUrl)
            ->assertJsonMissingPath('data.appointments_full.0.doctor.doctor_image_blob');
    }
}
