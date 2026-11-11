<?php

namespace Database\Factories;

use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Medical Ward',
                'Surgical Ward',
                'Pediatric Ward',
                'Obstetrics Ward',
                'Gynecology Ward',
                'Emergency Ward',
                'ICU',
                'CCU',
                'NICU',
                'PICU'
            ]),
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(10, 50),
            'is_active' => true,
        ];
    }
} 