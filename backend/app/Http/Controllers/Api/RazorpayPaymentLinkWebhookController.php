<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RazorpayPaymentLink;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RazorpayPaymentLinkWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = trim((string) $request->header('X-Razorpay-Signature', ''));
        $secret = trim((string) config('services.razorpay.webhook_secret', ''));

        if ($secret === '') {
            Log::error('razorpay.payment_link_webhook.missing_secret');

            return response()->json([
                'success' => false,
                'message' => 'Webhook secret is not configured.',
            ], 500);
        }

        if ($signature === '' || ! $this->isValidSignature($rawBody, $signature, $secret)) {
            Log::warning('razorpay.payment_link_webhook.invalid_signature', [
                'has_signature' => $signature !== '',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
            ], 400);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
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

        if (!Schema::hasTable('razorpay_payment_links')) {
            Log::error('razorpay.payment_link_webhook.missing_table');

            return response()->json([
                'success' => false,
                'message' => 'razorpay_payment_links table is missing.',
            ], 500);
        }

        $result = DB::transaction(function () use ($payload, $rawBody, $signature, $eventName) {
            $webhookEvent = $this->storeWebhookEvent($eventName, $signature, $rawBody);
            if ($webhookEvent->processed_at) {
                return [
                    'duplicate' => true,
                    'event' => $eventName,
                    'payment_link_id' => data_get($payload, 'payload.payment_link.entity.id'),
                ];
            }

            $paymentLink = $this->upsertPaymentLink($payload, $eventName);
            $payment = $this->upsertPayment($payload, $signature);
            $transaction = $this->upsertTransaction($payload, $paymentLink);

            $webhookEvent->processed_at = now();
            $webhookEvent->save();

            return [
                'duplicate' => false,
                'event' => $eventName,
                'payment_link_id' => $paymentLink?->payment_link_id,
                'payment_link_status' => $paymentLink?->status,
                'payment_id' => $payment?->razorpay_payment_id,
                'payment_status' => $payment?->status,
                'transaction_id' => $transaction?->id,
                'transaction_status' => $transaction?->status,
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

    private function storeWebhookEvent(string $eventName, string $signature, string $rawBody): WebhookEvent
    {
        return WebhookEvent::firstOrCreate(
            [
                'source' => 'razorpay_payment_link',
                'event' => $eventName,
                'signature' => $signature,
            ],
            [
                'payload' => $rawBody,
                'retries' => 0,
            ]
        );
    }

    private function upsertPaymentLink(array $payload, string $eventName): ?RazorpayPaymentLink
    {
        $entity = data_get($payload, 'payload.payment_link.entity');
        if (!is_array($entity)) {
            Log::info('razorpay.payment_link_webhook.ignored_missing_payment_link', [
                'event' => $eventName,
            ]);

            return null;
        }

        $paymentLinkId = trim((string) ($entity['id'] ?? ''));
        if ($paymentLinkId === '') {
            return null;
        }

        $paymentEntity = data_get($payload, 'payload.payment.entity');
        $orderEntity = data_get($payload, 'payload.order.entity');
        $notes = is_array($entity['notes'] ?? null) ? $entity['notes'] : [];
        $status = trim((string) ($entity['status'] ?? '')) ?: $this->statusFromEvent($eventName);
        $paymentId = is_array($paymentEntity) ? trim((string) ($paymentEntity['id'] ?? '')) : '';
        $orderId = is_array($orderEntity) ? trim((string) ($orderEntity['id'] ?? '')) : '';
        $paidAt = $this->isPaidStatus($status)
            ? $this->timestampFromUnix($paymentEntity['created_at'] ?? $entity['updated_at'] ?? null)
            : null;

        return RazorpayPaymentLink::updateOrCreate(
            ['payment_link_id' => $paymentLinkId],
            [
                'short_url' => $entity['short_url'] ?? null,
                'short_code' => $this->extractShortCode((string) ($entity['short_url'] ?? '')),
                'reference_id' => $entity['reference_id'] ?? null,
                'source' => $notes['source'] ?? 'razorpay_webhook',
                'user_id' => $this->nullableInt($notes['patient_id'] ?? null),
                'pet_id' => $this->nullableInt($notes['pet_id'] ?? null),
                'clinic_id' => $this->nullableInt($notes['clinic_id'] ?? null),
                'doctor_id' => $this->nullableInt($notes['doctor_id'] ?? null),
                'amount_paise' => $this->nullableInt($entity['amount'] ?? $entity['amount_paid'] ?? null),
                'currency' => $entity['currency'] ?? 'INR',
                'status' => $status ?: 'unknown',
                'payment_id' => $paymentId ?: null,
                'order_id' => $orderId ?: null,
                'payment_status' => is_array($paymentEntity) ? ($paymentEntity['status'] ?? null) : null,
                'paid_at' => $paidAt,
                'webhook_payload' => $payload,
            ]
        );
    }

    private function upsertPayment(array $payload, string $signature): ?Payment
    {
        if (!Schema::hasTable('payments')) {
            return null;
        }

        $paymentEntity = data_get($payload, 'payload.payment.entity');
        if (!is_array($paymentEntity)) {
            return null;
        }

        $paymentId = trim((string) ($paymentEntity['id'] ?? ''));
        if ($paymentId === '') {
            return null;
        }

        $orderEntity = data_get($payload, 'payload.order.entity');
        $paymentLinkEntity = data_get($payload, 'payload.payment_link.entity');
        $orderId = is_array($orderEntity) ? trim((string) ($orderEntity['id'] ?? '')) : '';
        $linkNotes = is_array($paymentLinkEntity) && is_array($paymentLinkEntity['notes'] ?? null)
            ? $paymentLinkEntity['notes']
            : [];

        return Payment::updateOrCreate(
            ['razorpay_payment_id' => $paymentId],
            [
                'razorpay_order_id' => $orderId ?: $paymentId,
                'razorpay_signature' => $signature,
                'amount' => $this->nullableInt($paymentEntity['amount'] ?? null),
                'currency' => $paymentEntity['currency'] ?? 'INR',
                'status' => $paymentEntity['status'] ?? null,
                'method' => $paymentEntity['method'] ?? null,
                'email' => $paymentEntity['email'] ?? null,
                'contact' => $paymentEntity['contact'] ?? null,
                'notes' => array_filter([
                    'source' => $linkNotes['source'] ?? 'razorpay_payment_link',
                    'payment_link_id' => is_array($paymentLinkEntity) ? ($paymentLinkEntity['id'] ?? null) : null,
                    'payment_link_status' => is_array($paymentLinkEntity) ? ($paymentLinkEntity['status'] ?? null) : null,
                    'reference_id' => is_array($paymentLinkEntity) ? ($paymentLinkEntity['reference_id'] ?? null) : null,
                    'patient_id' => $linkNotes['patient_id'] ?? null,
                    'pet_id' => $linkNotes['pet_id'] ?? null,
                    'clinic_id' => $linkNotes['clinic_id'] ?? null,
                    'doctor_id' => $linkNotes['doctor_id'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
                'raw_response' => $paymentEntity,
            ]
        );
    }

    private function upsertTransaction(array $payload, ?RazorpayPaymentLink $paymentLink): ?Transaction
    {
        if (!$paymentLink || !Schema::hasTable('transactions')) {
            return null;
        }

        $paymentEntity = data_get($payload, 'payload.payment.entity');
        $paymentLinkEntity = data_get($payload, 'payload.payment_link.entity');
        $orderEntity = data_get($payload, 'payload.order.entity');
        $paymentId = is_array($paymentEntity) ? trim((string) ($paymentEntity['id'] ?? '')) : '';
        $orderId = is_array($orderEntity) ? trim((string) ($orderEntity['id'] ?? '')) : '';
        $paymentStatus = is_array($paymentEntity) ? trim((string) ($paymentEntity['status'] ?? '')) : '';
        $linkStatus = trim((string) ($paymentLink->status ?? data_get($paymentLinkEntity, 'status') ?? ''));
        $amountPaise = (int) (
            $paymentLink->amount_paise
            ?: data_get($paymentLinkEntity, 'amount')
            ?: data_get($paymentEntity, 'amount')
            ?: 0
        );
        $transactionType = 'excell_export_campaign';
        $transactionStatus = $this->normalizeTransactionStatus($paymentStatus ?: $linkStatus);
        $metadata = [
            'order_type' => $transactionType,
            'payment_provider' => 'razorpay',
            'payment_flow' => 'payment_link',
            'payment_link_id' => $paymentLink->payment_link_id,
            'payment_link_url' => $paymentLink->short_url,
            'payment_link_status' => $linkStatus ?: null,
            'reference_id' => $paymentLink->reference_id,
            'payment_id' => $paymentId ?: null,
            'order_id' => $orderId ?: null,
            'gateway_status' => $paymentStatus ?: $linkStatus,
            'currency' => $paymentLink->currency ?: 'INR',
            'source' => $paymentLink->source ?: 'razorpay_webhook',
            'clinic_id' => $paymentLink->clinic_id,
            'doctor_id' => $paymentLink->doctor_id,
            'user_id' => $paymentLink->user_id,
            'pet_id' => $paymentLink->pet_id,
        ];

        $payloadForTransaction = [
            'clinic_id' => $paymentLink->clinic_id,
            'doctor_id' => $paymentLink->doctor_id,
            'user_id' => $paymentLink->user_id,
            'pet_id' => $paymentLink->pet_id,
            'amount_paise' => $amountPaise,
            'status' => $transactionStatus,
            'type' => $transactionType,
            'payment_method' => is_array($paymentEntity)
                ? ($paymentEntity['method'] ?? 'razorpay_payment_link')
                : 'razorpay_payment_link',
            'reference' => $paymentLink->payment_link_id,
            'metadata' => $metadata,
        ];

        $payoutBreakup = $this->buildExcelExportPayoutBreakup($amountPaise);
        $payloadForTransaction['metadata']['payout_breakup'] = $payoutBreakup;
        foreach ([
            'actual_amount_paid_by_consumer_paise',
            'payment_to_snoutiq_paise',
            'payment_to_doctor_paise',
        ] as $column) {
            if (Schema::hasColumn('transactions', $column)) {
                $payloadForTransaction[$column] = (int) $payoutBreakup[$column];
            }
        }

        return Transaction::updateOrCreate(
            ['reference' => $paymentLink->payment_link_id],
            $payloadForTransaction
        );
    }

    private function statusFromEvent(string $eventName): string
    {
        return match ($eventName) {
            'payment_link.paid' => 'paid',
            'payment_link.partially_paid' => 'partially_paid',
            'payment_link.cancelled' => 'cancelled',
            'payment_link.expired' => 'expired',
            default => 'received',
        };
    }

    private function normalizeTransactionStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));
        if ($normalized === '') {
            return 'pending';
        }

        if (in_array($normalized, ['captured', 'authorized', 'paid', 'success', 'verified'], true)) {
            return 'captured';
        }

        if (in_array($normalized, ['created', 'issued'], true)) {
            return 'pending';
        }

        return $normalized;
    }

    private function buildExcelExportPayoutBreakup(int $grossPaise): array
    {
        $grossPaise = max(0, $grossPaise);
        $amountBeforeGstPaise = (int) round($grossPaise / 1.18);
        $gstPaise = max(0, $grossPaise - $amountBeforeGstPaise);
        $doctorSharePaise = $this->resolveExcelDoctorSharePaise($amountBeforeGstPaise, $grossPaise);
        $snoutiqSharePaise = max(0, $amountBeforeGstPaise - $doctorSharePaise);

        return [
            'actual_amount_paid_by_consumer_paise' => $grossPaise,
            'gst_paise' => $gstPaise,
            'amount_after_gst_paise' => $amountBeforeGstPaise,
            'amount_before_gst_paise' => $amountBeforeGstPaise,
            'gst_deducted_from_amount' => true,
            'payment_to_snoutiq_paise' => $snoutiqSharePaise,
            'payment_to_doctor_paise' => $doctorSharePaise,
        ];
    }

    private function resolveExcelDoctorSharePaise(int $amountBeforeGstPaise, int $grossPaise): int
    {
        $amountBeforeGstPaise = max(0, $amountBeforeGstPaise);
        $grossPaise = max(0, $grossPaise);

        if (
            abs($amountBeforeGstPaise - 39900) <= 400
            || abs($grossPaise - 47100) <= 500
            || abs($grossPaise - 39900) <= 400
            || abs($amountBeforeGstPaise - 50000) <= 400
            || abs($grossPaise - 59000) <= 500
        ) {
            return min($amountBeforeGstPaise, 35000);
        }

        if (
            abs($amountBeforeGstPaise - 54900) <= 400
            || abs($grossPaise - 64800) <= 500
            || abs($grossPaise - 54900) <= 400
            || abs($amountBeforeGstPaise - 65000) <= 400
            || abs($grossPaise - 76700) <= 500
        ) {
            return min($amountBeforeGstPaise, 45000);
        }

        return min($amountBeforeGstPaise, 45000);
    }

    private function isPaidStatus(?string $status): bool
    {
        return in_array(strtolower((string) $status), ['paid', 'captured'], true);
    }

    private function timestampFromUnix($value): ?Carbon
    {
        if (!is_numeric($value)) {
            return now();
        }

        return Carbon::createFromTimestamp((int) $value);
    }

    private function nullableInt($value): ?int
    {
        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function extractShortCode(string $shortUrl): ?string
    {
        $path = parse_url($shortUrl, PHP_URL_PATH);
        if (!is_string($path) || trim($path, '/') === '') {
            return null;
        }

        return basename(trim($path, '/')) ?: null;
    }
}
