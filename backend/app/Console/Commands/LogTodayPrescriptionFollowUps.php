<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PushController;
use App\Models\DeviceToken;
use App\Models\Prescription;
use App\Services\Push\FcmService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LogTodayPrescriptionFollowUps extends Command
{
    protected $signature = 'notifications:prescription-followups-today';

    protected $description = 'Send follow-up push notifications for prescriptions due today and log the results.';

    public function handle(): int
    {
        if (! Schema::hasColumn('prescriptions', 'follow_up_notification_sent_at')) {
            Log::error('prescriptions.follow_up_today.missing_column', [
                'column' => 'follow_up_notification_sent_at',
                'table' => 'prescriptions',
            ]);

            $this->error('Missing column prescriptions.follow_up_notification_sent_at. Add it before running this command.');

            return self::FAILURE;
        }

        $today = now()->toDateString();

        $query = Prescription::query()
            ->whereDate('follow_up_date', $today)
            ->whereNull('follow_up_notification_sent_at')
            ->select([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'medical_record_id',
                'follow_up_date',
                'follow_up_notification_sent_at',
            ]);

        $count = (clone $query)->count();

        Log::info('prescriptions.follow_up_today.run_start', [
            'date' => $today,
            'pending_count' => $count,
        ]);

        if ($count === 0) {
            $this->info("No prescriptions found with follow_up_date {$today}.");
            Log::info('prescriptions.follow_up_today.run_finish', [
                'date' => $today,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
            ]);

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(200, function ($prescriptions) use (&$sent, &$failed, &$skipped) {
            foreach ($prescriptions as $prescription) {
                $userId = (int) ($prescription->user_id ?? 0);
                if ($userId <= 0) {
                    Log::warning('prescriptions.follow_up_today.skipped_missing_user', [
                        'prescription_id' => $prescription->id,
                        'medical_record_id' => $prescription->medical_record_id,
                    ]);
                    $skipped++;
                    continue;
                }

                $tokens = DeviceToken::query()
                    ->where('user_id', $userId)
                    ->pluck('token')
                    ->filter()
                    ->values()
                    ->all();

                if (empty($tokens)) {
                    Log::warning('prescriptions.follow_up_today.skipped_no_tokens', [
                        'prescription_id' => $prescription->id,
                        'user_id' => $userId,
                    ]);
                    $skipped++;
                    continue;
                }

                $sendResult = $this->sendPushViaTestApi($prescription, $tokens);

                if ($sendResult['success_count'] <= 0) {
                    Log::error('prescriptions.follow_up_today.push_failed', [
                        'prescription_id' => $prescription->id,
                        'user_id' => $userId,
                        'token_count' => count($tokens),
                        'failure_count' => $sendResult['failure_count'],
                        'errors' => $sendResult['errors'],
                    ]);
                    $failed++;
                    continue;
                }

                $updated = Prescription::query()
                    ->whereKey($prescription->id)
                    ->whereNull('follow_up_notification_sent_at')
                    ->update([
                        'follow_up_notification_sent_at' => now(),
                    ]);

                if ($updated === 0) {
                    continue;
                }

                Log::info('prescriptions.follow_up_today.match', [
                    'prescription_id' => $prescription->id,
                    'medical_record_id' => $prescription->medical_record_id,
                    'doctor_id' => $prescription->doctor_id,
                    'user_id' => $prescription->user_id,
                    'pet_id' => $prescription->pet_id,
                    'follow_up_date' => optional($prescription->follow_up_date)->toDateString(),
                    'token_count' => count($tokens),
                    'success_count' => $sendResult['success_count'],
                    'failure_count' => $sendResult['failure_count'],
                    'follow_up_notification_sent_at' => now()->toDateTimeString(),
                ]);

                $sent++;
            }
        });

        Log::info('prescriptions.follow_up_today.run_finish', [
            'date' => $today,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);

        $this->info("Follow-up push run done for {$today}: sent={$sent}, failed={$failed}, skipped={$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Send push through the existing /api/push/test flow by directly calling the controller action.
     *
     * @return array{success_count:int,failure_count:int,errors:array<int,array<string,mixed>>}
     */
    private function sendPushViaTestApi(Prescription $prescription, array $tokens): array
    {
        $fcm = app(FcmService::class);
        $controller = app(PushController::class);

        $title = 'Follow-up Reminder';
        $body = 'Your pet follow-up consultation is scheduled for today.';

        $data = [
            'type' => 'follow_up_reminder',
            'prescription_id' => (string) $prescription->id,
            'medical_record_id' => (string) ($prescription->medical_record_id ?? ''),
            'pet_id' => (string) ($prescription->pet_id ?? ''),
            'follow_up_date' => (string) optional($prescription->follow_up_date)->toDateString(),
        ];

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($tokens as $token) {
            $request = Request::create('/api/push/test', 'POST', [
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            try {
                $response = $controller->testToToken($request, $fcm);
                $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 500;
                $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
                $ok = $status >= 200 && $status < 300 && (bool) ($payload['sent'] ?? $payload['success'] ?? false);

                if ($ok) {
                    $successCount++;
                    continue;
                }

                $failureCount++;
                $errors[] = [
                    'token' => $this->maskToken((string) $token),
                    'status' => $status,
                    'details' => $payload['error'] ?? $payload['details'] ?? 'unknown_error',
                ];
            } catch (Throwable $e) {
                $failureCount++;
                $errors[] = [
                    'token' => $this->maskToken((string) $token),
                    'status' => 500,
                    'details' => $e->getMessage(),
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => array_slice($errors, 0, 5),
        ];
    }

    private function maskToken(string $token): string
    {
        $length = strlen($token);
        if ($length <= 12) {
            return $token;
        }

        return substr($token, 0, 6).'...'.substr($token, -6);
    }
}
