<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TestParameter;
use App\Models\Test;
use Illuminate\Support\Facades\DB;

class TestParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if tests table has data
        $cbcTest = Test::where('name', 'like', '%Complete Blood Count%')->first(); // Use like for flexibility
        if (!$cbcTest) {
            $this->command->warn('Could not find a CBC test to add parameters to. Skipping.');
            return; // Or attempt to create one?
        }

        $this->command->info("Found CBC Test (ID: {$cbcTest->id}). Seeding parameters...");

        // Define parameters with conversion factors
        $parameters = [
            [
                'test_id' => $cbcTest->id,
                'test_name' => 'White Blood Cell Count',
                'test_code' => 'WBC',
                'specimen_type' => 'Whole Blood',
                'unit' => '10^9/L', // Added default unit
                'conversion_factor' => 0.001, // Example: 10^9/L to 10^3/uL
                'sequence_no' => 1,
            ],
            [
                'test_id' => $cbcTest->id,
                'test_name' => 'Red Blood Cell Count',
                'test_code' => 'RBC',
                'specimen_type' => 'Whole Blood',
                'unit' => '10^12/L', // Added default unit
                'conversion_factor' => 1, // Example: Assuming 10^12/L to 10^6/uL (often same magnitude)
                'sequence_no' => 2,
            ],
             [
                'test_id' => $cbcTest->id,
                'test_name' => 'Hemoglobin',
                'test_code' => 'HGB',
                'specimen_type' => 'Whole Blood',
                'unit' => 'g/L', // Added default unit
                'conversion_factor' => 0.1, // Example: g/L to g/dL
                'sequence_no' => 3,
            ],
            [
                'test_id' => $cbcTest->id,
                'test_name' => 'Hematocrit',
                'test_code' => 'HCT',
                'specimen_type' => 'Whole Blood',
                'unit' => 'L/L', // Added default unit (fraction)
                'conversion_factor' => 100, // Factor to convert TO %
                'sequence_no' => 4,
            ],
             [
                'test_id' => $cbcTest->id,
                'test_name' => 'Platelet Count',
                'test_code' => 'PLT',
                'specimen_type' => 'Whole Blood',
                'unit' => '10^9/L', // Added default unit
                'conversion_factor' => 1, // Example: 10^9/L to 10^3/uL
                'sequence_no' => 5,
            ],
             // Add more CBC parameters as needed
        ];

        // Insert parameters
        foreach ($parameters as $paramData) {
            TestParameter::updateOrCreate(
                ['test_id' => $paramData['test_id'], 'test_code' => $paramData['test_code']], // Find by test_id and code
                $paramData // Data to insert or update
            );
            $this->command->info(" - Seeded/Updated parameter: {$paramData['test_name']}");
        }

        $this->command->info('Test parameters seeding finished for CBC.');

        // --- Optionally add parameters for other tests like Blood Typing ---
        $btTest = Test::where('name', 'like', '%Blood Typing%')->first();
        if ($btTest) {
             TestParameter::updateOrCreate(
                 ['test_id' => $btTest->id, 'test_code' => 'BTP'],
                 [
                    'test_id' => $btTest->id,
                    'test_name' => 'Blood Type',
                    'test_code' => 'BTP',
                    'specimen_type' => 'Whole Blood',
                    'unit' => null, // No specific unit
                    'conversion_factor' => null, // No conversion needed/applicable
                    'sequence_no' => 1,
                 ]
            );
            $this->command->info(" - Seeded/Updated parameter: Blood Type");
        } else {
             $this->command->warn('Could not find a Blood Typing test to add parameters to.');
        }
    }
}
