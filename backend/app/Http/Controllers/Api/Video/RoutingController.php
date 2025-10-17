<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Video;

use App\Http\Controllers\Controller;
use App\Http\Requests\Video\AssignRouteRequest;
use App\Services\VideoRoutingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RoutingController extends Controller
{
    public function __construct(protected VideoRoutingService $routing) {}

    // POST /api/video/route
    public function assign(AssignRouteRequest $request): JsonResponse
    {
        $lat = (float) $request->input('lat');
        $lon = (float) $request->input('lon');
        $ts = $request->input('ts');
        $tsIst = $ts ? Carbon::parse($ts)->setTimezone('Asia/Kolkata') : Carbon::now('Asia/Kolkata');

        $doctorId = $this->routing->assignDoctorFor($lat, $lon, $tsIst);
        return response()->json(['doctor_id' => $doctorId]);
    }
}

