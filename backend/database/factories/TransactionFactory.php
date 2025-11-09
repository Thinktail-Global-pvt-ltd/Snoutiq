<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'amount_paise' => $this->faker->numberBetween(50_000, 5_000_000),
            'status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
            'type' => $this->faker->randomElement(['Subscription', 'Payment', 'Renewal']),
            'payment_method' => $this->faker->randomElement(['UPI', 'Credit Card', 'Bank Transfer']),
            'reference' => Str::upper($this->faker->bothify('TXN###??')),
            'metadata' => [
                'note' => $this->faker->sentence(),
            ],
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'updated_at' => now(),
        ];
    }
}

