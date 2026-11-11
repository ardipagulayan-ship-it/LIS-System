<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeltaCheckRule;
use App\Models\TestParameter;
use App\Models\DeltaCheckSeverityLevel;
use Illuminate\Support\Facades\DB;

class DeltaCheckRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default delta check rules for all test parameters.
     */
    public function run(): void
    {
        // Ensure severity levels exist first
        $this->command->info('Checking for severity levels...');
        $severityLevel = DeltaCheckSeverityLevel::where('name', 'moderate')->first();
        if (!$severityLevel) {
            $this->command->warn('Severity levels not found. Running DeltaCheckRecommendationSeeder first...');
            $this->call(DeltaCheckRecommendationSeeder::class);
            $severityLevel = DeltaCheckSeverityLevel::where('name', 'moderate')->first();
        }
        $severityLevelId = $severityLevel ? $severityLevel->id : null;

        // Get all test parameters
        $testParameters = TestParameter::all();
        
        if ($testParameters->isEmpty()) {
            $this->command->warn('No test parameters found. Please run TestParameterSeeder first.');
            return;
        }

        $this->command->info("Found {$testParameters->count()} test parameters. Creating delta check rules...");

        // Default delta check limits by test category/type
        // These are based on CLIA and CAP guidelines
        $defaultRules = $this->getDefaultRuleConfigurations();

        $rulesCreated = 0;
        $rulesSkipped = 0;
        $rulesUpdated = 0;

        foreach ($testParameters as $parameter) {
            // Determine rule configuration based on test name
            $ruleConfig = $this->findRuleConfig($parameter->test_name, $defaultRules);
            
            // Check if rule already exists for this parameter
            $existingRule = DeltaCheckRule::where('test_parameter_id', $parameter->id)
                ->whereNull('equipment_id')
                ->first();

            if ($existingRule) {
                // Update existing rule if it has different limits
                if ($existingRule->percentage_limit != $ruleConfig['percentage_limit'] || 
                    $existingRule->absolute_limit != $ruleConfig['absolute_limit']) {
                    $existingRule->update([
                        'percentage_limit' => $ruleConfig['percentage_limit'],
                        'absolute_limit' => $ruleConfig['absolute_limit'],
                        'critical_threshold' => $ruleConfig['critical_threshold'],
                        'time_window_days' => $ruleConfig['time_window_days'],
                        'comparison_type' => $ruleConfig['comparison_type'],
                        'action_on_violation' => $ruleConfig['action_on_violation'],
                        'alert_message' => $ruleConfig['alert_message'],
                        'updated_by' => 1,
                    ]);
                    $rulesUpdated++;
                } else {
                    $rulesSkipped++;
                }
                continue;
            }

            // Create rule for this parameter
            DeltaCheckRule::create([
                'rule_name' => $parameter->test_name . ' - Default Delta Check Rule',
                'test_parameter_id' => $parameter->id,
                'equipment_id' => null, // Global rule for all analyzers
                'percentage_limit' => $ruleConfig['percentage_limit'],
                'absolute_limit' => $ruleConfig['absolute_limit'],
                'critical_threshold' => $ruleConfig['critical_threshold'],
                'time_window_days' => $ruleConfig['time_window_days'],
                'comparison_type' => $ruleConfig['comparison_type'],
                'action_on_violation' => $ruleConfig['action_on_violation'],
                'alert_message' => $ruleConfig['alert_message'],
                'severity_level_id' => $severityLevelId,
                'is_active' => true,
                'created_by' => 1, // Assuming admin user ID is 1
                'updated_by' => 1,
            ]);

            $rulesCreated++;
        }

        $this->command->info("Delta Check Rules Seeder completed:");
        $this->command->info("  - Rules created: {$rulesCreated}");
        $this->command->info("  - Rules updated: {$rulesUpdated}");
        $this->command->info("  - Rules skipped (already exist): {$rulesSkipped}");
    }

    /**
     * Get default rule configurations based on test type
     */
    private function getDefaultRuleConfigurations(): array
    {
        return [
            // Hematology Parameters
            'hemoglobin' => [
                'percentage_limit' => 25.0,
                'absolute_limit' => 3.0,
                'critical_threshold' => 50.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Hemoglobin delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'hematocrit' => [
                'percentage_limit' => 25.0,
                'absolute_limit' => 8.0,
                'critical_threshold' => 50.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Hematocrit delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'wbc' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => null,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'percentage',
                'action_on_violation' => 'flag',
                'alert_message' => 'WBC delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'rbc' => [
                'percentage_limit' => 20.0,
                'absolute_limit' => 0.5,
                'critical_threshold' => 40.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'RBC delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'platelet' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 100.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Platelet delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'mcv' => [
                'percentage_limit' => 10.0,
                'absolute_limit' => 5.0,
                'critical_threshold' => 20.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'MCV delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'mch' => [
                'percentage_limit' => 10.0,
                'absolute_limit' => 2.0,
                'critical_threshold' => 20.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'MCH delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'mchc' => [
                'percentage_limit' => 5.0,
                'absolute_limit' => 2.0,
                'critical_threshold' => 10.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'MCHC delta check violation detected. Verify patient identity and sample integrity.',
            ],

            // Chemistry Parameters
            'glucose' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 50.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 7,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: Glucose delta check violation detected. Require manual verification before release.',
            ],
            'creatinine' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 0.3,
                'critical_threshold' => 50.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Creatinine delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'urea' => [
                'percentage_limit' => 40.0,
                'absolute_limit' => 10.0,
                'critical_threshold' => 80.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Urea delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'bun' => [
                'percentage_limit' => 40.0,
                'absolute_limit' => 10.0,
                'critical_threshold' => 80.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'BUN delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'total_protein' => [
                'percentage_limit' => 20.0,
                'absolute_limit' => 1.0,
                'critical_threshold' => 40.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Total Protein delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'albumin' => [
                'percentage_limit' => 25.0,
                'absolute_limit' => 0.8,
                'critical_threshold' => 50.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Albumin delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'total_bilirubin' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 2.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Total Bilirubin delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'direct_bilirubin' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 1.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Direct Bilirubin delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'alt' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 20.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'ALT delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'ast' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 20.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'AST delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'alkaline_phosphatase' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 30.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Alkaline Phosphatase delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'sodium' => [
                'percentage_limit' => 5.0,
                'absolute_limit' => 5.0,
                'critical_threshold' => 10.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: Sodium delta check violation detected. Require manual verification before release.',
            ],
            'potassium' => [
                'percentage_limit' => 20.0,
                'absolute_limit' => 0.5,
                'critical_threshold' => 40.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: Potassium delta check violation detected. Require manual verification before release.',
            ],
            'chloride' => [
                'percentage_limit' => 5.0,
                'absolute_limit' => 5.0,
                'critical_threshold' => 10.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Chloride delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'calcium' => [
                'percentage_limit' => 15.0,
                'absolute_limit' => 1.0,
                'critical_threshold' => 30.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: Calcium delta check violation detected. Require manual verification before release.',
            ],
            'phosphorus' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 1.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Phosphorus delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'magnesium' => [
                'percentage_limit' => 25.0,
                'absolute_limit' => 0.3,
                'critical_threshold' => 50.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Magnesium delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'cholesterol' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 50.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Cholesterol delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'triglycerides' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 100.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Triglycerides delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'ldl' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 30.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'LDL delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'hdl' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 15.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'HDL delta check violation detected. Verify patient identity and sample integrity.',
            ],

            // Coagulation Parameters
            'pt' => [
                'percentage_limit' => 20.0,
                'absolute_limit' => 3.0,
                'critical_threshold' => 40.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: PT delta check violation detected. Require manual verification before release.',
            ],
            'inr' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 0.5,
                'critical_threshold' => 60.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: INR delta check violation detected. Require manual verification before release.',
            ],
            'aptt' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 10.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: APTT delta check violation detected. Require manual verification before release.',
            ],
            'fibrinogen' => [
                'percentage_limit' => 40.0,
                'absolute_limit' => 100.0,
                'critical_threshold' => 80.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Fibrinogen delta check violation detected. Verify patient identity and sample integrity.',
            ],

            // Additional Chemistry Parameters
            'urea_nitrogen' => [
                'percentage_limit' => 40.0,
                'absolute_limit' => 10.0,
                'critical_threshold' => 80.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Urea Nitrogen delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'uric_acid' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 2.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Uric Acid delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'lactate_dehydrogenase' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 100.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'LDH delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'ck' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 100.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'CK delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'troponin' => [
                'percentage_limit' => 100.0,
                'absolute_limit' => 0.5,
                'critical_threshold' => 200.0,
                'time_window_days' => 7,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag_and_stop',
                'alert_message' => 'CRITICAL: Troponin delta check violation detected. Require manual verification before release.',
            ],
            'nt_probnp' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 500.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'NT-proBNP delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'c_reactive_protein' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 10.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'CRP delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'procalcitonin' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 2.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 7,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Procalcitonin delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'ferritin' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 100.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Ferritin delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'vitamin_b12' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 200.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Vitamin B12 delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'folate' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 5.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Folate delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'tsh' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 2.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'TSH delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'ft4' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 5.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Free T4 delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'ft3' => [
                'percentage_limit' => 30.0,
                'absolute_limit' => 1.0,
                'critical_threshold' => 60.0,
                'time_window_days' => 30,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'Free T3 delta check violation detected. Verify patient identity and sample integrity.',
            ],
            'psa' => [
                'percentage_limit' => 50.0,
                'absolute_limit' => 2.0,
                'critical_threshold' => 100.0,
                'time_window_days' => 90,
                'comparison_type' => 'both',
                'action_on_violation' => 'flag',
                'alert_message' => 'PSA delta check violation detected. Verify patient identity and sample integrity.',
            ],

            // Default fallback for unknown parameters
            'default' => [
                'percentage_limit' => 25.0,
                'absolute_limit' => null,
                'critical_threshold' => 50.0,
                'time_window_days' => 30,
                'comparison_type' => 'percentage',
                'action_on_violation' => 'flag',
                'alert_message' => 'Delta check violation detected. Verify patient identity and sample integrity.',
            ],
        ];
    }

    /**
     * Find rule configuration for a test parameter
     */
    private function findRuleConfig(string $testName, array $defaultRules): array
    {
        $testNameLower = strtolower(trim($testName));
        
        // Normalize common variations
        $normalized = $this->normalizeTestName($testNameLower);
        
        // Try exact match first
        if (isset($defaultRules[$normalized])) {
            return $defaultRules[$normalized];
        }
        
        if (isset($defaultRules[$testNameLower])) {
            return $defaultRules[$testNameLower];
        }

        // Try partial match with priority (longer matches first)
        $matches = [];
        foreach ($defaultRules as $key => $config) {
            if ($key === 'default') continue;
            
            if (strpos($testNameLower, $key) !== false || strpos($key, $testNameLower) !== false) {
                $matches[$key] = strlen($key);
            }
        }
        
        if (!empty($matches)) {
            // Return the longest match
            arsort($matches);
            $bestMatch = array_key_first($matches);
            return $defaultRules[$bestMatch];
        }

        // Return default
        return $defaultRules['default'];
    }

    /**
     * Normalize test name for matching
     */
    private function normalizeTestName(string $name): string
    {
        // Common normalizations
        $normalizations = [
            'white blood cell' => 'wbc',
            'white blood cell count' => 'wbc',
            'red blood cell' => 'rbc',
            'red blood cell count' => 'rbc',
            'blood glucose' => 'glucose',
            'fasting glucose' => 'glucose',
            'random glucose' => 'glucose',
            'blood urea nitrogen' => 'bun',
            'urea nitrogen' => 'bun',
            'prothrombin time' => 'pt',
            'partial thromboplastin time' => 'aptt',
            'activated partial thromboplastin time' => 'aptt',
            'mean corpuscular volume' => 'mcv',
            'mean corpuscular hemoglobin' => 'mch',
            'mean corpuscular hemoglobin concentration' => 'mchc',
            'alanine aminotransferase' => 'alt',
            'aspartate aminotransferase' => 'ast',
            'alkaline phosphatase' => 'alkaline_phosphatase',
            'total cholesterol' => 'cholesterol',
            'low density lipoprotein' => 'ldl',
            'high density lipoprotein' => 'hdl',
            'creatine kinase' => 'ck',
            'creatine phosphokinase' => 'ck',
            'troponin i' => 'troponin',
            'troponin t' => 'troponin',
            'c-reactive protein' => 'c_reactive_protein',
            'free t4' => 'ft4',
            'free t3' => 'ft3',
            'thyroid stimulating hormone' => 'tsh',
            'prostate specific antigen' => 'psa',
        ];

        foreach ($normalizations as $pattern => $normalized) {
            if (strpos($name, $pattern) !== false) {
                return $normalized;
            }
        }

        // Remove common prefixes/suffixes
        $name = preg_replace('/\b(count|level|concentration|value|result)\b/i', '', $name);
        $name = preg_replace('/\s+/', '_', trim($name));
        
        return $name;
    }
}

