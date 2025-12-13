<?php

namespace App\Services\Notifications;

use App\Jobs\SendNotificationJob;
use App\Models\Appointment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AppointmentReminderService
{
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

        foreach ($appointments as $appointment) {
            $startTime = $this->resolveStartTime($appointment);
            if (! $startTime) {
                continue;
            }

            foreach ($this->reminders as $reminder) {
                $count += $this->handleReminder($appointment, $startTime, $reminder, $now);
            }
        }

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
        
        // Check if we're in the reminder window: now >= reminderAt AND now < startTime
        // Send reminder if current time is at or after reminder time, but before appointment start
        $isTooEarly = $now->lt($reminderAt);
        $isTooLate = $now->gte($startTime);
        
        if ($isTooEarly || $isTooLate) {
            Log::debug("Reminder not in window", [
                'appointment_id' => $appointment->id,
                'field' => $field,
                'now' => $now->toDateTimeString(),
                'reminder_at' => $reminderAt->toDateTimeString(),
                'start_time' => $startTime->toDateTimeString(),
                'minutes_until_reminder' => $now->diffInMinutes($reminderAt, false),
                'minutes_until_start' => $now->diffInMinutes($startTime, false),
                'is_too_early' => $isTooEarly,
                'is_too_late' => $isTooLate,
            ]);
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

        Log::info("Dispatching reminder", [
            'appointment_id' => $appointment->id,
            'field' => $field,
            'user_id' => $userId,
            'reminder_at' => $reminderAt->toDateTimeString(),
            'start_time' => $startTime->toDateTimeString(),
        ]);

        $notification = $this->createNotification($appointment, $startTime, $reminder, $userId);
        SendNotificationJob::dispatch($notification->id);

        $appointment->forceFill([$field => $now])->save();

        return 1;
    }

    protected function createNotification(Appointment $appointment, Carbon $startTime, array $reminder, int $userId): Notification
    {
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

        return Notification::create([
            'user_id' => $userId,
            'type' => 'consult_pre_reminder',
            'title' => $title,
            'body' => $body,
            'payload' => [
                'appointment_id' => $appointment->id,
                'clinic_id' => $appointment->vet_registeration_id,
                'doctor_id' => $appointment->doctor_id,
                'start_time' => $startTime->toIso8601String(),
                'offset_minutes' => $reminder['minutes'],
            ],
            'status' => Notification::STATUS_PENDING,
        ]);
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
}
