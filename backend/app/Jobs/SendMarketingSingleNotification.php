<?php

namespace App\Jobs;

use App\Models\MarketingSingleNotification;
use App\Services\Push\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMarketingSingleNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $notificationId)
    {
    }

    public function handle(FcmService $fcmService): void
    {
        $notification = MarketingSingleNotification::query()->find($this->notificationId);

        if (! $notification || $notification->status !== MarketingSingleNotification::STATUS_PENDING) {
            return;
        }

        try {
            $fcmService->sendToToken(
                $notification->token,
                $notification->title,
                $notification->body ?? '',
                ['type' => 'marketing_single', 'trigger' => 'marketing_single']
            );

            $notification->forceFill([
                'status' => MarketingSingleNotification::STATUS_SENT,
                'sent_at' => now(),
                'error_message' => null,
            ])->save();
        } catch (Throwable $e) {
            Log::error('SendMarketingSingleNotification failed: '.$e->getMessage(), [
                'notification_id' => $notification->id ?? null,
            ]);
            $notification->forceFill([
                'error_message' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($notification = MarketingSingleNotification::query()->find($this->notificationId)) {
            $notification->forceFill([
                'status' => MarketingSingleNotification::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ])->save();
        }
    }
}
