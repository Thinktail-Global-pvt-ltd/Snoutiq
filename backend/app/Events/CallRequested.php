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

    public function __construct($doctorId, $patientId, $channelName)
    {
        $this->doctorId = $doctorId;
        $this->patientId = $patientId;
        $this->channelName = $channelName;
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
        ];
    }
}

