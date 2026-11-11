<?php

namespace App\Services;

use App\Models\DeltaCheckParameter;
use App\Models\DeltaCheckSeverityLevel;
use App\Models\DeltaCheckAction;
use App\Models\DeltaCheckClinicalSignificance;
use App\Models\DeltaCheckPreventiveMeasure;
use App\Models\DeltaCheckQualityControl;
use App\Models\DeltaCheckGlobalRecommendation;
use App\Models\DeltaCheckRecommendationLog;

class DatabaseDeltaCheckRecommendationService
{
    /**
     * Generate comprehensive recommendation for a delta check violation using database
     */
    public function generateRecommendation($deltaCheckData)
    {
        $testParameter = $deltaCheckData['test_parameter']['test_name'] ?? '';
        $violationType = $deltaCheckData['status'] === 'flagged' ? 'violation' : 'normal';
        $percentageDifference = $deltaCheckData['percentage_difference'] ?? 0;
        $absoluteDifference = $deltaCheckData['absolute_difference'] ?? 0;
        $currentValue = $deltaCheckData['current_value'] ?? 0;
        $previousValue = $deltaCheckData['previous_value'] ?? 0;

        // Find parameter in database
        $parameter = $this->findParameter($testParameter);
        
        if (!$parameter) {
            return $this->getGenericRecommendation($violationType, $percentageDifference, $absoluteDifference);
        }

        // Determine severity level
        $severityLevel = $this->determineSeverityLevel($parameter, $percentageDifference, $absoluteDifference);
        
        // Get actions for this severity level
        $actions = $this->getActionsForSeverity($severityLevel);
        
        // Get clinical significance
        $clinicalSignificance = $this->getClinicalSignificance($parameter);
        
        // Get preventive measures
        $preventiveMeasures = $this->getPreventiveMeasures($parameter);
        
        // Get quality control recommendations
        $qualityControl = $this->getQualityControlRecommendations($parameter);

        $recommendation = [
            'severity' => $severityLevel->name,
            'severity_display' => $severityLevel->display_name,
            'severity_color' => $severityLevel->color,
            'action_required' => $actions,
            'clinical_significance' => $clinicalSignificance,
            'preventive_measures' => $preventiveMeasures,
            'quality_control' => $qualityControl,
            'parameter_info' => [
                'name' => $parameter->display_name,
                'category' => $parameter->category,
                'unit' => $parameter->unit,
                'specimen_type' => $parameter->specimen_type,
                'standards_references' => $parameter->standards_references,
            ],
            'context' => $this->getContextualInformation($deltaCheckData),
            'risk_assessment' => $this->getRiskAssessment($deltaCheckData, $severityLevel),
        ];

        // Log the recommendation
        $this->logRecommendation($deltaCheckData, $parameter, $severityLevel, $recommendation);

        return $recommendation;
    }

    /**
     * Find parameter by name (case-insensitive)
     */
    private function findParameter($parameterName)
    {
        return DeltaCheckParameter::active()
            ->whereRaw('LOWER(parameter_name) = ?', [strtolower($parameterName)])
            ->orWhereRaw('LOWER(display_name) = ?', [strtolower($parameterName)])
            ->first();
    }

    /**
     * Determine severity level based on parameter limits and differences
     */
    private function determineSeverityLevel($parameter, $percentageDifference, $absoluteDifference)
    {
        $criticalThreshold = $parameter->critical_threshold ?? 50;
        $percentageLimit = $parameter->percentage_limit ?? 20;
        $absoluteLimit = $parameter->absolute_limit ?? 1.0;

        if ($percentageDifference >= $criticalThreshold) {
            return DeltaCheckSeverityLevel::where('name', 'critical')->first();
        } elseif ($percentageDifference >= $percentageLimit) {
            return DeltaCheckSeverityLevel::where('name', 'high')->first();
        } elseif ($absoluteDifference && $absoluteDifference >= $absoluteLimit) {
            return DeltaCheckSeverityLevel::where('name', 'moderate')->first();
        } else {
            return DeltaCheckSeverityLevel::where('name', 'low')->first();
        }
    }

    /**
     * Get actions for a severity level
     */
    private function getActionsForSeverity($severityLevel)
    {
        $actions = $severityLevel->getActionsByType();
        
        $formattedActions = [];
        
        foreach ($actions as $type => $actionList) {
            $formattedActions[$type] = [
                'immediate' => $actionList->where('action_type', 'immediate')->first()?->title ?? 'No immediate action required',
                'steps' => $actionList->where('action_type', 'immediate')->first()?->steps ?? [],
                'follow_up' => $actionList->where('action_type', 'short_term')->first()?->steps ?? [],
                'long_term' => $actionList->where('action_type', 'long_term')->first()?->steps ?? [],
            ];
        }

        return $formattedActions;
    }

    /**
     * Get clinical significance for parameter
     */
    private function getClinicalSignificance($parameter)
    {
        $significance = $parameter->getActiveClinicalSignificance();
        
        if (!$significance) {
            return 'Consider clinical correlation and patient history';
        }

        return [
            'text' => $significance->significance_text,
            'risk_factors' => $significance->risk_factors ?? [],
            'clinical_conditions' => $significance->clinical_conditions ?? [],
        ];
    }

    /**
     * Get preventive measures for parameter
     */
    private function getPreventiveMeasures($parameter)
    {
        $measures = $parameter->getActivePreventiveMeasures();
        
        $formattedMeasures = [];
        
        foreach ($measures as $type => $measureList) {
            $formattedMeasures[$type] = $measureList->map(function ($measure) {
                return [
                    'title' => $measure->title,
                    'description' => $measure->description,
                    'specific_measures' => $measure->specific_measures ?? [],
                ];
            })->toArray();
        }

        return $formattedMeasures;
    }

    /**
     * Get quality control recommendations for parameter
     */
    private function getQualityControlRecommendations($parameter)
    {
        $qc = $parameter->getActiveQualityControl();
        
        return $qc->map(function ($item) {
            return [
                'type' => $item->qc_type,
                'title' => $item->title,
                'description' => $item->description,
                'steps' => $item->qc_steps ?? [],
            ];
        })->toArray();
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
    private function getRiskAssessment($deltaCheckData, $severityLevel)
    {
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
        if ($severityLevel->name === 'critical') {
            $riskLevel = 'critical';
            $riskFactorsPresent[] = 'Critical delta violation';
        } elseif ($severityLevel->name === 'high') {
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
     * Log recommendation for tracking and analysis
     */
    private function logRecommendation($deltaCheckData, $parameter, $severityLevel, $recommendation)
    {
        DeltaCheckRecommendationLog::create([
            'test_result_id' => $deltaCheckData['test_result']['id'] ?? null,
            'parameter_id' => $parameter->id ?? null,
            'violation_type' => $deltaCheckData['status'] === 'flagged' ? 'percentage' : 'normal',
            'percentage_difference' => $deltaCheckData['percentage_difference'] ?? 0,
            'absolute_difference' => $deltaCheckData['absolute_difference'] ?? 0,
            'severity_level' => $severityLevel->name,
            'recommendations_given' => $recommendation,
            'outcome' => null, // Will be updated when actions are taken
        ]);
    }

    /**
     * Get global statistics and recommendations
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
        
        // Get global recommendations from database
        $globalRecommendations = $this->getGlobalRecommendations($parameterStats, $violationRate);
        
        return [
            'overall' => [
                'total_checks' => $totalChecks,
                'flagged_checks' => $flaggedCount,
                'violation_rate' => round($violationRate, 2),
                'normal_rate' => round(100 - $violationRate, 2),
            ],
            'by_parameter' => $parameterStats,
            'recommendations' => $globalRecommendations,
        ];
    }

    /**
     * Get global recommendations from database
     */
    private function getGlobalRecommendations($parameterStats, $violationRate)
    {
        $recommendations = [];
        
        // Get recommendations from database
        $dbRecommendations = DeltaCheckGlobalRecommendation::active()->get();
        
        foreach ($dbRecommendations as $rec) {
            $shouldInclude = false;
            
            if ($rec->recommendation_type === 'overall_violation_rate') {
                $threshold = $rec->thresholds['violation_rate'] ?? 20;
                if ($violationRate > $threshold) {
                    $shouldInclude = true;
                }
            } elseif ($rec->recommendation_type === 'parameter_specific') {
                $threshold = $rec->thresholds['parameter_violation_rate'] ?? 30;
                foreach ($parameterStats as $param => $stats) {
                    if ($stats['violation_rate'] > $threshold && $stats['total'] >= 5) {
                        $shouldInclude = true;
                        break;
                    }
                }
            }
            
            if ($shouldInclude) {
                $recommendations[] = [
                    'type' => $rec->severity,
                    'message' => $rec->message,
                    'action' => $rec->action,
                    'title' => $rec->title,
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Get generic recommendation for unknown parameters
     */
    private function getGenericRecommendation($violationType, $percentageDifference, $absoluteDifference)
    {
        $severity = $percentageDifference >= 50 ? 'critical' : 
                   ($percentageDifference >= 25 ? 'high' : 
                   ($percentageDifference >= 15 ? 'moderate' : 'low'));

        return [
            'severity' => $severity,
            'severity_display' => ucfirst($severity),
            'severity_color' => $this->getSeverityColor($severity),
            'action_required' => [
                'immediate' => [
                    'immediate' => "DELTA CHECK VIOLATION: {$severity} severity",
                    'steps' => [
                        'Verify test result accuracy',
                        'Check for pre-analytical errors',
                        'Review instrument performance',
                        'Consider clinical correlation',
                        'Document findings'
                    ],
                ]
            ],
            'clinical_significance' => 'Consider clinical correlation and patient history',
            'preventive_measures' => [],
            'quality_control' => [],
            'parameter_info' => [
                'name' => 'Unknown Parameter',
                'category' => 'Unknown',
                'unit' => 'Unknown',
                'specimen_type' => 'Unknown',
                'standards_references' => ['CLIA', 'ISO 15189', 'Westgard Rules'],
            ],
            'context' => [],
            'risk_assessment' => [
                'risk_level' => $severity,
                'risk_factors' => ['Unknown parameter'],
                'mitigation_strategies' => ['Verify result', 'Check QC', 'Review patient data'],
            ],
        ];
    }

    /**
     * Get severity color
     */
    private function getSeverityColor($severity)
    {
        $colors = [
            'critical' => '#f56c6c',
            'high' => '#e6a23c',
            'moderate' => '#409eff',
            'low' => '#67c23a',
        ];
        
        return $colors[$severity] ?? '#909399';
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
     * Get all available parameters
     */
    public function getAllParameters()
    {
        return DeltaCheckParameter::active()
            ->orderBy('category')
            ->orderBy('display_name')
            ->get();
    }

    /**
     * Get recommended limits for a parameter
     */
    public function getRecommendedLimits($parameterName)
    {
        $parameter = $this->findParameter($parameterName);
        
        if (!$parameter) {
            return [
                'percentage_limit' => 20.0,
                'absolute_limit' => null,
                'critical_threshold' => 50.0,
                'recommendation' => 'Use generic limits - consider establishing specific limits'
            ];
        }

        return [
            'percentage_limit' => $parameter->percentage_limit,
            'absolute_limit' => $parameter->absolute_limit,
            'critical_threshold' => $parameter->critical_threshold,
            'recommendation' => 'Based on international standards',
            'standards_references' => $parameter->standards_references,
        ];
    }

    /**
     * Add new parameter to the system
     */
    public function addParameter($parameterData)
    {
        return DeltaCheckParameter::create($parameterData);
    }

    /**
     * Update parameter limits
     */
    public function updateParameterLimits($parameterId, $limits)
    {
        $parameter = DeltaCheckParameter::find($parameterId);
        
        if (!$parameter) {
            return false;
        }
        
        $parameter->update($limits);
        
        return true;
    }
}

