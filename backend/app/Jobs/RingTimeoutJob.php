<?php

namespace App\Jobs;

use App\Events\CallStatusUpdated;
use App\Models\Call;
use App\Services\CallRoutingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RingTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $callId)
    {
    }

    public function handle(CallRoutingService $service): void
    {
        $call = Call::find($this->callId);
        if (! $call) {
            return;
        }

        if ($call->status !== Call::STATUS_RINGING) {
            return; // already handled
        }

        $service->markMissed($call);
        event(new CallStatusUpdated($call));
    }
}
