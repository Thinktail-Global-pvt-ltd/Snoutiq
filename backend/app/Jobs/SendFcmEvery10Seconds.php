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
        $handler = static::class.'@handle';
        $tracker = new QueryTracker();
        $tracker->start();
        $startedAt = microtime(true);
        $run = null;
        $jobId = $this->job?->getJobId();
        $extra = [];

        FounderAudit::info('run_now.start', [
            'handler' => $handler,
            'schedule_id' => $this->scheduleId,
            'job_id' => $jobId,
        ]);

        $lock = null;
        $lockAcquired = false;
        $lockRejected = false;
        $store = Cache::getStore();
        if ($store instanceof LockProvider) {
            $lock = Cache::lock('fcm10:lock:' . $this->scheduleId, 9);
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
            if (! $schedule || ! $schedule->is_active || $schedule->frequency !== S::FREQUENCY_TEN_SECONDS) {
                $extra = ['skipped' => true];
                return;
            }

            $jobId = $this->job?->getJobId();
            try {
                $run = $pushService->broadcast(
                    $schedule,
                    $schedule->title,
                    $schedule->body ?? '',
                    'scheduled',
                    'SendFcmEvery10Seconds@handle → PushService@broadcast',
                    $jobId
                );
            } catch (Throwable $e) {
                FounderAudit::error('run_now.exception', $e, [
                    'handler' => $handler,
                    'job_id' => $jobId,
                    'schedule_id' => $this->scheduleId,
                ]);
                throw $e;
            }
            $schedule->forceFill(['last_run_at' => now()])->save();

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
