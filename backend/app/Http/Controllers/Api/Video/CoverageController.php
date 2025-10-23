<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Video;

use App\Http\Controllers\Controller;
use App\Models\GeoStrip;
use App\Models\VideoSlot;
use App\Services\SlotPublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoverageController extends Controller
{
    public function __construct(private readonly SlotPublisherService $publisher)
    {
    }

    // GET /api/video/coverage?date=YYYY-MM-DD&hour=H
    public function matrix(Request $request): JsonResponse
    {
        $date = (string) $request->query('date');
        $hour = $request->query('hour');
        if (!$date || $hour === null) {
            return response()->json(['error' => 'date and hour are required'], 422);
        }
        $hour = (int) $hour;

        $this->publisher->ensureNightSlotsForUtcWindow($date, $hour);

        $strips = GeoStrip::active()->orderBy('min_lon')->get(['id','name']);
        $rows = [];
        foreach ($strips as $s) {
            $primary = VideoSlot::query()->forWindow($s->id, $date, $hour, ['primary'])->first();
            $bench   = VideoSlot::query()->forWindow($s->id, $date, $hour, ['bench'])->first();
            $rows[] = [
                'strip_id' => $s->id,
                'strip_name' => $s->name,
                'primary' => $primary ? $primary->status : null,
                'bench' => $bench ? $bench->status : null,
            ];
        }
        return response()->json(['date' => $date, 'hour' => $hour, 'coverage' => $rows]);
    }
}
