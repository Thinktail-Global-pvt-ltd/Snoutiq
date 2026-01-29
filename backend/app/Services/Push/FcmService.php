<?php

namespace App\Services\Push;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use App\Models\DeviceToken;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Throwable;

class FcmService
{
    public function __construct(private readonly Messaging $messaging)
    {
    }

    private function normalizeToken(?string $token): string
    {
        // Trim whitespace and stray quotes that sometimes get saved from clients
        return trim(trim((string) $token), "\"'");
    }

    private function isLikelyFcmToken(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        // FCM device tokens are long (>100 chars) and contain no whitespace
        if (strlen($token) < 80 || preg_match('/\\s/', $token)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    private function normalizeDataPayload(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $normalized[$key] = json_encode($value);
                continue;
            }

            if ($value === null) {
                continue;
            }

            $normalized[$key] = (string) $value;
        }

        return $normalized;
    }

    /**
     * @param array<string,string> $data
     */
    private function isIncomingCall(array $data): bool
    {
        return strtolower($data['type'] ?? '') === 'incoming_call';
    }

    /**
     * @param array<string,string> $data
     */
    private function shouldSendDataOnly(array $data): bool
    {
        if ($this->isIncomingCall($data)) {
            // Incoming calls must be data-only so the background handler can persist state.
            return true;
        }

        $dataOnly = strtolower($data['data_only'] ?? '');
        return in_array($dataOnly, ['1', 'true', 'yes'], true);
    }

    /**
     * @param array<string,string> $data
     * @return array<string,string>
     */
    private function ensureIncomingCallDataOnlyFlag(array $data): array
    {
        if ($this->isIncomingCall($data) && !array_key_exists('data_only', $data)) {
            $data['data_only'] = '1';
        }

        return $data;
    }

    /**
     * @param array<string,string> $data
     */
    private function buildAndroidConfig(array $data): AndroidConfig
    {
        $config = [
            'priority' => 'high',
        ];

        if ($this->isIncomingCall($data)) {
            $config['ttl'] = '30s';
        }

        return AndroidConfig::fromArray($config);
    }

    /**
     * @param array<string,string> $data
     */
    private function buildApnsConfig(array $data): ?ApnsConfig
    {
        if (!$this->isIncomingCall($data)) {
            return null;
        }

        return ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => '10',
            ],
        ]);
    }

    /**
     * @param array<string,string> $data
     * @return array<string,mixed>
     */
    private function buildPayloadArray(
        string $token,
        string $title,
        string $body,
        array $data,
        bool $dataOnly
    ): array {
        $payload = [
            'token' => $token,
            'data' => $data,
            'android' => [
                'priority' => 'high',
            ],
        ];

        if ($this->isIncomingCall($data)) {
            $payload['android']['ttl'] = '30s';
            $payload['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
            ];
        }

        if (!$dataOnly) {
            $payload['notification'] = [
                'title' => $title,
                'body' => $body,
            ];
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function maskPayloadToken(array $payload): array
    {
        if (isset($payload['token']) && is_string($payload['token'])) {
            $payload['token'] = $this->maskToken($payload['token']);
        }

        return $payload;
    }

    /**
     * @param array<int,string> $tokens
     * @return array<int,string>
     */
    private function filterValidTokens(array $tokens): array
    {
        $normalized = array_map(fn ($t) => $this->normalizeToken($t), $tokens);

        return array_values(array_unique(array_filter($normalized, function ($token) {
            if (!$this->isLikelyFcmToken($token)) {
                \Log::warning('Skipping FCM send; token looks invalid', [
                    'token' => $token,
                ]);
                return false;
            }

            return true;
        })));
    }

    /**
     * Send a notification to a single device token.
     *
     * @param array<string,string> $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $normalizedToken = $this->normalizeToken($token);
        if (!$this->isLikelyFcmToken($normalizedToken)) {
            \Log::warning('Skipping FCM send; token rejected as invalid format', [
                'token' => $normalizedToken,
            ]);
            return;
        }

        $normalizedData = $this->normalizeDataPayload($data);
        $normalizedData = $this->ensureIncomingCallDataOnlyFlag($normalizedData);
        $dataOnly = $this->shouldSendDataOnly($normalizedData);
        $apnsConfig = $this->buildApnsConfig($normalizedData);
        $payload = $this->buildPayloadArray($normalizedToken, $title, $body, $normalizedData, $dataOnly);

        \Log::info('FCM send to token attempt', [
            'token' => $this->maskToken($normalizedToken),
            'title' => $title,
            'data_keys' => array_keys($normalizedData),
            'data_only' => $dataOnly,
        ]);

        if ($this->isIncomingCall($normalizedData)) {
            $payloadForLog = config('app.debug')
                ? $payload
                : $this->maskPayloadToken($payload);

            \Log::info('FCM payload (incoming_call)', [
                'payload' => $payloadForLog,
            ]);
        }

        $message = CloudMessage::withTarget('token', $normalizedToken)
            ->withData($normalizedData)
            ->withAndroidConfig($this->buildAndroidConfig($normalizedData));

        if ($apnsConfig !== null) {
            $message = $message->withApnsConfig($apnsConfig);
        }

        if (!$dataOnly) {
            $message = $message->withNotification(Notification::create($title, $body));
        }

        $messageId = $this->sendMessage($message, $normalizedToken);

        \Log::info('FCM send to token success', [
            'token' => $this->maskToken($normalizedToken),
            'message_id' => $messageId,
        ]);
    }

    /**
     * Send a notification to a single token and return a payload report.
     *
     * @param array<string,string> $data
     * @return array<string,mixed>
     */
    public function sendToTokenWithReport(string $token, string $title, string $body, array $data = []): array
    {
        $normalizedToken = $this->normalizeToken($token);
        if (!$this->isLikelyFcmToken($normalizedToken)) {
            throw new \InvalidArgumentException('Token rejected as invalid format.');
        }

        $normalizedData = $this->normalizeDataPayload($data);
        $normalizedData = $this->ensureIncomingCallDataOnlyFlag($normalizedData);
        $dataOnly = $this->shouldSendDataOnly($normalizedData);
        $apnsConfig = $this->buildApnsConfig($normalizedData);
        $payload = $this->buildPayloadArray($normalizedToken, $title, $body, $normalizedData, $dataOnly);

        if ($this->isIncomingCall($normalizedData)) {
            $payloadForLog = config('app.debug')
                ? $payload
                : $this->maskPayloadToken($payload);

            \Log::info('FCM payload (incoming_call)', [
                'payload' => $payloadForLog,
            ]);
        }

        $message = CloudMessage::withTarget('token', $normalizedToken)
            ->withData($normalizedData)
            ->withAndroidConfig($this->buildAndroidConfig($normalizedData));

        if ($apnsConfig !== null) {
            $message = $message->withApnsConfig($apnsConfig);
        }

        if (!$dataOnly) {
            $message = $message->withNotification(Notification::create($title, $body));
        }

        $messageId = $this->sendMessage($message, $normalizedToken);

        return [
            'payload' => $payload,
            'data_only' => $dataOnly,
            'message_id' => $messageId,
        ];
    }

    /**
     * Send to multiple device tokens in one request.
     *
     * @param array<int,string> $tokens
     * @param array<string,string> $data
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): array
    {
        $validTokens = $this->filterValidTokens($tokens);

        if (empty($validTokens)) {
            return [
                'success' => 0,
                'failure' => count($tokens),
                'results' => [],
            ];
        }

        $normalizedData = $this->normalizeDataPayload($data);
        $normalizedData = $this->ensureIncomingCallDataOnlyFlag($normalizedData);
        $dataOnly = $this->shouldSendDataOnly($normalizedData);
        $apnsConfig = $this->buildApnsConfig($normalizedData);

        $message = CloudMessage::new()
            ->withData($normalizedData)
            ->withAndroidConfig($this->buildAndroidConfig($normalizedData));

        if ($apnsConfig !== null) {
            $message = $message->withApnsConfig($apnsConfig);
        }

        if (!$dataOnly) {
            $message = $message->withNotification(Notification::create($title, $body));
        }

        return $this->sendMulticastMessage($message, $validTokens);
    }

    /**
     * Convenience: send to all tokens of a user id.
     *
     * @param array<string,string> $data
     */
    public function notifyUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->all();

        $this->sendMulticast($tokens, $title, $body, $data);
    }

    /**
     * Subscribe tokens to a topic and send.
     *
     * @param array<int,string> $tokens
     * @param array<string,string> $data
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], array $tokensToEnsure = []): void
    {
        if (!empty($tokensToEnsure)) {
            $this->messaging->subscribeToTopic($topic, $tokensToEnsure);
        }

        $normalizedData = $this->normalizeDataPayload($data);
        $normalizedData = $this->ensureIncomingCallDataOnlyFlag($normalizedData);
        $dataOnly = $this->shouldSendDataOnly($normalizedData);
        $apnsConfig = $this->buildApnsConfig($normalizedData);

        $message = CloudMessage::withTarget('topic', $topic)
            ->withData($normalizedData)
            ->withAndroidConfig($this->buildAndroidConfig($normalizedData));

        if ($apnsConfig !== null) {
            $message = $message->withApnsConfig($apnsConfig);
        }

        if (!$dataOnly) {
            $message = $message->withNotification(Notification::create($title, $body));
        }

        $this->sendMessage($message, $topic);
    }

    private function sendMessage(CloudMessage $message, string $target): mixed
    {
        try {
            return $this->messaging->send($message);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            \Log::error('FCM send failed', [
                'target' => $target,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array<int,string> $tokens
     */
    private function sendMulticastMessage(CloudMessage $message, array $tokens): array
    {
        try {
            $report = $this->messaging->sendMulticast($message, $tokens);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            \Log::error('FCM multicast send failed', [
                'tokens' => $tokens,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return [
            'success' => $report->successes()->count(),
            'failure' => $report->failures()->count(),
            'results' => $this->mapMulticastResults($report),
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function mapMulticastResults(MulticastSendReport $report): array
    {
        $results = [];
        foreach ($report->getItems() as $sendReport) {
            $token = $sendReport->target()->value();

            if ($sendReport->isSuccess()) {
                $results[$token] = ['ok' => true];
                continue;
            }

            $error = $sendReport->error();
            $results[$token] = [
                'ok' => false,
                'code' => $error?->getCode(),
                'error' => $error?->getMessage(),
            ];
        }

        return $results;
    }

    private function maskToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 12) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 6).'…'.substr($token, -6);
    }
}
