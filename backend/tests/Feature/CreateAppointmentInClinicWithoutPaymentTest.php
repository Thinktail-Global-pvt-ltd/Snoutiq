<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreateAppointmentInClinicWithoutPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*/api/push/test' => Http::response(['success' => true, 'sent' => true], 200),
            '*/backend/api/push/test' => Http::response(['success' => true, 'sent' => true], 200),
        ]);

        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::dropIfExists('users');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('device_tokens');

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

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('token')->nullable();
            $table->string('platform')->nullable();
            $table->string('device_id')->nullable();
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
        Schema::dropIfExists('device_tokens');

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

    public function test_gets_pending_transactions_for_doctor_correctly(): void
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

        // Create pending transaction via API
        $responseCreate = $this->postJson('/api/create-appointment-in-clinic-without-payment', [
            'clinic_id' => 10,
            'user_id' => 30,
            'doctor_id' => 20,
            'appointment_date' => '2026-06-15',
            'appointment_time' => '10:40:00',
            'amount' => 450.50,
        ]);
        $responseCreate->assertStatus(201);
        $createData = $responseCreate->json('data');

        // Seed a completed transaction for the same doctor (should be filtered out)
        DB::table('transactions')->insert([
            'id' => 999,
            'clinic_id' => 10,
            'doctor_id' => 20,
            'user_id' => 30,
            'amount_paise' => 50000,
            'status' => 'completed', // completed, not pending
            'type' => 'appointments',
            'reference' => 'completed_ref',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Call the GET API
        $responseGet = $this->getJson('/api/appointments/pending-transactions?doctor_id=20');

        $responseGet->assertStatus(200);
        $responseGet->assertJsonPath('success', true);
        $responseGet->assertJsonPath('count', 1);

        $data = $responseGet->json('data');
        $this->assertCount(1, $data);

        $pendingTx = $data[0];
        $this->assertEquals($createData['transaction_id'], $pendingTx['transaction_id']);
        $this->assertEquals($createData['transaction_reference'], $pendingTx['transaction_reference']);
        $this->assertEquals(45050, $pendingTx['amount_paise']);
        $this->assertEquals('pending', $pendingTx['status']);
        
        $this->assertNotNull($pendingTx['appointment']);
        $this->assertEquals($createData['appointment_id'], $pendingTx['appointment']['id']);
        $this->assertEquals('Jane Pet Parent', $pendingTx['appointment']['name']);
        $this->assertEquals('2026-06-15', $pendingTx['appointment']['appointment_date']);
        $this->assertEquals('10:40:00', $pendingTx['appointment']['appointment_time']);
    }

    public function test_captures_appointment_payment_successfully(): void
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

        // Create pending transaction via API
        $responseCreate = $this->postJson('/api/create-appointment-in-clinic-without-payment', [
            'clinic_id' => 10,
            'user_id' => 30,
            'doctor_id' => 20,
            'appointment_date' => '2026-06-15',
            'appointment_time' => '10:40:00',
            'amount' => 450.50,
        ]);
        $responseCreate->assertStatus(201);
        $createData = $responseCreate->json('data');

        // Capture payment via API
        $responseCapture = $this->postJson('/api/appointments/capture-transaction', [
            'appointment_id' => $createData['appointment_id'],
            'transaction_id' => $createData['transaction_id'],
        ]);

        $responseCapture->assertStatus(200);
        $responseCapture->assertJsonPath('success', true);
        $responseCapture->assertJsonPath('data.status', 'captured');

        // Assert status changed to captured in DB
        $this->assertDatabaseHas('transactions', [
            'id' => $createData['transaction_id'],
            'status' => 'captured',
        ]);
    }

    public function test_fails_to_capture_unlinked_appointment_payment(): void
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

        // Create appointment 1
        $responseCreate1 = $this->postJson('/api/create-appointment-in-clinic-without-payment', [
            'clinic_id' => 10,
            'user_id' => 30,
            'doctor_id' => 20,
            'appointment_date' => '2026-06-15',
            'appointment_time' => '10:40:00',
        ]);
        $createData1 = $responseCreate1->json('data');

        // Create appointment 2
        $responseCreate2 = $this->postJson('/api/create-appointment-in-clinic-without-payment', [
            'clinic_id' => 10,
            'user_id' => 30,
            'doctor_id' => 20,
            'appointment_date' => '2026-06-16',
            'appointment_time' => '11:00:00',
        ]);
        $createData2 = $responseCreate2->json('data');

        // Attempt to capture transaction of appointment 1 with appointment 2 (should fail)
        $responseCapture = $this->postJson('/api/appointments/capture-transaction', [
            'appointment_id' => $createData2['appointment_id'],
            'transaction_id' => $createData1['transaction_id'],
        ]);

        $responseCapture->assertStatus(422);
        $responseCapture->assertJsonPath('success', false);
        $responseCapture->assertJsonPath('message', 'The provided transaction is not linked to this appointment.');
    }

    public function test_appointment_submission_links_transaction_and_triggers_notifications(): void
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

        // Create transaction in DB
        DB::table('transactions')->insert([
            'id' => 777,
            'clinic_id' => null, // Will be linked
            'doctor_id' => null, // Will be linked
            'user_id' => null,   // Will be linked
            'pet_id' => null,    // Will be linked
            'amount_paise' => 45050,
            'status' => 'captured',
            'type' => 'appointments',
            'reference' => 'pay_abc123',
            'metadata' => json_encode([
                'order_id' => 'order_xyz456',
                'payment_id' => 'pay_abc123',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Call Submit Appointment API with payment details
        $response = $this->postJson('/api/appointments/submit', [
            'clinic_id' => 10,
            'doctor_id' => 20,
            'user_id' => 30,
            'pet_id' => 40,
            'patient_name' => 'Jane Pet Parent',
            'patient_phone' => '8888877777',
            'patient_email' => 'jane@example.com',
            'pet_name' => 'Fluffy',
            'date' => '2026-06-15',
            'time_slot' => '10:40:00',
            'amount' => 45050,
            'currency' => 'INR',
            'razorpay_payment_id' => 'pay_abc123',
            'razorpay_order_id' => 'order_xyz456',
            'razorpay_signature' => 'signature123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Assert appointment has correct transaction_id
        $this->assertDatabaseHas('appointments', [
            'vet_registeration_id' => 10,
            'doctor_id' => 20,
            'pet_id' => 40,
            'transaction_id' => 777,
            'status' => 'confirmed',
        ]);

        // Assert transaction updated with clinic, doctor, user, pet, and appointment metadata
        $transaction = DB::table('transactions')->where('id', 777)->first();
        $this->assertEquals(10, $transaction->clinic_id);
        $this->assertEquals(20, $transaction->doctor_id);
        $this->assertEquals(30, $transaction->user_id);
        $this->assertEquals(40, $transaction->pet_id);

        $metadata = json_decode($transaction->metadata, true);
        $this->assertNotNull($metadata['appointment_id']);
        $this->assertEquals('Dr. John Doe', $metadata['doctor_name']);
        $this->assertEquals('Healing Paws Clinic', $metadata['clinic_name']);
    }

    public function test_appointment_rescheduling_triggers_whatsapp_notification(): void
    {
        // Seed data
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

        $userId = DB::table('users')->insertGetId([
            'name' => 'Jane Pet Parent',
            'phone' => '918888877777',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('device_tokens')->insert([
            'user_id' => $userId,
            'token' => 'fcm-test-token-12345',
            'platform' => 'android',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'vet_registeration_id' => 10,
            'doctor_id' => 20,
            'pet_id' => 40,
            'name' => 'Jane Pet Parent',
            'mobile' => '8888877777',
            'appointment_date' => '2026-06-15',
            'appointment_time' => '10:40:00',
            'status' => 'confirmed',
            'notes' => json_encode(['patient_user_id' => $userId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mock WhatsAppService
        $whatsAppMock = $this->createMock(\App\Services\WhatsAppService::class);
        $whatsAppMock->method('isConfigured')->willReturn(true);
        
        $whatsAppMock->expects($this->once())
            ->method('sendTemplate')
            ->with(
                $this->equalTo('918888877777'),
                $this->equalTo('appointment_reschedule_1'),
                $this->callback(function ($components) {
                    $params = $components[0]['parameters'];
                    return $params[0]['text'] === 'Jane Pet Parent' &&
                           $params[1]['text'] === 'Dr. John Doe' &&
                           $params[2]['text'] === '2026-06-16' &&
                           $params[3]['text'] === '11:00:00';
                }),
                $this->equalTo('en_US'),
                $this->equalTo('appointment_reschedule_alert')
            );

        $this->app->instance(\App\Services\WhatsAppService::class, $whatsAppMock);

        // Mock FcmService
        $fcmMock = $this->createMock(\App\Services\Push\FcmService::class);
        $fcmMock->expects($this->once())
            ->method('sendToToken')
            ->with(
                $this->equalTo('fcm-test-token-12345'),
                $this->equalTo('Appointment Rescheduled'),
                $this->equalTo('Your upcoming appointment with Dr. John Doe has been rescheduled for 2026-06-16 at 11:00:00.'),
                $this->callback(function ($data) use ($appointmentId) {
                    return $data['type'] === 'test' &&
                           $data['appointment_id'] === (string) $appointmentId &&
                           $data['doctor_id'] === '20' &&
                           $data['clinic_id'] === '10' &&
                           $data['start_time'] === '2026-06-16 11:00:00';
                })
            );

        $this->app->instance(\App\Services\Push\FcmService::class, $fcmMock);

        // Put request to reschedule
        $response = $this->putJson("/api/appointments/{$appointmentId}", [
            'date' => '2026-06-16',
            'time_slot' => '11:00:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'appointment'
            ],
            'whatsapp' => [
                'sent',
                'to',
                'template'
            ],
            'fcm' => [
                'sent',
                'success_count',
                'failure_count'
            ]
        ]);
    }

    public function test_active_slots_excludes_already_booked_appointments(): void
    {
        // 1. Seed doctor
        DB::table('doctors')->insert([
            'id' => 20,
            'doctor_name' => 'Dr. John Doe',
            'doctor_email' => 'john.doe@example.com',
            'doctor_mobile' => '9999988888',
        ]);

        // 2. Setup doctor availability in doctor_availability table
        Schema::dropIfExists('doctor_availability');
        Schema::create('doctor_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->integer('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('service_type')->nullable();
            $table->timestamps();
        });

        DB::table('doctor_availability')->insert([
            'doctor_id' => 20,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'service_type' => 'in_clinic',
        ]);

        // 3. Seed an appointment booking for 09:20:00
        DB::table('appointments')->insert([
            'vet_registeration_id' => 10,
            'doctor_id' => 20,
            'pet_id' => 40,
            'name' => 'Jane Pet Parent',
            'mobile' => '8888877777',
            'appointment_date' => '2026-06-15', // a future Monday
            'appointment_time' => '09:20:00',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Request active-slots API
        $response = $this->getJson('/api/doctors/active-slots?doctor_id=20&date=2026-06-15');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        $activeHours = $response->json('active_hours');
        $this->assertCount(1, $activeHours);
        
        $slots = $activeHours[0]['slots'];
        // Slots generated for 09:00-10:00: 09:00, 09:20, 09:40.
        // But 09:20 is booked, so it should be hidden!
        // So slots should contain 09:00 and 09:40 only.
        $this->assertCount(2, $slots);
        $this->assertEquals('09:00', $slots[0]['start']);
        $this->assertEquals('09:40', $slots[1]['start']);

        // Clean up
        Schema::dropIfExists('doctor_availability');
    }
}
