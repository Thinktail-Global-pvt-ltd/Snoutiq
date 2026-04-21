<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceptionistAppointmentsTodayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('pets');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('name');
            $table->string('mobile');
            $table->string('pet_name')->nullable();
            $table->date('appointment_date');
            $table->string('appointment_time');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->string('status')->default('pending');
            $table->string('type')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('in_clinic_appointment_id')->nullable();
            $table->string('call_session')->nullable();
            $table->string('diagnosis')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('follow_up_type')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('pet_doc1')->nullable();
            $table->string('pet_doc2')->nullable();
            $table->string('pic_link')->nullable();
            $table->timestamps();
        });
    }

    public function test_appointments_today_includes_continuety_subscription_transactions_for_same_clinic_and_date(): void
    {
        DB::table('users')->insert([
            'id' => 1387,
            'name' => 'Rohit Sharma',
            'phone' => '9999999999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 116,
            'doctor_name' => 'Dr. Mehta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pets')->insert([
            'id' => 501,
            'user_id' => 1387,
            'name' => 'Bruno',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'id' => 901,
            'clinic_id' => 115,
            'doctor_id' => 116,
            'user_id' => 1387,
            'pet_id' => 501,
            'amount_paise' => 99900,
            'status' => 'captured',
            'type' => 'continuety_subscription',
            'channel_name' => 'channel_cont_123',
            'reference' => 'order_cont_123',
            'metadata' => json_encode(['order_type' => 'continuety_subscription'], JSON_THROW_ON_ERROR),
            'created_at' => '2026-04-21 14:15:00',
            'updated_at' => '2026-04-21 14:15:00',
        ]);

        DB::table('prescriptions')->insert([
            'id' => 701,
            'call_session' => 'channel_cont_123',
            'diagnosis' => 'Needs follow up',
            'follow_up_date' => '2026-04-28',
            'follow_up_type' => 'video',
            'follow_up_notes' => 'Check recovery',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/receptionist/appointments/today?clinic_id=115&date=2026-04-21');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'date' => '2026-04-21',
                'count' => 1,
            ])
            ->assertJsonPath('appointments.0.id', 901)
            ->assertJsonPath('appointments.0.source', 'continuety_subscription')
            ->assertJsonPath('appointments.0.order_type', 'continuety_subscription')
            ->assertJsonPath('appointments.0.patient_name', 'Rohit Sharma')
            ->assertJsonPath('appointments.0.patient_phone', '9999999999')
            ->assertJsonPath('appointments.0.pet_name', 'Bruno')
            ->assertJsonPath('appointments.0.pet_id', 501)
            ->assertJsonPath('appointments.0.doctor_id', 116)
            ->assertJsonPath('appointments.0.doctor_name', 'Dr. Mehta')
            ->assertJsonPath('appointments.0.status', 'captured')
            ->assertJsonPath('appointments.0.notes_payment', true)
            ->assertJsonPath('appointments.0.patient_id', 1387)
            ->assertJsonPath('appointments.0.prescription_id', 701)
            ->assertJsonPath('appointments.0.prescription_diagnosis', 'Needs follow up')
            ->assertJsonPath('appointments.0.prescription_follow_up_date', '2026-04-28')
            ->assertJsonPath('appointments.0.prescription_follow_up_type', 'video')
            ->assertJsonPath('appointments.0.prescription_follow_up_notes', 'Check recovery');
    }
}
