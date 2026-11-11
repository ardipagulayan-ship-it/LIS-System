<?php

namespace App\Repositories;

class DeltaCheckRecommendationRepository
{
    /**
     * Global delta check recommendations based on international standards
     * Following CLIA, CAP, ISO 15189, and Westgard rules
     */
    
    // CLIA (Clinical Laboratory Improvement Amendments) standards
    const CLIA_STANDARDS = [
        'glucose' => ['percentage' => 10, 'absolute' => null, 'critical' => 25],
        'cholesterol' => ['percentage' => 10, 'absolute' => null, 'critical' => 20],
        'hemoglobin' => ['percentage' => 7, 'absolute' => 1.0, 'critical' => 15],
        'hematocrit' => ['percentage' => 6, 'absolute' => 3.0, 'critical' => 12],
        'sodium' => ['percentage' => 5, 'absolute' => 4.0, 'critical' => 10],
        'potassium' => ['percentage' => 5, 'absolute' => 0.5, 'critical' => 15],
        'chloride' => ['percentage' => 5, 'absolute' => 4.0, 'critical' => 10],
        'creatinine' => ['percentage' => 15, 'absolute' => 0.3, 'critical' => 30],
        'bun' => ['percentage' => 20, 'absolute' => 4.0, 'critical' => 40],
        'calcium' => ['percentage' => 5, 'absolute' => 0.5, 'critical' => 10],
        'phosphorus' => ['percentage' => 10, 'absolute' => 0.5, 'critical' => 20],
        'magnesium' => ['percentage' => 15, 'absolute' => 0.3, 'critical' => 25],
        'albumin' => ['percentage' => 10, 'absolute' => 0.3, 'critical' => 20],
        'total_protein' => ['percentage' => 10, 'absolute' => 0.5, 'critical' => 20],
        'bilirubin_total' => ['percentage' => 20, 'absolute' => 0.5, 'critical' => 40],
        'bilirubin_direct' => ['percentage' => 25, 'absolute' => 0.2, 'critical' => 50],
        'alt' => ['percentage' => 20, 'absolute' => 10, 'critical' => 40],
        'ast' => ['percentage' => 20, 'absolute' => 10, 'critical' => 40],
        'alkaline_phosphatase' => ['percentage' => 15, 'absolute' => 15, 'critical' => 30],
        'ldh' => ['percentage' => 15, 'absolute' => 20, 'critical' => 30],
        'ck' => ['percentage' => 20, 'absolute' => 20, 'critical' => 40],
        'troponin' => ['percentage' => 20, 'absolute' => 0.1, 'critical' => 50],
        'bnp' => ['percentage' => 25, 'absolute' => 50, 'critical' => 50],
        'psa' => ['percentage' => 20, 'absolute' => 1.0, 'critical' => 40],
        'thyroid_tsh' => ['percentage' => 20, 'absolute' => 0.5, 'critical' => 40],
        'thyroid_ft4' => ['percentage' => 15, 'absolute' => 0.5, 'critical' => 30],
        'thyroid_ft3' => ['percentage' => 15, 'absolute' => 0.2, 'critical' => 30],
        'vitamin_d' => ['percentage' => 20, 'absolute' => 5.0, 'critical' => 40],
        'vitamin_b12' => ['percentage' => 20, 'absolute' => 50, 'critical' => 40],
        'folate' => ['percentage' => 25, 'absolute' => 2.0, 'critical' => 50],
        'ferritin' => ['percentage' => 20, 'absolute' => 20, 'critical' => 40],
        'iron' => ['percentage' => 20, 'absolute' => 20, 'critical' => 40],
        'tibc' => ['percentage' => 15, 'absolute' => 30, 'critical' => 30],
        'transferrin_saturation' => ['percentage' => 20, 'absolute' => 5.0, 'critical' => 40],
    ];

    // Hematology parameters (CBC)
    const HEMATOLOGY_STANDARDS = [
        'wbc' => ['percentage' => 15, 'absolute' => 1.0, 'critical' => 30],
        'rbc' => ['percentage' => 10, 'absolute' => 0.5, 'critical' => 20],
        'hemoglobin' => ['percentage' => 7, 'absolute' => 1.0, 'critical' => 15],
        'hematocrit' => ['percentage' => 6, 'absolute' => 3.0, 'critical' => 12],
        'mcv' => ['percentage' => 5, 'absolute' => 3.0, 'critical' => 10],
        'mch' => ['percentage' => 5, 'absolute' => 2.0, 'critical' => 10],
        'mchc' => ['percentage' => 5, 'absolute' => 2.0, 'critical' => 10],
        'rdw' => ['percentage' => 10, 'absolute' => 1.0, 'critical' => 20],
        'platelet_count' => ['percentage' => 15, 'absolute' => 30, 'critical' => 30],
        'mpv' => ['percentage' => 10, 'absolute' => 1.0, 'critical' => 20],
        'neutrophils' => ['percentage' => 20, 'absolute' => 5.0, 'critical' => 40],
        'lymphocytes' => ['percentage' => 20, 'absolute' => 3.0, 'critical' => 40],
        'monocytes' => ['percentage' => 25, 'absolute' => 1.0, 'critical' => 50],
        'eosinophils' => ['percentage' => 30, 'absolute' => 0.5, 'critical' => 60],
        'basophils' => ['percentage' => 50, 'absolute' => 0.1, 'critical' => 100],
    ];

    // Coagulation parameters
    const COAGULATION_STANDARDS = [
        'pt' => ['percentage' => 10, 'absolute' => 1.0, 'critical' => 20],
        'inr' => ['percentage' => 15, 'absolute' => 0.3, 'critical' => 30],
        'ptt' => ['percentage' => 10, 'absolute' => 3.0, 'critical' => 20],
        'fibrinogen' => ['percentage' => 15, 'absolute' => 50, 'critical' => 30],
        'd_dimer' => ['percentage' => 25, 'absolute' => 0.2, 'critical' => 50],
        'fdp' => ['percentage' => 30, 'absolute' => 2.0, 'critical' => 60],
    ];

    // Urinalysis parameters
    const URINALYSIS_STANDARDS = [
        'specific_gravity' => ['percentage' => 5, 'absolute' => 0.005, 'critical' => 10],
        'ph' => ['percentage' => 10, 'absolute' => 0.5, 'critical' => 20],
        'protein' => ['percentage' => 20, 'absolute' => 0.5, 'critical' => 40],
        'glucose' => ['percentage' => 15, 'absolute' => 0.5, 'critical' => 30],
        'ketones' => ['percentage' => 25, 'absolute' => 0.5, 'critical' => 50],
        'blood' => ['percentage' => 30, 'absolute' => 1.0, 'critical' => 60],
        'nitrite' => ['percentage' => 50, 'absolute' => 0.5, 'critical' => 100],
        'leukocyte_esterase' => ['percentage' => 30, 'absolute' => 1.0, 'critical' => 60],
    ];

    // Microbiology and Immunology
    const MICROBIOLOGY_STANDARDS = [
        'crp' => ['percentage' => 25, 'absolute' => 2.0, 'critical' => 50],
        'esr' => ['percentage' => 20, 'absolute' => 5.0, 'critical' => 40],
        'procalcitonin' => ['percentage' => 30, 'absolute' => 0.5, 'critical' => 60],
        'interleukin_6' => ['percentage' => 25, 'absolute' => 5.0, 'critical' => 50],
        'ferritin' => ['percentage' => 20, 'absolute' => 20, 'critical' => 40],
    ];

    // Endocrinology
    const ENDOCRINOLOGY_STANDARDS = [
        'insulin' => ['percentage' => 25, 'absolute' => 5.0, 'critical' => 50],
        'c_peptide' => ['percentage' => 20, 'absolute' => 0.5, 'critical' => 40],
        'hba1c' => ['percentage' => 5, 'absolute' => 0.3, 'critical' => 10],
        'cortisol' => ['percentage' => 20, 'absolute' => 2.0, 'critical' => 40],
        'acth' => ['percentage' => 25, 'absolute' => 10, 'critical' => 50],
        'aldosterone' => ['percentage' => 20, 'absolute' => 2.0, 'critical' => 40],
        'renin' => ['percentage' => 25, 'absolute' => 1.0, 'critical' => 50],
        'growth_hormone' => ['percentage' => 30, 'absolute' => 1.0, 'critical' => 60],
        'igf1' => ['percentage' => 20, 'absolute' => 20, 'critical' => 40],
    ];

    // Tumor Markers
    const TUMOR_MARKERS_STANDARDS = [
        'cea' => ['percentage' => 20, 'absolute' => 2.0, 'critical' => 40],
        'ca125' => ['percentage' => 20, 'absolute' => 10, 'critical' => 40],
        'ca153' => ['percentage' => 20, 'absolute' => 5.0, 'critical' => 40],
        'ca199' => ['percentage' => 20, 'absolute' => 10, 'critical' => 40],
        'afp' => ['percentage' => 20, 'absolute' => 5.0, 'critical' => 40],
        'hcg' => ['percentage' => 25, 'absolute' => 5.0, 'critical' => 50],
        'psa_total' => ['percentage' => 20, 'absolute' => 1.0, 'critical' => 40],
        'psa_free' => ['percentage' => 25, 'absolute' => 0.5, 'critical' => 50],
        'ca724' => ['percentage' => 20, 'absolute' => 2.0, 'critical' => 40],
    ];

    /**
     * Get recommendation based on test parameter and violation type
     */
    public static function getRecommendation($testParameter, $violationType, $percentageDifference, $absoluteDifference, $currentValue, $previousValue)
    {
        $parameter = strtolower($testParameter);
        $standards = self::getStandardsForParameter($parameter);
        
        if (!$standards) {
            return self::getGenericRecommendation($violationType, $percentageDifference, $absoluteDifference);
        }

        $recommendation = [
            'severity' => self::determineSeverity($parameter, $standards, $percentageDifference, $absoluteDifference),
            'action_required' => self::getActionRequired($parameter, $standards, $percentageDifference, $absoluteDifference),
            'clinical_significance' => self::getClinicalSignificance($parameter, $currentValue, $previousValue),
            'follow_up' => self::getFollowUpRecommendations($parameter, $violationType),
            'quality_control' => self::getQualityControlRecommendations($parameter, $violationType),
            'standards_reference' => self::getStandardsReference($parameter),
        ];

        return $recommendation;
    }

    /**
     * Get standards for a specific parameter
     */
    private static function getStandardsForParameter($parameter)
    {
        $allStandards = array_merge(
            self::CLIA_STANDARDS,
            self::HEMATOLOGY_STANDARDS,
            self::COAGULATION_STANDARDS,
            self::URINALYSIS_STANDARDS,
            self::MICROBIOLOGY_STANDARDS,
            self::ENDOCRINOLOGY_STANDARDS,
            self::TUMOR_MARKERS_STANDARDS
        );

        return $allStandards[$parameter] ?? null;
    }

    /**
     * Determine severity level
     */
    private static function determineSeverity($parameter, $standards, $percentageDifference, $absoluteDifference)
    {
        $criticalThreshold = $standards['critical'] ?? 50;
        
        if ($percentageDifference >= $criticalThreshold) {
            return 'critical';
        } elseif ($percentageDifference >= ($standards['percentage'] ?? 20)) {
            return 'high';
        } elseif ($absoluteDifference && $absoluteDifference >= ($standards['absolute'] ?? 1.0)) {
            return 'moderate';
        } else {
            return 'low';
        }
    }

    /**
     * Get action required based on severity
     */
    private static function getActionRequired($parameter, $standards, $percentageDifference, $absoluteDifference)
    {
        $severity = self::determineSeverity($parameter, $standards, $percentageDifference, $absoluteDifference);
        
        $actions = [
            'critical' => [
                'immediate' => 'CRITICAL: Immediate verification required',
                'steps' => [
                    'Repeat test with fresh sample',
                    'Verify patient identity',
                    'Check for pre-analytical errors',
                    'Notify physician immediately',
                    'Document all actions taken'
                ]
            ],
            'high' => [
                'immediate' => 'HIGH PRIORITY: Verification recommended',
                'steps' => [
                    'Repeat test within 2 hours',
                    'Verify sample integrity',
                    'Check instrument calibration',
                    'Review recent QC results',
                    'Consider clinical correlation'
                ]
            ],
            'moderate' => [
                'immediate' => 'MODERATE: Review recommended',
                'steps' => [
                    'Verify test result',
                    'Check instrument status',
                    'Review patient history',
                    'Consider repeat if clinically indicated'
                ]
            ],
            'low' => [
                'immediate' => 'LOW: Monitor trend',
                'steps' => [
                    'Document observation',
                    'Continue routine monitoring',
                    'Review at next scheduled test'
                ]
            ]
        ];

        return $actions[$severity] ?? $actions['low'];
    }

    /**
     * Get clinical significance
     */
    private static function getClinicalSignificance($parameter, $currentValue, $previousValue)
    {
        $clinicalSignificance = [
            'hemoglobin' => 'Significant changes may indicate bleeding, hemolysis, or treatment response',
            'hematocrit' => 'Changes may reflect fluid status, blood loss, or hematologic disorders',
            'glucose' => 'Variations may indicate diabetes control, stress response, or medication effects',
            'creatinine' => 'Changes may reflect kidney function, hydration status, or medication effects',
            'sodium' => 'Variations may indicate fluid balance, kidney function, or endocrine disorders',
            'potassium' => 'Changes may reflect kidney function, medication effects, or acid-base status',
            'troponin' => 'Significant changes may indicate myocardial injury or infarction',
            'psa' => 'Variations may indicate prostate disease progression or treatment response',
            'thyroid_tsh' => 'Changes may reflect thyroid function or medication adjustments',
            'vitamin_d' => 'Variations may indicate supplementation response or seasonal changes',
        ];

        return $clinicalSignificance[$parameter] ?? 'Consider clinical correlation and patient history';
    }

    /**
     * Get follow-up recommendations
     */
    private static function getFollowUpRecommendations($parameter, $violationType)
    {
        $followUp = [
            'immediate' => [
                'Verify patient identity and sample labeling',
                'Check for pre-analytical variables',
                'Review instrument maintenance logs',
                'Confirm calibration status'
            ],
            'short_term' => [
                'Monitor trend over next 24-48 hours',
                'Consider additional testing if clinically indicated',
                'Review with clinical team',
                'Document in quality management system'
            ],
            'long_term' => [
                'Establish baseline for future comparisons',
                'Implement enhanced monitoring if needed',
                'Review delta check limits if appropriate',
                'Update quality control procedures'
            ]
        ];

        return $followUp;
    }

    /**
     * Get quality control recommendations
     */
    private static function getQualityControlRecommendations($parameter, $violationType)
    {
        return [
            'instrument_check' => 'Verify instrument calibration and maintenance',
            'qc_review' => 'Review recent quality control results',
            'method_verification' => 'Confirm analytical method performance',
            'staff_training' => 'Review procedures with technical staff',
            'documentation' => 'Document all QC activities and results'
        ];
    }

    /**
     * Get standards reference
     */
    private static function getStandardsReference($parameter)
    {
        return [
            'clia' => 'Clinical Laboratory Improvement Amendments (CLIA)',
            'cap' => 'College of American Pathologists (CAP)',
            'iso' => 'ISO 15189:2012 Medical laboratories',
            'westgard' => 'Westgard QC Rules',
            'clsi' => 'Clinical and Laboratory Standards Institute (CLSI)',
            'ifcc' => 'International Federation of Clinical Chemistry (IFCC)'
        ];
    }

    /**
     * Get generic recommendation for unknown parameters
     */
    private static function getGenericRecommendation($violationType, $percentageDifference, $absoluteDifference)
    {
        $severity = $percentageDifference >= 50 ? 'critical' : 
                   ($percentageDifference >= 25 ? 'high' : 
                   ($percentageDifference >= 15 ? 'moderate' : 'low'));

        return [
            'severity' => $severity,
            'action_required' => [
                'immediate' => "DELTA CHECK VIOLATION: {$severity} severity",
                'steps' => [
                    'Verify test result accuracy',
                    'Check for pre-analytical errors',
                    'Review instrument performance',
                    'Consider clinical correlation',
                    'Document findings'
                ]
            ],
            'clinical_significance' => 'Consider clinical correlation and patient history',
            'follow_up' => [
                'immediate' => ['Verify result', 'Check QC', 'Review patient data'],
                'short_term' => ['Monitor trend', 'Clinical review'],
                'long_term' => ['Establish baseline', 'Update procedures']
            ],
            'quality_control' => [
                'instrument_check' => 'Verify instrument status',
                'qc_review' => 'Review QC results',
                'documentation' => 'Document findings'
            ],
            'standards_reference' => [
                'clia' => 'CLIA Standards',
                'iso' => 'ISO 15189',
                'westgard' => 'Westgard Rules'
            ]
        ];
    }

    /**
     * Get all available parameters
     */
    public static function getAllParameters()
    {
        return array_merge(
            array_keys(self::CLIA_STANDARDS),
            array_keys(self::HEMATOLOGY_STANDARDS),
            array_keys(self::COAGULATION_STANDARDS),
            array_keys(self::URINALYSIS_STANDARDS),
            array_keys(self::MICROBIOLOGY_STANDARDS),
            array_keys(self::ENDOCRINOLOGY_STANDARDS),
            array_keys(self::TUMOR_MARKERS_STANDARDS)
        );
    }

    /**
     * Get recommended delta check limits for a parameter
     */
    public static function getRecommendedLimits($parameter)
    {
        $standards = self::getStandardsForParameter(strtolower($parameter));
        
        if (!$standards) {
            return [
                'percentage_limit' => 20.0,
                'absolute_limit' => null,
                'critical_threshold' => 50.0,
                'recommendation' => 'Use generic limits - consider establishing specific limits'
            ];
        }

        return [
            'percentage_limit' => $standards['percentage'],
            'absolute_limit' => $standards['absolute'],
            'critical_threshold' => $standards['critical'],
            'recommendation' => 'Based on international standards'
        ];
    }
}

