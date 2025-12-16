<?php

namespace App\Jobs;

use App\Http\Controllers\Api\PushController;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Services\Push\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $notificationId)
    {
    }

    public function handle(FcmService $fcm): void
    {
        $notification = Notification::query()->find($this->notificationId);

        if (! $notification || $notification->status === Notification::STATUS_SENT) {
            \Log::info('SendNotificationJob skipped', [
                'notification_id' => $this->notificationId,
                'reason' => ! $notification ? 'not_found' : 'already_sent',
            ]);
            return;
        }

        \Log::info('SendNotificationJob starting', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'status' => $notification->status,
            'type' => $notification->type,
        ]);

        // Prefer explicit token in payload for debugging; otherwise use stored device tokens.
        $payload = $notification->payload ?? [];
        $explicitToken = is_array($payload) ? ($payload['fcm_token'] ?? null) : null;

        $tokens = [];
        if ($explicitToken) {
            $tokens[] = $explicitToken;
        } else {
            $tokens = DeviceToken::query()
                ->where('user_id', $notification->user_id)
                ->pluck('token')
                ->filter()
                ->values()
                ->all();
        }

        if (empty($tokens)) {
            $notification->forceFill([
                'status' => Notification::STATUS_FAILED,
                'channel' => 'push',
            ])->save();

            \Log::warning('SendNotificationJob push skipped; no tokens', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
            ]);

            return;
        }

        $title = $notification->title ?? 'Snoutiq';
        $body = $notification->body ?? 'You have an update from Snoutiq.';
        $data = ['type' => $notification->type ?? 'notification'];
        if (is_array($payload)) {
            foreach ($payload as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = json_encode($value);
                    continue;
                }
                $data[$key] = (string) $value;
            }
        }

        $success = 0;
        $failures = [];
        foreach ($tokens as $token) {
            $request = Request::create('/api/push/test', 'POST', [
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            try {
                app(PushController::class)->testToToken($request, $fcm);
                $success++;
            } catch (Throwable $e) {
                $failures[] = [
                    'token' => $token,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];
            }
        }

        if ($success <= 0) {
            $notification->forceFill([
                'status' => Notification::STATUS_FAILED,
                'channel' => 'push',
            ])->save();

            \Log::error('SendNotificationJob push failed (api/push/test path)', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'failures' => $failures,
            ]);

            throw new RuntimeException('All push attempts failed via api/push/test');
        }

        $notification->forceFill([
            'status' => Notification::STATUS_SENT,
            'channel' => 'push',
            'sent_at' => now(),
        ])->save();

        $fresh = Notification::query()->find($this->notificationId);
        \Log::info('SendNotificationJob finished', [
            'notification_id' => $this->notificationId,
            'user_id' => $fresh?->user_id,
            'status' => $fresh?->status,
            'type' => $fresh?->type,
            'channel' => $fresh?->channel,
        ]);
    }
}
