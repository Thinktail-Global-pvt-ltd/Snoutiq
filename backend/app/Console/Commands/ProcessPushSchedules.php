<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\ScheduledPushNotification;
use App\Jobs\SendScheduledPush;

class ProcessPushSchedules extends Command
{
    protected $signature = 'push:process-schedules';
    protected $description = 'Dispatch due scheduled push notifications (minute+ cadences)';

    public function handle(): int
    {
        $now = Carbon::now();

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
    }
}
