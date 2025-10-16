<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DoctorReliability;
use App\Models\VideoSlot;
use Carbon\Carbon;

class VideoRoutingService
{
    public function __construct(
        protected StripLocatorService $locator
    ) {}

    /**
     * Assign a doctor for given lat/lon and IST timestamp.
     * Priority: strip primary -> strip bench -> neighbor strips (left/right); tie by reliability.
     */
    public function assignDoctorFor(float $lat, float $lon, Carbon $tsIST): ?int
    {
        $stripId = $this->locator->findStripIdByLatLon($lat, $lon);
        if (!$stripId) {
            return null;
        }

        // Map IST time to UTC window key
        $utc = $tsIST->copy()->setTimezone('UTC');
        $slotDate = $utc->toDateString();
        $hour = (int) $utc->format('G');

        $candidates = collect();

        // Strip primary/bench
        $candidates = $candidates->merge($this->windowCandidates($stripId, $slotDate, $hour));

        // Neighbor strips up to k hops
        $k = (int) config('video_coverage.max_neighbor_hops', 2);
        $neighbors = $this->locator->neighborStrips($stripId, $k);
        foreach ($neighbors as $nid) {
            $candidates = $candidates->merge($this->windowCandidates($nid, $slotDate, $hour));
        }

        $docId = $this->chooseBestDoctor($candidates);
        return $docId ?: null;
    }

    protected function windowCandidates(int $stripId, string $slotDate, int $hour)
    {
        return VideoSlot::query()
            ->forWindow($stripId, $slotDate, $hour)
            ->whereIn('status', ['committed','in_progress'])
            ->get()
            ->map(function (VideoSlot $s) {
                return [
                    'doctor_id' => (int) $s->committed_doctor_id,
                    'role' => $s->role,
                ];
            });
    }

    protected function chooseBestDoctor($candidates): ?int
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        // Prefer primary, then bench; tie-break by reliability
        $scored = $candidates->map(function ($c) {
            $rel = DoctorReliability::query()->find($c['doctor_id']);
            return [
                'doctor_id' => $c['doctor_id'],
                'role' => $c['role'],
                'reliability' => $rel?->reliability_score ?? 0.8,
            ];
        });

        $primary = $scored->filter(fn($r) => $r['role'] === 'primary');
        if ($primary->isNotEmpty()) {
            return $primary->sortByDesc('reliability')->first()['doctor_id'];
        }
        $bench = $scored->filter(fn($r) => $r['role'] === 'bench');
        if ($bench->isNotEmpty()) {
            return $bench->sortByDesc('reliability')->first()['doctor_id'];
        }
        return null;
    }
}

