<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Api\ClinicFullOnboardingController;
use App\Models\Doctor;
use App\Models\FcmNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendClinicProfileCompletionNotifications extends Command
{
    protected $signature = 'notifications:clinic-profile-completion
        {--clinic_id= : Send only for one clinic}
        {--doctor_id= : Send only for one doctor}
        {--limit=500 : Maximum doctor tokens to process}
        {--min-interval-days=2 : Minimum days between reminders for the same clinic doctor}
        {--force : Send even when the clinic profile is already 100% complete}
        {--ignore-recent : Ignore the alternate-day throttle}
        {--dry : Calculate candidates without sending FCM}';

    protected $description = 'Send alternate-day clinic profile completion FCM nudges to doctors with saved FCM tokens.';

    public function handle(ClinicFullOnboardingController $controller): int
    {
        if (! Schema::hasTable('device_tokens')) {
            $this->warn('device_tokens table is not available.');
            Log::warning('clinic_profile_completion.scheduler.skip', [
                'reason' => 'device_tokens_table_missing',
            ]);

            return self::SUCCESS;
        }

        $clinicId = $this->option('clinic_id') ? (int) $this->option('clinic_id') : null;
        $doctorId = $this->option('doctor_id') ? (int) $this->option('doctor_id') : null;
        $limit = max(1, min((int) $this->option('limit'), 5000));
        $minIntervalDays = max(1, min((int) $this->option('min-interval-days'), 30));
        $force = (bool) $this->option('force');
        $ignoreRecent = (bool) $this->option('ignore-recent');
        $dryRun = (bool) $this->option('dry');
        $now = now();

        $query = Doctor::query()
            ->select(['doctors.id', 'doctors.vet_registeration_id'])
            ->join('device_tokens', 'device_tokens.user_id', '=', 'doctors.id')
            ->join('vet_registerations_temp', 'vet_registerations_temp.id', '=', 'doctors.vet_registeration_id')
            ->whereNotNull('doctors.vet_registeration_id')
            ->whereNotNull('device_tokens.token')
            ->where('device_tokens.token', '!=', '')
            ->distinct();

        if ($clinicId) {
            $query->where('doctors.vet_registeration_id', $clinicId);
        }

        if ($doctorId) {
            $query->where('doctors.id', $doctorId);
        }

        $doctors = $query
            ->orderBy('doctors.id')
            ->limit($limit)
            ->get();

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        Log::info('clinic_profile_completion.scheduler.start', [
            'candidate_count' => $doctors->count(),
            'clinic_id' => $clinicId,
            'doctor_id' => $doctorId,
            'limit' => $limit,
            'min_interval_days' => $minIntervalDays,
            'force' => $force,
            'ignore_recent' => $ignoreRecent,
            'dry' => $dryRun,
        ]);

        $this->info("Clinic profile completion candidates: {$doctors->count()}");

        foreach ($doctors as $doctor) {
            $currentClinicId = (int) $doctor->vet_registeration_id;
            $currentDoctorId = (int) $doctor->id;

            try {
                $payload = $this->fetchFullPayload($controller, $currentClinicId);
                $completionPercent = (int) data_get($payload, 'data.profile_completion.percentage', 0);
                $missingFields = collect(data_get($payload, 'data.profile_completion.missing_fields', []))
                    ->pluck('label')
                    ->filter()
                    ->values()
                    ->all();

                if ($dryRun) {
                    $lastSentAt = $this->lastSuccessfulReminderAt($currentClinicId, $currentDoctorId);
                    $eligibleAt = $lastSentAt?->copy()->addDays($minIntervalDays);
                    $throttled = $lastSentAt !== null && $eligibleAt !== null && $eligibleAt->greaterThan($now);
                    $this->line(sprintf(
                        'dry: clinic %d, doctor %d, completion %d%%, missing %d, last_sent %s, eligible %s',
                        $currentClinicId,
                        $currentDoctorId,
                        $completionPercent,
                        count($missingFields),
                        $lastSentAt?->toDateTimeString() ?? 'never',
                        $throttled ? $eligibleAt->toDateTimeString() : 'now'
                    ));
                    $skipped++;
                    continue;
                }

                if ($completionPercent >= 100 && ! $force) {
                    $this->line("skipped: clinic {$currentClinicId}, doctor {$currentDoctorId}, profile already complete");
                    $skipped++;
                    continue;
                }

                if (! $ignoreRecent && $this->recentReminderExists($currentClinicId, $currentDoctorId, $minIntervalDays, $now)) {
                    $lastSentAt = $this->lastSuccessfulReminderAt($currentClinicId, $currentDoctorId);
                    $eligibleAt = $lastSentAt?->copy()->addDays($minIntervalDays);
                    $this->line(sprintf(
                        'skipped: clinic %d, doctor %d, already reminded at %s%s',
                        $currentClinicId,
                        $currentDoctorId,
                        $lastSentAt?->toDateTimeString() ?? 'recently',
                        $eligibleAt ? ', eligible again at '.$eligibleAt->toDateTimeString() : ''
                    ));
                    $skipped++;
                    continue;
                }

                $request = Request::create(
                    "/api/vet-registerations/{$currentClinicId}/profile-completion-notification",
                    'POST',
                    [
                        'doctor_id' => $currentDoctorId,
                        'force' => $force,
                    ]
                );

                $response = $controller->sendProfileCompletionNotification($request, (string) $currentClinicId);
                $payload = $response->getData(true);

                if ($response->getStatusCode() >= 400 || ! ($payload['success'] ?? false)) {
                    $failed++;
                    $this->line("failed: clinic {$currentClinicId}, doctor {$currentDoctorId}, status {$response->getStatusCode()}, ".($payload['message'] ?? 'unknown error'));
                    Log::warning('clinic_profile_completion.scheduler.failed_candidate', [
                        'clinic_id' => $currentClinicId,
                        'doctor_id' => $currentDoctorId,
                        'status' => $response->getStatusCode(),
                        'message' => $payload['message'] ?? null,
                    ]);
                    continue;
                }

                if (($payload['sent'] ?? false) === true) {
                    $fcmSuccess = (int) data_get($payload, 'data.fcm_success', 0);
                    $fcmFailure = (int) data_get($payload, 'data.fcm_failure', 0);
                    $this->line("sent: clinic {$currentClinicId}, doctor {$currentDoctorId}, fcm_success {$fcmSuccess}, fcm_failure {$fcmFailure}");
                    $sent++;
                } else {
                    $this->line("skipped: clinic {$currentClinicId}, doctor {$currentDoctorId}, ".($payload['message'] ?? 'not sent'));
                    $skipped++;
                }
            } catch (Throwable $e) {
                $failed++;
                Log::error('clinic_profile_completion.scheduler.exception', [
                    'clinic_id' => $currentClinicId,
                    'doctor_id' => $currentDoctorId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('clinic_profile_completion.scheduler.finish', [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        $this->info("Clinic profile completion notifications finished. Sent: {$sent}, skipped: {$skipped}, failed: {$failed}.");

        return self::SUCCESS;
    }

    private function fetchFullPayload(ClinicFullOnboardingController $controller, int $clinicId): array
    {
        $response = $controller->show(Request::create("/api/vet-registerations/{$clinicId}/full", 'GET'), (string) $clinicId);
        $payload = $response->getData(true);

        return is_array($payload) ? $payload : [];
    }

    private function recentReminderExists(int $clinicId, int $doctorId, int $minIntervalDays, Carbon $now): bool
    {
        $lastSentAt = $this->lastSuccessfulReminderAt($clinicId, $doctorId);

        return $lastSentAt !== null && $lastSentAt->greaterThan($now->copy()->subDays($minIntervalDays));
    }

    private function lastSuccessfulReminderAt(int $clinicId, int $doctorId): ?Carbon
    {
        if (! Schema::hasTable('fcm_notifications')) {
            return null;
        }

        $query = FcmNotification::query()
            ->where('notification_type', 'clinic_profile_completion')
            ->where('status', 'sent')
            ->where('user_id', $doctorId)
            ->whereNotNull('sent_at');

        if (Schema::hasColumn('fcm_notifications', 'data_payload')) {
            $query->where(function ($payloadQuery) use ($clinicId, $doctorId) {
                $payloadQuery
                    ->where('data_payload->clinic_id', (string) $clinicId)
                    ->where('data_payload->doctor_id', (string) $doctorId);

                if (DB::connection()->getDriverName() === 'mysql') {
                    $payloadQuery->orWhere(function ($mysqlQuery) use ($clinicId, $doctorId) {
                        $mysqlQuery
                            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data_payload, '$.clinic_id')) = ?", [(string) $clinicId])
                            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data_payload, '$.doctor_id')) = ?", [(string) $doctorId]);
                    });
                }
            });
        }

        $lastSentAt = $query->max('sent_at');

        return $lastSentAt ? Carbon::parse($lastSentAt) : null;
    }
}
