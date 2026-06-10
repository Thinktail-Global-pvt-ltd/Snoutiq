<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Prescription;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DewormingWalkinsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSchema();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->resetSchema();

        parent::tearDown();
    }

    private function resetSchema(): void
    {
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('pets');
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
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->string('pet_gender')->nullable();
            $table->string('pet_age')->nullable();
            $table->string('pet_age_months')->nullable();
            $table->date('pet_dob')->nullable();
            $table->date('dob')->nullable();
            $table->boolean('deworming_yes_no')->nullable();
            $table->date('last_deworming_date')->nullable();
            $table->string('deworming_status', 50)->nullable();
            $table->date('next_deworming_date')->nullable();
            $table->json('dog_disease_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('vet_registeration_id');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medical_record_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('visit_category', 255)->nullable();
            $table->string('case_severity', 255)->nullable();
            $table->text('visit_notes')->nullable();
            $table->text('doctor_treatment')->nullable();
            $table->text('content_html')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->json('medications_json')->nullable();
            $table->string('deworming', 50)->nullable();
            $table->date('last_deworming_date')->nullable();
            
            // Other fields to prevent QueryException during fill/create/update
            $table->float('temperature')->nullable();
            $table->float('weight')->nullable();
            $table->float('heart_rate')->nullable();
            $table->text('exam_notes')->nullable();
            $table->string('diagnosis')->nullable();
            $table->string('diagnosis_status')->nullable();
            $table->boolean('is_chronic')->default(false);
            $table->string('disease_name')->nullable();
            $table->text('prognosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('home_care')->nullable();
            $table->text('history_snapshot')->nullable();
            $table->string('video_inclinic')->nullable();
            $table->string('call_session')->nullable();
            $table->boolean('follow_up_required')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('follow_up_type')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->string('system_affected')->nullable();
            $table->unsignedBigInteger('system_affected_id')->nullable();
            $table->string('mucous_membrane')->nullable();
            $table->string('dehydration_level')->nullable();
            $table->string('abdominal_pain_reaction')->nullable();
            $table->string('auscultation')->nullable();
            $table->text('physical_exam_other')->nullable();
            $table->unsignedBigInteger('video_appointment_id')->nullable();
            $table->unsignedBigInteger('in_clinic_appointment_id')->nullable();
            $table->string('vaccination_name')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('vaccination_date')->nullable();
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
    }

    private function assertDateEquals(string $expected, $actual): void
    {
        $this->assertNotNull($actual);
        $this->assertEquals($expected, \Carbon\Carbon::parse($actual)->toDateString());
    }

    public function test_store_medical_record_with_deworming_no(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'deworming_yes_no' => true,
            'last_deworming_date' => '2025-06-01',
            'deworming_status' => 'every_3_months',
            'next_deworming_date' => '2025-09-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/medical-records', [
            'user_id' => 1479,
            'doctor_id' => 40,
            'clinic_id' => 22,
            'pet_id' => 1357,
            'visit_category' => 'deworming',
            'deworming' => 'no',
            'notes' => 'Some deworming visit notes',
        ]);

        $response->assertStatus(201);

        $prescription = Prescription::where('pet_id', 1357)->first();
        $this->assertNotNull($prescription);
        $this->assertEquals('no', $prescription->deworming);
        $this->assertNull($prescription->last_deworming_date);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $this->assertFalse((bool)$pet->deworming_yes_no);
        $this->assertNull($pet->last_deworming_date);
        $this->assertNull($pet->deworming_status);
        $this->assertNull($pet->next_deworming_date);
    }

    public function test_store_medical_record_with_deworming_yes(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'deworming_yes_no' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/medical-records', [
            'user_id' => 1479,
            'doctor_id' => 40,
            'clinic_id' => 22,
            'pet_id' => 1357,
            'visit_category' => 'deworming',
            'deworming' => 'yes',
            'last_deworming_date' => '2026-06-01',
            'notes' => 'Deworming completed today',
        ]);

        $response->assertStatus(201);

        $prescription = Prescription::where('pet_id', 1357)->first();
        $this->assertNotNull($prescription);
        $this->assertEquals('yes', $prescription->deworming);
        $this->assertDateEquals('2026-06-01', $prescription->last_deworming_date);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $this->assertTrue((bool)$pet->deworming_yes_no);
        $this->assertDateEquals('2026-06-01', $pet->last_deworming_date);
        $this->assertEquals('every_3_months', $pet->deworming_status);
        $this->assertDateEquals('2026-09-01', $pet->next_deworming_date);
    }

    public function test_update_medical_record_with_deworming(): void
    {
        DB::table('users')->insert([
            'id' => 1479,
            'name' => 'Pet Parent',
            'last_vet_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vet_registerations_temp')->insert([
            'id' => 22,
            'name' => 'Vet Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 40,
            'vet_registeration_id' => 22,
            'doctor_name' => 'Dr. Rao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 1357,
            'user_id' => 1479,
            'name' => 'Bruno',
            'pet_dob' => '2025-01-01',
            'deworming_yes_no' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('medical_records')->insert([
            'id' => 99,
            'user_id' => 1479,
            'doctor_id' => 40,
            'vet_registeration_id' => 22,
            'notes' => 'initial visit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prescriptions')->insert([
            'id' => 77,
            'medical_record_id' => 99,
            'user_id' => 1479,
            'doctor_id' => 40,
            'pet_id' => 1357,
            'visit_category' => 'consultation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson('/api/medical-records/99', [
            'clinic_id' => 22,
            'doctor_id' => 40,
            'pet_id' => 1357,
            'visit_category' => 'deworming',
            'deworming' => 'yes',
            'last_deworming_date' => '2026-06-02',
            'notes' => 'updated to deworming',
        ]);

        $response->assertOk();

        $prescription = Prescription::find(77);
        $this->assertNotNull($prescription);
        $this->assertEquals('yes', $prescription->deworming);
        $this->assertDateEquals('2026-06-02', $prescription->last_deworming_date);

        $pet = Pet::find(1357);
        $this->assertNotNull($pet);
        $this->assertTrue((bool)$pet->deworming_yes_no);
        $this->assertDateEquals('2026-06-02', $pet->last_deworming_date);
        $this->assertEquals('every_3_months', $pet->deworming_status);
        $this->assertDateEquals('2026-09-02', $pet->next_deworming_date);
    }
}
