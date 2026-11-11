<?php

namespace App\Services;

use App\Models\TestResult;
use App\Models\TestParameter;
use App\Models\DeltaCheckRule;
use App\Services\DatabaseDeltaCheckRecommendationService;

class DeltaCheckService
{
    protected $recommendationService;

    public function __construct()
    {
        $this->recommendationService = new DatabaseDeltaCheckRecommendationService();
    }
    /**
     * Calculate delta check for a test result on-demand
     */
    public function calculateDeltaCheck(TestResult $currentTestResult)
    {
        $patientId = $currentTestResult->laboratoryRequest->patient_id;
        $testParameterId = $currentTestResult->test_parameter_id;
        $currentValue = (float) $currentTestResult->result_value;

        // Find the most recent previous valid test result for the same patient, test, and parameter
        $previousTestResult = TestResult::whereHas('laboratoryRequest', function ($query) use ($patientId) {
            $query->where('patient_id', $patientId);
        })
        ->where('test_parameter_id', $testParameterId)
        ->where('id', '!=', $currentTestResult->id)
        ->whereNotNull('result_value')
        ->where('created_at', '<', $currentTestResult->created_at) // Ensure it's actually previous
        ->orderBy('created_at', 'desc')
        ->first();

        if (!$previousTestResult) {
            return null; // No previous result to compare
        }

        $previousValue = (float) $previousTestResult->result_value;
        $parameter = TestParameter::find($testParameterId);

        $percentageDifference = $previousValue != 0 ? 
            abs(($currentValue - $previousValue) / $previousValue) * 100 : 0;
        
        $absoluteDifference = abs($currentValue - $previousValue);

        // Get equipment ID if available (from test result)
        $equipmentId = null;
        if ($currentTestResult->equipment_id) {
            $equipmentId = $currentTestResult->equipment_id;
        }

        // Check time window - ensure previous result is within configured time window
        $timeWindowDays = 30; // Default
        $previousResultDate = $previousTestResult->created_at;
        $currentResultDate = $currentTestResult->created_at;
        $daysDifference = $previousResultDate->diffInDays($currentResultDate);

        // Find applicable delta check rule
        // Priority: Equipment-specific > Parameter-specific > Parameter defaults
        $rule = null;
        if ($equipmentId) {
            $rule = DeltaCheckRule::active()
                ->where('test_parameter_id', $testParameterId)
                ->where('equipment_id', $equipmentId)
                ->first();
        }
        
        if (!$rule) {
            $rule = DeltaCheckRule::active()
                ->where('test_parameter_id', $testParameterId)
                ->whereNull('equipment_id')
                ->first();
        }

        // Get limits from rule or fall back to parameter defaults
        $percentageLimit = $rule ? $rule->percentage_limit : ($parameter ? $parameter->delta_check_percentage_limit : null);
        $absoluteLimit = $rule ? $rule->absolute_limit : ($parameter ? $parameter->delta_check_absolute_limit : null);
        $timeWindowDays = $rule ? $rule->time_window_days : $timeWindowDays;
        $comparisonType = $rule ? $rule->comparison_type : 'both';
        $actionOnViolation = $rule ? $rule->action_on_violation : 'flag';

        // Check if previous result is within time window
        if ($daysDifference > $timeWindowDays) {
            return null; // Previous result is too old
        }

        // Check against limits
        $isFlagged = false;
        $remarks = '';
        $violationType = null;

        if ($comparisonType === 'percentage' || $comparisonType === 'both') {
            if ($percentageLimit && $percentageDifference > $percentageLimit) {
                $isFlagged = true;
                $remarks = 'Percentage difference exceeds limit';
                $violationType = 'percentage';
            }
        }

        if (!$isFlagged && ($comparisonType === 'absolute' || $comparisonType === 'both')) {
            if ($absoluteLimit && $absoluteDifference > $absoluteLimit) {
                $isFlagged = true;
                $remarks = 'Absolute difference exceeds limit';
                $violationType = 'absolute';
            }
        }

        // Store rule reference for recommendations
        $appliedRule = $rule;

        $deltaCheckData = [
            'id' => 'delta_' . $currentTestResult->id, // Generate unique ID for frontend
            'test_result' => $currentTestResult,
            'previous_test_result' => $previousTestResult,
            'current_value' => $currentValue,
            'previous_value' => $previousValue,
            'difference' => $currentValue - $previousValue,
            'absolute_difference' => $absoluteDifference,
            'percentage_difference' => $percentageDifference,
            'status' => $isFlagged ? 'flagged' : 'normal',
            'remarks' => $remarks,
            'violation_type' => $violationType,
            'action_on_violation' => $actionOnViolation,
            'applied_rule' => $appliedRule,
            'rule_name' => $appliedRule ? $appliedRule->rule_name : null,
            'alert_message' => $appliedRule && $isFlagged ? $appliedRule->alert_message : null,
            'test' => $currentTestResult->test,
            'test_parameter' => $parameter,
            'created_at' => $currentTestResult->created_at,
        ];

        // Add recommendations for flagged results
        if ($isFlagged) {
            $deltaCheckData['recommendations'] = $this->recommendationService->generateRecommendation($deltaCheckData);
        }

        return $deltaCheckData;
    }

    /**
     * Get all delta checks for a report with filters
     */
    public function getDeltaChecksForReport($filters = [])
    {
        $query = TestResult::with(['laboratoryRequest.patient', 'test', 'testParameter'])
            ->whereNotNull('result_value')
            ->orderByDesc('created_at');

        // Apply filters
        if (isset($filters['patient_id'])) {
            $query->whereHas('laboratoryRequest', function ($q) use ($filters) {
                $q->where('patient_id', $filters['patient_id']);
            });
        }

        if (isset($filters['test_id'])) {
            $query->where('test_id', $filters['test_id']);
        }

        if (isset($filters['test_parameter_id'])) {
            $query->where('test_parameter_id', $filters['test_parameter_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59'
            ]);
        }

        $testResults = $query->get();
        $deltaChecks = [];

        foreach ($testResults as $testResult) {
            $deltaCheck = $this->calculateDeltaCheck($testResult);
            
            // Only include delta checks that have a previous result to compare against
            if ($deltaCheck && $deltaCheck['previous_test_result']) {
                // Apply status filter if specified
                if (isset($filters['status']) && $filters['status'] !== '' && 
                    $deltaCheck['status'] !== $filters['status']) {
                    continue;
                }
                
                $deltaChecks[] = $deltaCheck;
            }
        }

        return $deltaChecks;
    }

    /**
     * Get global statistics and recommendations
     */
    public function getGlobalStatistics($filters = [])
    {
        $deltaChecks = $this->getDeltaChecksForReport($filters);
        return $this->recommendationService->getGlobalStatistics($deltaChecks);
    }

    /**
     * Update parameter limits based on global recommendations
     */
    public function updateParameterLimits($testParameterId)
    {
        return $this->recommendationService->updateParameterLimits($testParameterId);
    }

    /**
     * Get recommended limits for a test parameter
     */
    public function getRecommendedLimits($testParameterName)
    {
        return \App\Repositories\DeltaCheckRecommendationRepository::getRecommendedLimits($testParameterName);
    }

    /**
     * Legacy method for backward compatibility (if needed)
     * This method is kept for any existing code that might still use it
     */
    public function performDeltaCheck(TestResult $currentTestResult)
    {
        // This method is now deprecated in favor of on-demand calculation
        // But we keep it for backward compatibility
        $deltaCheck = $this->calculateDeltaCheck($currentTestResult);
        
        if ($deltaCheck && $deltaCheck['status'] === 'flagged') {
            // Log the violation for audit purposes
            \Log::info('Delta check violation detected', [
                'test_result_id' => $currentTestResult->id,
                'patient_id' => $currentTestResult->laboratoryRequest->patient_id,
                'test_id' => $currentTestResult->test_id,
                'test_parameter_id' => $currentTestResult->test_parameter_id,
                'current_value' => $deltaCheck['current_value'],
                'previous_value' => $deltaCheck['previous_value'],
                'percentage_difference' => $deltaCheck['percentage_difference'],
                'remarks' => $deltaCheck['remarks'],
            ]);
        }
    }
}
