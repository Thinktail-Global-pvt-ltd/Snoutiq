<?php

namespace Database\Factories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(Alert::TYPES),
            'category' => $this->faker->randomElement(['System', 'Sales', 'Revenue']),
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->sentence(12),
            'metadata' => [
                'context' => $this->faker->word(),
            ],
            'is_read' => $this->faker->boolean(40),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }
}

