<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\UserMonthlySubscription;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RazorpaySubscriptionWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = trim((string) $request->header('X-Razorpay-Signature', ''));
        $eventId = trim((string) $request->header('X-Razorpay-Event-Id', ''));
        $secret = trim((string) config('services.razorpay.webhook_secret', ''));

        if ($secret === '') {
            Log::error('razorpay.subscription_webhook.missing_secret');

            return response()->json([
                'success' => false,
                'message' => 'Webhook secret is not configured.',
            ], 500);
        }

        if ($signature === '' || ! $this->isValidSignature($rawBody, $signature, $secret)) {
            Log::warning('razorpay.subscription_webhook.invalid_signature', [
                'has_signature' => $signature !== '',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
            ], 400);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ], 400);
        }

        $eventName = trim((string) ($payload['event'] ?? ''));
        if ($eventName === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing webhook event name.',
            ], 422);
        }

        if (! Schema::hasTable('webhook_events')) {
            Log::error('razorpay.subscription_webhook.missing_webhook_events_table');

            return response()->json([
                'success' => false,
                'message' => 'webhook_events table is missing.',
            ], 500);
        }

        if (! Schema::hasTable('transactions')) {
            Log::error('razorpay.subscription_webhook.missing_transactions_table');

            return response()->json([
                'success' => false,
                'message' => 'transactions table is missing.',
            ], 500);
        }

        $result = DB::transaction(function () use ($payload, $rawBody, $signature, $eventId, $eventName) {
            $webhookEvent = $this->storeWebhookEvent($eventName, $signature, $eventId, $rawBody);
            if ($webhookEvent->processed_at) {
                return [
                    'duplicate' => true,
                    'event' => $eventName,
                    'subscription_id' => data_get($payload, 'payload.subscription.entity.id'),
                    'payment_id' => data_get($payload, 'payload.payment.entity.id'),
                ];
            }

            $subscriptionEntity = data_get($payload, 'payload.subscription.entity');
            if (! is_array($subscriptionEntity)) {
                $webhookEvent->processed_at = now();
                $webhookEvent->save();

                return [
                    'duplicate' => false,
                    'ignored' => true,
                    'event' => $eventName,
                    'reason' => 'missing_subscription_entity',
                ];
            }

            $paymentEntity = data_get($payload, 'payload.payment.entity');
            $paymentEntity = is_array($paymentEntity) ? $paymentEntity : null;

            $payment = $this->upsertPayment($subscriptionEntity, $paymentEntity, $signature);
            $transaction = $this->upsertTransaction($eventName, $subscriptionEntity, $paymentEntity);
            $monthlySubscription = $this->upsertMonthlySubscription($eventName, $subscriptionEntity, $paymentEntity, $transaction);

            $webhookEvent->processed_at = now();
            $webhookEvent->save();

            return [
                'duplicate' => false,
                'event' => $eventName,
                'subscription_id' => $subscriptionEntity['id'] ?? null,
                'subscription_status' => $subscriptionEntity['status'] ?? null,
                'payment_id' => $payment?->razorpay_payment_id,
                'payment_status' => $payment?->status,
                'transaction_id' => $transaction?->id,
                'transaction_status' => $transaction?->status,
                'monthly_subscription_id' => $monthlySubscription?->id,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    private function isValidSignature(string $rawBody, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    private function storeWebhookEvent(string $eventName, string $signature, string $eventId, string $rawBody): WebhookEvent
    {
        $dedupeKey = $eventId !== '' ? 'evt:'.$eventId : 'sig:'.$signature;

        return WebhookEvent::firstOrCreate(
            [
                'source' => 'razorpay_subscription',
                'event' => $eventName,
                'signature' => $dedupeKey,
            ],
            [
                'payload' => $rawBody,
                'retries' => 0,
            ]
        );
    }

    private function upsertPayment(array $subscriptionEntity, ?array $paymentEntity, string $signature): ?Payment
    {
        if (! $paymentEntity || ! Schema::hasTable('payments')) {
            return null;
        }

        $paymentId = trim((string) ($paymentEntity['id'] ?? ''));
        if ($paymentId === '') {
            return null;
        }

        $subscriptionId = trim((string) ($subscriptionEntity['id'] ?? ''));
        $notes = is_array($subscriptionEntity['notes'] ?? null) ? $subscriptionEntity['notes'] : [];

        return Payment::updateOrCreate(
            ['razorpay_payment_id' => $paymentId],
            [
                'razorpay_order_id' => trim((string) ($paymentEntity['order_id'] ?? $subscriptionId)),
                'razorpay_signature' => $signature,
                'amount' => $this->nullableInt($paymentEntity['amount'] ?? null),
                'currency' => $paymentEntity['currency'] ?? 'INR',
                'status' => $paymentEntity['status'] ?? ($subscriptionEntity['status'] ?? null),
                'method' => $paymentEntity['method'] ?? 'razorpay_subscription',
                'email' => $paymentEntity['email'] ?? null,
                'contact' => $paymentEntity['contact'] ?? null,
                'notes' => array_filter([
                    'source' => 'razorpay_subscription_webhook',
                    'subscription_id' => $subscriptionId ?: null,
                    'plan_id' => $subscriptionEntity['plan_id'] ?? null,
                    'customer_id' => $subscriptionEntity['customer_id'] ?? null,
                    'user_id' => $notes['user_id'] ?? $notes['patient_id'] ?? null,
                    'pet_id' => $notes['pet_id'] ?? null,
                    'clinic_id' => $notes['clinic_id'] ?? null,
                    'doctor_id' => $notes['doctor_id'] ?? null,
                ], static fn ($value) => $value !== null && $value !== ''),
                'raw_response' => array_filter([
                    'subscription' => $subscriptionEntity,
                    'payment' => $paymentEntity,
                ]),
            ]
        );
    }

    private function upsertTransaction(string $eventName, array $subscriptionEntity, ?array $paymentEntity): ?Transaction
    {
        if (! $paymentEntity) {
            return null;
        }

        $paymentId = trim((string) ($paymentEntity['id'] ?? ''));
        if ($paymentId === '') {
            return null;
        }

        $notes = is_array($subscriptionEntity['notes'] ?? null) ? $subscriptionEntity['notes'] : [];
        $paymentStatus = trim((string) ($paymentEntity['status'] ?? ''));
        $subscriptionStatus = trim((string) ($subscriptionEntity['status'] ?? ''));
        $amountPaise = max(0, (int) ($paymentEntity['amount'] ?? 0));

        $metadata = array_filter([
            'order_type' => 'monthly_subscription',
            'requested_order_type' => $notes['order_type'] ?? null,
            'payment_provider' => 'razorpay',
            'payment_flow' => 'subscription_webhook',
            'event_name' => $eventName,
            'subscription_id' => $subscriptionEntity['id'] ?? null,
            'plan_id' => $subscriptionEntity['plan_id'] ?? null,
            'customer_id' => $subscriptionEntity['customer_id'] ?? null,
            'subscription_status' => $subscriptionStatus !== '' ? $subscriptionStatus : null,
            'current_start' => $subscriptionEntity['current_start'] ?? null,
            'current_end' => $subscriptionEntity['current_end'] ?? null,
            'charge_at' => $subscriptionEntity['charge_at'] ?? null,
            'start_at' => $subscriptionEntity['start_at'] ?? null,
            'end_at' => $subscriptionEntity['end_at'] ?? null,
            'total_count' => $subscriptionEntity['total_count'] ?? null,
            'paid_count' => $subscriptionEntity['paid_count'] ?? null,
            'remaining_count' => $subscriptionEntity['remaining_count'] ?? null,
            'quantity' => $subscriptionEntity['quantity'] ?? null,
            'payment_id' => $paymentId,
            'order_id' => $paymentEntity['order_id'] ?? null,
            'invoice_id' => $paymentEntity['invoice_id'] ?? null,
            'gateway_status' => $paymentStatus !== '' ? $paymentStatus : $subscriptionStatus,
            'currency' => $paymentEntity['currency'] ?? 'INR',
            'user_id' => $notes['user_id'] ?? $notes['patient_id'] ?? null,
            'pet_id' => $notes['pet_id'] ?? null,
            'clinic_id' => $notes['clinic_id'] ?? null,
            'doctor_id' => $notes['doctor_id'] ?? null,
            'channel_name' => $notes['channel_name'] ?? null,
            'subscription_notes' => $notes ?: null,
        ], static fn ($value) => $value !== null && $value !== '');

        $payload = [
            'clinic_id' => $this->nullableInt($notes['clinic_id'] ?? null),
            'doctor_id' => $this->nullableInt($notes['doctor_id'] ?? null),
            'user_id' => $this->nullableInt($notes['user_id'] ?? $notes['patient_id'] ?? null),
            'pet_id' => $this->nullableInt($notes['pet_id'] ?? null),
            'amount_paise' => $amountPaise,
            'status' => $this->normalizeTransactionStatus($paymentStatus !== '' ? $paymentStatus : $subscriptionStatus),
            'type' => 'monthly_subscription',
            'payment_method' => $paymentEntity['method'] ?? 'razorpay_subscription',
            'reference' => $paymentId,
            'metadata' => $metadata,
        ];

        if (Schema::hasColumn('transactions', 'channel_name')) {
            $channelName = trim((string) ($notes['channel_name'] ?? ''));
            if ($channelName !== '') {
                $payload['channel_name'] = $channelName;
            }
        }

        if (Schema::hasColumn('transactions', 'actual_amount_paid_by_consumer_paise')) {
            $payload['actual_amount_paid_by_consumer_paise'] = $amountPaise;
        }

        return Transaction::updateOrCreate(
            ['reference' => $paymentId],
            $payload
        );
    }

    private function upsertMonthlySubscription(
        string $eventName,
        array $subscriptionEntity,
        ?array $paymentEntity,
        ?Transaction $transaction
    ): ?UserMonthlySubscription {
        if (! Schema::hasTable('user_monthly_subscriptions')) {
            return null;
        }

        $notes = is_array($subscriptionEntity['notes'] ?? null) ? $subscriptionEntity['notes'] : [];
        $userId = $this->nullableInt($notes['user_id'] ?? $notes['patient_id'] ?? null);
        if (! $userId) {
            return null;
        }

        $subscription = UserMonthlySubscription::query()->firstOrNew(['user_id' => $userId]);
        $paymentId = trim((string) (($paymentEntity['id'] ?? null) ?: ''));
        $subscriptionId = trim((string) (($subscriptionEntity['id'] ?? null) ?: ''));
        $amountPaise = max(0, (int) (($paymentEntity['amount'] ?? null) ?: ($subscription->amount_paise ?? 0)));
        $currentStart = $this->timestampFromUnix($subscriptionEntity['current_start'] ?? null);
        $currentEnd = $this->timestampFromUnix($subscriptionEntity['current_end'] ?? null);
        $status = $this->normalizeSubscriptionStatus($subscriptionEntity['status'] ?? $eventName);

        $metadata = is_array($subscription->metadata) ? $subscription->metadata : [];
        $metadata['order_type'] = 'monthly_subscription';
        $metadata['subscription_id'] = $subscriptionId !== '' ? $subscriptionId : ($metadata['subscription_id'] ?? null);
        $metadata['plan_id'] = $subscriptionEntity['plan_id'] ?? ($metadata['plan_id'] ?? null);
        $metadata['customer_id'] = $subscriptionEntity['customer_id'] ?? ($metadata['customer_id'] ?? null);
        $metadata['subscription_selected'] = 1;
        $metadata['last_webhook'] = array_filter([
            'event' => $eventName,
            'subscription_status' => $subscriptionEntity['status'] ?? null,
            'payment_status' => $paymentEntity['status'] ?? null,
            'payment_id' => $paymentId !== '' ? $paymentId : null,
            'processed_at' => now()->toIso8601String(),
        ], static fn ($value) => $value !== null && $value !== '');

        $subscription->user_id = $userId;
        $subscription->transaction_id = $transaction?->id ?? $subscription->transaction_id;
        $subscription->order_reference = $subscriptionId !== '' ? $subscriptionId : $subscription->order_reference;
        $subscription->payment_reference = $paymentId !== '' ? $paymentId : $subscription->payment_reference;
        $subscription->status = $status;
        $subscription->amount_paise = $amountPaise;
        $subscription->metadata = $metadata;

        if ($currentStart) {
            $subscription->starts_at = $currentStart;
        }
        if ($currentEnd) {
            $subscription->expires_at = $currentEnd;
        }
        if ($status === 'active' && ! $subscription->activated_at) {
            $subscription->activated_at = $currentStart ?? $this->timestampFromUnix($paymentEntity['created_at'] ?? null) ?? now();
        }

        $subscription->save();

        return $subscription->fresh();
    }

    private function normalizeTransactionStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'paid', 'captured', 'successful', 'success', 'settled' => 'captured',
            'authorized' => 'authorized',
            'failed' => 'failed',
            'refunded' => 'refunded',
            'pending', 'created', 'initiated' => 'pending',
            default => $normalized !== '' ? $normalized : 'pending',
        };
    }

    private function normalizeSubscriptionStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'charged', 'activated', 'resumed' => 'active',
            default => $normalized !== '' ? $normalized : 'pending',
        };
    }

    private function timestampFromUnix($value): ?Carbon
    {
        if (! is_numeric($value)) {
            return null;
        }

        try {
            return Carbon::createFromTimestampUTC((int) $value);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
