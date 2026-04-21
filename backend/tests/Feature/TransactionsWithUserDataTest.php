<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionsWithUserDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('video_apointment');
        Schema::dropIfExists('call_sessions');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('breed')->nullable();
            $table->integer('pet_age')->nullable();
            $table->string('pet_gender')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('pet_doc2')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->unsignedBigInteger('actual_amount_paid_by_consumer_paise')->nullable();
            $table->unsignedBigInteger('payment_to_snoutiq_paise')->nullable();
            $table->unsignedBigInteger('payment_to_doctor_paise')->nullable();
            $table->string('status')->default('pending');
            $table->string('type')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token');
            $table->json('meta')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('call_session')->nullable();
            $table->timestamps();
        });

        Schema::create('call_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('channel_name');
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->timestamps();
        });

        Schema::create('video_apointment', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('call_session')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    public function test_transactions_with_user_data_includes_continuety_subscription_rows(): void
    {
        DB::table('users')->insert([
            'id' => 1387,
            'name' => 'Rohit Sharma',
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
            'breed' => 'Labrador',
            'pet_age' => 5,
            'pet_gender' => 'male',
            'weight' => 24.50,
            'pet_doc2' => 'pet-doc.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('device_tokens')->insert([
            'user_id' => 1387,
            'token' => 'token_123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'id' => 901,
            'doctor_id' => 116,
            'user_id' => 1387,
            'pet_id' => 501,
            'amount_paise' => 99900,
            'actual_amount_paid_by_consumer_paise' => 99900,
            'payment_to_snoutiq_paise' => 19900,
            'payment_to_doctor_paise' => 80000,
            'status' => 'captured',
            'type' => 'continuety_subscription',
            'channel_name' => 'channel_cont_123',
            'payment_method' => 'razorpay',
            'reference' => 'order_cont_123',
            'metadata' => json_encode(['order_type' => 'continuety_subscription'], JSON_THROW_ON_ERROR),
            'created_at' => '2026-04-21 14:15:00',
            'updated_at' => '2026-04-21 14:15:00',
        ]);

        DB::table('prescriptions')->insert([
            'call_session' => 'channel_cont_123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('call_sessions')->insert([
            'id' => 301,
            'patient_id' => 1387,
            'doctor_id' => 116,
            'channel_name' => 'channel_cont_123',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('video_apointment')->insert([
            'id' => 401,
            'order_id' => 'order_cont_123',
            'pet_id' => 501,
            'user_id' => 1387,
            'doctor_id' => 116,
            'clinic_id' => 115,
            'call_session' => 'cont123',
            'is_completed' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/transactions/with-user-data?doctor_id=116&date=2026-04-21');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 1,
            ])
            ->assertJsonPath('data.0.id', 901)
            ->assertJsonPath('data.0.user_id', 1387)
            ->assertJsonPath('data.0.doctor_id', 116)
            ->assertJsonPath('data.0.amount_paise', 99900)
            ->assertJsonPath('data.0.actual_amount_paid_by_consumer_paise', 99900)
            ->assertJsonPath('data.0.payment_to_snoutiq_paise', 19900)
            ->assertJsonPath('data.0.payment_to_doctor_paise', 80000)
            ->assertJsonPath('data.0.status', 'captured')
            ->assertJsonPath('data.0.type', 'continuety_subscription')
            ->assertJsonPath('data.0.payment_method', 'razorpay')
            ->assertJsonPath('data.0.reference', 'order_cont_123')
            ->assertJsonPath('data.0.prescription_send', true)
            ->assertJsonPath('data.0.user_name', 'Rohit Sharma')
            ->assertJsonPath('data.0.doctor_name', 'Dr. Mehta')
            ->assertJsonPath('data.0.device_tokens.0', 'token_123')
            ->assertJsonPath('data.0.pet.id', 501)
            ->assertJsonPath('data.0.pet.name', 'Bruno')
            ->assertJsonPath('data.0.call_session.channel_name', 'channel_cont_123')
            ->assertJsonPath('data.0.video_appointment.order_id', 'order_cont_123');
    }
}
