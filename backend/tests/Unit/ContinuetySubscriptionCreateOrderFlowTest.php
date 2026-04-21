<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentController;
use App\Models\CallSession;
use App\Models\Transaction;
use App\Models\VideoApointment;
use App\Services\Push\FcmService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContinuetySubscriptionCreateOrderFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('video_apointment');
        Schema::dropIfExists('call_sessions');
        Schema::dropIfExists('transactions');
        Schema::enableForeignKeyConstraints();

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

        Schema::create('call_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('channel_name');
            $table->enum('status', ['pending', 'accepted', 'ended'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamps();
        });

        Schema::create('video_apointment', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable()->index();
            $table->unsignedBigInteger('pet_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->string('call_session')->nullable()->index();
            $table->boolean('is_completed')->default(false)->index();
            $table->timestamps();
        });
    }

    public function test_continuety_subscription_uses_same_pre_payment_database_flow_as_video_consult(): void
    {
        $controller = $this->makeController();
        $request = Request::create('/api/create-order', 'POST', []);
        $context = [
            'clinic_id' => 115,
            'doctor_id' => 116,
            'user_id' => 1387,
            'pet_id' => 501,
        ];

        $this->assertTrue($controller->usesVideoConsultCreateOrderFlowPublic('continuety_subscription'));

        $callSession = $controller->createCallSessionPublic($context);

        $this->assertInstanceOf(CallSession::class, $callSession);
        $this->assertSame(1387, (int) $callSession->patient_id);
        $this->assertSame(116, (int) $callSession->doctor_id);
        $this->assertSame('pending', $callSession->status);
        $this->assertSame('unpaid', $callSession->payment_status);

        $context['call_identifier'] = $callSession->resolveIdentifier();
        $context['channel_name'] = $callSession->channel_name;
        $notes = [
            'order_type' => 'continuety_subscription',
            'channel_name' => $callSession->channel_name,
        ];
        $order = [
            'id' => 'order_cont_123',
            'amount' => 99900,
            'currency' => 'INR',
            'receipt' => 'rcpt_cont_123',
        ];

        $transaction = $controller->recordPendingTransactionPublic(
            $request,
            $order,
            $notes,
            $context,
            true
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame('continuety_subscription', $transaction->type);
        $this->assertSame('pending', $transaction->status);
        $this->assertSame('order_cont_123', $transaction->reference);
        $this->assertSame(99900, (int) $transaction->amount_paise);
        $this->assertSame('continuety_subscription', data_get($transaction->metadata, 'order_type'));
        $this->assertSame($callSession->channel_name, data_get($transaction->metadata, 'channel_name'));

        $videoApointment = $controller->recordVideoApointmentOrderPublic(
            $request,
            $order,
            $context,
            $callSession,
            $notes,
            true
        );

        $this->assertInstanceOf(VideoApointment::class, $videoApointment);
        $this->assertSame('order_cont_123', $videoApointment->order_id);
        $this->assertSame(501, (int) $videoApointment->pet_id);
        $this->assertSame(1387, (int) $videoApointment->user_id);
        $this->assertSame(116, (int) $videoApointment->doctor_id);
        $this->assertSame(115, (int) $videoApointment->clinic_id);
        $this->assertSame($callSession->resolveIdentifier(), $videoApointment->call_session);
        $this->assertFalse((bool) $videoApointment->is_completed);
    }

    private function makeController(): PaymentController
    {
        return new class(
            $this->createMock(WhatsAppService::class),
            $this->createMock(FcmService::class)
        ) extends PaymentController {
            public function usesVideoConsultCreateOrderFlowPublic(?string $transactionType): bool
            {
                return $this->usesVideoConsultCreateOrderFlow($transactionType);
            }

            public function createCallSessionPublic(array $context): ?CallSession
            {
                return $this->createCallSessionIfMissing($context);
            }

            public function recordPendingTransactionPublic(
                Request $request,
                array $order,
                array $notes,
                array $context,
                bool $throwOnFailure = false
            ): ?Transaction {
                return $this->recordPendingTransaction($request, $order, $notes, $context, $throwOnFailure);
            }

            public function recordVideoApointmentOrderPublic(
                Request $request,
                array $order,
                array $context,
                ?CallSession $callSession = null,
                array $notes = [],
                bool $throwOnFailure = false
            ): ?VideoApointment {
                return $this->recordVideoApointmentOrder($request, $order, $context, $callSession, $notes, $throwOnFailure);
            }
        };
    }
}
