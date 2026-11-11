<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CdsCorrelationRule;
use App\Models\TestParameter;

class CdsCorrelationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding CDS correlation rules...');

        // Lookup helper by test_code
        $byCode = fn (string $code): ?int => optional(TestParameter::where('test_code', $code)->first())->id;

        $ids = [
            'GLU' => $byCode('GLU') ?? $byCode('FBS') ?? null,
            'HBA1C' => $byCode('HBA1C'),
            'K' => $byCode('K'),
            'NA' => $byCode('NA'),
            'CL' => $byCode('CL'),
            'ALT' => $byCode('ALT'),
            'AST' => $byCode('AST'),
            'HGB' => $byCode('HGB'),
            'MCV' => $byCode('MCV'),
            'FERR' => $byCode('FERR'),
            'TRIG' => $byCode('TRIG'),
            'LDL' => $byCode('LDL'),
            'CHOL' => $byCode('CHOL'),
            'CREA' => $byCode('CREA'),
            'UA' => $byCode('UA'),
            'CRP' => $byCode('CRP'),
            'PCT' => $byCode('PCT'),
        ];

        // 1) Hyperglycemia or confirmed diabetes correlation (GLU vs HbA1c)
        if ($ids['GLU'] && $ids['HBA1C']) {
            $this->upsertRule([
                'rule_name' => 'Diabetes correlation: GLU and HbA1c',
                'trigger_test_id' => $ids['GLU'],
                'rule_logic_json' => [
                    'op' => 'OR',
                    'conditions' => [
                        ['parameter_id' => $ids['GLU'], 'operator' => '>=', 'value' => 11.1], // mmol/L random glucose diagnostic threshold
                        ['parameter_id' => $ids['HBA1C'], 'operator' => '>=', 'value' => 6.5], // % HbA1c diagnostic threshold
                    ],
                ],
                'correlated_tests' => implode(',', [$ids['GLU'], $ids['HBA1C']]),
                'alert_message' => 'Hyperglycemia or elevated HbA1c. Consider diabetes workup/confirmation.',
                'action_type' => 'ADD_COMMENT',
                'reflex_test_id' => null,
                'is_active' => true,
            ]);
        }

        // 2) Critical potassium: stop auto-verify
        if ($ids['K']) {
            $this->upsertRule([
                'rule_name' => 'Critical Potassium Stop Auto-Verify',
                'trigger_test_id' => $ids['K'],
                'rule_logic_json' => [
                    'op' => 'OR',
                    'conditions' => [
                        ['parameter_id' => $ids['K'], 'operator' => '<', 'value' => 2.5],
                        ['parameter_id' => $ids['K'], 'operator' => '>', 'value' => 6.5],
                    ],
                ],
                'correlated_tests' => (string) $ids['K'],
                'alert_message' => 'Critical K+. Verify sample and patient status immediately.',
                'action_type' => 'STOP_AUTO_VERIFY',
                'reflex_test_id' => null,
                'is_active' => true,
            ]);
        }

        // 3) Transaminases correlation (ALT & AST both high)
        if ($ids['ALT'] && $ids['AST']) {
            $this->upsertRule([
                'rule_name' => 'Transaminases both elevated',
                'trigger_test_id' => $ids['ALT'],
                'rule_logic_json' => [
                    'op' => 'AND',
                    'conditions' => [
                        ['parameter_id' => $ids['ALT'], 'operator' => '>', 'value' => 41],
                        ['parameter_id' => $ids['AST'], 'operator' => '>', 'value' => 40],
                    ],
                ],
                'correlated_tests' => implode(',', [$ids['ALT'], $ids['AST']]),
                'alert_message' => 'ALT and AST elevated. Consider hepatic injury workup.',
                'action_type' => 'ADD_COMMENT',
                'reflex_test_id' => null,
                'is_active' => true,
            ]);
        }

        // 4) Microcytic anemia reflex Ferritin: HGB low AND MCV low
        if ($ids['HGB'] && $ids['MCV']) {
            $this->upsertRule([
                'rule_name' => 'Microcytic anemia reflex Ferritin',
                'trigger_test_id' => $ids['HGB'],
                'rule_logic_json' => [
                    'op' => 'AND',
                    'conditions' => [
                        ['parameter_id' => $ids['HGB'], 'operator' => '<', 'value' => 120],
                        ['parameter_id' => $ids['MCV'], 'operator' => '<', 'value' => 80],
                    ],
                ],
                'correlated_tests' => implode(',', array_filter([$ids['HGB'], $ids['MCV'], $ids['FERR']])) ,
                'alert_message' => 'Low HGB with low MCV. Reflex Ferritin suggested.',
                'action_type' => $ids['FERR'] ? 'REFLEX_TEST' : 'ADD_COMMENT',
                'reflex_test_id' => $ids['FERR'] ?? null,
                'is_active' => true,
            ]);
        }

        // 5) Triglycerides very high -> LDL calculation unreliable
        if ($ids['TRIG'] && $ids['LDL']) {
            $this->upsertRule([
                'rule_name' => 'High TG note: LDL calculation unreliable',
                'trigger_test_id' => $ids['TRIG'],
                'rule_logic_json' => [
                    'op' => '>',
                    'parameter_id' => $ids['TRIG'],
                    'operator' => '>',
                    'value' => 4.5,
                ],
                'correlated_tests' => implode(',', [$ids['TRIG'], $ids['LDL'], $ids['CHOL'] ?? null]),
                'alert_message' => 'Triglycerides > 4.5 mmol/L — calculated LDL may be inaccurate.',
                'action_type' => 'ADD_COMMENT',
                'reflex_test_id' => null,
                'is_active' => true,
            ]);
        }

        // 6) Sepsis markers: CRP very high OR PCT high -> stop autoverify (flag)
        if ($ids['CRP'] || $ids['PCT']) {
            $conds = [];
            if ($ids['CRP']) $conds[] = ['parameter_id' => $ids['CRP'], 'operator' => '>', 'value' => 100];
            if ($ids['PCT']) $conds[] = ['parameter_id' => $ids['PCT'], 'operator' => '>', 'value' => 2.0];
            if (!empty($conds)) {
                $this->upsertRule([
                    'rule_name' => 'Sepsis markers high — manual review',
                    'trigger_test_id' => $ids['CRP'] ?? $ids['PCT'],
                    'rule_logic_json' => [ 'op' => 'OR', 'conditions' => $conds ],
                    'correlated_tests' => implode(',', array_filter([$ids['CRP'], $ids['PCT']])),
                    'alert_message' => 'CRP/PCT markedly elevated. Manual review required.',
                    'action_type' => 'STOP_AUTO_VERIFY',
                    'reflex_test_id' => null,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('CDS correlation rules seeding complete.');
    }

    private function upsertRule(array $data): void
    {
        CdsCorrelationRule::updateOrCreate(
            ['rule_name' => $data['rule_name']],
            $data
        );
        $this->command->line('  ✓ ' . $data['rule_name']);
    }
}
