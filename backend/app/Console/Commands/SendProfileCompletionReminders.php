<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendProfileCompletionReminders extends Command
{
    protected $signature = 'notifications:pp-profile-completion {--user_id=} {--dry}';
    protected $description = 'Send profile completion nudges to users whose completion percent is below 100%.';

    public function handle(): int
    {
        $forcedUserId = $this->option('user_id');
        $dryRun = (bool) $this->option('dry');
        $cooldownHours = 24;
        $cooldownStart = now()->subHours($cooldownHours);
        $todayStart = now()->startOfDay();

        if (! $forcedUserId && ! $dryRun && $this->hasRunToday($todayStart)) {
            Log::info('pp_profile_completion.run_skip', [
                'reason' => 'already_ran_today',
            ]);
            return self::SUCCESS;
        }

        $query = User::query()->select(['id', 'phone']);
        if ($forcedUserId) {
            $query->whereKey((int) $forcedUserId);
        } else {
            $query->whereDoesntHave('reminderLogs', function ($q) use ($cooldownStart) {
                $q->whereJsonContains('meta->type', 'pp_profile_completion')
                    ->whereIn('status', ['queued', 'sent'])
                    ->where('created_at', '>=', $cooldownStart);
            });
        }

        $total = (clone $query)->count();
        $sent = 0;
        $skipped = 0;
        $errors = 0;

        $controller = app(\App\Http\Controllers\Api\UserController::class);

        Log::info('pp_profile_completion.run_start', [
            'total_candidates' => $total,
            'forced_user_id' => $forcedUserId,
            'dry_run' => $dryRun,
            'cooldown_hours' => $cooldownHours,
        ]);

        $query->orderBy('id')->chunkById(200, function ($users) use ($controller, $dryRun, &$sent, &$skipped, &$errors) {
            foreach ($users as $user) {
                try {
                    $completion = $this->fetchCompletionPercent($controller, (int) $user->id);
                    if ($completion === null) {
                        $this->log($user->id, 'skipped', 'completion_unavailable', null);
                        $skipped++;
                        continue;
                    }

                    if ($completion >= 100) {
                        $this->log($user->id, 'skipped', 'completion_100', $completion);
                        $skipped++;
                        continue;
                    }

                    if (! DeviceToken::query()->where('user_id', $user->id)->exists()) {
                        $this->log($user->id, 'skipped', 'no_device_tokens', $completion);
                        $skipped++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->log($user->id, 'skipped', 'dry_run', $completion);
                        $skipped++;
                        continue;
                    }

                    $deepLink = 'snoutiq://videocall-appointment';
                    $notification = Notification::create([
                        'user_id' => $user->id,
                        'type' => 'profile_completion',
                        'title' => 'Complete your profile',
                        'body' => sprintf('Your profile is %d%% complete. Add missing details to finish.', $completion),
                        'payload' => [
                            'type' => 'profile_completion',
                            'completion_percent' => $completion,
                            'deepLink' => $deepLink,
                            'deep_link' => $deepLink,
                            'deeplink' => $deepLink,
                        ],
                        'status' => Notification::STATUS_PENDING,
                    ]);

                    SendNotificationJob::dispatch($notification->id);

                    $this->log($user->id, 'queued', null, $completion, $notification->id);
                    $sent++;
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('pp_profile_completion.failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->log($user->id, 'failed', $e->getMessage(), null);
                }
            }
        });

        Log::info('pp_profile_completion.run_finish', [
            'sent' => $sent,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);

        if (! $forcedUserId && ! $dryRun) {
            $this->logRun('completed');
        }

        return self::SUCCESS;
    }

    private function fetchCompletionPercent($controller, int $userId): ?int
    {
        $request = Request::create('/api/user/profile/completion', 'GET', [
            'user_id' => $userId,
        ]);

        $response = $controller->profileCompletion($request);
        $payload = $response->getData(true);

        if (! is_array($payload) || empty($payload['success'])) {
            return null;
        }

        $percent = $payload['data']['completion_percent'] ?? null;
        if ($percent === null || ! is_numeric($percent)) {
            return null;
        }

        return (int) $percent;
    }

    private function log(int $userId, string $status, ?string $error, ?int $completionPercent, ?int $notificationId = null): void
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
                    'type' => 'pp_profile_completion',
                    'completion_percent' => $completionPercent,
                    'notification_id' => $notificationId,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('pp_profile_completion.log_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function hasRunToday($todayStart): bool
    {
        return DB::table('vet_response_reminder_logs')
            ->whereJsonContains('meta->type', 'pp_profile_completion_run')
            ->where('created_at', '>=', $todayStart)
            ->exists();
    }

    private function logRun(string $status): void
    {
        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => null,
                'pet_id' => null,
                'phone' => null,
                'template' => null,
                'language' => null,
                'status' => $status,
                'error' => null,
                'meta' => json_encode([
                    'type' => 'pp_profile_completion_run',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('pp_profile_completion.run_log_failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
