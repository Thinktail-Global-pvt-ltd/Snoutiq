<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GeoStrip;
use Illuminate\Database\Seeder;

class GurugramStripsSeeder extends Seeder
{
    public function run(): void
    {
        // Gurugram bbox: lat [28.34, 28.56], lon [76.85, 77.15]
        $minLat = 28.34; $maxLat = 28.56;
        $minLon = 76.85; $maxLon = 77.15;

        // degPerKmLon ≈ 1/(111*cos(deg2rad(28.46))) ≈ ~0.0102 deg/km
        $degPerKmLon = 1.0 / (111.0 * cos(deg2rad(28.46)));
        $stepDeg = 2.0 * $degPerKmLon; // 2 km width ~0.0204°
        $overlapDeg = 0.5 * $degPerKmLon; // 0.5 km overlap

        $idx = 1;
        for ($lon = $minLon; $lon < $maxLon; $lon += $stepDeg) {
            $stripMinLon = max($minLon, $lon - $overlapDeg);
            $stripMaxLon = min($maxLon, $lon + $stepDeg + $overlapDeg);
            $name = sprintf('Gurugram-%02d', $idx++);
            GeoStrip::query()->updateOrCreate(
                ['name' => $name],
                [
                    'min_lat' => $minLat,
                    'max_lat' => $maxLat,
                    'min_lon' => $stripMinLon,
                    'max_lon' => $stripMaxLon,
                    'overlap_buffer_km' => 0.50,
                    'active' => true,
                ]
            );
        }
    }
}

