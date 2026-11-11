<?php

namespace Database\Factories;

use App\Models\TestResult;
use App\Models\LaboratoryRequestTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestResultFactory extends Factory
{
    protected $model = TestResult::class;

    public function definition(): array
    {
        return [
            'laboratory_request_test_id' => LaboratoryRequestTest::factory(),
            'parameter' => fake()->word(),
            'result' => fake()->randomFloat(2, 1, 1000),
            'unit' => fake()->randomElement(['g/L', 'mmol/L', 'U/L', '%', 'mg/dL', 'mEq/L']),
            'flag' => fake()->randomElement(['H', 'L', 'N', null]),
            'reference_range' => fake()->numberBetween(1, 100) . ' - ' . fake()->numberBetween(101, 200),
            'interpretation' => fake()->optional()->sentence(),
            'remarks' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['Draft', 'Final', 'Amended', 'Cancelled']),
            'is_active' => true,
        ];
    }
} 