<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Services\Push\FcmService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class NotificationChannelService
{
    public function __construct(private readonly FcmService $fcm)
    {
    }

    public function send(Notification $notification): void
    {
        $channels = [
            Notification::CHANNEL_WHATSAPP,
            Notification::CHANNEL_PUSH,
            Notification::CHANNEL_SMS,
            Notification::CHANNEL_IN_APP,
        ];

        foreach ($channels as $channel) {
            try {
                $this->sendViaChannel($notification, $channel);

                $notification->forceFill([
                    'status' => Notification::STATUS_SENT,
                    'channel' => $channel,
                    'sent_at' => now(),
                ])->save();

                return;
            } catch (Throwable $e) {
                Log::warning('Notification channel failed', [
                    'notification_id' => $notification->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        $notification->forceFill([
            'status' => Notification::STATUS_FAILED,
            'channel' => null,
        ])->save();
    }

    /**
     * @throws Throwable
     */
    protected function sendViaChannel(Notification $notification, string $channel): void
    {
        if ($notification->status === Notification::STATUS_SENT) {
            return;
        }

        match ($channel) {
            Notification::CHANNEL_WHATSAPP => $this->sendWhatsApp($notification),
            Notification::CHANNEL_PUSH => $this->sendPush($notification),
            Notification::CHANNEL_SMS => $this->sendSms($notification),
            Notification::CHANNEL_IN_APP => $this->storeInApp($notification),
            default => throw new RuntimeException('Unsupported channel '.$channel),
        };
    }

    protected function sendWhatsApp(Notification $notification): void
    {
        throw new RuntimeException('WhatsApp channel not implemented yet.');
    }

    protected function sendPush(Notification $notification): void
    {
        if (! $notification->user_id) {
            throw new RuntimeException('Cannot send push without user.');
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $notification->user_id)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        // Store tokens for debugging/visibility
        $notification->forceFill(['debug_tokens' => $tokens])->save();

        if (empty($tokens)) {
            throw new RuntimeException('No push tokens found for user.');
        }

        $data = $this->buildPayloadData($notification);
        $title = $notification->title ?? 'Snoutiq';
        $body = $notification->body ?? 'You have an update from Snoutiq.';

        $this->fcm->sendMulticast($tokens, $title, $body, $data);
    }

    protected function sendSms(Notification $notification): void
    {
        throw new RuntimeException('SMS channel not implemented yet.');
    }

    protected function storeInApp(Notification $notification): void
    {
        // No-op for now. The notification record itself acts as in-app storage.
    }

    /**
     * @return array<string,string>
     */
    private function buildPayloadData(Notification $notification): array
    {
        $payload = $notification->payload ?? [];
        $payload = array_merge(['notification_type' => $notification->type], $payload);

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
