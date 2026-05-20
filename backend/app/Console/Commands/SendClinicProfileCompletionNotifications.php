<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Api\ClinicFullOnboardingController;
use App\Models\Doctor;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendClinicProfileCompletionNotifications extends Command
{
    protected $signature = 'notifications:clinic-profile-completion
        {--clinic_id= : Send only for one clinic}
        {--doctor_id= : Send only for one doctor}
        {--limit=500 : Maximum doctor tokens to process}
        {--dry : Calculate candidates without sending FCM}';

    protected $description = 'Send clinic profile completion FCM nudges to doctors with saved FCM tokens.';

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
        $dryRun = (bool) $this->option('dry');

        $query = Doctor::query()
            ->select(['doctors.id', 'doctors.vet_registeration_id'])
            ->join('device_tokens', 'device_tokens.user_id', '=', 'doctors.id')
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
            'dry' => $dryRun,
        ]);

        foreach ($doctors as $doctor) {
            $currentClinicId = (int) $doctor->vet_registeration_id;
            $currentDoctorId = (int) $doctor->id;

            try {
                if ($dryRun) {
                    $payload = $this->fetchFullPayload($controller, $currentClinicId);
                    $completion = (int) data_get($payload, 'data.profile_completion_percentage', 0);
                    $this->line("dry: clinic {$currentClinicId}, doctor {$currentDoctorId}, completion {$completion}%");
                    $skipped++;
                    continue;
                }

                $request = Request::create(
                    "/api/vet-registerations/{$currentClinicId}/profile-completion-notification",
                    'POST',
                    ['doctor_id' => $currentDoctorId]
                );

                $response = $controller->sendProfileCompletionNotification($request, (string) $currentClinicId);
                $payload = $response->getData(true);

                if ($response->getStatusCode() >= 400 || ! ($payload['success'] ?? false)) {
                    $failed++;
                    Log::warning('clinic_profile_completion.scheduler.failed_candidate', [
                        'clinic_id' => $currentClinicId,
                        'doctor_id' => $currentDoctorId,
                        'status' => $response->getStatusCode(),
                        'message' => $payload['message'] ?? null,
                    ]);
                    continue;
                }

                if (($payload['sent'] ?? false) === true) {
                    $sent++;
                } else {
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
}
