<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallRecording;
use App\Models\CallSession;
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
        try {
            $disk = $this->resolveS3Disk();
        } catch (\Throwable $e) {
            \Log::error('Failed to resolve S3 disk', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Storage configuration error: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }

\Log::info('S3 DISK CONFIG', [
    'driver' => config('filesystems.disks.s3.driver'),
    'bucket' => config('filesystems.disks.s3.bucket'),
    'region' => config('filesystems.disks.s3.region'),
    'key_present' => !!config('filesystems.disks.s3.key'),
]);

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
        $disk->put($key, file_get_contents($file->getRealPath()), [
            'ContentType' => $file->getMimeType(),
        ]);

            $url = $disk->url($key);
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

        $session = CallSession::where('channel_name', $identifier)
            ->orWhere('call_identifier', $identifier)
            ->first();

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

        if (!$session?->id) {
            \Log::warning('Skipping recording persistence because call_session_id is missing', [
                'path' => $path,
                'call_identifier' => $data['call_identifier'] ?? $data['call_id'],
            ]);

            return null;
        }

        try {
            return CallRecording::updateOrCreate(
                [
                    'call_session_id' => $session->id,
                    'recording_path' => $path,
                ],
                [
                    'call_identifier' => $data['call_identifier'] ?? $data['call_id'] ?? $session->call_identifier,
                    'doctor_id' => $data['doctor_id'] ?? $session->doctor_id,
                    'patient_id' => $data['patient_id'] ?? $session->patient_id,
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
            \Log::warning('Failed to persist recording record', [
                'path' => $path,
                'call_session_id' => $session->id,
                'call_identifier' => $data['call_identifier'] ?? $data['call_id'] ?? $session->call_identifier,
                'error' => $error->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveS3Region(): void
    {
        if (config('filesystems.disks.s3.region')) {
            return;
        }

        $fallback = env('AWS_DEFAULT_REGION') ?: env('AGORA_STORAGE_REGION') ?: 'ap-south-1';
        if ($fallback) {
            \Log::warning('S3 region was missing; falling back to', ['region' => $fallback]);
            config(['filesystems.disks.s3.region' => $fallback]);
        }
    }

    private function resolveS3Disk()
    {
        $this->resolveS3Region();
        return Storage::disk('s3');
    }
}
