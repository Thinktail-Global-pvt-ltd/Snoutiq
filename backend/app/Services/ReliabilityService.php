<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DoctorReliability;

class ReliabilityService
{
    public function onFulfilled(int $doctorId): void
    {
        $row = DoctorReliability::query()->firstOrNew(['doctor_id' => $doctorId]);
        $row->reliability_score = min(1.10, max(0.00, (float) ($row->reliability_score ?? 0.80) + 0.01));
        $row->on_time_rate = min(1.0, max(0.0, (float) ($row->on_time_rate ?? 0.95) + 0.002));
        $row->updated_at = now('UTC');
        $row->save();
    }

    public function onNoShow(int $doctorId): void
    {
        $row = DoctorReliability::query()->firstOrNew(['doctor_id' => $doctorId]);
        $row->no_show_count = (int) ($row->no_show_count ?? 0) + 1;
        $row->reliability_score = max(0.50, (float) ($row->reliability_score ?? 0.80) - 0.05);
        $row->on_time_rate = max(0.0, (float) ($row->on_time_rate ?? 0.95) - 0.01);
        $row->updated_at = now('UTC');
        $row->save();
    }
}

