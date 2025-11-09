<?php

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicFactory extends Factory
{
    protected $model = Clinic::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company.' Veterinary Clinic',
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'country' => $this->faker->country,
        ];
    }
}

