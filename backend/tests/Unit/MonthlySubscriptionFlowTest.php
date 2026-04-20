<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentController;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMonthlySubscription;
use App\Services\Push\FcmService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlySubscriptionFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('user_monthly_subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
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

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_order_id', 191)->index();
            $table->string('razorpay_payment_id', 191)->unique();
            $table->string('razorpay_signature', 191);
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('currency', 10)->default('INR');
            $table->string('status', 50)->nullable();
            $table->string('method', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('contact', 30)->nullable();
            $table->json('notes')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('user_monthly_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('order_reference', 191)->nullable();
            $table->string('payment_reference', 191)->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function test_monthly_subscription_is_marked_pending_then_activated(): void
    {
        $user = User::query()->create([
            'name' => 'Monthly User',
            'email' => 'monthly@example.com',
            'password' => 'secret',
        ]);
        $controller = $this->makeController();

        $pendingTransaction = Transaction::query()->create([
            'user_id' => $user->id,
            'amount_paise' => 99900,
            'status' => 'pending',
            'type' => 'monthly_subscription',
            'reference' => 'order_sub_1',
            'metadata' => [
                'order_type' => 'monthly_subscription',
                'order_id' => 'order_sub_1',
            ],
        ]);

        $pending = $controller->syncPendingPublic(
            ['user_id' => $user->id],
            ['order_type' => 'monthly_subscription'],
            ['id' => 'order_sub_1', 'amount' => 99900, 'currency' => 'INR'],
            $pendingTransaction,
            true
        );

        $this->assertNotNull($pending);
        $this->assertSame('pending', $pending->status);
        $this->assertSame($user->id, $pending->user_id);
        $this->assertSame($pendingTransaction->id, $pending->transaction_id);
        $this->assertSame('order_sub_1', $pending->order_reference);

        $capturedTransaction = Transaction::query()->create([
            'user_id' => $user->id,
            'amount_paise' => 99900,
            'status' => 'captured',
            'type' => 'monthly_subscription',
            'reference' => 'pay_sub_1',
            'metadata' => [
                'order_type' => 'monthly_subscription',
                'order_id' => 'order_sub_1',
            ],
        ]);

        $payment = Payment::query()->create([
            'razorpay_order_id' => 'order_sub_1',
            'razorpay_payment_id' => 'pay_sub_1',
            'razorpay_signature' => 'sig_sub_1',
            'amount' => 99900,
            'currency' => 'INR',
            'status' => 'captured',
            'notes' => [
                'order_type' => 'monthly_subscription',
            ],
            'raw_response' => [],
        ]);

        $active = $controller->activatePublic(
            ['user_id' => $user->id],
            ['order_type' => 'monthly_subscription'],
            $capturedTransaction,
            $payment,
            99900,
            true
        );

        $this->assertNotNull($active);
        $this->assertSame('active', $active->status);
        $this->assertSame($capturedTransaction->id, $active->transaction_id);
        $this->assertSame('order_sub_1', $active->order_reference);
        $this->assertSame('pay_sub_1', $active->payment_reference);
        $this->assertTrue($active->starts_at !== null);
        $this->assertTrue($active->expires_at !== null);
        $this->assertTrue($active->expires_at->equalTo($active->starts_at->copy()->addMonthNoOverflow()));
    }

    public function test_monthly_subscription_renewal_extends_from_existing_expiry(): void
    {
        $user = User::query()->create([
            'name' => 'Renewal User',
            'email' => 'renewal@example.com',
            'password' => 'secret',
        ]);
        $existingExpiry = now()->addDays(10)->startOfMinute();

        UserMonthlySubscription::query()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'amount_paise' => 99900,
            'starts_at' => now()->subDays(20)->startOfMinute(),
            'expires_at' => $existingExpiry,
            'activated_at' => now()->subDays(20)->startOfMinute(),
            'metadata' => [
                'order_type' => 'monthly_subscription',
            ],
        ]);

        $controller = $this->makeController();

        $capturedTransaction = Transaction::query()->create([
            'user_id' => $user->id,
            'amount_paise' => 99900,
            'status' => 'captured',
            'type' => 'monthly_subscription',
            'reference' => 'pay_sub_renewal',
            'metadata' => [
                'order_type' => 'monthly_subscription',
                'order_id' => 'order_sub_renewal',
            ],
        ]);

        $payment = Payment::query()->create([
            'razorpay_order_id' => 'order_sub_renewal',
            'razorpay_payment_id' => 'pay_sub_renewal',
            'razorpay_signature' => 'sig_sub_renewal',
            'amount' => 99900,
            'currency' => 'INR',
            'status' => 'captured',
            'notes' => [
                'order_type' => 'monthly_subscription',
            ],
            'raw_response' => [],
        ]);

        $renewed = $controller->activatePublic(
            ['user_id' => $user->id],
            ['order_type' => 'monthly_subscription'],
            $capturedTransaction,
            $payment,
            99900,
            true
        );

        $this->assertNotNull($renewed);
        $this->assertSame('active', $renewed->status);
        $this->assertTrue($renewed->starts_at->equalTo($existingExpiry));
        $this->assertTrue($renewed->expires_at->equalTo($existingExpiry->copy()->addMonthNoOverflow()));
    }

    public function test_create_order_rejects_when_active_monthly_subscription_already_exists(): void
    {
        $user = User::query()->create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => 'secret',
        ]);

        $expiry = now()->addDays(12)->startOfMinute();

        UserMonthlySubscription::query()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'amount_paise' => 99900,
            'starts_at' => now()->subDays(18)->startOfMinute(),
            'expires_at' => $expiry,
            'activated_at' => now()->subDays(18)->startOfMinute(),
            'metadata' => [
                'order_type' => 'monthly_subscription',
            ],
        ]);

        $expectedDaysLeft = (int) ceil(now()->diffInSeconds($expiry, false) / 86400);

        $response = $this->postJson('/api/create-order', [
            'amount' => 999,
            'order_type' => 'monthly_subscription',
            'user_id' => $user->id,
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => 'Monthly subscription already active',
                'days_left' => $expectedDaysLeft,
            ]);
    }

    public function test_monthly_subscription_status_endpoint_returns_days_left(): void
    {
        $user = User::query()->create([
            'name' => 'Status User',
            'email' => 'status@example.com',
            'password' => 'secret',
        ]);

        $expiry = now()->addDays(7)->startOfMinute();

        UserMonthlySubscription::query()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'amount_paise' => 99900,
            'starts_at' => now()->subDays(23)->startOfMinute(),
            'expires_at' => $expiry,
            'activated_at' => now()->subDays(23)->startOfMinute(),
            'metadata' => [
                'order_type' => 'monthly_subscription',
            ],
        ]);

        $expectedDaysLeft = (int) ceil(now()->diffInSeconds($expiry, false) / 86400);

        $response = $this->getJson('/api/monthly-subscription/status?user_id=' . $user->id);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'user_id' => $user->id,
                'has_active_subscription' => true,
                'days_left' => $expectedDaysLeft,
            ]);
    }

    private function makeController(): PaymentController
    {
        return new class(
            $this->createMock(WhatsAppService::class),
            $this->createMock(FcmService::class)
        ) extends PaymentController {
            public function syncPendingPublic(
                array $context,
                array $notes,
                array $order,
                ?Transaction $transaction = null,
                bool $throwOnFailure = false
            ): ?UserMonthlySubscription {
                return $this->syncMonthlySubscriptionPending($context, $notes, $order, $transaction, $throwOnFailure);
            }

            public function activatePublic(
                array $context,
                array $notes,
                ?Transaction $transaction = null,
                ?Payment $payment = null,
                ?int $amountPaise = null,
                bool $throwOnFailure = false
            ): ?UserMonthlySubscription {
                return $this->activateMonthlySubscription($context, $notes, $transaction, $payment, $amountPaise, $throwOnFailure);
            }
        };
    }
}
