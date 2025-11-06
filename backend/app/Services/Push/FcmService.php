<?php

namespace App\Services\Push;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\DeviceToken;

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

        $this->messaging->send($message);
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

        $this->messaging->sendMulticast($message, $tokens);
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

        $this->messaging->send($message);
    }
}
