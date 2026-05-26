<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Push\FcmService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PrescriptionMedicationReminderController extends Controller
{
    private const LOG_TYPE = 'cf_medication_reminder';

    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly FcmService $fcm,
    )
    {
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'dry_run' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
        ]);

        $prescription = Prescription::query()->find($id);
        if (!$prescription) {
            return response()->json([
                'success' => false,
                'error' => 'prescription_not_found',
            ], 404);
        }

        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $force = (bool) ($validated['force'] ?? false);

        if (!$force && $this->hasSentToday($prescription)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'sent' => false,
                    'skipped' => true,
                    'reason' => 'already_sent_today',
                    'prescription_id' => $prescription->id,
                ],
            ]);
        }

        $user = $prescription->user_id
            ? User::query()->select(['id', 'name', 'phone'])->find($prescription->user_id)
            : null;
        $pet = $prescription->pet_id ? Pet::query()->find($prescription->pet_id) : null;
        $doctor = $prescription->doctor_id ? Doctor::query()->find($prescription->doctor_id) : null;

        $medicineLines = $this->buildMedicationLines($prescription->medications_json);
        if ($medicineLines === []) {
            $this->logReminder($prescription, 'skipped', null, null, null, 'missing_medications_json');

            return response()->json([
                'success' => false,
                'error' => 'missing_medications_json',
            ], 422);
        }

        $medicineLines = $this->padMedicationLines($medicineLines);
        $doctorName = $this->cleanDoctorName($doctor?->doctor_name);
        $parentName = $this->cleanText($user?->name) ?: 'Pet Parent';
        $petName = $this->cleanText($pet?->name) ?: 'your pet';
        $template = config('services.whatsapp.templates.cf_medication_reminder', 'cf_medication_reminder');
        $language = config('services.whatsapp.templates.cf_medication_reminder_language', 'en');
        $fcmResult = $this->sendFcmReminder($prescription, $petName, $medicineLines, $dryRun);
        $to = $this->normalizeWhatsAppPhone($user?->phone);

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $medicineLines[0]],
                    ['type' => 'text', 'text' => $medicineLines[1]],
                    ['type' => 'text', 'text' => $medicineLines[2]],
                    ['type' => 'text', 'text' => $medicineLines[3]],
                ],
            ],
        ];

        if ($dryRun) {
            return response()->json([
                'success' => true,
                'data' => [
                    'sent' => false,
                    'dry_run' => true,
                    'template' => $template,
                    'language' => $language,
                    'to' => $to,
                    'prescription_id' => $prescription->id,
                    'medicine_lines' => $medicineLines,
                    'components' => $components,
                    'fcm' => $fcmResult,
                ],
            ]);
        }

        if (!$to) {
            $this->logReminder($prescription, $fcmResult['sent'] ? 'sent' : 'skipped', null, null, null, 'missing_patient_phone', [
                'channel' => 'fcm',
                'fcm' => $fcmResult,
            ]);

            return response()->json([
                'success' => (bool) $fcmResult['sent'],
                'error' => $fcmResult['sent'] ? null : 'missing_patient_phone',
                'data' => [
                    'sent' => (bool) $fcmResult['sent'],
                    'whatsapp_sent' => false,
                    'fcm_sent' => (bool) $fcmResult['sent'],
                    'fcm' => $fcmResult,
                    'prescription_id' => $prescription->id,
                    'medicine_lines' => $medicineLines,
                ],
            ], $fcmResult['sent'] ? 200 : 422);
        }

        if (!$this->whatsApp->isConfigured()) {
            $this->logReminder($prescription, $fcmResult['sent'] ? 'sent' : 'skipped', $to, $template, $language, 'whatsapp_not_configured', [
                'channel' => 'fcm',
                'fcm' => $fcmResult,
            ]);

            return response()->json([
                'success' => (bool) $fcmResult['sent'],
                'error' => 'whatsapp_not_configured',
                'data' => [
                    'sent' => (bool) $fcmResult['sent'],
                    'whatsapp_sent' => false,
                    'fcm_sent' => (bool) $fcmResult['sent'],
                    'fcm' => $fcmResult,
                    'prescription_id' => $prescription->id,
                    'medicine_lines' => $medicineLines,
                ],
            ], $fcmResult['sent'] ? 200 : 500);
        }

        try {
            $response = $this->whatsApp->sendTemplateWithResult(
                to: $to,
                template: $template,
                components: $components,
                language: $language,
                channelName: 'medication_reminder'
            );

            $this->logReminder($prescription, 'sent', $to, $template, $language, null, [
                'medicine_lines' => $medicineLines,
                'whatsapp_response' => $response,
                'fcm' => $fcmResult,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'sent' => true,
                    'whatsapp_sent' => true,
                    'fcm_sent' => (bool) $fcmResult['sent'],
                    'template' => $template,
                    'language' => $language,
                    'to' => $to,
                    'prescription_id' => $prescription->id,
                    'medicine_lines' => $medicineLines,
                    'whatsapp' => $response,
                    'fcm' => $fcmResult,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logReminder($prescription, $fcmResult['sent'] ? 'sent' : 'failed', $to, $template, $language, $e->getMessage(), [
                'medicine_lines' => $medicineLines,
                'channel' => $fcmResult['sent'] ? 'fcm' : 'whatsapp',
                'fcm' => $fcmResult,
            ]);

            Log::warning('prescriptions.medication_reminder_failed', [
                'prescription_id' => $prescription->id,
                'user_id' => $prescription->user_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => (bool) $fcmResult['sent'],
                'error' => 'send_failed',
                'message' => $e->getMessage(),
                'data' => [
                    'sent' => (bool) $fcmResult['sent'],
                    'whatsapp_sent' => false,
                    'fcm_sent' => (bool) $fcmResult['sent'],
                    'fcm' => $fcmResult,
                    'prescription_id' => $prescription->id,
                    'medicine_lines' => $medicineLines,
                ],
            ], $fcmResult['sent'] ? 200 : 500);
        }
    }

    private function sendFcmReminder(Prescription $prescription, string $petName, array $medicineLines, bool $dryRun): array
    {
        $userId = (int) ($prescription->user_id ?? 0);
        if ($userId <= 0 || !Schema::hasTable('device_tokens')) {
            return [
                'sent' => false,
                'success' => 0,
                'failure' => 0,
                'token_count' => 0,
                'reason' => $userId <= 0 ? 'missing_user_id' : 'missing_device_tokens_table',
            ];
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->whereNotNull('token')
            ->pluck('token')
            ->filter()
            ->map(fn ($token) => trim(trim((string) $token), "\"'"))
            ->filter(fn (string $token) => $token !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return [
                'sent' => false,
                'success' => 0,
                'failure' => 0,
                'token_count' => 0,
                'reason' => 'no_device_tokens',
            ];
        }

        $title = 'Medication Reminder';
        $body = 'Time for '.$petName.' medication: '.$medicineLines[0];
        $data = [
            'type' => 'medication_reminder',
            'prescription_id' => (string) $prescription->id,
            'medical_record_id' => (string) ($prescription->medical_record_id ?? ''),
            'pet_id' => (string) ($prescription->pet_id ?? ''),
            'follow_up_date' => (string) optional($prescription->follow_up_date)->toDateString(),
            'deepLink' => 'snoutiq://prescriptions/'.$prescription->id,
            'deep_link' => 'snoutiq://prescriptions/'.$prescription->id,
            'deeplink' => 'snoutiq://prescriptions/'.$prescription->id,
        ];

        if ($dryRun) {
            return [
                'sent' => false,
                'dry_run' => true,
                'token_count' => count($tokens),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ];
        }

        try {
            $result = $this->fcm->sendMulticast($tokens, $title, $body, $data);
            $success = (int) ($result['success'] ?? 0);
            $failure = (int) ($result['failure'] ?? max(0, count($tokens) - $success));

            return [
                'sent' => $success > 0,
                'success' => $success,
                'failure' => $failure,
                'token_count' => count($tokens),
                'errors' => collect($result['results'] ?? [])
                    ->filter(fn ($item) => ! (bool) ($item['ok'] ?? false))
                    ->map(fn ($item) => [
                        'code' => $item['code'] ?? null,
                        'error' => $item['error'] ?? null,
                    ])
                    ->values()
                    ->take(5)
                    ->all(),
            ];
        } catch (\Throwable $e) {
            Log::warning('prescriptions.medication_reminder_fcm_failed', [
                'prescription_id' => $prescription->id,
                'user_id' => $userId,
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'success' => 0,
                'failure' => count($tokens),
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
            ];
        }
    }

    private function buildMedicationLines($medications): array
    {
        if (is_string($medications)) {
            $decoded = json_decode($medications, true);
            $medications = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($medications)) {
            return [];
        }

        $lines = [];
        foreach ($medications as $medication) {
            if (is_string($medication)) {
                $line = $this->cleanText($medication);
            } elseif (is_array($medication)) {
                $line = $this->formatMedicationLine($medication);
            } else {
                $line = '';
            }

            if ($line !== '') {
                $lines[] = $this->limitText($line, 180);
            }
        }

        if (count($lines) > 4) {
            $extraCount = count($lines) - 3;
            $lines = array_slice($lines, 0, 3);
            $lines[] = 'Plus '.$extraCount.' more medicine'.($extraCount === 1 ? '' : 's').' in the prescription';
        }

        return $lines;
    }

    private function formatMedicationLine(array $medication): string
    {
        $name = $this->cleanText($medication['name'] ?? $medication['medicine'] ?? $medication['drug'] ?? '');
        $dose = $this->cleanText($medication['dose'] ?? $medication['dosage'] ?? '');
        $time = $this->formatMedicationTime($medication);

        $parts = array_values(array_filter([$name, $dose, $time], fn ($part) => $part !== ''));

        return implode(' | ', $parts);
    }

    private function formatMedicationTime(array $medication): string
    {
        $timings = $medication['timings'] ?? $medication['timing'] ?? $medication['time'] ?? null;
        if (is_array($timings)) {
            $timings = implode(', ', array_values(array_filter(array_map(fn ($value) => $this->cleanText((string) $value), $timings))));
        }

        $time = $this->cleanText((string) ($timings ?? ''));
        if ($time !== '') {
            return $time;
        }

        foreach (['frequency', 'duration', 'route', 'notes'] as $key) {
            $value = $this->cleanText((string) ($medication[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function padMedicationLines(array $lines): array
    {
        $lines = array_values(array_slice($lines, 0, 4));
        while (count($lines) < 4) {
            $lines[] = 'No additional medicine';
        }

        return $lines;
    }

    private function hasSentToday(Prescription $prescription): bool
    {
        if (!Schema::hasTable('vet_response_reminder_logs')) {
            return false;
        }

        return DB::table('vet_response_reminder_logs')
            ->whereJsonContains('meta->type', self::LOG_TYPE)
            ->whereJsonContains('meta->prescription_id', $prescription->id)
            ->where('status', 'sent')
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }

    private function normalizeWhatsAppPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '91'.substr($digits, 1);
        }

        return strlen($digits) >= 11 ? $digits : null;
    }

    private function cleanDoctorName(?string $name): string
    {
        $clean = $this->cleanText($name);
        $clean = preg_replace('/^dr\.?\s+/i', '', $clean) ?: $clean;

        return trim($clean) !== '' ? trim($clean) : 'Snoutiq';
    }

    private function cleanText(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?: '');
    }

    private function limitText(string $value, int $limit): string
    {
        return mb_strlen($value) > $limit
            ? mb_substr($value, 0, $limit - 3).'...'
            : $value;
    }

    private function logReminder(
        Prescription $prescription,
        string $status,
        ?string $phone,
        ?string $template,
        ?string $language,
        ?string $error,
        array $extraMeta = []
    ): void {
        if (!Schema::hasTable('vet_response_reminder_logs')) {
            return;
        }

        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => $prescription->user_id,
                'pet_id' => $prescription->pet_id,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode(array_merge([
                    'type' => self::LOG_TYPE,
                    'prescription_id' => $prescription->id,
                    'medical_record_id' => $prescription->medical_record_id,
                    'doctor_id' => $prescription->doctor_id,
                    'follow_up_date' => optional($prescription->follow_up_date)->toDateString(),
                ], $extraMeta)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('prescriptions.medication_reminder_log_failed', [
                'prescription_id' => $prescription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
