<?php

namespace App\Services;

use App\Models\ConsultationShareSession;
use App\Models\Doctor;
use App\Models\RazorpayPaymentLink;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CallSessionUrlBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConsultationShareSessionService
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    public function createForPatient(User $user, ?object $pet, array $data, ?int $clinicId): ConsultationShareSession
    {
        $this->ensureSessionsTableExists();

        $session = ConsultationShareSession::create([
            'session_token' => $this->generateSessionToken(),
            'clinic_id' => $clinicId,
            'doctor_id' => !empty($data['doctor_id']) ? (int) $data['doctor_id'] : null,
            'user_id' => $user->id,
            'pet_id' => data_get($pet, 'id'),
            'parent_name' => $this->normalizeText($data['name'] ?? $user->name ?? null),
            'parent_phone' => $this->normalizeWhatsAppPhone((string) ($user->phone ?? $data['phone'] ?? '')),
            'pet_name' => $this->normalizeText($data['pet_name'] ?? data_get($pet, 'name')),
            'pet_type' => $this->normalizeText($data['pet_type'] ?? data_get($pet, 'type') ?? data_get($pet, 'pet_type')),
            'pet_breed' => $this->normalizeText($data['pet_breed'] ?? data_get($pet, 'breed')),
            'amount_paise' => $this->resolveConsultationAmountPaise($data),
            'response_time_minutes' => max(1, (int) ($data['response_time_minutes'] ?? 10)),
            'status' => 'pending',
            'meta' => [
                'user_email' => $this->normalizeText($user->email),
                'pet_gender' => $this->normalizeText($data['pet_gender'] ?? data_get($pet, 'gender') ?? data_get($pet, 'pet_gender')),
                'pet_age' => $data['pet_age'] ?? null,
                'weight' => $data['weight'] ?? null,
            ],
        ]);

        return $session;
    }

    public function findByToken(?string $token): ?ConsultationShareSession
    {
        $normalized = $this->normalizeSessionToken($token);
        if ($normalized === null || !Schema::hasTable('consultation_share_sessions')) {
            return null;
        }

        return ConsultationShareSession::query()->where('session_token', $normalized)->first();
    }

    public function formatForResponse(ConsultationShareSession $session): array
    {
        $landingUrl = $this->buildLandingUrl($session);
        $shareMessage = $this->buildVetShareMessage($session, $landingUrl);

        return [
            'id' => $session->id,
            'session_id' => $session->session_token,
            'status' => $session->status,
            'parent_phone' => $session->parent_phone,
            'amount_paise' => $session->amount_paise,
            'amount_rupees' => $this->formatRupeesForTemplate($session->amount_paise),
            'response_time_minutes' => $session->response_time_minutes,
            'landing_url' => $landingUrl,
            'share_message' => $shareMessage,
            'share_whatsapp_url' => $this->buildVetShareWhatsAppUrl($session, $shareMessage),
            'payment_link_sent' => $session->payment_link_sent_at !== null,
            'payment_link_url' => $session->razorpay_payment_link_url,
            'initiated_at' => optional($session->initiated_at)->toIso8601String(),
            'payment_link_sent_at' => optional($session->payment_link_sent_at)->toIso8601String(),
            'paid_at' => optional($session->paid_at)->toIso8601String(),
            'created_at' => optional($session->created_at)->toIso8601String(),
        ];
    }

    public function buildParentInitiationWhatsAppUrl(ConsultationShareSession $session): ?string
    {
        $businessPhone = $this->normalizedBusinessPhone();
        if ($businessPhone === null) {
            return null;
        }

        $message = sprintf(
            'Hi, I want to start consultation. Session: %s',
            $session->session_token
        );

        return sprintf(
            'https://wa.me/%s?text=%s',
            $businessPhone,
            rawurlencode($message)
        );
    }

    public function normalizedBusinessPhone(): ?string
    {
        $normalized = $this->normalizeWhatsAppPhone(
            (string) config('whatsapp.business_phone_number', '')
        );

        return $normalized !== '' ? $normalized : null;
    }

    public function handleInboundMessage(array $message, ?array $payload = null): ?ConsultationShareSession
    {
        $session = $this->resolveSessionFromInboundMessage($message);
        if (!$session) {
            return null;
        }

        $session->last_inbound_message_at = now();
        if ($session->status === 'pending') {
            $session->status = 'initiated';
            $session->initiated_at ??= now();
        }
        $session->save();

        if ($session->status === 'paid') {
            return $session->fresh();
        }

        if ($session->payment_link_sent_at !== null) {
            return $session->fresh();
        }

        try {
            $paymentPayload = $this->sendPaymentLinkForSession($session);
            $meta = $session->meta ?? [];
            $meta['payment_link_whatsapp'] = $paymentPayload;
            $meta['last_error'] = null;

            $session->status = 'initiated';
            $session->payment_link_sent_at = now();
            $session->razorpay_payment_link_id = $paymentPayload['payment_link_id'] ?? $session->razorpay_payment_link_id;
            $session->razorpay_payment_link_url = $paymentPayload['payment_link'] ?? $session->razorpay_payment_link_url;
            $session->razorpay_short_code = $paymentPayload['payment_link_slug'] ?? $session->razorpay_short_code;
            $session->meta = $meta;
            $session->save();
        } catch (\Throwable $e) {
            Log::warning('consultation_share_session.inbound_payment_link_failed', [
                'session_token' => $session->session_token,
                'user_id' => $session->user_id,
                'doctor_id' => $session->doctor_id,
                'error' => $e->getMessage(),
            ]);

            $meta = $session->meta ?? [];
            $meta['last_error'] = $e->getMessage();
            $session->meta = $meta;
            $session->save();
        }

        return $session->fresh();
    }

    public function markPaidFromPaymentLinkId(?string $paymentLinkId): ?ConsultationShareSession
    {
        $paymentLinkId = trim((string) $paymentLinkId);
        if ($paymentLinkId === '' || !Schema::hasTable('consultation_share_sessions')) {
            return null;
        }

        $session = ConsultationShareSession::query()
            ->where('razorpay_payment_link_id', $paymentLinkId)
            ->first();

        if (!$session) {
            return null;
        }

        $session->status = 'paid';
        $session->paid_at ??= now();
        $session->save();

        return $session->fresh();
    }

    public function buildLandingUrl(ConsultationShareSession $session): string
    {
        $base = rtrim(CallSessionUrlBuilder::frontendBase(), '/');
        if ($base === '') {
            $base = rtrim(url('/'), '/');
        }

        if (str_ends_with($base, '/backend')) {
            $base = substr($base, 0, -strlen('/backend'));
        }

        return $base . '/c/' . rawurlencode($session->session_token);
    }

    private function resolveSessionFromInboundMessage(array $message): ?ConsultationShareSession
    {
        if (!Schema::hasTable('consultation_share_sessions')) {
            return null;
        }

        $phone = $this->normalizeWhatsAppPhone((string) ($message['from'] ?? ''));
        $body = $this->extractInboundMessageText($message);
        $sessionToken = $this->extractSessionToken($body);

        $query = ConsultationShareSession::query()
            ->whereIn('status', ['pending', 'initiated']);

        if ($sessionToken !== null) {
            $query->where('session_token', $sessionToken);
        }

        if ($phone !== '') {
            $query->where('parent_phone', $phone);
        }

        return $query->orderByDesc('id')->first();
    }

    private function sendPaymentLinkForSession(ConsultationShareSession $session): array
    {
        $user = User::query()->find($session->user_id);
        if (!$user) {
            throw new \RuntimeException('Consult session user not found');
        }

        $amountPaise = max(100, (int) $session->amount_paise);
        $paymentLink = $this->ensurePaymentLink($session, $user, $amountPaise);
        $shortUrl = trim((string) ($paymentLink['short_url'] ?? $session->razorpay_payment_link_url ?? ''));
        $shortCode = trim((string) ($paymentLink['short_code'] ?? $session->razorpay_short_code ?? $this->extractRazorpayShortCode($shortUrl)));

        if ($shortCode === '') {
            throw new \RuntimeException('Razorpay payment link did not return a usable short URL');
        }

        $doctorName = $this->resolveTemplateDoctorName($session->doctor_id, $session->clinic_id);
        $parentName = $this->normalizeText($session->parent_name);
        $petName = $this->normalizeText($session->pet_name);
        $petBreed = $this->normalizeText($session->pet_breed);
        $useFullTemplate = $parentName !== '' && $petName !== '' && $petBreed !== '';
        $responseTime = (string) max(1, (int) ($session->response_time_minutes ?? 10));
        $amountRupees = $this->formatRupeesForTemplate($amountPaise);
        $to = $session->parent_phone;

        if ($useFullTemplate) {
            $template = config('services.whatsapp.templates.cf_payment_link_full', 'cf_payment_link_full');
            $language = config('services.whatsapp.templates.cf_payment_link_full_language', 'en');
            $bodyParameters = [
                ['type' => 'text', 'text' => $doctorName],
                ['type' => 'text', 'text' => $parentName],
                ['type' => 'text', 'text' => $petName],
                ['type' => 'text', 'text' => $responseTime],
                ['type' => 'text', 'text' => $amountRupees],
            ];
        } else {
            $template = config('services.whatsapp.templates.cf_payment_link_mini', 'cf_payment_link_mini');
            $language = config('services.whatsapp.templates.cf_payment_link_mini_language', 'en');
            $bodyParameters = [
                ['type' => 'text', 'text' => $doctorName],
                ['type' => 'text', 'text' => $responseTime],
                ['type' => 'text', 'text' => $amountRupees],
            ];
        }

        $components = [
            [
                'type' => 'body',
                'parameters' => $bodyParameters,
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $shortCode],
                ],
            ],
        ];

        $whatsAppResponse = $this->whatsApp->sendTemplateWithResult(
            to: $to,
            template: $template,
            components: $components,
            language: $language,
            channelName: 'consultation_share_session_payment_link'
        );

        return [
            'sent' => true,
            'template' => $template,
            'template_variant' => $useFullTemplate ? 'full' : 'mini',
            'to' => $to,
            'amount' => $amountRupees,
            'amount_paise' => $amountPaise,
            'payment_link' => $shortUrl,
            'payment_link_slug' => $shortCode,
            'book_now_url' => $shortCode !== '' ? 'https://rzp.io/rzp/' . $shortCode : null,
            'button_parameter_sent' => $shortCode,
            'payment_link_id' => $paymentLink['id'] ?? $session->razorpay_payment_link_id,
            'whatsapp' => $whatsAppResponse,
        ];
    }

    private function ensurePaymentLink(
        ConsultationShareSession $session,
        User $user,
        int $amountPaise
    ): array {
        if ($session->razorpay_payment_link_id && $session->razorpay_payment_link_url) {
            return [
                'id' => $session->razorpay_payment_link_id,
                'short_url' => $session->razorpay_payment_link_url,
                'short_code' => $session->razorpay_short_code,
            ];
        }

        return $this->createRazorpayPaymentLink($session, $user, $amountPaise);
    }

    private function createRazorpayPaymentLink(
        ConsultationShareSession $session,
        User $user,
        int $amountPaise
    ): array {
        $key = trim((string) (config('services.razorpay.key') ?? ''));
        $secret = trim((string) (config('services.razorpay.secret') ?? ''));

        if ($key === '' || $secret === '') {
            throw new \RuntimeException('Razorpay credentials missing');
        }

        $referenceId = 'SNOUTIQ_CONSULT_' . $user->id . '_' . Str::upper(Str::random(8));
        $payload = [
            'amount' => $amountPaise,
            'currency' => 'INR',
            'reference_id' => $referenceId,
            'description' => 'Snoutiq - Veterinary Consultation',
            'customer' => array_filter([
                'name' => $user->name,
                'contact' => $session->parent_phone ? '+' . $session->parent_phone : null,
                'email' => $user->email,
            ]),
            'notify' => [
                'sms' => true,
                'email' => !empty($user->email),
            ],
            'reminder_enable' => true,
            'notes' => array_filter([
                'service' => 'Veterinary Consultation',
                'source' => 'receptionist_patients',
                'clinic_id' => $session->clinic_id,
                'patient_id' => $user->id,
                'pet_id' => $session->pet_id,
                'doctor_id' => $session->doctor_id,
                'pet_name' => $session->pet_name,
                'pet_breed' => $session->pet_breed,
                'consult_session_token' => $session->session_token,
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        $response = Http::withBasicAuth($key, $secret)
            ->acceptJson()
            ->asJson()
            ->post('https://api.razorpay.com/v1/payment_links', $payload);

        $body = $response->json();
        if (!$response->successful()) {
            $message = data_get($body, 'error.description')
                ?? data_get($body, 'error.reason')
                ?? $response->body()
                ?? 'Unable to create Razorpay payment link';
            throw new \RuntimeException('Razorpay payment link failed: ' . $message);
        }

        $paymentLink = is_array($body) ? $body : [];
        $shortUrl = trim((string) ($paymentLink['short_url'] ?? ''));
        $paymentLinkId = trim((string) ($paymentLink['id'] ?? ''));

        if ($paymentLinkId !== '') {
            $this->storeRazorpayPaymentLink(
                session: $session,
                user: $user,
                paymentLink: $paymentLink,
                shortUrl: $shortUrl,
                referenceId: (string) ($paymentLink['reference_id'] ?? $referenceId),
                amountPaise: $amountPaise
            );
        }

        return $paymentLink;
    }

    private function storeRazorpayPaymentLink(
        ConsultationShareSession $session,
        User $user,
        array $paymentLink,
        string $shortUrl,
        string $referenceId,
        int $amountPaise
    ): void {
        $paymentLinkId = trim((string) ($paymentLink['id'] ?? ''));
        if ($paymentLinkId === '') {
            return;
        }

        $shortCode = $this->extractRazorpayShortCode($shortUrl);

        if (Schema::hasTable('razorpay_payment_links')) {
            RazorpayPaymentLink::updateOrCreate(
                ['payment_link_id' => $paymentLinkId],
                [
                    'short_url' => $shortUrl ?: null,
                    'short_code' => $shortCode ?: null,
                    'reference_id' => $paymentLink['reference_id'] ?? $referenceId,
                    'source' => 'receptionist_patients',
                    'user_id' => $user->id,
                    'pet_id' => $session->pet_id,
                    'clinic_id' => $session->clinic_id,
                    'doctor_id' => $session->doctor_id,
                    'amount_paise' => $amountPaise,
                    'currency' => $paymentLink['currency'] ?? 'INR',
                    'status' => $paymentLink['status'] ?? 'created',
                    'raw_response' => $paymentLink,
                ]
            );
        }

        if (Schema::hasTable('transactions')) {
            $payload = [
                'clinic_id' => $session->clinic_id,
                'doctor_id' => $session->doctor_id,
                'user_id' => $user->id,
                'pet_id' => $session->pet_id,
                'amount_paise' => $amountPaise,
                'status' => $this->normalizePaymentLinkTransactionStatus((string) ($paymentLink['status'] ?? 'created')),
                'type' => 'excell_export_campaign',
                'payment_method' => 'razorpay_payment_link',
                'reference' => $paymentLinkId,
                'metadata' => [
                    'order_type' => 'excell_export_campaign',
                    'payment_provider' => 'razorpay',
                    'payment_flow' => 'payment_link',
                    'payment_link_id' => $paymentLinkId,
                    'payment_link_url' => $shortUrl ?: null,
                    'reference_id' => $referenceId ?: null,
                    'source' => 'receptionist_patients',
                    'clinic_id' => $session->clinic_id,
                    'doctor_id' => $session->doctor_id,
                    'user_id' => $user->id,
                    'pet_id' => $session->pet_id,
                    'gateway_status' => $paymentLink['status'] ?? 'created',
                    'consult_session_token' => $session->session_token,
                ],
            ];

            Transaction::updateOrCreate(['reference' => $paymentLinkId], $payload);
        }

        $session->razorpay_payment_link_id = $paymentLinkId;
        $session->razorpay_payment_link_url = $shortUrl ?: null;
        $session->razorpay_short_code = $shortCode ?: null;
        $session->save();
    }

    private function buildVetShareMessage(ConsultationShareSession $session, string $landingUrl): string
    {
        $doctorName = $this->resolveTemplateDoctorName($session->doctor_id, $session->clinic_id);

        return trim(sprintf(
            "Hi%s, start your consultation with Dr %s here:\n%s\n\nOnce you send the WhatsApp message on that page, your payment link will be sent automatically.",
            $session->parent_name ? ' ' . $session->parent_name : '',
            $doctorName,
            $landingUrl
        ));
    }

    private function buildVetShareWhatsAppUrl(
        ConsultationShareSession $session,
        string $shareMessage
    ): ?string {
        if ($session->parent_phone === '') {
            return null;
        }

        return sprintf(
            'https://wa.me/%s?text=%s',
            $session->parent_phone,
            rawurlencode($shareMessage)
        );
    }

    private function generateSessionToken(): string
    {
        do {
            $token = 'cs_' . Str::lower(Str::random(18));
        } while (ConsultationShareSession::query()->where('session_token', $token)->exists());

        return $token;
    }

    private function extractInboundMessageText(array $message): string
    {
        $candidates = [
            data_get($message, 'text.body'),
            data_get($message, 'button.text'),
            data_get($message, 'interactive.button_reply.title'),
            data_get($message, 'interactive.list_reply.title'),
        ];

        foreach ($candidates as $candidate) {
            $text = trim((string) $candidate);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function extractSessionToken(?string $text): ?string
    {
        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        if (preg_match('/\b(cs_[A-Za-z0-9_-]{6,64})\b/', $text, $matches)) {
            return $this->normalizeSessionToken($matches[1]);
        }

        return null;
    }

    private function normalizeSessionToken(?string $token): ?string
    {
        if (!is_string($token)) {
            return null;
        }

        $normalized = preg_replace('/[^A-Za-z0-9_\-]/', '', $token);
        if (!is_string($normalized)) {
            return null;
        }

        $normalized = strtolower(substr($normalized, 0, 64));

        return $normalized !== '' ? $normalized : null;
    }

    private function ensureSessionsTableExists(): void
    {
        if (!Schema::hasTable('consultation_share_sessions')) {
            throw new \RuntimeException('consultation_share_sessions table is missing. Please run migrations.');
        }
    }

    private function resolveConsultationAmountPaise(array $data): int
    {
        if (!empty($data['amount_paise'])) {
            return max(100, (int) $data['amount_paise']);
        }

        $amountRupees = (float) ($data['amount'] ?? 499);

        return max(100, (int) round($amountRupees * 100));
    }

    private function resolveTemplateDoctorName(?int $doctorId, ?int $clinicId): string
    {
        $doctor = null;

        if ($doctorId) {
            $doctor = Doctor::query()
                ->when($clinicId, fn ($query) => $query->where('vet_registeration_id', $clinicId))
                ->find((int) $doctorId);
        }

        if (!$doctor && $clinicId) {
            $doctor = Doctor::query()
                ->where('vet_registeration_id', $clinicId)
                ->orderBy('id')
                ->first();
        }

        $name = trim((string) ($doctor?->doctor_name ?? 'Snoutiq'));
        $name = preg_replace('/^\s*dr\.?\s+/i', '', $name) ?: $name;

        return $name ?: 'Snoutiq';
    }

    private function normalizeWhatsAppPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (strlen($digits) === 10) {
            return '91' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91' . substr($digits, 1);
        }

        return $digits;
    }

    private function extractRazorpayShortCode(string $shortUrl): string
    {
        $path = parse_url($shortUrl, PHP_URL_PATH);
        if (!is_string($path) || trim($path, '/') === '') {
            return '';
        }

        return basename(trim($path, '/'));
    }

    private function formatRupeesForTemplate(int $amountPaise): string
    {
        $amount = $amountPaise / 100;

        return floor($amount) === $amount
            ? (string) (int) $amount
            : number_format($amount, 2, '.', '');
    }

    private function normalizePaymentLinkTransactionStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        if ($normalized === '') {
            return 'pending';
        }

        if (in_array($normalized, ['paid', 'captured', 'success', 'successful'], true)) {
            return 'captured';
        }

        if (in_array($normalized, ['created', 'issued'], true)) {
            return 'pending';
        }

        return $normalized;
    }

    private function normalizeText($value): string
    {
        return trim((string) $value);
    }
}
