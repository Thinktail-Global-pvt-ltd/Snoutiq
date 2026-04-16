<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RazorpayPaymentLink;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RazorpayPaymentLinkWebhookController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

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
            $shouldSendPaymentAlerts = $this->shouldSendPaymentConfirmedAlerts($paymentLink, $payment, $transaction);

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
                'send_parent_payment_alert' => $shouldSendPaymentAlerts,
                'send_vet_payment_alert' => $shouldSendPaymentAlerts,
            ];
        });

        if (!($result['duplicate'] ?? false) && ($result['send_parent_payment_alert'] ?? false)) {
            $result['parent_payment_alert'] = $this->sendParentPaymentConfirmedAlert($result['payment_link_id'] ?? null);
        }

        if (!($result['duplicate'] ?? false) && ($result['send_vet_payment_alert'] ?? false)) {
            $result['vet_payment_alert'] = $this->sendVetPaymentConfirmedAlert($result['payment_link_id'] ?? null);
        }

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
        $hasChannelNameColumn = Schema::hasColumn('transactions', 'channel_name');
        $existingTransaction = $hasChannelNameColumn
            ? Transaction::query()
                ->select('id', 'channel_name')
                ->where('reference', $paymentLink->payment_link_id)
                ->first()
            : null;
        $channelName = trim((string) ($existingTransaction?->channel_name ?? ''));
        if ($hasChannelNameColumn && $transactionStatus === 'captured') {
            $channelName = $this->resolveTransactionChannelName($channelName);
        }

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

        if ($hasChannelNameColumn && $channelName !== '') {
            $payloadForTransaction['channel_name'] = $channelName;
            $payloadForTransaction['metadata']['channel_name'] = $channelName;
        }

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

    private function resolveTransactionChannelName(?string $currentValue): string
    {
        $currentValue = trim((string) $currentValue);
        if ($currentValue !== '') {
            return $currentValue;
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $channelName = 'cf_'.strtolower(Str::random(16));
            if (!Transaction::query()->where('channel_name', $channelName)->exists()) {
                return $channelName;
            }
        }

        return 'cf_'.strtolower(Str::random(24));
    }

    private function shouldSendPaymentConfirmedAlerts(?RazorpayPaymentLink $paymentLink, ?Payment $payment, ?Transaction $transaction): bool
    {
        if (!$paymentLink) {
            return false;
        }

        return $this->isPaidStatus($paymentLink->status)
            || $this->isPaidStatus($paymentLink->payment_status)
            || $this->isPaidStatus($payment?->status)
            || $this->isPaidStatus($transaction?->status);
    }

    private function sendParentPaymentConfirmedAlert(?string $paymentLinkId): array
    {
        $paymentLinkId = trim((string) $paymentLinkId);
        if ($paymentLinkId === '') {
            return ['sent' => false, 'skipped' => true, 'reason' => 'missing_payment_link_id'];
        }

        $paymentLink = RazorpayPaymentLink::where('payment_link_id', $paymentLinkId)->first();
        if (!$paymentLink) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'payment_link_not_found'];
        }

        if ($this->hasSentParentPaymentAlert($paymentLink)) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'already_sent'];
        }

        $parent = $this->resolvePetParent($paymentLink);
        $parentPhone = $this->normalizeWhatsAppPhone($parent['phone'] ?? null);
        if (!$parentPhone) {
            $this->logParentPaymentAlert($paymentLink, 'skipped', null, null, null, 'missing_parent_phone');
            return ['sent' => false, 'skipped' => true, 'reason' => 'missing_parent_phone'];
        }

        if (!$this->whatsApp->isConfigured()) {
            $this->logParentPaymentAlert($paymentLink, 'skipped', $parentPhone, null, null, 'whatsapp_not_configured');
            return ['sent' => false, 'skipped' => true, 'reason' => 'whatsapp_not_configured'];
        }

        $doctorName = $this->resolveDoctorName($paymentLink);
        $parentName = $this->cleanText($parent['name'] ?? null) ?: $this->formatIndianPhoneForTemplate($parent['phone'] ?? null) ?: 'Pet Parent';
        $amount = $this->formatRupeesForTemplate($this->resolvePaidAmountPaise($paymentLink));
        $responseTime = $this->resolveResponseTimeMinutes($paymentLink);
        $template = config('services.whatsapp.templates.cf_payment_confirmed_parent', 'cf_payment_confirmed_parent');
        $language = config('services.whatsapp.templates.cf_payment_confirmed_parent_language', 'en');

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $amount],
                    ['type' => 'text', 'text' => $responseTime],
                ],
            ],
        ];

        try {
            $response = $this->whatsApp->sendTemplateWithResult(
                to: $parentPhone,
                template: $template,
                components: $components,
                language: $language,
                channelName: 'payment_confirmed_parent_alert'
            );

            $this->logParentPaymentAlert($paymentLink, 'sent', $parentPhone, $template, $language, null, [
                'parent_name' => $parentName,
                'amount' => $amount,
                'response_time_minutes' => $responseTime,
                'whatsapp_response' => $response,
            ]);

            return [
                'sent' => true,
                'template' => $template,
                'to' => $parentPhone,
                'doctor_name' => $doctorName,
                'parent_name' => $parentName,
                'amount' => $amount,
                'response_time_minutes' => $responseTime,
            ];
        } catch (\Throwable $e) {
            $this->logParentPaymentAlert($paymentLink, 'failed', $parentPhone, $template, $language, $e->getMessage(), [
                'parent_name' => $parentName,
                'amount' => $amount,
                'response_time_minutes' => $responseTime,
            ]);

            Log::warning('razorpay.payment_link_webhook.parent_payment_alert_failed', [
                'payment_link_id' => $paymentLink->payment_link_id,
                'parent_phone' => $parentPhone,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'skipped' => false,
                'reason' => $e->getMessage(),
                'template' => $template,
                'to' => $parentPhone,
            ];
        }
    }

    private function sendVetPaymentConfirmedAlert(?string $paymentLinkId): array
    {
        $paymentLinkId = trim((string) $paymentLinkId);
        if ($paymentLinkId === '') {
            return ['sent' => false, 'skipped' => true, 'reason' => 'missing_payment_link_id'];
        }

        $paymentLink = RazorpayPaymentLink::where('payment_link_id', $paymentLinkId)->first();
        if (!$paymentLink) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'payment_link_not_found'];
        }

        if ($this->hasSentVetPaymentAlert($paymentLink)) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'already_sent'];
        }

        if (!$this->whatsApp->isConfigured()) {
            $this->logVetPaymentAlert($paymentLink, 'skipped', null, null, null, 'whatsapp_not_configured');
            return ['sent' => false, 'skipped' => true, 'reason' => 'whatsapp_not_configured'];
        }

        $vet = $this->resolveVetRecipient($paymentLink);
        $vetPhone = $this->normalizeWhatsAppPhone($vet['phone'] ?? null);
        if (!$vetPhone) {
            $this->logVetPaymentAlert($paymentLink, 'skipped', null, null, null, 'missing_vet_phone');
            return ['sent' => false, 'skipped' => true, 'reason' => 'missing_vet_phone'];
        }

        $parent = $this->resolvePetParent($paymentLink);
        $parentPhone = $this->formatIndianPhoneForTemplate($parent['phone'] ?? null);
        $parentName = $this->cleanText($parent['name'] ?? null) ?: ($parentPhone ?: 'Pet Parent');
        $amount = $this->formatRupeesForTemplate($this->resolvePaidAmountPaise($paymentLink));
        $template = config('services.whatsapp.templates.cf_payment_confirmed_vet', 'cf_payment_confirmed_vet');
        $language = config('services.whatsapp.templates.cf_payment_confirmed_vet_language', 'en');

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $amount],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $parentPhone ?: ''],
                ],
            ],
        ];

        try {
            $response = $this->whatsApp->sendTemplateWithResult(
                to: $vetPhone,
                template: $template,
                components: $components,
                language: $language,
                channelName: 'payment_confirmed_vet_alert'
            );

            $this->logVetPaymentAlert($paymentLink, 'sent', $vetPhone, $template, $language, null, [
                'vet_doctor_id' => $vet['doctor_id'] ?? null,
                'vet_phone' => $vetPhone,
                'parent_phone' => $parentPhone,
                'amount' => $amount,
                'whatsapp_response' => $response,
            ]);

            return [
                'sent' => true,
                'template' => $template,
                'to' => $vetPhone,
                'amount' => $amount,
                'parent_name' => $parentName,
                'parent_phone' => $parentPhone,
            ];
        } catch (\Throwable $e) {
            $this->logVetPaymentAlert($paymentLink, 'failed', $vetPhone, $template, $language, $e->getMessage(), [
                'vet_doctor_id' => $vet['doctor_id'] ?? null,
                'parent_phone' => $parentPhone,
                'amount' => $amount,
            ]);

            Log::warning('razorpay.payment_link_webhook.vet_payment_alert_failed', [
                'payment_link_id' => $paymentLink->payment_link_id,
                'vet_phone' => $vetPhone,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'skipped' => false,
                'reason' => $e->getMessage(),
                'template' => $template,
                'to' => $vetPhone,
            ];
        }
    }

    private function hasSentVetPaymentAlert(RazorpayPaymentLink $paymentLink): bool
    {
        if (!Schema::hasTable('vet_response_reminder_logs')) {
            return false;
        }

        return DB::table('vet_response_reminder_logs')
            ->whereJsonContains('meta->type', 'cf_payment_confirmed_vet')
            ->whereJsonContains('meta->payment_link_id', $paymentLink->payment_link_id)
            ->where('status', 'sent')
            ->exists();
    }

    private function hasSentParentPaymentAlert(RazorpayPaymentLink $paymentLink): bool
    {
        if (!Schema::hasTable('vet_response_reminder_logs')) {
            return false;
        }

        return DB::table('vet_response_reminder_logs')
            ->whereJsonContains('meta->type', 'cf_payment_confirmed_parent')
            ->whereJsonContains('meta->payment_link_id', $paymentLink->payment_link_id)
            ->where('status', 'sent')
            ->exists();
    }

    private function resolveVetRecipient(RazorpayPaymentLink $paymentLink): array
    {
        $doctor = null;

        if ($paymentLink->doctor_id) {
            $doctor = DB::table('doctors')
                ->select(['id', 'doctor_name', 'doctor_mobile'])
                ->where('id', $paymentLink->doctor_id)
                ->first();
        }

        if ((!$doctor || empty($doctor->doctor_mobile)) && $paymentLink->clinic_id) {
            $doctor = DB::table('doctors')
                ->select(['id', 'doctor_name', 'doctor_mobile'])
                ->where('vet_registeration_id', $paymentLink->clinic_id)
                ->whereNotNull('doctor_mobile')
                ->where('doctor_mobile', '!=', '')
                ->orderBy('id')
                ->first();
        }

        $clinicPhone = null;
        if ($paymentLink->clinic_id) {
            $clinicPhone = DB::table('vet_registerations_temp')
                ->where('id', $paymentLink->clinic_id)
                ->value('mobile');
        }

        return [
            'doctor_id' => $doctor->id ?? $paymentLink->doctor_id,
            'name' => $doctor->doctor_name ?? null,
            'phone' => $doctor->doctor_mobile ?? $clinicPhone,
        ];
    }

    private function resolvePetParent(RazorpayPaymentLink $paymentLink): array
    {
        $user = $paymentLink->user_id
            ? DB::table('users')->select(['name', 'phone'])->where('id', $paymentLink->user_id)->first()
            : null;

        return [
            'name' => $user->name
                ?? data_get($paymentLink->webhook_payload, 'payload.payment.entity.name')
                ?? data_get($paymentLink->webhook_payload, 'payload.payment_link.entity.customer.name'),
            'phone' => $user->phone
                ?? data_get($paymentLink->webhook_payload, 'payload.payment.entity.contact')
                ?? data_get($paymentLink->webhook_payload, 'payload.payment_link.entity.customer.contact'),
        ];
    }

    private function resolveDoctorName(RazorpayPaymentLink $paymentLink): string
    {
        $doctorName = null;

        if ($paymentLink->doctor_id) {
            $doctorName = DB::table('doctors')
                ->where('id', $paymentLink->doctor_id)
                ->value('doctor_name');
        }

        if (!$doctorName && $paymentLink->clinic_id) {
            $doctorName = DB::table('doctors')
                ->where('vet_registeration_id', $paymentLink->clinic_id)
                ->orderBy('id')
                ->value('doctor_name');
        }

        return $this->cleanDoctorName($doctorName);
    }

    private function resolvePaidAmountPaise(RazorpayPaymentLink $paymentLink): int
    {
        return (int) (
            data_get($paymentLink->webhook_payload, 'payload.payment.entity.amount')
            ?: data_get($paymentLink->webhook_payload, 'payload.payment_link.entity.amount_paid')
            ?: $paymentLink->amount_paise
            ?: 0
        );
    }

    private function resolveResponseTimeMinutes(RazorpayPaymentLink $paymentLink): string
    {
        $rawValue = data_get($paymentLink->webhook_payload, 'payload.payment_link.entity.notes.response_time_minutes')
            ?: data_get($paymentLink->webhook_payload, 'payload.payment_link.entity.notes.response_time')
            ?: data_get($paymentLink->raw_response, 'notes.response_time_minutes')
            ?: data_get($paymentLink->raw_response, 'notes.response_time')
            ?: 10;

        return (string) max(1, (int) $rawValue);
    }

    private function normalizeWhatsAppPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }

        return strlen($digits) >= 11 ? $digits : null;
    }

    private function formatIndianPhoneForTemplate(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        if (strlen($digits) >= 12 && str_starts_with($digits, '91')) {
            return substr($digits, -10);
        }

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    private function formatRupeesForTemplate(int $amountPaise): string
    {
        $formatted = number_format(max(0, $amountPaise) / 100, 2, '.', '');

        return str_ends_with($formatted, '.00')
            ? substr($formatted, 0, -3)
            : rtrim(rtrim($formatted, '0'), '.');
    }

    private function cleanText(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?: '');
    }

    private function cleanDoctorName(?string $name): string
    {
        $clean = $this->cleanText($name);
        $clean = preg_replace('/^dr\.?\s+/i', '', $clean) ?: $clean;

        return trim($clean) !== '' ? trim($clean) : 'Snoutiq';
    }

    private function logParentPaymentAlert(
        RazorpayPaymentLink $paymentLink,
        string $status,
        ?string $phone,
        ?string $template,
        ?string $language,
        ?string $error,
        array $extraMeta = []
    ): void {
        if (!Schema::hasTable('vet_response_reminder_logs')) {
            return;
        }

        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => $paymentLink->user_id,
                'pet_id' => $paymentLink->pet_id,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode(array_merge([
                    'type' => 'cf_payment_confirmed_parent',
                    'payment_link_id' => $paymentLink->payment_link_id,
                    'razorpay_payment_link_row_id' => $paymentLink->id,
                    'payment_id' => $paymentLink->payment_id,
                    'clinic_id' => $paymentLink->clinic_id,
                    'doctor_id' => $paymentLink->doctor_id,
                ], $extraMeta)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('razorpay.payment_link_webhook.parent_payment_alert_log_failed', [
                'payment_link_id' => $paymentLink->payment_link_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logVetPaymentAlert(
        RazorpayPaymentLink $paymentLink,
        string $status,
        ?string $phone,
        ?string $template,
        ?string $language,
        ?string $error,
        array $extraMeta = []
    ): void {
        if (!Schema::hasTable('vet_response_reminder_logs')) {
            return;
        }

        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => $paymentLink->user_id,
                'pet_id' => $paymentLink->pet_id,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode(array_merge([
                    'type' => 'cf_payment_confirmed_vet',
                    'payment_link_id' => $paymentLink->payment_link_id,
                    'razorpay_payment_link_row_id' => $paymentLink->id,
                    'payment_id' => $paymentLink->payment_id,
                    'clinic_id' => $paymentLink->clinic_id,
                    'doctor_id' => $paymentLink->doctor_id,
                ], $extraMeta)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('razorpay.payment_link_webhook.vet_payment_alert_log_failed', [
                'payment_link_id' => $paymentLink->payment_link_id,
                'error' => $e->getMessage(),
            ]);
        }
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
