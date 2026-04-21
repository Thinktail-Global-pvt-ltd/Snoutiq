<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\UserMonthlySubscription;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RazorpaySubscriptionWebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.razorpay.webhook_secret', 'whsec_test_subscription');

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('user_monthly_subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('transactions');
        Schema::enableForeignKeyConstraints();

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('amount_paise')->default(0);
            $table->unsignedBigInteger('actual_amount_paid_by_consumer_paise')->nullable();
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

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('event');
            $table->string('signature')->nullable();
            $table->longText('payload');
            $table->dateTimeTz('processed_at', 0)->nullable();
            $table->unsignedSmallInteger('retries')->default(0);
            $table->timestamps();
            $table->unique(['source', 'event', 'signature'], 'uniq_webhook_source_event_sig');
        });
    }

    public function test_subscription_charged_webhook_inserts_transaction_and_updates_subscription(): void
    {
        $payload = [
            'entity' => 'event',
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => 'sub_test_123',
                        'entity' => 'subscription',
                        'plan_id' => 'plan_test_123',
                        'customer_id' => 'cust_test_123',
                        'status' => 'active',
                        'current_start' => now()->startOfDay()->timestamp,
                        'current_end' => now()->addMonthNoOverflow()->startOfDay()->timestamp,
                        'total_count' => 12,
                        'paid_count' => 1,
                        'remaining_count' => 11,
                        'quantity' => 1,
                        'notes' => [
                            'order_type' => 'customer_subscription',
                            'user_id' => '1387',
                            'pet_id' => '501',
                            'clinic_id' => '77',
                            'doctor_id' => '88',
                            'channel_name' => 'sub_chan_001',
                        ],
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test_123',
                        'entity' => 'payment',
                        'amount' => 99900,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'method' => 'card',
                        'email' => 'user@example.com',
                        'contact' => '9999999999',
                        'order_id' => 'order_test_123',
                        'invoice_id' => 'inv_test_123',
                        'created_at' => now()->timestamp,
                    ],
                ],
            ],
        ];

        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $rawBody, 'whsec_test_subscription');

        $response = $this->call(
            'POST',
            '/api/razorpay/subscription-webhook',
            [],
            [],
            [],
            [
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
                'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_test_123',
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawBody
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'event' => 'subscription.charged',
                    'subscription_id' => 'sub_test_123',
                    'payment_id' => 'pay_test_123',
                ],
            ]);

        $transaction = Transaction::query()->where('reference', 'pay_test_123')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('monthly_subscription', $transaction->type);
        $this->assertSame('captured', $transaction->status);
        $this->assertSame(1387, $transaction->user_id);
        $this->assertSame(501, $transaction->pet_id);
        $this->assertSame(77, $transaction->clinic_id);
        $this->assertSame(88, $transaction->doctor_id);
        $this->assertSame(99900, $transaction->amount_paise);
        $this->assertSame('sub_test_123', $transaction->metadata['subscription_id'] ?? null);

        $subscription = UserMonthlySubscription::query()->where('user_id', 1387)->first();
        $this->assertNotNull($subscription);
        $this->assertSame('active', $subscription->status);
        $this->assertSame($transaction->id, $subscription->transaction_id);
        $this->assertSame('sub_test_123', $subscription->order_reference);
        $this->assertSame('pay_test_123', $subscription->payment_reference);
        $this->assertSame(99900, $subscription->amount_paise);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->expires_at);
    }
}
