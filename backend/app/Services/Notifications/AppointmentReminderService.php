<?php

namespace App\Services\Notifications;

use App\Models\Appointment;
use App\Models\DeviceToken;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\Push\FcmService;

class AppointmentReminderService
{
    public function __construct(private readonly FcmService $fcm)
    {
    }
    /**
     * Reminder offsets in minutes.
     *
     * @var array<int,array<string,mixed>>
     */
    private array $reminders = [
        ['field' => 'reminder_24h_sent_at', 'minutes' => 24 * 60, 'label' => '24 hours'],
        ['field' => 'reminder_3h_sent_at', 'minutes' => 3 * 60, 'label' => '3 hours'],
        ['field' => 'reminder_30m_sent_at', 'minutes' => 30, 'label' => '30 minutes'],
    ];

    public function dispatch(): int
    {
        $appointments = $this->upcomingAppointments();
        $now = now();
        $count = 0;
        $buckets = [
            '24h' => ['threshold' => 24 * 60, 'appointments' => []],
            '3h' => ['threshold' => 3 * 60, 'appointments' => []],
            '30m' => ['threshold' => 30, 'appointments' => []],
        ];

        Log::info('Reminder run started', [
            'appointments_to_check' => $appointments->count(),
            'at' => $now->toDateTimeString(),
        ]);

        foreach ($appointments as $appointment) {
            $startTime = $this->resolveStartTime($appointment);
            if (! $startTime) {
                continue;
            }

            $minutesUntilStart = $now->diffInMinutes($startTime, false);
            if ($minutesUntilStart >= 0) {
                foreach ($buckets as $label => &$bucket) {
                    if ($minutesUntilStart <= $bucket['threshold']) {
                        $bucket['appointments'][] = [
                            'id' => $appointment->id,
                            'start_time' => $startTime->toDateTimeString(),
                            'minutes_until_start' => $minutesUntilStart,
                        ];
                    }
                }
                unset($bucket);
            }

            foreach ($this->reminders as $reminder) {
                $count += $this->handleReminder($appointment, $startTime, $reminder, $now);
            }
        }

        Log::info('Reminder run finished', [
            'dispatched' => $count,
            'at' => now()->toDateTimeString(),
        ]);

        Log::info('Reminder buckets summary', [
            '24h' => count($buckets['24h']['appointments']),
            '3h' => count($buckets['3h']['appointments']),
            '30m' => count($buckets['30m']['appointments']),
        ]);

        return $count;
    }

    protected function upcomingAppointments(): Collection
    {
        $now = now()->copy()->subDay(); // allow previously missed ones

        return Appointment::query()
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->whereDate('appointment_date', '>=', $now->toDateString())
            ->whereNull('reminder_30m_sent_at')
            ->orderBy('appointment_date')
            ->get();
    }

    protected function handleReminder(Appointment $appointment, Carbon $startTime, array $reminder, Carbon $now): int
    {
        $field = $reminder['field'];
        if ($appointment->{$field}) {
            Log::debug("Reminder already sent", [
                'appointment_id' => $appointment->id,
                'field' => $field,
                'sent_at' => $appointment->{$field},
            ]);
            return 0;
        }

        $reminderAt = $startTime->copy()->subMinutes($reminder['minutes']);
        $minutesUntilStart = $now->diffInMinutes($startTime, false);
        $minutesUntilReminder = $now->diffInMinutes($reminderAt, false);

        // Check if we're in the reminder window: now >= reminderAt AND now < startTime
        // Send reminder if current time is at or after reminder time, but before appointment start
        $isTooEarly = $now->lt($reminderAt);
        $isTooLate = $now->gte($startTime);
        
        if ($isTooEarly || $isTooLate) {
            return 0;
        }

        $userId = $this->resolvePatientUserId($appointment);
        if (! $userId) {
            Log::warning("Cannot send reminder: patient_user_id not found", [
                'appointment_id' => $appointment->id,
                'field' => $field,
                'notes' => $appointment->notes,
            ]);
            return 0;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        if (empty($tokens)) {
            Log::warning('Reminder push skipped; no tokens', [
                'appointment_id' => $appointment->id,
                'field' => $field,
                'user_id' => $userId,
            ]);
            return 0;
        }

        $payload = [
            'appointment_id' => $appointment->id,
            'clinic_id' => $appointment->vet_registeration_id,
            'doctor_id' => $appointment->doctor_id,
            'start_time' => $startTime->toIso8601String(),
            'offset_minutes' => $reminder['minutes'],
        ];

        $label = $reminder['label'];
        $clinicName = $appointment->clinic->name ?? $this->extractFromNotes($appointment, 'clinic_name') ?? 'your clinic';
        $doctorName = $appointment->doctor->name ?? $this->extractFromNotes($appointment, 'doctor_name') ?? 'your vet';

        $title = sprintf('Consultation in %s', $label);
        $body = sprintf(
            'Hi! Your consultation with %s at %s is scheduled for %s. Please be ready.',
            $doctorName,
            $clinicName,
            $startTime->timezone(config('app.timezone'))->format('d M Y h:i A')
        );

        $success = 0;
        $errors = [];
        foreach ($tokens as $token) {
            try {
                $this->fcm->sendToToken(
                    $token,
                    $title,
                    $body,
                    array_merge(['type' => 'consult_pre_reminder'], $this->stringifyPayload($payload))
                );
                $success++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'token' => $token,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Reminder push results', [
            'appointment_id' => $appointment->id,
            'field' => $field,
            'user_id' => $userId,
            'token_count' => count($tokens),
            'success' => $success,
            'errors' => $errors,
        ]);

        if ($success <= 0) {
            return 0;
        }

        $appointment->forceFill([$field => $now])->save();

        return 1;
    }

    protected function resolveStartTime(Appointment $appointment): ?Carbon
    {
        if (! $appointment->appointment_date || ! $appointment->appointment_time) {
            return null;
        }

        $dateTime = $appointment->appointment_date.' '.$appointment->appointment_time;

        try {
            return Carbon::createFromFormat('Y-m-d H:i', $dateTime, config('app.timezone', 'UTC'));
        } catch (\Exception) {
            return null;
        }
    }

    protected function resolvePatientUserId(Appointment $appointment): ?int
    {
        if (! empty($appointment->patient_user_id)) {
            return (int) $appointment->patient_user_id;
        }

        $notesUserId = $this->extractFromNotes($appointment, 'patient_user_id');
        return $notesUserId ? (int) $notesUserId : null;
    }

    protected function extractFromNotes(Appointment $appointment, string $key): mixed
    {
        $notes = $appointment->notes;
        if (! $notes) {
            return null;
        }

        $decoded = json_decode($notes, true);
        if (! is_array($decoded)) {
            return null;
        }

        return $decoded[$key] ?? null;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,string>
     */
    private function stringifyPayload(array $payload): array
    {
        $stringPayload = [];
        foreach ($payload as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $stringPayload[(string) $key] = json_encode($value);
                continue;
            }
            $stringPayload[(string) $key] = (string) $value;
        }

        return $stringPayload;
    }
}
