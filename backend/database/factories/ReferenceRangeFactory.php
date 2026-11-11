<?php

namespace Database\Factories;

use App\Models\ReferenceRange;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferenceRangeFactory extends Factory
{
    protected $model = ReferenceRange::class;

    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'gender' => fake()->randomElement(['Male', 'Female', null]),
            'min_age' => fake()->randomFloat(2, 0, 100),
            'max_age' => function (array $attributes) {
                return fake()->randomFloat(2, $attributes['min_age'], 120);
            },
            'condition' => fake()->optional()->word(),
            'lower_limit' => fake()->randomFloat(4, 0, 100),
            'upper_limit' => function (array $attributes) {
                return fake()->randomFloat(4, $attributes['lower_limit'], 200);
            },
            'unit' => fake()->randomElement(['g/L', 'mmol/L', 'U/L', '%', 'mg/dL', 'mEq/L']),
            'description' => fake()->sentence(),
            'interpretation' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
} 