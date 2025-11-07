<?php

namespace App\Jobs;

use App\Models\ScheduledPushNotification as S;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SendFcmEvery10Seconds implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    // small window so duplicate kicks collapse into one iteration
    public int $uniqueFor = 9;

    public function __construct(public int $scheduleId)
    {
    }

    public function uniqueId(): string
    {
        return 'fcm10:' . $this->scheduleId;
    }

    public function handle(): void
    {
        $lock = null;
        $store = Cache::getStore();
        if ($store instanceof LockProvider) {
            $lock = Cache::lock('fcm10:lock:' . $this->scheduleId, 9);
            if (! $lock->get()) {
                return; // another worker already handling this tick
            }
        }

        try {
            $schedule = S::find($this->scheduleId);
            if (! $schedule || ! $schedule->is_active || $schedule->frequency !== S::FREQUENCY_TEN_SECONDS) {
                return;
            }

            BroadcastScheduledNotification::dispatchSync($schedule->getKey());
            $schedule->forceFill(['last_run_at' => now()])->save();

            // keep the loop alive with a 10s delay
            self::dispatch($schedule->getKey())->delay(now()->addSeconds(10));
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }
}
