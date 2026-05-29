<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\Receptionist;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceptionistOtpLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('whatsapp.phone_number_id', '');
        config()->set('whatsapp.access_token', '');
        $this->app->instance(WhatsAppService::class, new WhatsAppService(null, null));

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('chat_rooms');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('otps');
        Schema::dropIfExists('receptionists');
        Schema::dropIfExists('vet_registerations_temp');
        Schema::enableForeignKeyConstraints();

        Schema::create('vet_registerations_temp', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('clinic_profile')->nullable();
            $table->timestamps();
        });

        Schema::create('receptionists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->json('meta')->nullable();
            $table->string('password')->nullable();
            $table->string('receptionist_password')->nullable();
            $table->string('api_token_hash')->nullable();
            $table->timestamp('api_token_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_email')->nullable();
            $table->string('doctor_mobile')->nullable();
            $table->string('doctor_license')->nullable();
            $table->string('doctor_image')->nullable();
            $table->boolean('toggle_availability')->nullable();
            $table->integer('doctors_price')->nullable();
            $table->timestamps();
        });

        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->string('type');
            $table->string('value');
            $table->string('otp');
            $table->integer('is_verified')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('chat_room_token', 80)->unique();
            $table->string('name', 150)->nullable();
            $table->timestamps();
        });
    }

    public function test_receptionist_can_login_with_phone_otp(): void
    {
        $clinicId = 25;

        \DB::table('vet_registerations_temp')->insert([
            'id' => $clinicId,
            'name' => 'SnoutIQ Clinic',
            'clinic_profile' => 'SnoutIQ Clinic',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receptionist = Receptionist::query()->create([
            'vet_registeration_id' => $clinicId,
            'name' => 'Front Desk',
            'email' => 'frontdesk@example.com',
            'phone' => '9876543210',
            'role' => 'receptionist',
        ]);

        $requestResponse = $this->postJson('/api/auth/receptionist/otp/request', [
            'phone' => '+91 98765 43210',
        ]);

        $requestResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['request_id', 'otp']);

        $otp = Otp::query()->where('type', 'receptionist_whatsapp')->firstOrFail();

        $verifyResponse = $this->postJson('/api/auth/receptionist/otp/verify', [
            'phone' => '9876543210',
            'otp' => $otp->otp,
            'request_id' => $otp->token,
        ]);

        $verifyResponse
            ->assertOk()
            ->assertJsonPath('role', 'receptionist')
            ->assertJsonPath('receptionist_id', $receptionist->id)
            ->assertJsonPath('clinic_id', $clinicId)
            ->assertJsonPath('user.phone', '9876543210');

        $this->assertSame(1, Otp::query()->where('is_verified', 1)->count());
        $this->assertSame(1, \DB::table('chat_rooms')->count());
    }

    public function test_receptionist_special_phone_gets_fixed_otp(): void
    {
        Receptionist::query()->create([
            'name' => 'Fixed OTP Desk',
            'email' => 'fixed-otp@example.com',
            'phone' => '8799730966',
            'role' => 'receptionist',
        ]);

        $response = $this->postJson('/api/auth/receptionist/otp/request', [
            'phone' => '8799730966',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('otps', [
            'type' => 'receptionist_whatsapp',
            'value' => '8799730966',
            'otp' => '000000',
            'is_verified' => 0,
        ]);
    }
}
