<?php

namespace App\Services;

use App\Repositories\DeltaCheckRecommendationRepository;
use App\Models\TestParameter;

class DeltaCheckRecommendationService
{
    protected $recommendationRepository;

    public function __construct()
    {
        $this->recommendationRepository = new DeltaCheckRecommendationRepository();
    }

    /**
     * Generate comprehensive recommendation for a delta check violation
     */
    public function generateRecommendation($deltaCheckData)
    {
        $testParameter = $deltaCheckData['test_parameter']['test_name'] ?? '';
        $violationType = $deltaCheckData['status'] === 'flagged' ? 'violation' : 'normal';
        $percentageDifference = $deltaCheckData['percentage_difference'] ?? 0;
        $absoluteDifference = $deltaCheckData['absolute_difference'] ?? 0;
        $currentValue = $deltaCheckData['current_value'] ?? 0;
        $previousValue = $deltaCheckData['previous_value'] ?? 0;

        // Get base recommendation
        $recommendation = DeltaCheckRecommendationRepository::getRecommendation(
            $testParameter,
            $violationType,
            $percentageDifference,
            $absoluteDifference,
            $currentValue,
            $previousValue
        );

        // Add contextual information
        $recommendation['context'] = $this->getContextualInformation($deltaCheckData);
        $recommendation['risk_assessment'] = $this->getRiskAssessment($deltaCheckData, $recommendation);
        $recommendation['preventive_measures'] = $this->getPreventiveMeasures($testParameter, $violationType);

        return $recommendation;
    }

    /**
     * Get contextual information about the delta check
     */
    private function getContextualInformation($deltaCheckData)
    {
        $patient = $deltaCheckData['test_result']['laboratory_request']['patient'] ?? null;
        $test = $deltaCheckData['test'] ?? null;
        $testParameter = $deltaCheckData['test_parameter'] ?? null;

        return [
            'patient_info' => [
                'name' => $patient ? "{$patient['first_name']} {$patient['last_name']}" : 'Unknown',
                'age' => $patient ? $this->calculateAge($patient['date_of_birth']) : null,
                'gender' => $patient['gender'] ?? null,
            ],
            'test_info' => [
                'test_name' => $test['name'] ?? 'Unknown',
                'parameter' => $testParameter['test_name'] ?? 'Unknown',
                'unit' => $testParameter['unit'] ?? 'Unknown',
                'specimen_type' => $testParameter['specimen_type'] ?? 'Unknown',
            ],
            'timing' => [
                'current_date' => $deltaCheckData['created_at'] ?? null,
                'time_since_previous' => $this->calculateTimeDifference(
                    $deltaCheckData['previous_test_result']['created_at'] ?? null,
                    $deltaCheckData['created_at'] ?? null
                ),
            ],
            'values' => [
                'current' => $deltaCheckData['current_value'] ?? 0,
                'previous' => $deltaCheckData['previous_value'] ?? 0,
                'difference' => $deltaCheckData['difference'] ?? 0,
                'percentage_change' => $deltaCheckData['percentage_difference'] ?? 0,
            ]
        ];
    }

    /**
     * Get risk assessment
     */
    private function getRiskAssessment($deltaCheckData, $recommendation)
    {
        $severity = $recommendation['severity'];
        $percentageDifference = $deltaCheckData['percentage_difference'] ?? 0;
        $testParameter = strtolower($deltaCheckData['test_parameter']['test_name'] ?? '');

        $riskFactors = [
            'high_risk_parameters' => ['troponin', 'psa', 'glucose', 'potassium', 'sodium', 'creatinine'],
            'critical_parameters' => ['troponin', 'psa', 'glucose', 'potassium'],
            'time_sensitive' => ['troponin', 'glucose', 'potassium', 'sodium'],
        ];

        $riskLevel = 'low';
        $riskFactorsPresent = [];

        // Check parameter-specific risks
        if (in_array($testParameter, $riskFactors['critical_parameters'])) {
            $riskLevel = 'high';
            $riskFactorsPresent[] = 'Critical parameter';
        } elseif (in_array($testParameter, $riskFactors['high_risk_parameters'])) {
            $riskLevel = 'moderate';
            $riskFactorsPresent[] = 'High-risk parameter';
        }

        // Check severity
        if ($severity === 'critical') {
            $riskLevel = 'critical';
            $riskFactorsPresent[] = 'Critical delta violation';
        } elseif ($severity === 'high') {
            $riskLevel = max($riskLevel, 'high');
            $riskFactorsPresent[] = 'High delta violation';
        }

        // Check percentage threshold
        if ($percentageDifference >= 100) {
            $riskLevel = 'critical';
            $riskFactorsPresent[] = 'Extreme percentage change';
        } elseif ($percentageDifference >= 50) {
            $riskLevel = max($riskLevel, 'high');
            $riskFactorsPresent[] = 'Significant percentage change';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactorsPresent,
            'mitigation_strategies' => $this->getMitigationStrategies($riskLevel, $testParameter),
        ];
    }

    /**
     * Get preventive measures
     */
    private function getPreventiveMeasures($testParameter, $violationType)
    {
        $measures = [
            'pre_analytical' => [
                'Verify patient identity',
                'Check sample collection time',
                'Ensure proper sample handling',
                'Verify fasting status if required',
                'Check for interfering substances'
            ],
            'analytical' => [
                'Verify instrument calibration',
                'Check quality control results',
                'Review maintenance logs',
                'Confirm method performance',
                'Check for carryover effects'
            ],
            'post_analytical' => [
                'Verify result entry',
                'Check reference ranges',
                'Review delta check limits',
                'Confirm clinical correlation',
                'Document all findings'
            ],
            'system_improvements' => [
                'Review delta check limits',
                'Update quality control procedures',
                'Enhance staff training',
                'Implement additional checks',
                'Improve documentation'
            ]
        ];

        // Add parameter-specific measures
        $parameterMeasures = $this->getParameterSpecificMeasures($testParameter);
        $measures = array_merge_recursive($measures, $parameterMeasures);

        return $measures;
    }

    /**
     * Get parameter-specific preventive measures
     */
    private function getParameterSpecificMeasures($testParameter)
    {
        $parameter = strtolower($testParameter);
        
        $specificMeasures = [
            'glucose' => [
                'pre_analytical' => ['Verify fasting status', 'Check sample timing', 'Avoid hemolysis'],
                'analytical' => ['Check glucose meter calibration', 'Verify reagent stability'],
            ],
            'hemoglobin' => [
                'pre_analytical' => ['Check for hemolysis', 'Verify sample mixing', 'Check anticoagulant'],
                'analytical' => ['Verify hematology analyzer', 'Check for carryover'],
            ],
            'troponin' => [
                'pre_analytical' => ['Verify sample timing', 'Check for hemolysis', 'Ensure proper collection'],
                'analytical' => ['Check assay calibration', 'Verify sensitivity'],
                'post_analytical' => ['Immediate physician notification', 'Critical value protocol'],
            ],
            'psa' => [
                'pre_analytical' => ['Check for recent procedures', 'Verify sample timing', 'Check for infections'],
                'analytical' => ['Verify assay calibration', 'Check for interference'],
            ],
            'creatinine' => [
                'pre_analytical' => ['Check for muscle mass changes', 'Verify sample timing', 'Check for medications'],
                'analytical' => ['Verify method calibration', 'Check for interference'],
            ],
        ];

        return $specificMeasures[$parameter] ?? [];
    }

    /**
     * Get mitigation strategies based on risk level
     */
    private function getMitigationStrategies($riskLevel, $testParameter)
    {
        $strategies = [
            'critical' => [
                'Immediate result verification',
                'Repeat testing with fresh sample',
                'Notify physician immediately',
                'Implement emergency protocols',
                'Document all actions taken'
            ],
            'high' => [
                'Priority result verification',
                'Repeat testing within 2 hours',
                'Notify clinical team',
                'Review quality control',
                'Enhanced documentation'
            ],
            'moderate' => [
                'Standard verification process',
                'Repeat if clinically indicated',
                'Review with supervisor',
                'Monitor trend',
                'Document findings'
            ],
            'low' => [
                'Routine verification',
                'Continue monitoring',
                'Document observation',
                'Review at next test'
            ]
        ];

        return $strategies[$riskLevel] ?? $strategies['low'];
    }

    /**
     * Calculate age from date of birth
     */
    private function calculateAge($dateOfBirth)
    {
        if (!$dateOfBirth) return null;
        
        $birthDate = new \DateTime($dateOfBirth);
        $today = new \DateTime();
        return $today->diff($birthDate)->y;
    }

    /**
     * Calculate time difference between two dates
     */
    private function calculateTimeDifference($previousDate, $currentDate)
    {
        if (!$previousDate || !$currentDate) return null;
        
        $previous = new \DateTime($previousDate);
        $current = new \DateTime($currentDate);
        $diff = $current->diff($previous);
        
        if ($diff->days > 0) {
            return $diff->days . ' days';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours';
        } else {
            return $diff->i . ' minutes';
        }
    }

    /**
     * Get global statistics and trends
     */
    public function getGlobalStatistics($deltaChecks)
    {
        $totalChecks = count($deltaChecks);
        $flaggedChecks = array_filter($deltaChecks, fn($dc) => $dc['status'] === 'flagged');
        $flaggedCount = count($flaggedChecks);
        
        $violationRate = $totalChecks > 0 ? ($flaggedCount / $totalChecks) * 100 : 0;
        
        // Group by parameter
        $parameterStats = [];
        foreach ($deltaChecks as $check) {
            $param = $check['test_parameter']['test_name'] ?? 'Unknown';
            if (!isset($parameterStats[$param])) {
                $parameterStats[$param] = ['total' => 0, 'flagged' => 0];
            }
            $parameterStats[$param]['total']++;
            if ($check['status'] === 'flagged') {
                $parameterStats[$param]['flagged']++;
            }
        }
        
        // Calculate violation rates by parameter
        foreach ($parameterStats as $param => $stats) {
            $parameterStats[$param]['violation_rate'] = $stats['total'] > 0 ? 
                ($stats['flagged'] / $stats['total']) * 100 : 0;
        }
        
        // Sort by violation rate
        uasort($parameterStats, fn($a, $b) => $b['violation_rate'] <=> $a['violation_rate']);
        
        return [
            'overall' => [
                'total_checks' => $totalChecks,
                'flagged_checks' => $flaggedCount,
                'violation_rate' => round($violationRate, 2),
                'normal_rate' => round(100 - $violationRate, 2),
            ],
            'by_parameter' => $parameterStats,
            'recommendations' => $this->getGlobalRecommendations($parameterStats, $violationRate),
        ];
    }

    /**
     * Get global recommendations based on statistics
     */
    private function getGlobalRecommendations($parameterStats, $violationRate)
    {
        $recommendations = [];
        
        // Overall violation rate recommendations
        if ($violationRate > 20) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'High overall violation rate detected',
                'action' => 'Immediate review of delta check limits and procedures required'
            ];
        } elseif ($violationRate > 10) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Elevated violation rate detected',
                'action' => 'Review delta check limits and quality control procedures'
            ];
        }
        
        // Parameter-specific recommendations
        foreach ($parameterStats as $param => $stats) {
            if ($stats['violation_rate'] > 30 && $stats['total'] >= 5) {
                $recommendations[] = [
                    'type' => 'parameter_specific',
                    'parameter' => $param,
                    'message' => "High violation rate for {$param}",
                    'action' => "Review delta check limits for {$param}",
                    'violation_rate' => round($stats['violation_rate'], 2)
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Update test parameter limits based on recommendations
     */
    public function updateParameterLimits($testParameterId)
    {
        $testParameter = TestParameter::find($testParameterId);
        if (!$testParameter) {
            return false;
        }
        
        $recommendedLimits = DeltaCheckRecommendationRepository::getRecommendedLimits(
            $testParameter->test_name
        );
        
        $testParameter->update([
            'delta_check_percentage_limit' => $recommendedLimits['percentage_limit'],
            'delta_check_absolute_limit' => $recommendedLimits['absolute_limit'],
        ]);
        
        return true;
    }
}
