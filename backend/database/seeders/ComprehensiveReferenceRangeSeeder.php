<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReferenceRange;
use App\Models\TestParameter;
use App\Models\Analyzer;

class ComprehensiveReferenceRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!TestParameter::exists()) {
            $this->command->warn('No test parameters found. Run ComprehensiveTestParameterSeeder first.');
            return;
        }

        // Resolve a default analyzer to attribute instrument-specific ranges when available
        $defaultAnalyzerId = Analyzer::query()->value('id');

        $rangesByCode = $this->getAdultRangesByCode($defaultAnalyzerId);
        $created = 0; $updated = 0; $skipped = 0; $missing = [];

        foreach ($rangesByCode as $testCode => $config) {
            $param = TestParameter::where('test_code', $testCode)->first();
            if (!$param) {
                $missing[] = $testCode;
                continue;
            }

            // Allow male/female/any definitions per code
            $definitions = $config['definitions'];
            foreach ($definitions as $definition) {
                $payload = [
                    'test_parameter_id' => $param->id,
                    'equipment_id' => $definition['equipment_id'] ?? null,
                    'si_range' => $definition['si_range'] ?? null,
                    'si_unit' => $definition['si_unit'] ?? ($config['si_unit'] ?? null),
                    'conv_range' => $definition['conv_range'] ?? null,
                    'conv_unit' => $definition['conv_unit'] ?? ($config['conv_unit'] ?? null),
                    'low_critical_value' => $definition['low_critical_value'] ?? null,
                    'low_value' => $definition['low_value'] ?? null,
                    'high_value' => $definition['high_value'] ?? null,
                    'high_critical_value' => $definition['high_critical_value'] ?? null,
                    'age_min' => $definition['age_min'] ?? 18,
                    'age_max' => $definition['age_max'] ?? 99,
                    'age_unit' => $definition['age_unit'] ?? 'Years',
                    'gender' => $definition['gender'] ?? 'Any',
                ];

                $unique = [
                    'test_parameter_id' => $payload['test_parameter_id'],
                    'age_min' => $payload['age_min'],
                    'age_max' => $payload['age_max'],
                    'age_unit' => $payload['age_unit'],
                    'gender' => $payload['gender'],
                    'equipment_id' => $payload['equipment_id'],
                ];

                $existing = ReferenceRange::where($unique)->first();
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    ReferenceRange::create($payload);
                    $created++;
                }
            }
        }

        if (!empty($missing)) {
            $this->command->warn('Missing TestParameter for codes: ' . implode(', ', $missing));
        }
        $this->command->info("Reference ranges upserted. Created: {$created}, Updated: {$updated}, Missing params: " . count($missing));
    }

    /**
     * Returns adult reference ranges for common analytes keyed by test_code.
     * Values are SI; conv strings provided for display when customary.
     */
    private function getAdultRangesByCode(?int $defaultAnalyzerId = null): array
    {
        return [
            // Hematology - CBC
            'WBC' => [
                'si_unit' => '10^9/L', 'conv_unit' => '10^3/µL',
                'definitions' => [
                    ['gender' => 'Any', 'low_value' => 4.0, 'high_value' => 11.0, 'low_critical_value' => 2.0, 'high_critical_value' => 50.0, 'si_range' => '4.0–11.0', 'conv_range' => '4.0–11.0', 'equipment_id' => $defaultAnalyzerId],
                ],
            ],
            'RBC' => [
                'si_unit' => '10^12/L', 'conv_unit' => '10^6/µL',
                'definitions' => [
                    ['gender' => 'Male', 'low_value' => 4.5, 'high_value' => 5.9, 'low_critical_value' => 3.0, 'high_critical_value' => 7.0, 'si_range' => '4.5–5.9', 'equipment_id' => $defaultAnalyzerId],
                    ['gender' => 'Female', 'low_value' => 4.0, 'high_value' => 5.2, 'low_critical_value' => 2.5, 'high_critical_value' => 6.5, 'si_range' => '4.0–5.2', 'equipment_id' => $defaultAnalyzerId],
                ],
            ],
            'HGB' => [
                'si_unit' => 'g/L', 'conv_unit' => 'g/dL',
                'definitions' => [
                    ['gender' => 'Male', 'low_value' => 130, 'high_value' => 170, 'low_critical_value' => 70, 'high_critical_value' => 230, 'si_range' => '130–170', 'conv_range' => '13.0–17.0', 'equipment_id' => $defaultAnalyzerId],
                    ['gender' => 'Female', 'low_value' => 120, 'high_value' => 150, 'low_critical_value' => 70, 'high_critical_value' => 230, 'si_range' => '120–150', 'conv_range' => '12.0–15.0', 'equipment_id' => $defaultAnalyzerId],
                ],
            ],
            'HCT' => [
                'si_unit' => 'L/L', 'conv_unit' => '%',
                'definitions' => [
                    ['gender' => 'Male', 'low_value' => 0.40, 'high_value' => 0.50, 'low_critical_value' => 0.20, 'high_critical_value' => 0.65, 'si_range' => '0.40–0.50', 'conv_range' => '40–50', 'equipment_id' => $defaultAnalyzerId],
                    ['gender' => 'Female', 'low_value' => 0.36, 'high_value' => 0.44, 'low_critical_value' => 0.20, 'high_critical_value' => 0.65, 'si_range' => '0.36–0.44', 'conv_range' => '36–44', 'equipment_id' => $defaultAnalyzerId],
                ],
            ],
            'PLT' => [
                'si_unit' => '10^9/L', 'conv_unit' => '10^3/µL',
                'definitions' => [
                    ['gender' => 'Any', 'low_value' => 150, 'high_value' => 400, 'low_critical_value' => 20, 'high_critical_value' => 1000, 'si_range' => '150–400', 'equipment_id' => $defaultAnalyzerId],
                ],
            ],
            'MCV' => [
                'si_unit' => 'fL',
                'definitions' => [
                    ['gender' => 'Any', 'low_value' => 80, 'high_value' => 100, 'si_range' => '80–100'],
                ],
            ],
            'MCH' => [
                'si_unit' => 'pg',
                'definitions' => [
                    ['gender' => 'Any', 'low_value' => 27, 'high_value' => 33, 'si_range' => '27–33'],
                ],
            ],
            'MCHC' => [
                'si_unit' => 'g/L', 'conv_unit' => 'g/dL',
                'definitions' => [
                    ['gender' => 'Any', 'low_value' => 320, 'high_value' => 360, 'low_critical_value' => 250, 'high_critical_value' => 420, 'si_range' => '320–360', 'conv_range' => '32.0–36.0', 'equipment_id' => $defaultAnalyzerId],
                ],
            ],
            // Differential (percentages)
            'NEUT' => ['si_unit' => '%', 'definitions' => [['gender' => 'Any', 'low_value' => 40, 'high_value' => 75, 'si_range' => '40–75']]],
            'LYMPH' => ['si_unit' => '%', 'definitions' => [['gender' => 'Any', 'low_value' => 20, 'high_value' => 45, 'si_range' => '20–45']]],
            'MONO' => ['si_unit' => '%', 'definitions' => [['gender' => 'Any', 'low_value' => 2, 'high_value' => 10, 'si_range' => '2–10']]],
            'EOS' => ['si_unit' => '%', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 6, 'si_range' => '0–6']]],
            'BASO' => ['si_unit' => '%', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 2, 'si_range' => '0–2']]],

            // Electrolytes
            'NA' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 135, 'high_value' => 145, 'low_critical_value' => 120, 'high_critical_value' => 160, 'si_range' => '135–145']]],
            'K'  => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 3.5, 'high_value' => 5.1, 'low_critical_value' => 2.5, 'high_critical_value' => 6.5, 'si_range' => '3.5–5.1']]],
            'CL' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 98, 'high_value' => 107, 'low_critical_value' => 80, 'high_critical_value' => 120, 'si_range' => '98–107']]],
            'CO2'=> ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 22, 'high_value' => 29, 'low_critical_value' => 10, 'high_critical_value' => 40, 'si_range' => '22–29']]],

            // Glucose & Diabetes
            'GLU' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 3.9, 'high_value' => 5.6, 'low_critical_value' => 2.5, 'high_critical_value' => 25.0, 'si_range' => '3.9–5.6']]],
            'FBS' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 3.9, 'high_value' => 5.6, 'low_critical_value' => 2.5, 'high_critical_value' => 25.0, 'si_range' => '3.9–5.6']]],
            'RBS' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 3.9, 'high_value' => 7.7, 'low_critical_value' => 2.5, 'high_critical_value' => 30.0, 'si_range' => '3.9–7.7']]],
            'HBA1C' => ['si_unit' => '%', 'definitions' => [['gender' => 'Any', 'low_value' => 4.0, 'high_value' => 5.6, 'si_range' => '4.0–5.6']]],

            // Lipid panel
            'CHOL' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 5.2, 'si_range' => '≤5.2']]],
            'TRIG' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0.3, 'high_value' => 1.7, 'si_range' => '0.3–1.7']]],
            'LDL'  => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 3.4, 'si_range' => '≤3.4']]],
            'HDL'  => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 1.0, 'high_value' => 2.0, 'si_range' => '1.0–2.0']]],

            // Kidney function
            'CREA' => [
                'si_unit' => 'µmol/L',
                'definitions' => [
                    ['gender' => 'Male', 'low_value' => 62, 'high_value' => 115, 'low_critical_value' => 30, 'high_critical_value' => 1000, 'si_range' => '62–115'],
                    ['gender' => 'Female', 'low_value' => 45, 'high_value' => 97, 'low_critical_value' => 30, 'high_critical_value' => 1000, 'si_range' => '45–97'],
                ],
            ],
            'UREA' => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 2.5, 'high_value' => 7.8, 'low_critical_value' => 1.0, 'high_critical_value' => 40.0, 'si_range' => '2.5–7.8']]],
            'BUN'  => ['si_unit' => 'mmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 2.5, 'high_value' => 7.8, 'low_critical_value' => 1.0, 'high_critical_value' => 40.0, 'si_range' => '2.5–7.8']]],
            'UA'   => ['si_unit' => 'µmol/L', 'definitions' => [
                ['gender' => 'Male', 'low_value' => 202, 'high_value' => 416, 'low_critical_value' => 120, 'high_critical_value' => 700, 'si_range' => '202–416'],
                ['gender' => 'Female', 'low_value' => 143, 'high_value' => 357, 'low_critical_value' => 120, 'high_critical_value' => 700, 'si_range' => '143–357'],
            ]],

            // Liver function
            'TBIL' => ['si_unit' => 'µmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 3.4, 'high_value' => 20.5, 'si_range' => '3.4–20.5']]],
            'DBIL' => ['si_unit' => 'µmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 7.0, 'si_range' => '≤7.0']]],
            'IBIL' => ['si_unit' => 'µmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 13.7, 'si_range' => '≤13.7']]],
            'ALT'  => ['si_unit' => 'U/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 41, 'low_critical_value' => 0, 'high_critical_value' => 1000, 'si_range' => '≤41']]],
            'AST'  => ['si_unit' => 'U/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0, 'high_value' => 40, 'low_critical_value' => 0, 'high_critical_value' => 1000, 'si_range' => '≤40']]],
            'ALP'  => ['si_unit' => 'U/L', 'definitions' => [['gender' => 'Any', 'low_value' => 44, 'high_value' => 147, 'si_range' => '44–147']]],
            'TP'   => ['si_unit' => 'g/L', 'definitions' => [['gender' => 'Any', 'low_value' => 64, 'high_value' => 83, 'si_range' => '64–83']]],
            'ALB'  => ['si_unit' => 'g/L', 'definitions' => [['gender' => 'Any', 'low_value' => 35, 'high_value' => 50, 'si_range' => '35–50']]],
            'GLOB' => ['si_unit' => 'g/L', 'definitions' => [['gender' => 'Any', 'low_value' => 20, 'high_value' => 35, 'si_range' => '20–35']]],

            // Thyroid
            'TSH'  => ['si_unit' => 'mIU/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0.4, 'high_value' => 4.0, 'si_range' => '0.4–4.0']]],
            'FT4'  => ['si_unit' => 'pmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 12, 'high_value' => 22, 'si_range' => '12–22']]],
            'FT3'  => ['si_unit' => 'pmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 3.1, 'high_value' => 6.8, 'si_range' => '3.1–6.8']]],

            // Inflammation markers
            'CRP'  => ['si_unit' => 'mg/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0.0, 'high_value' => 5.0, 'low_critical_value' => 0.0, 'high_critical_value' => 300.0, 'si_range' => '≤5.0']]],
            'PCT'  => ['si_unit' => 'ng/mL', 'definitions' => [['gender' => 'Any', 'low_value' => 0.0, 'high_value' => 0.5, 'low_critical_value' => 0.0, 'high_critical_value' => 100.0, 'si_range' => '≤0.5']]],

            // Iron / vitamins
            'FERR' => ['si_unit' => 'µg/L', 'definitions' => [
                ['gender' => 'Male', 'low_value' => 30, 'high_value' => 400, 'si_range' => '30–400'],
                ['gender' => 'Female', 'low_value' => 13, 'high_value' => 150, 'si_range' => '13–150'],
            ]],
            'B12'  => ['si_unit' => 'pmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 148, 'high_value' => 615, 'si_range' => '148–615']]],
            'FOL'  => ['si_unit' => 'nmol/L', 'definitions' => [['gender' => 'Any', 'low_value' => 7.0, 'high_value' => 45.0, 'si_range' => '7.0–45.0']]],

            // Prostate
            'PSA'  => ['si_unit' => 'ng/mL', 'definitions' => [['gender' => 'Male', 'low_value' => 0.0, 'high_value' => 4.0, 'low_critical_value' => 0.0, 'high_critical_value' => 100.0, 'si_range' => '≤4.0']]],

            // Coagulation
            'PT'   => ['si_unit' => 'seconds', 'definitions' => [['gender' => 'Any', 'low_value' => 11.0, 'high_value' => 13.5, 'low_critical_value' => 8.0, 'high_critical_value' => 60.0, 'si_range' => '11.0–13.5']]],
            'INR'  => ['si_unit' => 'ratio', 'definitions' => [['gender' => 'Any', 'low_value' => 0.8, 'high_value' => 1.2, 'low_critical_value' => 0.5, 'high_critical_value' => 5.0, 'si_range' => '0.8–1.2']]],
            'APTT' => ['si_unit' => 'seconds', 'definitions' => [['gender' => 'Any', 'low_value' => 25.0, 'high_value' => 35.0, 'low_critical_value' => 15.0, 'high_critical_value' => 150.0, 'si_range' => '25–35']]],
            'FIB'  => ['si_unit' => 'g/L', 'definitions' => [['gender' => 'Any', 'low_value' => 2.0, 'high_value' => 4.0, 'low_critical_value' => 0.5, 'high_critical_value' => 10.0, 'si_range' => '2.0–4.0']]],
            'DDIM' => ['si_unit' => 'mg/L', 'definitions' => [['gender' => 'Any', 'low_value' => 0.0, 'high_value' => 0.5, 'low_critical_value' => 0.0, 'high_critical_value' => 20.0, 'si_range' => '≤0.5']]],
        ];
    }
}


