<?php

namespace App\Console\Commands;

use App\Models\Pet;
use App\Models\RazorpayPaymentLink;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendProfileCreatedPaymentLinkReminders extends Command
{
    private const LOG_TYPE = 'pp_profile_created_20m_payment_reminder';

    protected $signature = 'notifications:pp-profile-created-20m {--user_id=} {--dry}';
    protected $description = 'Send payment-link WhatsApp reminder 20 minutes after users.created_at.';

    public function __construct(private readonly WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();
        $forcedUserId = $this->option('user_id');
        $dryRun = (bool) $this->option('dry');

        $windowEnd = $now->copy()->subMinutes(20);
        $windowStart = $now->copy()->subMinutes(25);

        if (! Schema::hasTable('razorpay_payment_links')) {
            Log::warning('pp_profile_created_20m_payment_reminder.table_missing');
            return self::SUCCESS;
        }

        $users = User::query()
            ->select(['id', 'name', 'phone', 'last_vet_id', 'created_at'])
            ->when($forcedUserId, fn ($q) => $q->whereKey((int) $forcedUserId))
            ->when(! $forcedUserId, function ($q) use ($windowStart, $windowEnd) {
                $q->whereBetween('created_at', [$windowStart, $windowEnd])
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->whereDoesntHave('reminderLogs', function ($logs) {
                        $logs->whereJsonContains('meta->type', self::LOG_TYPE)
                            ->whereIn('status', ['sent', 'skipped']);
                    });
            })
            ->orderBy('id')
            ->limit(200)
            ->get();

        Log::info('pp_profile_created_20m_payment_reminder.run_start', [
            'candidates' => $users->count(),
            'forced_user_id' => $forcedUserId,
            'dry_run' => $dryRun,
            'window_start' => $windowStart->toDateTimeString(),
            'window_end' => $windowEnd->toDateTimeString(),
            'whatsapp_configured' => $this->whatsApp->isConfigured(),
        ]);

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($users as $user) {
            $phone = $this->normalizeWhatsAppPhone($user->phone);
            if (! $phone) {
                $skipped++;
                $this->logReminder($user, null, 'skipped', null, null, null, 'missing_phone');
                continue;
            }

            $paymentLink = $this->pendingPaymentLinkFor($user);
            if (! $paymentLink) {
                $skipped++;
                $this->logReminder($user, null, 'skipped', $phone, null, null, 'missing_pending_payment_link');
                continue;
            }

            $shortCode = $paymentLink->short_code ?: $this->extractRazorpayShortCode((string) $paymentLink->short_url);
            if ($shortCode === '') {
                $skipped++;
                $this->logReminder($user, $paymentLink, 'skipped', $phone, null, null, 'missing_payment_link_slug');
                continue;
            }

            if (! $this->whatsApp->isConfigured()) {
                $skipped++;
                $this->logReminder($user, $paymentLink, 'skipped', $phone, null, null, 'whatsapp_not_configured');
                continue;
            }

            $context = $this->buildReminderContext($user, $paymentLink);
            $useFullTemplate = $context['parent_name'] !== ''
                && $context['pet_name'] !== '';

            $template = $useFullTemplate
                ? config('services.whatsapp.templates.cf_payment_link_reminder_full', 'cf_payment_link_reminder_full')
                : config('services.whatsapp.templates.cf_payment_link_reminder_minimal', 'cf_payment_link_reminder_minimal');
            $language = $useFullTemplate
                ? config('services.whatsapp.templates.cf_payment_link_reminder_full_language', 'en')
                : config('services.whatsapp.templates.cf_payment_link_reminder_minimal_language', 'en');
            $variant = $useFullTemplate ? 'full' : 'minimal';
            $components = $this->buildTemplateComponents($context, $shortCode, $useFullTemplate);

            if ($dryRun) {
                $skipped++;
                $this->logReminder($user, $paymentLink, 'dry_run', $phone, $template, $language, null, $variant);
                continue;
            }

            try {
                $this->whatsApp->sendTemplate(
                    to: $phone,
                    template: $template,
                    components: $components,
                    language: $language,
                    channelName: 'profile_created_payment_link_reminder'
                );

                $sent++;
                $this->logReminder($user, $paymentLink, 'sent', $phone, $template, $language, null, $variant);
            } catch (\Throwable $e) {
                $failed++;
                $this->logReminder($user, $paymentLink, 'failed', $phone, $template, $language, $e->getMessage(), $variant);
                Log::warning('pp_profile_created_20m_payment_reminder.send_failed', [
                    'user_id' => $user->id,
                    'payment_link_id' => $paymentLink->payment_link_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('pp_profile_created_20m_payment_reminder.run_finish', [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'at' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }

    private function pendingPaymentLinkFor(User $user): ?RazorpayPaymentLink
    {
        return RazorpayPaymentLink::query()
            ->where('user_id', $user->id)
            ->whereNull('paid_at')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereIn('status', ['created', 'issued', 'partially_paid']);
            })
            ->where(function ($q) {
                $q->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['paid', 'completed', 'captured', 'success', 'authorized']);
            })
            ->latest('id')
            ->first();
    }

    private function buildReminderContext(User $user, RazorpayPaymentLink $paymentLink): array
    {
        $pet = $paymentLink->pet_id
            ? Pet::query()->find($paymentLink->pet_id)
            : Pet::query()->where('user_id', $user->id)->latest('id')->first();

        $clinicId = $paymentLink->clinic_id ?: $user->last_vet_id;
        $doctorName = $this->resolveDoctorName($paymentLink->doctor_id, $clinicId);

        return [
            'doctor_name' => $doctorName ?: 'Snoutiq',
            'parent_name' => $this->cleanText($user->name),
            'pet_name' => $this->cleanText($pet?->name),
            'pet_breed' => $this->cleanText($pet?->breed),
            'response_time' => (string) ((int) (data_get($paymentLink->raw_response, 'notes.response_time_minutes') ?: 10)),
            'amount' => $this->formatRupeesForTemplate((int) ($paymentLink->amount_paise ?: 49900)),
        ];
    }

    private function buildTemplateComponents(array $context, string $shortCode, bool $useFullTemplate): array
    {
        if ($useFullTemplate) {
            $bodyParameters = [
                ['type' => 'text', 'text' => $context['doctor_name']], // {{1}}
                ['type' => 'text', 'text' => $context['parent_name']], // {{2}}
                ['type' => 'text', 'text' => $context['pet_name']],    // {{3}}
                ['type' => 'text', 'text' => $context['amount']],      // {{4}}
                ['type' => 'text', 'text' => $context['response_time']], // {{5}}
            ];
        } else {
            $bodyParameters = [
                ['type' => 'text', 'text' => $context['doctor_name']], // {{1}}
                ['type' => 'text', 'text' => $context['amount']],      // {{2}}
                ['type' => 'text', 'text' => $context['response_time']], // {{3}}
            ];
        }

        return [
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
    }

    private function resolveDoctorName(?int $doctorId, ?int $clinicId): ?string
    {
        $doctorName = null;

        if ($doctorId) {
            $doctorName = DB::table('doctors')
                ->where('id', $doctorId)
                ->value('doctor_name');
        }

        if (! $doctorName && $clinicId) {
            $doctorName = DB::table('doctors')
                ->where('vet_registeration_id', $clinicId)
                ->orderBy('id')
                ->value('doctor_name');
        }

        return $this->cleanDoctorName($doctorName);
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

    private function extractRazorpayShortCode(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        $segment = trim((string) $path, '/');
        if ($segment === '') {
            return '';
        }

        $parts = explode('/', $segment);
        return trim((string) end($parts));
    }

    private function formatRupeesForTemplate(int $amountPaise): string
    {
        $formatted = number_format($amountPaise / 100, 2, '.', '');

        return str_ends_with($formatted, '.00')
            ? substr($formatted, 0, -3)
            : rtrim(rtrim($formatted, '0'), '.');
    }

    private function cleanDoctorName(?string $name): string
    {
        $clean = $this->cleanText($name);
        $clean = preg_replace('/^dr\.?\s+/i', '', $clean) ?: $clean;

        return trim($clean);
    }

    private function cleanText(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?: '');
    }

    private function logReminder(
        User $user,
        ?RazorpayPaymentLink $paymentLink,
        string $status,
        ?string $phone,
        ?string $template,
        ?string $language,
        ?string $error,
        ?string $variant = null
    ): void {
        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => $user->id,
                'pet_id' => $paymentLink?->pet_id,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode([
                    'type' => self::LOG_TYPE,
                    'variant' => $variant,
                    'payment_link_id' => $paymentLink?->payment_link_id,
                    'razorpay_payment_link_row_id' => $paymentLink?->id,
                    'user_created_at' => optional($user->created_at)->toDateTimeString(),
                    'due_at' => optional($user->created_at)->copy()?->addMinutes(20)->toDateTimeString(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('pp_profile_created_20m_payment_reminder.log_failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
