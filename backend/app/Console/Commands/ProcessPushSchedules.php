<?php

namespace App\Console\Commands;

use App\Services\Logging\FounderAudit;
use App\Support\QueryTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\ScheduledPushNotification;
use App\Jobs\SendScheduledPush;
use Throwable;

class ProcessPushSchedules extends Command
{
    protected $signature = 'push:process-schedules';
    protected $description = 'Dispatch due scheduled push notifications (minute+ cadences)';

    public function handle(): int
    {
        $handler = static::class.'@handle';
        $tracker = new QueryTracker();
        $tracker->start();
        $startedAt = microtime(true);

        FounderAudit::info('run_now.start', ['handler' => $handler]);

        $now = Carbon::now();

        try {
            ScheduledPushNotification::query()
                ->active()
                ->whereNotNull('next_run_at')
                ->where('next_run_at', '<=', $now)
                ->whereIn('frequency', [
                    ScheduledPushNotification::FREQUENCY_DAILY,
                    ScheduledPushNotification::FREQUENCY_WEEKLY,
                    ScheduledPushNotification::FREQUENCY_MONTHLY,
                ])
                ->orderBy('next_run_at')
                ->chunkById(200, function ($rows) use ($now) {
                    foreach ($rows as $s) {
                        SendScheduledPush::dispatch($s->getKey());

                        $s->last_run_at = $now;
                        $s->next_run_at = $s->computeNextRun($now);
                        $s->save();
                    }
                });

            $this->info('Processed push schedules at '.$now->toDateTimeString());
            return self::SUCCESS;
        } catch (Throwable $e) {
            FounderAudit::error('run_now.exception', $e, ['handler' => $handler]);
            throw $e;
        } finally {
            $metrics = $tracker->finish();
            FounderAudit::info('run_now.db', $metrics + ['handler' => $handler]);
            FounderAudit::info('run_now.end', [
                'handler' => $handler,
                'durationMs' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        }
    }
}
