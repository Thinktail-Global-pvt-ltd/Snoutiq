<?php

namespace Database\Factories;

use App\Models\DeviceToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceTokenFactory extends Factory
{
    protected $model = DeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'token' => $this->faker->unique()->sha256,
            'platform' => $this->faker->randomElement(['ios', 'android', 'web']),
            'device_id' => $this->faker->uuid(),
            'meta' => null,
            'last_seen_at' => now(),
        ];
    }
}

