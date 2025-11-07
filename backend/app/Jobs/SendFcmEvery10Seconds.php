<?php

namespace App\Jobs;

use App\Models\ScheduledPushNotification as S;
use App\Services\PushService;
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

    public function handle(PushService $pushService): void
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

            $jobId = $this->job?->getJobId();
            $pushService->broadcast(
                $schedule,
                $schedule->title,
                $schedule->body ?? '',
                'scheduled',
                'SendFcmEvery10Seconds@handle → PushService@broadcast',
                $jobId
            );
            $schedule->forceFill(['last_run_at' => now()])->save();

            $this->dispatchNext();
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }

    protected function dispatchNext(): void
    {
        $pending = static::dispatch($this->scheduleId);

        if ($connection = static::preferredConnection()) {
            $pending->onConnection($connection);
        }

        $pending->delay(10);
    }

    public static function preferredConnection(): ?string
    {
        $default = config('queue.default');
        $databaseConfigured = ! empty(config('queue.connections.database'));

        if ($default === 'sync' && $databaseConfigured) {
            return 'database';
        }

        return null;
    }
}
