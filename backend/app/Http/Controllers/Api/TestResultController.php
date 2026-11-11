<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestResult;
use App\Models\LaboratoryRequest;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\TestParameter;
use App\Models\Patient;
use App\Models\ReferenceRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Services\QualityControlLockoutService;

class TestResultController extends Controller
{
    public function index(): JsonResponse
    {
        $results = TestResult::with(['laboratoryRequest.patient', 'test', 'testParameter', 'performer', 'verifier', 'pathologist'])
            ->latest()
            ->paginate(10);
        return response()->json($results);
    }

    /**
     * Store a new set of test results (may include multiple parameter results).
     */
    public function store(Request $request): JsonResponse
    {
        // Log the entire request payload for debugging
        Log::info('⚡️ TEST RESULT STORE - Raw request data:', [
            'request_data' => json_encode($request->all(), JSON_PRETTY_PRINT)
        ]);
        
        $validator = Validator::make($request->all(), [
            'laboratory_request_id' => 'required|exists:laboratory_requests,id',
            'active_test_category_id' => 'required|exists:test_categories,id',
            'results' => 'required|array',
            'results.*.test_id' => 'required|exists:tests,id',
            'results.*.remarks' => 'nullable|string',
            'results.*.comment' => 'nullable|string',
            'results.*.parameters' => 'sometimes|array',
            'results.*.parameters.*.test_parameter_id' => 'required_with:results.*.parameters|exists:test_parameters,id',
            'results.*.parameters.*.result_value' => 'required_with:results.*.parameters',
            'results.*.parameters.*.unit' => 'nullable|string',
            'results.*.parameters.*.equipment_id' => 'nullable|exists:equipment,id',
            'results.*.parameters.*.result_id' => 'nullable|exists:test_results,id',
            'results.*.parameters.*.result_comment' => 'nullable|string',
            'results.*.result_value' => 'required_without:results.*.parameters|string',
            'results.*.result_id' => 'nullable|exists:test_results,id',
            'results.*.result_comment' => 'nullable|string',
            'performer_id' => 'nullable|exists:users,id',
            'verifier_id' => 'nullable|exists:users,id',
            'pathologist_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $laboratoryRequest = LaboratoryRequest::findOrFail($request->laboratory_request_id);
            $activeCategoryId = $request->input('active_test_category_id');
            
            // Diagnostic: Log ALL tests associated with this lab request
            $allTestsInfo = [];
            $allTests = $laboratoryRequest->tests()->with('testCategory')->get();
            foreach ($allTests as $test) {
                $allTestsInfo[] = [
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'test_category_id' => $test->test_category_id,
                    'test_category_name' => $test->testCategory ? $test->testCategory->name : 'Unknown',
                    'pivot_status' => $test->pivot ? $test->pivot->item_status : 'Unknown',
                ];
            }
            Log::info('ALL TESTS FOR THIS LAB REQUEST BEFORE PROCESSING:', $allTestsInfo);
            
            // Get list of test IDs that are explicitly included in the request payload
            $requestedTestIds = collect($request->results)->pluck('test_id')->toArray();
            Log::info('EXPLICITLY REQUESTED TEST IDs FOR STATUS UPDATE:', $requestedTestIds);
            
            // EMERGENCY SOLUTION - DIRECT MANUAL SQL
            if (!empty($requestedTestIds)) {
                Log::info("🔥🔥🔥 EMERGENCY SOLUTION: Manually updating test statuses for test IDs: " . implode(', ', $requestedTestIds));
                
                try {
                    // Turn on query logging to see the actual SQL
                    DB::enableQueryLog();
                    
                    // Start transaction to isolate our changes
                    DB::beginTransaction();
                    
                    foreach ($requestedTestIds as $testId) {
                        // Direct SQL update for each test individually
                        $query = "UPDATE laboratory_request_test 
                                 SET item_status = 'Processing Results' 
                                 WHERE laboratory_request_id = ? AND test_id = ?";
                        
                        $params = [$laboratoryRequest->id, $testId];
                        Log::info("Executing SQL: {$query} with params: " . json_encode($params));
                        
                        $affected = DB::update($query, $params);
                        Log::info("Direct SQL update affected {$affected} rows for test ID {$testId}");
                    }
                    
                    // Check affected rows with a direct query
                    $checkQuery = "SELECT test_id, item_status FROM laboratory_request_test WHERE laboratory_request_id = ?";
                    $results = DB::select($checkQuery, [$laboratoryRequest->id]);
                    Log::info("After individual updates, current test statuses:", $results);
                    
                    // Commit transaction
                    DB::commit();
                    
                    // Get the executed queries
                    $queries = DB::getQueryLog();
                    Log::info("All executed SQL queries:", $queries);
                    
                    // Disable query logging to avoid memory issues
                    DB::disableQueryLog();
                } catch (\Exception $e) {
                    // Rollback if there's an error
                    DB::rollBack();
                    Log::error("Error in emergency update: " . $e->getMessage());
                }
            }
            
            $testResults = [];

            // Process each test result in the request
            foreach ($request->results as $resultData) {
                $testId = $resultData['test_id'];

                // Verify the test is part of the lab request
                $labRequestTest = $laboratoryRequest->tests()->where('tests.id', $testId)->first();
                if (!$labRequestTest) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Test ID $testId is not part of laboratory request {$laboratoryRequest->id}"
                    ], 422);
                }

                // Fetch the test to get its category_id
                $testModel = Test::find($testId);
                if (!$testModel) {
                    Log::warning("Test with ID {$testId} not found when processing results.");
                    continue;
                }

                // Add detailed logging to help diagnose the issue
                Log::info('Processing test:', [
                    'test_id' => $testId,
                    'test_name' => $testModel->name ?? 'Unknown',
                    'test_category_id' => $testModel->test_category_id ?? 'Not set',
                    'active_category_id' => $activeCategoryId,
                    'is_matching_category' => ($testModel->test_category_id == $activeCategoryId) ? 'Yes' : 'No'
                ]);

                // Verify the test is in the active category before proceeding with results
                if ($testModel->test_category_id != $activeCategoryId) {
                    Log::warning("Skipping results processing for test {$testId} - category mismatch: test is {$testModel->test_category_id}, active is {$activeCategoryId}");
                    continue;
                }

                // Process test with parameters
                if (isset($resultData['parameters']) && is_array($resultData['parameters'])) {
                    foreach ($resultData['parameters'] as $paramData) {
                        // First check if result_id is provided directly
                        $existingResult = null;
                        if (isset($paramData['result_id'])) {
                            $existingResult = TestResult::find($paramData['result_id']);
                            if ($existingResult) {
                                Log::info("Using directly provided result_id: {$paramData['result_id']}");
                            }
                        }
                        
                        // If no result_id provided or not found, check by other fields
                        if (!$existingResult) {
                            $existingResult = TestResult::where([
                                'laboratory_request_id' => $laboratoryRequest->id,
                                'test_id' => $testId,
                                'test_parameter_id' => $paramData['test_parameter_id']
                            ])->first();
                        }
                        
                        if ($existingResult) {
                            // Update existing result instead of creating a new one
                            Log::debug('Updating test result with ID: ' . $existingResult->id . ', param data:', [
                                'test_parameter_id' => $paramData['test_parameter_id'],
                                'result_value' => $paramData['result_value'],
                                'provided_result_comment' => $paramData['result_comment'] ?? 'not provided',
                                'existing_result_comment' => $existingResult->result_comment,
                                'full_param_data' => json_encode($paramData, JSON_PRETTY_PRINT)
                            ]);
                            
                            // Check if result_comment is provided and properly handle different cases
                            $resultComment = null;
                            if (array_key_exists('result_comment', $paramData)) {
                                // Key exists in the request, use the provided value (never null, default to empty string)
                                $resultComment = $paramData['result_comment'] === null ? '' : $paramData['result_comment'];
                                Log::debug("Using provided result_comment for parameter {$paramData['test_parameter_id']}: " . ($resultComment === null ? 'null' : $resultComment));
                            } else {
                                // Key doesn't exist, keep the existing value
                                $resultComment = $existingResult->result_comment;
                                Log::debug("Keeping existing result_comment for parameter {$paramData['test_parameter_id']}: " . ($resultComment === null ? 'null' : $resultComment));
                            }
                            
                            $existingResult->update([
                                'result_value' => $paramData['result_value'],
                                'unit' => isset($paramData['unit']) ? trim($paramData['unit']) : $existingResult->unit,
                                'equipment_id' => $paramData['equipment_id'] ?? ($paramData['analyzer_id'] ?? $existingResult->equipment_id),
                                'result_comment' => $resultComment,
                                'status' => 'saved',
                            ]);
                            
                            // Calculate and set flag
                            $existingResult->calculateFlag();
                            $existingResult->save();
                            
                            // After update, verify the saved result_comment
                            Log::debug('⚡️ AFTER UPDATE - Existing result:', [
                                'id' => $existingResult->id,
                                'saved_result_comment' => $existingResult->result_comment
                            ]);
                            
                            $testResults[] = $existingResult;
                        } else {
                            // Create new result if it doesn't exist
                            Log::debug('Creating new test result, param data:', [
                                'test_parameter_id' => $paramData['test_parameter_id'],
                                'result_value' => $paramData['result_value'],
                                'provided_result_comment' => $paramData['result_comment'] ?? 'not provided',
                                'full_param_data' => json_encode($paramData, JSON_PRETTY_PRINT)
                            ]);
                            
                            $testResult = TestResult::create([
                                'laboratory_request_id' => $laboratoryRequest->id,
                                'test_id' => $testId,
                                'test_parameter_id' => $paramData['test_parameter_id'],
                                'result_value' => $paramData['result_value'],
                                'unit' => isset($paramData['unit']) ? trim($paramData['unit']) : null,
                                'equipment_id' => $paramData['equipment_id'] ?? ($paramData['analyzer_id'] ?? null),
                                'result_comment' => isset($paramData['result_comment']) ? ($paramData['result_comment'] === null ? '' : $paramData['result_comment']) : '',
                                'status' => 'saved',
                            ]);

                            // Calculate and set flag with safety guard to avoid 500s on bad inputs
                            try {
                                $testResult->calculateFlag();
                            } catch (\Throwable $flagError) {
                                Log::warning('calculateFlag failed while creating result', [
                                    'error' => $flagError->getMessage(),
                                    'laboratory_request_id' => $laboratoryRequest->id,
                                    'test_id' => $testId,
                                    'test_parameter_id' => $paramData['test_parameter_id'] ?? null,
                                ]);
                                // Fall back to null flag to keep saving resilient
                                $testResult->flag = null;
                            }
                            $testResult->save();

                            // After creation, verify the saved result_comment
                            Log::debug('⚡️ AFTER CREATE - New result:', [
                                'id' => $testResult->id,
                                'saved_result_comment' => $testResult->result_comment
                            ]);

                            $testResults[] = $testResult;
                        }

                        // Add additional debug logging for result_comment
                        Log::debug('⚡️ PARAMETER DATA FOR STORE:', [
                            'test_id' => $testId,
                            'test_parameter_id' => $paramData['test_parameter_id'],
                            'result_value' => $paramData['result_value'],
                            'raw_result_comment' => $paramData['result_comment'] ?? '[NOT SET]',
                            'has_result_comment_key' => array_key_exists('result_comment', $paramData),
                            'result_comment_type' => isset($paramData['result_comment']) ? gettype($paramData['result_comment']) : 'not set'
                        ]);
                    }
                } else {
                    // Process test without parameters
                    // First check if result_id is provided directly
                    $existingResult = null;
                    if (isset($resultData['result_id'])) {
                        $existingResult = TestResult::find($resultData['result_id']);
                        if ($existingResult) {
                            Log::info("Using directly provided result_id for non-parameterized test: {$resultData['result_id']}");
                        }
                    }
                    
                    // If no result_id provided or not found, check by other fields
                    if (!$existingResult) {
                        $existingResult = TestResult::where([
                            'laboratory_request_id' => $laboratoryRequest->id,
                            'test_id' => $testId,
                            'test_parameter_id' => null
                        ])->first();
                    }
                    
                    if ($existingResult) {
                        // Update existing result
                        // Check if result_comment is provided and properly handle different cases
                        $resultComment = null;
                        if (array_key_exists('result_comment', $resultData)) {
                            // Key exists in the request, use the provided value (never null, default to empty string)
                            $resultComment = $resultData['result_comment'] === null ? '' : $resultData['result_comment'];
                            Log::debug("Using provided result_comment for non-parameterized test {$testId}: " . ($resultComment === null ? 'null' : $resultComment));
                        } else {
                            // Key doesn't exist, keep the existing value
                            $resultComment = $existingResult->result_comment;
                            Log::debug("Keeping existing result_comment for non-parameterized test {$testId}: " . ($resultComment === null ? 'null' : $resultComment));
                        }
                        
                        $existingResult->update([
                            'result_value' => $resultData['result_value'],
                            'unit' => isset($resultData['unit']) ? trim($resultData['unit']) : $existingResult->unit,
                            'equipment_id' => $resultData['equipment_id'] ?? ($resultData['analyzer_id'] ?? $existingResult->equipment_id),
                            'result_comment' => $resultComment,
                            'status' => 'saved',
                        ]);
                        
                        $testResults[] = $existingResult;
                    } else {
                        // Create new result only if it doesn't exist
                        $testResult = TestResult::create([
                            'laboratory_request_id' => $laboratoryRequest->id,
                            'test_id' => $testId,
                            'result_value' => $resultData['result_value'],
                            'unit' => isset($resultData['unit']) ? trim($resultData['unit']) : null,
                            'equipment_id' => $resultData['equipment_id'] ?? ($resultData['analyzer_id'] ?? null), 
                            'result_comment' => isset($resultData['result_comment']) ? ($resultData['result_comment'] === null ? '' : $resultData['result_comment']) : '',
                            'status' => 'saved',
                        ]);

                        $testResults[] = $testResult;
                    }
                }

                // Update signatory fields in the pivot table if present in the request
                $pivotUpdate = [];
                if ($request->has('performer_id')) {
                    $pivotUpdate['perform_by'] = $request->input('performer_id');
                    $pivotUpdate['perform_at'] = now();
                }
                if ($request->has('verifier_id')) {
                    $pivotUpdate['verify_by'] = $request->input('verifier_id');
                    $pivotUpdate['verify_at'] = now();
                }
                if ($request->has('pathologist_id')) {
                    $pivotUpdate['pathologist_id'] = $request->input('pathologist_id');
                }
                if (!empty($pivotUpdate)) {
                    $laboratoryRequest->tests()->updateExistingPivot($testId, $pivotUpdate);
                }
            }

            DB::commit();
            
            // Transform the testResults to ensure correct nesting of result_comment
            $transformedResults = $this->transformResultsForResponse($testResults);
            
            return response()->json([
                'message' => 'Test results saved successfully',
                'results' => $transformedResults
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating test results: ' . $e->getMessage());
            return response()->json(['message' => 'Error creating test results: ' . $e->getMessage()], 500);
        }
    }

    public function show(TestResult $testResult): JsonResponse
    {
        $testResult->load(['laboratoryRequest.patient', 'test', 'testParameter', 'performer', 'verifier', 'pathologist']);
        return response()->json($testResult);
    }

    /**
     * Update results for a specific laboratory request.
     */
    public function update(Request $request, $laboratoryRequestId): JsonResponse
    {
        // Log the entire request payload for debugging
        Log::info('⚡️ TEST RESULT UPDATE - Raw request data:', [
            'laboratory_request_id' => $laboratoryRequestId,
            'request_data' => json_encode($request->all(), JSON_PRETTY_PRINT)
        ]);
        
        $validator = Validator::make($request->all(), [
            'active_test_category_id' => 'required|exists:test_categories,id',
            'results' => 'required|array',
            'results.*.test_id' => 'required|exists:tests,id',
            'results.*.remarks' => 'nullable|string',
            'results.*.comment' => 'nullable|string',
            'results.*.parameters' => 'sometimes|array',
            'results.*.parameters.*.test_parameter_id' => 'required_with:results.*.parameters|exists:test_parameters,id',
            'results.*.parameters.*.result_value' => 'required_with:results.*.parameters',
            'results.*.parameters.*.unit' => 'nullable|string',
            'results.*.parameters.*.equipment_id' => 'nullable|exists:equipment,id',
            'results.*.parameters.*.result_id' => 'nullable|exists:test_results,id',
            'results.*.parameters.*.result_comment' => 'nullable|string',
            'results.*.result_value' => 'required_without:results.*.parameters|string',
            'results.*.result_id' => 'nullable|exists:test_results,id',
            'results.*.result_comment' => 'nullable|string',
            'performer_id' => 'nullable|exists:users,id',
            'verifier_id' => 'nullable|exists:users,id',
            'pathologist_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:saved,completed,validated,released',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $laboratoryRequest = LaboratoryRequest::findOrFail($laboratoryRequestId);
            $activeCategoryId = $request->input('active_test_category_id');
            
            // Diagnostic: Log ALL tests associated with this lab request
            $allTestsInfo = [];
            $allTests = $laboratoryRequest->tests()->with('testCategory')->get();
            foreach ($allTests as $test) {
                $allTestsInfo[] = [
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'test_category_id' => $test->test_category_id,
                    'test_category_name' => $test->testCategory ? $test->testCategory->name : 'Unknown',
                    'pivot_status' => $test->pivot ? $test->pivot->item_status : 'Unknown',
                ];
            }
            Log::info('ALL TESTS FOR THIS LAB REQUEST BEFORE UPDATING:', $allTestsInfo);
            
            // Get list of test IDs that are explicitly included in the request payload
            $requestedTestIds = collect($request->results)->pluck('test_id')->toArray();
            Log::info('EXPLICITLY REQUESTED TEST IDs FOR STATUS UPDATE:', $requestedTestIds);
            
            // Determine status to set
            $newStatus = 'Processing Results';
            if ($request->status) {
                if ($request->status === 'validated') {
                    $newStatus = 'Result Validated';
                } else if ($request->status === 'completed' || $request->status === 'released') {
                    $newStatus = 'Result Printed';
                }
            }
            
            // Check for QC lockout before releasing/validating results
            if ($newStatus === 'Result Validated' || $newStatus === 'Result Printed') {
                $testResults = TestResult::where('laboratory_request_id', $laboratoryRequestId)
                    ->whereIn('test_id', $requestedTestIds)
                    ->with(['testParameter', 'equipment'])
                    ->get();

                Log::info('QC Lockout Check - Test Result IDs for Hematology:', $testResults->pluck('id')->toArray());
                Log::info('QC Lockout Check - Found results count: ' . $testResults->count());

                $qcLockoutService = new QualityControlLockoutService();
                $lockedParameters = [];
                $qcIssues = [];
                
                foreach ($testResults as $result) {
                    Log::info("QC Lockout Check - Result ID: {$result->id}, Equipment: {$result->equipment_id}, Parameter: {$result->test_parameter_id}");
                    
                    if ($result->equipment_id && $result->test_parameter_id) {
                        $qcStatus = $qcLockoutService->checkQCStatus($result->equipment_id, $result->test_parameter_id);
                        Log::info("QC Lockout Check - QC Status:", ['is_in_control' => $qcStatus['is_in_control'], 'violations' => $qcStatus['violations']]);
                        
                        if (!$qcStatus['is_in_control']) {
                            $lockedParameters[] = [
                                'parameter' => $result->testParameter->test_name ?? 'Unknown',
                                'violations' => implode(', ', $qcStatus['violations'])
                            ];
                            $qcIssues[] = [
                                'result_id' => $result->id,
                                'equipment_id' => $result->equipment_id,
                                'equipment_name' => $result->equipment ? ($result->equipment->manufacturer . ' - ' . $result->equipment->model) : null,
                                'test_parameter_id' => $result->test_parameter_id,
                                'test_parameter_name' => $result->testParameter->test_name ?? null,
                                'violations' => $qcStatus['violations'],
                            ];
                            
                            // Mark this test result as locked
                            $result->qc_lockout_active = true;
                            $result->qc_lockout_date = now();
                            $result->requires_review = true;
                            $result->save();
                        }
                    }
                }
                
                Log::info('QC Lockout Check - Locked Parameters Count: ' . count($lockedParameters));
                
                // If QC is out of control, block the release
                if (!empty($lockedParameters)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Quality Control lockout active. Results cannot be released.',
                        'locked_parameters' => $lockedParameters,
                        'issues' => $qcIssues,
                        'error_type' => 'qc_lockout'
                    ], 403);
                }
            }
            
            // EMERGENCY SOLUTION - DIRECT MANUAL SQL
            if (!empty($requestedTestIds)) {
                Log::info("🔥🔥🔥 EMERGENCY SOLUTION: Manually updating test statuses to '{$newStatus}' for test IDs: " . implode(', ', $requestedTestIds));
                
                try {
                    // Turn on query logging to see the actual SQL
                    DB::enableQueryLog();
                    
                    // Start transaction to isolate our changes
                    DB::beginTransaction();
                    
                    foreach ($requestedTestIds as $testId) {
                        // Direct SQL update for each test individually
                        $query = "UPDATE laboratory_request_test 
                                 SET item_status = ? 
                                 WHERE laboratory_request_id = ? AND test_id = ?";
                        
                        $params = [$newStatus, $laboratoryRequestId, $testId];
                        Log::info("Executing SQL: {$query} with params: " . json_encode($params));
                        
                        $affected = DB::update($query, $params);
                        Log::info("Direct SQL update affected {$affected} rows for test ID {$testId}");
                    }
                    
                    // Check affected rows with a direct query
                    $checkQuery = "SELECT test_id, item_status FROM laboratory_request_test WHERE laboratory_request_id = ?";
                    $results = DB::select($checkQuery, [$laboratoryRequestId]);
                    Log::info("After individual updates, current test statuses:", $results);
                    
                    // Commit transaction
                    DB::commit();
                    
                    // Get the executed queries
                    $queries = DB::getQueryLog();
                    Log::info("All executed SQL queries:", $queries);
                    
                    // Disable query logging to avoid memory issues
                    DB::disableQueryLog();
                } catch (\Exception $e) {
                    // Rollback if there's an error
                    DB::rollBack();
                    Log::error("Error in emergency update: " . $e->getMessage());
                }
            }
            
            $updatedResults = [];

            // Process each test result
            foreach ($request->results as $resultData) {
                $testId = $resultData['test_id'];
                
                // Fetch the test to get its category_id
                $testModel = Test::find($testId);
                if (!$testModel) {
                    Log::warning("Test with ID {$testId} not found when updating results.");
                    continue;
                }
                
                // Add detailed logging to help diagnose the issue
                Log::info('Processing test for update:', [
                    'test_id' => $testId,
                    'test_name' => $testModel->name ?? 'Unknown',
                    'test_category_id' => $testModel->test_category_id ?? 'Not set',
                    'active_category_id' => $activeCategoryId,
                    'is_matching_category' => ($testModel->test_category_id == $activeCategoryId) ? 'Yes' : 'No'
                ]);
                
                // Verify the test is in the active category before proceeding with results
                if ($testModel->test_category_id != $activeCategoryId) {
                    Log::warning("Skipping results processing for test {$testId} - category mismatch: test is {$testModel->test_category_id}, active is {$activeCategoryId}");
                    continue;
                }

                if (isset($resultData['parameters']) && is_array($resultData['parameters'])) {
                    // Process test with parameters
                    foreach ($resultData['parameters'] as $paramData) {
                        $existingResult = null;
                        
                        // First check if result_id is provided directly
                        if (isset($paramData['result_id'])) {
                            $existingResult = TestResult::find($paramData['result_id']);
                            if ($existingResult) {
                                Log::info("Using directly provided result_id for parameterized test: {$paramData['result_id']}");
                            }
                        }
                        
                        // If no result_id provided or not found, check by other fields
                        if (!$existingResult) {
                            $existingResult = TestResult::where([
                                'laboratory_request_id' => $laboratoryRequestId,
                                'test_id' => $testId,
                                'test_parameter_id' => $paramData['test_parameter_id']
                            ])->first();
                        }
                        
                        // Log parameter data for debugging
                        Log::debug('Processing parameter update:', [
                            'test_parameter_id' => $paramData['test_parameter_id'],
                            'result_value' => $paramData['result_value'],
                            'result_comment' => $paramData['result_comment'] ?? 'not provided',
                            'is_existing_result' => $existingResult ? 'yes' : 'no',
                            'existing_result_id' => $existingResult ? $existingResult->id : null,
                            'existing_result_comment' => $existingResult ? $existingResult->result_comment : null
                        ]);
                        
                        if ($existingResult) {
                            // Update existing result
                            // Check if result_comment is provided and properly handle different cases
                            $resultComment = null;
                            if (array_key_exists('result_comment', $paramData)) {
                                // Key exists in the request, use the provided value (never null, default to empty string)
                                $resultComment = $paramData['result_comment'] === null ? '' : $paramData['result_comment'];
                                Log::debug("Using provided result_comment for parameter {$paramData['test_parameter_id']}: " . ($resultComment === null ? 'null' : $resultComment));
                            } else {
                                // Key doesn't exist, keep the existing value
                                $resultComment = $existingResult->result_comment;
                                Log::debug("Keeping existing result_comment for parameter {$paramData['test_parameter_id']}: " . ($resultComment === null ? 'null' : $resultComment));
                            }
                            
                            $existingResult->update([
                                'result_value' => $paramData['result_value'],
                                'unit' => isset($paramData['unit']) ? trim($paramData['unit']) : $existingResult->unit,
                                'equipment_id' => $paramData['equipment_id'] ?? ($paramData['analyzer_id'] ?? $existingResult->equipment_id),
                                'result_comment' => $resultComment,
                                'status' => $request->status ?? 'saved'
                            ]);
                            
                            // Calculate and set flag (if application)
                            $existingResult->calculateFlag();
                            $existingResult->save();
                            
                            // After update, verify the saved result_comment
                            Log::debug('⚡️ AFTER UPDATE - Existing result:', [
                                'id' => $existingResult->id,
                                'saved_result_comment' => $existingResult->result_comment
                            ]);
                            
                            $updatedResults[] = $existingResult;
                        } else {
                            // Create new result if it doesn't exist
                            $testResult = TestResult::create([
                                'laboratory_request_id' => $laboratoryRequestId,
                                'test_id' => $testId,
                                'test_parameter_id' => $paramData['test_parameter_id'],
                                'result_value' => $paramData['result_value'],
                                'unit' => isset($paramData['unit']) ? trim($paramData['unit']) : null,
                                'equipment_id' => $paramData['equipment_id'] ?? ($paramData['analyzer_id'] ?? null),
                                'result_comment' => isset($paramData['result_comment']) ? ($paramData['result_comment'] === null ? '' : $paramData['result_comment']) : '',
                                'status' => $request->status ?? 'saved'
                            ]);
                            
                            // Calculate and set flag with safety guard to avoid 500s on bad inputs
                            try {
                                $testResult->calculateFlag();
                            } catch (\Throwable $flagError) {
                                Log::warning('calculateFlag failed while updating/creating result', [
                                    'error' => $flagError->getMessage(),
                                    'laboratory_request_id' => $laboratoryRequestId,
                                    'test_id' => $testId,
                                    'test_parameter_id' => $paramData['test_parameter_id'] ?? null,
                                ]);
                                $testResult->flag = null;
                            }
                            $testResult->save();
                            
                            // After creation, verify the saved result_comment
                            Log::debug('⚡️ AFTER CREATE - New result:', [
                                'id' => $testResult->id,
                                'saved_result_comment' => $testResult->result_comment
                            ]);

                            $updatedResults[] = $testResult;
                        }

                        // Add additional debug logging for result_comment
                        Log::debug('⚡️ PARAMETER DATA FOR UPDATE:', [
                            'test_id' => $testId,
                            'test_parameter_id' => $paramData['test_parameter_id'],
                            'result_value' => $paramData['result_value'],
                            'raw_result_comment' => $paramData['result_comment'] ?? '[NOT SET]',
                            'has_result_comment_key' => array_key_exists('result_comment', $paramData),
                            'result_comment_type' => isset($paramData['result_comment']) ? gettype($paramData['result_comment']) : 'not set'
                        ]);
                    }
                } else {
                    // Handle non-parameterized tests
                    // First check if result_id is provided directly
                    $existingResult = null;
                    if (isset($resultData['result_id'])) {
                        $existingResult = TestResult::find($resultData['result_id']);
                        if ($existingResult) {
                            Log::info("Using directly provided result_id for non-parameterized test in update: {$resultData['result_id']}");
                        }
                    }
                    
                    // If no result_id provided or not found, check by other fields
                    if (!$existingResult) {
                        $existingResult = TestResult::where([
                            'laboratory_request_id' => $laboratoryRequestId,
                            'test_id' => $testId,
                            'test_parameter_id' => null
                        ])->first();
                    }

                    if ($existingResult) {
                        // Update existing result
                        // Check if result_comment is provided and properly handle different cases
                        $resultComment = null;
                        if (array_key_exists('result_comment', $resultData)) {
                            // Key exists in the request, use the provided value (never null, default to empty string)
                            $resultComment = $resultData['result_comment'] === null ? '' : $resultData['result_comment'];
                            Log::debug("Using provided result_comment for non-parameterized test {$testId}: " . ($resultComment === null ? 'null' : $resultComment));
                        } else {
                            // Key doesn't exist, keep the existing value
                            $resultComment = $existingResult->result_comment;
                            Log::debug("Keeping existing result_comment for non-parameterized test {$testId}: " . ($resultComment === null ? 'null' : $resultComment));
                        }
                        
                        $existingResult->update([
                            'result_value' => $resultData['result_value'],
                            'unit' => isset($resultData['unit']) ? trim($resultData['unit']) : $existingResult->unit,
                            'equipment_id' => $resultData['equipment_id'] ?? ($resultData['analyzer_id'] ?? $existingResult->equipment_id),
                            'result_comment' => $resultComment,
                            'status' => $request->status ?? 'saved'
                        ]);
                        
                        $updatedResults[] = $existingResult;
                    } else {
                        // Create a new non-parameterized test result
                        $testResult = TestResult::create([
                            'laboratory_request_id' => $laboratoryRequestId,
                            'test_id' => $testId,
                            'test_parameter_id' => null,
                            'result_value' => $resultData['result_value'],
                            'unit' => isset($resultData['unit']) ? trim($resultData['unit']) : null,
                            'equipment_id' => $resultData['equipment_id'] ?? ($resultData['analyzer_id'] ?? null),
                            'result_comment' => isset($resultData['result_comment']) ? ($resultData['result_comment'] === null ? '' : $resultData['result_comment']) : '',
                            'status' => $request->status ?? 'saved'
                        ]);
                        
                        $updatedResults[] = $testResult;
                    }
                }

                // Update signatory fields in the pivot table if present in the request
                $pivotUpdate = [];
                if ($request->has('performer_id')) {
                    $pivotUpdate['performer_id'] = $request->input('performer_id');
                    $pivotUpdate['performed_at'] = now();
                }
                if ($request->has('verifier_id')) {
                    $pivotUpdate['verifier_id'] = $request->input('verifier_id');
                    $pivotUpdate['verified_at'] = now();
                }
                if ($request->has('pathologist_id')) {
                    $pivotUpdate['pathologist_id'] = $request->input('pathologist_id');
                }
                if (!empty($pivotUpdate)) {
                    $laboratoryRequest->tests()->updateExistingPivot($testId, $pivotUpdate);
                }
            }

            DB::commit();
            
            // Transform the updatedResults to ensure correct nesting of result_comment
            $transformedResults = $this->transformResultsForResponse($updatedResults);
            
            return response()->json([
                'message' => 'Test results updated successfully',
                'results' => $transformedResults
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating test results: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating test results: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(TestResult $testResult): JsonResponse
    {
        $testResult->delete();
        return response()->json(null, 204);
    }

    public function getLaboratoryRequests(): JsonResponse
    {
        $requests = LaboratoryRequest::with(['patient', 'tests'])
            ->whereDoesntHave('testResults')
            ->latest()
            ->get();
        return response()->json($requests);
    }

    public function getTestsByRequest(LaboratoryRequest $laboratoryRequest): JsonResponse
    {
        $tests = $laboratoryRequest->tests()
            ->whereDoesntHave('testResults', function ($query) use ($laboratoryRequest) {
                $query->where('laboratory_request_id', $laboratoryRequest->id);
            })
            ->get();
        return response()->json($tests);
    }

    /**
     * Fallback: Get test results for a request filtered by equipment id (regardless of qc_lockout flag).
     */
    public function getResultsByRequestAndEquipment($laboratoryRequestId, $equipmentId): JsonResponse
    {
        $results = TestResult::where('laboratory_request_id', $laboratoryRequestId)
            ->where('equipment_id', $equipmentId)
            ->with(['testParameter', 'laboratoryRequest', 'equipment'])
            ->get();
        return response()->json($results);
    }

    /**
     * Get distinct equipment IDs used for a laboratory request's test results.
     */
    public function getEquipmentForRequest($laboratoryRequestId)
    {
        try {
            $ids = TestResult::where('laboratory_request_id', $laboratoryRequestId)
                ->whereNotNull('equipment_id')
                ->distinct()
                ->pluck('equipment_id')
                ->values();

            return response()->json([
                'laboratory_request_id' => (int) $laboratoryRequestId,
                'equipment_ids' => $ids,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'laboratory_request_id' => (int) $laboratoryRequestId,
                'equipment_ids' => [],
                'error' => 'failed_to_fetch_analyzers',
            ], 200);
        }
    }

    /**
     * Calculate flag and conventional unit value based on input.
     */
    public function calculateDerivedData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'result' => 'required|numeric',
            'unit' => 'nullable|string|max:50',
            'test_parameter_id' => 'required|exists:test_parameters,id',
            'patient_id' => 'required|exists:patients,id',
            'equipment_id' => 'nullable|exists:equipment,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resultValue = (float) $request->input('result');
        $resultUnit = $request->input('unit');
        $parameterId = $request->input('test_parameter_id');
        $patientId = $request->input('patient_id');
        $equipmentId = $request->input('equipment_id') ?? $request->input('analyzer_id'); // Support both for backward compatibility

        try {
            $parameter = TestParameter::find($parameterId);
            $patient = Patient::find($patientId);

            if (!$parameter || !$patient) {
                return response()->json(['message' => 'Parameter or Patient not found.'], 404);
            }

            // Flag Calculation Logic
            $flagValue = null;
            $ageInYears = $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : null;
            $gender = $patient->gender ?? 'Any';

            if ($ageInYears !== null) {
                // Query for reference range considering equipment
                $query = ReferenceRange::where('test_parameter_id', $parameter->id)
                    ->where('age_min', '<=', $ageInYears)
                    ->where('age_max', '>=', $ageInYears)
                    ->where(function ($q) use ($gender) {
                        $q->where('gender', $gender)->orWhere('gender', 'Any');
                    });
                
                // If equipment ID is provided, filter by it
                if ($equipmentId) {
                    $query->where(function ($q) use ($equipmentId) {
                        $q->where('equipment_id', $equipmentId)
                          ->orWhereNull('equipment_id');
                    });
                }
                
                $referenceRange = $query->orderBy('gender', 'desc')
                                       ->orderByRaw('equipment_id IS NULL')
                                       ->first();

                if ($referenceRange) {
                    // Flag Comparison
                    if ($referenceRange->low_critical_value !== null && $resultValue < $referenceRange->low_critical_value) {
                        $flagValue = 'L';
                    } elseif ($referenceRange->low_value !== null && $resultValue < $referenceRange->low_value) {
                        $flagValue = 'l';
                    } elseif ($referenceRange->high_critical_value !== null && $resultValue > $referenceRange->high_critical_value) {
                        $flagValue = 'H';
                    } elseif ($referenceRange->high_value !== null && $resultValue > $referenceRange->high_value) {
                        $flagValue = 'h';
                    }
                }
            }

            // Calculate conventional value if conversion factor exists
            $conventionalValue = null;
            $conventionalUnit = $parameter->conventional_unit;
            
            if ($parameter->conversion_factor && is_numeric($parameter->conversion_factor)) {
                $conversionFactor = (float) $parameter->conversion_factor;
                $conventionalValue = $resultValue * $conversionFactor;
                $conventionalValue = round($conventionalValue, 2); // Round to 2 decimal places for display
            }

            return response()->json([
                'flag' => $flagValue,
                'conventional_value' => $conventionalValue,
                'conventional_unit' => $conventionalUnit,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in calculateDerivedData: " . $e->getMessage());
            return response()->json(['message' => 'Error calculating derived data: ' . $e->getMessage()], 500);
        }
    }

    public function renameInterpretationToResultComment()
    {
        try {
            if (Schema::hasColumn('test_results', 'interpretation') && !Schema::hasColumn('test_results', 'result_comment')) {
                Schema::table('test_results', function (Blueprint $table) {
                    $table->renameColumn('interpretation', 'result_comment');
                });
            }
        } catch (\Exception $e) {
            error_log('Error renaming interpretation to result_comment: ' . $e->getMessage());
        }
    }

    /**
     * Transform the TestResult models to properly structure result_comment
     * under the test parameters array instead of at the root level.
     */
    private function transformResultsForResponse($results)
    {
        $transformedResults = [];
        
        foreach ($results as $result) {
            // Create properly nested structure
            $transformedResult = [
                'id' => $result->id,
                'laboratory_request_id' => $result->laboratory_request_id,
                'test_id' => $result->test_id,
                'result_value' => $result->result_value,
                'unit' => $result->unit,
                'flag' => $result->flag,
                'status' => $result->status,
                'created_at' => $result->created_at,
                'updated_at' => $result->updated_at,
                'equipment_id' => $result->equipment_id
            ];
            
            // If there's a test parameter, nest the result_comment under it
            if ($result->test_parameter_id) {
                // Load parameter details if not already loaded
                if (!isset($result->testParameter) && $result->test_parameter_id) {
                    $result->load('testParameter');
                }
                
                $transformedResult['test_parameter'] = [
                    'id' => $result->test_parameter_id,
                    'name' => $result->testParameter ? $result->testParameter->name : null,
                    'result_comment' => $result->result_comment,
                ];
            } else {
                // For non-parameterized tests, include the result_comment at the parameter level
                $transformedResult['test_parameter'] = null;
                $transformedResult['parameters'] = [
                    [
                        'result_comment' => $result->result_comment
                    ]
                ];
            }
            
            $transformedResults[] = $transformedResult;
        }
        
        return $transformedResults;
    }
} 