<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QcAnalyteSigmaConfig;
use App\Models\TestParameter;
use Illuminate\Support\Facades\DB;

class QcAnalyteSigmaConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some test parameters to configure
        $testParameters = TestParameter::with('test')
            ->limit(10)
            ->get();

        if ($testParameters->isEmpty()) {
            $this->command->warn('No test parameters found. Please seed TestParameter first.');
            return;
        }

        // Common TE_a values based on CLIA, CAP, and other regulatory standards
        $teAValues = [
            // Chemistry - Common analytes with TE_a values
            'glucose' => ['value' => 6.0, 'unit' => '%'],
            'creatinine' => ['value' => 15.0, 'unit' => '%'],
            'sodium' => ['value' => 0.8, 'unit' => '%'], // Or could be mmol/L
            'potassium' => ['value' => 5.0, 'unit' => '%'],
            'chloride' => ['value' => 5.0, 'unit' => '%'],
            'bun' => ['value' => 20.0, 'unit' => '%'],
            'total_protein' => ['value' => 10.0, 'unit' => '%'],
            'albumin' => ['value' => 10.0, 'unit' => '%'],
            'bilirubin_total' => ['value' => 20.0, 'unit' => '%'],
            'alt' => ['value' => 20.0, 'unit' => '%'],
            'ast' => ['value' => 20.0, 'unit' => '%'],
            'alkaline_phosphatase' => ['value' => 15.0, 'unit' => '%'],
            // Hematology
            'wbc' => ['value' => 15.0, 'unit' => '%'],
            'rbc' => ['value' => 6.0, 'unit' => '%'],
            'hemoglobin' => ['value' => 7.0, 'unit' => '%'],
            'hematocrit' => ['value' => 6.0, 'unit' => '%'],
            'platelet' => ['value' => 25.0, 'unit' => '%'],
            // Hormones
            'tsh' => ['value' => 12.0, 'unit' => '%'],
            't4' => ['value' => 12.0, 'unit' => '%'],
            // Default fallback
            'default' => ['value' => 10.0, 'unit' => '%'],
        ];

        $configsCreated = 0;

        foreach ($testParameters as $testParameter) {
            // Try to match by test name or test code
            $testName = strtolower($testParameter->test_name ?? '');
            $testCode = strtolower($testParameter->test_code ?? '');

            // Find matching TE_a value
            $teA = $teAValues['default'];
            
            // Check for exact matches in test name
            foreach ($teAValues as $key => $value) {
                if ($key !== 'default' && 
                    (stripos($testName, $key) !== false || 
                     stripos($testCode, $key) !== false ||
                     stripos(strtolower($testParameter->test->name ?? ''), $key) !== false)) {
                    $teA = $value;
                    break;
                }
            }

            // Special handling for common abbreviations
            if (stripos($testCode, 'glu') !== false) {
                $teA = $teAValues['glucose'];
            } elseif (stripos($testCode, 'cre') !== false || stripos($testName, 'creatinine') !== false) {
                $teA = $teAValues['creatinine'];
            } elseif (stripos($testCode, 'na') !== false || stripos($testName, 'sodium') !== false) {
                $teA = $teAValues['sodium'];
            } elseif (stripos($testCode, 'k') !== false || stripos($testName, 'potassium') !== false) {
                $teA = $teAValues['potassium'];
            } elseif (stripos($testCode, 'wbc') !== false || stripos($testName, 'white blood') !== false) {
                $teA = $teAValues['wbc'];
            } elseif (stripos($testCode, 'rbc') !== false || stripos($testName, 'red blood') !== false) {
                $teA = $teAValues['rbc'];
            } elseif (stripos($testCode, 'hgb') !== false || stripos($testCode, 'hb') !== false || stripos($testName, 'hemoglobin') !== false) {
                $teA = $teAValues['hemoglobin'];
            } elseif (stripos($testCode, 'hct') !== false || stripos($testName, 'hematocrit') !== false) {
                $teA = $teAValues['hematocrit'];
            } elseif (stripos($testCode, 'plt') !== false || stripos($testName, 'platelet') !== false) {
                $teA = $teAValues['platelet'];
            }

            // Check if config already exists for this parameter
            $existingConfig = QcAnalyteSigmaConfig::where('test_parameter_id', $testParameter->id)
                ->where('date_effective', '<=', now())
                ->whereNull('deleted_at')
                ->first();

            if (!$existingConfig) {
                QcAnalyteSigmaConfig::create([
                    'test_parameter_id' => $testParameter->id,
                    't_e_a' => $teA['value'],
                    'te_a_unit' => $teA['unit'],
                    'date_effective' => now()->subDays(30), // Effective 30 days ago
                    'is_active' => true,
                    'created_by' => 1,
                ]);
                $configsCreated++;
            }
        }

        $this->command->info("Created {$configsCreated} TE_a configurations for test parameters.");
    }
}



