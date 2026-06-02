<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateAppointmentInClinicWithoutPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('users');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('pets');

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

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
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

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->string('status')->default('pending')->index();
            $table->string('type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('users');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('pets');

        parent::tearDown();
    }

    public function test_creates_appointment_and_pending_transaction_correctly(): void
    {
        // Seed database
        DB::table('vet_registerations_temp')->insert([
            'id' => 10,
            'name' => 'Healing Paws Clinic',
            'city' => 'Delhi',
            'pincode' => '110001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 20,
            'vet_registeration_id' => 10,
            'doctor_name' => 'Dr. John Doe',
            'doctor_email' => 'john.doe@example.com',
            'doctor_mobile' => '9999988888',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 30,
            'name' => 'Jane Pet Parent',
            'email' => 'jane@example.com',
            'phone' => '8888877777',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 40,
            'user_id' => 30,
            'name' => 'Fluffy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/create-appointment-in-clinic-without-payment', [
            'clinic_id' => 10,
            'user_id' => 30,
            'doctor_id' => 20,
            'pet_id' => 40,
            'appointment_date' => '2026-06-15',
            'appointment_time' => '10:40:00',
            'amount' => 450.50,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Appointment created successfully without payment.',
        ]);

        $responseData = $response->json('data');
        $this->assertNotNull($responseData['appointment_id']);
        $this->assertNotNull($responseData['transaction_id']);
        $this->assertNotNull($responseData['transaction_reference']);
        $this->assertEquals(45050, $responseData['amount_paise']);

        // Assert Appointment was created in the database and linked to the correct transaction
        $this->assertDatabaseHas('appointments', [
            'id' => $responseData['appointment_id'],
            'vet_registeration_id' => 10,
            'doctor_id' => 20,
            'pet_id' => 40,
            'transaction_id' => $responseData['transaction_id'],
            'name' => 'Jane Pet Parent',
            'mobile' => '8888877777',
            'pet_name' => 'Fluffy',
            'appointment_date' => '2026-06-15',
            'appointment_time' => '10:40:00',
            'status' => 'confirmed',
        ]);

        // Assert Transaction was created in the database with status pending and correct amount_paise
        $this->assertDatabaseHas('transactions', [
            'id' => $responseData['transaction_id'],
            'clinic_id' => 10,
            'doctor_id' => 20,
            'user_id' => 30,
            'pet_id' => 40,
            'amount_paise' => 45050,
            'status' => 'pending',
            'type' => 'appointments',
            'reference' => $responseData['transaction_reference'],
        ]);

        // Assert metadata contains correct attributes
        $transaction = DB::table('transactions')->where('id', $responseData['transaction_id'])->first();
        $metadata = json_decode($transaction->metadata, true);
        $this->assertEquals($responseData['appointment_id'], $metadata['appointment_id']);
        $this->assertEquals('Healing Paws Clinic', $metadata['clinic_name']);
        $this->assertEquals('Dr. John Doe', $metadata['doctor_name']);
        $this->assertEquals('Jane Pet Parent', $metadata['user_name']);
        $this->assertEquals('Fluffy', $metadata['pet_name']);
        $this->assertEquals(45050, $metadata['amount_paise']);
    }
}
