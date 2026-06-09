<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentController;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Pet;
use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PetParentFCMNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('users');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('device_tokens');
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
            $table->string('doctor_mobile')->nullable();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('token')->unique();
            $table->string('platform', 16)->nullable();
            $table->string('device_id', 128)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('users');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('device_tokens');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_pet_parent_fcm_notification_sends_to_proper_tokens_with_right_content(): void
    {
        Http::fake([
            '*/api/push/test' => Http::response(['success' => true, 'sent' => true], 200),
            '*/backend/api/push/test' => Http::response(['success' => true, 'sent' => true], 200),
        ]);

        $user = User::query()->create([
            'name' => 'Jane Doe',
            'phone' => '9876543210',
        ]);

        $doctor = Doctor::query()->create([
            'doctor_name' => 'Dr. Vet Surgeon',
            'doctor_mobile' => '9812345678',
        ]);

        $pet = Pet::query()->create([
            'user_id' => $user->id,
            'name' => 'Buddy',
        ]);

        DeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'fcm_parent_token_abc_123',
            'platform' => 'android',
        ]);

        config(['app.url' => 'http://localhost']);

        $controller = $this->makeController();

        $result = $controller->notifyPetParentOrderCreatedPublic(
            [
                'user_id' => $user->id,
                'doctor_id' => $doctor->id,
                'pet_id' => $pet->id,
            ],
            [
                'order_type' => 'appointment',
            ],
            450
        );

        $this->assertTrue($result['sent']);
        $this->assertSame(1, $result['sent_count']);
        $this->assertSame(1, $result['token_count']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/push/test') &&
                $request['token'] === 'fcm_parent_token_abc_123' &&
                $request['title'] === 'Appointment Booked' &&
                str_contains($request['body'], 'Buddy') &&
                str_contains($request['body'], 'Dr. Vet Surgeon');
        });
    }

    private function makeController(): PaymentController
    {
        return new class(
            $this->createMock(WhatsAppService::class),
            $this->createMock(FcmService::class)
        ) extends PaymentController {
            public function notifyPetParentOrderCreatedPublic(
                array $context,
                array $notes,
                int $amountInInr
            ): array {
                return $this->notifyPetParentOrderCreated($context, $notes, $amountInInr);
            }
        };
    }
}
