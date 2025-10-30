<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class CallRequested implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $doctorId;
    public $patientId;
    public $channelName;   // ✅ Add channel name
    public $sessionId;

    public function __construct($doctorId, $patientId, $channelName, $sessionId = null)
    {
        $this->doctorId = $doctorId;
        $this->patientId = $patientId;
        $this->channelName = $channelName;
        $this->sessionId = $sessionId;
    }

    public function broadcastOn()
    {
        return new Channel('doctor.' . $this->doctorId);
    }

    public function broadcastWith()
    {
        return [
            'doctorId'   => $this->doctorId,
            'patientId'  => $this->patientId,
            'channel'    => $this->channelName, // ✅ send channel
            'sessionId'  => $this->sessionId,
        ];
    }
}

