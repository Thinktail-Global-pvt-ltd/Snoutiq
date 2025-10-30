<?php

namespace App\Support;

use Illuminate\Support\Str;

class CallSessionUrlBuilder
{
    public const IDENTIFIER_MAX_LENGTH = 64;
    public const CHANNEL_MAX_LENGTH = 64;

    public static function ensureIdentifier(?string $value = null): string
    {
        return self::normalizeIdentifier($value) ?? self::generateIdentifier();
    }

    public static function normalizeIdentifier(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $filtered = preg_replace('/[^A-Za-z0-9_\-]/', '', $value);
        if (!is_string($filtered)) {
            return null;
        }

        $trimmed = substr($filtered, 0, self::IDENTIFIER_MAX_LENGTH);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function generateIdentifier(): string
    {
        return 'call_' . Str::lower(Str::random(18));
    }

    public static function ensureChannel(?string $value, string $identifier): string
    {
        return self::normalizeChannel($value) ?? self::defaultChannel($identifier);
    }

    public static function normalizeChannel(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $filtered = preg_replace('/[^A-Za-z0-9_\-]/', '', $value);
        if (!is_string($filtered)) {
            return null;
        }

        $trimmed = substr($filtered, 0, self::CHANNEL_MAX_LENGTH);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function defaultChannel(string $identifier): string
    {
        return 'channel_' . $identifier;
    }

    public static function frontendBase(): string
    {
        $candidates = [
            config('app.frontend_base'),
            env('FRONTEND_BASE'),
            config('app.url'),
            url('/'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && $value !== '') {
                return rtrim($value, '/');
            }
        }

        return '';
    }

    public static function doctorJoinUrl(
        ?string $channel,
        ?string $identifier,
        ?int $doctorId,
        ?int $patientId,
        ?string $base = null
    ): ?string {
        if (!$channel || !$identifier || !$doctorId) {
            return null;
        }

        $baseUrl = $base ? rtrim($base, '/') : self::frontendBase();
        if ($baseUrl === '') {
            return null;
        }

        $params = [
            'uid' => $doctorId,
            'doctorId' => $doctorId,
            'role' => 'host',
            'pip' => '1',
            'callId' => $identifier,
        ];

        if ($patientId) {
            $params['patientId'] = $patientId;
        }

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return sprintf('%s/call-page/%s?%s', $baseUrl, rawurlencode($channel), $query);
    }

    public static function patientPaymentUrl(
        ?string $channel,
        ?string $identifier,
        ?int $doctorId,
        ?int $patientId,
        ?string $base = null
    ): ?string {
        if (!$channel || !$identifier || !$doctorId || !$patientId) {
            return null;
        }

        $baseUrl = $base ? rtrim($base, '/') : self::frontendBase();
        if ($baseUrl === '') {
            return null;
        }

        $params = [
            'callId' => $identifier,
            'doctorId' => $doctorId,
            'channel' => $channel,
            'patientId' => $patientId,
        ];

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return sprintf('%s/payment/%s?%s', $baseUrl, rawurlencode($identifier), $query);
    }
}

