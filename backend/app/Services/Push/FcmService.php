<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use App\Models\FcmNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Throwable;

class FcmService
{
    private const DELIVERY_MODE_HYBRID = 'hybrid';
    private const DELIVERY_MODE_DATA_ONLY = 'data_only';
    private const DELIVERY_MODE_NOTIFICATION_ONLY = 'notification_only';
    private const STATUS_SENT = 'sent';
    private const STATUS_FAILED = 'failed';
    private const STATUS_SKIPPED = 'skipped';
    private const TARGET_TOKEN = 'token';
    private const TARGET_MULTICAST_TOKEN = 'multicast_token';
    private const TARGET_TOPIC = 'topic';

    private static ?bool $notificationTableExists = null;
    private static ?bool $notificationCallSessionColumnExists = null;

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

        $notificationId = trim((string) ($normalized['notification_id'] ?? ''));
        $fcmNotificationId = trim((string) ($normalized['fcm_notification_id'] ?? ''));

        // Keep identifiers consistent across payload keys used by different clients.
        if ($notificationId === '' && $fcmNotificationId === '') {
            $generatedId = (string) $this->generatePayloadNotificationId();
            $normalized['notification_id'] = $generatedId;
            $normalized['fcm_notification_id'] = $generatedId;
        } elseif ($notificationId !== '' && $fcmNotificationId === '') {
            $normalized['fcm_notification_id'] = $notificationId;
        } elseif ($notificationId === '' && $fcmNotificationId !== '') {
            $normalized['notification_id'] = $fcmNotificationId;
        }

        return $normalized;
    }

    private function generatePayloadNotificationId(): int
    {
        // Millisecond epoch + random suffix keeps ids numeric and collision-safe enough
        // for concurrent scheduler sends.
        $epochMillis = (int) floor(microtime(true) * 1000);
        $suffix = random_int(100, 999);

        return ($epochMillis * 1000) + $suffix;
    }

    /**
     * @param array<string,string> $data
     */
    private function shouldSendDataOnly(array $data): bool
    {
        return $this->resolveDeliveryMode($data) === self::DELIVERY_MODE_DATA_ONLY;
    }

    /**
     * @param array<string,string> $data
     */
    private function resolveDeliveryMode(array $data): string
    {
        $rawMode = $data['delivery_mode'] ?? $data['deliveryMode'] ?? null;
        if (is_string($rawMode)) {
            $normalizedMode = strtolower(trim($rawMode));
            if (in_array($normalizedMode, [
                self::DELIVERY_MODE_HYBRID,
                self::DELIVERY_MODE_DATA_ONLY,
                self::DELIVERY_MODE_NOTIFICATION_ONLY,
            ], true)) {
                return $normalizedMode;
            }
        }

        $dataOnly = strtolower($data['data_only'] ?? '');
        if (in_array($dataOnly, ['1', 'true', 'yes'], true)) {
            return self::DELIVERY_MODE_DATA_ONLY;
        }

        // Backward compatibility: incoming_call was historically forced to data-only.
        $type = strtolower($data['type'] ?? '');
        if ($type === 'incoming_call') {
            return self::DELIVERY_MODE_DATA_ONLY;
        }

        return self::DELIVERY_MODE_HYBRID;
    }

    /**
     * @param array<string,string> $data
     */
    private function isIncomingCallPayload(array $data): bool
    {
        return strtolower($data['type'] ?? '') === 'incoming_call';
    }

    /**
     * @param array<string,string> $data
     * @return array<string,string>
     */
    private function buildNotificationOnlyData(array $data): array
    {
        $minimalKeys = [
            'type',
            'call_id',
            'callId',
            'call_identifier',
            'callIdentifier',
            'call_session_id',
            'callSessionId',
            'doctor_id',
            'doctorId',
            'patient_id',
            'patientId',
            'channel',
            'channel_name',
            'channelName',
            'expires_at',
            'event',
            'action',
            'delivery_mode',
            'deliveryMode',
        ];

        $minimal = [];
        foreach ($minimalKeys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = (string) $data[$key];
            if ($value === '') {
                continue;
            }

            $minimal[$key] = $value;
        }

        if (!isset($minimal['delivery_mode'])) {
            $minimal['delivery_mode'] = self::DELIVERY_MODE_NOTIFICATION_ONLY;
        }
        if (!isset($minimal['deliveryMode'])) {
            $minimal['deliveryMode'] = self::DELIVERY_MODE_NOTIFICATION_ONLY;
        }

        return $minimal;
    }

    /**
     * @param array<string,string> $data
     * @return array<string,mixed>
     */
    private function buildPayloadArray(string $token, ?string $title, ?string $body, array $data): array
    {
        $deliveryMode = $this->resolveDeliveryMode($data);
        $dataOnly = $deliveryMode === self::DELIVERY_MODE_DATA_ONLY;
        $notificationOnly = $deliveryMode === self::DELIVERY_MODE_NOTIFICATION_ONLY;
        $isIncomingCall = $this->isIncomingCallPayload($data);

        $payload = [
            'token' => $token,
            'android' => [
                'priority' => 'high',
            ],
        ];

        if (!$notificationOnly) {
            $payload['data'] = $data;
        } else {
            $payload['data'] = $this->buildNotificationOnlyData($data);
        }

        if ($isIncomingCall) {
            $payload['android']['ttl'] = '30s';
        } elseif ($dataOnly) {
            $payload['android']['ttl'] = '90s';
        }

        if ($isIncomingCall && !$dataOnly) {
            $payload['android']['notification'] = [
                'channel_id' => 'incoming_calls_v6',
            ];
        }

        if ($dataOnly || $isIncomingCall) {
            $payload['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
            ];
        }

        if (!$dataOnly && $title !== null && trim($title) !== '') {
            $payload['notification'] = [
                'title' => $title,
                'body' => $body ?? '',
            ];
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function applyRichPayloadOverrides(array $payload, array $options): array
    {
        $canDecorateNotification = array_key_exists('notification', $payload);
        $image = $this->stringifyValue($options['image'] ?? null, 2048);
        if ($image !== null && $canDecorateNotification) {
            if (!isset($payload['notification']) || !is_array($payload['notification'])) {
                $payload['notification'] = [];
            }
            $payload['notification']['image'] = $image;

            if (!isset($payload['android']) || !is_array($payload['android'])) {
                $payload['android'] = [];
            }
            if (!isset($payload['android']['notification']) || !is_array($payload['android']['notification'])) {
                $payload['android']['notification'] = [];
            }
            $payload['android']['notification']['image'] = $image;

            if (!isset($payload['apns']) || !is_array($payload['apns'])) {
                $payload['apns'] = [];
            }
            if (!isset($payload['apns']['fcm_options']) || !is_array($payload['apns']['fcm_options'])) {
                $payload['apns']['fcm_options'] = [];
            }
            $payload['apns']['fcm_options']['image'] = $image;

            if (!isset($payload['apns']['payload']) || !is_array($payload['apns']['payload'])) {
                $payload['apns']['payload'] = [];
            }
            if (!isset($payload['apns']['payload']['aps']) || !is_array($payload['apns']['payload']['aps'])) {
                $payload['apns']['payload']['aps'] = [];
            }
            $payload['apns']['payload']['aps']['mutable-content'] = 1;
        }

        $icon = $this->stringifyValue($options['icon'] ?? null, 255);
        if ($icon !== null && $canDecorateNotification) {
            if (!isset($payload['android']) || !is_array($payload['android'])) {
                $payload['android'] = [];
            }
            if (!isset($payload['android']['notification']) || !is_array($payload['android']['notification'])) {
                $payload['android']['notification'] = [];
            }
            $payload['android']['notification']['icon'] = $icon;
        }

        return $payload;
    }

    /**
     * @param array<int,string> $tokens
     * @return array{valid:array<int,string>,invalid:array<int,string>}
     */
    private function partitionTokensByValidity(array $tokens): array
    {
        $normalized = array_values(array_unique(array_map(fn ($t) => $this->normalizeToken($t), $tokens)));
        $valid = [];
        $invalid = [];

        foreach ($normalized as $token) {
            if (!$this->isLikelyFcmToken($token)) {
                Log::warning('Skipping FCM send; token looks invalid', [
                    'token' => $token,
                ]);
                $invalid[] = $token;
                continue;
            }

            $valid[] = $token;
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }

    /**
     * Send a notification to a single device token.
     *
     * @param array<string,string> $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $normalizedToken = $this->normalizeToken($token);
        $normalizedData = $this->normalizeDataPayload($data);
        $deliveryMode = $this->resolveDeliveryMode($normalizedData);
        $notificationType = $this->resolveNotificationType($normalizedData);
        $payload = $this->buildPayloadArray($normalizedToken, $title, $body, $normalizedData);
        $source = $this->resolveDispatchSource();
        $recipient = $this->resolveRecipientContexts([$normalizedToken])[$normalizedToken] ?? [];

        if (!$this->isLikelyFcmToken($normalizedToken)) {
            Log::warning('Skipping FCM send; token rejected as invalid format', [
                'token' => $normalizedToken,
            ]);

            $this->storeDispatchLog([
                'status' => self::STATUS_SKIPPED,
                'target_type' => self::TARGET_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $normalizedToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => null,
                'error_code' => 'invalid_token_format',
                'error_message' => 'Token rejected as invalid format',
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => null,
                'sent_at' => null,
            ]);

            return;
        }

        $dataOnly = $this->shouldSendDataOnly($normalizedData);
        $includesNotification = array_key_exists('notification', $payload);

        Log::info('FCM send to token attempt', [
            'token' => $this->maskToken($normalizedToken),
            'title' => $title,
            'data_keys' => array_keys($normalizedData),
            'delivery_mode' => $deliveryMode,
            'data_only' => $dataOnly,
            'includes_notification' => $includesNotification,
            'payload' => $this->maskPayloadToken($payload),
        ]);

        $message = CloudMessage::fromArray($payload);
        try {
            $responsePayload = $this->sendMessage($message, $normalizedToken);
            $providerMessageId = $this->extractProviderMessageId($responsePayload);

            Log::info('FCM send to token success', [
                'token' => $this->maskToken($normalizedToken),
            ]);

            $this->storeDispatchLog([
                'status' => self::STATUS_SENT,
                'target_type' => self::TARGET_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $normalizedToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => $providerMessageId,
                'error_code' => null,
                'error_message' => null,
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => $responsePayload,
                'sent_at' => now(),
            ]);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            $this->storeDispatchLog([
                'status' => self::STATUS_FAILED,
                'target_type' => self::TARGET_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $normalizedToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => null,
                'error_code' => $this->normalizeErrorCode($e),
                'error_message' => $e->getMessage(),
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => null,
                'sent_at' => null,
            ]);

            throw $e;
        }
    }

    /**
     * Send a notification to a single device token with rich options.
     *
     * @param array<string,string> $data
     * @param array<string,mixed> $options
     */
    public function sendToTokenRich(string $token, string $title, string $body, array $data = [], array $options = []): void
    {
        $normalizedToken = $this->normalizeToken($token);
        $normalizedData = $this->normalizeDataPayload($data);
        $deliveryMode = $this->resolveDeliveryMode($normalizedData);
        $notificationType = $this->resolveNotificationType($normalizedData);
        $payload = $this->buildPayloadArray($normalizedToken, $title, $body, $normalizedData);
        $payload = $this->applyRichPayloadOverrides($payload, $options);
        $source = $this->resolveDispatchSource();
        $recipient = $this->resolveRecipientContexts([$normalizedToken])[$normalizedToken] ?? [];

        if (!$this->isLikelyFcmToken($normalizedToken)) {
            Log::warning('Skipping FCM send; token rejected as invalid format', [
                'token' => $normalizedToken,
            ]);

            $this->storeDispatchLog([
                'status' => self::STATUS_SKIPPED,
                'target_type' => self::TARGET_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $normalizedToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => null,
                'error_code' => 'invalid_token_format',
                'error_message' => 'Token rejected as invalid format',
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => null,
                'sent_at' => null,
            ]);

            return;
        }

        $dataOnly = $this->shouldSendDataOnly($normalizedData);
        $includesNotification = array_key_exists('notification', $payload);

        Log::info('FCM send to token attempt', [
            'token' => $this->maskToken($normalizedToken),
            'title' => $title,
            'data_keys' => array_keys($normalizedData),
            'delivery_mode' => $deliveryMode,
            'data_only' => $dataOnly,
            'includes_notification' => $includesNotification,
            'payload' => $this->maskPayloadToken($payload),
        ]);

        $message = CloudMessage::fromArray($payload);
        try {
            $responsePayload = $this->sendMessage($message, $normalizedToken);
            $providerMessageId = $this->extractProviderMessageId($responsePayload);

            Log::info('FCM send to token success', [
                'token' => $this->maskToken($normalizedToken),
            ]);

            $this->storeDispatchLog([
                'status' => self::STATUS_SENT,
                'target_type' => self::TARGET_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $normalizedToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => $providerMessageId,
                'error_code' => null,
                'error_message' => null,
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => $responsePayload,
                'sent_at' => now(),
            ]);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            $this->storeDispatchLog([
                'status' => self::STATUS_FAILED,
                'target_type' => self::TARGET_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $normalizedToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => null,
                'error_code' => $this->normalizeErrorCode($e),
                'error_message' => $e->getMessage(),
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => null,
                'sent_at' => null,
            ]);

            throw $e;
        }
    }

    /**
     * Send to multiple device tokens in one request.
     *
     * @param array<int,string> $tokens
     * @param array<string,string> $data
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): array
    {
        $normalizedData = $this->normalizeDataPayload($data);
        $deliveryMode = $this->resolveDeliveryMode($normalizedData);
        $notificationType = $this->resolveNotificationType($normalizedData);
        $payload = $this->buildPayloadArray('', $title, $body, $normalizedData);
        unset($payload['token']);

        $partitionedTokens = $this->partitionTokensByValidity($tokens);
        $validTokens = $partitionedTokens['valid'];
        $invalidTokens = $partitionedTokens['invalid'];
        $source = $this->resolveDispatchSource();
        $recipientContexts = $this->resolveRecipientContexts(array_merge($validTokens, $invalidTokens));

        foreach ($invalidTokens as $invalidToken) {
            $recipient = $recipientContexts[$invalidToken] ?? [];

            $this->storeDispatchLog([
                'status' => self::STATUS_SKIPPED,
                'target_type' => self::TARGET_MULTICAST_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $invalidToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => null,
                'error_code' => 'invalid_token_format',
                'error_message' => 'Token rejected as invalid format',
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => null,
                'sent_at' => null,
            ]);
        }

        if (empty($validTokens)) {
            return [
                'success' => 0,
                'failure' => count($tokens),
                'results' => [],
            ];
        }

        $message = CloudMessage::fromArray($payload);
        try {
            $response = $this->sendMulticastMessage($message, $validTokens);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            foreach ($validTokens as $targetToken) {
                $recipient = $recipientContexts[$targetToken] ?? [];

                $this->storeDispatchLog([
                    'status' => self::STATUS_FAILED,
                    'target_type' => self::TARGET_MULTICAST_TOKEN,
                    'notification_type' => $notificationType,
                    'delivery_mode' => $deliveryMode,
                    'from_source' => $source['source'],
                    'from_file' => $source['file'],
                    'from_line' => $source['line'],
                    'to_target' => $targetToken,
                    'to_topic' => null,
                    'device_token_id' => $recipient['device_token_id'] ?? null,
                    'user_id' => $recipient['user_id'] ?? null,
                    'owner_model' => $recipient['owner_model'] ?? null,
                    'title' => $title,
                    'notification_text' => $body,
                    'provider_message_id' => null,
                    'error_code' => $this->normalizeErrorCode($e),
                    'error_message' => $e->getMessage(),
                    'data_payload' => $normalizedData,
                    'request_payload' => $payload,
                    'response_payload' => null,
                    'sent_at' => null,
                ]);
            }

            throw $e;
        }

        foreach ($validTokens as $targetToken) {
            $recipient = $recipientContexts[$targetToken] ?? [];
            $result = $response['results'][$targetToken] ?? null;
            $ok = (bool) ($result['ok'] ?? false);

            $this->storeDispatchLog([
                'status' => $ok ? self::STATUS_SENT : self::STATUS_FAILED,
                'target_type' => self::TARGET_MULTICAST_TOKEN,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $targetToken,
                'to_topic' => null,
                'device_token_id' => $recipient['device_token_id'] ?? null,
                'user_id' => $recipient['user_id'] ?? null,
                'owner_model' => $recipient['owner_model'] ?? null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => is_string($result['provider_message_id'] ?? null)
                    ? $result['provider_message_id']
                    : null,
                'error_code' => !$ok ? $this->stringifyValue($result['code'] ?? null, 120) : null,
                'error_message' => !$ok ? $this->stringifyValue($result['error'] ?? null, 65535) : null,
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => is_array($result['response'] ?? null) ? $result['response'] : $result,
                'sent_at' => $ok ? now() : null,
            ]);
        }

        return $response;
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
        $normalizedData = $this->normalizeDataPayload($data);
        $deliveryMode = $this->resolveDeliveryMode($normalizedData);
        $notificationType = $this->resolveNotificationType($normalizedData);
        $payload = $this->buildPayloadArray('', $title, $body, $normalizedData);
        unset($payload['token']);
        $payload['topic'] = $topic;
        $source = $this->resolveDispatchSource();

        $message = CloudMessage::fromArray($payload);
        try {
            if (!empty($tokensToEnsure)) {
                $this->messaging->subscribeToTopic($topic, $tokensToEnsure);
            }

            $responsePayload = $this->sendMessage($message, $topic);

            $this->storeDispatchLog([
                'status' => self::STATUS_SENT,
                'target_type' => self::TARGET_TOPIC,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $topic,
                'to_topic' => $topic,
                'device_token_id' => null,
                'user_id' => null,
                'owner_model' => null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => $this->extractProviderMessageId($responsePayload),
                'error_code' => null,
                'error_message' => null,
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => $responsePayload,
                'sent_at' => now(),
            ]);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            $this->storeDispatchLog([
                'status' => self::STATUS_FAILED,
                'target_type' => self::TARGET_TOPIC,
                'notification_type' => $notificationType,
                'delivery_mode' => $deliveryMode,
                'from_source' => $source['source'],
                'from_file' => $source['file'],
                'from_line' => $source['line'],
                'to_target' => $topic,
                'to_topic' => $topic,
                'device_token_id' => null,
                'user_id' => null,
                'owner_model' => null,
                'title' => $title,
                'notification_text' => $body,
                'provider_message_id' => null,
                'error_code' => $this->normalizeErrorCode($e),
                'error_message' => $e->getMessage(),
                'data_payload' => $normalizedData,
                'request_payload' => $payload,
                'response_payload' => null,
                'sent_at' => null,
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function sendMessage(CloudMessage $message, string $target): array
    {
        try {
            return $this->messaging->send($message);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            Log::error('FCM send failed', [
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
            Log::error('FCM multicast send failed', [
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
            $responsePayload = $sendReport->result();
            $providerMessageId = $this->extractProviderMessageId($responsePayload);

            if ($sendReport->isSuccess()) {
                $results[$token] = [
                    'ok' => true,
                    'provider_message_id' => $providerMessageId,
                    'response' => $responsePayload,
                ];
                continue;
            }

            $error = $sendReport->error();
            $results[$token] = [
                'ok' => false,
                'code' => $error?->getCode(),
                'error' => $error?->getMessage(),
                'provider_message_id' => $providerMessageId,
                'response' => $responsePayload,
            ];
        }

        return $results;
    }

    private function resolveNotificationType(array $normalizedData): ?string
    {
        $candidates = [
            $normalizedData['type'] ?? null,
            $normalizedData['notification_type'] ?? null,
            $normalizedData['event'] ?? null,
            $normalizedData['action'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $trimmed = trim($candidate);
            if ($trimmed === '') {
                continue;
            }

            return $this->truncate($trimmed, 64);
        }

        return null;
    }

    /**
     * @param array<int,string> $tokens
     * @return array<string,array<string,mixed>>
     */
    private function resolveRecipientContexts(array $tokens): array
    {
        if (empty($tokens)) {
            return [];
        }

        $contexts = [];
        $rows = DeviceToken::query()
            ->select(['id', 'user_id', 'token', 'meta'])
            ->whereIn('token', $tokens)
            ->get();

        foreach ($rows as $row) {
            $rowToken = $this->normalizeToken((string) $row->token);
            if ($rowToken === '') {
                continue;
            }

            $meta = is_array($row->meta) ? $row->meta : [];
            $ownerModel = is_string($meta['owner_model'] ?? null) ? $meta['owner_model'] : null;

            $contexts[$rowToken] = [
                'device_token_id' => $row->id,
                'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
                'owner_model' => $ownerModel,
            ];
        }

        return $contexts;
    }

    /**
     * @return array{source:?string,file:?string,line:?int}
     */
    private function resolveDispatchSource(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);

        foreach ($trace as $frame) {
            $class = $frame['class'] ?? null;
            if (!is_string($class) || $class === self::class) {
                continue;
            }

            $function = is_string($frame['function'] ?? null) ? $frame['function'] : 'unknown';
            $source = $class.'@'.$function;
            $file = isset($frame['file']) && is_string($frame['file']) ? $frame['file'] : null;
            $line = isset($frame['line']) ? (int) $frame['line'] : null;

            return [
                'source' => $source,
                'file' => $file,
                'line' => $line,
            ];
        }

        return [
            'source' => null,
            'file' => null,
            'line' => null,
        ];
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function storeDispatchLog(array $attributes): void
    {
        if (self::$notificationTableExists === false) {
            return;
        }

        try {
            $logPayload = [
                'status' => $this->truncate((string) ($attributes['status'] ?? ''), 20),
                'target_type' => $this->truncate((string) ($attributes['target_type'] ?? ''), 32),
                'notification_type' => $this->truncate(
                    $this->stringifyValue($attributes['notification_type'] ?? null, 64),
                    64
                ),
                'delivery_mode' => $this->truncate(
                    $this->stringifyValue($attributes['delivery_mode'] ?? null, 32),
                    32
                ),
                'from_source' => $this->truncate($this->stringifyValue($attributes['from_source'] ?? null, 255), 255),
                'from_file' => $this->truncate($this->stringifyValue($attributes['from_file'] ?? null, 255), 255),
                'from_line' => isset($attributes['from_line']) ? (int) $attributes['from_line'] : null,
                'to_target' => $this->truncate($this->stringifyValue($attributes['to_target'] ?? null, 512), 512),
                'to_topic' => $this->truncate($this->stringifyValue($attributes['to_topic'] ?? null, 191), 191),
                'device_token_id' => isset($attributes['device_token_id']) ? (int) $attributes['device_token_id'] : null,
                'user_id' => isset($attributes['user_id']) ? (int) $attributes['user_id'] : null,
                'owner_model' => $this->truncate($this->stringifyValue($attributes['owner_model'] ?? null, 255), 255),
                'title' => $this->truncate($this->stringifyValue($attributes['title'] ?? null, 255), 255),
                'notification_text' => $this->stringifyValue($attributes['notification_text'] ?? null, 65535),
                'provider_message_id' => $this->truncate(
                    $this->stringifyValue($attributes['provider_message_id'] ?? null, 191),
                    191
                ),
                'error_code' => $this->truncate($this->stringifyValue($attributes['error_code'] ?? null, 120), 120),
                'error_message' => $this->stringifyValue($attributes['error_message'] ?? null, 65535),
                'data_payload' => is_array($attributes['data_payload'] ?? null) ? $attributes['data_payload'] : null,
                'request_payload' => is_array($attributes['request_payload'] ?? null) ? $attributes['request_payload'] : null,
                'response_payload' => is_array($attributes['response_payload'] ?? null) ? $attributes['response_payload'] : null,
                'sent_at' => $attributes['sent_at'] ?? null,
            ];

            if ($this->supportsCallSessionColumn()) {
                $logPayload['call_session'] = $this->resolveCallSessionForFollowUp($attributes);
            }

            FcmNotification::query()->create($logPayload);
        } catch (Throwable $e) {
            if ($this->isMissingTableError($e)) {
                self::$notificationTableExists = false;
            }

            Log::warning('fcm.notification.persist_failed', [
                'error' => $e->getMessage(),
                'status' => $attributes['status'] ?? null,
                'to_target' => $attributes['to_target'] ?? null,
                'target_type' => $attributes['target_type'] ?? null,
            ]);
        }
    }

    private function supportsCallSessionColumn(): bool
    {
        if (self::$notificationCallSessionColumnExists === null) {
            self::$notificationCallSessionColumnExists = Schema::hasTable('fcm_notifications')
                && Schema::hasColumn('fcm_notifications', 'call_session');
        }

        return self::$notificationCallSessionColumnExists;
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function resolveCallSessionForFollowUp(array $attributes): ?string
    {
        $notificationType = strtolower(trim((string) ($attributes['notification_type'] ?? '')));
        if ($notificationType === '') {
            return null;
        }

        $isFollowUpNotification = str_contains($notificationType, 'follow_up')
            || str_contains($notificationType, 'followup');

        if (!$isFollowUpNotification) {
            return null;
        }

        $callSession = $this->stringifyValue($attributes['call_session'] ?? null, 255);
        if ($callSession !== null && trim($callSession) !== '') {
            return $this->truncate($callSession, 255);
        }

        $dataPayload = is_array($attributes['data_payload'] ?? null) ? $attributes['data_payload'] : [];
        $candidates = [
            $dataPayload['call_session'] ?? null,
            $dataPayload['callSession'] ?? null,
            $dataPayload['call_session_id'] ?? null,
            $dataPayload['callSessionId'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->stringifyValue($candidate, 255);
            if ($normalized === null || trim($normalized) === '') {
                continue;
            }

            return $this->truncate($normalized, 255);
        }

        return null;
    }

    private function isMissingTableError(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, '42s02')
            || str_contains($message, 'base table or view not found')
            || str_contains($message, 'fcm_notifications');
    }

    /**
     * @param array<string,mixed>|null $responsePayload
     */
    private function extractProviderMessageId(?array $responsePayload): ?string
    {
        if (!is_array($responsePayload)) {
            return null;
        }

        $name = $responsePayload['name'] ?? null;
        if (!is_string($name)) {
            return null;
        }

        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return $this->truncate($name, 191);
    }

    private function normalizeErrorCode(Throwable $e): ?string
    {
        $code = $e->getCode();
        if (!is_int($code) && !is_string($code)) {
            return null;
        }

        $codeAsString = trim((string) $code);
        if ($codeAsString === '' || $codeAsString === '0') {
            return null;
        }

        return $this->truncate($codeAsString, 120);
    }

    private function truncate(?string $value, int $length): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return substr($value, 0, $length);
    }

    private function stringifyValue(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            return $this->truncate($trimmed, $maxLength);
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return $this->truncate((string) $value, $maxLength);
        }

        $encoded = json_encode($value);
        if ($encoded === false) {
            return null;
        }

        return $this->truncate($encoded, $maxLength);
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

        return substr($token, 0, 6).'...'.substr($token, -6);
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
}
