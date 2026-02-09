<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Pet;
use App\Models\VetResponseReminderLog;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendUserCreatedReminders extends Command
{
    protected $signature = 'notifications:pp-user-created {--user_id=}';
    protected $description = 'Send SNQ_PP_RECORDS_CREATED WhatsApp after a user (pet parent) is created (>=2h ago).';

    public function __construct(private readonly WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->whatsApp->isConfigured()) {
            Log::warning('pp_user_created.whatsapp_not_configured');
            return self::SUCCESS;
        }

        $now = now();
        $forcedUserId = $this->option('user_id');

        $windowStart = $now->copy()->subHours(3);
        $windowEnd   = $now->copy()->subHours(2);

        if ($forcedUserId) {
            $users = User::query()
                ->where('id', (int) $forcedUserId)
                ->limit(1)
                ->get();
        } else {
            $users = User::query()
                ->whereBetween('created_at', [$windowStart, $windowEnd])
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereDoesntHave('reminderLogs', function ($q) {
                    $q->whereJsonContains('meta->type', 'pp_user_created');
                })
                ->limit(200)
                ->get();
        }

        Log::info('pp_user_created.run_start', [
            'candidates' => $users->count(),
            'at' => $now->toDateTimeString(),
            'forced_user_id' => $forcedUserId,
        ]);

        $sent = 0;
        foreach ($users as $user) {
            $phone = $user->phone;
            if (! $phone) {
                $this->log($user->id, 'skipped', $phone, null, null, 'missing_phone');
                continue;
            }

            // Fetch pet name
            $petName = Pet::where('user_id', $user->id)->orderByDesc('id')->value('name');
            if (! $petName) {
                $this->log($user->id, 'skipped', $phone, null, null, 'missing_pet_name');
                continue;
            }

            // Clinic + doctor name
            $clinicName = null;
            $doctorName = null;
            $lastVetId = $user->last_vet_id ?? null;
            if ($lastVetId) {
                $clinicName = DB::table('vet_registerations_temp')->where('id', $lastVetId)->value('name');
                $doctorName = DB::table('doctors')
                    ->where('vet_registeration_id', $lastVetId)
                    ->orderBy('id')
                    ->value('doctor_name');
            }

            if (! $doctorName || ! $clinicName) {
                $this->log($user->id, 'skipped', $phone, null, null, 'missing_doctor_or_clinic');
                continue;
            }

            // Body params only; template has static URL button.
            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $doctorName], // {{1}}
                        ['type' => 'text', 'text' => $petName],    // {{2}}
                    ],
                ],
            ];

            $templates = array_values(array_filter([
                config('services.whatsapp.templates.snq_pp_records_created') ?? null,
                'SNQ_PP_RECORDS_CREATED',
                'snq_pp_records_created',
            ]));
            $languages = array_values(array_filter([
                config('services.whatsapp.templates.snq_pp_records_created_language') ?? null,
                'en_US',
                'en_GB',
                'en',
            ]));

            $ok = false; $last = null; $tplUsed = null; $langUsed = null;
            foreach ($templates as $tpl) {
                foreach ($languages as $lang) {
                    try {
                        $this->whatsApp->sendTemplate($phone, $tpl, $components, $lang);
                        $ok = true; $tplUsed = $tpl; $langUsed = $lang;
                        break 2;
                    } catch (\RuntimeException $e) {
                        $last = $e->getMessage();
                    }
                }
            }

            if ($ok) {
                $sent++;
                $this->log($user->id, 'sent', $phone, $tplUsed, $langUsed, null);
            } else {
                $this->log($user->id, 'failed', $phone, $tplUsed ?? $templates[0] ?? null, $langUsed ?? $languages[0] ?? null, $last);
            }
        }

        Log::info('pp_user_created.run_finish', [
            'sent' => $sent,
            'at' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }

    private function log(int $userId, string $status, ?string $phone, ?string $template, ?string $language, ?string $error): void
    {
        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => $userId,
                'pet_id' => null,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode(['type' => 'pp_user_created']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('pp_user_created.log_failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
