<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentController;
use App\Models\Doctor;
use App\Models\User;
use App\Services\Push\FcmService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VideoConsultCreateOrderWhatsAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
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
            $table->string('doctor_mobile')->nullable();
            $table->unsignedBigInteger('vet_registeration_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_video_consult_whatsapp_notifications_are_sent_for_both_sides(): void
    {
        $user = User::query()->create([
            'name' => 'Pet Parent',
            'phone' => '9876543210',
        ]);

        $doctor = Doctor::query()->create([
            'doctor_name' => 'Dr. Vet',
            'doctor_mobile' => '9812345678',
            'vet_registeration_id' => null,
        ]);

        $whatsApp = new class extends WhatsAppService {
            /** @var array<int, array<string, mixed>> */
            public array $calls = [];

            public function __construct()
            {
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function sendTemplate(
                string $to,
                ?string $template = null,
                array $components = [],
                ?string $language = null,
                ?string $channelName = null
            ): void {
                $this->calls[] = [
                    'to' => $to,
                    'template' => $template,
                    'components' => $components,
                    'language' => $language,
                    'channel_name' => $channelName,
                ];
            }
        };

        $controller = $this->makeController($whatsApp);

        $result = $controller->sendVideoConsultWhatsAppNotificationsPublic(
            [
                'user_id' => $user->id,
                'doctor_id' => $doctor->id,
                'pet_id' => null,
                'channel_name' => 'channel_test_123',
            ],
            [
                'order_type' => 'video_consult',
                'channel_name' => 'channel_test_123',
            ],
            500
        );

        $this->assertSame(2, count($whatsApp->calls));
        $this->assertSame('919876543210', $whatsApp->calls[0]['to']);
        $this->assertSame('pp_video_consult_booked', $whatsApp->calls[0]['template']);
        $this->assertCount(1, $whatsApp->calls[0]['components']);
        $this->assertSame('919812345678', $whatsApp->calls[1]['to']);
        $this->assertSame('appointment_confirmation_v2', $whatsApp->calls[1]['template']);
        $this->assertCount(1, $whatsApp->calls[1]['components']);

        $this->assertTrue((bool) data_get($result, 'whatsapp.sent'));
        $this->assertTrue((bool) data_get($result, 'vet_whatsapp.sent'));
        $this->assertSame('pp_video_consult_booked', data_get($result, 'whatsapp.template'));
        $this->assertSame('appointment_confirmation_v2', data_get($result, 'vet_whatsapp.template'));
    }

    private function makeController(WhatsAppService $whatsApp): PaymentController
    {
        return new class($whatsApp, $this->createMock(FcmService::class)) extends PaymentController {
            public function sendVideoConsultWhatsAppNotificationsPublic(
                array $context,
                array $notes,
                int $amountInInr
            ): array {
                return $this->sendVideoConsultWhatsAppNotifications($context, $notes, $amountInInr);
            }
        };
    }
}
