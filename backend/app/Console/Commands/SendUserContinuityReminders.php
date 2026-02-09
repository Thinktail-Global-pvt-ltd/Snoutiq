<?php

namespace App\Console\Commands;

use App\Models\Pet;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendUserContinuityReminders extends Command
{
    protected $signature = 'notifications:pp-user-continuity {--user_id=}';
    protected $description = 'Send SNQ_PP_RECORDS_CONTINUITY WhatsApp 24h after Template 1 (pp_user_created) was sent.';

    public function __construct(private readonly WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->whatsApp->isConfigured()) {
            Log::warning('pp_user_continuity.whatsapp_not_configured');
            return self::SUCCESS;
        }

        $now = now();
        $forcedUserId = $this->option('user_id');

        // Find users who received template 1 at least 24h ago and haven't received continuity
        $eligibleUsers = DB::table('vet_response_reminder_logs')
            ->select('user_id', DB::raw("MIN(created_at) as first_sent_at"))
            ->where('status', 'sent')
            ->whereRaw("JSON_EXTRACT(meta, '$.type') = 'pp_user_created'")
            ->when($forcedUserId, fn($q) => $q->where('user_id', (int) $forcedUserId))
            ->groupBy('user_id')
            ->havingRaw('first_sent_at <= ?', [$now->copy()->subHours(24)])
            ->pluck('first_sent_at', 'user_id');

        if ($eligibleUsers->isEmpty()) {
            Log::info('pp_user_continuity.run_start', [
                'candidates' => 0,
                'at' => $now->toDateTimeString(),
                'forced_user_id' => $forcedUserId,
            ]);
            Log::info('pp_user_continuity.run_finish', ['sent' => 0, 'at' => now()->toDateTimeString()]);
            return self::SUCCESS;
        }

        // Exclude users who already got continuity
        $alreadySent = DB::table('vet_response_reminder_logs')
            ->select('user_id')
            ->whereRaw("JSON_EXTRACT(meta, '$.type') = 'pp_user_continuity'")
            ->where('status', 'sent')
            ->pluck('user_id')
            ->all();

        $userIds = $eligibleUsers->keys()->diff($alreadySent)->values();
        $users = User::whereIn('id', $userIds)->get();

        Log::info('pp_user_continuity.run_start', [
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

            $petName = Pet::where('user_id', $user->id)->orderByDesc('id')->value('name');
            if (! $petName) {
                $this->log($user->id, 'skipped', $phone, null, null, 'missing_pet_name');
                continue;
            }

            $doctorName = null;
            $lastVetId = $user->last_vet_id ?? null;
            if ($lastVetId) {
                $doctorName = DB::table('doctors')
                    ->where('vet_registeration_id', $lastVetId)
                    ->orderBy('id')
                    ->value('doctor_name');
            }
            if (! $doctorName) {
                $this->log($user->id, 'skipped', $phone, null, null, 'missing_doctor');
                continue;
            }

            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $petName],    // {{1}}
                        ['type' => 'text', 'text' => $doctorName], // {{2}}
                    ],
                ],
            ];

            $templates = array_values(array_filter([
                config('services.whatsapp.templates.snq_pp_records_continuity') ?? null,
                'SNQ_PP_RECORDS_CONTINUITY',
                'snq_pp_records_continuity',
            ]));
            $languages = array_values(array_filter([
                config('services.whatsapp.templates.snq_pp_records_continuity_language') ?? null,
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

        Log::info('pp_user_continuity.run_finish', [
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
                'meta' => json_encode(['type' => 'pp_user_continuity']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('pp_user_continuity.log_failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
