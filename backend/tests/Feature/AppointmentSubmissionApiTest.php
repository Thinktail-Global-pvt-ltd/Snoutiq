<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentSubmissionApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_email')->nullable();
            $table->string('doctor_mobile')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('last_vet_id')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('pet_name')->nullable();
            $table->string('appointment_type')->nullable();
            $table->date('appointment_date');
            $table->string('appointment_time', 16);
            $table->string('status', 24)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->integer('pet_age')->nullable();
            $table->integer('pet_age_months')->nullable();
            $table->date('pet_dob')->nullable();
            $table->date('dob')->nullable();
            $table->string('pet_gender')->nullable();
            $table->text('reported_symptom')->nullable();
            $table->string('suggested_disease')->nullable();
            $table->string('health_state')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pet_doc2')->nullable();
            $table->boolean('is_nuetered')->nullable();
            $table->boolean('is_neutered')->nullable();
            $table->boolean('deworming_yes_no')->nullable();
            $table->date('last_deworming_date')->nullable();
            $table->string('deworming_status')->nullable();
            $table->date('next_deworming_date')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_store_allows_missing_clinic_and_doctor_ids(): void
    {
        $response = $this->postJson('/api/appointments/submit', [
            'patient_name' => 'Walk In Patient',
            'patient_phone' => '9000011111',
            'date' => '2026-04-10',
            'time_slot' => '10:30:00',
            'notes' => 'Walk-in without assigned clinic or doctor',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointment.clinic.id', null)
            ->assertJsonPath('data.appointment.doctor.id', null)
            ->assertJsonPath('data.appointment.patient.phone', '9000011111');

        $this->assertDatabaseHas('appointments', [
            'name' => 'Walk In Patient',
            'mobile' => '9000011111',
            'vet_registeration_id' => null,
            'doctor_id' => null,
            'appointment_date' => '2026-04-10',
            'appointment_time' => '10:30:00',
        ]);
    }

    public function test_store_infers_clinic_id_from_doctor_when_clinic_id_is_omitted(): void
    {
        $clinicId = 501;
        $doctorId = 601;

        $this->createClinic($clinicId);
        $this->createDoctor($doctorId, $clinicId);

        $response = $this->postJson('/api/appointments/submit', [
            'doctor_id' => $doctorId,
            'patient_name' => 'Mapped Patient',
            'patient_phone' => '9000012222',
            'date' => '2026-04-11',
            'time_slot' => '11:45:00',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointment.clinic.id', $clinicId)
            ->assertJsonPath('data.appointment.doctor.id', $doctorId);

        $this->assertDatabaseHas('appointments', [
            'name' => 'Mapped Patient',
            'mobile' => '9000012222',
            'vet_registeration_id' => $clinicId,
            'doctor_id' => $doctorId,
            'appointment_date' => '2026-04-11',
            'appointment_time' => '11:45:00',
        ]);
    }

    public function test_store_syncs_exact_notes_to_pet_reported_symptom(): void
    {
        DB::table('users')->insert([
            'id' => 701,
            'name' => 'Pet Parent',
            'email' => 'pet-parent@example.test',
            'phone' => '9000090000',
            'role' => 'pet_parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 801,
            'user_id' => 701,
            'name' => 'Sheru',
            'breed' => 'Indie',
            'pet_age' => 4,
            'pet_gender' => 'male',
            'reported_symptom' => 'old symptom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reportedSymptom = 'Vomiting twice since morning and not eating food';

        $response = $this->postJson('/api/appointments/submit', [
            'user_id' => 701,
            'patient_name' => 'Pet Parent',
            'patient_phone' => '9000090000',
            'pet_id' => 801,
            'pet_name' => 'Sheru',
            'date' => '2026-04-12',
            'time_slot' => '12:15:00',
            'notes' => $reportedSymptom,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointment.appointment_table.notes_decoded.text', $reportedSymptom);

        $this->assertDatabaseHas('pets', [
            'id' => 801,
            'reported_symptom' => $reportedSymptom,
        ]);

        $this->getJson('/api/pets/801/overview')
            ->assertOk()
            ->assertJsonPath('data.pet.reported_symptom', $reportedSymptom);
    }

    public function test_store_syncs_reported_symptom_alias_to_pet(): void
    {
        DB::table('users')->insert([
            'id' => 702,
            'name' => 'Alias Parent',
            'email' => 'alias-parent@example.test',
            'phone' => '9000090001',
            'role' => 'pet_parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 802,
            'user_id' => 702,
            'name' => 'Milo',
            'breed' => 'Beagle',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reportedSymptom = 'Limping after evening walk';

        $response = $this->postJson('/api/appointments/submit', [
            'user_id' => 702,
            'patient_name' => 'Alias Parent',
            'patient_phone' => '9000090001',
            'pet_id' => 802,
            'pet_name' => 'Milo',
            'date' => '2026-04-13',
            'time_slot' => '13:30:00',
            'reported_symptom' => $reportedSymptom,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointment.appointment_table.notes_decoded.text', $reportedSymptom);

        $this->assertDatabaseHas('pets', [
            'id' => 802,
            'reported_symptom' => $reportedSymptom,
        ]);
    }

    private function createClinic(int $id): void
    {
        DB::table('vet_registerations_temp')->insert([
            'id' => $id,
            'name' => 'Clinic '.$id,
            'city' => 'Mumbai',
            'pincode' => '400001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDoctor(int $id, int $clinicId): void
    {
        DB::table('doctors')->insert([
            'id' => $id,
            'vet_registeration_id' => $clinicId,
            'doctor_name' => 'Doctor '.$id,
            'doctor_email' => "doctor{$id}@example.test",
            'doctor_mobile' => '9000099999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
