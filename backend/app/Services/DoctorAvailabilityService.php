<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoctorAvailabilityService
{
    public function getActiveDoctorIds(): Collection
    {
        $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
        if (!$socketUrl) {
            return collect();
        }

        $endpoint = rtrim($socketUrl, '/') . '/active-doctors';

        try {
            $response = Http::timeout(2)->retry(2, 150)->get($endpoint);
            if (!$response->successful()) {
                Log::warning('DoctorAvailabilityService: non-success response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return collect();
            }

            $payload = $response->json();
            $rawIds = $payload['activeDoctors'] ?? $payload['availableDoctorIds'] ?? $payload ?? [];

            if (!is_array($rawIds)) {
                Log::warning('DoctorAvailabilityService: unexpected payload format', ['payload' => $payload]);

                return collect();
            }

            $activeDoctorIds = collect($rawIds)
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn ($id) => $id > 0)
                ->unique()
                ->values();

            $this->syncDoctorToggleFlags($activeDoctorIds);

            return $activeDoctorIds;
        } catch (\Throwable $e) {
            Log::warning('DoctorAvailabilityService: failed to reach socket server', [
                'message' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    protected function syncDoctorToggleFlags(Collection $activeDoctorIds): void
    {
        try {
            if ($activeDoctorIds->isEmpty()) {
                Doctor::query()
                    ->where('toggle_availability', 1)
                    ->update(['toggle_availability' => 0]);

                return;
            }

            Doctor::query()
                ->whereIn('id', $activeDoctorIds)
                ->where('toggle_availability', '!=', 1)
                ->update(['toggle_availability' => 1]);

            Doctor::query()
                ->whereNotIn('id', $activeDoctorIds)
                ->where('toggle_availability', 1)
                ->update(['toggle_availability' => 0]);
        } catch (\Throwable $e) {
            Log::warning('DoctorAvailabilityService: failed to sync toggle flags', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
