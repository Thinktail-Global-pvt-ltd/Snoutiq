<?php

namespace App\Services;

use App\Models\CallSession;
use App\Support\GeminiConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TranscriptService
{
    public function generateFromCallSession(CallSession $session, ?string $recordingReference = null): array
    {
        $provider = config('services.transcript.provider', 'openai');
        $tempFile = $this->downloadRecordingToTemp($session, $recordingReference);

        try {
            return match ($provider) {
                'openai' => $this->generateWithOpenAI($tempFile),
                'gemini' => $this->generateWithGemini($tempFile),
                default => throw new RuntimeException("Unsupported transcript provider [{$provider}]"),
            };
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    protected function generateWithOpenAI(string $filePath): array
    {
        $config = config('services.transcript.openai', []);
        $apiKey = Arr::get($config, 'api_key');

        if (!$apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = Arr::get($config, 'model', 'gpt-4o-mini-transcribe');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])
            ->attach('file', file_get_contents($filePath), basename($filePath) . '.aac')
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => $model,
                'response_format' => 'verbose_json',
            ]);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'Transcript API failed.');
        }

        $payload = $response->json();

        return [
            'text' => $payload['text'] ?? null,
            'segments' => $payload['segments'] ?? null,
            'raw' => $payload,
        ];
    }

    protected function generateWithGemini(string $filePath): array
    {
        $config = config('services.transcript.gemini', []);
        $apiKey = trim($config['api_key'] ?? '') ?: trim(GeminiConfig::apiKey());

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $model = $config['model'] ?? GeminiConfig::defaultModel();
        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);
        $audioBinary = file_get_contents($filePath);

        if ($audioBinary === false) {
            throw new RuntimeException('Failed to read recording for Gemini transcription.');
        }

        $mimeType = $this->detectMimeType($filePath);
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => 'Transcribe this veterinary consultation audio. Provide clean verbatim text with speaker cues if clear.'],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => base64_encode($audioBinary),
                        ],
                    ],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.9,
                'maxOutputTokens' => 8192,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->post($endpoint, $payload);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new RuntimeException($error ?: 'Gemini transcript API failed.');
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (!$text) {
            throw new RuntimeException('Gemini transcript response did not include text.');
        }

        return [
            'text' => trim($text),
            'raw' => $response->json(),
        ];
    }

    protected function downloadRecordingToTemp(CallSession $session, ?string $recordingReference = null): string
    {
        $candidateUrl = $recordingReference;

        if (!$candidateUrl && method_exists($session, 'recordingTemporaryUrl')) {
            $candidateUrl = $session->recordingTemporaryUrl(now()->addMinutes(120));
        }

        $binary = null;

        if ($candidateUrl) {
            $binary = $this->downloadBinaryFromUrl($candidateUrl);
        }

        if (!$binary) {
            $binary = $this->downloadBinaryFromDisk($session, $recordingReference);
        }

        if (!$binary) {
            throw new RuntimeException('Unable to download recording file for transcription.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'agora_audio_');
        file_put_contents($tempFile, $binary);

        return $tempFile;
    }

    protected function downloadBinaryFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(120)->get($url);

            if ($response->failed()) {
                Log::warning('Failed to download recording via URL', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::error('Recording download via URL errored', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function downloadBinaryFromDisk(CallSession $session, ?string $reference = null): ?string
    {
        $diskName = $session->recordingDisk();
        $disk = Storage::disk($diskName);
        $path = $reference ?: $session->resolvedRecordingFilePath();

        if (!$path) {
            return null;
        }

        if (!$disk->exists($path)) {
            Log::warning('Recording file missing from disk', [
                'session_id' => $session->id,
                'disk' => $diskName,
                'path' => $path,
            ]);

            return null;
        }

        return $disk->get($path);
    }

    protected function detectMimeType(string $filePath): string
    {
        $mime = null;

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($filePath);
        }

        if (!$mime && class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath) ?: null;
        }

        return $mime ?: 'audio/mpeg';
    }
}
