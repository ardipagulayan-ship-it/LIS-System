<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserRole>
 */
class UserRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_type' => $this->faker->randomElement(['pathologist', 'verifier', 'performer']),
            'is_active' => true,
        ];
    }
    
    /**
     * Set the role type.
     */
    public function role(string $roleType): self
    {
        return $this->state(function (array $attributes) use ($roleType) {
            return [
                'role_type' => $roleType,
            ];
        });
    }
}
