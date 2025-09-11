<?php
namespace App\Helpers;

class AccessToken
{
    public $appID;
    public $appCertificate;
    public $channelName;
    public $uid;
    public $salt;
    public $ts;
    public $message;

    const Privileges = [
        "JoinChannel"       => 1,
        "PublishAudioStream"=> 2,
        "PublishVideoStream"=> 3,
        "PublishDataStream" => 4
    ];

    public function __construct($appID, $appCertificate, $channelName, $uid)
    {
        $this->appID = $appID;
        $this->appCertificate = $appCertificate;
        $this->channelName = $channelName;
        $this->uid = $uid;
        $this->salt = random_int(1, 99999999);
        $this->ts = time() + 24 * 3600;
        $this->message = [];
    }

    public function addPrivilege($privilege, $expireTimestamp)
    {
        $this->message[$privilege] = $expireTimestamp;
    }

    public function build()
    {
        $msg = $this->packContent($this->message);
        $content = $this->appID.$this->channelName.$this->uid.$msg.$this->ts.$this->salt;
        $signature = hash_hmac('sha256', $content, $this->appCertificate, true);

        $token = "006" . $this->appID . base64_encode($signature);
        return $token;
    }

    private function packContent($msg)
    {
        return json_encode($msg);
    }
}
