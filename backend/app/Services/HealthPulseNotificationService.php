<?php

namespace App\Services;

use App\Http\Controllers\Api\PushController;
use App\Models\DeviceToken;
use App\Models\FcmNotification;
use App\Models\HealthPulseEntry;
use App\Models\Pet;
use App\Models\User;
use App\Services\Push\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class HealthPulseNotificationService
{
    public function __construct(private readonly FcmService $fcm)
    {
    }

    public function sendAiFlagNotification(
        Pet $pet,
        HealthPulseEntry $entry,
        ?string $explicitToken = null,
        ?string $userName = null
    ): bool {
        if (!in_array($entry->ai_flag_level, ['Watch', 'Alert'], true)) {
            return false;
        }

        $petName = $pet->name ?: 'Your pet';
        $type = $entry->ai_flag_level === 'Alert' ? 'health_pulse_ai_alert' : 'health_pulse_ai_watch';
        $body = $entry->ai_flag_level === 'Alert'
            ? "{$petName}'s pulse has signs worth a vet check if they continue."
            : "{$petName}'s pulse has a change worth monitoring.";

        return $this->sendPush(
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
            ],
            explicitToken: $explicitToken,
            userName: $userName
        );
    }

    public function sendReminder(Pet $pet, string $trigger, string $title, string $body): bool
    {
        if ($this->reminderAlreadySent((int) $pet->user_id, (int) $pet->id, $trigger)) {
            return false;
        }

        return $this->sendPush(
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

    private function sendPush(
        int $userId,
        int $petId,
        string $type,
        string $title,
        string $body,
        array $payload,
        ?string $explicitToken = null,
        ?string $userName = null
    ): bool {
        $data = array_merge($payload, [
            'type' => $type,
            'notification_type' => $type,
            'pet_id' => (string) $petId,
            'user_id' => (string) $userId,
        ]);

        $tokens = $this->tokensForUser($userId, $explicitToken, $userName);
        if (empty($tokens)) {
            Log::warning('health_pulse.push_skipped_no_tokens', [
                'user_id' => $userId,
                'pet_id' => $petId,
                'type' => $type,
            ]);

            return false;
        }

        $sent = 0;
        foreach ($tokens as $token) {
            $request = Request::create('/api/push/test', 'POST', [
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            try {
                app(PushController::class)->testToToken($request, $this->fcm);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('health_pulse.push_failed', [
                    'user_id' => $userId,
                    'pet_id' => $petId,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent > 0;
    }

    private function tokensForUser(int $userId, ?string $explicitToken, ?string $userName = null): array
    {
        $explicitToken = trim((string) $explicitToken);
        if ($explicitToken !== '') {
            return [$explicitToken];
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        if (!empty($tokens)) {
            return $tokens;
        }

        $resolvedUserId = $this->resolveUserIdByName($userName);
        if ($resolvedUserId === null || $resolvedUserId === $userId) {
            return [];
        }

        return DeviceToken::query()
            ->where('user_id', $resolvedUserId)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();
    }

    private function resolveUserIdByName(?string $userName): ?int
    {
        $userName = trim((string) $userName);
        if ($userName === '' || !Schema::hasTable('users')) {
            return null;
        }

        $query = User::query();
        if (Schema::hasColumn('users', 'name')) {
            $query->where('name', $userName);
        }
        if (Schema::hasColumn('users', 'email')) {
            $query->orWhere('email', $userName);
        }
        if (Schema::hasColumn('users', 'phone')) {
            $query->orWhere('phone', $userName);
        }

        $userId = $query->value('id');

        return is_numeric($userId) ? (int) $userId : null;
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
