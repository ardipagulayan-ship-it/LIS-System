<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaboratoryRequest;
use App\Models\Patient; // Needed for helpers
use App\Models\Doctor;  // Needed for helpers
use App\Models\Test;    // Needed for helpers
use App\Models\TestResult;
use App\Models\User;
use App\Models\RuleDecisionAudit;
use App\Models\SystemSetting;
use App\Models\CdsCorrelationRule;
use App\Models\LaboratoryRequestTest;
use App\Services\DeltaCheckService;
use App\Services\CdsRuleEngine;
use App\Services\QualityControlLockoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // For transactions
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LaboratoryRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LaboratoryRequest::with([
            'patient', 
            'doctor', 
            'tests.testCategory', // Keep existing test relationship
            'ward',              // Load new relationship
            'room',              // Load new relationship
            'patientType',       // Load new relationship
            'creator'            // Load new relationship (user who created)
        ]);

        // --- Filtering --- 
        
        // Existing Search (adapt if needed)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                  ->orWhere('sample_id', 'like', "%{$searchTerm}%") // Search new field
                  ->orWhere('referring_doctor', 'like', "%{$searchTerm}%") // Search new field
                  ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
                      $patientQuery->where('last_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('first_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('middle_name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('doctor', function ($doctorQuery) use ($searchTerm) {
                      $doctorQuery->where('name', 'like', "%{$searchTerm}%");
                  });
                  // Add more search criteria for new fields if needed (e.g., Ward Name)
            });
        }

        // Status Filter (Removed)
        /*
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        */
       
        // Priority Level Filter (New)
        if ($request->filled('priority_level')) {
            $query->where('priority_level', $request->input('priority_level'));
        }
       
        // Patient Type Filter (New)
        if ($request->filled('patient_type_id')) {
            $query->where('patient_type_id', $request->input('patient_type_id'));
        }

        // Patient Filter
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }
       
        // Ward Filter (New)
        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->input('ward_id'));
        }

        // Date Range Filter (Keep existing)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date') . ' 00:00:00';
            $endDate = $request->input('end_date') . ' 23:59:59';
            $query->whereBetween('request_date', [$startDate, $endDate]);
        }

        // --- Sorting --- (Keep existing or adapt)
        $query->orderBy('request_date', 'desc');

        // --- Pagination --- (Keep existing)
        $perPage = $request->input('per_page', 10);
        $requests = $query->paginate($perPage);

        return response()->json($requests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Inline Validation for new schema
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'request_date' => 'sometimes|date|nullable',
            'sample_id' => 'nullable|string|max:255',
            'ward_id' => 'nullable|integer|exists:wards,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'referring_doctor' => 'nullable|string|max:255',
            'priority_level' => 'nullable|string|max:50', // Consider stricter validation later (e.g., in:[...])
            'patient_type_id' => 'nullable|integer|exists:patient_types,id',
            'test_items' => 'required|array|min:1',
            'test_items.*' => 'required|integer|exists:tests,id',
            // Removed validation for status, total_amount
        ]);

        // Use a transaction to ensure atomicity
        $laboratoryRequest = DB::transaction(function () use ($validated, $request) {
            
            // Prepare data for creation
            $requestData = $validated;
            $requestData['request_date'] = $validated['request_date'] ?? now();
            $requestData['created_by'] = Auth::id(); // Get ID of authenticated user

            // Remove test_items from data used to create the request itself
            unset($requestData['test_items']);

            // Create the main request record
            $labRequest = LaboratoryRequest::create($requestData);

            // Fetch tests to get their current prices
            $tests = Test::findMany($validated['test_items']);
            $pivotData = [];

            foreach ($tests as $test) {
                $price = $test->price ?? 0; // Get current price
                // Set default item_status and price for pivot
                $pivotData[$test->id] = [
                    'item_status' => 'Pending', // Default status for new items
                    'price_at_request' => $price
                ];
            }

            // Attach tests with pivot data
            $labRequest->tests()->attach($pivotData);

            // No need to update total_amount anymore
            // $labRequest->total_amount = $totalAmount;
            // $labRequest->save();

            return $labRequest; // Return the created request from the transaction
        });

        // Eager load relationships for the response
        $laboratoryRequest->load([
            'patient',
            'doctor',
            'tests.testCategory',
            'ward',
            'room',
            'patientType',
            'creator'
        ]);

        return response()->json($laboratoryRequest, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LaboratoryRequest $laboratoryRequest)
    {
        // Eager load everything needed for detailed view/edit
         $laboratoryRequest->load([
             'patient',
             'doctor',
             'tests.testCategory',
             'ward',
             'room',
             'patientType',
             'creator'
             // Add any other relationships needed for the detail view
         ]);
         return response()->json($laboratoryRequest);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LaboratoryRequest $laboratoryRequest)
    {
         // Inline validation for updatable fields
         $validated = $request->validate([
             // 'patient_id' => 'sometimes|required|exists:patients,id', // Usually not updated
             'doctor_id' => 'sometimes|nullable|exists:doctors,id',
             'request_date' => 'sometimes|date|nullable',
             'sample_id' => 'sometimes|nullable|string|max:255',
             'ward_id' => 'sometimes|nullable|integer|exists:wards,id',
             'room_id' => 'sometimes|nullable|integer|exists:rooms,id',
             'referring_doctor' => 'sometimes|nullable|string|max:255',
             'priority_level' => 'sometimes|nullable|string|max:50',
             'patient_type_id' => 'sometimes|nullable|integer|exists:patient_types,id',
             // 'created_by' => 'sometimes|required|exists:users,id', // Usually not updated
             // 'status' => 'sometimes|required|in:Pending,In Progress,Completed,Cancelled', // Removed
             'test_items' => 'sometimes|array|min:1',
             'test_items.*' => 'required|integer|exists:tests,id',
         ]);

         // Partial edit: only Pending lines may be removed. Every other line
         // (in-process, completed, results printed, cancelled…) keeps its
         // pivot state and must stay in `test_items`. Orders that already
         // contain Completed/Results Printed lines are fully read-only: their
         // test list may not change at all (no adds, no removals).
         if ($request->has('test_items')) {
             $laboratoryRequest->loadMissing('tests');
             $desiredIds = collect($validated['test_items'] ?? [])->unique()->values();
             $existingIds = $laboratoryRequest->tests->pluck('id');

             $isReadonlyOrder = $laboratoryRequest->tests->contains(function ($test) {
                 $status = strtolower($test->pivot->item_status ?? 'Pending');
                 return in_array($status, ['completed', 'result printed', 'results printed'], true);
             });
             if ($isReadonlyOrder) {
                 $missingIds = $existingIds->diff($desiredIds)->values();
                 $extraIds = $desiredIds->diff($existingIds)->values();
                 if ($missingIds->isNotEmpty() || $extraIds->isNotEmpty()) {
                     return response()->json([
                         'message' => 'This request has Completed/Results Printed tests and is read-only. Tests cannot be added or removed.',
                     ], 422);
                 }
             }

             $lockedIds = $laboratoryRequest->tests->filter(function ($test) {
                 return strtolower($test->pivot->item_status ?? 'Pending') !== 'pending';
             })->pluck('id')->all();

             $missingLocked = collect($lockedIds)->diff($desiredIds)->values();
             if ($missingLocked->isNotEmpty()) {
                 return response()->json([
                     'message' => 'Only Pending tests can be removed. In-process, Completed, Results Printed and Cancelled tests are locked.',
                 ], 422);
             }

             // Only Pending lines may be detached; every other existing line
             // (in-process or locked) is force-kept regardless of the payload.
             $detachableIds = $laboratoryRequest->tests->filter(function ($test) {
                 return ($test->pivot->item_status ?? 'Pending') === 'Pending';
             })->pluck('id');
             $idsToDetach = $detachableIds->diff($desiredIds)->values();
             $idsToAttach = $desiredIds->diff($existingIds)->values();

             if ($idsToDetach->isNotEmpty()) {
                 $laboratoryRequest->tests()->detach($idsToDetach->all());
             }

             if ($idsToAttach->isNotEmpty()) {
                 $testsToAttach = Test::findMany($idsToAttach->all());
                 $pivotData = [];
                 foreach ($testsToAttach as $test) {
                     $pivotData[$test->id] = [
                         'item_status' => 'Pending',
                         'price_at_request' => $test->price ?? 0,
                     ];
                 }
                 $laboratoryRequest->tests()->attach($pivotData);
             }

             unset($validated['test_items']);
         }

         $laboratoryRequest->update($validated);

         // Reload relationships for the response
         $laboratoryRequest->load([
            'patient',
            'doctor',
            'tests.testCategory',
            'ward',
            'room',
            'patientType',
            'creator'
        ]);

         return response()->json($laboratoryRequest);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LaboratoryRequest $laboratoryRequest)
    {
        // Note: Cascading deletes for pivot table are handled by DB constraints
        $laboratoryRequest->delete();
        return response()->json(null, 204);
    }

    // --- Helper methods for Frontend Forms ---

    public function getPatients(Request $request)
    {
        // Simple search for patient selection
        $query = Patient::query()->select('id', 'name');
         if ($request->filled('search')) {
             $searchTerm = '%' . $request->input('search') . '%';
             $query->where('name', 'like', $searchTerm);
         }
        return response()->json($query->limit(20)->get()); // Limit results for dropdown performance
    }

    /**
     * Updates the status of all tests in a laboratory request.
     * Used for batch status updates like "Print & Release"
     */
    public function updateRequestStatus(Request $request, $laboratoryRequestId)
    {
        // Validate the request
        $validated = $request->validate([
            'status' => 'required|string|in:Result Printed,Result Validated,Processing Results',
            'test_id' => 'sometimes|nullable|integer|exists:tests,id',
            'test_category_id' => 'sometimes|nullable|integer|exists:test_categories,id',
            'supervisor_id' => 'sometimes|nullable|integer|exists:users,id'
        ]);

        try {
            // Find the laboratory request
            $laboratoryRequest = LaboratoryRequest::findOrFail($laboratoryRequestId);
            
            // Guard: if all tests are Rejected, make the request read-only
            $totalTests = $laboratoryRequest->tests()->count();
            $nonRejected = $laboratoryRequest->tests()->wherePivot('item_status', '<>', 'Rejected')->count();
            if ($totalTests > 0 && $nonRejected === 0) {
                return response()->json([
                    'message' => 'Request is fully rejected and read-only. No status updates allowed.',
                    'error_type' => 'request_rejected'
                ], 409);
            }
            
            // Use a transaction to ensure all test statuses are updated together
            DB::beginTransaction();
            
            // Start with a base query for tests
            $testsQuery = $laboratoryRequest->tests();
            
            // Apply filters if provided
            if ($request->filled('test_id')) {
                // If test_id is provided, only update that specific test
                $testsQuery->where('tests.id', $validated['test_id']);
            } else if ($request->filled('test_category_id')) {
                // If test_category_id is provided, only update tests in that category
                $testsQuery->where('tests.test_category_id', $validated['test_category_id']);
            }
            
            $testsQuery->wherePivotNotIn('item_status', ['Rejected']);
            
            // Get the filtered tests
            $tests = $testsQuery->get();
            
            // Log what we're about to update
            Log::info('Updating test statuses for laboratory request', [
                'laboratory_request_id' => $laboratoryRequestId,
                'status' => $validated['status'],
                'test_id_filter' => $request->input('test_id'),
                'category_filter' => $request->input('test_category_id'),
                'affected_test_ids' => $tests->pluck('id')->toArray()
            ]);

            $requiresGuards = in_array($validated['status'], ['Result Printed', 'Result Validated'], true);
            $testIds = $tests->pluck('id')->toArray();

            $testResults = collect();
            if (!empty($testIds)) {
                $testResults = TestResult::with(['laboratoryRequest', 'test', 'testParameter'])
                    ->where('laboratory_request_id', $laboratoryRequestId)
                    ->whereIn('test_id', $testIds)
                    ->whereNotNull('test_parameter_id')
                    ->get();
            }

            if ($requiresGuards) {
                $qcPolicy = $this->getQcPolicy();
                $qcBlock = $this->evaluateQcLockout($testResults, $qcPolicy, $request);
                if ($qcBlock) {
                    DB::rollBack();
                    return response()->json($qcBlock, $qcBlock['status_code'] ?? 403);
                }

                $deltaBlock = $this->evaluateDeltaCheckHold($laboratoryRequest, $testResults);
                if ($deltaBlock) {
                    DB::rollBack();
                    return response()->json($deltaBlock, 409);
                }

                $cdsBlock = $this->evaluateCdsBlock($laboratoryRequest, $testResults);
                if ($cdsBlock) {
                    DB::rollBack();
                    return response()->json($cdsBlock, 409);
                }
            }
            
            // Update the selected tests with the new status
            foreach ($tests as $test) {
                $laboratoryRequest->tests()->updateExistingPivot($test->id, [
                    'item_status' => $validated['status']
                ]);
            }
            
            DB::commit();

            // After successful commit, record the final pass in audit trail
            if ($requiresGuards) {
                $this->recordRuleDecision([
                    'rule_type' => 'FINAL_RELEASE',
                    'decision' => 'pass',
                    'message' => 'Status updated to ' . $validated['status'],
                    'laboratory_request_id' => $laboratoryRequest->id,
                    'user_id' => Auth::id(),
                    'context' => ['status' => $validated['status']]
                ]);
            }
            
            // Return success response with the updated request
            $laboratoryRequest->load(['patient', 'doctor', 'tests.testCategory', 'ward', 'room', 'patientType']);
            return response()->json([
                'message' => 'Laboratory request status updated successfully',
                'request' => $laboratoryRequest
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update laboratory request status', [
                'laboratory_request_id' => $laboratoryRequestId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Failed to update laboratory request status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function acknowledgeViolations(Request $request, $laboratoryRequestId)
    {
        $validated = $request->validate([
            'cds_alerts' => 'sometimes|array',
            'cds_alerts.*.id' => 'sometimes|nullable|integer',
            'cds_alerts.*.rule_name' => 'sometimes|nullable|string',
            'cds_alerts.*.alert_message' => 'sometimes|nullable|string',
            'delta_checks' => 'sometimes|array',
            'delta_checks.*.id' => 'sometimes|nullable|integer',
            'delta_checks.*.message' => 'sometimes|nullable|string',
            'critical_values' => 'sometimes|array',
            'critical_values.*.parameter_id' => 'sometimes|nullable|integer',
            'critical_values.*.message' => 'sometimes|nullable|string',
            'action_taken_code' => 'required|string',
            'action_taken_label' => 'sometimes|nullable|string',
            'action_taken_other' => 'sometimes|nullable|string',
            'action_comment' => 'required|string|min:15',
        ]);

        $laboratoryRequest = LaboratoryRequest::findOrFail($laboratoryRequestId);
        $userId = Auth::id();
        $cdsAlerts = $validated['cds_alerts'] ?? [];
        $deltaChecks = $validated['delta_checks'] ?? [];
        $criticalValues = $validated['critical_values'] ?? [];
        $actionContext = [
            'action_taken_code' => $validated['action_taken_code'] ?? null,
            'action_taken_label' => $validated['action_taken_label'] ?? null,
            'action_taken_other' => $validated['action_taken_other'] ?? null,
            'action_comment' => $validated['action_comment'] ?? null,
        ];

        DB::beginTransaction();
        try {
            $hasCdsAck = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                ->where('rule_type', 'CDS')
                ->where('decision', 'acknowledged')
                ->exists();
            if (!empty($cdsAlerts) && !$hasCdsAck) {
                $alerts = [];
                foreach ($cdsAlerts as $alert) {
                    $alerts[] = [
                        'rule_id' => $alert['id'] ?? null,
                        'rule_name' => $alert['rule_name'] ?? null,
                        'message' => $alert['alert_message'] ?? null,
                    ];
                }
                $this->recordRuleDecision([
                    'rule_type' => 'CDS',
                    'decision' => 'acknowledged',
                    'message' => 'CDS rules acknowledged',
                    'laboratory_request_id' => $laboratoryRequest->id,
                    'user_id' => $userId,
                    'context' => array_merge($actionContext, [
                        'alerts' => $alerts,
                    ]),
                ]);
            }

            $hasDeltaAck = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                ->where('rule_type', 'DELTA_CHECK')
                ->where('decision', 'acknowledged')
                ->exists();
            if (!empty($deltaChecks) && !$hasDeltaAck) {
                $checks = [];
                foreach ($deltaChecks as $dc) {
                    $checks[] = [
                        'test_result_id' => $dc['test_result_id'] ?? $dc['id'] ?? null,
                        'test_parameter_id' => $dc['test_parameter_id'] ?? null,
                        'message' => $dc['message'] ?? null,
                    ];
                }
                $this->recordRuleDecision([
                    'rule_type' => 'DELTA_CHECK',
                    'decision' => 'acknowledged',
                    'message' => 'Delta checks acknowledged',
                    'laboratory_request_id' => $laboratoryRequest->id,
                    'user_id' => $userId,
                    'context' => array_merge($actionContext, [
                        'delta_checks' => $checks,
                    ]),
                ]);
            }

            $hasCriticalAck = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                ->where('rule_type', 'CRITICAL_VALUE')
                ->where('decision', 'acknowledged')
                ->exists();
            if (!empty($criticalValues) && !$hasCriticalAck) {
                $values = [];
                foreach ($criticalValues as $cv) {
                    $values[] = [
                        'test_parameter_id' => $cv['parameter_id'] ?? null,
                        'message' => $cv['message'] ?? null,
                    ];
                }
                $this->recordRuleDecision([
                    'rule_type' => 'CRITICAL_VALUE',
                    'decision' => 'acknowledged',
                    'message' => 'Critical values acknowledged',
                    'laboratory_request_id' => $laboratoryRequest->id,
                    'user_id' => $userId,
                    'context' => array_merge($actionContext, [
                        'critical_values' => $values,
                    ]),
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Violations acknowledged'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to acknowledge violations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getQcPolicy(): string
    {
        $setting = SystemSetting::where('key', 'qc_policy')->first();
        $value = $setting ? strtolower((string) $setting->value) : 'strict';
        if (!in_array($value, ['strict', 'medium', 'off'], true)) {
            return 'strict';
        }
        return $value;
    }

    private function evaluateQcLockout($testResults, string $policy, Request $request): ?array
    {
        if ($policy === 'off') {
            return null;
        }

        if ($testResults->isEmpty()) {
            return null;
        }

        $qcLockoutService = new QualityControlLockoutService();
        $lockedParameters = [];
        $lockedResultIds = [];

        foreach ($testResults as $result) {
            if ($result->equipment_id && $result->test_parameter_id) {
                $qcStatus = $qcLockoutService->checkQCStatus($result->equipment_id, $result->test_parameter_id);
                if (!$qcStatus['is_in_control']) {
                    $lockedParameters[] = [
                        'equipment_id' => $result->equipment_id,
                        'test_parameter_id' => $result->test_parameter_id,
                        'test_parameter_name' => $result->testParameter->test_name ?? null,
                        'violations' => $qcStatus['violations'] ?? []
                    ];
                    $lockedResultIds[] = $result->id;
                }
            }
        }

        if (empty($lockedParameters)) {
            return null;
        }

        TestResult::whereIn('id', $lockedResultIds)->update([
            'qc_lockout_active' => true,
            'qc_lockout_date' => now(),
            'requires_review' => true
        ]);

        $supervisorId = $request->input('supervisor_id');
        $hasOverride = $this->hasSupervisorOverride($supervisorId);

        if ($policy === 'medium' && $hasOverride) {
            $this->recordRuleDecision([
                'rule_type' => 'QC',
                'decision' => 'override',
                'policy' => $policy,
                'laboratory_request_id' => $testResults->first()->laboratory_request_id ?? null,
                'user_id' => Auth::id(),
                'supervisor_id' => $supervisorId,
                'context' => ['locked_parameters' => $lockedParameters]
            ]);
            return null;
        }

        $this->recordRuleDecision([
            'rule_type' => 'QC',
            'decision' => 'block',
            'policy' => $policy,
            'laboratory_request_id' => $testResults->first()->laboratory_request_id ?? null,
            'user_id' => Auth::id(),
            'supervisor_id' => $supervisorId,
            'context' => ['locked_parameters' => $lockedParameters]
        ]);

        return [
            'message' => 'Quality Control lockout active. Results cannot be released.',
            'locked_parameters' => $lockedParameters,
            'error_type' => 'qc_lockout',
            'status_code' => 403
        ];
    }

    private function hasSupervisorOverride(?int $supervisorId): bool
    {
        if ($supervisorId) {
            $supervisor = User::find($supervisorId);
            if ($supervisor && $supervisor->hasRole('supervisor')) {
                return true;
            }
        }

        $currentUser = Auth::user();
        if ($currentUser && $currentUser->hasRole('supervisor')) {
            return true;
        }

        return false;
    }

    private function evaluateDeltaCheckHold(LaboratoryRequest $laboratoryRequest, $testResults): ?array
    {
        if ($testResults->isEmpty()) {
            return null;
        }

        $deltaService = new DeltaCheckService();
        $flagged = [];

        foreach ($testResults as $result) {
            if ($result->result_value === null) {
                continue;
            }
            $isAcknowledged = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                ->where('rule_type', 'DELTA_CHECK')
                ->where('decision', 'acknowledged')
                ->whereJsonContains('context->flagged_results', ['test_result_id' => $result->id])
                ->exists();
            if ($isAcknowledged) {
                continue;
            }
            $delta = $deltaService->calculateDeltaCheck($result);
            if ($delta && ($delta['status'] ?? null) === 'flagged') {
                $action = strtolower((string) ($delta['action_on_violation'] ?? ''));
                if ($action === 'flag_and_stop' || $action === 'flag and stop') {
                    $flagged[] = [
                        'test_result_id' => $result->id,
                        'test_id' => $result->test_id,
                        'test_parameter_id' => $result->test_parameter_id,
                        'message' => $delta['alert_message'] ?? $delta['remarks'] ?? 'Delta check hold'
                    ];
                }
            }
        }

        if (empty($flagged)) {
            return null;
        }

        $this->recordRuleDecision([
            'rule_type' => 'DELTA_CHECK',
            'decision' => 'block',
            'laboratory_request_id' => $laboratoryRequest->id,
            'user_id' => Auth::id(),
            'context' => ['holds' => $flagged]
        ]);

        return [
            'message' => 'Delta check hold active. Release blocked until acceptance.',
            'holds' => $flagged,
            'error_type' => 'delta_check_hold'
        ];
    }

    private function evaluateCdsBlock(LaboratoryRequest $laboratoryRequest, $testResults): ?array
    {
        $cdsViolation = $this->runCdsChecks($laboratoryRequest, $testResults);
        if ($cdsViolation !== null) {
            // Check if this specific rule has been acknowledged recently for this request
            $isAcknowledged = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                ->where('rule_type', 'CDS')
                ->where('decision', 'acknowledged')
                ->where('rule_id', $cdsViolation['rule']->id ?? null)
                ->exists();

            if ($isAcknowledged) {
                return null; // Acknowledged, so don't block
            }

            $this->recordRuleDecision([
                'rule_type' => 'CDS',
                'decision' => 'block',
                'rule_id' => $cdsViolation['rule']->id ?? null,
                'message' => $cdsViolation['message'] ?? null,
                'laboratory_request_id' => $laboratoryRequest->id,
                'user_id' => Auth::id(),
                'context' => ['action' => $cdsViolation['action'] ?? null]
            ]);
            return [
                'message' => $cdsViolation['message'] ?? 'CDS rule triggered. Review required.',
                'rule' => $cdsViolation['rule'] ?? null,
                'action' => $cdsViolation['action'] ?? 'STOP_AUTO_VERIFY',
                'error_type' => 'cds_block'
            ];
        }

        return null;
    }

    /**
     * Get the audit trail for a laboratory request
     */
    public function getAuditTrail($laboratoryRequestId)
    {
        $audits = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequestId)
            ->with(['user:id,name', 'supervisor:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($audits);
    }

    private function runCdsChecks(LaboratoryRequest $laboratoryRequest, $testResults): ?array
    {
        try {
            $parameterIds = $testResults->pluck('test_parameter_id')->filter()->unique()->values()->toArray();
            if (empty($parameterIds)) {
                return null;
            }

            $rules = CdsCorrelationRule::active()
                ->whereIn('trigger_test_id', $parameterIds)
                ->get();

            if ($rules->isEmpty()) {
                return null;
            }

            $engine = new CdsRuleEngine();
            foreach ($rules as $rule) {
                $correlated = collect(explode(',', (string)$rule->correlated_tests))
                    ->filter()
                    ->map(fn($v) => (int)trim($v))
                    ->values()
                    ->toArray();

                $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $parameterIds)));
                $fires = $engine->evaluate((array)$rule->rule_logic_json, $laboratoryRequest, $needed);
                if ($fires) {
                    if ($rule->action_type === 'STOP_AUTO_VERIFY') {
                        return [
                            'action' => 'STOP_AUTO_VERIFY',
                            'message' => $rule->alert_message ?? 'CDS rule triggered. Review required.',
                            'rule' => $rule,
                        ];
                    }
                    if ($rule->action_type === 'REFLEX_TEST') {
                        return [
                            'action' => 'REFLEX_TEST',
                            'message' => $rule->alert_message ?? 'CDS suggests a reflex test.',
                            'rule' => $rule,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('CDS Engine error: '.$e->getMessage());
            return null;
        }
        return null;
    }

    private function recordRuleDecision(array $data): void
    {
        RuleDecisionAudit::create([
            'rule_type' => $data['rule_type'] ?? null,
            'decision' => $data['decision'] ?? null,
            'message' => $data['message'] ?? null,
            'rule_id' => $data['rule_id'] ?? null,
            'policy' => $data['policy'] ?? null,
            'laboratory_request_id' => $data['laboratory_request_id'] ?? null,
            'test_result_id' => $data['test_result_id'] ?? null,
            'test_parameter_id' => $data['test_parameter_id'] ?? null,
            'test_id' => $data['test_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'context' => $data['context'] ?? null,
        ]);
    }

    public function getDoctors()
    {
        return response()->json(Doctor::orderBy('name')->get(['id', 'name']));
    }

    public function getTests($category = null)
    {
         $query = Test::query()->orderBy('name');

         // Apply category filter if provided
         $query->when($category, function ($q, $category) {
             return $q->where('test_category_id', $category);
         });

         // Select relevant columns (assuming price is also needed)
         return response()->json($query->get(['id', 'name', 'price', 'test_category_id']));
         // Ensure 'category' is included if the frontend needs it for filtering (Option 2)
         // If only using Option 1 (backend filtering), you might not need to return 'category'
    }

    /**
     * Generate the next sample ID
     *
     * @return JsonResponse
     */
    public function generateSampleId(): JsonResponse
    {
        try {
            // Get the latest sample ID from the database
            $latestRequest = LaboratoryRequest::whereNotNull('sample_id')->orderBy('id', 'desc')->first();
            
            // If no requests exist, start with 1
            $nextId = 1;
            if ($latestRequest) {
                // Extract the numeric part and increment
                $numericPart = (int) preg_replace('/[^0-9]/', '', $latestRequest->sample_id);
                $nextId = $numericPart + 1;
            }
            
            // Format the ID with leading zeros (6 digits)
            $sampleId = str_pad($nextId, 6, '0', STR_PAD_LEFT);
            
            return response()->json(['sample_id' => $sampleId]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating sample ID',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept flagged delta check result - Validate original result
     * Stores original flagged value and acceptance comment in history
     */
    public function acceptDeltaCheckResult(Request $request, $laboratoryRequestId)
    {
        $validated = $request->validate([
            'reviewer_id' => 'nullable|integer|exists:users,id',
            'reviewed_at' => 'nullable|date',
            'investigation_checklist' => 'required|array|min:1',
            'accept_reason' => 'required|string',
            'accept_reason_label' => 'sometimes|nullable|string',
            'rationale' => 'required|string|min:50',
            'preanalytical_status' => 'required|string',
            'flagged_results' => 'required|array|min:1',
            'flagged_results.*.test_result_id' => 'required|exists:test_results,id',
            'flagged_results.*.test_parameter_id' => 'required|exists:test_parameters,id',
        ]);

        try {
            DB::beginTransaction();

            $laboratoryRequest = LaboratoryRequest::findOrFail($laboratoryRequestId);
            $userId = Auth::id();
            $reviewerId = $validated['reviewer_id'] ?? $userId;
            $reviewedAt = isset($validated['reviewed_at']) ? now()->parse($validated['reviewed_at']) : now();

            $flaggedResults = [];
            foreach ($validated['flagged_results'] as $flaggedResult) {
                $testResult = \App\Models\TestResult::with('test')->findOrFail($flaggedResult['test_result_id']);
                $flaggedResults[] = [
                    'test_result_id' => $testResult->id,
                    'test_parameter_id' => $testResult->test_parameter_id,
                    'test_id' => $testResult->test_id,
                    'current_value' => $testResult->result_value,
                ];
            }

            $this->recordRuleDecision([
                'rule_type' => 'DELTA_CHECK',
                'decision' => 'acknowledged',
                'message' => 'Delta check accepted',
                'laboratory_request_id' => $laboratoryRequest->id,
                'user_id' => $reviewerId,
                'context' => [
                    'reviewed_at' => $reviewedAt->format('Y-m-d H:i:s'),
                    'investigation_checklist' => $validated['investigation_checklist'] ?? [],
                    'accept_reason' => $validated['accept_reason'],
                    'action_taken_code' => $validated['accept_reason'] ?? null,
                    'action_taken_label' => $validated['accept_reason_label'] ?? $validated['accept_reason'] ?? null,
                    'action_comment' => $validated['rationale'] ?? null,
                    'rationale' => $validated['rationale'],
                    'preanalytical_status' => $validated['preanalytical_status'],
                    'flagged_results' => $flaggedResults,
                ],
            ]);

            DB::commit();

            Log::info('Delta check result accepted', [
                'laboratory_request_id' => $laboratoryRequestId,
                'user_id' => $userId,
                'flagged_results_count' => count($validated['flagged_results'])
            ]);

            return response()->json([
                'message' => 'Delta check accepted. You may proceed with release.',
                'request' => $laboratoryRequest->load(['patient', 'tests'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error accepting delta check result', [
                'error' => $e->getMessage(),
                'laboratory_request_id' => $laboratoryRequestId
            ]);
            return response()->json([
                'message' => 'Failed to accept result: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject flagged delta check result - Cancel original result
     * Stores rejected result and rejection comment in history
     */
    public function rejectDeltaCheckResult(Request $request, $laboratoryRequestId)
    {
        $validated = $request->validate([
            'reviewer_id' => 'nullable|integer|exists:users,id',
            'reviewed_at' => 'nullable|date',
            'investigation_checklist' => 'required|array|min:1',
            'root_cause' => 'required|string',
            'action_taken' => 'required|string',
            'action_taken_label' => 'sometimes|nullable|string',
            'rejection_comment' => 'required|string|min:50',
            'original_value_canceled' => 'nullable|boolean',
            'flagged_results' => 'required|array|min:1',
            'flagged_results.*.test_result_id' => 'required|exists:test_results,id',
            'flagged_results.*.test_parameter_id' => 'required|exists:test_parameters,id',
        ]);

        try {
            DB::beginTransaction();

            $laboratoryRequest = LaboratoryRequest::findOrFail($laboratoryRequestId);
            $userId = Auth::id();
            $reviewerId = $validated['reviewer_id'] ?? $userId;
            $reviewedAt = isset($validated['reviewed_at']) ? now()->parse($validated['reviewed_at']) : now();

            $flaggedResults = [];
            foreach ($validated['flagged_results'] as $flaggedResult) {
                $testResult = \App\Models\TestResult::with('test')->findOrFail($flaggedResult['test_result_id']);
                $flaggedResults[] = [
                    'test_result_id' => $testResult->id,
                    'test_parameter_id' => $testResult->test_parameter_id,
                    'test_id' => $testResult->test_id,
                    'current_value' => $testResult->result_value,
                ];
                
                // Store original flagged value and rejection comment in result_comment
                // Format: "DELTA_CHECK_HISTORY: Original Value: {value} | Rejected: {date} | Reason: {reason}"
                $originalValue = $testResult->result_value;
                $checklist = implode(', ', $validated['investigation_checklist'] ?? []);
                $root = $validated['root_cause'];
                $action = $validated['action_taken'];
                $detail = $validated['rejection_comment'];
                $historyComment =
                    "DELTA_CHECK_HISTORY: Original Flagged Value: {$originalValue} | Rejected: " . $reviewedAt->format('Y-m-d H:i:s') .
                    " | Reviewer ID: {$reviewerId} | Checklist: {$checklist} | Root Cause: {$root} | Action: {$action}\nComment: {$detail}";
                
                // Append to existing comment if any
                $existingComment = $testResult->result_comment ?? '';
                $testResult->result_comment = $existingComment ? "{$existingComment}\n{$historyComment}" : $historyComment;
                
                // Mark result as cancelled (using status = 'pending' with special handling, or we could add 'cancelled' to enum)
                // For now, we'll clear the result value and mark with comment
                $testResult->result_value = null;
                $testResult->status = 'pending';
                $testResult->save();
            }

            $this->recordRuleDecision([
                'rule_type' => 'DELTA_CHECK',
                'decision' => 'rejected',
                'message' => 'Delta check rejected',
                'laboratory_request_id' => $laboratoryRequest->id,
                'user_id' => $reviewerId,
                'context' => [
                    'reviewed_at' => $reviewedAt->format('Y-m-d H:i:s'),
                    'investigation_checklist' => $validated['investigation_checklist'] ?? [],
                    'root_cause' => $validated['root_cause'],
                    'action_taken_code' => $validated['action_taken'] ?? null,
                    'action_taken_label' => $validated['action_taken_label'] ?? $validated['action_taken'] ?? null,
                    'action_comment' => $validated['rejection_comment'] ?? null,
                    'rejection_comment' => $validated['rejection_comment'] ?? null,
                    'original_value_canceled' => $validated['original_value_canceled'] ?? null,
                    'flagged_results' => $flaggedResults,
                ],
            ]);

            // Update test status to indicate cancellation
            foreach ($laboratoryRequest->tests as $test) {
                $test->pivot->update(['item_status' => 'Processing Results']);
            }

            DB::commit();

            Log::info('Delta check result rejected', [
                'laboratory_request_id' => $laboratoryRequestId,
                'user_id' => $userId,
                'flagged_results_count' => count($validated['flagged_results'])
            ]);

            return response()->json([
                'message' => 'Result rejected and cancelled successfully',
                'request' => $laboratoryRequest->load(['patient', 'tests'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting delta check result', [
                'error' => $e->getMessage(),
                'laboratory_request_id' => $laboratoryRequestId
            ]);
            return response()->json([
                'message' => 'Failed to reject result: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectRequest(Request $request, $laboratoryRequestId)
    {
        $validated = $request->validate([
            'scope' => 'required|string|in:request,tests',
            'test_ids' => 'sometimes|array',
            'test_ids.*' => 'integer|exists:tests,id',
            'selected_results' => 'sometimes|array',
            'selected_results.*.test_result_id' => 'required|exists:test_results,id',
            'selected_results.*.test_parameter_id' => 'sometimes|nullable|exists:test_parameters,id',
            'reviewer_id' => 'nullable|integer|exists:users,id',
            'reviewed_at' => 'nullable|date',
            'investigation_checklist' => 'required|array|min:1',
            'root_cause' => 'required|string',
            'action_taken' => 'required|string',
            'action_taken_label' => 'sometimes|nullable|string',
            'rejection_comment' => 'required|string|min:50',
            'original_value_canceled' => 'nullable|boolean',
        ]);

        $scope = $validated['scope'];

        if ($scope === 'tests' && empty($validated['test_ids'])) {
            return response()->json(['message' => 'Please select at least one test to reject.'], 422);
        }

        try {
            DB::beginTransaction();

            $laboratoryRequest = LaboratoryRequest::findOrFail($laboratoryRequestId);
            $userId = Auth::id();
            $reviewerId = $validated['reviewer_id'] ?? $userId;
            $reviewedAt = isset($validated['reviewed_at']) ? now()->parse($validated['reviewed_at']) : now();

            $targetTestIds = [];
            if ($scope === 'request') {
                $targetTestIds = $laboratoryRequest->tests()->pluck('tests.id')->toArray();
            } else {
                $targetTestIds = array_values(array_unique($validated['test_ids'] ?? []));
            }

            foreach ($targetTestIds as $testId) {
                $pivot = LaboratoryRequestTest::where('laboratory_request_id', $laboratoryRequestId)
                    ->where('test_id', $testId)
                    ->first();

                if ($pivot) {
                    $pivot->item_status = 'Rejected';
                    $pivot->rejected_by = $reviewerId;
                    $pivot->rejected_at = $reviewedAt->toDateTimeString();
                    $pivot->rejection_reason = isset($validated['rejection_comment'])
                        ? Str::limit($validated['rejection_comment'], 255)
                        : null;
                    $pivot->save();
                } else {
                    // Fallback: ensure pivot row exists
                    $laboratoryRequest->tests()->updateExistingPivot($testId, [
                        'item_status' => 'Rejected',
                        'rejected_by' => $reviewerId,
                        'rejected_at' => $reviewedAt->toDateTimeString(),
                        'rejection_reason' => isset($validated['rejection_comment'])
                            ? Str::limit($validated['rejection_comment'], 255)
                            : null,
                    ]);
                }
            }

            $flaggedResults = [];
            $selectedResults = $validated['selected_results'] ?? [];
            foreach ($selectedResults as $selected) {
                $testResult = TestResult::findOrFail($selected['test_result_id']);
                $flaggedResults[] = [
                    'test_result_id' => $testResult->id,
                    'test_parameter_id' => $testResult->test_parameter_id,
                    'test_id' => $testResult->test_id,
                    'current_value' => $testResult->result_value,
                ];

                $checklist = implode(', ', $validated['investigation_checklist'] ?? []);
                $root = $validated['root_cause'];
                $action = $validated['action_taken'];
                $detail = $validated['rejection_comment'];
                $historyComment =
                    "REJECTION_HISTORY: Rejected: " . $reviewedAt->format('Y-m-d H:i:s') .
                    " | Reviewer ID: {$reviewerId} | Checklist: {$checklist} | Root Cause: {$root} | Action: {$action}\nComment: {$detail}";

                $existingComment = $testResult->result_comment ?? '';
                $testResult->result_comment = $existingComment ? "{$existingComment}\n{$historyComment}" : $historyComment;

                if (!empty($validated['original_value_canceled'])) {
                    $testResult->result_value = null;
                }

                $testResult->save();
            }

            $this->recordRuleDecision([
                'rule_type' => 'REJECTION',
                'decision' => 'rejected',
                'message' => $scope === 'request' ? 'Request rejected' : 'Tests rejected',
                'laboratory_request_id' => $laboratoryRequest->id,
                'user_id' => $reviewerId,
                'context' => [
                    'reviewed_at' => $reviewedAt->format('Y-m-d H:i:s'),
                    'investigation_checklist' => $validated['investigation_checklist'] ?? [],
                    'root_cause' => $validated['root_cause'],
                    'action_taken_code' => $validated['action_taken'] ?? null,
                    'action_taken_label' => $validated['action_taken_label'] ?? $validated['action_taken'] ?? null,
                    'action_comment' => $validated['rejection_comment'] ?? null,
                    'rejection_comment' => $validated['rejection_comment'] ?? null,
                    'original_value_canceled' => $validated['original_value_canceled'] ?? null,
                    'scope' => $scope,
                    'test_ids' => $targetTestIds,
                    'selected_results' => $flaggedResults,
                ],
            ]);

            DB::commit();

            return response()->json([
                'message' => $scope === 'request' ? 'Request rejected successfully' : 'Selected tests rejected successfully',
                'request' => $laboratoryRequest->load(['patient', 'tests'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting request', [
                'error' => $e->getMessage(),
                'laboratory_request_id' => $laboratoryRequestId
            ]);
            return response()->json([
                'message' => 'Failed to reject request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate re-run for flagged delta check result
     * Marks request as "For Re-run" or "Pending Repeat Analysis"
     */
    public function rerunDeltaCheckResult(Request $request, $laboratoryRequestId)
    {
        $validated = $request->validate([
            'flagged_results' => 'required|array|min:1',
            'flagged_results.*.test_result_id' => 'required|exists:test_results,id',
            'flagged_results.*.test_parameter_id' => 'required|exists:test_parameters,id',
        ]);

        try {
            DB::beginTransaction();

            $laboratoryRequest = LaboratoryRequest::findOrFail($laboratoryRequestId);
            $userId = Auth::id();

            // Store original flagged values for comparison when new result arrives
            foreach ($validated['flagged_results'] as $flaggedResult) {
                $testResult = \App\Models\TestResult::findOrFail($flaggedResult['test_result_id']);
                
                // Store original flagged value for future comparison
                $originalValue = $testResult->result_value;
                $historyComment = "DELTA_CHECK_HISTORY: Original Flagged Value: {$originalValue} | Re-run Initiated: " . now()->format('Y-m-d H:i:s');

                // Append to existing comment if any
                $existingComment = $testResult->result_comment ?? '';
                $testResult->result_comment = $existingComment ? "{$existingComment}\n{$historyComment}" : $historyComment;
                $testResult->save();

                // Update the item_status on the pivot table for the original test
                $laboratoryRequest->tests()->updateExistingPivot($testResult->test_id, ['item_status' => 'superseded']);

                // Create a new test result (R1) for the re-run
                Log::info('Before replication - Original TestResult ID: ' . $testResult->id . ', Version: ' . $testResult->version);
                $newTestResult = $testResult->replicate();
                $newTestResult->result_value = null; // Null for pending result
                $newTestResult->version = $testResult->version + 1; // Increment version
                $newTestResult->parent_result_id = $testResult->id; // Link to original R0
                $newTestResult->result_comment = "Re-run initiated for Test Result ID: {$testResult->id} (R{$testResult->version})";
                $newTestResult->save();
                Log::info('After replication and save - New TestResult ID: ' . $newTestResult->id . ', Version: ' . $newTestResult->version);

                // Update the item_status on the pivot table for the new test result
                $laboratoryRequest->tests()->updateExistingPivot($newTestResult->test_id, ['item_status' => 'Pending']);

                $newTestResults[] = $newTestResult;
            }



            DB::commit();

            Log::info('Delta check re-run initiated', [
                'laboratory_request_id' => $laboratoryRequestId,
                'user_id' => $userId,
                'flagged_results_count' => count($validated['flagged_results'])
            ]);

            return response()->json([
                'message' => 'Re-run initiated successfully. Please perform the re-run on the instrument.',
                'request' => $laboratoryRequest->load(['patient', 'tests']),
                'new_test_results' => $newTestResults ?? []
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error initiating re-run', [
                'error' => $e->getMessage(),
                'laboratory_request_id' => $laboratoryRequestId
            ]);
            return response()->json([
                'message' => 'Failed to initiate re-run: ' . $e->getMessage()
            ], 500);
        }
    }
}
