<?php

namespace App\Jobs;

use App\Services\Push\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<string,string> $data
     */
    public function __construct(
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = []
    ) {
        // queue connection & retry strategy can be customized here if needed
    }

    public function handle(FcmService $push): void
    {
        $push->sendToToken($this->token, $this->title, $this->body, $this->data);
    }
}

