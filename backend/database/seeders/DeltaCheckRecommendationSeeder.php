<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeltaCheckParameter;
use App\Models\DeltaCheckSeverityLevel;
use App\Models\DeltaCheckAction;
use App\Models\DeltaCheckClinicalSignificance;
use App\Models\DeltaCheckPreventiveMeasure;
use App\Models\DeltaCheckQualityControl;
use App\Models\DeltaCheckGlobalRecommendation;

class DeltaCheckRecommendationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSeverityLevels();
        $this->seedActions();
        $this->seedParameters();
        $this->seedClinicalSignificance();
        $this->seedPreventiveMeasures();
        $this->seedQualityControl();
        $this->seedGlobalRecommendations();
    }

    private function seedSeverityLevels()
    {
        $severityLevels = [
            [
                'name' => 'critical',
                'display_name' => 'Critical',
                'color' => '#f56c6c',
                'priority_order' => 1,
                'description' => 'Immediate action required',
            ],
            [
                'name' => 'high',
                'display_name' => 'High Priority',
                'color' => '#e6a23c',
                'priority_order' => 2,
                'description' => 'Priority verification recommended',
            ],
            [
                'name' => 'moderate',
                'display_name' => 'Moderate',
                'color' => '#409eff',
                'priority_order' => 3,
                'description' => 'Standard review recommended',
            ],
            [
                'name' => 'low',
                'display_name' => 'Low',
                'color' => '#67c23a',
                'priority_order' => 4,
                'description' => 'Monitor trend',
            ],
        ];

        foreach ($severityLevels as $level) {
            DeltaCheckSeverityLevel::firstOrCreate(
                ['name' => $level['name']],
                $level
            );
        }
    }

    private function seedActions()
    {
        // Look up severity level IDs by name
        $severityLevels = [
            'critical' => DeltaCheckSeverityLevel::where('name', 'critical')->first(),
            'high' => DeltaCheckSeverityLevel::where('name', 'high')->first(),
            'moderate' => DeltaCheckSeverityLevel::where('name', 'moderate')->first(),
            'low' => DeltaCheckSeverityLevel::where('name', 'low')->first(),
        ];
        
        $actions = [];
        
        if ($critical = $severityLevels['critical']) {
            $actions[] = [
                'severity_level_id' => $critical->id,
                'action_type' => 'immediate',
                'title' => 'CRITICAL: Immediate verification required',
                'description' => 'Critical delta check violation detected',
                'steps' => [
                    'Repeat test with fresh sample',
                    'Verify patient identity',
                    'Check for pre-analytical errors',
                    'Notify physician immediately',
                    'Document all actions taken'
                ],
                'order' => 1,
            ];
            $actions[] = [
                'severity_level_id' => $critical->id,
                'action_type' => 'short_term',
                'title' => 'Critical Follow-up',
                'description' => 'Immediate follow-up actions',
                'steps' => [
                    'Monitor patient closely',
                    'Consider additional testing',
                    'Review with clinical team',
                    'Update quality management system'
                ],
                'order' => 1,
            ];
        }
        
        if ($high = $severityLevels['high']) {
            $actions[] = [
                'severity_level_id' => $high->id,
                'action_type' => 'immediate',
                'title' => 'HIGH PRIORITY: Verification recommended',
                'description' => 'High priority delta check violation',
                'steps' => [
                    'Repeat test within 2 hours',
                    'Verify sample integrity',
                    'Check instrument calibration',
                    'Review recent QC results',
                    'Consider clinical correlation'
                ],
                'order' => 1,
            ];
            $actions[] = [
                'severity_level_id' => $high->id,
                'action_type' => 'short_term',
                'title' => 'High Priority Follow-up',
                'description' => 'Short-term follow-up actions',
                'steps' => [
                    'Monitor trend over next 24-48 hours',
                    'Consider additional testing if clinically indicated',
                    'Review with clinical team',
                    'Document in quality management system'
                ],
                'order' => 1,
            ];
        }
        
        if ($moderate = $severityLevels['moderate']) {
            $actions[] = [
                'severity_level_id' => $moderate->id,
                'action_type' => 'immediate',
                'title' => 'MODERATE: Review recommended',
                'description' => 'Moderate delta check violation',
                'steps' => [
                    'Verify test result',
                    'Check instrument status',
                    'Review patient history',
                    'Consider repeat if clinically indicated'
                ],
                'order' => 1,
            ];
            $actions[] = [
                'severity_level_id' => $moderate->id,
                'action_type' => 'short_term',
                'title' => 'Moderate Follow-up',
                'description' => 'Short-term monitoring',
                'steps' => [
                    'Monitor trend',
                    'Review at next scheduled test',
                    'Document findings'
                ],
                'order' => 1,
            ];
        }
        
        if ($low = $severityLevels['low']) {
            $actions[] = [
                'severity_level_id' => $low->id,
                'action_type' => 'immediate',
                'title' => 'LOW: Monitor trend',
                'description' => 'Low priority delta check observation',
                'steps' => [
                    'Document observation',
                    'Continue routine monitoring',
                    'Review at next scheduled test'
                ],
                'order' => 1,
            ];
        }

        foreach ($actions as $action) {
            DeltaCheckAction::firstOrCreate(
                [
                    'severity_level_id' => $action['severity_level_id'],
                    'action_type' => $action['action_type'],
                    'title' => $action['title']
                ],
                $action
            );
        }
    }

    private function seedParameters()
    {
        $parameters = [
            // Clinical Chemistry
            [
                'parameter_name' => 'glucose',
                'display_name' => 'Blood Glucose',
                'category' => 'chemistry',
                'unit' => 'mg/dL',
                'specimen_type' => 'Serum/Plasma',
                'percentage_limit' => 10.0,
                'absolute_limit' => null,
                'critical_threshold' => 25.0,
                'description' => 'Blood glucose measurement for diabetes monitoring',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
            [
                'parameter_name' => 'hemoglobin',
                'display_name' => 'Hemoglobin',
                'category' => 'hematology',
                'unit' => 'g/dL',
                'specimen_type' => 'Whole Blood',
                'percentage_limit' => 7.0,
                'absolute_limit' => 1.0,
                'critical_threshold' => 15.0,
                'description' => 'Hemoglobin concentration in blood',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
            [
                'parameter_name' => 'hematocrit',
                'display_name' => 'Hematocrit',
                'category' => 'hematology',
                'unit' => '%',
                'specimen_type' => 'Whole Blood',
                'percentage_limit' => 6.0,
                'absolute_limit' => 3.0,
                'critical_threshold' => 12.0,
                'description' => 'Percentage of blood volume occupied by red blood cells',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
            [
                'parameter_name' => 'sodium',
                'display_name' => 'Sodium',
                'category' => 'chemistry',
                'unit' => 'mEq/L',
                'specimen_type' => 'Serum',
                'percentage_limit' => 5.0,
                'absolute_limit' => 4.0,
                'critical_threshold' => 10.0,
                'description' => 'Serum sodium concentration',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
            [
                'parameter_name' => 'potassium',
                'display_name' => 'Potassium',
                'category' => 'chemistry',
                'unit' => 'mEq/L',
                'specimen_type' => 'Serum',
                'percentage_limit' => 5.0,
                'absolute_limit' => 0.5,
                'critical_threshold' => 15.0,
                'description' => 'Serum potassium concentration',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
            [
                'parameter_name' => 'creatinine',
                'display_name' => 'Creatinine',
                'category' => 'chemistry',
                'unit' => 'mg/dL',
                'specimen_type' => 'Serum',
                'percentage_limit' => 15.0,
                'absolute_limit' => 0.3,
                'critical_threshold' => 30.0,
                'description' => 'Serum creatinine for kidney function assessment',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
            [
                'parameter_name' => 'troponin',
                'display_name' => 'Troponin I',
                'category' => 'cardiac',
                'unit' => 'ng/mL',
                'specimen_type' => 'Serum',
                'percentage_limit' => 20.0,
                'absolute_limit' => 0.1,
                'critical_threshold' => 50.0,
                'description' => 'Cardiac troponin for myocardial injury detection',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
            [
                'parameter_name' => 'psa',
                'display_name' => 'Prostate Specific Antigen',
                'category' => 'tumor_marker',
                'unit' => 'ng/mL',
                'specimen_type' => 'Serum',
                'percentage_limit' => 20.0,
                'absolute_limit' => 1.0,
                'critical_threshold' => 40.0,
                'description' => 'PSA for prostate cancer screening and monitoring',
                'standards_references' => ['CLIA', 'CAP', 'ISO 15189'],
            ],
        ];

        foreach ($parameters as $parameter) {
            DeltaCheckParameter::firstOrCreate(
                ['parameter_name' => $parameter['parameter_name']],
                $parameter
            );
        }
    }

    private function seedClinicalSignificance()
    {
        // Look up actual parameter IDs by name
        $parameters = [
            'glucose' => DeltaCheckParameter::where('parameter_name', 'glucose')->first(),
            'hemoglobin' => DeltaCheckParameter::where('parameter_name', 'hemoglobin')->first(),
            'hematocrit' => DeltaCheckParameter::where('parameter_name', 'hematocrit')->first(),
            'sodium' => DeltaCheckParameter::where('parameter_name', 'sodium')->first(),
            'potassium' => DeltaCheckParameter::where('parameter_name', 'potassium')->first(),
            'creatinine' => DeltaCheckParameter::where('parameter_name', 'creatinine')->first(),
            'troponin' => DeltaCheckParameter::where('parameter_name', 'troponin')->first(),
            'psa' => DeltaCheckParameter::where('parameter_name', 'psa')->first(),
        ];
        
        $clinicalSignificance = [];
        
        if ($glucose = $parameters['glucose']) {
            $clinicalSignificance[] = [
                'parameter_id' => $glucose->id,
                'significance_text' => 'Significant changes may indicate diabetes control, stress response, or medication effects',
                'risk_factors' => ['Diabetes', 'Medication changes', 'Stress', 'Infection'],
                'clinical_conditions' => ['Diabetes mellitus', 'Hypoglycemia', 'Hyperglycemia'],
            ];
        }
        
        if ($hemoglobin = $parameters['hemoglobin']) {
            $clinicalSignificance[] = [
                'parameter_id' => $hemoglobin->id,
                'significance_text' => 'Significant changes may indicate bleeding, hemolysis, or treatment response',
                'risk_factors' => ['Bleeding', 'Hemolysis', 'Iron deficiency', 'Chronic disease'],
                'clinical_conditions' => ['Anemia', 'Polycythemia', 'Hemorrhage'],
            ];
        }
        
        if ($hematocrit = $parameters['hematocrit']) {
            $clinicalSignificance[] = [
                'parameter_id' => $hematocrit->id,
                'significance_text' => 'Changes may reflect fluid status, blood loss, or hematologic disorders',
                'risk_factors' => ['Dehydration', 'Blood loss', 'Polycythemia', 'Anemia'],
                'clinical_conditions' => ['Anemia', 'Polycythemia', 'Dehydration'],
            ];
        }
        
        if ($sodium = $parameters['sodium']) {
            $clinicalSignificance[] = [
                'parameter_id' => $sodium->id,
                'significance_text' => 'Variations may indicate fluid balance, kidney function, or endocrine disorders',
                'risk_factors' => ['Dehydration', 'Heart failure', 'Kidney disease', 'SIADH'],
                'clinical_conditions' => ['Hyponatremia', 'Hypernatremia', 'SIADH'],
            ];
        }
        
        if ($potassium = $parameters['potassium']) {
            $clinicalSignificance[] = [
                'parameter_id' => $potassium->id,
                'significance_text' => 'Changes may reflect kidney function, medication effects, or acid-base status',
                'risk_factors' => ['Kidney disease', 'Medications', 'Acidosis', 'Alkalosis'],
                'clinical_conditions' => ['Hyperkalemia', 'Hypokalemia', 'Kidney disease'],
            ];
        }
        
        if ($creatinine = $parameters['creatinine']) {
            $clinicalSignificance[] = [
                'parameter_id' => $creatinine->id,
                'significance_text' => 'Changes may reflect kidney function, hydration status, or medication effects',
                'risk_factors' => ['Kidney disease', 'Dehydration', 'Medications', 'Muscle mass changes'],
                'clinical_conditions' => ['Acute kidney injury', 'Chronic kidney disease'],
            ];
        }
        
        if ($troponin = $parameters['troponin']) {
            $clinicalSignificance[] = [
                'parameter_id' => $troponin->id,
                'significance_text' => 'Significant changes may indicate myocardial injury or infarction',
                'risk_factors' => ['Chest pain', 'Cardiac procedures', 'Heart failure', 'Kidney disease'],
                'clinical_conditions' => ['Myocardial infarction', 'Unstable angina', 'Heart failure'],
            ];
        }
        
        if ($psa = $parameters['psa']) {
            $clinicalSignificance[] = [
                'parameter_id' => $psa->id,
                'significance_text' => 'Variations may indicate prostate disease progression or treatment response',
                'risk_factors' => ['Prostate procedures', 'Prostatitis', 'Age', 'Family history'],
                'clinical_conditions' => ['Prostate cancer', 'Benign prostatic hyperplasia', 'Prostatitis'],
            ];
        }

        foreach ($clinicalSignificance as $significance) {
            DeltaCheckClinicalSignificance::firstOrCreate(
                ['parameter_id' => $significance['parameter_id']],
                $significance
            );
        }
    }

    private function seedPreventiveMeasures()
    {
        // Look up actual parameter IDs by name
        $glucoseParam = DeltaCheckParameter::where('parameter_name', 'glucose')->first();
        $hemoglobinParam = DeltaCheckParameter::where('parameter_name', 'hemoglobin')->first();
        
        $preventiveMeasures = [];
        
        if ($glucoseParam) {
            $preventiveMeasures[] = [
                'parameter_id' => $glucoseParam->id,
                'measure_type' => 'pre_analytical',
                'title' => 'Pre-analytical Measures for Glucose',
                'description' => 'Measures to prevent pre-analytical errors in glucose testing',
                'specific_measures' => [
                    'Verify fasting status',
                    'Check sample collection time',
                    'Avoid hemolysis',
                    'Ensure proper sample handling'
                ],
                'order' => 1,
            ];
            $preventiveMeasures[] = [
                'parameter_id' => $glucoseParam->id,
                'measure_type' => 'analytical',
                'title' => 'Analytical Measures for Glucose',
                'description' => 'Measures to ensure accurate glucose measurement',
                'specific_measures' => [
                    'Check glucose meter calibration',
                    'Verify reagent stability',
                    'Monitor temperature',
                    'Check for interference'
                ],
                'order' => 1,
            ];
        }
        
        if ($hemoglobinParam) {
            $preventiveMeasures[] = [
                'parameter_id' => $hemoglobinParam->id,
                'measure_type' => 'pre_analytical',
                'title' => 'Pre-analytical Measures for Hemoglobin',
                'description' => 'Measures to prevent pre-analytical errors in hemoglobin testing',
                'specific_measures' => [
                    'Check for hemolysis',
                    'Verify sample mixing',
                    'Check anticoagulant',
                    'Ensure proper collection'
                ],
                'order' => 1,
            ];
            $preventiveMeasures[] = [
                'parameter_id' => $hemoglobinParam->id,
                'measure_type' => 'analytical',
                'title' => 'Analytical Measures for Hemoglobin',
                'description' => 'Measures to ensure accurate hemoglobin measurement',
                'specific_measures' => [
                    'Verify hematology analyzer',
                    'Check for carryover',
                    'Monitor QC results',
                    'Verify calibration'
                ],
                'order' => 1,
            ];
        }

        foreach ($preventiveMeasures as $measure) {
            DeltaCheckPreventiveMeasure::firstOrCreate(
                [
                    'parameter_id' => $measure['parameter_id'],
                    'measure_type' => $measure['measure_type'],
                    'title' => $measure['title']
                ],
                $measure
            );
        }
    }

    private function seedQualityControl()
    {
        // Look up actual parameter IDs by name
        $glucoseParam = DeltaCheckParameter::where('parameter_name', 'glucose')->first();
        $hemoglobinParam = DeltaCheckParameter::where('parameter_name', 'hemoglobin')->first();
        
        $qualityControl = [];
        
        if ($glucoseParam) {
            $qualityControl[] = [
                'parameter_id' => $glucoseParam->id,
                'qc_type' => 'instrument_check',
                'title' => 'Glucose Instrument Check',
                'description' => 'Quality control checks for glucose instruments',
                'qc_steps' => [
                    'Verify instrument calibration',
                    'Check maintenance logs',
                    'Review recent QC results',
                    'Confirm method performance'
                ],
                'order' => 1,
            ];
        }
        
        if ($hemoglobinParam) {
            $qualityControl[] = [
                'parameter_id' => $hemoglobinParam->id,
                'qc_type' => 'instrument_check',
                'title' => 'Hemoglobin Instrument Check',
                'description' => 'Quality control checks for hemoglobin instruments',
                'qc_steps' => [
                    'Verify hematology analyzer',
                    'Check for carryover',
                    'Review QC results',
                    'Confirm calibration'
                ],
                'order' => 1,
            ];
        }

        foreach ($qualityControl as $qc) {
            DeltaCheckQualityControl::firstOrCreate(
                [
                    'parameter_id' => $qc['parameter_id'],
                    'qc_type' => $qc['qc_type'],
                    'title' => $qc['title']
                ],
                $qc
            );
        }
    }

    private function seedGlobalRecommendations()
    {
        $globalRecommendations = [
            [
                'recommendation_type' => 'overall_violation_rate',
                'condition' => 'violation_rate > 20',
                'severity' => 'critical',
                'title' => 'High Overall Violation Rate',
                'message' => 'High overall violation rate detected',
                'action' => 'Immediate review of delta check limits and procedures required',
                'thresholds' => ['violation_rate' => 20],
            ],
            [
                'recommendation_type' => 'overall_violation_rate',
                'condition' => 'violation_rate > 10',
                'severity' => 'warning',
                'title' => 'Elevated Violation Rate',
                'message' => 'Elevated violation rate detected',
                'action' => 'Review delta check limits and quality control procedures',
                'thresholds' => ['violation_rate' => 10],
            ],
            [
                'recommendation_type' => 'parameter_specific',
                'condition' => 'parameter_violation_rate > 30',
                'severity' => 'warning',
                'title' => 'High Parameter Violation Rate',
                'message' => 'High violation rate for specific parameter',
                'action' => 'Review delta check limits for this parameter',
                'thresholds' => ['parameter_violation_rate' => 30],
            ],
        ];

        foreach ($globalRecommendations as $recommendation) {
            DeltaCheckGlobalRecommendation::firstOrCreate(
                [
                    'recommendation_type' => $recommendation['recommendation_type'],
                    'condition' => $recommendation['condition']
                ],
                $recommendation
            );
        }
    }
}