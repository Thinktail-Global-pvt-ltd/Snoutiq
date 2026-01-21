<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Call $call)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('doctor.'.$this->call->doctor_id),
            new Channel('patient.'.$this->call->patient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CallStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'doctor_id' => $this->call->doctor_id,
            'patient_id' => $this->call->patient_id,
            'status' => $this->call->status,
            'channel' => $this->call->channel,
            'rtc' => $this->call->rtc,
            'accepted_at' => $this->call->accepted_at?->toIso8601String(),
            'rejected_at' => $this->call->rejected_at?->toIso8601String(),
            'ended_at' => $this->call->ended_at?->toIso8601String(),
            'cancelled_at' => $this->call->cancelled_at?->toIso8601String(),
            'missed_at' => $this->call->missed_at?->toIso8601String(),
            'updated_at' => $this->call->updated_at?->toIso8601String(),
        ];
    }
}
