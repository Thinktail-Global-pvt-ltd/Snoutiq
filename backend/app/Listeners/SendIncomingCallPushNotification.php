<?php

namespace App\Listeners;

use App\Events\CallRequested;
use App\Models\CallSession;
use App\Services\DoctorPresenceService;
use App\Services\IncomingCallNotifier;
use Illuminate\Support\Facades\Log;

class SendIncomingCallPushNotification
{
    public function __construct(
        private readonly DoctorPresenceService $presenceService,
        private readonly IncomingCallNotifier $notifier
    ) {
    }

    public function handle(CallRequested $event): void
    {
        if (!$event->sessionId || !$event->doctorId) {
            return;
        }

        if ($this->presenceService->isDoctorAvailable((int) $event->doctorId)) {
            Log::info('incoming-call-listener: doctor is online, skipping push', [
                'doctor_id' => $event->doctorId,
            ]);
            return;
        }

        $session = CallSession::find($event->sessionId);
        if (!$session) {
            return;
        }

        if ($session->push_notified_at) {
            Log::info('incoming-call-listener: session already notified', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $this->notifier->notify($session);
    }
}
