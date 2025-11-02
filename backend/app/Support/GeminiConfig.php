<?php

namespace App\Support;

use Dotenv\Dotenv;

final class GeminiConfig
{
    private const DEFAULT_MODEL = 'gemini-1.5-flash';
    private const DEFAULT_CHAT_MODEL = 'gemini-2.0-flash';

    private static ?array $envFileCache = null;

    public static function apiKey(): string
    {
        $value = config('services.gemini.api_key');
        if (empty($value)) {
            $value = env('GEMINI_API_KEY') ?: env('GOOGLE_API_KEY');
        }
        if (empty($value)) {
            $value = self::envFileValue('GEMINI_API_KEY') ?? self::envFileValue('GOOGLE_API_KEY');
        }

        return trim((string) ($value ?? ''));
    }

    public static function defaultModel(): string
    {
        $value = config('services.gemini.model');
        if (empty($value)) {
            $value = env('GEMINI_MODEL');
        }
        if (empty($value)) {
            $value = self::envFileValue('GEMINI_MODEL');
        }

        return trim((string) ($value ?? self::DEFAULT_MODEL));
    }

    public static function chatModel(): string
    {
        $value = config('services.gemini.chat_model');
        if (empty($value)) {
            $value = env('GEMINI_CHAT_MODEL');
        }
        if (empty($value)) {
            $value = self::envFileValue('GEMINI_CHAT_MODEL');
        }

        return trim((string) ($value ?? self::DEFAULT_CHAT_MODEL));
    }

    private static function envFileValue(string $key): ?string
    {
        $entries = self::loadEnvFile();

        if ($entries === null) {
            return null;
        }

        return array_key_exists($key, $entries) ? $entries[$key] : null;
    }

    private static function loadEnvFile(): ?array
    {
        if (self::$envFileCache !== null) {
            return self::$envFileCache;
        }

        $path = base_path('.env');
        if (!is_readable($path)) {
            self::$envFileCache = [];
            return self::$envFileCache;
        }

        try {
            $contents = file_get_contents($path);
            self::$envFileCache = $contents !== false ? Dotenv::parse($contents) : [];
        } catch (\Throwable $e) {
            self::$envFileCache = [];
        }

        return self::$envFileCache;
    }
}
