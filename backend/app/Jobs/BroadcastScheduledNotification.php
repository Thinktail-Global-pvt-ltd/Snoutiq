<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\ScheduledPushNotification;
use App\Models\ScheduledPushDispatchLog;
use App\Services\Push\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastScheduledNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string,string> $data
     */
    public function __construct(
        public readonly int $scheduledNotificationId,
    ) {
    }

    public function handle(FcmService $push): void
    {
        $notification = ScheduledPushNotification::query()
            ->whereKey($this->scheduledNotificationId)
            ->where('is_active', true)
            ->first();

        if (!$notification) {
            return;
        }

        $title = $notification->title;
        $body = $notification->body ?? '';
        $data = array_merge(
            ['scheduled_notification_id' => (string)$notification->getKey()],
            $notification->data ?? []
        );

        DeviceToken::query()
            ->select(['id', 'token', 'user_id'])
            ->whereNotNull('token')
            ->orderBy('id')
            ->chunkById(500, function ($tokens) use ($push, $title, $body, $data, $notification) {
                $tokenValues = $tokens->pluck('token')
                    ->filter()
                    ->values()
                    ->all();

                if (empty($tokenValues)) {
                    return;
                }

                $push->sendMulticast($tokenValues, $title, $body, $data);

                $timestamp = now();

                $payloadJson = empty($data) ? null : json_encode($data);

                $logRows = $tokens->map(function (DeviceToken $token) use ($notification, $payloadJson, $timestamp) {
                    return [
                        'scheduled_push_notification_id' => $notification->getKey(),
                        'device_token_id' => $token->id,
                        'user_id' => $token->user_id,
                        'token' => $token->token,
                        'payload' => $payloadJson,
                        'dispatched_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })->all();

                ScheduledPushDispatchLog::insert($logRows);
            });
    }
}
