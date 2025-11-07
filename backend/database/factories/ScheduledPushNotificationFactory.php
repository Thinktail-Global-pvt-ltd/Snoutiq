<?php

namespace Database\Factories;

use App\Models\ScheduledPushNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledPushNotificationFactory extends Factory
{
    protected $model = ScheduledPushNotification::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->sentence(),
            'frequency' => $this->faker->randomElement(ScheduledPushNotification::FREQUENCIES),
            'data' => ['type' => 'factory'],
            'is_active' => true,
            'next_run_at' => now()->addHour(),
            'last_run_at' => null,
        ];
    }

    public function tenSeconds(): self
    {
        return $this->state(fn () => ['frequency' => ScheduledPushNotification::FREQUENCY_TEN_SECONDS]);
    }

    public function oneMinute(): self
    {
        return $this->state(fn () => ['frequency' => ScheduledPushNotification::FREQUENCY_ONE_MINUTE]);
    }
}

