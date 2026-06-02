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
}
