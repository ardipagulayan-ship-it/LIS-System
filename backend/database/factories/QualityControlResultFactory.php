<?php

namespace Database\Factories;

use App\Models\QualityControlResult;
use App\Models\QualityControl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QualityControlResultFactory extends Factory
{
    protected $model = QualityControlResult::class;

    public function definition(): array
    {
        $qc = QualityControl::factory()->create();
        $result = fake()->randomFloat(4, 
            $qc->target_value - (3 * $qc->standard_deviation), 
            $qc->target_value + (3 * $qc->standard_deviation)
        );
        $sdi = ($result - $qc->target_value) / $qc->standard_deviation;
        $user = User::factory()->create();
        $runDateTime = fake()->dateTimeBetween('-1 month', 'now');
        
        return [
            'quality_control_id' => $qc->id,
            'result_value' => $result,
            'standard_deviation_index' => $sdi,
            'unit' => fake()->randomElement(['g/L', 'mmol/L', 'U/L', '%', 'mg/dL', 'mEq/L']),
            'run_date' => $runDateTime->format('Y-m-d'),
            'run_time' => $runDateTime->format('H:i:s'),
            'operator_id' => $user->id,
            'westgard_violations' => json_encode([]),
            'is_valid' => true,
            'is_within_range' => abs($sdi) <= 2,
            'remarks' => fake()->optional()->sentence(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
    }
} 