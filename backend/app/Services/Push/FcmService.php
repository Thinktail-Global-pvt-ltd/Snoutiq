<?php

namespace App\Services\Push;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\DeviceToken;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;

class FcmService
{
    public function __construct(private readonly Messaging $messaging)
    {
    }

    /**
     * Send a notification to a single device token.
     *
     * @param array<string,string> $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->sendMessage($message, $token);
    }

    /**
     * Send to multiple device tokens in one request.
     *
     * @param array<int,string> $tokens
     * @param array<string,string> $data
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->sendMulticastMessage($message, $tokens);
    }

    /**
     * Convenience: send to all tokens of a user id.
     *
     * @param array<string,string> $data
     */
    public function notifyUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('user_id', $userId)->pluck('token')->all();
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
    private function sendMulticastMessage(CloudMessage $message, array $tokens): void
    {
        try {
            $this->messaging->sendMulticast($message, $tokens);
        } catch (MessagingException | FirebaseException | Throwable $e) {
            \Log::error('FCM multicast send failed', [
                'tokens' => $tokens,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
