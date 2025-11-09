<?php

namespace App\Jobs;

use App\Models\ScheduledPushNotification;
use App\Services\Logging\FounderAudit;
use App\Services\PushService;
use App\Support\QueryTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

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
        $handler = static::class.'@handle';
        $tracker = new QueryTracker();
        $tracker->start();
        $startedAt = microtime(true);
        $run = null;
        $jobId = $this->job?->getJobId();

        FounderAudit::info('run_now.start', [
            'handler' => $handler,
            'schedule_id' => $this->scheduleId,
            'job_id' => $jobId,
        ]);

        $schedule = ScheduledPushNotification::query()
            ->active()
            ->whereKey($this->scheduleId)
            ->first();

        if (! $schedule) {
            $metrics = $tracker->finish();
            FounderAudit::info('run_now.db', $metrics + ['handler' => $handler]);
            FounderAudit::info('run_now.end', [
                'handler' => $handler,
                'job_id' => $jobId,
                'durationMs' => (int) ((microtime(true) - $startedAt) * 1000),
                'skipped' => true,
            ]);
            return;
        }

        try {
            $run = $pushService->broadcast(
                $schedule,
                $schedule->title,
                $schedule->body ?? '',
                $this->trigger,
                'SendScheduledPush@handle → PushService@broadcast',
                $jobId
            );

            $schedule->forceFill(['last_run_at' => now()])->save();
        } catch (Throwable $e) {
            FounderAudit::error('run_now.exception', $e, [
                'handler' => $handler,
                'job_id' => $jobId,
                'schedule_id' => $this->scheduleId,
            ]);
            throw $e;
        } finally {
            $metrics = $tracker->finish();
            FounderAudit::info('run_now.db', $metrics + [
                'handler' => $handler,
                'job_id' => $jobId,
            ]);

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
            ]);
        }
    }
}
