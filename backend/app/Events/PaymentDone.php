<?php

// app/Events/PaymentDone.php
namespace App\Events;

use App\Models\CallSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PaymentDone implements ShouldBroadcastNow
{
    public function __construct(public CallSession $session)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('call-session-' . $this->session->id);
    }

    public function broadcastAs(): string
    {
        return 'PaymentDone';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->session->id,
            'patient_id'     => $this->session->patient_id,
            'doctor_id'      => $this->session->doctor_id,
            'channel_name'   => $this->session->channel_name,
            'status'         => $this->session->status,
            'payment_status' => $this->session->payment_status,
            'amount_paid'    => $this->session->amount_paid,
            'currency'       => $this->session->currency,
        ];
    }
}
