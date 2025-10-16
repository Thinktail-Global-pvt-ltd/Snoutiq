<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeoStrip;

class StripLocatorService
{
    public function findStripIdByLatLon(float $lat, float $lon): ?int
    {
        $strip = GeoStrip::active()
            ->where('min_lat', '<=', $lat)
            ->where('max_lat', '>=', $lat)
            ->where('min_lon', '<=', $lon)
            ->where('max_lon', '>=', $lon)
            ->orderBy('min_lon')
            ->first();

        return $strip?->id;
    }

    /**
     * Return neighbor strip IDs by longitude order: left/right up to k hops each side.
     */
    public function neighborStrips(int $stripId, int $k = 2): array
    {
        $all = GeoStrip::active()->orderBy('min_lon')->get(['id', 'min_lon'])->pluck('id')->all();
        $idx = array_search($stripId, $all, true);
        if ($idx === false) {
            return [];
        }
        $out = [];
        for ($i = 1; $i <= $k; $i++) {
            if (isset($all[$idx - $i])) { $out[] = $all[$idx - $i]; }
            if (isset($all[$idx + $i])) { $out[] = $all[$idx + $i]; }
        }
        return $out;
    }
}

