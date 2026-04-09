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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
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
