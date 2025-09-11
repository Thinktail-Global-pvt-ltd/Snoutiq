<?php

namespace App\Services;

class AgoraTokenService
{
    const ROLE_PUBLISHER = 1;
    const ROLE_SUBSCRIBER = 2;

    public function generateToken(string $channelName, int $uid, int $role = self::ROLE_PUBLISHER, int $expireSeconds = 3600): string
    {
        $appId = env('AGORA_APP_ID');
        $appCertificate = env('AGORA_APP_CERTIFICATE');

        if (!$appId || !$appCertificate) {
            throw new \Exception("Agora credentials missing in .env");
        }

        $expireTimestamp = time() + $expireSeconds;

        // build content
        $content = $appId . $appCertificate . $channelName . $uid . $role . $expireTimestamp;

        // signature
        $signature = hash_hmac('sha256', $content, $appCertificate, true);

        // final token
        $token = "006" . $appId . base64_encode($signature);

        return $token;
    }
}
