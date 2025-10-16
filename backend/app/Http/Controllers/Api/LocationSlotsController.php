<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\VideoSlot;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class LocationSlotsController extends Controller
{
    // Gurugram bbox for strip banding (adjust if your data differs)
    private float $minLon = 76.80;
    private float $maxLon = 77.25;

    private function getSessionUserCoords(Request $r): array
    {
        $userId = (int) ($r->session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            abort(401, 'No session user_id');
        }

        // table: vet_registerations_temp (uses columns: lat, lng)
        $row = DB::table('vet_registerations_temp')
            ->where('id', $userId) // change column if your PK is different
            ->select(['lat', 'lng'])
            ->first();

        if (!$row || $row->lat === null || $row->lng === null) {
            abort(404, 'User location not found');
        }
        return [(float)$row->lat, (float)$row->lng];
    }

    private function nearestPincodeRow(float $lat, float $lon): ?object
    {
        // Haversine (km)
        $sql = "
            SELECT pincode AS code, label AS name, lat, lon,
            (6371 * ACOS(
               LEAST(1, COS(RADIANS(?)) * COS(RADIANS(lat)) * COS(RADIANS(lon - ?))
                       + SIN(RADIANS(?)) * SIN(RADIANS(lat)))
            )) AS km
            FROM geo_pincodes
            WHERE active = 1 AND city = ?
            ORDER BY km ASC
            LIMIT 1
        ";

        return DB::selectOne($sql, [$lat, $lon, $lat, 'Gurugram']);
    }

    private function mapLonToStrip(float $lon, int $stripsCount): int
    {
        $stripsCount = max(1, $stripsCount);
        $step   = ($this->maxLon - $this->minLon) / $stripsCount;
        $idx    = (int) floor(($lon - $this->minLon) / max($step, 1e-9));
        $idx    = max(0, min($stripsCount - 1, $idx));
        return $idx + 1; // strip_id is 1..N
    }

        private function detectStripsCount(string $date): int
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('geo_strips')) {
                $c = (int) DB::table('geo_strips')->where('active', 1)->count();
                if ($c > 0) {
                    return $c;
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }
        return (int) env('VIDEO_NIGHT_STRIPS', 15);
    }

    // Prefer real geo_strips id by longitudinal proximity; fall back to band mapping if needed
    private function nearestStripIdByLon(float $lon, ?string $dateIst = null): ?int
    {
        try {
            if (Schema::hasTable('geo_strips')) {
                $row = DB::table('geo_strips')
                    ->where('active', 1)
                    ->select('id', DB::raw('((min_lon + max_lon) / 2) as center'))
                    ->orderByRaw('ABS(((min_lon + max_lon) / 2) - ?) ASC', [$lon])
                    ->first();
                if ($row && isset($row->id)) {
                    return (int) $row->id;
                }
            }
        } catch (\Throwable $e) {
            // ignore and try fallback
        }

        $count = $this->detectStripsCount($dateIst ?: date('Y-m-d'));
        return $this->mapLonToStrip($lon, $count);
    }

    // GET /api/geo/nearest-pincode (uses session user_id -> vet_registerations_temp.lat/lng)
    public function nearestPincode(Request $r): JsonResponse
    {
        [$lat, $lon] = $this->getSessionUserCoords($r);
        $pin = $this->nearestPincodeRow($lat, $lon);
        if (!$pin) {
            return response()->json(['error' => 'nearest pincode not found'], 404);
        }
        return response()->json([
            'coords'  => ['lat' => $lat, 'lon' => $lon],
            'pincode' => $pin,
        ]);
    }

    // GET /api/video/slots/nearby?date=YYYY-MM-DD (IST date)
    public function openSlotsNear(Request $r): JsonResponse
    {
        $dateIst = (string) $r->query('date');
        if ($dateIst === '') {
            return response()->json(['error' => 'date is required (YYYY-MM-DD, IST)'], 422);
        }

        // Locate user and map to nearest geo_strips row (by longitude center)
        [, $lon] = $this->getSessionUserCoords($r);
        $stripId = $this->nearestStripIdByLon((float) $lon, $dateIst);

        // Night window 19:00..07:00 IST spans two UTC dates. Build list of (utcDate, utcHour) pairs.
        $hoursIst = [19,20,21,22,23,0,1,2,3,4,5,6];
        $pairs = [];
        foreach ($hoursIst as $hIst) {
            $ist = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dateIst . ' ' . str_pad((string) $hIst, 2, '0', STR_PAD_LEFT) . ':00:00', 'Asia/Kolkata');
            if (in_array($hIst, [0,1,2,3,4,5,6], true)) {
                // 00-06 are technically next day for the same IST night
                $ist = $ist->addDay();
            }
            $utc   = $ist->setTimezone('UTC');
            $pairs[] = [
                'date' => $utc->toDateString(),
                'hour' => (int) $utc->format('G'),
            ];
        }

        $q = VideoSlot::query()
            ->where('strip_id', $stripId)
            ->whereIn('status', ['open','held'])
            ->whereIn('role', ['primary','bench'])
            ->where(function ($w) use ($pairs) {
                foreach ($pairs as $p) {
                    $w->orWhere(function ($q) use ($p) {
                        $q->where('slot_date', $p['date'])
                          ->where('hour_24', $p['hour']);
                    });
                }
            })
            ->orderBy('slot_date')
            ->orderBy('hour_24')
            ->orderBy('role');

        $stripRow = null;
        try { $stripRow = DB::table('geo_strips')->where('id', $stripId)->first(['id','name','min_lon','max_lon']); } catch (\Throwable $e) {}

        return response()->json([
            'date'      => $dateIst,
            'strip_id'  => $stripId,
            'strip'     => $stripRow,
            'slots'     => $q->get(),
        ]);
    }

    // GET /api/video/slots/nearby/pincode?date=YYYY-MM-DD[&code=122009]
    // Uses Gurugram pincodes and a fixed band mapping (env VIDEO_NIGHT_STRIPS) to avoid geo_strips table.
    public function openSlotsByPincode(Request $r): JsonResponse
    {
        $dateIst = (string) $r->query('date');
        if ($dateIst === '') {
            return response()->json(['error' => 'date is required (YYYY-MM-DD, IST)'], 422);
        }

        $code = (string) $r->query('code', '');
        $pinRow = null;
        if ($code !== '') {
            $pinRow = DB::table('geo_pincodes')->where('pincode', $code)->first(['pincode as code','label as name','lat','lon','city']);
        } else {
            // Fallback to nearest pincode from session user location
            try {
                [$lat, $lon] = $this->getSessionUserCoords($r);
                $pinRow = $this->nearestPincodeRow($lat, $lon);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'unable to resolve pincode: '.$e->getMessage()], 404);
            }
        }
        if (!$pinRow) {
            return response()->json(['error' => 'pincode not found'], 404);
        }

        // Avoid geo_strips entirely: compute band by longitude with configured strips count
        $stripsCount = (int) env('VIDEO_NIGHT_STRIPS', 15);
        $stripId = $this->mapLonToStrip((float) $pinRow->lon, $stripsCount);

        // Build IST night hours window
        $hoursIst = [19,20,21,22,23,0,1,2,3,4,5,6];
        $pairs = [];
        foreach ($hoursIst as $hIst) {
            $ist = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dateIst . ' ' . str_pad((string) $hIst, 2, '0', STR_PAD_LEFT) . ':00:00', 'Asia/Kolkata');
            if (in_array($hIst, [0,1,2,3,4,5,6], true)) {
                $ist = $ist->addDay();
            }
            $utc   = $ist->setTimezone('UTC');
            $pairs[] = [
                'date' => $utc->toDateString(),
                'hour' => (int) $utc->format('G'),
            ];
        }

        $q = VideoSlot::query()
            ->where('strip_id', $stripId)
            ->whereIn('status', ['open','held'])
            ->whereIn('role', ['primary','bench'])
            ->where(function ($w) use ($pairs) {
                foreach ($pairs as $p) {
                    $w->orWhere(function ($q) use ($p) {
                        $q->where('slot_date', $p['date'])
                          ->where('hour_24', $p['hour']);
                    });
                }
            })
            ->orderBy('slot_date')
            ->orderBy('hour_24')
            ->orderBy('role');

        // No DB dependency for strip meta; provide a synthetic label
        $stripMeta = (object) [
            'id'   => $stripId,
            'name' => 'Band-'.$stripId,
        ];

        return response()->json([
            'date'      => $dateIst,
            'using'     => 'pincode_band',
            'strip_id'  => (int) $stripId,
            'strip'     => $stripMeta,
            'pincode'   => $pinRow,
            'slots'     => $q->get(),
        ]);
    }

    // GET /api/debug/user-location (best-effort helper used by UI)
    public function dumpUserLocation(Request $r): JsonResponse
    {
        try {
            [$lat, $lon] = $this->getSessionUserCoords($r);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
        $nearest = $this->nearestPincodeRow($lat, $lon);
        return response()->json([
            'lat' => $lat,
            'lon' => $lon,
            'nearest_pincode' => $nearest,
        ]);
    }

    // GET /api/video/strip-for-pincode?code=122009
    public function stripForPincode(Request $r): JsonResponse
    {
        $code = (string) $r->query('code', '');
        if ($code === '') {
            return response()->json(['error' => 'code is required'], 422);
        }
        $row = DB::table('geo_pincodes')->where('pincode', $code)->first(['pincode as code','label as name','lat','lon','city']);
        if (!$row) {
            return response()->json(['error' => 'pincode not found'], 404);
        }
        $stripId = $this->nearestStripIdByLon((float) $row->lon, date('Y-m-d'));
        return response()->json([
            'code' => $row->code,
            'strip_id' => $stripId,
            'pincode' => $row,
        ]);
    }

    // GET /api/video/nearest-strip (uses session user_id location)
    public function nearestStrip(Request $r): JsonResponse
    {
        [, $lon] = $this->getSessionUserCoords($r);
        $stripId = $this->nearestStripIdByLon((float) $lon, date('Y-m-d'));
        $row = DB::table('geo_strips')->where('id', $stripId)->first(['id','name','min_lon','max_lon']);
        if (!$row) {
            return response()->json(['error' => 'no active strips found'], 404);
        }
        return response()->json(['strip' => $row]);
    }
}
