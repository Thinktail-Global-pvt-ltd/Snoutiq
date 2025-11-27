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
use Illuminate\Support\Carbon;
use Throwable;

class SendFcmEvery5Minutes implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes timeout

    // small window so duplicate kicks collapse into one iteration
    public int $uniqueFor = 240; // 4 minutes

    public function __construct(public int $scheduleId)
    {
    }

    public function uniqueId(): string
    {
        return 'fcm5min:' . $this->scheduleId;
    }

    public function handle(PushService $pushService): void
    {
        $handler = static::class.'@handle';
        $tracker = new QueryTracker();
        $tracker->start();
        $startedAt = microtime(true);
        $run = null;
        $jobId = $this->job?->getJobId();
        $extra = [];

        FounderAudit::info('fcm5min.start', [
            'handler' => $handler,
            'schedule_id' => $this->scheduleId,
            'job_id' => $jobId,
        ]);

        $lock = null;
        $lockAcquired = false;
        $lockRejected = false;
        $store = Cache::getStore();
        if ($store instanceof LockProvider) {
            $lock = Cache::lock('fcm5min:lock:' . $this->scheduleId, 240); // 4 minutes lock
            if (! $lock->get()) {
                $extra = ['skipped' => true, 'reason' => 'lock'];
                $lockRejected = true; // another worker already handling this tick
            } else {
                $lockAcquired = true;
            }
        }

        try {
            if ($lockRejected) {
                return;
            }

            $schedule = S::find($this->scheduleId);
            if (! $schedule || ! $schedule->is_active || $schedule->frequency !== S::FREQUENCY_FIVE_MINUTES) {
                $extra = ['skipped' => true];
                return;
            }

            $jobId = $this->job?->getJobId();
            try {
                $run = $pushService->broadcast(
                    $schedule,
                    $schedule->title,
                    $schedule->body ?? '',
                    'marketing',
                    'SendFcmEvery5Minutes@handle → PushService@broadcast',
                    $jobId
                );
            } catch (Throwable $e) {
                FounderAudit::error('fcm5min.exception', $e, [
                    'handler' => $handler,
                    'job_id' => $jobId,
                    'schedule_id' => $this->scheduleId,
                ]);
                throw $e;
            }

            // Update schedule
            $schedule->forceFill([
                'last_run_at' => now(),
                'next_run_at' => now()->addMinutes(5),
            ])->save();

            $this->dispatchNext();
        } finally {
            $this->finalizeLogging($tracker, $handler, $jobId, $startedAt, $run ?? null, $extra);
            if ($lock && $lockAcquired) {
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

        // Schedule next run in 5 minutes
        $pending->delay(now()->addMinutes(5));
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
        FounderAudit::info('fcm5min.db', $metrics + [
            'handler' => $handler,
            'job_id' => $jobId,
        ] + $extra);

        if ($run) {
            FounderAudit::info('fcm5min.fcm', [
                'handler' => $handler,
                'job_id' => $jobId,
                'run_id' => $run->id,
                'targeted' => $run->targeted_count,
                'success' => $run->success_count,
                'failed' => $run->failure_count,
            ]);
        }

        FounderAudit::info('fcm5min.end', [
            'handler' => $handler,
            'job_id' => $jobId,
            'durationMs' => (int) ((microtime(true) - $startedAt) * 1000),
        ] + $extra);
    }
}

