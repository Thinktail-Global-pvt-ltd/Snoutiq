<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoctorPresenceService
{
    public function __construct(private readonly ?string $socketServerBaseUrl = null)
    {
    }

    public function isDoctorAvailable(int $doctorId): bool
    {
        $baseUrl = $this->socketServerBaseUrl ?? config('services.socket_server.base_url');
        if (!$baseUrl) {
            return false;
        }

        try {
            $response = Http::timeout(3)->get(rtrim($baseUrl, '/') . '/active-doctors');
            if (!$response->successful()) {
                Log::warning('doctor-presence: unexpected response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $payload = $response->json();
            $active = $payload['activeDoctors'] ?? [];

            return in_array($doctorId, $active, false);
        } catch (\Throwable $exception) {
            Log::debug('doctor-presence: failed to query socket server', [
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }
}
