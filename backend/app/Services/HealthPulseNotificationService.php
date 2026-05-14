<?php

namespace App\Services;

use App\Models\FcmNotification;
use App\Models\HealthPulseEntry;
use App\Models\Pet;
use App\Services\Push\FcmService;
use Illuminate\Support\Facades\Schema;

class HealthPulseNotificationService
{
    public function __construct(private readonly FcmService $fcm)
    {
    }

    public function sendAiFlagNotification(Pet $pet, HealthPulseEntry $entry): bool
    {
        if (!in_array($entry->ai_flag_level, ['Watch', 'Alert'], true)) {
            return false;
        }

        $petName = $pet->name ?: 'Your pet';
        $type = $entry->ai_flag_level === 'Alert' ? 'health_pulse_ai_alert' : 'health_pulse_ai_watch';
        $body = $entry->ai_flag_level === 'Alert'
            ? "{$petName}'s pulse has signs worth a vet check if they continue."
            : "{$petName}'s pulse has a change worth monitoring.";

        $this->sendPush(
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

        return true;
    }

    public function sendReminder(Pet $pet, string $trigger, string $title, string $body): bool
    {
        if ($this->reminderAlreadySent((int) $pet->user_id, (int) $pet->id, $trigger)) {
            return false;
        }

        $this->sendPush(
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

        return true;
    }

    private function sendPush(int $userId, int $petId, string $type, string $title, string $body, array $payload): void
    {
        $data = array_merge($payload, [
            'type' => $type,
            'notification_type' => $type,
            'pet_id' => (string) $petId,
            'user_id' => (string) $userId,
        ]);

        $this->fcm->notifyUser($userId, $title, $body, $data);
    }

    private function reminderAlreadySent(int $userId, int $petId, string $trigger): bool
    {
        if (!Schema::hasTable('fcm_notifications')) {
            return false;
        }

        return FcmNotification::query()
            ->where('user_id', $userId)
            ->where('notification_type', 'health_pulse_reminder')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data_payload, '$.pet_id')) = ?", [(string) $petId])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data_payload, '$.trigger')) = ?", [$trigger])
            ->exists();
    }
}
