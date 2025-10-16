<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeoStrip;

class IncentiveService
{
    public function payoutFor(int $hour, GeoStrip $strip, float $reliability = 0.8, float $demand = 1.0): int
    {
        $base = (int) config('video_coverage.base_payout_paise', 10000);

        $bandCoeff = 1.0;
        $bands = (array) config('video_coverage.night_bands', []);
        // Normalize keys and compute coefficient by IST hour
        $hourMap = [19=>1,20=>1,21=>1,22=>1,23=>1,0=>1,1=>1,2=>1,3=>1,4=>1,5=>1,6=>1];
        foreach ($bands as $key => $coeff) {
            if (preg_match('/^(\d{2})-(\d{2})$/', $key, $m)) {
                $start = (int)$m[1];
                $end = (int)$m[2];
                $range = [];
                $h = $start;
                do {
                    $range[] = $h;
                    $h = ($h + 1) % 24;
                } while ($h !== $end);
                $range[] = $end;
                if (in_array($hour, $range, true)) { $bandCoeff = (float) $coeff; }
            } elseif (preg_match('/^(\d{2})$/', $key) && (int)$key === $hour) {
                $bandCoeff = (float) $coeff;
            }
        }

        // Scarcity multiplier: simple heuristic (placeholder)
        // Will be adjusted by SlotPublisher/cron for near-start windows
        $scarcity = 1.0; // dynamic updates elsewhere

        $demandSurge = max(1.0, min(1.5, (float)$demand));
        $reliabilityBonus = max(0.95, min(1.10, (float)$reliability + 0.2)); // map 0.8 -> ~1.0

        $payout = (int) round($base * $bandCoeff * $scarcity * $demandSurge * $reliabilityBonus);
        return max(0, $payout);
    }
}

