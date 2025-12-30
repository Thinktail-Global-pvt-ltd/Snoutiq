<?php

namespace App\Jobs;

use App\Models\CallSession;
use App\Services\TranscriptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCallTranscript implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $callSessionId, public ?string $recordingUrl = null)
    {
    }

    public function handle(TranscriptService $transcriptService): void
    {
        $session = CallSession::find($this->callSessionId);

        if (!$session) {
            return;
        }

        $session->transcript_status = 'processing';
        $session->transcript_requested_at = $session->transcript_requested_at ?? now();
        $session->save();

        try {
            $result = $transcriptService->generateFromCallSession($session, $this->recordingUrl);

            $session->transcript_status = 'completed';
            $session->transcript_text = $result['text'] ?? $session->transcript_text;
            $session->transcript_url = $result['url'] ?? $session->transcript_url ?? null;
            $session->transcript_completed_at = now();
            $session->transcript_error = null;
        } catch (\Throwable $e) {
            Log::error('Transcript generation failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            $session->transcript_status = 'failed';
            $session->transcript_error = $e->getMessage();
        }

        $session->save();
    }
}
