<?php

namespace Database\Factories;

use App\Models\QualityControl;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

class QualityControlFactory extends Factory
{
    protected $model = QualityControl::class;

    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'lot_number' => fake()->unique()->bothify('QC-####??'),
            'level' => fake()->randomElement(['Low', 'Normal', 'High']),
            'target_value' => fake()->randomFloat(4, 1, 100),
            'standard_deviation' => fake()->randomFloat(4, 0.1, 5.0),
            'expiry_date' => fake()->dateTimeBetween('now', '+2 years'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
} 