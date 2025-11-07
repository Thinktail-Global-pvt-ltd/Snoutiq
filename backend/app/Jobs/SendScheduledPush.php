<?php

namespace App\Jobs;

use App\Models\ScheduledPushNotification;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledPush implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $scheduleId, public string $trigger = 'scheduled')
    {
    }

    public function handle(PushService $pushService): void
    {
        $schedule = ScheduledPushNotification::query()
            ->active()
            ->whereKey($this->scheduleId)
            ->first();

        if (! $schedule) {
            return;
        }

        $jobId = $this->job?->getJobId();

        $pushService->broadcast(
            $schedule,
            $schedule->title,
            $schedule->body ?? '',
            $this->trigger,
            'SendScheduledPush@handle → PushService@broadcast',
            $jobId
        );

        $schedule->forceFill(['last_run_at' => now()])->save();
    }
}

