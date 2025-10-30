<?php

namespace App\Services;

use App\Models\DoctorPushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebPushService
{
    public function __construct(
        private readonly string $publicKey,
        private readonly string $privateKey,
        private readonly string $subject,
        private readonly int $ttlSeconds = 60
    ) {
    }

    public function send(DoctorPushSubscription $subscription, int $ttl = null): bool
    {
        $endpoint = $subscription->endpoint;
        $audience = $this->buildAudience($endpoint);

        if ($audience === null) {
            Log::warning('webpush: unable to determine audience for endpoint', ['endpoint' => $endpoint]);
            return false;
        }

        $token = $this->createVapidToken($audience, $ttl ?? $this->ttlSeconds);

        if ($token === null) {
            Log::warning('webpush: unable to sign vapid token');
            return false;
        }

        $headers = [
            'TTL' => (string) ($ttl ?? $this->ttlSeconds),
            'Authorization' => 'WebPush ' . $token,
            'Crypto-Key' => 'p256ecdsa=' . $this->publicKey,
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(5)
                ->withBody('', 'application/octet-stream')
                ->post($endpoint);

            if ($response->status() === 404 || $response->status() === 410) {
                Log::info('webpush: subscription expired, deleting', ['endpoint' => $endpoint]);
                $subscription->delete();
                return false;
            }

            if ($response->successful() || in_array($response->status(), [201, 202, 204], true)) {
                $subscription->markNotified();
                return true;
            }

            Log::warning('webpush: push request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Throwable $exception) {
            Log::error('webpush: exception sending push', [
                'message' => $exception->getMessage(),
                'endpoint' => $endpoint,
            ]);
        }

        return false;
    }

    private function buildAudience(string $endpoint): ?string
    {
        try {
            $url = parse_url($endpoint);
            if (!$url || empty($url['scheme']) || empty($url['host'])) {
                return null;
            }

            $audience = $url['scheme'] . '://' . $url['host'];
            if (!empty($url['port'])) {
                $audience .= ':' . $url['port'];
            }

            return $audience;
        } catch (Throwable) {
            return null;
        }
    }

    private function createVapidToken(string $audience, int $ttl): ?string
    {
        $now = time();
        $expiration = $now + max($ttl, 60);

        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => $expiration,
            'sub' => $this->subject,
        ], JSON_THROW_ON_ERROR));

        $data = $header . '.' . $payload;

        $signature = $this->sign($data);
        if ($signature === null) {
            return null;
        }

        return $data . '.' . $signature;
    }

    private function sign(string $data): ?string
    {
        $privateKeyResource = $this->createPrivateKeyResource();
        if ($privateKeyResource === null) {
            return null;
        }

        $signature = '';
        $result = openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKeyResource);

        if (!$result) {
            return null;
        }

        return $this->base64UrlEncode($this->derToJose($signature));
    }

    private function createPrivateKeyResource(): mixed
    {
        $rawPrivate = $this->base64UrlDecode($this->privateKey);
        $rawPublic = $this->base64UrlDecode($this->publicKey);

        if ($rawPrivate === null || $rawPublic === null) {
            return null;
        }

        if (strlen($rawPublic) === 65 && ord($rawPublic[0]) === 0x04) {
            $rawPublic = substr($rawPublic, 1);
        }

        if (strlen($rawPublic) !== 64 || strlen($rawPrivate) !== 32) {
            return null;
        }

        $der = $this->generateDerPrivateKey($rawPrivate, $rawPublic);
        $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";

        return openssl_pkey_get_private($pem);
    }

    private function generateDerPrivateKey(string $rawPrivate, string $rawPublic): string
    {
        $hexPrivate = bin2hex($rawPrivate);
        $hexPublic = '04' . bin2hex($rawPublic);
        $der = '3077440201010420' . $hexPrivate . 'a00706052b8104000aa144034200' . $hexPublic;

        return hex2bin($der);
    }

    private function derToJose(string $der): string
    {
        $offset = ord($der[3]);
        $offset += 4;
        $offset += ord($der[$offset + 1]);
        $offset += 2;

        $rLength = ord($der[$offset + 1]);
        $r = substr($der, $offset + 2, $rLength);
        $offset += $rLength + 2;

        $sLength = ord($der[$offset + 1]);
        $s = substr($der, $offset + 2, $sLength);

        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
