<?php

namespace App\Services;

use App\Models\WhatsAppNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WhatsAppService
{
    private const STATUS_SENT = 'sent';
    private const STATUS_FAILED = 'failed';

    private static ?bool $notificationTableExists = null;

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

    public function sendDocument(string $to, string $link, ?string $filename = null): array
    {
        return $this->dispatch([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => array_filter([
                'link' => $link,
                'filename' => $filename,
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
            $this->storeDispatchLog(
                $payload,
                self::STATUS_FAILED,
                null,
                null,
                'WhatsApp credentials missing',
                null,
                null
            );
            throw new RuntimeException('WhatsApp credentials missing');
        }

        $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->post("https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages", $payload);

        $responseBody = $response->body();
        $responseJson = $response->json();
        $responsePayload = is_array($responseJson) ? $responseJson : null;

        if (!$response->successful()) {
            $errorMessage = data_get($responsePayload, 'error.message') ?? $responseBody;
            $errorDetails = data_get($responsePayload, 'error.error_data.details') ?? null;

            $errorDetailsText = null;
            if (is_string($errorDetails)) {
                $errorDetailsText = $errorDetails;
            } elseif ($errorDetails !== null) {
                $encoded = json_encode($errorDetails);
                $errorDetailsText = $encoded === false ? null : $encoded;
            }

            Log::error('whatsapp.send.failed', [
                'status' => $response->status(),
                'body' => $responseBody,
                'error_message' => $errorMessage,
                'error_details' => $errorDetailsText,
            ]);

            $message = 'Failed to send WhatsApp message';
            if ($errorMessage) {
                $message .= ': ' . $errorMessage;
            }
            if ($errorDetailsText) {
                $message .= ' (' . $errorDetailsText . ')';
            }

            $this->storeDispatchLog(
                $payload,
                self::STATUS_FAILED,
                $response->status(),
                $responsePayload,
                is_string($errorMessage) ? $errorMessage : (string) $errorMessage,
                $errorDetailsText,
                $responseBody
            );

            throw new RuntimeException($message);
        }

        $this->storeDispatchLog(
            $payload,
            self::STATUS_SENT,
            $response->status(),
            $responsePayload,
            null,
            null,
            $responseBody
        );

        return $returnResponse ? ($responsePayload ?? []) : [];
    }

    private function storeDispatchLog(
        array $payload,
        string $status,
        ?int $httpStatus,
        ?array $responsePayload,
        ?string $errorMessage,
        ?string $errorDetails,
        ?string $responseBody
    ): void {
        // Skip only when we already know table is unavailable.
        if (self::$notificationTableExists === false) {
            return;
        }

        [$source, $sourceFile, $sourceLine] = $this->resolveDispatchSource();
        $template = is_array($payload['template'] ?? null) ? $payload['template'] : [];
        $templateName = is_string($template['name'] ?? null) ? $template['name'] : null;
        $languageCode = data_get($template, 'language.code');

        try {
            WhatsAppNotification::query()->create([
                'recipient' => is_string($payload['to'] ?? null) ? $payload['to'] : null,
                'message_type' => is_string($payload['type'] ?? null) ? $payload['type'] : null,
                'template_name' => $this->truncate($templateName, 120),
                'language_code' => is_string($languageCode) ? $this->truncate($languageCode, 32) : null,
                'status' => $status,
                'http_status' => $httpStatus,
                'provider_message_id' => $this->truncate(
                    is_string(data_get($responsePayload, 'messages.0.id'))
                        ? data_get($responsePayload, 'messages.0.id')
                        : null,
                    191
                ),
                'payload' => $payload,
                'response_payload' => $responsePayload,
                'response_body' => $responseBody,
                'error_message' => $errorMessage,
                'error_details' => $errorDetails,
                'source' => $this->truncate($source, 255),
                'source_file' => $this->truncate($sourceFile, 255),
                'source_line' => $sourceLine,
                'sent_at' => $status === self::STATUS_SENT ? now() : null,
            ]);
        } catch (Throwable $e) {
            if ($this->isMissingTableError($e)) {
                self::$notificationTableExists = false;
            }

            Log::warning('whatsapp.notification.persist_failed', [
                'error' => $e->getMessage(),
                'status' => $status,
                'to' => $payload['to'] ?? null,
            ]);
        }
    }

    private function isMissingTableError(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, '42s02')
            || str_contains($message, 'base table or view not found')
            || str_contains($message, 'whatsapp_notifications');
    }

    private function resolveDispatchSource(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);

        foreach ($trace as $frame) {
            $class = $frame['class'] ?? null;
            if (! is_string($class) || $class === self::class) {
                continue;
            }

            $function = is_string($frame['function'] ?? null) ? $frame['function'] : 'unknown';
            $source = $class . '@' . $function;
            $file = isset($frame['file']) && is_string($frame['file']) ? $frame['file'] : null;
            $line = isset($frame['line']) ? (int) $frame['line'] : null;

            return [$source, $file, $line];
        }

        return [null, null, null];
    }

    private function truncate(?string $value, int $length): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return substr($value, 0, $length);
    }
}
