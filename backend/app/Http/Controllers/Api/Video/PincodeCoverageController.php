<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Video;

use App\Http\Controllers\Controller;
use App\Models\VideoSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PincodeCoverageController extends Controller
{
    private float $minLon = 76.80; // Gurugram bbox used for band mapping
    private float $maxLon = 77.25;

    private function mapLonToStrip(float $lon, int $stripsCount): int
    {
        $stripsCount = max(1, $stripsCount);
        $step   = ($this->maxLon - $this->minLon) / $stripsCount;
        $idx    = (int) floor(($lon - $this->minLon) / max($step, 1e-9));
        $idx    = max(0, min($stripsCount - 1, $idx));
        return $idx + 1; // strip_id is 1..N
    }

    // GET /api/video/pincode-coverage?date=YYYY-MM-DD&hour=H (UTC window)
    public function matrix(Request $request): JsonResponse
    {
        $date = (string) $request->query('date');
        $hour = $request->query('hour');
        if (!$date || $hour === null) {
            return response()->json(['error' => 'date and hour are required'], 422);
        }
        $hour = (int) $hour;

        // Load active Gurugram pincodes
        $pins = DB::table('geo_pincodes')
            ->where('active', 1)
            ->where('city', 'Gurugram')
            ->orderBy('pincode')
            ->get(['pincode as code','label','lat','lon']);

        $stripsCount = (int) env('VIDEO_NIGHT_STRIPS', 15);

        $rows = [];
        foreach ($pins as $p) {
            $stripId = $this->mapLonToStrip((float) $p->lon, $stripsCount);
            $primary = VideoSlot::query()->forWindow($stripId, $date, $hour, ['primary'])->first();
            $bench   = VideoSlot::query()->forWindow($stripId, $date, $hour, ['bench'])->first();
            $rows[] = [
                'pincode' => (string) $p->code,
                'label'   => (string) $p->label,
                'strip_id'=> $stripId,
                'primary' => $primary ? $primary->status : null,
                'bench'   => $bench ? $bench->status : null,
            ];
        }

        return response()->json(['date' => $date, 'hour' => $hour, 'coverage' => $rows]);
    }
}

