<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PetOverviewHomeVisitApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    public function test_overview_includes_home_visit_transaction_and_assigned_doctor(): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'name' => 'Ananya',
            'phone' => '9876543210',
            'email' => 'ananya@example.com',
            'city' => 'Gurgaon',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 20,
            'user_id' => 10,
            'name' => 'Milo',
            'breed' => 'Indie',
            'pet_age' => 3,
            'pet_age_months' => 4,
            'pet_dob' => '2022-12-01',
            'dob' => null,
            'pet_gender' => 'Male',
            'health_state' => 'stable',
            'ai_summary' => null,
            'reported_symptom' => 'Vomiting',
            'suggested_disease' => null,
            'pet_doc1' => null,
            'pet_doc2' => null,
            'dog_disease_payload' => null,
            'pet_card_for_ai' => null,
            'is_nuetered' => 'Y',
            'is_neutered' => null,
            'deworming_yes_no' => 1,
            'last_deworming_date' => '2026-03-01',
            'deworming_status' => 'every_3_months',
            'next_deworming_date' => '2026-06-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('home_service_required_by_pet')->insert([
            'id' => 30,
            'user_id' => 10,
            'pet_id' => 20,
            'latest_completed_step' => 3,
            'owner_name' => 'Ananya',
            'owner_phone' => '9876543210',
            'pet_type' => 'Dog',
            'area' => 'Gurgaon',
            'reason_for_visit' => 'Vomiting',
            'date_of_visit' => '2026-04-20',
            'time_of_visit' => '18:30:00',
            'concern_description' => 'Vomiting since morning',
            'symptoms' => json_encode(['Vomiting', 'Low energy']),
            'vaccination_status' => 'Up to date',
            'last_deworming' => '2026-03-01',
            'past_illnesses_or_surgeries' => 'None',
            'current_medications' => null,
            'known_allergies' => null,
            'vet_notes' => 'Evening visit',
            'payment_status' => 'paid',
            'amount_payable' => 999,
            'amount_paid' => 999,
            'payment_provider' => 'razorpay',
            'payment_reference' => 'pay_home_123',
            'booking_reference' => 'HV-00030',
            'step1_completed_at' => now(),
            'step2_completed_at' => now(),
            'step3_completed_at' => now(),
            'confirmed_at' => now(),
            'created_at' => now()->subMinute(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 7,
            'doctor_name' => 'Dr. Rao',
            'doctor_email' => 'rao@example.com',
            'doctor_mobile' => '9999999999',
            'doctor_license' => 'VET-123',
            'doctor_image' => null,
            'doctor_image_blob' => 'binary-image',
            'doctor_image_mime' => 'image/png',
            'degree' => 'BVSc',
            'years_of_experience' => '8',
            'specialization_select_all_that_apply' => 'Home visits',
            'languages_spoken' => 'English,Hindi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            [
                'id' => 50,
                'clinic_id' => 7,
                'doctor_id' => 40,
                'user_id' => 10,
                'pet_id' => 20,
                'amount_paise' => 99900,
                'status' => 'captured',
                'type' => 'home_service',
                'payment_method' => 'upi',
                'reference' => 'pay_home_123',
                'channel_name' => null,
                'metadata' => json_encode([
                    'order_type' => 'home_service',
                    'home_service_booking_id' => 30,
                    'notes' => [
                        'home_service_booking_id' => '30',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 51,
                'clinic_id' => 7,
                'doctor_id' => 41,
                'user_id' => 10,
                'pet_id' => 20,
                'amount_paise' => 49900,
                'status' => 'captured',
                'type' => 'video_consult',
                'payment_method' => 'upi',
                'reference' => 'pay_video_123',
                'channel_name' => null,
                'metadata' => json_encode(['order_type' => 'video_consult']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/pets/20/overview');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.home_visit_appointment.id', 30)
            ->assertJsonPath('data.home_visit_appointment.symptoms.0', 'Vomiting')
            ->assertJsonPath('data.home_visit_appointment.transactions.0.id', 50)
            ->assertJsonPath('data.home_visit_appointment.transactions.0.metadata.home_service_booking_id', 30)
            ->assertJsonPath('data.home_visit_appointment.transactions.0.doctor.id', 40)
            ->assertJsonPath('data.home_visit_appointment.transactions.0.doctor.doctor_name', 'Dr. Rao')
            ->assertJsonPath('data.home_visit_appointment.assigned_doctors.0.id', 40)
            ->assertJsonPath('data.latest_appointments.home_visit.id', 30)
            ->assertJsonMissingPath('data.home_visit_appointment.transactions.0.doctor.doctor_image_blob')
            ->assertJsonMissingPath('data.home_visit_appointment.transactions.1');
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('home_service_required_by_pet');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->unsignedInteger('pet_age')->nullable();
            $table->unsignedInteger('pet_age_months')->nullable();
            $table->date('pet_dob')->nullable();
            $table->date('dob')->nullable();
            $table->string('pet_gender')->nullable();
            $table->string('health_state')->nullable();
            $table->text('ai_summary')->nullable();
            $table->text('reported_symptom')->nullable();
            $table->string('suggested_disease')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pet_doc2')->nullable();
            $table->json('dog_disease_payload')->nullable();
            $table->string('pet_card_for_ai')->nullable();
            $table->string('is_nuetered')->nullable();
            $table->string('is_neutered')->nullable();
            $table->unsignedTinyInteger('deworming_yes_no')->nullable();
            $table->date('last_deworming_date')->nullable();
            $table->string('deworming_status')->nullable();
            $table->date('next_deworming_date')->nullable();
            $table->timestamps();
        });

        Schema::create('home_service_required_by_pet', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedTinyInteger('latest_completed_step')->default(1);
            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 30)->nullable();
            $table->string('pet_type', 50)->nullable();
            $table->string('area')->nullable();
            $table->string('reason_for_visit')->nullable();
            $table->date('date_of_visit')->nullable();
            $table->time('time_of_visit')->nullable();
            $table->text('concern_description')->nullable();
            $table->json('symptoms')->nullable();
            $table->string('vaccination_status')->nullable();
            $table->string('last_deworming')->nullable();
            $table->text('past_illnesses_or_surgeries')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('known_allergies')->nullable();
            $table->text('vet_notes')->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->decimal('amount_payable', 10, 2)->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('booking_reference')->nullable();
            $table->timestamp('step1_completed_at')->nullable();
            $table->timestamp('step2_completed_at')->nullable();
            $table->timestamp('step3_completed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_email')->nullable();
            $table->string('doctor_mobile')->nullable();
            $table->string('doctor_license')->nullable();
            $table->string('doctor_image')->nullable();
            $table->binary('doctor_image_blob')->nullable();
            $table->string('doctor_image_mime', 100)->nullable();
            $table->string('degree')->nullable();
            $table->string('years_of_experience')->nullable();
            $table->text('specialization_select_all_that_apply')->nullable();
            $table->text('languages_spoken')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->string('status')->default('pending');
            $table->string('type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->string('channel_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
