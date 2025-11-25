<?php

namespace App\Services;

use App\Helpers\RtcTokenBuilder;
use App\Jobs\GenerateCallTranscript;
use App\Models\CallRecording;
use App\Models\CallSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CallRecordingManager
{
    public function __construct(
        protected AgoraRecordingService $agoraRecording
    ) {
    }

    public function start(CallSession $session, array $options = []): array
    {
        if (!$session->channel_name) {
            throw ValidationException::withMessages([
                'channel_name' => 'Channel name is required to start Agora recording.',
            ]);
        }

        if ($session->hasActiveRecording()) {
            return [
                'session' => $session->fresh(),
                'status' => 'already_recording',
            ];
        }

        $uid = (string) ($options['uid'] ?? config('services.agora.recording.uid', '10000'));
        $token = $options['token'] ?? $this->generateRecordingToken($session, $uid);
        $resourceExpire = $options['resource_expire_hours'] ?? null;

        $resource = $this->agoraRecording->acquire(
            $session->channel_name,
            $uid,
            $resourceExpire
        );

        $resourceId = $resource['resourceId'] ?? null;
        if (!$resourceId) {
            throw ValidationException::withMessages([
                'resource' => 'Agora did not return a resourceId.',
            ]);
        }

        $start = $this->agoraRecording->start(
            $session->channel_name,
            $resourceId,
            $uid,
            $token
        );

        $sid = $start['sid'] ?? null;
        if (!$sid) {
            throw ValidationException::withMessages([
                'sid' => 'Agora did not return a recording SID.',
            ]);
        }

        $session->recording_resource_id = $resourceId;
        $session->recording_sid = $sid;
        $session->recording_status = 'recording';
        $session->recording_started_at = now();
        $session->save();

        return [
            'session' => $session->fresh(),
            'resource' => $resource,
            'start' => $start,
            'status' => 'recording',
        ];
    }

    public function stop(CallSession $session, array $options = []): array
    {
        if (!$session->recording_resource_id || !$session->recording_sid) {
            throw ValidationException::withMessages([
                'recording' => 'No active Agora recording for this call.',
            ]);
        }

        $uid = (string) ($options['uid'] ?? config('services.agora.recording.uid', '10000'));

        try {
            $response = $this->agoraRecording->stop(
                $session->channel_name,
                $session->recording_resource_id,
                $session->recording_sid,
                $uid
            );
        } catch (RuntimeException $exception) {
            if (!$this->isWorkerMissing($exception)) {
                throw $exception;
            }

            $response = [
                'serverResponse' => null,
                'message' => 'Recording worker already cleaned up on Agora side.',
            ];
        }

        $session->recording_status = 'stopped';
        $session->recording_ended_at = now();
        $session->recording_file_list = Arr::get($response, 'serverResponse');
        $session->recording_url = Arr::get($response, 'serverResponse.fileList.0.fileName', $session->recording_url);
        $session->save();

        $this->persistRecordingLog($session, $response);

        if ($this->shouldTranscribe($options['transcribe'] ?? null)) {
            $this->queueTranscript($session, $session->recording_url);
        }

        return [
            'session' => $session->fresh(),
            'stop' => $response,
            'status' => 'stopped',
        ];
    }

    public function status(CallSession $session, array $options = []): array
    {
        if (!$session->recording_resource_id || !$session->recording_sid) {
            throw ValidationException::withMessages([
                'recording' => 'Recording has not been started for this session.',
            ]);
        }

        $uid = (string) ($options['uid'] ?? config('services.agora.recording.uid', '10000'));

        $status = $this->queryRecordingStatusWithRetry($session, $uid);

        return [
            'session' => $session->fresh(),
            'status' => $status,
        ];
    }

    public function queueTranscript(CallSession $session, ?string $recordingUrl = null): void
    {
        $session->transcript_status = 'queued';
        $session->transcript_requested_at = now();
        $session->save();

        GenerateCallTranscript::dispatch($session->id, $recordingUrl ?? $session->recording_url);
    }

    protected function persistRecordingLog(CallSession $session, array $response): void
    {
        $files = $this->resolveFileList($response);

        if (empty($files)) {
            $this->storeRecordingEntry($session, null, $response);
            return;
        }

        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }

            $this->storeRecordingEntry($session, $file, $response);
        }
    }

    protected function storeRecordingEntry(CallSession $session, ?array $file, array $response): void
    {
        $path = $this->extractFilePath($file);
        if (!$path) {
            $path = $session->resolvedRecordingFilePath();
        }

        if (!$path) {
            return;
        }

        $disk = $session->recordingDisk();
        $metadata = $file ?? Arr::get($response, 'serverResponse') ?? $response;
        $size = $this->extractFileSize($file);
        $url = $this->resolveRecordingUrl($session, $disk, $path, $metadata);

        CallRecording::updateOrCreate(
            [
                'call_session_id' => $session->id,
                'recording_path' => $path,
            ],
            [
                'call_identifier' => $session->call_identifier,
                'doctor_id' => $session->doctor_id,
                'patient_id' => $session->patient_id,
                'recording_disk' => $disk,
                'recording_name' => basename($path),
                'recording_url' => $url,
                'recording_status' => $session->recording_status,
                'recording_size' => $size,
                'metadata' => $metadata ?: null,
                'recorded_at' => $session->recording_ended_at ?? now(),
            ]
        );
    }

    protected function resolveFileList(array $response): array
    {
        foreach ([
            Arr::get($response, 'serverResponse.fileList'),
            Arr::get($response, 'serverResponse.file_list'),
            Arr::get($response, 'serverResponse.files'),
            Arr::get($response, 'fileList'),
            Arr::get($response, 'file_list'),
            Arr::get($response, 'files'),
        ] as $candidate) {
            if (is_array($candidate) && !empty($candidate)) {
                return $candidate;
            }
        }

        return [];
    }

    protected function resolveRecordingUrl(CallSession $session, string $disk, string $path, ?array $metadata): ?string
    {
        if ($metadata) {
            foreach (['fileUrl', 'file_url', 'recordingUrl', 'recording_url'] as $field) {
                $value = Arr::get($metadata, $field);
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable $exception) {
            Log::warning('Unable to resolve recording URL for session', [
                'session_id' => $session->id,
                'disk' => $disk,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    protected function extractFilePath(?array $file): ?string
    {
        if (!$file) {
            return null;
        }

        foreach (['fileName', 'file_name', 'filePath', 'file_path', 'filename', 'path'] as $key) {
            $value = Arr::get($file, $key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function extractFileSize(?array $file): ?int
    {
        if (!$file) {
            return null;
        }

        $size = Arr::get($file, 'fileSize', Arr::get($file, 'file_size'));
        if ($size === null) {
            return null;
        }

        return is_numeric($size) ? (int) $size : null;
    }

    protected function shouldTranscribe(?bool $explicit = null): bool
    {
        if ($explicit !== null) {
            return $explicit;
        }

        return (bool) config('services.agora.recording.auto_transcribe', false);
    }

    protected function generateRecordingToken(CallSession $session, string $uid): ?string
    {
        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.certificate');

        if (!$appId || !$appCertificate) {
            Log::warning('Agora recording token skipped; missing credentials', [
                'session_id' => $session->id,
            ]);

            return null;
        }

        $ttl = (int) config('services.agora.recording.token_ttl', 3600);
        $expiresAt = time() + max($ttl, 60);

        return RtcTokenBuilder::buildTokenWithUid(
            $appId,
            $appCertificate,
            $session->channel_name,
            (int) $uid,
            RtcTokenBuilder::RolePublisher,
            $expiresAt
        );
    }

    protected function queryRecordingStatusWithRetry(CallSession $session, string $uid, int $maxAttempts = 3, int $sleepMilliseconds = 600): array
    {
        $attempt = 0;

        do {
            try {
                return $this->agoraRecording->query(
                    $session->channel_name,
                    $session->recording_resource_id,
                    $session->recording_sid,
                    $uid
                );
            } catch (RuntimeException $exception) {
                if (!$this->isWorkerNotReady($exception)) {
                    throw $exception;
                }

                $attempt++;
                if ($attempt >= $maxAttempts) {
                    throw ValidationException::withMessages([
                        'recording' => 'Recording worker not ready yet. Try again in a few seconds.',
                    ]);
                }

                usleep(max(1, $sleepMilliseconds) * 1000);
            }
        } while ($attempt < $maxAttempts);

        throw ValidationException::withMessages([
            'recording' => 'Recording worker not ready yet. Try again later.',
        ]);
    }

    protected function isWorkerNotReady(RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'failed to find worker');
    }

    protected function isWorkerMissing(RuntimeException $exception): bool
    {
        return $this->isWorkerNotReady($exception);
    }
}
