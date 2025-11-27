<?php

namespace App\Jobs;

use App\Models\ScheduledPushNotification as S;
use App\Services\Logging\FounderAudit;
use App\Services\PushService;
use App\Support\QueryTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

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
        // DISABLED: 10-second notifications are no longer supported
        // This job will immediately return without processing anything
        // and will not dispatch itself again
        
        $schedule = S::find($this->scheduleId);
        if ($schedule) {
            // Deactivate the schedule to prevent it from being restarted
            $schedule->is_active = false;
            $schedule->next_run_at = null;
            $schedule->save();
        }
        
        // Do not dispatch next - this stops the 10-second cycle
        return;
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

    protected function finalizeLogging(QueryTracker $tracker, string $handler, ?string $jobId, float $startedAt, $run = null, array $extra = []): void
    {
        $metrics = $tracker->finish();
        FounderAudit::info('run_now.db', $metrics + [
            'handler' => $handler,
            'job_id' => $jobId,
        ] + $extra);

        if ($run) {
            FounderAudit::info('run_now.fcm', [
                'handler' => $handler,
                'job_id' => $jobId,
                'run_id' => $run->id,
                'targeted' => $run->targeted_count,
                'success' => $run->success_count,
                'failed' => $run->failure_count,
            ]);
        }

        FounderAudit::info('run_now.end', [
            'handler' => $handler,
            'job_id' => $jobId,
            'durationMs' => (int) ((microtime(true) - $startedAt) * 1000),
        ] + $extra);
    }
}
