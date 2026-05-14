<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\HealthPulseEntry;
use App\Models\Notification;
use App\Models\Pet;
use Illuminate\Support\Facades\Schema;

class HealthPulseNotificationService
{
    public function sendAiFlagNotification(Pet $pet, HealthPulseEntry $entry): ?Notification
    {
        if (!Schema::hasTable('notifications') || !in_array($entry->ai_flag_level, ['Watch', 'Alert'], true)) {
            return null;
        }

        $petName = $pet->name ?: 'Your pet';
        $type = $entry->ai_flag_level === 'Alert' ? 'health_pulse_ai_alert' : 'health_pulse_ai_watch';
        $body = $entry->ai_flag_level === 'Alert'
            ? "{$petName}'s pulse has signs worth a vet check if they continue."
            : "{$petName}'s pulse has a change worth monitoring.";

        return $this->queuePush(
            userId: (int) $entry->user_id,
            petId: (int) $entry->pet_id,
            type: $type,
            title: 'Daily Health Pulse',
            body: $body,
            payload: [
                'pet_id' => (string) $entry->pet_id,
                'entry_id' => (string) $entry->id,
                'entry_date' => $entry->entry_date?->toDateString(),
                'flag_level' => $entry->ai_flag_level,
                'screen' => 'health_pulse',
            ]
        );
    }

    public function sendReminder(Pet $pet, string $trigger, string $title, string $body): ?Notification
    {
        if (!Schema::hasTable('notifications')) {
            return null;
        }

        $exists = Notification::query()
            ->where('user_id', $pet->user_id)
            ->where('pet_id', $pet->id)
            ->where('type', 'health_pulse_reminder')
            ->where('payload->trigger', $trigger)
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->queuePush(
            userId: (int) $pet->user_id,
            petId: (int) $pet->id,
            type: 'health_pulse_reminder',
            title: $title,
            body: $body,
            payload: [
                'trigger' => $trigger,
                'pet_id' => (string) $pet->id,
                'screen' => 'health_pulse',
            ]
        );
    }

    private function queuePush(int $userId, int $petId, string $type, string $title, string $body, array $payload): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'pet_id' => $petId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'status' => Notification::STATUS_PENDING,
            'channel' => Notification::CHANNEL_PUSH,
        ]);

        SendNotificationJob::dispatch($notification->id);

        return $notification;
    }
}
