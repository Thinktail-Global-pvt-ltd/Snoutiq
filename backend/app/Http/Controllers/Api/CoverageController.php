<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Snoutiq\CoverageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoverageController extends Controller
{
    // GET /api/coverage/dashboard
    public function dashboard(CoverageManager $coverage)
    {
        return response()->json($coverage->getDashboardData());
    }

    // GET /api/coverage/zone/{id}
    public function zone(string $id)
    {
        $zone = DB::table('zones')->where('id', $id)->first();
        if (!$zone) {
            return response()->json(['error' => 'Zone not found', 'success' => false], 404);
        }
        $today = date('Y-m-d');
        $hour = (int) date('G');
        $coverage = DB::table('coverage_matrix')
            ->where(['zone_id' => $id, 'coverage_date' => $today, 'hour' => $hour])
            ->get();

        return response()->json([
            'zone' => $zone,
            'coverage' => $coverage,
        ]);
    }
}

