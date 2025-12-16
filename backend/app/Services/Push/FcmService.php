<?php

namespace App\Services\Push;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
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

        \Log::info('FCM send to token attempt', [
            'token' => $this->maskToken($normalizedToken),
            'title' => $title,
            'data_keys' => array_keys($data),
        ]);

        $message = CloudMessage::withTarget('token', $normalizedToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->sendMessage($message, $normalizedToken);

        \Log::info('FCM send to token success', [
            'token' => $this->maskToken($normalizedToken),
        ]);
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

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

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

        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->sendMessage($message, $topic);
    }

    private function sendMessage(CloudMessage $message, string $target): void
    {
        try {
            $this->messaging->send($message);
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
