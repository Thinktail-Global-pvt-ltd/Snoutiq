<?php

// app/Events/PaymentDone.php
namespace App\Events;
use App\Models\CallSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PaymentDone implements ShouldBroadcast
{
    public $session;
    public function __construct(CallSession $session) { $this->session = $session; }
    public function broadcastOn() { return new Channel('call-session-'.$this->session->id); }
}
