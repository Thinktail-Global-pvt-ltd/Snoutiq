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

    public function sendTemplate(string $to, ?string $template = null, array $components = [], ?string $language = null): void
    {
        $this->dispatch([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => array_filter([
                'name' => $template ?: $this->defaultTemplate,
                'language' => ['code' => $language ?: $this->defaultLanguage],
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

    public function sendTextWithResult(string $to, string $body): array
    {
        return $this->dispatch([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ], true);
    }

    public function sendTemplateWithResult(string $to, ?string $template = null, array $components = [], ?string $language = null): array
    {
        return $this->dispatch([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => array_filter([
                'name' => $template ?: $this->defaultTemplate,
                'language' => ['code' => $language ?: $this->defaultLanguage],
                'components' => $components ?: null,
            ]),
        ], true);
    }

    public function sendOtpTemplate(string $to, string $otp, string $template = 'whatsapp_authentication', string $language = 'en'): void
    {
        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $otp],
                ],
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $otp],
                ],
            ],
        ];

        $this->sendTemplate($to, $template, $components, $language);
    }

    private function dispatch(array $payload, bool $returnResponse = false): array
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

        return $returnResponse ? $response->json() : [];
    }
}
