<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClinicFullOnboardingApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('groomer_services');
        Schema::dropIfExists('groomer_service_categories');
        Schema::dropIfExists('clinic_specialized_packages');
        Schema::dropIfExists('doctor_availability');
        Schema::dropIfExists('doctor_video_availability');
        Schema::dropIfExists('vet_at_home_services');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('users');

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->text('address')->nullable();
            $table->text('bio')->nullable();
            $table->string('password')->nullable();
            $table->string('hospital_profile')->nullable();
            $table->string('clinic_profile')->nullable();
            $table->string('slug')->nullable();
            $table->string('status')->nullable();
            $table->string('public_id')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('claim_token')->nullable();
            $table->timestamp('draft_expires_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->decimal('clinic_day_fee', 10, 2)->nullable();
            $table->decimal('clinic_night_fee', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_email')->nullable();
            $table->string('doctor_mobile')->nullable();
            $table->string('doctor_license')->nullable();
            $table->integer('exported_from_excell')->default(0);
            $table->timestamps();
        });

        Schema::create('groomer_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('pet_type')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('duration')->nullable();
            $table->string('main_service')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('groomer_service_category_id')->nullable();
            $table->timestamps();
        });

        Schema::create('groomer_service_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_specialized_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->decimal('dog_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('cat_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('dog_neutering_price', 10, 2)->nullable();
            $table->decimal('cat_neutering_price', 10, 2)->nullable();
            $table->decimal('puppy_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('adult_dog_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('kitten_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('adult_cat_vaccination_package_price', 10, 2)->nullable();
            $table->decimal('dog_vaccination_male_package_price', 10, 2)->nullable();
            $table->decimal('dog_vaccination_female_package_price', 10, 2)->nullable();
            $table->decimal('cat_vaccination_male_package_price', 10, 2)->nullable();
            $table->decimal('cat_vaccination_female_package_price', 10, 2)->nullable();
            $table->decimal('dog_neutering_male_price', 10, 2)->nullable();
            $table->decimal('dog_neutering_female_price', 10, 2)->nullable();
            $table->decimal('cat_neutering_male_price', 10, 2)->nullable();
            $table->decimal('cat_neutering_female_price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('service_type')->nullable();
            $table->integer('day_of_week')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('break_start')->nullable();
            $table->string('break_end')->nullable();
            $table->integer('avg_consultation_mins')->nullable();
            $table->integer('max_bookings_per_hour')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('doctor_video_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->integer('day_of_week')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('break_start')->nullable();
            $table->string('break_end')->nullable();
            $table->integer('avg_consultation_mins')->nullable();
            $table->integer('max_bookings_per_hour')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('vet_at_home_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('service_hours')->nullable();
            $table->string('response_time')->nullable();
            $table->decimal('base_payout', 10, 2)->nullable();
            $table->string('protocol_label')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('groomer_services');
        Schema::dropIfExists('groomer_service_categories');
        Schema::dropIfExists('clinic_specialized_packages');
        Schema::dropIfExists('doctor_availability');
        Schema::dropIfExists('doctor_video_availability');
        Schema::dropIfExists('vet_at_home_services');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_stores_onboarding_data_with_clinic_fees_correctly(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Default Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/vet-registerations/store-full', [
            'name' => 'Pet Vet Center',
            'email' => 'anil.kumarr1224@gmail.com',
            'mobile' => '9871198033',
            'city' => 'Delhi Division',
            'pincode' => '110075',
            'clinic_day_fee' => 450.00,
            'clinic_night_fee' => 650.00,
            'doctors' => [
                [
                    'doctor_name' => 'Dr. Clinic Vet',
                    'doctor_email' => 'clinic.vet@example.com',
                    'doctor_mobile' => '9999988888',
                ]
            ],
            'services' => [
                [
                    'serviceName' => 'General Consultation',
                    'price' => 499,
                ]
            ]
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Assert clinic_day_fee and clinic_night_fee are saved in the DB
        $this->assertDatabaseHas('vet_registerations_temp', [
            'name' => 'Pet Vet Center',
            'email' => 'anil.kumarr1224@gmail.com',
            'clinic_day_fee' => 450.00,
            'clinic_night_fee' => 650.00,
        ]);
    }

    public function test_inclinic_lists_returns_clinic_fees(): void
    {
        // 1. Seed a clinic with day and night fees
        DB::table('vet_registerations_temp')->insert([
            'id' => 100,
            'name' => 'Excellent Vet Care',
            'city' => 'New Delhi',
            'pincode' => '110075',
            'clinic_day_fee' => 400.00,
            'clinic_night_fee' => 600.00,
            'created_at' => '2026-06-01 12:00:00',
            'updated_at' => '2026-06-01 12:00:00',
        ]);

        // 2. Seed an associated doctor with exported_from_excell = 1
        DB::table('doctors')->insert([
            'id' => 200,
            'vet_registeration_id' => 100,
            'doctor_name' => 'Dr. Excellent',
            'exported_from_excell' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Call the inclinic lists API
        $response = $this->getJson('/api/inclinic-lists-new-after-10th-may-registerations?from_date=2026-05-10');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Assert response contains custom fees
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $clinicData = collect($data)->firstWhere('id', 100);
        $this->assertNotNull($clinicData);
        $this->assertEquals(400.0, $clinicData['clinic_day_fee']);
        $this->assertEquals(600.0, $clinicData['clinic_night_fee']);
    }

    public function test_doctor_availability_update_can_update_clinic_fees(): void
    {
        // 1. Seed a clinic
        DB::table('vet_registerations_temp')->insert([
            'id' => 100,
            'name' => 'Excellent Vet Care',
            'city' => 'New Delhi',
            'pincode' => '110075',
            'clinic_day_fee' => 400.00,
            'clinic_night_fee' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed an associated doctor
        DB::table('doctors')->insert([
            'id' => 200,
            'vet_registeration_id' => 100,
            'doctor_name' => 'Dr. Excellent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Put to updateAvailability API
        $response = $this->putJson('/api/doctors/200/availability', [
            'clinic_day_fee' => 450.00,
            'clinic_night_fee' => 750.00,
            'availability' => [
                [
                    'service_type' => 'in_clinic',
                    'day_of_week' => 1,
                    'start_time' => '10:00',
                    'end_time' => '18:00',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // 4. Assert updated fees in DB
        $this->assertDatabaseHas('vet_registerations_temp', [
            'id' => 100,
            'clinic_day_fee' => 450.00,
            'clinic_night_fee' => 750.00,
        ]);

        // 5. Get availability via API and assert clinic fees
        $getResp = $this->getJson('/api/doctors/200/availability');
        $getResp->assertStatus(200);
        $this->assertEquals(450.0, $getResp->json('clinic_day_fee'));
        $this->assertEquals(750.0, $getResp->json('clinic_night_fee'));
    }

    public function test_clinic_availability_update_can_update_clinic_fees(): void
    {
        // 1. Seed a clinic
        DB::table('vet_registerations_temp')->insert([
            'id' => 100,
            'name' => 'Excellent Vet Care',
            'city' => 'New Delhi',
            'pincode' => '110075',
            'clinic_day_fee' => 400.00,
            'clinic_night_fee' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed an associated doctor
        DB::table('doctors')->insert([
            'id' => 200,
            'vet_registeration_id' => 100,
            'doctor_name' => 'Dr. Excellent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Put to updateClinicAvailability API
        $response = $this->putJson('/api/clinics/100/doctor-availability', [
            'clinic_day_fee' => 480.00,
            'clinic_night_fee' => 780.00,
            'availability' => [
                [
                    'service_type' => 'in_clinic',
                    'day_of_week' => 2,
                    'start_time' => '09:00',
                    'end_time' => '19:00',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // 4. Assert updated fees in DB
        $this->assertDatabaseHas('vet_registerations_temp', [
            'id' => 100,
            'clinic_day_fee' => 480.00,
            'clinic_night_fee' => 780.00,
        ]);

        // 5. Get clinic availability via API and assert clinic fees
        $getResp = $this->getJson('/api/clinics/100/doctor-availability');
        $getResp->assertStatus(200);
        $this->assertEquals(480.0, $getResp->json('clinic_day_fee'));
        $this->assertEquals(780.0, $getResp->json('clinic_night_fee'));
    }

    public function test_full_onboarding_payload_returns_profile_completion_with_clinic_fees(): void
    {
        // 1. Seed a clinic
        DB::table('vet_registerations_temp')->insert([
            'id' => 313,
            'name' => 'Excellent Vet Care',
            'city' => 'New Delhi',
            'pincode' => '110075',
            'clinic_day_fee' => 450.00,
            'clinic_night_fee' => null, // clinic_night_fee is missing
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Call the GET /api/vet-registerations/313/full API
        $response = $this->getJson('/api/vet-registerations/313/full');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // 3. Assert profile completion structure contains our new checks
        $checks = collect($response->json('data.profile_completion.checks'));
        $dayFeeCheck = $checks->firstWhere('key', 'clinic_day_fee');
        $nightFeeCheck = $checks->firstWhere('key', 'clinic_night_fee');

        $this->assertNotNull($dayFeeCheck);
        $this->assertTrue($dayFeeCheck['complete']);
        $this->assertEquals('Clinic day fee', $dayFeeCheck['label']);

        $this->assertNotNull($nightFeeCheck);
        $this->assertFalse($nightFeeCheck['complete']);
        $this->assertEquals('Clinic night fee', $nightFeeCheck['label']);

        // Assert missing_fields contains clinic_night_fee
        $missing = collect($response->json('data.profile_completion.missing_fields'));
        $this->assertTrue($missing->contains('key', 'clinic_night_fee'));
        $this->assertFalse($missing->contains('key', 'clinic_day_fee'));
    }
}
