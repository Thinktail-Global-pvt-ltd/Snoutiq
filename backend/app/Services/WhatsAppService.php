<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppService
{
    public function __construct(
        private readonly ?string $phoneNumberId,
        private readonly ?string $accessToken,
        private readonly string $defaultLanguage = 'en_US',
        private readonly string $defaultTemplate = 'hello_world'
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->phoneNumberId) && !empty($this->accessToken);
    }

    public function sendTemplate(string $to, ?string $template = null, array $components = []): void
    {
        $this->dispatch([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => array_filter([
                'name' => $template ?: $this->defaultTemplate,
                'language' => ['code' => $this->defaultLanguage],
                'components' => $components ?: null,
            ]),
        ]);
    }

    public function sendText(string $to, string $body): void
    {
        $this->dispatch([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ]);
    }

    private function dispatch(array $payload): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('WhatsApp credentials missing');
        }

        $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->post("https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages", $payload);

        if (!$response->successful()) {
            Log::error('whatsapp.send.failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Failed to send WhatsApp message');
        }
    }
}
