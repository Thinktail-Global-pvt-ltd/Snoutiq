<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LeadAiMarketingPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendLeadAiMarketingPushes extends Command
{
    protected $signature = 'notifications:lead-ai-marketing-push {--user_id=} {--limit=100} {--dry} {--force}';

    protected $description = 'Send Gemini-personalized marketing push to app users older than 10 days with no captured payment.';

    public function handle(LeadAiMarketingPushService $pushService): int
    {
        if (
            !Schema::hasTable('users')
            || !Schema::hasTable('device_tokens')
            || !Schema::hasTable('notifications')
        ) {
            $this->error('Required tables are missing.');
            return self::FAILURE;
        }

        $forcedUserId = $this->option('user_id');
        $dryRun = (bool) $this->option('dry');
        $force = (bool) $this->option('force');
        $limit = max(1, min((int) ($this->option('limit') ?: 100), 500));
        $skipExisting = !$force;

        $query = User::query()
            ->select(['id', 'name', 'phone', 'created_at'])
            ->whereNotNull('created_at');

        if ($forcedUserId) {
            $query->whereKey((int) $forcedUserId);
        } else {
            $query
                ->where('created_at', '<=', now()->subDays(10))
                ->whereExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('device_tokens')
                        ->whereColumn('device_tokens.user_id', 'users.id')
                        ->whereNotNull('device_tokens.token')
                        ->where('device_tokens.token', '!=', '');
                });

            if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'status')) {
                $query->whereNotExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('transactions')
                        ->whereColumn('transactions.user_id', 'users.id')
                        ->whereRaw("LOWER(TRIM(COALESCE(transactions.status, ''))) = 'captured'");
                });
            }

            if ($skipExisting) {
                $query->whereNotExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('notifications')
                        ->whereColumn('notifications.user_id', 'users.id')
                        ->where('notifications.type', 'ai_marketing_no_payment')
                        ->whereIn('notifications.status', ['pending', 'sent', 'delivered']);
                });
            }
        }

        $candidates = $query
            ->orderBy('id')
            ->limit($limit)
            ->get();

        Log::info('lead_ai_marketing_push.run_start', [
            'candidates' => $candidates->count(),
            'forced_user_id' => $forcedUserId ? (int) $forcedUserId : null,
            'limit' => $limit,
            'dry_run' => $dryRun,
            'force' => $force,
        ]);

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $user) {
            $eligibility = $pushService->eligibility($user, $skipExisting);
            if (!$eligibility['eligible']) {
                $skipped++;
                Log::info('lead_ai_marketing_push.skipped', [
                    'user_id' => $user->id,
                    'reason' => $eligibility['reason'],
                ]);
                continue;
            }

            if ($dryRun) {
                $skipped++;
                $this->line("DRY eligible user_id={$user->id}");
                continue;
            }

            $result = $pushService->send($user, $skipExisting);
            if ($result['sent']) {
                $sent++;
                $this->info("Sent AI marketing push to user_id={$user->id}");
                continue;
            }

            if (($result['reason'] ?? '') === 'already_sent') {
                $skipped++;
            } else {
                $failed++;
            }

            Log::warning('lead_ai_marketing_push.not_sent', [
                'user_id' => $user->id,
                'reason' => $result['reason'],
                'message' => $result['message'],
            ]);
        }

        Log::info('lead_ai_marketing_push.run_finish', [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        $this->info("Lead AI marketing push complete: sent={$sent}, skipped={$skipped}, failed={$failed}.");

        return self::SUCCESS;
    }
}
