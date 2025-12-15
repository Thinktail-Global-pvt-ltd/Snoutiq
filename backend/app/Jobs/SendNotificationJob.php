<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Notifications\NotificationChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $notificationId)
    {
    }

    public function handle(NotificationChannelService $service): void
    {
        $notification = Notification::query()->find($this->notificationId);

        if (! $notification || $notification->status === Notification::STATUS_SENT) {
            return;
        }

        \Log::info('SendNotificationJob starting', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'status' => $notification->status,
            'type' => $notification->type,
        ]);

        $service->send($notification);

        \Log::info('SendNotificationJob finished', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'status' => $notification->status,
            'type' => $notification->type,
            'channel' => $notification->channel,
        ]);
    }
}
