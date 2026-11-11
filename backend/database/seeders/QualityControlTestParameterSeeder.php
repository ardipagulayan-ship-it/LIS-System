<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\QualityControl;
use App\Models\TestParameter;

class QualityControlTestParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing Quality Controls and Test Parameters
        $qualityControls = QualityControl::all();
        $testParameters = TestParameter::all();

        if ($qualityControls->isEmpty() || $testParameters->isEmpty()) {
            $this->command->warn('Skipping quality_control_test_parameter seeding: No quality controls or test parameters found.');
            return;
        }

        // Do not truncate to avoid breaking FK references; we'll upsert/ignore instead

        // Seed the pivot table
        foreach ($qualityControls as $qc) {
            // Attach a few random test parameters to each quality control
            $qcTestParameters = $testParameters->random(rand(1, min(5, $testParameters->count())));

            foreach ($qcTestParameters as $param) {
                // Generate sample data for pivot fields (target, tolerance, SDs)
                // These values should ideally be related to the test parameter's properties
                // For simplicity, generating random plausible numbers for now.
                $target = rand(10, 1000) / 10.0;
                $tolerance = rand(1, 10) / 10.0;
                $sd = $tolerance / 2.0;

                DB::table('quality_control_test_parameter')->insertOrIgnore([
                    'quality_control_id' => $qc->id,
                    'test_parameter_id' => $param->id,
                    'target' => $target,
                    'tolerance' => $tolerance,
                    'minus_3sd' => $target - (3 * $sd),
                    'minus_2sd' => $target - (2 * $sd),
                    'minus_1sd' => $target - (1 * $sd),
                    'plus_1sd' => $target + (1 * $sd),
                    'plus_2sd' => $target + (2 * $sd),
                    'plus_3sd' => $target + (3 * $sd),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('quality_control_test_parameter table seeded.');
    }
}
