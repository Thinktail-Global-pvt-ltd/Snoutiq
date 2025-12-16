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

        // Gather tokens for the user (same as /api/push/test path)
        $tokens = DeviceToken::query()
            ->where('user_id', $notification->user_id)
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

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

        $success = 0;
        $failures = [];
        foreach ($tokens as $token) {
            $request = Request::create('/api/push/test', 'POST', [
                'token' => $token,
                'title' => $title,
                'body' => $body,
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
