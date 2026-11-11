<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'ward_id' => Ward::factory(),
            'name' => fake()->unique()->numerify('Room ###'),
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(1, 6),
            'type' => fake()->randomElement(['private', 'semi-private', 'ward']),
            'is_active' => true,
        ];
    }
} 