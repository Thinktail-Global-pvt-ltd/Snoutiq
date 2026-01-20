<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallSessionUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public array $session, public string $eventType = 'status_update')
    {
    }

    public function broadcastOn(): array
    {
        $channels = [];

        if (!empty($this->session['doctorId'])) {
            $channels[] = new Channel('doctor-'.$this->session['doctorId']);
        }

        if (!empty($this->session['patientId'])) {
            $channels[] = new Channel('patient-'.$this->session['patientId']);
        }

        if (!empty($this->session['callId'])) {
            $channels[] = new Channel('call-'.$this->session['callId']);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'call-session.'.$this->eventType;
    }

    public function broadcastWith(): array
    {
        return [
            'callId' => $this->session['callId'],
            'doctorId' => $this->session['doctorId'] ?? null,
            'patientId' => $this->session['patientId'] ?? null,
            'status' => $this->session['status'] ?? null,
            'channel' => $this->session['channel'] ?? null,
            'payload' => $this->session,
        ];
    }
}
