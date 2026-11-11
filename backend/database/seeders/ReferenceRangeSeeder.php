<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReferenceRange;
use App\Models\TestParameter;
use Illuminate\Support\Facades\DB;

class ReferenceRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if test parameters exist
        if (TestParameter::count() == 0) {
            $this->command->info('TestParameters table is empty. Cannot seed ReferenceRanges.');
            return;
        }

        // Example: Find specific parameters (e.g., WBC, RBC from CBC)
        // Adjust codes based on what TestParameterSeeder actually created
        $wbcParam = TestParameter::where('test_code', 'WBC')->first();
        $rbcParam = TestParameter::where('test_code', 'RBC')->first();
        // You could add HGB, HCT, PLT params here too if needed

        // --- Seed Ranges for WBC Parameter ---
        if ($wbcParam) {
            // Use updateOrCreate to avoid duplicates if seeder runs multiple times
            // Define ranges as an array
            $wbcRanges = [
                 // Example: Newborn (0-30 days)
                [
                    'test_parameter_id' => $wbcParam->id,
                    'si_range' => '9.0-30.0', 'si_unit' => '10^9/L',
                    'conv_range' => '9.0-30.0', 'conv_unit' => '10^3/uL',
                    'low_value' => 9.0, 'high_value' => 30.0,
                    'age_min' => 0, 'age_max' => 30, 'age_unit' => 'Days', 'gender' => 'Any', 'equipment_id' => null
                ],
                // Example: Adult Male (18+ years)
                [
                    'test_parameter_id' => $wbcParam->id,
                    'si_range' => '4.5-11.0', 'si_unit' => '10^9/L',
                    'conv_range' => '4.5-11.0', 'conv_unit' => '10^3/uL',
                    'low_value' => 4.5, 'high_value' => 11.0,
                    'low_critical_value' => 2.0, 'high_critical_value' => 50.0,
                    'age_min' => 18, 'age_max' => 120, 'age_unit' => 'Years', 'gender' => 'Male', 'equipment_id' => null
                ],
                 // Example: Adult Female (18+ years)
                 [
                    'test_parameter_id' => $wbcParam->id,
                    'si_range' => '4.0-10.5', 'si_unit' => '10^9/L',
                    'conv_range' => '4.0-10.5', 'conv_unit' => '10^3/uL',
                    'low_value' => 4.0, 'high_value' => 10.5,
                    'low_critical_value' => 2.0, 'high_critical_value' => 50.0,
                    'age_min' => 18, 'age_max' => 120, 'age_unit' => 'Years', 'gender' => 'Female', 'equipment_id' => null
                ],
            ];
            // Insert or update based on unique criteria (e.g., param_id, age, gender, analyzer)
            foreach ($wbcRanges as $rangeData) {
                 ReferenceRange::updateOrCreate(
                     [
                         'test_parameter_id' => $rangeData['test_parameter_id'],
                         'age_min' => $rangeData['age_min'],
                         'age_max' => $rangeData['age_max'],
                         'age_unit' => $rangeData['age_unit'],
                         'gender' => $rangeData['gender'],
                         'equipment_id' => $rangeData['equipment_id'] ?? null
                     ],
                     $rangeData // Data to insert/update
                 );
            }
            $this->command->info("Reference ranges seeded/updated for parameter: {$wbcParam->test_name}");
        } else {
             $this->command->warn("Could not find Test Parameter with code 'WBC' to seed ranges.");
        }

        // --- Seed Ranges for RBC Parameter ---
        if ($rbcParam) {
             $rbcRanges = [
                 // Example: Adult Male
                 [
                    'test_parameter_id' => $rbcParam->id,
                    'si_range' => '4.5-5.9', 'si_unit' => '10^12/L',
                    'conv_range' => '4.5-5.9', 'conv_unit' => '10^6/uL',
                    'low_value' => 4.5, 'high_value' => 5.9,
                    'age_min' => 18, 'age_max' => 120, 'age_unit' => 'Years', 'gender' => 'Male', 'equipment_id' => null
                ],
                // Example: Adult Female
                 [
                    'test_parameter_id' => $rbcParam->id,
                    'si_range' => '4.0-5.2', 'si_unit' => '10^12/L',
                    'conv_range' => '4.0-5.2', 'conv_unit' => '10^6/uL',
                    'low_value' => 4.0, 'high_value' => 5.2,
                    'age_min' => 18, 'age_max' => 120, 'age_unit' => 'Years', 'gender' => 'Female', 'equipment_id' => null
                ],
             ];
             foreach ($rbcRanges as $rangeData) {
                  ReferenceRange::updateOrCreate(
                      [
                          'test_parameter_id' => $rangeData['test_parameter_id'],
                          'age_min' => $rangeData['age_min'],
                          'age_max' => $rangeData['age_max'],
                          'age_unit' => $rangeData['age_unit'],
                          'gender' => $rangeData['gender'],
                          'equipment_id' => $rangeData['equipment_id'] ?? null
                      ],
                      $rangeData
                  );
             }
              $this->command->info("Reference ranges seeded/updated for parameter: {$rbcParam->test_name}");
        } else {
             $this->command->warn("Could not find Test Parameter with code 'RBC' to seed ranges.");
        }

         $this->command->info('Reference Range seeding process finished.');

    }
}
