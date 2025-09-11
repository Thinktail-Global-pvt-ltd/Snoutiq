<?php

namespace App\Helpers;

// ye bhi include karna padega

class RtcTokenBuilder
{
    const Role_Publisher = 1;
    const Role_Subscriber = 2;

    public static function buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs)
    {
        $token = new AccessToken($appID, $appCertificate, $channelName, $uid);
        $token->addPrivilege(AccessToken::Privileges["JoinChannel"], $privilegeExpiredTs);

        if ($role == self::Role_Publisher) {
            $token->addPrivilege(AccessToken::Privileges["PublishAudioStream"], $privilegeExpiredTs);
            $token->addPrivilege(AccessToken::Privileges["PublishVideoStream"], $privilegeExpiredTs);
            $token->addPrivilege(AccessToken::Privileges["PublishDataStream"], $privilegeExpiredTs);
        }

        return $token->build();
    }
}
