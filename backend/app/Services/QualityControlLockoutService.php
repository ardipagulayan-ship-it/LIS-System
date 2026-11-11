<?php

namespace App\Services;

use App\Models\QualityControlResult;
use App\Models\QualityControlTestParameter;
use App\Models\QualityControl;
use App\Models\Equipment;
use App\Models\TestResult;
use App\Models\CorrectiveAction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service to manage QC lockout enforcement for patient results.
 * Prevents release of results when QC is out of control.
 */
class QualityControlLockoutService
{
    /**
     * Check if QC is in control for a given test parameter on equipment.
     * Returns the most recent QC status for the parameter.
     * 
     * @param int $equipmentId
     * @param int $testParameterId
     * @return array ['is_in_control' => bool, 'last_qc_result' => QualityControlResult|null, 'violations' => array]
     */
    public function checkQCStatus(int $equipmentId, int $testParameterId): array
    {
        // Find the QC test parameter for this equipment and test parameter
        $qcTestParameter = QualityControlTestParameter::whereHas('qualityControl', function ($query) use ($equipmentId) {
                $query->where('equipment_id', $equipmentId);
            })
            ->whereHas('qualityControl.testParameters', function ($query) use ($testParameterId) {
                $query->where('test_parameters.id', $testParameterId);
            })
            ->first();

        if (!$qcTestParameter) {
            // No QC configured for this test/equipment - allow release
            return ['is_in_control' => true, 'last_qc_result' => null, 'violations' => []];
        }

        // Get the most recent QC result
        $lastQCResult = QualityControlResult::where('quality_control_test_parameter_id', $qcTestParameter->id)
            ->orderBy('run_date', 'desc')
            ->orderBy('run_time', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastQCResult) {
            // No QC results yet - allow release
            return ['is_in_control' => true, 'last_qc_result' => null, 'violations' => []];
        }

        // Check if QC is valid
        $isValid = $lastQCResult->is_valid;
        $violations = $lastQCResult->westgard_violations ?? [];

        return [
            'is_in_control' => $isValid,
            'last_qc_result' => $lastQCResult,
            'violations' => $violations,
        ];
    }

    /**
     * Apply QC lockout to patient test results based on QC status.
     * This should be called when attempting to release or validate results.
     * 
     * @param int $laboratoryRequestId
     * @param int $equipmentId
     * @param array $testParameterIds Array of test parameter IDs to check
     * @return array ['locked' => bool, 'locked_parameters' => array, 'messages' => array]
     */
    public function applyLockoutToResults(int $laboratoryRequestId, int $equipmentId, array $testParameterIds): array
    {
        $lockedParameters = [];
        $messages = [];

        foreach ($testParameterIds as $testParameterId) {
            $qcStatus = $this->checkQCStatus($equipmentId, $testParameterId);

            if (!$qcStatus['is_in_control']) {
                $violations = implode(', ', $qcStatus['violations']);
                $messages[] = "QC is out of control for parameter ID {$testParameterId}. Violations: {$violations}";

                // Mark all test results for this parameter as locked
                TestResult::where('laboratory_request_id', $laboratoryRequestId)
                    ->where('test_parameter_id', $testParameterId)
                    ->update([
                        'qc_lockout_active' => true,
                        'qc_lockout_date' => now(),
                        'requires_review' => true,
                    ]);

                $lockedParameters[] = $testParameterId;
            }
        }

        return [
            'locked' => !empty($lockedParameters),
            'locked_parameters' => $lockedParameters,
            'messages' => $messages,
        ];
    }

    /**
     * Release QC lockout after corrective action is completed and new QC passes.
     * 
     * @param int $equipmentId
     * @param int $testParameterId
     * @param int|null $correctiveActionId
     * @return bool Success status
     */
    public function releaseLockout(int $equipmentId, int $testParameterId, ?int $correctiveActionId = null): bool
    {
        DB::beginTransaction();
        try {
            // Check current QC status
            $qcStatus = $this->checkQCStatus($equipmentId, $testParameterId);

            if (!$qcStatus['is_in_control']) {
                DB::rollBack();
                return false; // QC still not in control
            }

            // Release lockout on all affected test results
            $updateQuery = TestResult::where('test_parameter_id', $testParameterId)
                ->where('qc_lockout_active', true);

            if ($correctiveActionId) {
                $updateQuery->update([
                    'qc_lockout_active' => false,
                    'qc_corrective_action_id' => $correctiveActionId,
                ]);
            } else {
                $updateQuery->update([
                    'qc_lockout_active' => false,
                ]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Get locked test results that require review due to QC issues.
     * 
     * @param int|null $testParameterId Optional filter by test parameter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLockedResults(?int $testParameterId = null)
    {
        $query = TestResult::where('qc_lockout_active', true)
            ->with(['testParameter', 'laboratoryRequest', 'equipment']);

        if ($testParameterId) {
            $query->where('test_parameter_id', $testParameterId);
        }

        return $query->get();
    }

    /**
     * Summarize equipment QC status for a given test category.
     * Returns per-equipment hold status and latest violations snapshot.
     *
     * @param int $testCategoryId
     * @return array
     */
    public function summarizeEquipmentForCategory(int $testCategoryId): array
    {
        $equipment = Equipment::where('test_category_id', $testCategoryId)->get();

        $summary = [
            'total_equipment' => $equipment->count(),
            'on_hold_count' => 0,
            'equipment' => []
        ];

        foreach ($equipment as $eq) {
            // Get all QC test parameters configured for this equipment
            $qcParameters = QualityControlTestParameter::whereHas('qualityControl', function ($q) use ($eq) {
                    $q->where('equipment_id', $eq->id);
                })
                ->pluck('id');

            $isOnHold = false;
            $violationsList = [];

            if ($qcParameters->isNotEmpty()) {
                // For each parameter, look at the most recent QC result
                $latestResults = QualityControlResult::whereIn('quality_control_test_parameter_id', $qcParameters)
                    ->select('quality_control_test_parameter_id', 'is_valid', 'westgard_violations', 'run_date', 'run_time', 'id')
                    ->orderBy('run_date', 'desc')
                    ->orderBy('run_time', 'desc')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->groupBy('quality_control_test_parameter_id')
                    ->map->first();

                foreach ($latestResults as $result) {
                    if (!$result->is_valid) {
                        $isOnHold = true;
                        $v = $result->westgard_violations ?? [];
                        if (is_array($v) && !empty($v)) {
                            $violationsList = array_values(array_unique(array_merge($violationsList, $v)));
                        }
                    }
                }
            }

            if ($isOnHold) {
                $summary['on_hold_count'] += 1;
            }

            $summary['equipment'][] = [
                'id' => $eq->id,
                'name' => $eq->manufacturer . ' - ' . $eq->model,
                'on_hold' => $isOnHold,
                'violations' => $violationsList,
            ];
        }

        return $summary;
    }

    /**
     * Get list of locked patient test results for a specific equipment.
     */
    public function getLockedResultsByEquipment(int $equipmentId)
    {
        return TestResult::where('qc_lockout_active', true)
            ->where('equipment_id', $equipmentId)
            ->with(['testParameter', 'laboratoryRequest', 'equipment'])
            ->get();
    }
}

