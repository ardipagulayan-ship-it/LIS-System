<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TestCategory;
use App\Models\Test;
use App\Models\TestParameter;
use Illuminate\Support\Facades\DB;

class ComprehensiveTestParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates comprehensive test categories, tests, and test parameters with proper relationships.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive test structure...');

        // Step 1: Ensure test categories exist
        $categories = $this->createTestCategories();
        
        // Step 2: Create tests for each category
        $tests = $this->createTests($categories);
        
        // Step 3: Create test parameters for each test
        $this->createTestParameters($tests);

        $this->command->info('Comprehensive test structure created successfully!');
    }

    /**
     * Create or get test categories
     */
    private function createTestCategories(): array
    {
        $categoriesData = [
            [
                'name' => 'Hematology',
                'description' => 'Blood cell analysis and coagulation studies',
            ],
            [
                'name' => 'Clinical Chemistry',
                'description' => 'Biochemistry tests including liver function, kidney function, lipids, and electrolytes',
            ],
            [
                'name' => 'Coagulation',
                'description' => 'Coagulation and hemostasis studies',
            ],
            [
                'name' => 'Serology/Immunology',
                'description' => 'Serological and immunological tests',
            ],
            [
                'name' => 'Clinical Microscopy',
                'description' => 'Urine analysis and other microscopic examinations',
            ],
            [
                'name' => 'Microbiology',
                'description' => 'Microbiological cultures and identifications',
            ],
            [
                'name' => 'Histopathology',
                'description' => 'Tissue examination and pathology',
            ],
            [
                'name' => 'Molecular Diagnostics',
                'description' => 'Molecular and genetic testing',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $catData) {
            $category = TestCategory::firstOrCreate(
                ['name' => $catData['name']],
                $catData
            );
            $categories[$catData['name']] = $category;
            $this->command->info("  ✓ Category: {$catData['name']}");
        }

        return $categories;
    }

    /**
     * Create tests for each category
     */
    private function createTests(array $categories): array
    {
        $testsData = [
            // Hematology Tests
            'Hematology' => [
                [
                    'name' => 'Complete Blood Count (CBC)',
                    'description' => 'Complete blood count with differential',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 30,
                ],
                [
                    'name' => 'Hemoglobin',
                    'description' => 'Hemoglobin level',
                    'price' => 150.00,
                    'turnaround_time_minutes' => 30,
                ],
                [
                    'name' => 'Hematocrit',
                    'description' => 'Hematocrit level',
                    'price' => 150.00,
                    'turnaround_time_minutes' => 30,
                ],
                [
                    'name' => 'ESR (Erythrocyte Sedimentation Rate)',
                    'description' => 'ESR test',
                    'price' => 200.00,
                    'turnaround_time_minutes' => 60,
                ],
            ],

            // Clinical Chemistry Tests
            'Clinical Chemistry' => [
                [
                    'name' => 'Liver Function Test (LFT)',
                    'description' => 'Comprehensive liver function panel',
                    'price' => 800.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Kidney Function Test (KFT/RFT)',
                    'description' => 'Renal function panel',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Lipid Profile',
                    'description' => 'Complete lipid panel',
                    'price' => 700.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Electrolyte Panel',
                    'description' => 'Sodium, Potassium, Chloride, CO2',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 45,
                ],
                [
                    'name' => 'Blood Glucose',
                    'description' => 'Blood glucose level',
                    'price' => 200.00,
                    'turnaround_time_minutes' => 30,
                ],
                [
                    'name' => 'Fasting Blood Sugar (FBS)',
                    'description' => 'Fasting blood glucose',
                    'price' => 200.00,
                    'turnaround_time_minutes' => 30,
                ],
                [
                    'name' => 'Random Blood Sugar (RBS)',
                    'description' => 'Random blood glucose',
                    'price' => 200.00,
                    'turnaround_time_minutes' => 30,
                ],
                [
                    'name' => 'HbA1c (Glycated Hemoglobin)',
                    'description' => 'Hemoglobin A1c for diabetes monitoring',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Cardiac Markers',
                    'description' => 'Troponin, CK-MB, BNP',
                    'price' => 1200.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Thyroid Function Test (TFT)',
                    'description' => 'TSH, Free T4, Free T3',
                    'price' => 900.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'C-Reactive Protein (CRP)',
                    'description' => 'CRP test',
                    'price' => 400.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Procalcitonin',
                    'description' => 'Procalcitonin test',
                    'price' => 800.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Vitamin B12',
                    'description' => 'Vitamin B12 level',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Folate',
                    'description' => 'Folate level',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Ferritin',
                    'description' => 'Ferritin level',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'PSA (Prostate Specific Antigen)',
                    'description' => 'PSA test',
                    'price' => 700.00,
                    'turnaround_time_minutes' => 60,
                ],
            ],

            // Coagulation Tests
            'Coagulation' => [
                [
                    'name' => 'Prothrombin Time (PT)',
                    'description' => 'PT test',
                    'price' => 400.00,
                    'turnaround_time_minutes' => 45,
                ],
                [
                    'name' => 'INR (International Normalized Ratio)',
                    'description' => 'INR test',
                    'price' => 400.00,
                    'turnaround_time_minutes' => 45,
                ],
                [
                    'name' => 'APTT (Activated Partial Thromboplastin Time)',
                    'description' => 'APTT test',
                    'price' => 400.00,
                    'turnaround_time_minutes' => 45,
                ],
                [
                    'name' => 'Fibrinogen',
                    'description' => 'Fibrinogen level',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'D-Dimer',
                    'description' => 'D-Dimer test',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 60,
                ],
            ],

            // Serology/Immunology Tests
            'Serology/Immunology' => [
                [
                    'name' => 'Hepatitis B Surface Antigen (HBsAg)',
                    'description' => 'HBsAg test',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'Hepatitis C Antibody (HCV Ab)',
                    'description' => 'HCV antibody test',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'HIV Test',
                    'description' => 'HIV screening',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'RA Factor',
                    'description' => 'Rheumatoid factor',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 60,
                ],
                [
                    'name' => 'ANA (Antinuclear Antibody)',
                    'description' => 'ANA test',
                    'price' => 700.00,
                    'turnaround_time_minutes' => 120,
                ],
            ],

            // Clinical Microscopy Tests
            'Clinical Microscopy' => [
                [
                    'name' => 'Urine Analysis (Complete)',
                    'description' => 'Complete urine analysis',
                    'price' => 300.00,
                    'turnaround_time_minutes' => 30,
                ],
                [
                    'name' => 'Urine Culture',
                    'description' => 'Urine culture and sensitivity',
                    'price' => 500.00,
                    'turnaround_time_minutes' => 2880, // 48 hours
                ],
                [
                    'name' => 'Stool Analysis',
                    'description' => 'Stool examination',
                    'price' => 300.00,
                    'turnaround_time_minutes' => 60,
                ],
            ],

            // Microbiology Tests
            'Microbiology' => [
                [
                    'name' => 'Blood Culture',
                    'description' => 'Blood culture and sensitivity',
                    'price' => 800.00,
                    'turnaround_time_minutes' => 4320, // 72 hours
                ],
                [
                    'name' => 'Sputum Culture',
                    'description' => 'Sputum culture and sensitivity',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 2880, // 48 hours
                ],
                [
                    'name' => 'Wound Culture',
                    'description' => 'Wound culture and sensitivity',
                    'price' => 600.00,
                    'turnaround_time_minutes' => 2880, // 48 hours
                ],
            ],

            // Histopathology Tests
            'Histopathology' => [
                [
                    'name' => 'Tissue Biopsy',
                    'description' => 'Tissue examination',
                    'price' => 2000.00,
                    'turnaround_time_minutes' => 1440, // 24 hours
                ],
                [
                    'name' => 'Cytology',
                    'description' => 'Cytological examination',
                    'price' => 1500.00,
                    'turnaround_time_minutes' => 1440, // 24 hours
                ],
            ],

            // Molecular Diagnostics Tests
            'Molecular Diagnostics' => [
                [
                    'name' => 'PCR Test',
                    'description' => 'Polymerase chain reaction test',
                    'price' => 1500.00,
                    'turnaround_time_minutes' => 240, // 4 hours
                ],
                [
                    'name' => 'COVID-19 RT-PCR',
                    'description' => 'COVID-19 RT-PCR test',
                    'price' => 1200.00,
                    'turnaround_time_minutes' => 240, // 4 hours
                ],
            ],
        ];

        $tests = [];
        foreach ($testsData as $categoryName => $categoryTests) {
            if (!isset($categories[$categoryName])) {
                $this->command->warn("Category '{$categoryName}' not found, skipping tests.");
                continue;
            }

            $category = $categories[$categoryName];
            foreach ($categoryTests as $testData) {
                $test = Test::firstOrCreate(
                    [
                        'name' => $testData['name'],
                        'test_category_id' => $category->id,
                    ],
                    array_merge($testData, [
                        'test_category_id' => $category->id,
                    ])
                );
                $tests[$testData['name']] = $test;
                $this->command->info("  ✓ Test: {$testData['name']} (Category: {$categoryName})");
            }
        }

        return $tests;
    }

    /**
     * Create test parameters for each test
     */
    private function createTestParameters(array $tests): void
    {
        $parametersData = [
            // Complete Blood Count (CBC)
            'Complete Blood Count (CBC)' => [
                ['test_name' => 'White Blood Cell Count', 'test_code' => 'WBC', 'specimen_type' => 'Whole Blood', 'unit' => '10^9/L', 'conversion_factor' => 0.001, 'sequence_no' => 1],
                ['test_name' => 'Red Blood Cell Count', 'test_code' => 'RBC', 'specimen_type' => 'Whole Blood', 'unit' => '10^12/L', 'conversion_factor' => 1.0, 'sequence_no' => 2],
                ['test_name' => 'Hemoglobin', 'test_code' => 'HGB', 'specimen_type' => 'Whole Blood', 'unit' => 'g/L', 'conversion_factor' => 0.1, 'sequence_no' => 3],
                ['test_name' => 'Hematocrit', 'test_code' => 'HCT', 'specimen_type' => 'Whole Blood', 'unit' => 'L/L', 'conversion_factor' => 100.0, 'sequence_no' => 4],
                ['test_name' => 'Platelet Count', 'test_code' => 'PLT', 'specimen_type' => 'Whole Blood', 'unit' => '10^9/L', 'conversion_factor' => 1.0, 'sequence_no' => 5],
                ['test_name' => 'Mean Corpuscular Volume', 'test_code' => 'MCV', 'specimen_type' => 'Whole Blood', 'unit' => 'fL', 'conversion_factor' => 1.0, 'sequence_no' => 6],
                ['test_name' => 'Mean Corpuscular Hemoglobin', 'test_code' => 'MCH', 'specimen_type' => 'Whole Blood', 'unit' => 'pg', 'conversion_factor' => 1.0, 'sequence_no' => 7],
                ['test_name' => 'Mean Corpuscular Hemoglobin Concentration', 'test_code' => 'MCHC', 'specimen_type' => 'Whole Blood', 'unit' => 'g/L', 'conversion_factor' => 0.1, 'sequence_no' => 8],
                ['test_name' => 'Neutrophils', 'test_code' => 'NEUT', 'specimen_type' => 'Whole Blood', 'unit' => '%', 'conversion_factor' => 1.0, 'sequence_no' => 9],
                ['test_name' => 'Lymphocytes', 'test_code' => 'LYMPH', 'specimen_type' => 'Whole Blood', 'unit' => '%', 'conversion_factor' => 1.0, 'sequence_no' => 10],
                ['test_name' => 'Monocytes', 'test_code' => 'MONO', 'specimen_type' => 'Whole Blood', 'unit' => '%', 'conversion_factor' => 1.0, 'sequence_no' => 11],
                ['test_name' => 'Eosinophils', 'test_code' => 'EOS', 'specimen_type' => 'Whole Blood', 'unit' => '%', 'conversion_factor' => 1.0, 'sequence_no' => 12],
                ['test_name' => 'Basophils', 'test_code' => 'BASO', 'specimen_type' => 'Whole Blood', 'unit' => '%', 'conversion_factor' => 1.0, 'sequence_no' => 13],
            ],

            // Hemoglobin
            'Hemoglobin' => [
                ['test_name' => 'Hemoglobin', 'test_code' => 'HGB', 'specimen_type' => 'Whole Blood', 'unit' => 'g/L', 'conversion_factor' => 0.1, 'sequence_no' => 1],
            ],

            // Hematocrit
            'Hematocrit' => [
                ['test_name' => 'Hematocrit', 'test_code' => 'HCT', 'specimen_type' => 'Whole Blood', 'unit' => 'L/L', 'conversion_factor' => 100.0, 'sequence_no' => 1],
            ],

            // ESR
            'ESR (Erythrocyte Sedimentation Rate)' => [
                ['test_name' => 'ESR', 'test_code' => 'ESR', 'specimen_type' => 'Whole Blood', 'unit' => 'mm/hr', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // Liver Function Test (LFT)
            'Liver Function Test (LFT)' => [
                ['test_name' => 'Total Bilirubin', 'test_code' => 'TBIL', 'specimen_type' => 'Serum', 'unit' => 'μmol/L', 'conversion_factor' => 0.0585, 'sequence_no' => 1],
                ['test_name' => 'Direct Bilirubin', 'test_code' => 'DBIL', 'specimen_type' => 'Serum', 'unit' => 'μmol/L', 'conversion_factor' => 0.0585, 'sequence_no' => 2],
                ['test_name' => 'Indirect Bilirubin', 'test_code' => 'IBIL', 'specimen_type' => 'Serum', 'unit' => 'μmol/L', 'conversion_factor' => 0.0585, 'sequence_no' => 3],
                ['test_name' => 'ALT (Alanine Aminotransferase)', 'test_code' => 'ALT', 'specimen_type' => 'Serum', 'unit' => 'U/L', 'conversion_factor' => 1.0, 'sequence_no' => 4],
                ['test_name' => 'AST (Aspartate Aminotransferase)', 'test_code' => 'AST', 'specimen_type' => 'Serum', 'unit' => 'U/L', 'conversion_factor' => 1.0, 'sequence_no' => 5],
                ['test_name' => 'Alkaline Phosphatase', 'test_code' => 'ALP', 'specimen_type' => 'Serum', 'unit' => 'U/L', 'conversion_factor' => 1.0, 'sequence_no' => 6],
                ['test_name' => 'Total Protein', 'test_code' => 'TP', 'specimen_type' => 'Serum', 'unit' => 'g/L', 'conversion_factor' => 0.1, 'sequence_no' => 7],
                ['test_name' => 'Albumin', 'test_code' => 'ALB', 'specimen_type' => 'Serum', 'unit' => 'g/L', 'conversion_factor' => 0.1, 'sequence_no' => 8],
                ['test_name' => 'Globulin', 'test_code' => 'GLOB', 'specimen_type' => 'Serum', 'unit' => 'g/L', 'conversion_factor' => 0.1, 'sequence_no' => 9],
            ],

            // Kidney Function Test (KFT/RFT)
            'Kidney Function Test (KFT/RFT)' => [
                ['test_name' => 'Creatinine', 'test_code' => 'CREA', 'specimen_type' => 'Serum', 'unit' => 'μmol/L', 'conversion_factor' => 0.0113, 'sequence_no' => 1],
                ['test_name' => 'Urea', 'test_code' => 'UREA', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 2.801, 'sequence_no' => 2],
                ['test_name' => 'Blood Urea Nitrogen', 'test_code' => 'BUN', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 2.801, 'sequence_no' => 3],
                ['test_name' => 'Uric Acid', 'test_code' => 'UA', 'specimen_type' => 'Serum', 'unit' => 'μmol/L', 'conversion_factor' => 0.0168, 'sequence_no' => 4],
                ['test_name' => 'Sodium', 'test_code' => 'NA', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 1.0, 'sequence_no' => 5],
                ['test_name' => 'Potassium', 'test_code' => 'K', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 1.0, 'sequence_no' => 6],
                ['test_name' => 'Chloride', 'test_code' => 'CL', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 1.0, 'sequence_no' => 7],
            ],

            // Lipid Profile
            'Lipid Profile' => [
                ['test_name' => 'Total Cholesterol', 'test_code' => 'CHOL', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0259, 'sequence_no' => 1],
                ['test_name' => 'Triglycerides', 'test_code' => 'TRIG', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0113, 'sequence_no' => 2],
                ['test_name' => 'LDL Cholesterol', 'test_code' => 'LDL', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0259, 'sequence_no' => 3],
                ['test_name' => 'HDL Cholesterol', 'test_code' => 'HDL', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0259, 'sequence_no' => 4],
                ['test_name' => 'VLDL Cholesterol', 'test_code' => 'VLDL', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0259, 'sequence_no' => 5],
            ],

            // Electrolyte Panel
            'Electrolyte Panel' => [
                ['test_name' => 'Sodium', 'test_code' => 'NA', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 1.0, 'sequence_no' => 1],
                ['test_name' => 'Potassium', 'test_code' => 'K', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 1.0, 'sequence_no' => 2],
                ['test_name' => 'Chloride', 'test_code' => 'CL', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 1.0, 'sequence_no' => 3],
                ['test_name' => 'Carbon Dioxide (CO2)', 'test_code' => 'CO2', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 1.0, 'sequence_no' => 4],
            ],

            // Blood Glucose
            'Blood Glucose' => [
                ['test_name' => 'Glucose', 'test_code' => 'GLU', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0555, 'sequence_no' => 1],
            ],

            // Fasting Blood Sugar
            'Fasting Blood Sugar (FBS)' => [
                ['test_name' => 'Fasting Blood Glucose', 'test_code' => 'FBS', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0555, 'sequence_no' => 1],
            ],

            // Random Blood Sugar
            'Random Blood Sugar (RBS)' => [
                ['test_name' => 'Random Blood Glucose', 'test_code' => 'RBS', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'conversion_factor' => 0.0555, 'sequence_no' => 1],
            ],

            // HbA1c
            'HbA1c (Glycated Hemoglobin)' => [
                ['test_name' => 'HbA1c', 'test_code' => 'HBA1C', 'specimen_type' => 'Whole Blood', 'unit' => '%', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // Cardiac Markers
            'Cardiac Markers' => [
                ['test_name' => 'Troponin I', 'test_code' => 'TROP_I', 'specimen_type' => 'Serum', 'unit' => 'ng/mL', 'conversion_factor' => 1.0, 'sequence_no' => 1],
                ['test_name' => 'Troponin T', 'test_code' => 'TROP_T', 'specimen_type' => 'Serum', 'unit' => 'ng/mL', 'conversion_factor' => 1.0, 'sequence_no' => 2],
                ['test_name' => 'CK-MB', 'test_code' => 'CKMB', 'specimen_type' => 'Serum', 'unit' => 'U/L', 'conversion_factor' => 1.0, 'sequence_no' => 3],
                ['test_name' => 'BNP (Brain Natriuretic Peptide)', 'test_code' => 'BNP', 'specimen_type' => 'Plasma', 'unit' => 'pg/mL', 'conversion_factor' => 1.0, 'sequence_no' => 4],
                ['test_name' => 'NT-proBNP', 'test_code' => 'NTBNP', 'specimen_type' => 'Serum', 'unit' => 'pg/mL', 'conversion_factor' => 1.0, 'sequence_no' => 5],
            ],

            // Thyroid Function Test
            'Thyroid Function Test (TFT)' => [
                ['test_name' => 'TSH (Thyroid Stimulating Hormone)', 'test_code' => 'TSH', 'specimen_type' => 'Serum', 'unit' => 'mIU/L', 'conversion_factor' => 1.0, 'sequence_no' => 1],
                ['test_name' => 'Free T4', 'test_code' => 'FT4', 'specimen_type' => 'Serum', 'unit' => 'pmol/L', 'conversion_factor' => 0.0777, 'sequence_no' => 2],
                ['test_name' => 'Free T3', 'test_code' => 'FT3', 'specimen_type' => 'Serum', 'unit' => 'pmol/L', 'conversion_factor' => 0.0154, 'sequence_no' => 3],
            ],

            // CRP
            'C-Reactive Protein (CRP)' => [
                ['test_name' => 'C-Reactive Protein', 'test_code' => 'CRP', 'specimen_type' => 'Serum', 'unit' => 'mg/L', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // Procalcitonin
            'Procalcitonin' => [
                ['test_name' => 'Procalcitonin', 'test_code' => 'PCT', 'specimen_type' => 'Serum', 'unit' => 'ng/mL', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // Vitamin B12
            'Vitamin B12' => [
                ['test_name' => 'Vitamin B12', 'test_code' => 'B12', 'specimen_type' => 'Serum', 'unit' => 'pmol/L', 'conversion_factor' => 0.7378, 'sequence_no' => 1],
            ],

            // Folate
            'Folate' => [
                ['test_name' => 'Folate', 'test_code' => 'FOL', 'specimen_type' => 'Serum', 'unit' => 'nmol/L', 'conversion_factor' => 2.266, 'sequence_no' => 1],
            ],

            // Ferritin
            'Ferritin' => [
                ['test_name' => 'Ferritin', 'test_code' => 'FERR', 'specimen_type' => 'Serum', 'unit' => 'μg/L', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // PSA
            'PSA (Prostate Specific Antigen)' => [
                ['test_name' => 'PSA Total', 'test_code' => 'PSA', 'specimen_type' => 'Serum', 'unit' => 'ng/mL', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // Prothrombin Time
            'Prothrombin Time (PT)' => [
                ['test_name' => 'Prothrombin Time', 'test_code' => 'PT', 'specimen_type' => 'Plasma', 'unit' => 'seconds', 'conversion_factor' => 1.0, 'sequence_no' => 1],
                ['test_name' => 'INR', 'test_code' => 'INR', 'specimen_type' => 'Plasma', 'unit' => 'ratio', 'conversion_factor' => 1.0, 'sequence_no' => 2],
            ],

            // INR
            'INR (International Normalized Ratio)' => [
                ['test_name' => 'INR', 'test_code' => 'INR', 'specimen_type' => 'Plasma', 'unit' => 'ratio', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // APTT
            'APTT (Activated Partial Thromboplastin Time)' => [
                ['test_name' => 'APTT', 'test_code' => 'APTT', 'specimen_type' => 'Plasma', 'unit' => 'seconds', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // Fibrinogen
            'Fibrinogen' => [
                ['test_name' => 'Fibrinogen', 'test_code' => 'FIB', 'specimen_type' => 'Plasma', 'unit' => 'g/L', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // D-Dimer
            'D-Dimer' => [
                ['test_name' => 'D-Dimer', 'test_code' => 'DDIM', 'specimen_type' => 'Plasma', 'unit' => 'mg/L', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // HBsAg
            'Hepatitis B Surface Antigen (HBsAg)' => [
                ['test_name' => 'HBsAg', 'test_code' => 'HBSAG', 'specimen_type' => 'Serum', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
            ],

            // HCV Ab
            'Hepatitis C Antibody (HCV Ab)' => [
                ['test_name' => 'HCV Antibody', 'test_code' => 'HCVAB', 'specimen_type' => 'Serum', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
            ],

            // HIV Test
            'HIV Test' => [
                ['test_name' => 'HIV Antibody', 'test_code' => 'HIV', 'specimen_type' => 'Serum', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
            ],

            // RA Factor
            'RA Factor' => [
                ['test_name' => 'Rheumatoid Factor', 'test_code' => 'RF', 'specimen_type' => 'Serum', 'unit' => 'IU/mL', 'conversion_factor' => 1.0, 'sequence_no' => 1],
            ],

            // ANA
            'ANA (Antinuclear Antibody)' => [
                ['test_name' => 'ANA', 'test_code' => 'ANA', 'specimen_type' => 'Serum', 'unit' => 'titer', 'conversion_factor' => null, 'sequence_no' => 1],
            ],

            // Urine Analysis
            'Urine Analysis (Complete)' => [
                ['test_name' => 'Color', 'test_code' => 'COLOR', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Appearance', 'test_code' => 'APP', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 2],
                ['test_name' => 'Specific Gravity', 'test_code' => 'SG', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => 1.0, 'sequence_no' => 3],
                ['test_name' => 'pH', 'test_code' => 'PH', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => 1.0, 'sequence_no' => 4],
                ['test_name' => 'Protein', 'test_code' => 'PROT', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 5],
                ['test_name' => 'Glucose', 'test_code' => 'GLU', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 6],
                ['test_name' => 'Ketones', 'test_code' => 'KET', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 7],
                ['test_name' => 'Blood', 'test_code' => 'BLD', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 8],
                ['test_name' => 'Leukocytes', 'test_code' => 'LEU', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 9],
                ['test_name' => 'Nitrites', 'test_code' => 'NIT', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 10],
                ['test_name' => 'RBC', 'test_code' => 'RBC', 'specimen_type' => 'Urine', 'unit' => '/HPF', 'conversion_factor' => 1.0, 'sequence_no' => 11],
                ['test_name' => 'WBC', 'test_code' => 'WBC', 'specimen_type' => 'Urine', 'unit' => '/HPF', 'conversion_factor' => 1.0, 'sequence_no' => 12],
                ['test_name' => 'Epithelial Cells', 'test_code' => 'EPI', 'specimen_type' => 'Urine', 'unit' => '/HPF', 'conversion_factor' => 1.0, 'sequence_no' => 13],
                ['test_name' => 'Casts', 'test_code' => 'CAST', 'specimen_type' => 'Urine', 'unit' => '/LPF', 'conversion_factor' => 1.0, 'sequence_no' => 14],
                ['test_name' => 'Crystals', 'test_code' => 'CRYS', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 15],
            ],

            // Urine Culture
            'Urine Culture' => [
                ['test_name' => 'Organism', 'test_code' => 'ORG', 'specimen_type' => 'Urine', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Colony Count', 'test_code' => 'CFU', 'specimen_type' => 'Urine', 'unit' => 'CFU/mL', 'conversion_factor' => 1.0, 'sequence_no' => 2],
            ],

            // Stool Analysis
            'Stool Analysis' => [
                ['test_name' => 'Consistency', 'test_code' => 'CONS', 'specimen_type' => 'Stool', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Color', 'test_code' => 'COLOR', 'specimen_type' => 'Stool', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 2],
                ['test_name' => 'Occult Blood', 'test_code' => 'OB', 'specimen_type' => 'Stool', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 3],
                ['test_name' => 'Parasites', 'test_code' => 'PAR', 'specimen_type' => 'Stool', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 4],
                ['test_name' => 'WBC', 'test_code' => 'WBC', 'specimen_type' => 'Stool', 'unit' => '/HPF', 'conversion_factor' => 1.0, 'sequence_no' => 5],
                ['test_name' => 'RBC', 'test_code' => 'RBC', 'specimen_type' => 'Stool', 'unit' => '/HPF', 'conversion_factor' => 1.0, 'sequence_no' => 6],
            ],

            // Blood Culture
            'Blood Culture' => [
                ['test_name' => 'Organism', 'test_code' => 'ORG', 'specimen_type' => 'Blood', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Sensitivity', 'test_code' => 'SENS', 'specimen_type' => 'Blood', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 2],
            ],

            // Sputum Culture
            'Sputum Culture' => [
                ['test_name' => 'Organism', 'test_code' => 'ORG', 'specimen_type' => 'Sputum', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Sensitivity', 'test_code' => 'SENS', 'specimen_type' => 'Sputum', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 2],
            ],

            // Wound Culture
            'Wound Culture' => [
                ['test_name' => 'Organism', 'test_code' => 'ORG', 'specimen_type' => 'Wound Swab', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Sensitivity', 'test_code' => 'SENS', 'specimen_type' => 'Wound Swab', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 2],
            ],

            // Tissue Biopsy
            'Tissue Biopsy' => [
                ['test_name' => 'Diagnosis', 'test_code' => 'DIAG', 'specimen_type' => 'Tissue', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Histological Description', 'test_code' => 'HIST', 'specimen_type' => 'Tissue', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 2],
            ],

            // Cytology
            'Cytology' => [
                ['test_name' => 'Diagnosis', 'test_code' => 'DIAG', 'specimen_type' => 'Cytology', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
                ['test_name' => 'Cytological Description', 'test_code' => 'CYTO', 'specimen_type' => 'Cytology', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 2],
            ],

            // PCR Test
            'PCR Test' => [
                ['test_name' => 'PCR Result', 'test_code' => 'PCR', 'specimen_type' => 'Various', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
            ],

            // COVID-19 RT-PCR
            'COVID-19 RT-PCR' => [
                ['test_name' => 'COVID-19 RT-PCR', 'test_code' => 'COVID', 'specimen_type' => 'Nasopharyngeal Swab', 'unit' => null, 'conversion_factor' => null, 'sequence_no' => 1],
            ],
        ];

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($parametersData as $testName => $testParams) {
            if (!isset($tests[$testName])) {
                $this->command->warn("Test '{$testName}' not found, skipping parameters.");
                continue;
            }

            $test = $tests[$testName];
            foreach ($testParams as $paramData) {
                $existing = TestParameter::where('test_id', $test->id)
                    ->where('test_code', $paramData['test_code'])
                    ->first();

                if ($existing) {
                    $totalSkipped++;
                    continue;
                }

                TestParameter::create(array_merge($paramData, [
                    'test_id' => $test->id,
                    'created_by' => 1,
                ]));

                $totalCreated++;
            }
        }

        $this->command->info("Test Parameters Seeder completed:");
        $this->command->info("  - Parameters created: {$totalCreated}");
        $this->command->info("  - Parameters skipped (already exist): {$totalSkipped}");
    }
}


