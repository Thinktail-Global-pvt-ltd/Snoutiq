<?php

namespace App\Services\Socket;

use App\Events\CallSessionUpdated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SocketStateService
{
    private const DOCTOR_PRESENCE_KEY = 'ws:doctor-presence';
    private const ACTIVE_CALLS_KEY = 'ws:active-calls';
    private const DOCTOR_BUSY_LOCK_PREFIX = 'ws:doctor-busy';
    private const CALL_TTL_SECONDS = 86_400; // 24 hours
    private const RESUMABLE_STATUSES = [
        'active',
        'disconnected',
        'awaiting_resume',
        'payment_completed',
    ];

    private const TERMINAL_STATUSES = [
        'ended',
        'rejected',
        'expired',
        'payment_cancelled',
        'payment_timeout',
    ];

    public function health(): array
    {
        return [
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'firebase' => $this->isFirebaseConfigured() ? 'enabled' : 'disabled',
            'redis' => $this->redisReady() ? 'ready' : 'disabled',
        ];
    }

    public function activeDoctors(): array
    {
        $raw = $this->readHash(self::DOCTOR_PRESENCE_KEY);
        $available = [];
        $details = [];

        foreach ($raw as $doctorId => $value) {
            $entry = $this->parseDoctorEntry($value);
            if (!$entry) {
                continue;
            }

            $isConnected =
                isset($entry['connectionStatus']) &&
                $entry['connectionStatus'] === 'connected';

            $isReconnecting =
                isset($entry['connectionStatus']) &&
                $entry['connectionStatus'] === 'reconnecting';

            if (!($isConnected || $isReconnecting)) {
                continue;
            }

            $isBusy = $this->isDoctorBusy((int) $doctorId);
            if (!$isBusy) {
                $available[] = (int) $doctorId;
            }

            $details[] = [
                'doctorId' => (int) $doctorId,
                'status' => $entry['connectionStatus'] ?? 'disconnected',
                'lastSeen' => $entry['lastSeen'] ?? null,
                'socketId' => $entry['socketId'] ?? null,
                'busy' => $isBusy,
            ];
        }

        return [
            'availableDoctors' => $available,
            'details' => $details,
            'count' => count($available),
            'updatedAt' => now()->toISOString(),
        ];
    }

    public function storeCallSession(array $payload): array
    {
        $callId = $this->normalizeCallId($payload['call_session'] ?? null);
        if (!$callId) {
            $callId = $this->normalizeCallId($payload['callId'] ?? null);
        }
        if (!$callId) {
            throw new \InvalidArgumentException('call_session (callId) is required');
        }

        $session = $this->getCallSessionRaw($callId);
        $session = array_merge($session ?: [], [
            'callId' => $callId,
            'doctorId' => $payload['doctor_id'] ?? $session['doctorId'] ?? null,
            'patientId' => $payload['patient_id'] ?? $session['patientId'] ?? null,
            'channel' => $payload['channel'] ?? $session['channel'] ?? null,
            'status' => $session['status'] ?? 'requested',
            'createdAt' => $session['createdAt'] ?? Carbon::now()->toISOString(),
        ]);

        $this->persistCallSession($session);
        event(new CallSessionUpdated($session, 'stored'));

        return $this->formatSession($session);
    }

    public function findCallSession(?int $doctorId, ?int $patientId): ?array
    {
        if (!$doctorId && !$patientId) {
            return null;
        }

        $rawEntries = $this->readHash(self::ACTIVE_CALLS_KEY);
        $now = Carbon::now();

        foreach ($rawEntries as $callId => $raw) {
            $entry = $this->parseCallEntry($raw);
            if (!$entry) {
                continue;
            }

            $matchesDoctor = $doctorId && ((int) $entry['doctorId'] === $doctorId);
            $matchesPatient = $patientId && ((int) $entry['patientId'] === $patientId);
            if (!($matchesDoctor || $matchesPatient)) {
                continue;
            }

            if (!$this->isSessionResumable($entry, $now)) {
                continue;
            }

            return $this->formatSession($entry);
        }

        return null;
    }

    public function getCallSession(string $callId): ?array
    {
        $entry = $this->getCallSessionRaw($callId);
        if (!$entry) {
            return null;
        }

        return $this->formatSession($entry);
    }

    public function cleanupStaleCalls(): array
    {
        $entries = $this->readHash(self::ACTIVE_CALLS_KEY);
        if (!$entries) {
            return ['scanned' => 0, 'expired' => 0];
        }

        $now = Carbon::now('UTC');
        $threshold = $now->copy()->subMinutes(5);
        $scanned = count($entries);
        $expired = 0;

        foreach ($entries as $raw) {
            $entry = $this->parseCallEntry($raw);
            if (!$entry) {
                continue;
            }

            $status = $entry['status'] ?? null;
            if (!$status || in_array($status, self::TERMINAL_STATUSES, true)) {
                continue;
            }

            if ($status === 'payment_completed' || in_array($status, self::RESUMABLE_STATUSES, true)) {
                continue;
            }

            $createdAt = $this->parseTimestamp($entry['createdAt'] ?? null);
            if (!$createdAt || $createdAt->greaterThan($threshold)) {
                continue;
            }

            $entry['status'] = 'expired';
            $entry['endedAt'] = Carbon::now('UTC')->toIso8601String();
            $entry['resumableUntil'] = null;

            $this->persistCallSession($entry);
            event(new CallSessionUpdated($entry, 'expired'));
            $expired++;
        }

        Log::info('socket.cleanup', [
            'scanned' => $scanned,
            'expired' => $expired,
        ]);

        return ['scanned' => $scanned, 'expired' => $expired];
    }

    protected function persistCallSession(array $session): void
    {
        $payload = [
            'callId' => $session['callId'],
            'doctorId' => $session['doctorId'],
            'patientId' => $session['patientId'],
            'channel' => $session['channel'] ?? null,
            'status' => $session['status'] ?? null,
            'createdAt' => $this->toIso($session['createdAt'] ?? null),
            'acceptedAt' => $this->toIso($session['acceptedAt'] ?? null),
            'resumableUntil' => $this->toIso($session['resumableUntil'] ?? null),
            'paidAt' => $this->toIso($session['paidAt'] ?? null),
            'endedAt' => $this->toIso($session['endedAt'] ?? null),
        ];

        Redis::hset(self::ACTIVE_CALLS_KEY, $session['callId'], json_encode($payload));
        Redis::expire(self::ACTIVE_CALLS_KEY, self::CALL_TTL_SECONDS);
    }

    protected function getCallSessionRaw(string $callId): ?array
    {
        $raw = Redis::hget(self::ACTIVE_CALLS_KEY, $callId);
        return $this->parseCallEntry($raw);
    }

    protected function readHash(string $key): array
    {
        try {
            return Redis::hgetall($key) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    protected function parseCallEntry($raw): ?array
    {
        if (!$raw) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        return array_merge(['callId' => $decoded['callId'] ?? null], $decoded);
    }

    protected function parseDoctorEntry($raw): ?array
    {
        if (!$raw) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    protected function formatSession(array $session): array
    {
        $resumableUntil = $this->parseTimestamp($session['resumableUntil'] ?? null);
        $paidAt = $this->parseTimestamp($session['paidAt'] ?? null);

        return [
            'callId' => $session['callId'],
            'doctorId' => $session['doctorId'] ? (int) $session['doctorId'] : null,
            'patientId' => $session['patientId'] ? (int) $session['patientId'] : null,
            'channel' => $session['channel'] ?? null,
            'status' => $session['status'] ?? null,
            'paidAt' => $paidAt?->toIso8601String(),
            'resumableUntil' => $resumableUntil?->toIso8601String(),
            'rejoinAllowed' => $this->callIsResumable($resumableUntil),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function parseTimestamp($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value);
    }

    protected function toIso($value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        return Carbon::parse($value)->toIso8601String();
    }

    protected function callIsResumable(?Carbon $resumableUntil): bool
    {
        if (!$resumableUntil) {
            return true;
        }

        return $resumableUntil->greaterThanOrEqualTo(now());
    }

    protected function isSessionResumable(array $session, Carbon $now): bool
    {
        $status = $session['status'] ?? null;
        if (!$status || !in_array($status, self::RESUMABLE_STATUSES, true)) {
            return false;
        }

        $resumableUntil = $this->parseTimestamp($session['resumableUntil'] ?? null);
        return !$resumableUntil || $resumableUntil->greaterThanOrEqualTo($now);
    }

    protected function normalizeCallId(?string $rawId): ?string
    {
        if (!$rawId) {
            return null;
        }

        $trimmed = trim($rawId);
        if ($trimmed === '') {
            return null;
        }

        $collapsed = preg_replace('/_{2,}/', '_', $trimmed);
        $withoutPrefix = preg_replace('/^call_*/i', '', $collapsed);
        $parts = array_filter(explode('_', $withoutPrefix));
        $body = implode('_', $parts);
        if ($body === '') {
            return 'call';
        }

        return 'call_' . Str::slug($body, '_');
    }

    protected function isDoctorBusy(int $doctorId): bool
    {
        $key = self::DOCTOR_BUSY_LOCK_PREFIX . ':' . $doctorId;
        $value = Redis::get($key);
        return !empty($value);
    }

    protected function redisReady(): bool
    {
        try {
            return Redis::connection()->ping() === 'PONG';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isFirebaseConfigured(): bool
    {
        $path = config('firebase.projects.app.credentials.file');
        return is_string($path) && $path !== '' && file_exists($path);
    }
}
