<?php

namespace App\Helpers;

class RtcTokenBuilder
{
    const RolePublisher = 1;
    const RoleSubscriber = 2;

    private static function hmac($key, $message) {
        return hash_hmac('sha256', $message, $key, true);
    }

    public static function buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs)
    {
        $version = "006";
        $unixTs = time();
        $salt = rand(1, 99999999);
        $uidStr = $uid == 0 ? "" : (string)$uid;
        $message = $appId.$channelName.$uidStr.$unixTs.$salt.$privilegeExpiredTs.$role;
        $signature = self::hmac($appCertificate, $message);
        $content = $signature.pack("V", $unixTs).pack("V", $salt).pack("V", $privilegeExpiredTs);
        return $version.$appId.base64_encode($content);
    }
}
