<?php

namespace Database\Factories;

use App\Models\FounderSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FounderSettingFactory extends Factory
{
    protected $model = FounderSetting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'data' => [
                'notifications' => [
                    'enabled' => true,
                ],
                'theme' => 'light',
            ],
        ];
    }
}

