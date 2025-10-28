<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoctorAvailabilityService
{
    protected ?array $socketSnapshot = null;
    protected bool $syncPerformed = false;

    public function getActiveClinicIds(): Collection
    {
        $snapshot = $this->fetchSocketSnapshot();

        return $snapshot['clinicIds'];
    }

    public function getActiveDoctorSummaries(): Collection
    {
        $snapshot = $this->fetchSocketSnapshot();
        $doctorIds = $snapshot['doctorIds'];

        if ($doctorIds->isEmpty()) {
            return collect();
        }

        $vetsById = VetRegisterationTemp::query()
            ->whereIn('id', $doctorIds->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        return $doctorIds
            ->map(static function (int $doctorId) use ($vetsById) {
                $vet = $vetsById->get($doctorId);

                return [
                    'id' => $doctorId,
                    'name' => $vet?->name,
                ];
            })
            ->values();
    }

    protected function fetchSocketSnapshot(): array
    {
        if ($this->socketSnapshot !== null) {
            return $this->socketSnapshot;
        }

        $emptySnapshot = [
            'clinicIds' => collect(),
            'doctorIds' => collect(),
        ];

        $socketUrl = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
        if (!$socketUrl) {
            return $this->socketSnapshot = $emptySnapshot;
        }

        $endpoint = rtrim($socketUrl, '/') . '/active-doctors';

        try {
            $response = Http::timeout(2)->retry(2, 150)->get($endpoint);
            if (!$response->successful()) {
                Log::warning('DoctorAvailabilityService: non-success response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->socketSnapshot = $emptySnapshot;
            }

            $payload = $response->json();
            $rawClinicIds = $payload['activeClinics'] ?? [];
            $clinicDetails = $payload['clinics'] ?? [];
            $rawDoctorIds = $payload['activeDoctors'] ?? [];

            if (!is_array($rawClinicIds) && !is_array($rawDoctorIds)) {
                Log::warning('DoctorAvailabilityService: unexpected payload format', ['payload' => $payload]);

                return $this->socketSnapshot = $emptySnapshot;
            }

            $activeClinicIds = collect($rawClinicIds)
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn ($id) => $id > 0)
                ->unique()
                ->values();

            $doctorIdsForSync = collect($clinicDetails)
                ->flatMap(static fn ($clinic) => (array) ($clinic['doctorIds'] ?? []))
                ->merge(is_array($rawDoctorIds) ? $rawDoctorIds : [])
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($activeClinicIds->isEmpty() && $doctorIdsForSync->isNotEmpty()) {
                $activeClinicIds = Doctor::query()
                    ->whereIn('id', $doctorIdsForSync->all())
                    ->pluck('vet_registeration_id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter(static fn ($id) => $id > 0)
                    ->unique()
                    ->values();
            }

            if (!$this->syncPerformed && $doctorIdsForSync->isNotEmpty()) {
                $this->syncDoctorToggleFlags($doctorIdsForSync);
                $this->syncPerformed = true;
            }

            return $this->socketSnapshot = [
                'clinicIds' => $activeClinicIds,
                'doctorIds' => $doctorIdsForSync,
            ];
        } catch (\Throwable $e) {
            Log::warning('DoctorAvailabilityService: failed to reach socket server', [
                'message' => $e->getMessage(),
            ]);

            return $this->socketSnapshot = $emptySnapshot;
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
