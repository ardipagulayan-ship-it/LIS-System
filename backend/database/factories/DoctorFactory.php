<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Doctor; // Ensure this matches your model location

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Doctor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Only include fields present in the migration
        return [
            'name' => 'Dr. ' . fake()->name(),
            'specialization' => fake()->randomElement(['Cardiology', 'Pediatrics', 'General Medicine', 'Neurology', 'Oncology', 'Radiology', 'Surgery']),
            // 'contact_number' => fake()->phoneNumber(), // Removed
            // 'email' => fake()->unique()->safeEmail(), // Removed
            // 'address' => fake()->address(), // Removed
        ];
    }
}