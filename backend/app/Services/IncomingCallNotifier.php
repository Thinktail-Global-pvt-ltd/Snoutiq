<?php

namespace App\Services;

use App\Models\CallSession;
use App\Models\DoctorPushSubscription;
use Illuminate\Support\Facades\Log;

class IncomingCallNotifier
{
    public function __construct(private readonly WebPushService $webPushService)
    {
    }

    public function notify(CallSession $session): void
    {
        if (!$session->doctor_id) {
            return;
        }

        $subscriptions = DoctorPushSubscription::query()
            ->where('doctor_id', $session->doctor_id)
            ->get();

        if ($subscriptions->isEmpty()) {
            Log::info('incoming-call-notifier: no push subscriptions for doctor', [
                'doctor_id' => $session->doctor_id,
            ]);
            return;
        }

        $delivered = false;

        foreach ($subscriptions as $subscription) {
            $delivered = $this->webPushService->send($subscription) || $delivered;
        }

        if ($delivered) {
            $session->forceFill(['push_notified_at' => now()])->save();
        }
    }
}
