<?php

namespace Database\Factories;

use App\Models\PushRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PushRunFactory extends Factory
{
    protected $model = PushRun::class;

    public function definition(): array
    {
        $started = now();

        return [
            'id' => (string) Str::uuid(),
            'schedule_id' => null,
            'trigger' => 'scheduled',
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->sentence(),
            'targeted_count' => 10,
            'success_count' => 10,
            'failure_count' => 0,
            'started_at' => $started,
            'finished_at' => (clone $started)->addSeconds(1),
            'duration_ms' => 1000,
            'code_path' => 'factory',
            'log_file' => storage_path('logs/push-test.log'),
            'job_id' => null,
            'sample_device_ids' => [],
            'sample_errors' => [],
        ];
    }
}

