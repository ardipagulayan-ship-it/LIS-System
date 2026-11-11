<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\TestCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $testCategoryIds = TestCategory::pluck('id')->toArray();
        $statuses = array_keys(Equipment::getStatuses());

        return [
            'unique_asset_id' => 'EQ-' . $this->faker->unique()->numberBetween(1000, 9999),
            'manufacturer' => $this->faker->company(),
            'model' => $this->faker->bothify('Model-###??'),
            'serial_number' => $this->faker->unique()->bothify('SN-#######'),
            'purchase_date' => $this->faker->date(),
            'warranty_expiration_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'location_lab' => 'Main Lab',
            'location_bench' => 'Bench ' . $this->faker->numberBetween(1, 10),
            'current_status' => $this->faker->randomElement($statuses),
            'test_category_id' => !empty($testCategoryIds) ? $this->faker->randomElement($testCategoryIds) : null,
            'sop_operation_manual_link' => $this->faker->url(),
            'cycle_count' => $this->faker->numberBetween(100, 10000),
            'runtime_hours' => $this->faker->randomFloat(2, 100, 5000),
            'last_maintenance_date' => $this->faker->date(),
            'next_maintenance_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'total_repair_cost' => $this->faker->randomFloat(2, 0, 10000),
            'created_by' => 1, // Assuming admin user with ID 1 exists
            'updated_by' => 1,
        ];
    }
}
