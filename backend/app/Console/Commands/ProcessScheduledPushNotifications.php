<?php

namespace App\Console\Commands;

use App\Jobs\BroadcastScheduledNotification;
use App\Models\ScheduledPushNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessScheduledPushNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch due scheduled push notifications to all device tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $due = ScheduledPushNotification::query()
            ->where('is_active', true)
            ->where('frequency', '!=', ScheduledPushNotification::FREQUENCY_TEN_SECONDS)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', $now);
            })
            ->orderBy('next_run_at')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled push notifications due.');
            return self::SUCCESS;
        }

        foreach ($due as $notification) {
            $notification->last_run_at = $now;
            $notification->next_run_at = $notification->computeNextRun($now);
            $notification->save();

            BroadcastScheduledNotification::dispatchSync($notification->getKey());
        }

        $this->info("Dispatched {$due->count()} scheduled push notifications.");

        return self::SUCCESS;
    }
}
