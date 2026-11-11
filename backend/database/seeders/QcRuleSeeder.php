<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QcRule;
use App\Models\TestParameter;
use Illuminate\Support\Facades\DB;

class QcRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default QC rules for all Westgard rules.
     */
    public function run(): void
    {
        $this->command->info('Creating QC Rules...');

        // Create global QC rules for all Westgard rule types
        $globalCreated = $this->createGlobalQcRules();

        // Create parameter-specific QC rules (optional - can be expanded)
        $paramCreated = $this->createParameterSpecificQcRules();

        $this->command->info("QC Rules Seeder completed successfully:");
        $this->command->info("  - Global rules created: {$globalCreated}");
        $this->command->info("  - Parameter-specific rules created: {$paramCreated}");
    }

    /**
     * Create global QC rules for all Westgard rule types
     * @return int Number of rules created
     */
    private function createGlobalQcRules(): int
    {
        $westgardRules = [
            [
                'rule_name' => 'Global 1-2s Warning Rule',
                'westgard_rule_type' => '1-2s',
                'is_enabled' => true,
                'action_on_violation' => 'warning',
                'severity_level' => 'warning',
                'alert_message' => 'Warning: 1-2s rule violation detected. One control value exceeds ±2SD. Monitor next control.',
                'custom_threshold_multiplier' => null,
                'description' => 'Warning rule - first check for systematic errors',
            ],
            [
                'rule_name' => 'Global 1-3s Reject Rule',
                'westgard_rule_type' => '1-3s',
                'is_enabled' => true,
                'action_on_violation' => 'reject',
                'severity_level' => 'error',
                'alert_message' => 'REJECT: 1-3s rule violation detected. One control value exceeds ±3SD. Stop patient testing.',
                'custom_threshold_multiplier' => null,
                'description' => 'Reject rule - random error detected',
            ],
            [
                'rule_name' => 'Global 2-2s Reject Rule',
                'westgard_rule_type' => '2-2s',
                'is_enabled' => true,
                'action_on_violation' => 'reject',
                'severity_level' => 'error',
                'alert_message' => 'REJECT: 2-2s rule violation detected. Two consecutive controls exceed ±2SD on same side. Systematic error suspected.',
                'custom_threshold_multiplier' => null,
                'description' => 'Reject rule - systematic error detected',
            ],
            [
                'rule_name' => 'Global R-4s Reject Rule',
                'westgard_rule_type' => 'R-4s',
                'is_enabled' => true,
                'action_on_violation' => 'reject',
                'severity_level' => 'error',
                'alert_message' => 'REJECT: R-4s rule violation detected. Range between two consecutive controls exceeds 4SD. Random error suspected.',
                'custom_threshold_multiplier' => null,
                'description' => 'Reject rule - random error detected',
            ],
            [
                'rule_name' => 'Global 4-1s Reject Rule',
                'westgard_rule_type' => '4-1s',
                'is_enabled' => true,
                'action_on_violation' => 'reject',
                'severity_level' => 'error',
                'alert_message' => 'REJECT: 4-1s rule violation detected. Four consecutive controls exceed ±1SD on same side. Systematic error suspected.',
                'custom_threshold_multiplier' => null,
                'description' => 'Reject rule - systematic error detected',
            ],
            [
                'rule_name' => 'Global 10x Reject Rule',
                'westgard_rule_type' => '10x',
                'is_enabled' => true,
                'action_on_violation' => 'reject',
                'severity_level' => 'error',
                'alert_message' => 'REJECT: 10x rule violation detected. Ten consecutive controls on same side of mean. Systematic error detected.',
                'custom_threshold_multiplier' => null,
                'description' => 'Reject rule - systematic error detected',
            ],
        ];

        $created = 0;
        foreach ($westgardRules as $ruleData) {
            // Check if global rule already exists
            $existingRule = QcRule::where('test_parameter_id', null)
                ->where('equipment_id', null)
                ->where('quality_control_test_parameter_id', null)
                ->where('westgard_rule_type', $ruleData['westgard_rule_type'])
                ->first();

            if (!$existingRule) {
                QcRule::create([
                    'rule_name' => $ruleData['rule_name'],
                    'test_parameter_id' => null,
                    'equipment_id' => null,
                    'quality_control_test_parameter_id' => null,
                    'westgard_rule_type' => $ruleData['westgard_rule_type'],
                    'is_enabled' => $ruleData['is_enabled'],
                    'action_on_violation' => $ruleData['action_on_violation'],
                    'severity_level' => $ruleData['severity_level'],
                    'alert_message' => $ruleData['alert_message'],
                    'custom_threshold_multiplier' => $ruleData['custom_threshold_multiplier'],
                    'is_active' => true,
                    'created_by' => 1, // Assuming admin user ID is 1
                    'updated_by' => 1,
                ]);

                $this->command->info("  ✓ Created global QC rule: {$ruleData['rule_name']}");
                $created++;
            } else {
                $this->command->line("  - Global QC rule already exists: {$ruleData['rule_name']}");
            }
        }
        return $created;
    }

    /**
     * Create parameter-specific QC rules for critical tests
     * This creates additional rules for specific parameters that may need stricter controls
     * @return int Number of rules created
     */
    private function createParameterSpecificQcRules(): int
    {
        // Get critical test parameters that might need stricter QC rules
        $criticalParameters = TestParameter::whereIn('test_name', [
            'Glucose', 'Sodium', 'Potassium', 'Calcium', 'PT', 'INR', 'APTT'
        ])->get();

        if ($criticalParameters->isEmpty()) {
            $this->command->line('No critical parameters found for parameter-specific QC rules.');
            return 0;
        }

        // Create stricter rules for critical parameters
        $criticalRules = [
            [
                'westgard_rule_type' => '1-3s',
                'is_enabled' => true,
                'action_on_violation' => 'reject',
                'severity_level' => 'critical',
                'alert_message' => 'CRITICAL: 1-3s rule violation for critical parameter. Immediate action required.',
                'custom_threshold_multiplier' => null,
            ],
            [
                'westgard_rule_type' => '2-2s',
                'is_enabled' => true,
                'action_on_violation' => 'reject',
                'severity_level' => 'critical',
                'alert_message' => 'CRITICAL: 2-2s rule violation for critical parameter. Systematic error suspected.',
                'custom_threshold_multiplier' => null,
            ],
        ];

        $created = 0;
        foreach ($criticalParameters as $parameter) {
            foreach ($criticalRules as $ruleData) {
                // Check if parameter-specific rule already exists
                $existingRule = QcRule::where('test_parameter_id', $parameter->id)
                    ->whereNull('equipment_id')
                    ->whereNull('quality_control_test_parameter_id')
                    ->where('westgard_rule_type', $ruleData['westgard_rule_type'])
                    ->first();

                if (!$existingRule) {
                    QcRule::create([
                        'rule_name' => $parameter->test_name . ' - ' . $ruleData['westgard_rule_type'] . ' Critical Rule',
                        'test_parameter_id' => $parameter->id,
                        'equipment_id' => null,
                        'quality_control_test_parameter_id' => null,
                        'westgard_rule_type' => $ruleData['westgard_rule_type'],
                        'is_enabled' => $ruleData['is_enabled'],
                        'action_on_violation' => $ruleData['action_on_violation'],
                        'severity_level' => $ruleData['severity_level'],
                        'alert_message' => $ruleData['alert_message'],
                        'custom_threshold_multiplier' => $ruleData['custom_threshold_multiplier'],
                        'is_active' => true,
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]);

                    $this->command->info("  ✓ Created parameter-specific QC rule: {$parameter->test_name} - {$ruleData['westgard_rule_type']}");
                    $created++;
                }
            }
        }
        return $created;
    }
}

