<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TestCategory; // Ensure this matches your model location

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TestCategory>
 */
class TestCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TestCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Define how to generate fake data for a TestCategory
        // Adjust fields based on your TestCategory migration/model
        return [
            'name' => fake()->unique()->randomElement([
                'Hematology',
                'Clinical Chemistry',
                'Clinical Microscopy',
                'Microbiology',
                'Serology/Immunology',
                'Histopathology',
                'Molecular Diagnostics'
            ]),
            'description' => fake()->sentence(),
        ];
    }
}