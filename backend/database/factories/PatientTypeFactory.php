<?php

namespace Database\Factories;

use App\Models\PatientType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientTypeFactory extends Factory
{
    protected $model = PatientType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Out Patient',
                'In Patient',
                'Emergency',
                'Senior Citizen',
                'PWD',
                'Newborn',
                'Pediatric',
                'Adult',
                'Geriatric'
            ]),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
} 