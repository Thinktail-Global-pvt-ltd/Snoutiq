<?php

namespace App\Services\Snoutiq;

use Illuminate\Support\Facades\DB;

class MLEngine
{
    public function predictDemand(int $zoneId, string $serviceType, string $date, int $hour): array
    {
        $dow = (int) date('w', strtotime($date));
        $month = (int) date('n', strtotime($date));

        $pattern = DB::table('ml_demand_patterns')
            ->where(['zone_id' => $zoneId, 'service_type' => $serviceType, 'day_of_week' => $dow, 'hour' => $hour, 'month' => $month])
            ->orderByDesc('last_trained')
            ->first();

        if (!$pattern) {
            // Default estimate if no pattern
            return [
                'predicted_demand' => 1.0,
                'confidence' => 0.5,
                'base_demand' => 1.0,
                'factors' => [
                    'seasonal' => 1.0,
                    'weather' => 1.0,
                    'holiday' => 1.0,
                    'growth' => 1.0,
                ],
            ];
        }

        return [
            'predicted_demand' => (float) $pattern->avg_bookings,
            'confidence' => (float) ($pattern->model_accuracy ?? 0.5),
            'base_demand' => (float) $pattern->avg_bookings,
            'factors' => [
                'seasonal' => (float) ($pattern->seasonal_factor ?? 1.0),
                'weather' => (float) ($pattern->weather_impact_factor ?? 1.0),
                'holiday' => (float) ($pattern->holiday_impact_factor ?? 1.0),
                'growth' => 1.0,
            ],
        ];
    }

    public function runDailyLearning(): void
    {
        // Placeholder for scheduled ML training.
        // Intentionally no-op in this scaffold.
    }
}

