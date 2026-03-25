<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Pet;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendUserCreatedReminders extends Command
{
    protected $signature = 'notifications:pp-user-created {--user_id=}';
    protected $description = 'Send SNQ_PP_RECORDS_CREATED WhatsApp + FCM 2 hours after user creation (same-day only).';

    public function __construct(private readonly WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $whatsAppConfigured = $this->whatsApp->isConfigured();
        if (! $whatsAppConfigured) {
            Log::warning('pp_user_created.whatsapp_not_configured');
        }

        $now = now();
        $forcedUserId = $this->option('user_id');

        // Run every minute and target users whose creation timestamp is ~2h old.
        $windowEnd = $now->copy()->subHours(2);
        $windowStart = $windowEnd->copy()->subMinute();

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
            'whatsapp_configured' => $whatsAppConfigured,
            'window_start' => $windowStart->toDateTimeString(),
            'window_end' => $windowEnd->toDateTimeString(),
        ]);

        $whatsAppSent = 0;
        $fcmSent = 0;

        foreach ($users as $user) {
            if (! $forcedUserId && $user->created_at) {
                $createdAt = $user->created_at->copy();
                $dueAt = $createdAt->copy()->addHours(2);

                // Requirement: send on the same calendar day as user creation.
                if (! $dueAt->isSameDay($createdAt)) {
                    $this->log($user->id, 'skipped', $user->phone, null, null, 'due_time_not_same_day');
                    continue;
                }
            }

            // Fetch pet name
            $petName = Pet::where('user_id', $user->id)->orderByDesc('id')->value('name');
            if (! $petName) {
                $this->log($user->id, 'skipped', $user->phone, null, null, 'missing_pet_name');
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
                $this->log($user->id, 'skipped', $user->phone, null, null, 'missing_doctor_or_clinic');
                continue;
            }

            // Send FCM in addition to WhatsApp.
            $fcm = $this->sendFcmNotification($user, $petName, $doctorName, $clinicName);
            $this->logFcm($user->id, $fcm['status'], $fcm['notification_id'], $fcm['error']);
            if ($fcm['status'] === 'sent') {
                $fcmSent++;
            }

            $phone = $user->phone;

            if (! $whatsAppConfigured) {
                $this->log($user->id, 'skipped', $phone, null, null, 'whatsapp_not_configured');
                continue;
            }

            if (! $phone) {
                $this->log($user->id, 'skipped', $phone, null, null, 'missing_phone');
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

            $ok = false;
            $last = null;
            $tplUsed = null;
            $langUsed = null;
            foreach ($templates as $tpl) {
                foreach ($languages as $lang) {
                    try {
                        $this->whatsApp->sendTemplate($phone, $tpl, $components, $lang);
                        $ok = true;
                        $tplUsed = $tpl;
                        $langUsed = $lang;
                        break 2;
                    } catch (\RuntimeException $e) {
                        $last = $e->getMessage();
                    }
                }
            }

            if ($ok) {
                $whatsAppSent++;
                $this->log($user->id, 'sent', $phone, $tplUsed, $langUsed, null);
            } else {
                $this->log($user->id, 'failed', $phone, $tplUsed ?? $templates[0] ?? null, $langUsed ?? $languages[0] ?? null, $last);
            }
        }

        Log::info('pp_user_created.run_finish', [
            'sent_whatsapp' => $whatsAppSent,
            'sent_fcm' => $fcmSent,
            'at' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }

    private function sendFcmNotification(User $user, string $petName, string $doctorName, string $clinicName): array
    {
        try {
            $notification = Notification::create([
                'user_id' => $user->id,
                'pet_id' => null,
                'clinic_id' => null,
                'type' => 'pp_user_created',
                'title' => 'Welcome to Snoutiq',
                'body' => sprintf(
                    'Dr %s can now help %s via %s. Tap to continue.',
                    $doctorName,
                    $petName,
                    $clinicName
                ),
                'payload' => [
                    'type' => 'pp_user_created',
                    'user_id' => (string) $user->id,
                    'pet_name' => $petName,
                    'doctor_name' => $doctorName,
                    'clinic_name' => $clinicName,
                ],
                'status' => Notification::STATUS_PENDING,
            ]);

            SendNotificationJob::dispatchSync($notification->id);
            $notification->refresh();

            if ($notification->status === Notification::STATUS_SENT) {
                return [
                    'status' => 'sent',
                    'notification_id' => $notification->id,
                    'error' => null,
                ];
            }

            return [
                'status' => 'failed',
                'notification_id' => $notification->id,
                'error' => 'notification_status_'.$notification->status,
            ];
        } catch (\Throwable $e) {
            Log::error('pp_user_created.fcm_failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'notification_id' => null,
                'error' => $e->getMessage(),
            ];
        }
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

    private function logFcm(int $userId, string $status, ?int $notificationId, ?string $error): void
    {
        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => $userId,
                'pet_id' => null,
                'phone' => null,
                'template' => null,
                'language' => null,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode([
                    'type' => 'pp_user_created_fcm',
                    'notification_id' => $notificationId,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('pp_user_created.fcm_log_failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
