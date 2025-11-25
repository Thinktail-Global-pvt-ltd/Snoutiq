<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallRecording;
use App\Models\CallSession;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecordingUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $disk = null;
        try {
            $disk = Storage::disk('s3');
            \Log::info('S3 DISK CONFIG', [
                'driver' => config('filesystems.disks.s3.driver'),
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
                'key_present' => !!config('filesystems.disks.s3.key'),
            ]);
        } catch (\Throwable $diskError) {
            \Log::warning('Failed to resolve S3 disk via Flysystem, will attempt AWS SDK fallback', [
                'error' => $diskError->getMessage(),
            ]);
        }

        $data = $request->validate([
            'call_id'         => 'nullable|string|max:64',
            'call_identifier' => 'nullable|string|max:64',
            'channel_name'    => 'nullable|string|max:64',
            'doctor_id'       => 'nullable|integer',
            'patient_id'      => 'nullable|integer',
            'uid'             => 'nullable|integer',
            'role'            => 'nullable|string|max:32',
            'metadata'        => 'nullable|string',
            'recording'       => 'required|file|max:512000',
        ]);

        /** @var UploadedFile $file */
        $file = $data['recording'];

        $extension = $file->getClientOriginalExtension()
            ?: $file->guessExtension()
            ?: 'webm';

        $filename = now()->format('YmdHis') . '_' . Str::uuid() . '.' . $extension;
        $key = "recordings/{$filename}";

        try {
            if ($disk) {
                $disk->put($key, file_get_contents($file->getRealPath()), [
                    'ContentType' => $file->getMimeType(),
                ]);
                $url = $disk->url($key);
            } else {
                $url = $this->uploadViaAwsSdk($file, $key);
            }

            $session = $this->resolveCallSession($data);
            $metadata = $this->parseMetadata($request, $data, $file, $key);
            $recording = $this->persistRecordingEntry($session, $data, $key, $url, $file, $metadata);

            $this->attachToSession(
                $data['call_identifier'] ?? $data['call_id'] ?? $data['channel_name'],
                $url,
                $key
            );

            return response()->json([
                'success' => true,
                'path' => $key,
                'url' => $url,
                'call_id' => $data['call_id'] ?? null,
                'doctor_id' => $data['doctor_id'] ?? null,
                'patient_id'=> $data['patient_id'] ?? null,
                'recording_id' => $recording?->id,
                'metadata' => $metadata,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error("S3 upload failed", ['err' => $e->getMessage()]);
            $errorMessage = $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $errorMessage,
                'errors' => [
                    'recording' => [$errorMessage],
                ],
            ], 500);
        }
    }

    private function attachToSession(?string $identifier, string $url, string $key): void
    {
        if (!$identifier) {
            return;
        }

        $sessionQuery = CallSession::where('channel_name', $identifier);

        if (CallSession::supportsColumn('call_identifier')) {
            $sessionQuery->orWhere('call_identifier', $identifier);
        }

        $session = $sessionQuery->first();

        if (!$session) {
            return;
        }

        if (method_exists($session, 'setAttribute')) {
            $session->recording_url = $url;
            $session->recording_file_list = array_filter([$key]);
            $session->recording_status = 'stopped';
            $session->recording_ended_at = now();
            $session->save();
        }
    }

    private function parseMetadata(Request $request, array $data, UploadedFile $file, string $path): array
    {
        $metadata = [];

        if (!empty($data['metadata'])) {
            $decoded = json_decode($data['metadata'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $metadata = array_merge($metadata, $decoded);
            }
        }

        foreach (['call_id', 'call_identifier', 'channel_name', 'doctor_id', 'patient_id', 'uid', 'role'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $metadata[$key] = $data[$key];
            }
        }

        $metadata['file_path'] = $path;
        $metadata['file_name'] = $file->getClientOriginalName();
        $metadata['file_size'] = $file->getSize();
        $metadata['uploaded_at'] = now()->toIso8601String();
        $metadata['call_page_url'] = $request->fullUrl();

        return $metadata;
    }

    private function resolveCallSession(array $data): ?CallSession
    {
        $callIdentifier = $data['call_identifier'] ?? $data['call_id'] ?? null;
        $channelName = $data['channel_name'] ?? null;

        if (!$callIdentifier && !$channelName) {
            return null;
        }

        $query = CallSession::query();
        $supportsCallIdentifier = CallSession::supportsColumn('call_identifier');

        if ($callIdentifier && $supportsCallIdentifier) {
            $query->where('call_identifier', $callIdentifier);
        }

        if ($channelName) {
            if ($callIdentifier) {
                $query->orWhere('channel_name', $channelName);
            } else {
                $query->where('channel_name', $channelName);
            }
        }

        return $query->first();
    }

    private function persistRecordingEntry(?CallSession $session, array $data, string $path, string $url, UploadedFile $file, array $metadata): ?CallRecording
    {
        if (!Schema::hasTable('call_recordings')) {
            \Log::warning('Call recordings table missing; skipping persistence', [
                'path' => $path,
                'call_identifier' => $data['call_identifier'] ?? $data['call_id'] ?? $session?->call_identifier,
            ]);

            return null;
        }

        try {
            $callSessionId = $session?->id;

            return CallRecording::updateOrCreate(
                [
                    'call_session_id' => $callSessionId,
                    'recording_path' => $path,
                ],
                [
                    'call_identifier' => $data['call_identifier'] ?? $data['call_id'] ?? $session?->call_identifier,
                    'doctor_id' => $data['doctor_id'] ?? $session?->doctor_id,
                    'patient_id' => $data['patient_id'] ?? $session?->patient_id,
                    'recording_disk' => 's3',
                    'recording_name' => basename($path),
                    'recording_url' => $url,
                    'recording_status' => 'uploaded',
                    'recording_size' => $file->getSize(),
                    'metadata' => $metadata ?: null,
                    'recorded_at' => now(),
                ]
            );
        } catch (\Throwable $error) {
            \Log::error('Failed to persist recording record', [
                'path' => $path,
                'call_session_id' => $session?->id,
                'call_identifier' => $data['call_identifier'] ?? $data['call_id'] ?? $session?->call_identifier,
                'error' => $error->getMessage(),
            ]);

            throw $error;
        }
    }

    private function uploadViaAwsSdk(UploadedFile $file, string $key): string
    {
        $config = $this->awsConfig();
        $bucket = $config['bucket'];

        if (!$config['credentials']['key'] || !$config['credentials']['secret'] || !$bucket) {
            throw new \RuntimeException('AWS credentials or bucket not configured.');
        }

        try {
            $client = new S3Client($config['client']);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to initialize AWS SDK client: ' . $exception->getMessage(), 0, $exception);
        }

        try {
            $client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $file->getMimeType(),
            ]);
        } catch (AwsException $awsException) {
            throw new \RuntimeException('AWS SDK upload failed: ' . $awsException->getAwsErrorMessage(), 0, $awsException);
        }

        return $client->getObjectUrl($bucket, $key);
    }

    private function awsConfig(): array
    {
        $bucket = config('filesystems.disks.s3.bucket') ?? env('AWS_BUCKET');
        $region = config('filesystems.disks.s3.region') ?? env('AWS_DEFAULT_REGION', 'ap-south-1');
        $key = config('filesystems.disks.s3.key') ?? env('AWS_ACCESS_KEY_ID');
        $secret = config('filesystems.disks.s3.secret') ?? env('AWS_SECRET_ACCESS_KEY');
        $endpoint = config('filesystems.disks.s3.endpoint');
        $usePathStyle = config('filesystems.disks.s3.use_path_style_endpoint', false);

        $clientConfig = [
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ];

        if ($endpoint) {
            $clientConfig['endpoint'] = $endpoint;
            $clientConfig['use_path_style_endpoint'] = $usePathStyle;
        }

        return [
            'bucket' => $bucket,
            'credentials' => ['key' => $key, 'secret' => $secret],
            'client' => $clientConfig,
        ];
    }
}
