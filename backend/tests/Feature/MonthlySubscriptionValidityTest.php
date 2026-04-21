<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlySubscriptionValidityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('user_monthly_subscriptions');
        Schema::dropIfExists('transactions');
        Schema::enableForeignKeyConstraints();

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
    }

    public function test_validity_api_returns_active_one_time_subscription_from_monthly_subscription_table(): void
    {
        DB::table('user_monthly_subscriptions')->insert([
            'user_id' => 1387,
            'transaction_id' => 101,
            'order_reference' => 'order_test_123',
            'payment_reference' => 'pay_test_123',
            'status' => 'active',
            'amount_paise' => 99900,
            'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
            'activated_at' => now()->subDays(5),
            'metadata' => json_encode([
                'order_type' => 'monthly_subscription',
                'subscription_selected' => 0,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/monthly-subscription/validity?user_id=1387');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'user_id' => 1387,
                'has_valid_subscription' => true,
                'has_active_subscription' => true,
                'has_subscription_record' => true,
                'status' => 'active',
                'source' => 'user_monthly_subscriptions',
                'payment_mode' => 'one_time',
            ])
            ->assertJsonPath('subscription.order_reference', 'order_test_123')
            ->assertJsonPath('subscription.payment_reference', 'pay_test_123');

        $this->assertGreaterThan(0, (int) $response->json('days_left'));
    }

    public function test_validity_api_returns_pending_recurring_subscription_created_via_create_order(): void
    {
        DB::table('user_monthly_subscriptions')->insert([
            'user_id' => 2001,
            'transaction_id' => null,
            'order_reference' => 'sub_test_123',
            'payment_reference' => null,
            'status' => 'pending',
            'amount_paise' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'activated_at' => null,
            'metadata' => json_encode([
                'order_type' => 'monthly_subscription',
                'subscription_selected' => 1,
                'plan_id' => 'plan_test_123',
                'pending_order' => [
                    'order_id' => 'sub_test_123',
                    'subscription_id' => 'sub_test_123',
                    'gateway_status' => 'created',
                    'created_at' => now()->toIso8601String(),
                ],
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/monthly-subscription/validity?user_id=2001');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'user_id' => 2001,
                'has_valid_subscription' => false,
                'has_active_subscription' => false,
                'has_subscription_record' => true,
                'status' => 'pending',
                'source' => 'user_monthly_subscriptions',
                'payment_mode' => 'recurring',
            ])
            ->assertJsonPath('subscription.order_reference', 'sub_test_123')
            ->assertJsonPath('subscription.status', 'pending');

        $this->assertGreaterThan(0, (int) $response->json('days_left'));
    }

    public function test_validity_api_falls_back_to_subscription_transaction_when_webhook_charge_exists(): void
    {
        DB::table('transactions')->insert([
            'user_id' => 3001,
            'amount_paise' => 99900,
            'actual_amount_paid_by_consumer_paise' => 99900,
            'status' => 'captured',
            'type' => 'monthly_subscription',
            'payment_method' => 'card',
            'reference' => 'pay_sub_123',
            'metadata' => json_encode([
                'order_type' => 'monthly_subscription',
                'subscription_selected' => 1,
                'subscription_id' => 'sub_test_789',
                'current_start' => now()->startOfDay()->timestamp,
                'current_end' => now()->addDays(26)->endOfDay()->timestamp,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/monthly-subscription/validity?user_id=3001');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'user_id' => 3001,
                'has_valid_subscription' => true,
                'has_active_subscription' => true,
                'has_subscription_record' => true,
                'status' => 'active',
                'source' => 'transactions',
                'payment_mode' => 'recurring',
            ])
            ->assertJsonPath('subscription.order_reference', 'sub_test_789')
            ->assertJsonPath('subscription.payment_reference', 'pay_sub_123');

        $this->assertGreaterThan(0, (int) $response->json('days_left'));
    }
}
