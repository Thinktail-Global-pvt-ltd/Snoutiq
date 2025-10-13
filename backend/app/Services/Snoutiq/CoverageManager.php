<?php

namespace App\Services\Snoutiq;

use Illuminate\Support\Facades\DB;

class CoverageManager
{
    public function getDashboardData(string $city = 'Gurgaon'): array
    {
        $currentHour = (int) date('G');
        $today = date('Y-m-d');

        $currentStatus = [
            'vets_online' => (int) DB::table('provider_availability')->where('day_of_week', (int) date('w'))->count(),
            'bookings_queue' => (int) DB::table('bookings')->whereIn('status', ['routing', 'accepted', 'in_progress'])->count(),
            'avg_response_time_mins' => 0.0,
            'status' => 'UNKNOWN',
        ];
        $currentStatus['status'] = $currentStatus['vets_online'] >= 3 ? 'ADEQUATE' : 'WEAK';

        $forecast = [];
        for ($i = 0; $i < 4; $i++) {
            $hour = ($currentHour + $i) % 24;
            $providers = (int) DB::table('coverage_matrix')->whereDate('coverage_date', $today)->where('hour', $hour)->sum('available_providers');
            $forecast[] = [
                'hour' => $hour,
                'providers' => $providers,
                'status' => $providers >= 3 ? 'OK' : ($providers >= 1 ? 'WEAK' : 'CRITICAL'),
                'has_issues' => (bool) DB::table('coverage_matrix')->whereDate('coverage_date', $today)->where('hour', $hour)->whereIn('priority', ['high', 'critical'])->count(),
            ];
        }

        $zones = DB::table('zones')->select('id', 'name')->get();
        $zoneMap = [];
        foreach ($zones as $z) {
            $row = DB::table('coverage_matrix')
                ->selectRaw('AVG(coverage_score) as avg_score, SUM(available_providers) as total_providers')
                ->where(['zone_id' => $z->id, 'coverage_date' => $today, 'hour' => $currentHour])
                ->first();
            $avg = (float) ($row->avg_score ?? 0);
            $zoneMap[] = [
                'id' => $z->id,
                'name' => $z->name,
                'providers' => (int) ($row->total_providers ?? 0),
                'status' => $avg < 50 ? 'critical' : ($avg < 80 ? 'weak' : 'good'),
                'score' => (int) round($avg),
            ];
        }

        $criticalZones = array_values(array_filter($zoneMap, fn ($zone) => $zone['status'] === 'critical'));

        return [
            'current_status' => $currentStatus,
            'forecast' => $forecast,
            'zone_map' => $zoneMap,
            'critical_zones' => $criticalZones,
            'last_updated' => date('g:i A, M j'),
        ];
    }
}

