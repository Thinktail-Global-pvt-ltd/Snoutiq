<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LastVetDetailsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        $this->resetSchema();

        parent::tearDown();
    }

    public function test_last_vet_details_returns_all_doctors_with_availability_status(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 23, 11, 15, 0, 'Asia/Kolkata'));

        DB::table('vet_registerations_temp')->insert([
            'id' => 10,
            'name' => 'Care Clinic',
            'clinic_image' => 'dummy_image_data',
            'clinic_video' => 'dummy_video_data',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 1404,
            'name' => 'Pet Parent',
            'last_vet_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            [
                'id' => 101,
                'vet_registeration_id' => 10,
                'doctor_name' => 'Dr Available',
                'doctor_image_blob' => 'dummy_doctor_blob',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 102,
                'vet_registeration_id' => 10,
                'doctor_name' => 'Dr Later',
                'doctor_image_blob' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 103,
                'vet_registeration_id' => 10,
                'doctor_name' => 'Dr Break',
                'doctor_image_blob' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 104,
                'vet_registeration_id' => 11,
                'doctor_name' => 'Other Clinic Doctor',
                'doctor_image_blob' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('doctor_video_availability')->insert([
            [
                'doctor_id' => 101,
                'day_of_week' => 4,
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'break_start' => null,
                'break_end' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => 102,
                'day_of_week' => 4,
                'start_time' => '12:30:00',
                'end_time' => '14:00:00',
                'break_start' => null,
                'break_end' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => 103,
                'day_of_week' => 4,
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'break_start' => '11:00:00',
                'break_end' => '11:30:00',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => 104,
                'day_of_week' => 4,
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'break_start' => null,
                'break_end' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/users/last-vet-details?user_id=1404');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', 1404)
            ->assertJsonPath('data.last_vet_id', 10)
            ->assertJsonCount(3, 'data.doctors')
            ->assertJsonPath('data.doctors.0.id', 101)
            ->assertJsonPath('data.doctors.0.doctor_name', 'Dr Available')
            ->assertJsonPath('data.doctors.0.is_available', true)
            ->assertJsonPath('data.doctors.1.id', 102)
            ->assertJsonPath('data.doctors.1.doctor_name', 'Dr Later')
            ->assertJsonPath('data.doctors.1.is_available', false)
            ->assertJsonPath('data.doctors.2.id', 103)
            ->assertJsonPath('data.doctors.2.doctor_name', 'Dr Break')
            ->assertJsonPath('data.doctors.2.is_available', false)
            ->assertJsonPath('data.availability.source', 'doctor_video_availability')
            ->assertJsonPath('data.availability.day_of_week', 4)
            ->assertJsonPath('data.availability.time', '11:15:00')
            ->assertJsonPath('data.clinic.clinic_image', route('clinics.media.image', ['clinic' => 10]))
            ->assertJsonPath('data.clinic.clinic_image_url', route('clinics.media.image', ['clinic' => 10]))
            ->assertJsonPath('data.clinic.clinic_video', route('clinics.media.video', ['clinic' => 10]))
            ->assertJsonPath('data.clinic.clinic_video_url', route('clinics.media.video', ['clinic' => 10]))
            ->assertJsonPath('data.doctors.0.doctor_image_blob', route('api.doctors.blob-image', ['doctor' => 101]))
            ->assertJsonPath('data.doctors.0.doctor_image_blob_url', route('api.doctors.blob-image', ['doctor' => 101]));
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('doctor_video_availability');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('users');
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('last_vet_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->binary('clinic_image')->nullable();
            $table->binary('clinic_video')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->binary('doctor_image_blob')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_video_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}
