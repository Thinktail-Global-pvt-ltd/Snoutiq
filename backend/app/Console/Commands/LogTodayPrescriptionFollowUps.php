<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PushController;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use App\Services\Push\FcmService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LogTodayPrescriptionFollowUps extends Command
{
    private const WHATSAPP_LOG_TYPE = 'prescription_follow_up_whatsapp';

    protected $signature = 'notifications:prescription-followups-today';

    protected $description = 'Send follow-up push and WhatsApp reminders for prescriptions.';

    public function __construct(private readonly WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

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

        $now = now('Asia/Kolkata');
        $today = $now->toDateString();

        $query = Prescription::query()
            ->whereDate('follow_up_date', $today)
            ->whereNull('follow_up_notification_sent_at')
            ->select([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'medical_record_id',
                'call_session',
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
            Log::info('prescriptions.follow_up_today.push_no_candidates', [
                'date' => $today,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
            ]);
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        if ($count > 0) {
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
        }

        $whatsApp = $this->sendWhatsAppFollowUpReminders($now);

        Log::info('prescriptions.follow_up_today.run_finish', [
            'date' => $today,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'whatsapp_sent' => $whatsApp['sent'],
            'whatsapp_failed' => $whatsApp['failed'],
            'whatsapp_skipped' => $whatsApp['skipped'],
        ]);

        $this->info("Follow-up run done for {$today}: push_sent={$sent}, push_failed={$failed}, push_skipped={$skipped}, whatsapp_sent={$whatsApp['sent']}, whatsapp_failed={$whatsApp['failed']}, whatsapp_skipped={$whatsApp['skipped']}.");

        return self::SUCCESS;
    }

    /**
     * WhatsApp follows the same prescription follow-up source as push.
     * The form currently stores only a date, so the final reminder is sent on the follow-up day.
     *
     * @return array{sent:int,failed:int,skipped:int}
     */
    private function sendWhatsAppFollowUpReminders(Carbon $now): array
    {
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        if (! $this->whatsApp->isConfigured()) {
            Log::warning('prescriptions.follow_up.whatsapp_not_configured');
            return $stats;
        }

        if (! Schema::hasTable('vet_response_reminder_logs')) {
            Log::warning('prescriptions.follow_up.whatsapp_missing_log_table');
            return $stats;
        }

        $targets = [
            'two_days_before' => $now->copy()->addDays(2)->toDateString(),
            'due_day' => $now->toDateString(),
        ];

        foreach ($targets as $trigger => $followUpDate) {
            $query = Prescription::query()
                ->whereDate('follow_up_date', $followUpDate)
                ->select($this->prescriptionFollowUpColumns());

            if (Schema::hasColumn('prescriptions', 'follow_up_required')) {
                $query->where(function ($q) {
                    $q->where('follow_up_required', true)
                        ->orWhereNull('follow_up_required');
                });
            }

            $query->orderBy('id')->chunkById(100, function ($prescriptions) use ($trigger, &$stats) {
                foreach ($prescriptions as $prescription) {
                    if ($this->hasLoggedWhatsAppFollowUp((int) $prescription->id, $trigger, ['sent'])) {
                        $stats['skipped']++;
                        continue;
                    }

                    $context = $this->resolveWhatsAppContext($prescription);
                    if (!$context['phone']) {
                        if (! $this->hasLoggedWhatsAppFollowUp((int) $prescription->id, $trigger, ['skipped'])) {
                            $this->logWhatsAppFollowUp($prescription, $trigger, 'skipped', null, null, null, 'missing_user_phone', $context);
                        }
                        $stats['skipped']++;
                        continue;
                    }

                    $templateType = $this->isClinicFollowUp($prescription->follow_up_type ?? null) ? 'clinic' : 'video';
                    $template = $templateType === 'clinic'
                        ? config('services.whatsapp.templates.cf_followup_reminder_clinic', 'cf_followup_reminder_clinic')
                        : config('services.whatsapp.templates.cf_followup_reminder_video', 'cf_followup_reminder_video');
                    $language = $templateType === 'clinic'
                        ? config('services.whatsapp.templates.cf_followup_reminder_clinic_language', 'en')
                        : config('services.whatsapp.templates.cf_followup_reminder_video_language', 'en');

                    try {
                        $this->whatsApp->sendTemplate(
                            $context['phone'],
                            $template,
                            $this->buildWhatsAppComponents($templateType, $context),
                            $language,
                            'prescription_follow_up_reminder'
                        );

                        $this->logWhatsAppFollowUp($prescription, $trigger, 'sent', $context['phone'], $template, $language, null, $context);
                        $stats['sent']++;
                    } catch (\RuntimeException $e) {
                        Log::error('prescriptions.follow_up.whatsapp_failed', [
                            'prescription_id' => $prescription->id,
                            'trigger' => $trigger,
                            'template' => $template,
                            'error' => $e->getMessage(),
                        ]);

                        $this->logWhatsAppFollowUp($prescription, $trigger, 'failed', $context['phone'], $template, $language, $e->getMessage(), $context);
                        $stats['failed']++;
                    }
                }
            });
        }

        return $stats;
    }

    private function prescriptionFollowUpColumns(): array
    {
        $columns = [
            'id',
            'doctor_id',
            'user_id',
            'pet_id',
            'medical_record_id',
            'follow_up_date',
        ];

        foreach (['follow_up_type', 'call_session', 'video_appointment_id', 'in_clinic_appointment_id'] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveWhatsAppContext(Prescription $prescription): array
    {
        $user = $prescription->user_id ? User::query()->select('id', 'name', 'phone', 'last_vet_id')->find($prescription->user_id) : null;
        $pet = $prescription->pet_id ? Pet::query()->select('id', 'name', 'user_id')->find($prescription->pet_id) : null;
        if (!$pet && $user) {
            $pet = Pet::query()->select('id', 'name', 'user_id')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->first();
        }

        $record = $prescription->medical_record_id
            ? MedicalRecord::query()->select('id', 'doctor_id', 'vet_registeration_id')->find($prescription->medical_record_id)
            : null;

        $doctorId = $prescription->doctor_id ?: ($record?->doctor_id);
        $doctor = $doctorId ? Doctor::query()->select('id', 'doctor_name', 'vet_registeration_id')->find($doctorId) : null;

        $clinicId = $record?->vet_registeration_id
            ?: $doctor?->vet_registeration_id
            ?: ($user?->last_vet_id);
        $clinic = $clinicId ? VetRegisterationTemp::query()
            ->select('id', 'name', 'address', 'formatted_address', 'city', 'pincode')
            ->find($clinicId) : null;

        $appointment = $this->resolveInClinicAppointment($prescription);
        $date = $this->formatAppointmentDate($prescription->follow_up_date);
        $time = trim((string) ($appointment?->appointment_time ?? '')) ?: 'To be confirmed';
        $phone = $this->normalizeWhatsAppPhone((string) ($user?->phone ?? ''));
        $doctorName = $this->sanitizeDoctorName($doctor?->doctor_name);

        return [
            'phone' => $phone,
            'doctor_name' => $doctorName,
            'parent_name' => trim((string) ($user?->name ?? 'Pet Parent')) ?: 'Pet Parent',
            'pet_name' => trim((string) ($pet?->name ?? 'your pet')) ?: 'your pet',
            'appointment_date' => $date,
            'appointment_time' => $time,
            'clinic' => $this->formatClinicNameAndAddress($clinic),
            'clinic_id' => $clinicId,
            'user_id' => $user?->id,
            'pet_id' => $pet?->id,
            'doctor_id' => $doctor?->id,
        ];
    }

    private function resolveInClinicAppointment(Prescription $prescription): ?object
    {
        $appointmentId = (int) ($prescription->in_clinic_appointment_id ?? 0);
        if ($appointmentId <= 0 || ! Schema::hasTable('appointments')) {
            return null;
        }

        $columns = ['id'];
        foreach (['appointment_date', 'appointment_time'] as $column) {
            if (Schema::hasColumn('appointments', $column)) {
                $columns[] = $column;
            }
        }

        return DB::table('appointments')->select($columns)->where('id', $appointmentId)->first();
    }

    private function buildWhatsAppComponents(string $templateType, array $context): array
    {
        if ($templateType === 'clinic') {
            $parameters = [
                ['type' => 'text', 'text' => $context['doctor_name']],
                ['type' => 'text', 'text' => $context['parent_name']],
                ['type' => 'text', 'text' => $context['pet_name']],
                ['type' => 'text', 'text' => $context['appointment_date']],
                ['type' => 'text', 'text' => $context['appointment_time']],
                ['type' => 'text', 'text' => $context['clinic']],
            ];
        } else {
            $parameters = [
                ['type' => 'text', 'text' => $context['doctor_name']],
                ['type' => 'text', 'text' => $context['parent_name']],
                ['type' => 'text', 'text' => $context['pet_name']],
                ['type' => 'text', 'text' => $context['doctor_name']],
                ['type' => 'text', 'text' => $context['appointment_date']],
            ];
        }

        return [
            [
                'type' => 'body',
                'parameters' => $parameters,
            ],
        ];
    }

    private function hasLoggedWhatsAppFollowUp(int $prescriptionId, string $trigger, array $statuses): bool
    {
        return DB::table('vet_response_reminder_logs')
            ->whereIn('status', $statuses)
            ->whereJsonContains('meta->type', self::WHATSAPP_LOG_TYPE)
            ->whereJsonContains('meta->prescription_id', $prescriptionId)
            ->whereJsonContains('meta->trigger', $trigger)
            ->exists();
    }

    private function logWhatsAppFollowUp(
        Prescription $prescription,
        string $trigger,
        string $status,
        ?string $phone,
        ?string $template,
        ?string $language,
        ?string $error,
        array $context
    ): void {
        DB::table('vet_response_reminder_logs')->insert([
            'transaction_id' => null,
            'user_id' => $prescription->user_id,
            'pet_id' => $prescription->pet_id ?: ($context['pet_id'] ?? null),
            'phone' => $phone,
            'template' => $template,
            'language' => $language,
            'status' => $status,
            'error' => $error,
            'meta' => json_encode([
                'type' => self::WHATSAPP_LOG_TYPE,
                'trigger' => $trigger,
                'prescription_id' => (int) $prescription->id,
                'medical_record_id' => $prescription->medical_record_id,
                'follow_up_date' => optional($prescription->follow_up_date)->toDateString(),
                'follow_up_type' => $prescription->follow_up_type,
                'doctor_id' => $context['doctor_id'] ?? $prescription->doctor_id,
                'clinic_id' => $context['clinic_id'] ?? null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isClinicFollowUp(?string $followUpType): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', trim((string) $followUpType)));

        return str_contains($normalized, 'clinic');
    }

    private function formatAppointmentDate($value): string
    {
        if (!$value) {
            return 'To be confirmed';
        }

        try {
            return Carbon::parse($value, 'Asia/Kolkata')->format('d M Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function formatClinicNameAndAddress(?VetRegisterationTemp $clinic): string
    {
        if (!$clinic) {
            return 'Snoutiq clinic';
        }

        $parts = array_filter([
            trim((string) ($clinic->name ?? '')),
            trim((string) ($clinic->formatted_address ?? $clinic->address ?? '')),
            trim((string) ($clinic->city ?? '')),
            trim((string) ($clinic->pincode ?? '')),
        ]);

        return $parts ? implode(', ', array_unique($parts)) : 'Snoutiq clinic';
    }

    private function normalizeWhatsAppPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (strlen($digits) === 10) {
            return '91'.$digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }

        return strlen($digits) >= 11 ? $digits : null;
    }

    private function sanitizeDoctorName(?string $doctorName): string
    {
        $name = trim((string) $doctorName);
        if ($name === '') {
            return 'Doctor';
        }

        $name = preg_replace('/^\s*dr\.?\s+/i', '', $name) ?: $name;
        $name = trim($name);

        return $name !== '' ? $name : 'Doctor';
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
            'deepLink' => 'snoutiq://videocall-appointment',
            'deep_link' => 'snoutiq://videocall-appointment',
            'deeplink' => 'snoutiq://videocall-appointment',
        ];

        $callSession = trim((string) ($prescription->call_session ?? ''));
        if ($callSession !== '') {
            $data['call_session'] = $callSession;
        }

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
