<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Snoutiq\MLEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MLController extends Controller
{
    // POST /api/ml/train
    public function train(MLEngine $ml)
    {
        $ml->runDailyLearning();
        return response()->json(['message' => 'ML training completed']);
    }

    // GET /api/ml/provider-performance/{id}
    public function providerPerformance(string $id)
    {
        $performance = DB::table('ml_provider_performance')
            ->where('provider_id', $id)
            ->orderByDesc('calculated_at')
            ->limit(30)
            ->get();
        return response()->json(['performance' => $performance]);
    }

    // GET /api/ml/demand-prediction
    public function demandPrediction(Request $request, MLEngine $ml)
    {
        $zoneId = (int) $request->query('zone_id', 0);
        $serviceType = $request->query('service_type', 'video');
        $date = $request->query('date', date('Y-m-d'));
        $hour = (int) $request->query('hour', (int) date('G'));

        if ($zoneId <= 0) {
            return response()->json(['error' => 'Zone ID required', 'success' => false], 400);
        }

        $prediction = $ml->predictDemand($zoneId, $serviceType, $date, $hour);
        return response()->json(['prediction' => $prediction]);
    }
}

