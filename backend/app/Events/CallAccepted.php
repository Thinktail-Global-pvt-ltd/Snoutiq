<?php

namespace App\Events;

use App\Models\CallSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // 👈 use Now

class CallAccepted implements ShouldBroadcastNow
{
    public $session;

    public function __construct(CallSession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn()
    {
        // public channel
        return new Channel('call-session-' . $this->session->id);
    }

    public function broadcastAs()
    {
        return 'CallAccepted'; // 👈 client will bind to this
    }

    public function broadcastWith()
    {
        // send a small, clean payload
        return [
            'id'             => $this->session->id,
            'patient_id'     => $this->session->patient_id,
            'doctor_id'      => $this->session->doctor_id,
            'channel_name'   => $this->session->channel_name,
            'status'         => $this->session->status,
            'payment_status' => $this->session->payment_status,
            'accepted_at'    => optional($this->session->accepted_at)->toIso8601String(),
        ];
    }
}

