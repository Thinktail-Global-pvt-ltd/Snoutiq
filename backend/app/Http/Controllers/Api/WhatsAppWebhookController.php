<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConsultationShareSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly ConsultationShareSessionService $consultSessions
    ) {
    }

    public function verify(Request $request)
    {
        $mode = (string) ($request->query('hub_mode') ?? $request->query('hub.mode') ?? '');
        $token = (string) ($request->query('hub_verify_token') ?? $request->query('hub.verify_token') ?? '');
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query('hub.challenge') ?? '');
        $expectedToken = trim((string) config('whatsapp.webhook_verify_token', ''));

        if ($mode === 'subscribe' && $expectedToken !== '' && hash_equals($expectedToken, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403)->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $messages = collect(data_get($payload, 'entry', []))
            ->flatMap(fn ($entry) => data_get($entry, 'changes', []))
            ->flatMap(fn ($change) => data_get($change, 'value.messages', []));

        $processed = 0;
        $matched = 0;

        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $processed++;
            $session = $this->consultSessions->handleInboundMessage($message, $payload);
            if ($session) {
                $matched++;
            }
        }

        Log::info('whatsapp.webhook.inbound_processed', [
            'processed' => $processed,
            'matched' => $matched,
        ]);

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'matched' => $matched,
        ]);
    }
}
