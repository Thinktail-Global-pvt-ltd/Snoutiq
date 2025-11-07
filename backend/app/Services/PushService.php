<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\PushRun;
use App\Models\PushRunDelivery;
use App\Models\ScheduledPushNotification;
use App\Services\Push\FcmService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PushService
{
    public function __construct(private readonly FcmService $fcm)
    {
    }

    /**
     * Broadcast a push notification and persist/log execution metadata.
     */
    public function broadcast(
        ?ScheduledPushNotification $schedule,
        string $title,
        string $body,
        string $trigger = 'scheduled',
        string $codePath = 'PushService@broadcast',
        ?string $jobId = null
    ): PushRun {
        $run = new PushRun();
        $run->id = (string) Str::uuid();
        $run->schedule_id = $schedule?->getKey();
        $run->trigger = $trigger;
        $run->title = $title;
        $run->body = $body;
        $run->started_at = now();
        $run->code_path = $codePath;
        $run->job_id = $jobId;
        $run->log_file = $this->logFilePath();
        $run->save();

        $executionStartedAt = microtime(true);

        $batchSize = max(1, (int) config('push.batch_size', 500));
        $log = Log::channel(config('push.channel', 'push'));
        $payloadData = [
            'trigger' => $trigger,
        ];

        if ($schedule) {
            $payloadData['scheduled_notification_id'] = (string) $schedule->getKey();
            $payloadData = array_merge($payloadData, $schedule->data ?? []);
        }

        $log->info("PushRun {$run->id} START", [
            'schedule_id' => $schedule?->getKey(),
            'trigger' => $trigger,
            'title' => $title,
            'targeted' => 0,
            'code_path' => $codePath,
            'job_id' => $jobId,
            'file' => __FILE__.':'.__LINE__,
        ]);

        $targeted = 0;
        $success = 0;
        $failure = 0;
        $sampleDeviceIds = [];
        $sampleErrors = [];
        $exception = null;

        try {
            DeviceToken::query()
                ->select(['id', 'token', 'platform', 'device_id'])
                ->whereNotNull('token')
                ->orderBy('id')
                ->chunkById($batchSize, function ($tokens) use (
                    $title,
                    $body,
                    $payloadData,
                    $log,
                    $run,
                    &$targeted,
                    &$success,
                    &$failure,
                    &$sampleDeviceIds,
                    &$sampleErrors
                ) {
                    $tokens = $tokens->values();
                    if ($tokens->isEmpty()) {
                        return;
                    }

                    $tokenStrings = $tokens->pluck('token')->filter()->values()->all();
                    if (empty($tokenStrings)) {
                        return;
                    }

                    try {
                        $response = $this->fcm->sendMulticast($tokenStrings, $title, $body, $payloadData);
                    } catch (Throwable $e) {
                        $failure += count($tokenStrings);
                        $targeted += count($tokenStrings);
                        $log->error("PushRun {$run->id} batch exception: ".$e->getMessage(), [
                            'file' => __FILE__.':'.__LINE__,
                        ]);
                        $this->recordDeliveryFailuresFromException(
                            $run,
                            $tokens,
                            $e,
                            $sampleDeviceIds,
                            $sampleErrors
                        );
                        return;
                    }

                    $batchRows = [];
                    $now = now();

                    foreach ($tokens as $index => $deviceToken) {
                        $token = $tokenStrings[$index] ?? $deviceToken->token;
                        $result = $response['results'][$token] ?? null;
                        $ok = (bool) ($result['ok'] ?? false);

                        $targeted++;
                        $status = $ok ? 'success' : 'failed';
                        $status === 'success' ? $success++ : $failure++;

                        $deviceLabel = $deviceToken->device_id ?: 'device-token-'.$deviceToken->id;
                        if (count($sampleDeviceIds) < 5) {
                            $sampleDeviceIds[] = $deviceLabel;
                        }

                        $errorCode = isset($result['code']) ? (string) $result['code'] : null;
                        $errorMessage = isset($result['error']) ? Str::limit((string) $result['error'], 180) : null;

                        if (! $ok && count($sampleErrors) < 5) {
                            $sampleErrors[] = $errorMessage ?? 'unknown';
                        }

                        $batchRows[] = [
                            'push_run_id' => $run->id,
                            'device_id' => $deviceLabel,
                            'platform' => $deviceToken->platform,
                            'status' => $status,
                            'error_code' => $errorCode,
                            'error_message' => $errorMessage,
                            'fcm_response_snippet' => $ok ? null : json_encode($result),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (! empty($batchRows)) {
                        PushRunDelivery::insert($batchRows);
                    }

                    $log->info("PushRun {$run->id} batch complete", [
                        'batch_count' => count($batchRows),
                        'success_so_far' => $success,
                        'failure_so_far' => $failure,
                        'file' => __FILE__.':'.__LINE__,
                    ]);
                });
        } catch (Throwable $e) {
            $exception = $e;
            $log->error("PushRun {$run->id} fatal exception: ".$e->getMessage(), [
                'code_path' => $codePath,
                'file' => __FILE__.':'.__LINE__,
            ]);
        }

        $run->targeted_count = $targeted;
        $run->success_count = $success;
        $run->failure_count = $failure;
        $run->finished_at = now();
        $run->duration_ms = (int) max(0, round((microtime(true) - $executionStartedAt) * 1000));
        $run->sample_device_ids = array_slice($sampleDeviceIds, 0, 5);
        $run->sample_errors = array_slice($sampleErrors, 0, 5);
        $run->save();

        $log->info("PushRun {$run->id} END", [
            'targeted' => $targeted,
            'success' => $success,
            'failure' => $failure,
            'duration_ms' => $run->duration_ms,
            'job_id' => $jobId,
            'file' => __FILE__.':'.__LINE__,
        ]);

        if ($exception) {
            throw $exception;
        }

        return $run;
    }

    private function recordDeliveryFailuresFromException(
        PushRun $run,
        Collection $tokens,
        Throwable $exception,
        array &$sampleDeviceIds,
        array &$sampleErrors
    ): void {
        $now = now();
        $batchRows = [];
        foreach ($tokens as $deviceToken) {
            $deviceLabel = $deviceToken->device_id ?: 'device-token-'.$deviceToken->id;
            if (count($sampleDeviceIds) < 5) {
                $sampleDeviceIds[] = $deviceLabel;
            }
            if (count($sampleErrors) < 5) {
                $sampleErrors[] = Str::limit($exception->getMessage(), 180);
            }
            $batchRows[] = [
                'push_run_id' => $run->id,
                'device_id' => $deviceLabel,
                'platform' => $deviceToken->platform,
                'status' => 'failed',
                'error_code' => method_exists($exception, 'getCode') ? (string) $exception->getCode() : null,
                'error_message' => Str::limit($exception->getMessage(), 180),
                'fcm_response_snippet' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($batchRows)) {
            PushRunDelivery::insert($batchRows);
        }
    }

    private function logFilePath(): string
    {
        return storage_path('logs/push-'.now()->toDateString().'.log');
    }
}
