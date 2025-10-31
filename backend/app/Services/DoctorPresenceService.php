<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoctorPresenceService
{
    private ?array $cachedDoctorLists = null;

    public function __construct(private readonly ?string $socketServerBaseUrl = null)
    {
    }

    public function isDoctorAvailable(int $doctorId): bool
    {
        $lists = $this->fetchDoctorLists();
        $visible = $lists['visible'] ?? [];
        $active = $lists['active'] ?? [];

        $doctorPool = !empty($visible) ? $visible : $active;

        return in_array($doctorId, $doctorPool, false);
    }

    public function isDoctorHidden(int $doctorId): bool
    {
        $lists = $this->fetchDoctorLists();
        $hidden = $lists['hidden'] ?? [];

        return in_array($doctorId, $hidden, false);
    }

    private function fetchDoctorLists(): array
    {
        if ($this->cachedDoctorLists !== null) {
            return $this->cachedDoctorLists;
        }

        $baseUrl = $this->socketServerBaseUrl ?? config('services.socket_server.base_url');
        if (!$baseUrl) {
            return $this->cachedDoctorLists = [
                'visible' => [],
                'active' => [],
                'hidden' => [],
            ];
        }

        try {
            $response = Http::timeout(3)->get(rtrim($baseUrl, '/') . '/active-doctors');
            if (!$response->successful()) {
                Log::warning('doctor-presence: unexpected response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->cachedDoctorLists = [
                    'visible' => [],
                    'active' => [],
                    'hidden' => [],
                ];
            }

            $payload = $response->json();
            $visible = $payload['visibleDoctors'] ?? null;
            $active = $payload['activeDoctors'] ?? [];
            $hidden = $payload['hiddenDoctors'] ?? [];

            $lists = [
                'visible' => is_array($visible) ? $visible : [],
                'active' => is_array($active) ? $active : [],
                'hidden' => is_array($hidden) ? $hidden : [],
            ];

            return $this->cachedDoctorLists = $lists;
        } catch (\Throwable $exception) {
            Log::debug('doctor-presence: failed to query socket server', [
                'message' => $exception->getMessage(),
            ]);
            return $this->cachedDoctorLists = [
                'visible' => [],
                'active' => [],
                'hidden' => [],
            ];
        }
    }
}
