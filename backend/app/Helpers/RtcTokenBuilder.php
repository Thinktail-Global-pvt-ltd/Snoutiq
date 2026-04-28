<?php

namespace App\Helpers;

class RtcTokenBuilder
{
    public const RolePublisher = 1;
    public const RoleSubscriber = 2;

    private const VERSION = '006';
    private const PRIVILEGE_JOIN_CHANNEL = 1;
    private const PRIVILEGE_PUBLISH_AUDIO_STREAM = 2;
    private const PRIVILEGE_PUBLISH_VIDEO_STREAM = 3;
    private const PRIVILEGE_PUBLISH_DATA_STREAM = 4;

    public static function buildTokenWithUid(
        string $appId,
        string $appCertificate,
        string $channelName,
        int|string $uid,
        int $role,
        int $privilegeExpiredTs
    ): string {
        $uidString = ((string) $uid === '0') ? '' : (string) $uid;
        $salt = random_int(1, 0xffffffff);
        $privileges = [
            self::PRIVILEGE_JOIN_CHANNEL => $privilegeExpiredTs,
        ];

        if ((int) $role === self::RolePublisher) {
            $privileges[self::PRIVILEGE_PUBLISH_AUDIO_STREAM] = $privilegeExpiredTs;
            $privileges[self::PRIVILEGE_PUBLISH_VIDEO_STREAM] = $privilegeExpiredTs;
            $privileges[self::PRIVILEGE_PUBLISH_DATA_STREAM] = $privilegeExpiredTs;
        }

        $message = self::packMessage($salt, time() + 86400, $privileges);
        $signature = hash_hmac('sha256', $appId . $channelName . $uidString . $message, $appCertificate, true);
        $content = self::packBytes($signature)
            . self::packUint32(self::crc32Unsigned($channelName))
            . self::packUint32(self::crc32Unsigned($uidString))
            . self::packBytes($message);

        return self::VERSION . $appId . base64_encode($content);
    }

    /**
     * Agora AccessToken v006 message payload.
     *
     * @param array<int,int> $privileges privilege id => absolute expiry timestamp
     */
    private static function packMessage(int $salt, int $ts, array $privileges): string
    {
        $payload = self::packUint32($salt)
            . self::packUint32($ts)
            . self::packUint16(count($privileges));

        foreach ($privileges as $privilege => $expire) {
            $payload .= self::packUint16((int) $privilege) . self::packUint32((int) $expire);
        }

        return $payload;
    }

    private static function packBytes(string $value): string
    {
        return self::packUint16(strlen($value)) . $value;
    }

    private static function packUint16(int $value): string
    {
        return pack('v', $value);
    }

    private static function packUint32(int $value): string
    {
        return pack('V', $value);
    }

    private static function crc32Unsigned(string $value): int
    {
        return (int) sprintf('%u', crc32($value));
    }
}
