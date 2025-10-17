<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GurugramStripsQuantileSeeder extends Seeder
{
    public function run(): void
    {
        $nStrips = 15; // keep in sync with VIDEO_NIGHT_STRIPS

        $lons = DB::table('geo_pincodes')
            ->where('city', 'Gurugram')
            ->where('active', 1)
            ->orderBy('lon')
            ->pluck('lon')
            ->values()
            ->all();

        if (count($lons) < 2) return;

        $minLat = 28.340000; // fixed north-south cap used in your table
        $maxLat = 28.560000;

        DB::transaction(function () use ($lons, $nStrips, $minLat, $maxLat) {
            $N = count($lons);

            for ($i = 0; $i < $nStrips; $i++) {
                $startIdx = (int) floor($i     * $N / $nStrips);
                $endIdx   = (int) floor(($i+1) * $N / $nStrips) - 1;
                $endIdx   = max($endIdx, $startIdx);

                $minLon = $lons[$startIdx];
                $maxLon = $lons[$endIdx];

                DB::table('geo_strips')->updateOrInsert(
                    ['id' => $i+1],
                    [
                        'name'              => 'Gurugram-'.str_pad($i+1, 2, '0', STR_PAD_LEFT),
                        'min_lat'           => $minLat,
                        'max_lat'           => $maxLat,
                        'min_lon'           => $minLon,
                        'max_lon'           => $maxLon,
                        'overlap_buffer_km' => 0.50,
                        'active'            => 1,
                        'updated_at'        => now(),
                        'created_at'        => now(),
                    ]
                );
            }
        });
    }
}
