<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Pet;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConsultationBookingWhatsAppService
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    public function sendExcelExportAssignmentNotifications(Transaction $transaction): array
    {
        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];

        if (! $this->isExcelExportCampaignTransaction($transaction, $metadata)) {
            return [
                'parent_whatsapp' => ['sent' => false, 'reason' => 'unsupported_transaction_type'],
                'vet_whatsapp' => ['sent' => false, 'reason' => 'unsupported_transaction_type'],
            ];
        }

        $context = [
            'channel_name' => $this->resolveChannelName($transaction, $metadata),
            'clinic_id' => $transaction->clinic_id ? (int) $transaction->clinic_id : $this->toNullableInt($metadata['clinic_id'] ?? null),
            'doctor_id' => $transaction->doctor_id ? (int) $transaction->doctor_id : $this->toNullableInt($metadata['doctor_id'] ?? null),
            'user_id' => $transaction->user_id ? (int) $transaction->user_id : $this->toNullableInt($metadata['user_id'] ?? null),
            'pet_id' => $transaction->pet_id ? (int) $transaction->pet_id : $this->toNullableInt($metadata['pet_id'] ?? null),
        ];

        $amountPaise = (int) ($transaction->actual_amount_paid_by_consumer_paise ?: $transaction->amount_paise ?: 0);
        $amountInInr = max(0, (int) round($amountPaise / 100));

        return [
            'parent_whatsapp' => $this->notifyExcelExportCampaignBooked(
                context: $context,
                notes: $metadata,
                amountInInr: $amountInInr
            ),
            'vet_whatsapp' => $this->notifyVetExcelExportCampaignAssigned(
                context: $context,
                notes: $metadata,
                amountInInr: $amountInInr
            ),
        ];
    }

    private function notifyExcelExportCampaignBooked(array $context, array $notes, int $amountInInr): array
    {
        if (! $this->whatsApp->isConfigured()) {
            return ['sent' => false, 'reason' => 'whatsapp_not_configured'];
        }

        try {
            $user = $context['user_id'] ? User::find($context['user_id']) : null;
            if (! $user || empty($user->phone)) {
                return ['sent' => false, 'reason' => 'user_or_phone_missing'];
            }

            $normalizedPhone = $this->normalizePhone($user->phone);
            if (! $normalizedPhone) {
                return ['sent' => false, 'reason' => 'user_phone_invalid'];
            }

            $doctorName = null;
            if ($context['doctor_id']) {
                $doctorName = Doctor::where('id', $context['doctor_id'])->value('doctor_name');
            }
            $doctorName = $this->sanitizeDoctorNameForWhatsApp($doctorName);

            $pet = $context['pet_id'] ? Pet::find($context['pet_id']) : null;
            $petName = $pet?->name ?: 'your pet';
            $petType = $pet?->pet_type ?? $pet?->type ?? 'Pet';

            $responseMinutes = (int) ($notes['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));
            $channelName = $context['channel_name'] ?? null;

            $components = [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $user->name ?: 'Pet Parent'],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $petType],
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => (string) $responseMinutes],
                    ['type' => 'text', 'text' => (string) $amountInInr],
                    ['type' => 'text', 'text' => $doctorName],
                ],
            ]];

            $this->whatsApp->sendTemplate(
                $normalizedPhone,
                'pp_booking_confirmed',
                $components,
                'en',
                $channelName
            );

            return [
                'sent' => true,
                'to' => $normalizedPhone,
                'template' => 'pp_booking_confirmed',
                'language' => 'en',
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    private function notifyVetExcelExportCampaignAssigned(array $context, array $notes, int $amountInInr): array
    {
        if (! $this->whatsApp->isConfigured()) {
            return ['sent' => false, 'reason' => 'whatsapp_not_configured'];
        }

        try {
            $doctorId = $context['doctor_id'] ?? null;
            if (! $doctorId) {
                return ['sent' => false, 'reason' => 'doctor_missing', 'doctor_id' => null];
            }

            $doctor = Doctor::find($doctorId);
            if (! $doctor) {
                return ['sent' => false, 'reason' => 'doctor_missing', 'doctor_id' => $doctorId];
            }

            $doctorPhone = $doctor->doctor_mobile ?? null;
            if (! $doctorPhone && isset($doctor->doctor_phone)) {
                $doctorPhone = $doctor->doctor_phone;
            }
            if (! $doctorPhone && isset($doctor->phone)) {
                $doctorPhone = $doctor->phone;
            }
            if (! $doctorPhone && $doctor->vet_registeration_id) {
                try {
                    $doctorPhone = DB::table('vet_registerations_temp')
                        ->where('id', $doctor->vet_registeration_id)
                        ->value('mobile');
                } catch (\Throwable) {
                    $doctorPhone = null;
                }
            }

            if (empty($doctorPhone)) {
                return ['sent' => false, 'reason' => 'doctor_phone_missing', 'doctor_id' => $doctorId];
            }

            $normalizedDoctorPhone = $this->normalizePhone($doctorPhone);
            if (! $normalizedDoctorPhone) {
                return ['sent' => false, 'reason' => 'doctor_phone_invalid', 'doctor_id' => $doctorId];
            }

            $user = $context['user_id'] ? User::find($context['user_id']) : null;
            $pet = $context['pet_id'] ? Pet::find($context['pet_id']) : null;

            $petName = $pet?->name ?? 'Pet';
            $species = $pet?->pet_type ?? $pet?->type ?? 'Pet';
            $breed = $pet?->breed ?? $species;

            $ageText = null;
            if ($pet?->pet_age !== null) {
                $ageText = $pet->pet_age . ' yrs';
            } elseif ($pet?->pet_age_months !== null) {
                $ageText = $pet->pet_age_months . ' months';
            } elseif ($pet?->pet_dob) {
                try {
                    $months = \Carbon\Carbon::parse($pet->pet_dob)->diffInMonths(now());
                    $ageText = $months >= 12 ? floor($months / 12) . ' yrs' : $months . ' months';
                } catch (\Throwable) {
                    $ageText = null;
                }
            }
            $ageText = $ageText ?: '-';

            $parentName = $user?->name ?? 'Pet Parent';
            $parentPhone = $user?->phone ?? 'N/A';
            $issue = $notes['issue'] ?? $notes['concern'] ?? $pet?->reported_symptom ?? 'N/A';
            $responseMinutes = (int) ($notes['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));
            $channelName = $context['channel_name'] ?? null;

            $requestedTemplate = strtolower(trim((string) ($notes['vet_template'] ?? '')));
            if (in_array($requestedTemplate, ['vet_new_video_consult', 'vet_new_consultation_assigned'], true)) {
                $requestedTemplate = 'appointment_confirmation_v2';
            }

            $configuredTemplate = strtolower(trim((string) (config('services.whatsapp.templates.vet_new_video_consult') ?? '')));
            if (in_array($configuredTemplate, ['vet_new_video_consult', 'vet_new_consultation_assigned'], true)) {
                $configuredTemplate = 'appointment_confirmation_v2';
            }

            $templateCandidates = array_values(array_unique(array_filter([
                $requestedTemplate ?: null,
                $configuredTemplate ?: null,
                'appointment_confirmation_v2',
                'vet_new_consultation_assigned',
            ])));

            $configuredLanguage = trim((string) (config('services.whatsapp.templates.vet_new_video_consult_language') ?? 'en'));
            $languageCandidates = array_values(array_unique(array_filter([
                $configuredLanguage !== '' ? $configuredLanguage : null,
                'en',
                'en_US',
            ])));

            $lastError = null;
            foreach ($templateCandidates as $template) {
                foreach ($languageCandidates as $language) {
                    try {
                        if ($template === 'vet_new_consultation_assigned') {
                            $userId = $context['user_id'] ?? null;
                            $petId = $context['pet_id'] ?? null;
                            $base = rtrim((string) config('app.url'), '/');
                            if (! str_ends_with($base, '/backend')) {
                                $base .= '/backend';
                            }
                            $mediaString = $base . '/api/consultation/prescription/pdf?user_id=' . ($userId ?? '0') . '&pet_id=' . ($petId ?? '0');

                            $components = [[
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $this->sanitizeDoctorNameForWhatsApp($doctor->doctor_name)],
                                    ['type' => 'text', 'text' => $petName],
                                    ['type' => 'text', 'text' => $breed],
                                    ['type' => 'text', 'text' => $parentName],
                                    ['type' => 'text', 'text' => $parentPhone],
                                    ['type' => 'text', 'text' => $issue],
                                    ['type' => 'text', 'text' => $mediaString],
                                    ['type' => 'text', 'text' => (string) $responseMinutes],
                                ],
                            ]];
                        } else {
                            $components = $this->buildVetTemplateComponents(
                                template: $template,
                                doctorName: $this->sanitizeDoctorNameForWhatsApp($doctor->doctor_name),
                                parentName: $parentName,
                                petName: $petName,
                                breed: $breed,
                                species: $species,
                                ageText: $ageText,
                                issue: $issue,
                                amountInInr: $amountInInr,
                                responseMinutes: $responseMinutes
                            );
                        }

                        $this->whatsApp->sendTemplate(
                            $normalizedDoctorPhone,
                            $template,
                            $components,
                            $language,
                            $channelName
                        );

                        return [
                            'sent' => true,
                            'to' => $normalizedDoctorPhone,
                            'template' => $template,
                            'language' => $language,
                            'doctor_id' => $doctorId,
                        ];
                    } catch (\RuntimeException $ex) {
                        $lastError = $ex->getMessage();
                    }
                }
            }

            return [
                'sent' => false,
                'reason' => 'template_failed',
                'doctor_id' => $doctorId,
                'to' => $normalizedDoctorPhone,
                'message' => $lastError ?: 'No WhatsApp template candidate succeeded',
                'attempted_templates' => $templateCandidates,
                'attempted_languages' => $languageCandidates,
            ];
        } catch (\Throwable $e) {
            report($e);

            return ['sent' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    private function buildVetTemplateComponents(
        string $template,
        string $doctorName,
        string $parentName,
        string $petName,
        string $breed,
        string $species,
        string $ageText,
        string $issue,
        int $amountInInr,
        int $responseMinutes
    ): array {
        $templateKey = strtolower(trim($template));

        if ($templateKey === 'appointment_confirmation_v2') {
            return [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $breed],
                ],
            ]];
        }

        if ($templateKey === 'vet_sla_reminder') {
            return [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $doctorName],
                    ['type' => 'text', 'text' => $parentName],
                    ['type' => 'text', 'text' => $petName],
                    ['type' => 'text', 'text' => $breed],
                    ['type' => 'text', 'text' => (string) $responseMinutes],
                ],
            ]];
        }

        return [[
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $petName],
                ['type' => 'text', 'text' => $species],
                ['type' => 'text', 'text' => $ageText],
                ['type' => 'text', 'text' => $parentName],
                ['type' => 'text', 'text' => $issue],
                ['type' => 'text', 'text' => (string) $amountInInr],
                ['type' => 'text', 'text' => (string) $responseMinutes],
            ],
        ]];
    }

    private function resolveChannelName(Transaction $transaction, array $metadata): ?string
    {
        $candidate = trim((string) (
            $transaction->channel_name
            ?? $metadata['channel_name']
            ?? $metadata['call_session_id']
            ?? $metadata['call_session']
            ?? ''
        ));

        if ($candidate !== '') {
            return $candidate;
        }

        if (! $transaction->user_id || ! $transaction->doctor_id) {
            return null;
        }

        try {
            $row = DB::table('call_sessions')
                ->where('patient_id', (int) $transaction->user_id)
                ->where('doctor_id', (int) $transaction->doctor_id)
                ->whereNotNull('channel_name')
                ->where('channel_name', '!=', '')
                ->orderByDesc('id')
                ->value('channel_name');

            return is_string($row) && trim($row) !== '' ? trim($row) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePhone(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits) {
            return null;
        }

        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        return $digits;
    }

    private function sanitizeDoctorNameForWhatsApp(?string $doctorName): string
    {
        $name = trim((string) $doctorName);
        if ($name === '') {
            return 'Doctor';
        }

        $sanitized = preg_replace('/^\s*dr\.?\s+/i', '', $name);
        $sanitized = trim((string) $sanitized);

        return $sanitized !== '' ? $sanitized : 'Doctor';
    }

    private function isExcelExportCampaignTransaction(Transaction $transaction, array $metadata): bool
    {
        $type = $this->normalizeOrderType($transaction->type ?? null);
        $orderType = $this->normalizeOrderType($metadata['order_type'] ?? null);

        return $type === 'excell_export_campaign' || $orderType === 'excell_export_campaign';
    }

    private function normalizeOrderType(?string $orderType): ?string
    {
        if (! is_string($orderType)) {
            return null;
        }

        $trimmed = trim($orderType);
        if ($trimmed === '') {
            return null;
        }

        $normalized = strtolower(str_replace(['-', ' '], '_', $trimmed));

        return match ($normalized) {
            'excel_export_campaign' => 'excell_export_campaign',
            default => $normalized,
        };
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
