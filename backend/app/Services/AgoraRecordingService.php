<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AgoraRecordingService
{
    protected string $appId;
    protected string $restEndpoint;
    protected string $mode;
    protected array $recordingConfig;
    protected string $customerId;
    protected string $customerSecret;

    public function __construct()
    {
        $this->appId = (string) config('services.agora.app_id');
        $this->mode = config('services.agora.mode', 'mix');
        $this->restEndpoint = rtrim((string) config('services.agora.rest_endpoint', 'https://api.agora.io'), '/');
        $this->recordingConfig = config('services.agora.recording', []);
        $this->customerId = (string) config('services.agora.customer_id');
        $this->customerSecret = (string) config('services.agora.customer_secret');

        if (empty($this->appId) || empty($this->customerId) || empty($this->customerSecret)) {
            throw new RuntimeException('Agora recording credentials are not configured.');
        }
    }

    public function acquire(string $channelName, string $uid, ?int $resourceExpireHours = null): array
    {
        $payload = [
            'cname' => $channelName,
            'uid' => $uid,
            'clientRequest' => [
                'resourceExpiredHour' => $resourceExpireHours ?? Arr::get($this->recordingConfig, 'resource_expire_hours', 24),
            ],
        ];

        return $this->request('post', "/v1/apps/{$this->appId}/cloud_recording/acquire", $payload);
    }

    public function start(string $channelName, string $resourceId, string $uid, ?string $token = null, array $overrides = []): array
    {
        $payload = [
            'cname' => $channelName,
            'uid' => $uid,
            'clientRequest' => array_replace_recursive(
                $this->defaultClientRequest($token),
                $overrides['clientRequest'] ?? []
            ),
        ];

        return $this->request(
            'post',
            "/v1/apps/{$this->appId}/cloud_recording/resourceid/{$resourceId}/mode/{$this->mode}/start",
            $payload
        );
    }

    public function stop(string $channelName, string $resourceId, string $sid, string $uid): array
    {
        $payload = [
            'cname' => $channelName,
            'uid' => $uid,
            'clientRequest' => [
                'async_stop' => false,
            ],
        ];

        return $this->request(
            'post',
            "/v1/apps/{$this->appId}/cloud_recording/resourceid/{$resourceId}/sid/{$sid}/mode/{$this->mode}/stop",
            $payload
        );
    }

    public function query(string $channelName, string $resourceId, string $sid, string $uid): array
    {
        $params = [
            'cname' => $channelName,
            'uid' => $uid,
        ];

        return $this->request(
            'get',
            "/v1/apps/{$this->appId}/cloud_recording/resourceid/{$resourceId}/sid/{$sid}/mode/{$this->mode}/query",
            $params
        );
    }

    protected function defaultClientRequest(?string $token = null): array
    {
        $subscribeUids = array_values(array_filter(array_map(
            static fn ($uid) => trim((string) $uid),
            Arr::wrap(Arr::get($this->recordingConfig, 'subscribe_uids', ['#allstream#']))
        )));

        if (empty($subscribeUids)) {
            $subscribeUids = ['#allstream#'];
        }

        return array_filter([
            'token' => $token,
            'recordingConfig' => [
                'channelType' => Arr::get($this->recordingConfig, 'channel_type', 1),
                'streamTypes' => Arr::get($this->recordingConfig, 'stream_types', 2),
                'audioProfile' => 1,
                'videoStreamType' => 0,
                'maxIdleTime' => Arr::get($this->recordingConfig, 'max_idle_time', 30),
                'subscribeVideoUids' => $subscribeUids,
                'subscribeAudioUids' => $subscribeUids,
            ],
            'recordingFileConfig' => [
                'avFileType' => ['hls', 'mp4'],
            ],
            'storageConfig' => $this->storageConfig(),
            'snapshotConfig' => [
                'captureInterval' => 5,
                'fileType' => ['jpg'],
            ],
        ]);
    }

    protected function storageConfig(): array
    {
        $diskName = Arr::get($this->recordingConfig, 'disk', config('filesystems.default'));
        $diskConfig = config("filesystems.disks.{$diskName}", []);

        $bucket = Arr::get($this->recordingConfig, 'bucket') ?: Arr::get($diskConfig, 'bucket');
        $accessKey = Arr::get($this->recordingConfig, 'access_key') ?: Arr::get($diskConfig, 'key');
        $secretKey = Arr::get($this->recordingConfig, 'secret_key') ?: Arr::get($diskConfig, 'secret');

        if (!$bucket || !$accessKey || !$secretKey) {
            throw new RuntimeException('Agora storage configuration is incomplete. Set AGORA_STORAGE_BUCKET/ACCESS_KEY/SECRET_KEY or ensure your default filesystem disk has bucket/key/secret.');
        }

        $prefix = array_values(array_filter(explode('/', Arr::get($this->recordingConfig, 'file_path', 'agora/recordings'))));

        return [
            'vendor' => Arr::get($this->recordingConfig, 'vendor', 3),
            'region' => Arr::get($this->recordingConfig, 'region', 14),
            'bucket' => $bucket,
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'fileNamePrefix' => $prefix,
        ];
    }

    protected function request(string $method, string $path, array $payload): array
    {
        $client = $this->httpClient();

        try {
            $response = $method === 'get'
                ? $client->get($path, $payload)
                : $client->{$method}($path, $payload);
        } catch (RequestException $exception) {
            $this->throwAgoraException($path, $payload, $exception->response, $exception);
        }

        if ($response->failed()) {
            $this->throwAgoraException($path, $payload, $response);
        }

        return $response->json();
    }

    protected function httpClient(): PendingRequest
    {
        $auth = base64_encode("{$this->customerId}:{$this->customerSecret}");

        return Http::baseUrl($this->restEndpoint)
            ->withHeaders([
                'Authorization' => "Basic {$auth}",
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->timeout(30)
            ->retry(3, 200);
    }

    /**
     * @throws RuntimeException
     */
    protected function throwAgoraException(string $path, array $payload, ?Response $response = null, ?Throwable $previous = null): void
    {
        $body = $response?->json() ?? $response?->body();

        Log::error('Agora recording API request failed', [
            'path' => $path,
            'payload' => $payload,
            'status' => $response?->status(),
            'body' => $body,
        ]);

        if (is_array($body)) {
            $body = json_encode($body);
        }

        throw new RuntimeException(
            sprintf('Agora recording API error: %s', $body ?: 'Unknown error'),
            0,
            $previous
        );
    }
}
