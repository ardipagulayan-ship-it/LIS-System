<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaboratoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\TestCategory; // Assuming TestCategory model exists
use App\Models\TestParameter;
use App\Models\TestResult;
use App\Models\User;
use App\Models\RuleDecisionAudit;
use App\Services\QualityControlLockoutService;
use App\Models\CdsCorrelationRule;
use App\Services\CdsRuleEngine;
use App\Services\ReflexTestService;


class ResultManagementController extends Controller
{
    /**
     * Fetch requests relevant to Hematology Result Management.
     *
     * Fetches requests that contain tests belonging to the 'Hematology' category
     * and includes relevant patient and test details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function getHematologyRequests(Request $request)
    // {
    //     // --- Configuration ---
    //     $hematologyCategoryName = 'Hematology'; // Case-sensitive category name
    //     // ---------------------

    //     try {
    //         // Find the ID of the Hematology category
    //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();

    //         if (!$hematologyCategory) {
    //             Log::warning("Hematology category '{$hematologyCategoryName}' not found.");
    //             // Return empty paginated response if category doesn't exist
    //             return response()->json(LaboratoryRequest::paginate($request->input('per_page', 15)));
    //         }

    //         $hematologyCategoryId = $hematologyCategory->id;

    //         $query = LaboratoryRequest::query();

    //         // 1. Filter Requests: Only include requests with at least one test in the Hematology category
    //         //    and where that test status is relevant for results (e.g., 'Accepted')
    //         $query->whereHas('tests', function ($testQuery) use ($hematologyCategoryId) {
    //             $testQuery->where('test_category_id', $hematologyCategoryId)
    //                       ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run']); // Statuses indicating results can be viewed/entered
    //         });

    //         // 2. Eager Load Relations:
    //         $query->with([
    //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email', // Select necessary patient fields
    //             'patientType:id,name', // Load patient type directly from LaboratoryRequest
    //             'ward:id,name',        // Load ward directly from LaboratoryRequest
    //             'room:id,name',        // Load room directly from LaboratoryRequest
    //             // Load ONLY the tests relevant to Hematology for this view
    //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
    //                 $testQuery->where('test_category_id', $hematologyCategoryId)
    //                           ->select('tests.id', 'tests.name') // Select only needed test fields
    //                           ->withPivot('item_status'); // Load pivot status
    //             }
    //         ]);

    //         // 3. Search Functionality (Optional - Example)
    //         if ($request->filled('search')) {
    //             $searchTerm = $request->input('search');
    //             $query->where(function ($q) use ($searchTerm) {
    //                 $q->where('id', 'like', "%{$searchTerm}%")
    //                   ->orWhere('sample_id', 'like', "%{$searchTerm}%")
    //                   ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
    //                       $patientQuery->where(DB::raw("CONCAT(lname, ' ', fname)"), 'like', "%{$searchTerm}%")
    //                                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
    //                                    ->orWhere('first_name', 'like', "%{$searchTerm}%")
    //                                    ->orWhere('middle_name', 'like', "%{$searchTerm}%")
    //                                    ->orWhere('id', 'like', "%{$searchTerm}%");
    //                   });
    //             });
    //         }

    //         // 4. Ordering
    //         $query->orderBy('priority_level', 'desc')
    //               ->orderBy('request_date', 'asc');

    //         // 5. Paginate
    //         $perPage = $request->input('per_page', 15);
    //         \Log::debug('Executing getHematologyRequests query: ' . $query->toSql());
    //         $requests = $query->paginate($perPage);

    //         // 6. Transform Data (Add computed fields like age, test list string, status)
    //         $requests->getCollection()->transform(function ($request) use ($hematologyCategoryId) {
    //             // Calculate Age
    //             //$request->patient_age = $request->patient && $request->patient->date_of_birth
    //             //    ? now()->diffInYears($request->patient->date_of_birth)
    //             //    : 'N/A';

    //             // Create comma-separated list of Hematology test names
    //             $request->hematology_test_list = $request->tests->pluck('name')->implode(', ');

    //             // Determine overall Hematology status (Placeholder Logic)
    //             // This needs refinement based on actual result entry/validation models/flags
    //             $hasValidated = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
    //             });
    //              $hasPrinted = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Result Printed';
    //             });
    //             $hasAccepted = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Accepted' || $test->pivot->item_status === 'Collected' || $test->pivot->item_status === 'Send-out' || $test->pivot->item_status === 'Parked';
    //             });
    //             $hasResults = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Processing Results';
    //             });
    //             $hasCheckedIn = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Checked-in';
    //             });
    //             $hasForRerun = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'For Re-run';
    //             });

    //             if($hasPrinted) {
    //                 $request->hematology_status = 'Printed';
    //             } elseif ($hasForRerun) {
    //                 $request->hematology_status = 'For Re-run';
    //             } elseif ($hasValidated) {
    //                  $request->hematology_status = 'Validated';
    //             } elseif ($hasAccepted) {
    //                 $request->hematology_status = 'Pending Sample';
    //             } elseif ($hasCheckedIn) {
    //                  $request->hematology_status = 'Checked-in';
    //             } elseif ($hasResults) {
    //                     $request->hematology_status = 'Processing Results';
    //             } else {
    //                  $request->hematology_status = 'Unknown'; // Or Unknown?
    //             }

    //             // Add test parameter counts for progress tracking
    //             foreach ($request->tests as $test) {
    //                 // Get the total parameter count for this test
    //                 $test->parameter_count = TestParameter::where('test_id', $test->id)->count();
                    
    //                 // Get the completed parameter count (parameters with results)
    //                 $test->completed_parameter_count = TestResult::where([
    //                     'laboratory_request_id' => $request->id,
    //                     'test_id' => $test->id,
    //                 ])->whereNotNull('test_parameter_id')->count();
                    
    //                 // Special case: if the test has no parameters, count a single non-parameterized result
    //                 if ($test->parameter_count === 0) {
    //                     $test->parameter_count = 1;
    //                     $test->completed_parameter_count = TestResult::where([
    //                         'laboratory_request_id' => $request->id,
    //                         'test_id' => $test->id,
    //                         'test_parameter_id' => null,
    //                     ])->count();
    //                 }
    //             }

    //             // Optionally unset the loaded tests relation if frontend doesn't need the full structure
    //             // unset($request->tests);

    //             return $request;
    //         });

    //         return response()->json($requests);

    //     } catch (\Exception $e) {
    //         Log::error("Error fetching Hematology requests: " . $e->getMessage());
    //         // Return a generic error response
    //         return response()->json(['message' => 'An error occurred while fetching Hematology requests.'], 500);
    //     }
    // }

    /**
     * Fetch detailed information for a single Hematology request, including tests and results.
     *
     * @param  \App\Models\LaboratoryRequest  $laboratoryRequest
     * @return \Illuminate\Http\JsonResponse
     */
    // public function getHematologyRequestDetails(LaboratoryRequest $laboratoryRequest)
    // {
    //     // --- Configuration ---
    //     $hematologyCategoryName = 'Hematology';
    //     // ---------------------

    //     try {
    //         // Find the ID of the Hematology category
    //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();
    //         if (!$hematologyCategory) {
    //              return response()->json(['message' => "Hematology category '{$hematologyCategoryName}' not found."], 404);
    //         }
    //         $hematologyCategoryId = $hematologyCategory->id;
            
    //         // Add debugging logs
    //         \Log::info('⚡️ Starting getHematologyRequestDetails for laboratory request ID: ' . $laboratoryRequest->id);

    //         // 1. Load the core request data and RELATIONS, including filtered tests
    //         $laboratoryRequest->load([
    //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email',
    //             'patientType:id,name',
    //             'ward:id,name',
    //             'room:id,name',
    //             'doctor:id,name', // Assuming Doctor model has 'name' attribute
    //             'creator:id,name',  // Assuming User model has 'name' attribute
    //             // Load ONLY Hematology tests with basic pivot data (like item_status, remarks, comment)
    //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
    //                 $testQuery->where('test_category_id', $hematologyCategoryId)
    //                           ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run'])
    //                           ->withPivot('id', 'remarks', 'comment', 'perform_by', 'perform_at', 'verify_by', 'verify_at', 'pathologist_id'); // Include tracking fields
    //             },
    //             // Load test results for this laboratory request
    //             'results' => function($query) use ($hematologyCategoryId) {
    //                 $query->whereHas('test', function($q) use ($hematologyCategoryId) {
    //                     $q->where('test_category_id', $hematologyCategoryId);
    //                 })
    //                 ->with(['test', 'testParameter', 'equipment']);
    //             }
    //         ]);

    //         // Log the loaded test results for debugging
    //         \Log::debug('⚡️ Loaded test results:', [
    //             'count' => $laboratoryRequest->results->count(),
    //             'sample' => $laboratoryRequest->results->take(3)->map(function($result) {
    //                 return [
    //                     'id' => $result->id,
    //                     'test_id' => $result->test_id,
    //                     'test_parameter_id' => $result->test_parameter_id,
    //                     'result_value' => $result->result_value,
    //                     'result_comment' => $result->result_comment
    //                 ];
    //             })
    //         ]);

    //         // Process the loaded test results and associate them with their respective tests
    //         $tests = $laboratoryRequest->tests;
            
    //         // Create a map to store test parameters and their results by test_id
    //         $testResultsMap = [];
            
    //         // Add user information for tracking fields
    //         $laboratoryRequest->tests->each(function($test) {
    //             // Load performer user
    //             if ($test->pivot->perform_by) {
    //                 $test->performer_id = $test->pivot->perform_by;
    //                 $test->perform_at = $test->pivot->perform_at;
                    
    //                 // Load the actual user data if needed
    //                 try {
    //                     $performer = \App\Models\User::find($test->pivot->perform_by, ['id', 'name', 'email']);
    //                     if ($performer) {
    //                         $test->performer = $performer;
    //                     }
    //                 } catch (\Exception $e) {
    //                     \Log::error("Error loading performer user: " . $e->getMessage());
    //                 }
    //             }
                
    //             // Load verifier user
    //             if ($test->pivot->verify_by) {
    //                 $test->verifier_id = $test->pivot->verify_by;
    //                 $test->verify_at = $test->pivot->verify_at;
                    
    //                 // Load the actual user data if needed
    //                 try {
    //                     $verifier = User::find($test->pivot->verify_by, ['id', 'name', 'email']);
    //                     if ($verifier) {
    //                         $test->verifier = $verifier;
    //                     }
    //                 } catch (\Exception $e) {
    //                     \Log::error("Error loading verifier user: " . $e->getMessage());
    //                 }
    //             }
                
    //             // Load pathologist user
    //             if ($test->pivot->pathologist_id) {
    //                 $test->pathologist_id = $test->pivot->pathologist_id;
                    
    //                 // Load the actual user data if needed
    //                 try {
    //                     $pathologist = User::find($test->pivot->pathologist_id, ['id', 'name', 'email']);
    //                     if ($pathologist) {
    //                         $test->pathologist = $pathologist;
    //                     }
    //                 } catch (\Exception $e) {
    //                     \Log::error("Error loading pathologist user: " . $e->getMessage());
    //                 }
    //             }
    //         });
            
    //         // Process each test result and group by test_id
    //         foreach ($laboratoryRequest->results as $result) {
    //             $testId = $result->test_id;
                
    //             if (!isset($testResultsMap[$testId])) {
    //                 $testResultsMap[$testId] = [
    //                     'parameters' => [],
    //                     'performer_id' => $result->performer_id,
    //                     'verifier_id' => $result->verifier_id,
    //                     'pathologist_id' => $result->pathologist_id,
    //                     'status' => $result->status
    //                 ];
    //             }
                
    //             // Add this result to the parameters array if it has a parameter
    //             if ($result->testParameter) {
    //                 // Log the result_comment for debugging
    //                 \Log::debug('⚡️ Processing test result with parameter:', [
    //                     'result_id' => $result->id,
    //                     'test_id' => $result->test_id,
    //                     'parameter_id' => $result->test_parameter_id,
    //                     'result_value' => $result->result_value,
    //                     'result_comment' => $result->result_comment,
    //                     'result_comment_type' => gettype($result->result_comment)
    //                 ]);
                
    //                 $testResultsMap[$testId]['parameters'][] = [
    //                     'id' => $result->testParameter->id,
    //                     'name' => $result->testParameter->name,
    //                     'test_name' => $result->testParameter->name,
    //                     'resultValue' => $result->result_value,
    //                     'unit' => $result->unit,
    //                     'instrument' => $result->equipment_id,
    //                     'equipment_name' => $result->equipment ? ($result->equipment->manufacturer . ' - ' . $result->equipment->model) : null,
    //                     'resultComment' => $result->result_comment,
    //                     'flag' => $result->flag,
    //                     'result_id' => $result->id, // Include the actual result ID for proper tracking
    //                     // Don't include reference ranges from saved results
    //                     // Let the frontend fetch these from test parameters directly
    //                 ];
    //             } else {
    //                 // Log non-parameterized test result
    //                 \Log::debug('⚡️ Processing non-parameterized test result:', [
    //                     'result_id' => $result->id,
    //                     'test_id' => $result->test_id,
    //                     'result_value' => $result->result_value,
    //                     'result_comment' => $result->result_comment,
    //                     'result_comment_type' => gettype($result->result_comment)
    //                 ]);
                    
    //                 // If it's a non-parameterized test, set the result directly on the test
    //                 $test = $tests->firstWhere('id', $testId);
    //                 if ($test) {
    //                     $test->initial_resultValue = $result->result_value;
    //                     $test->initial_instrument = $result->equipment_id;
    //                     $test->initial_resultComment = $result->result_comment;
    //                     $test->result_id = $result->id; // Include the actual result ID for proper tracking
    //                 }
    //             }
    //         }
            
    //         // Make sure result_comment is ONLY present in the parameters and NOT at the laboratory_request level
    //         // If a result_comment property accidentally exists at the laboratory_request level, remove it 
    //         if (property_exists($laboratoryRequest, 'result_comment')) {
    //             \Log::warning('⚡️ Found result_comment at laboratory_request level - removing it');
    //             unset($laboratoryRequest->result_comment);
    //         }
            
    //         // Debug log the final testParameters structure
    //         \Log::debug('⚡️ Final testParameters structure:', [
    //             'count' => count($testResultsMap),
    //             'sample' => array_slice($testResultsMap, 0, 1, true)
    //         ]);

    //         // Now associate the parameters with each test
    //         foreach ($tests as $test) {
    //             if (isset($testResultsMap[$test->id])) {
    //                 // Set the test parameters directly in the testParameters structure that the frontend expects
    //                 $testParameters = [];
    //                 $testParameters[$test->id] = $testResultsMap[$test->id]['parameters'];
    //                 $laboratoryRequest->testParameters = $testParameters;
                    
    //                 // Set the signatories
    //                 $laboratoryRequest->performer_id = $testResultsMap[$test->id]['performer_id'];
    //                 $laboratoryRequest->verifier_id = $testResultsMap[$test->id]['verifier_id'];
    //                 $laboratoryRequest->pathologist_id = $testResultsMap[$test->id]['pathologist_id'];
                    
    //                 // Add test result status - will be the same for all test parameters
    //                 $test->result_status = $testResultsMap[$test->id]['status'];
                    
    //                 // Also add the remarks and comment from the pivot
    //                 if ($test->pivot && $test->pivot->remarks) {
    //                     $laboratoryRequest->remarks = $test->pivot->remarks;
    //                 }
                    
    //                 if ($test->pivot && $test->pivot->comment) {
    //                     $laboratoryRequest->comment = $test->pivot->comment;
    //                 }
    //             }
    //         }

    //         // 2. Fetch User Lists for Dropdowns (if needed, can be separate API call)
    //         // Consider if these are truly needed here or could be fetched once by the frontend.
    //         $users = User::select('id', 'name')->orderBy('name')->get();
    //         $verifierList = $users;
    //         $performerList = $users;
    //         $pathologistList = $users;

    //          // 3. Calculate Age (can also be done on frontend)
    //         if (!$laboratoryRequest->patient_age && $laboratoryRequest->patient) {
    //             $laboratoryRequest->patient_age = $laboratoryRequest->patient && $laboratoryRequest->patient->date_of_birth
    //                 ? now()->diffInYears($laboratoryRequest->patient->date_of_birth)
    //                 : 'N/A';
    //         }

    //         // 4. Structure the response
    //         // Pass the main model and the user lists separately
    //         return response()->json([
    //             'request_details' => $laboratoryRequest,
    //             'user_lists' => [
    //                 'verifiers' => $verifierList,
    //                 'performers' => $performerList,
    //                 'pathologists' => $pathologistList,
    //             ]
    //         ]);

    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Laboratory request not found.'], 404);
    //     } catch (\Exception $e) {
    //         \Log::error("⚡️ Error in getHematologyRequestDetails: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return response()->json(['message' => 'An error occurred while fetching request details.'], 500);
    //     }
    // }

    // ----------------------------------------------------------------------------------
    // CHEMISTRY RESULT MANAGEMENT METHODS
    // ----------------------------------------------------------------------------------

/**
     * Fetch requests relevant to Hematology Result Management.
     *
     * Fetches requests that contain tests belonging to the 'Hematology' category
     * and includes relevant patient and test details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function getChemistryRequests(Request $request)
    // {
    //     // --- Configuration ---
    //     $hematologyCategoryName = 'Clinical Chemistry'; // Case-sensitive category name
    //     // ---------------------

    //     try {
    //         // Find the ID of the Hematology category
    //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();

    //         if (!$hematologyCategory) {
    //             Log::warning("Chemistry category '{$hematologyCategoryName}' not found.");
    //             // Return empty paginated response if category doesn't exist
    //             return response()->json(LaboratoryRequest::paginate($request->input('per_page', 15)));
    //         }

    //         $hematologyCategoryId = $hematologyCategory->id;

    //         $query = LaboratoryRequest::query();

    //         // 1. Filter Requests: Only include requests with at least one test in the Hematology category
    //         //    and where that test status is relevant for results (e.g., 'Accepted')
    //         $query->whereHas('tests', function ($testQuery) use ($hematologyCategoryId) {
    //             $testQuery->where('test_category_id', $hematologyCategoryId)
    //                       ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run']); // Statuses indicating results can be viewed/entered
    //         });

    //         // 2. Eager Load Relations:
    //         $query->with([
    //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email', // Select necessary patient fields
    //             'patientType:id,name', // Load patient type directly from LaboratoryRequest
    //             'ward:id,name',        // Load ward directly from LaboratoryRequest
    //             'room:id,name',        // Load room directly from LaboratoryRequest
    //             // Load ONLY the tests relevant to Hematology for this view
    //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
    //                 $testQuery->where('test_category_id', $hematologyCategoryId)
    //                           ->select('tests.id', 'tests.name') // Select only needed test fields
    //                           ->withPivot('item_status'); // Load pivot status
    //             }
    //         ]);

    //         // 3. Search Functionality (Optional - Example)
    //         if ($request->filled('search')) {
    //             $searchTerm = $request->input('search');
    //             $query->where(function ($q) use ($searchTerm) {
    //                 $q->where('id', 'like', "%{$searchTerm}%")
    //                   ->orWhere('sample_id', 'like', "%{$searchTerm}%")
    //                   ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
    //                       $patientQuery->where(DB::raw("CONCAT(lname, ' ', fname)"), 'like', "%{$searchTerm}%")
    //                                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
    //                                    ->orWhere('first_name', 'like', "%{$searchTerm}%")
    //                                    ->orWhere('middle_name', 'like', "%{$searchTerm}%")
    //                                    ->orWhere('id', 'like', "%{$searchTerm}%");
    //                   });
    //             });
    //         }

    //         // 4. Ordering
    //         $query->orderBy('priority_level', 'desc')
    //               ->orderBy('request_date', 'asc');

    //         // 5. Paginate
    //         $perPage = $request->input('per_page', 15);
    //         $requests = $query->paginate($perPage);

    //         // 6. Transform Data (Add computed fields like age, test list string, status)
    //         $requests->getCollection()->transform(function ($request) use ($hematologyCategoryId) {
    //             // Calculate Age
    //             //$request->patient_age = $request->patient && $request->patient->date_of_birth
    //             //    ? now()->diffInYears($request->patient->date_of_birth)
    //             //    : 'N/A';

    //             // Create comma-separated list of Hematology test names
    //             $request->hematology_test_list = $request->tests->pluck('name')->implode(', ');

    //             // Determine overall Hematology status (Placeholder Logic)
    //             // This needs refinement based on actual result entry/validation models/flags
    //             $hasValidated = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
    //             });
    //              $hasPrinted = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Result Printed';
    //             });
    //             $hasAccepted = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Accepted' || $test->pivot->item_status === 'Collected' || $test->pivot->item_status === 'Send-out' || $test->pivot->item_status === 'Parked';
    //             });
    //             $hasResults = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Processing Results';
    //             });
    //             $hasCheckedIn = $request->tests->contains(function ($test) {
    //                 return $test->pivot->item_status === 'Checked-in';
    //             });

    //             if($hasPrinted) {
    //                 $request->hematology_status = 'Printed';
    //             } elseif ($hasValidated) {
    //                  $request->hematology_status = 'Validated';
    //             } elseif ($hasAccepted) {
    //                 $request->hematology_status = 'Pending Sample';
    //             } elseif ($hasCheckedIn) {
    //                  $request->hematology_status = 'Checked-in';
    //             } elseif ($hasResults) {
    //                     $request->hematology_status = 'Processing Results';
    //             } else {
    //                  $request->hematology_status = 'Unknown'; // Or Unknown?
    //             }

    //             // Add test parameter counts for progress tracking
    //             foreach ($request->tests as $test) {
    //                 // Get the total parameter count for this test
    //                 $test->parameter_count = TestParameter::where('test_id', $test->id)->count();
                    
    //                 // Get the completed parameter count (parameters with results)
    //                 $test->completed_parameter_count = TestResult::where([
    //                     'laboratory_request_id' => $request->id,
    //                     'test_id' => $test->id,
    //                 ])->whereNotNull('test_parameter_id')->count();
                    
    //                 // Special case: if the test has no parameters, count a single non-parameterized result
    //                 if ($test->parameter_count === 0) {
    //                     $test->parameter_count = 1;
    //                     $test->completed_parameter_count = TestResult::where([
    //                         'laboratory_request_id' => $request->id,
    //                         'test_id' => $test->id,
    //                         'test_parameter_id' => null,
    //                     ])->count();
    //                 }
    //             }

    //             // Optionally unset the loaded tests relation if frontend doesn't need the full structure
    //             // unset($request->tests);

    //             return $request;
    //         });

    //         return response()->json($requests);

    //     } catch (\Exception $e) {
    //         Log::error("Error fetching Hematology requests: " . $e->getMessage());
    //         // Return a generic error response
    //         return response()->json(['message' => 'An error occurred while fetching Hematology requests.'], 500);
    //     }
    // }

    // // Add other result management methods here (e.g., getChemistryRequests, getMicrobiologyRequests)

    // /**
    //  * Fetch detailed information for a single Hematology request, including tests and results.
    //  *
    //  * @param  \App\Models\LaboratoryRequest  $laboratoryRequest
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // public function getChemistryRequestDetails(LaboratoryRequest $laboratoryRequest)
    // {
    //     // --- Configuration ---
    //     $hematologyCategoryName = 'Clinical Chemistry';
    //     // ---------------------

    //     try {
    //         // Find the ID of the Hematology category
    //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();
    //         if (!$hematologyCategory) {
    //              return response()->json(['message' => "Hematology category '{$hematologyCategoryName}' not found."], 404);
    //         }
    //         $hematologyCategoryId = $hematologyCategory->id;
            
    //         // Add debugging logs
    //         \Log::info('⚡️ Starting getHematologyRequestDetails for laboratory request ID: ' . $laboratoryRequest->id);

    //         // 1. Load the core request data and RELATIONS, including filtered tests
    //         $laboratoryRequest->load([
    //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email',
    //             'patientType:id,name',
    //             'ward:id,name',
    //             'room:id,name',
    //             'doctor:id,name', // Assuming Doctor model has 'name' attribute
    //             'creator:id,name',  // Assuming User model has 'name' attribute
    //             // Load ONLY Hematology tests with basic pivot data (like item_status, remarks, comment)
    //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
    //                 $testQuery->where('test_category_id', $hematologyCategoryId)
    //                           ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed'])
    //                           ->withPivot('id', 'remarks', 'comment', 'perform_by', 'perform_at', 'verify_by', 'verify_at', 'pathologist_id'); // Include tracking fields
    //             },
    //             // Load test results for this laboratory request
    //             'results' => function($query) use ($hematologyCategoryId) {
    //                 $query->whereHas('test', function($q) use ($hematologyCategoryId) {
    //                     $q->where('test_category_id', $hematologyCategoryId);
    //                 })
    //                 ->with(['test', 'testParameter', 'equipment']);
    //             }
    //         ]);

    //         // Log the loaded test results for debugging
    //         \Log::debug('⚡️ Loaded test results:', [
    //             'count' => $laboratoryRequest->results->count(),
    //             'sample' => $laboratoryRequest->results->take(3)->map(function($result) {
    //                 return [
    //                     'id' => $result->id,
    //                     'test_id' => $result->test_id,
    //                     'test_parameter_id' => $result->test_parameter_id,
    //                     'result_value' => $result->result_value,
    //                     'result_comment' => $result->result_comment
    //                 ];
    //             })
    //         ]);

    //         // Process the loaded test results and associate them with their respective tests
    //         $tests = $laboratoryRequest->tests;
            
    //         // Create a map to store test parameters and their results by test_id
    //         $testResultsMap = [];
            
    //         // Add user information for tracking fields
    //         $laboratoryRequest->tests->each(function($test) {
    //             // Load performer user
    //             if ($test->pivot->perform_by) {
    //                 $test->performer_id = $test->pivot->perform_by;
    //                 $test->perform_at = $test->pivot->perform_at;
                    
    //                 // Load the actual user data if needed
    //                 try {
    //                     $performer = \App\Models\User::find($test->pivot->perform_by, ['id', 'name', 'email']);
    //                     if ($performer) {
    //                         $test->performer = $performer;
    //                     }
    //                 } catch (\Exception $e) {
    //                     \Log::error("Error loading performer user: " . $e->getMessage());
    //                 }
    //             }
                
    //             // Load verifier user
    //             if ($test->pivot->verify_by) {
    //                 $test->verifier_id = $test->pivot->verify_by;
    //                 $test->verify_at = $test->pivot->verify_at;
                    
    //                 // Load the actual user data if needed
    //                 try {
    //                     $verifier = User::find($test->pivot->verify_by, ['id', 'name', 'email']);
    //                     if ($verifier) {
    //                         $test->verifier = $verifier;
    //                     }
    //                 } catch (\Exception $e) {
    //                     \Log::error("Error loading verifier user: " . $e->getMessage());
    //                 }
    //             }
                
    //             // Load pathologist user
    //             if ($test->pivot->pathologist_id) {
    //                 $test->pathologist_id = $test->pivot->pathologist_id;
                    
    //                 // Load the actual user data if needed
    //                 try {
    //                     $pathologist = User::find($test->pivot->pathologist_id, ['id', 'name', 'email']);
    //                     if ($pathologist) {
    //                         $test->pathologist = $pathologist;
    //                     }
    //                 } catch (\Exception $e) {
    //                     \Log::error("Error loading pathologist user: " . $e->getMessage());
    //                 }
    //             }
    //         });
            
    //         // Process each test result and group by test_id
    //         foreach ($laboratoryRequest->results as $result) {
    //             $testId = $result->test_id;
                
    //             if (!isset($testResultsMap[$testId])) {
    //                 $testResultsMap[$testId] = [
    //                     'parameters' => [],
    //                     'performer_id' => $result->performer_id,
    //                     'verifier_id' => $result->verifier_id,
    //                     'pathologist_id' => $result->pathologist_id,
    //                     'status' => $result->status
    //                 ];
    //             }
                
    //             // Add this result to the parameters array if it has a parameter
    //             if ($result->testParameter) {
    //                 // Log the result_comment for debugging
    //                 \Log::debug('⚡️ Processing test result with parameter:', [
    //                     'result_id' => $result->id,
    //                     'test_id' => $result->test_id,
    //                     'parameter_id' => $result->test_parameter_id,
    //                     'result_value' => $result->result_value,
    //                     'result_comment' => $result->result_comment,
    //                     'result_comment_type' => gettype($result->result_comment)
    //                 ]);
                
    //                 $testResultsMap[$testId]['parameters'][] = [
    //                     'id' => $result->testParameter->id,
    //                     'name' => $result->testParameter->name,
    //                     'test_name' => $result->testParameter->name,
    //                     'resultValue' => $result->result_value,
    //                     'unit' => $result->unit,
    //                     'instrument' => $result->equipment_id,
    //                     'equipment_name' => $result->equipment ? ($result->equipment->manufacturer . ' - ' . $result->equipment->model) : null,
    //                     'resultComment' => $result->result_comment,
    //                     'flag' => $result->flag,
    //                     'result_id' => $result->id, // Include the actual result ID for proper tracking
    //                     // Don't include reference ranges from saved results
    //                     // Let the frontend fetch these from test parameters directly
    //                 ];
    //             } else {
    //                 // Log non-parameterized test result
    //                 \Log::debug('⚡️ Processing non-parameterized test result:', [
    //                     'result_id' => $result->id,
    //                     'test_id' => $result->test_id,
    //                     'result_value' => $result->result_value,
    //                     'result_comment' => $result->result_comment,
    //                     'result_comment_type' => gettype($result->result_comment)
    //                 ]);
                    
    //                 // If it's a non-parameterized test, set the result directly on the test
    //                 $test = $tests->firstWhere('id', $testId);
    //                 if ($test) {
    //                     $test->initial_resultValue = $result->result_value;
    //                     $test->initial_instrument = $result->equipment_id;
    //                     $test->initial_resultComment = $result->result_comment;
    //                     $test->result_id = $result->id; // Include the actual result ID for proper tracking
    //                 }
    //             }
    //         }
            
    //         // Make sure result_comment is ONLY present in the parameters and NOT at the laboratory_request level
    //         // If a result_comment property accidentally exists at the laboratory_request level, remove it 
    //         if (property_exists($laboratoryRequest, 'result_comment')) {
    //             \Log::warning('⚡️ Found result_comment at laboratory_request level - removing it');
    //             unset($laboratoryRequest->result_comment);
    //         }
            
    //         // Debug log the final testParameters structure
    //         \Log::debug('⚡️ Final testParameters structure:', [
    //             'count' => count($testResultsMap),
    //             'sample' => array_slice($testResultsMap, 0, 1, true)
    //         ]);

    //         // Now associate the parameters with each test
    //         foreach ($tests as $test) {
    //             if (isset($testResultsMap[$test->id])) {
    //                 // Set the test parameters directly in the testParameters structure that the frontend expects
    //                 $testParameters = [];
    //                 $testParameters[$test->id] = $testResultsMap[$test->id]['parameters'];
    //                 $laboratoryRequest->testParameters = $testParameters;
                    
    //                 // Set the signatories
    //                 $laboratoryRequest->performer_id = $testResultsMap[$test->id]['performer_id'];
    //                 $laboratoryRequest->verifier_id = $testResultsMap[$test->id]['verifier_id'];
    //                 $laboratoryRequest->pathologist_id = $testResultsMap[$test->id]['pathologist_id'];
                    
    //                 // Add test result status - will be the same for all test parameters
    //                 $test->result_status = $testResultsMap[$test->id]['status'];
                    
    //                 // Also add the remarks and comment from the pivot
    //                 if ($test->pivot && $test->pivot->remarks) {
    //                     $laboratoryRequest->remarks = $test->pivot->remarks;
    //                 }
                    
    //                 if ($test->pivot && $test->pivot->comment) {
    //                     $laboratoryRequest->comment = $test->pivot->comment;
    //                 }
    //             }
    //         }

    //         // 2. Fetch User Lists for Dropdowns (if needed, can be separate API call)
    //         // Consider if these are truly needed here or could be fetched once by the frontend.
    //         $users = User::select('id', 'name')->orderBy('name')->get();
    //         $verifierList = $users;
    //         $performerList = $users;
    //         $pathologistList = $users;

    //          // 3. Calculate Age (can also be done on frontend)
    //         if (!$laboratoryRequest->patient_age && $laboratoryRequest->patient) {
    //             $laboratoryRequest->patient_age = $laboratoryRequest->patient && $laboratoryRequest->patient->date_of_birth
    //                 ? now()->diffInYears($laboratoryRequest->patient->date_of_birth)
    //                 : 'N/A';
    //         }

    //         // 4. Structure the response
    //         // Pass the main model and the user lists separately
    //         return response()->json([
    //             'request_details' => $laboratoryRequest,
    //             'user_lists' => [
    //                 'verifiers' => $verifierList,
    //                 'performers' => $performerList,
    //                 'pathologists' => $pathologistList,
    //             ]
    //         ]);

    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['message' => 'Laboratory request not found.'], 404);
    //     } catch (\Exception $e) {
    //         \Log::error("⚡️ Error in getHematologyRequestDetails: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return response()->json(['message' => 'An error occurred while fetching request details.'], 500);
    //     }
    // }

    // /**
    //  * Fetch parameters for a specific test within a laboratory request, 
    //  * including any existing results.
    //  *
    //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
    //  * @param  \\App\\Models\\Test  $test
    //  * @param  \\Illuminate\\Http\\Request  $request (for is_rerun, though not used yet)
    //  * @return \\Illuminate\\Http\\JsonResponse
    //  */
    // public function getChemistryTestParameters(LaboratoryRequest $laboratoryRequest, Test $test, Request $request)
    // {
    //     // is_rerun logic is not implemented yet as per user's information.
    //     // For now, this will fetch current parameters and their saved results.

    //     try {
    //         $testTypeParameters = TestParameter::where('test_id', $test->id)
    //                                 ->orderBy('order', 'asc')
    //                                 ->get([
    //                                     'id as parameter_id', 
    //                                     'parameter_name',
    //                                     'test_id',
    //                                     'si_unit', 
    //                                     'si_default_value',
    //                                     'conventional_unit', 
    //                                     'conventional_default_value',
    //                                     'conversion_factor',
    //                                     'decimal_places',
    //                                     'reference_note'
    //                                 ]);

    //         if ($testTypeParameters->isEmpty()) {
    //             return response()->json([]); // Return empty if no parameters defined for the test
    //         }

    //         $patientAgeYears = $laboratoryRequest->patient ? Carbon::parse($laboratoryRequest->patient->date_of_birth)->age : null;
    //         $patientGender = $laboratoryRequest->patient ? $laboratoryRequest->patient->gender : null;

    //         $parametersWithResults = $testTypeParameters->map(function ($paramDefinition) use ($laboratoryRequest, $patientAgeYears, $patientGender) {
    //             $currentResult = TestResult::where('laboratory_request_id', $laboratoryRequest->id)
    //                                        ->where('test_id', $paramDefinition->test_id) // Ensure test_id context
    //                                        ->where('test_parameter_id', $paramDefinition->parameter_id)
    //                                        ->first();
               
    //             $referenceRangeData = ReferenceRange::getEffectiveReferenceRange(
    //                     $paramDefinition->parameter_id, 
    //                     $patientAgeYears,
    //                     $patientGender,
    //                     $currentResult->equipment_id ?? null
    //                 );

    //             $paramData = [
    //                 'test_result_id' => $currentResult->id ?? null,
    //                 'parameter_id' => $paramDefinition->parameter_id,
    //                 'parameter_name' => $paramDefinition->parameter_name,
    //                 'result_value' => $currentResult->result_value ?? $paramDefinition->si_default_value ?? null,
    //                 'si_unit' => $paramDefinition->si_unit,
    //                 'si_ref_range' => $referenceRangeData['si_range'] ?? 'N/A',
    //                 'conventional_value' => $currentResult->conventional_value ?? null,
    //                 'conventional_unit' => $paramDefinition->conventional_unit,
    //                 'conventional_ref_range' => $referenceRangeData['conventional_range'] ?? 'N/A',
    //                 'instrument_id' => $currentResult->equipment_id ?? null,
    //                 'result_remarks' => $currentResult->result_comment ?? '',
    //                 'flag' => $currentResult->flag ?? null,
    //                 'status' => $currentResult->status ?? 'Draft',
    //                 'decimal_places' => $paramDefinition->decimal_places,
    //                 'reference_note' => $paramDefinition->reference_note,
    //             ];
               
    //             // Calculate conventional value from SI if no current result and SI default exists
    //             if (!$currentResult && $paramData['result_value'] !== null && $paramDefinition->conversion_factor) {
    //                 $paramData['conventional_value'] = (float)$paramData['result_value'] * (float)$paramDefinition->conversion_factor;
    //                 if ($paramDefinition->decimal_places !== null) {
    //                     $paramData['conventional_value'] = round($paramData['conventional_value'], $paramDefinition->decimal_places);
    //                 }
    //             }
    //             return $paramData;
    //         });

    //         return response()->json($parametersWithResults);

    //     } catch (\Exception $e) {
    //         Log::error("Error fetching Chemistry test parameters for lab request ID {$laboratoryRequest->id}, test ID {$test->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return response()->json(['message' => 'An error occurred while fetching test parameters.'], 500);
    //     }
    // }

    // /**
    //  * Save Chemistry results for a laboratory request.
    //  *
    //  * @param  \\Illuminate\\Http\\Request  $request
    //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
    //  * @return \\Illuminate\\Http\\JsonResponse
    //  */
    // public function saveChemistryResults(Request $request, LaboratoryRequest $laboratoryRequest)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'test_results' => 'present|array', // Array of parameters to save
    //         'test_results.*.test_result_id' => 'nullable|integer|exists:test_results,id',
    //         'test_results.*.parameter_id' => 'required_without:test_results.*.test_result_id|integer|exists:test_parameters,id',
    //         'test_results.*.result_value' => 'nullable', // Allow null to clear result
    //         'test_results.*.instrument_id' => 'nullable|integer|exists:analyzers,id',
    //         'test_results.*.result_remarks' => 'nullable|string',
    //         'performed_by_id' => 'nullable|integer|exists:users,id',
    //         'verified_by_id' => 'nullable|integer|exists:users,id', // For potential direct save with verifier
    //         'pathologist_id' => 'nullable|integer|exists:users,id', // For potential direct save with pathologist
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $chemistryCategoryName = 'Chemistry';
    //         $chemistryCategory = TestCategory::where('name', $chemistryCategoryName)->firstOrFail();

    //         $savedTestResultIds = [];
    //         $affectedRequestTestPivots = []; // To store [test_id => pivot_data_updates]

    //         foreach ($request->input('test_results', []) as $paramResultData) {
    //             $parameter = TestParameter::with('test')->find($paramResultData['parameter_id']);
    //             if (!$parameter || $parameter->test->test_category_id !== $chemistryCategory->id) {
    //                 Log::warning('Attempted to save result for non-chemistry parameter or invalid parameter.', $paramResultData);
    //                 continue; // Skip this parameter
    //             }

    //             $testId = $parameter->test_id;

    //             $resultAttributes = [
    //                 'laboratory_request_id' => $laboratoryRequest->id,
    //                 'test_id' => $testId,
    //                 'test_parameter_id' => $paramResultData['parameter_id'],
    //             ];
                
    //             $resultValues = [
    //                 'result_value' => $paramResultData['result_value'],
    //                 'equipment_id' => $paramResultData['instrument_id'] ?? null, // Frontend sends instrument_id
    //                 'result_comment' => $paramResultData['result_remarks'] ?? '',
    //                 'status' => 'Saved',
    //             ];

    //             $currentVersion = 0;
    //             if (isset($paramResultData['test_result_id']) && $paramResultData['test_result_id']) {
    //                 // This is a re-run or update of an existing result
    //                 $existingResult = TestResult::find($paramResultData['test_result_id']);
    //                 if ($existingResult) {
    //                     $currentVersion = $existingResult->version + 1;
    //                     // Optionally, mark the old result as superseded or inactive if needed
    //                     // For now, we just create a new one with an incremented version
    //                 }
    //             }

    //             $testResult = new TestResult();
    //             $testResult->fill($resultAttributes);
    //             $testResult->fill($resultValues);
    //             $testResult->version = $currentVersion;
    //             $testResult->save();
    //             $testResult->calculateFlag(); // Assumes TestResult model has this method
    //             $savedTestResultIds[] = $testResult->id;

    //             // Mark this test in the pivot as having results being processed
    //             if (!isset($affectedRequestTestPivots[$testId])) {
    //                 $affectedRequestTestPivots[$testId] = ['item_status' => 'Processing Results'];
    //             }
    //         }

    //         // Update personnel on the pivot table (laboratory_request_test)
    //         // This applies to all tests within the chemistry category for this request
    //         // if personnel are provided at the top level of the save request.
    //         $mainPersonnelUpdates = [];
    //         if ($request->filled('performed_by_id')) {
    //             $mainPersonnelUpdates['perform_by'] = $request->input('performed_by_id');
    //             $mainPersonnelUpdates['perform_at'] = now();
    //         }
    //         // If saving also implies verification (less common for just 'Save' button)
    //         if ($request->filled('verified_by_id')) {
    //              $mainPersonnelUpdates['verify_by'] = $request->input('verified_by_id');
    //              $mainPersonnelUpdates['verify_at'] = now();
    //              // If verified, typically all items within should also be marked as at least 'Saved' or 'Validated'
    //              // For simplicity, this save action primarily focuses on 'Saved' status for parameters.
    //         }
    //          if ($request->filled('pathologist_id')) {
    //              $mainPersonnelUpdates['pathologist_id'] = $request->input('pathologist_id');
    //         }

    //         if (!empty($mainPersonnelUpdates)) {
    //             foreach ($laboratoryRequest->tests()->where('test_category_id', $chemistryCategory->id)->get() as $requestTest) {
    //                 if (isset($affectedRequestTestPivots[$requestTest->id])) {
    //                     $pivotUpdates = array_merge($affectedRequestTestPivots[$requestTest->id], $mainPersonnelUpdates);
    //                 } else {
    //                     $pivotUpdates = $mainPersonnelUpdates;
    //                 }
    //                 $laboratoryRequest->tests()->updateExistingPivot($requestTest->id, $pivotUpdates);
    //             }
    //         } elseif (!empty($affectedRequestTestPivots)) {
    //              foreach ($affectedRequestTestPivots as $testIdToUpdate => $pivotDataOnlyStatus) {
    //                 $laboratoryRequest->tests()->updateExistingPivot($testIdToUpdate, $pivotDataOnlyStatus);
    //              }
    //         }

    //         // Update overall request status if all chemistry tests are now 'Saved' or further
    //         $this->updateOverallRequestStatus($laboratoryRequest, $chemistryCategory->id);

    //         DB::commit();
    //         return response()->json(['message' => 'Chemistry results saved successfully.', 'saved_ids' => $savedTestResultIds], 200);

    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         DB::rollBack();
    //         Log::error("Error saving Chemistry results: Category not found. " . $e->getMessage());
    //         return response()->json(['message' => 'Configuration error: Chemistry category not found.'], 500);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Error saving Chemistry results for lab request ID {$laboratoryRequest->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return response()->json(['message' => 'An error occurred while saving Chemistry results.'], 500);
    //     }
    // }

    // /**
    //  * Handle Print & Release for Chemistry results.
    //  *
    //  * @param  \\Illuminate\\Http\\Request  $request
    //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
    //  * @return \\Illuminate\\Http\\JsonResponse
    //  */
    // public function printReleaseChemistryResults(Request $request, LaboratoryRequest $laboratoryRequest)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'test_result_ids' => 'required|array',
    //         'test_result_ids.*' => 'integer|exists:test_results,id',
    //         'performed_by_id' => 'required|integer|exists:users,id',
    //         'verified_by_id' => 'required|integer|exists:users,id',
    //         'pathologist_id' => 'nullable|integer|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $chemistryCategoryName = 'Chemistry';
    //         $chemistryCategory = TestCategory::where('name', $chemistryCategoryName)->firstOrFail();
            
    //         $testResultIds = $request->input('test_result_ids');
    //         TestResult::whereIn('id', $testResultIds)
    //                     ->where('laboratory_request_id', $laboratoryRequest->id)
    //                     ->update(['status' => 'Printed']); 
            
    //         // Update pivot table for associated tests
    //         $affectedTestIds = TestResult::whereIn('id', $testResultIds)->pluck('test_id')->unique();
    //         $pivotUpdates = [
    //             'item_status' => 'Result Printed',
    //             'perform_by' => $request->input('performed_by_id'),
    //             'perform_at' => now(), // Or use existing perform_at if already set and not re-assigning performer
    //             'verify_by' => $request->input('verified_by_id'),
    //             'verify_at' => now(),
    //         ];
    //         if ($request->filled('pathologist_id')) {
    //             $pivotUpdates['pathologist_id'] = $request->input('pathologist_id');
    //         }
    //         $laboratoryRequest->tests()->whereIn('tests.id', $affectedTestIds)->where('test_category_id', $chemistryCategory->id)->updateExistingPivot($affectedTestIds, $pivotUpdates);

    //         $this->updateOverallRequestStatus($laboratoryRequest, $chemistryCategory->id);
            
    //         // TODO: PDF Generation Logic here
    //         // $pdf = generateChemistryReport($laboratoryRequest, $testResultIds);
    //         // return $pdf->download('chemistry-report-'.$laboratoryRequest->id.'.pdf');

    //         DB::commit();
    //         // For now, returning a success message. Frontend expects blob for auto-print.
    //         // This needs to be changed to return a PDF stream once PDF generation is implemented.
    //         // Create a dummy PDF blob for now to match frontend expectation
    //         $dummyPdfContent = "Dummy PDF content for request " . $laboratoryRequest->id;
    //         return response($dummyPdfContent, 200)
    //             ->header('Content-Type', 'application/pdf')
    //             ->header('Content-Disposition', 'inline; filename="chemistry-report-'.$laboratoryRequest->id.'.pdf"');

    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         DB::rollBack();
    //         Log::error("Error printing Chemistry results: Category not found. " . $e->getMessage());
    //         return response()->json(['message' => 'Configuration error: Chemistry category not found.'], 500);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Error printing Chemistry results for lab request ID {$laboratoryRequest->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return response()->json(['message' => 'An error occurred while printing Chemistry results.'], 500);
    //     }
    // }

    // /**
    //  * Handle Validate & Release for Chemistry results.
    //  *
    //  * @param  \\Illuminate\\Http\\Request  $request
    //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
    //  * @return \\Illuminate\\Http\\JsonResponse
    //  */
    // public function validateReleaseChemistryResults(Request $request, LaboratoryRequest $laboratoryRequest)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'test_result_ids' => 'required|array',
    //         'test_result_ids.*' => 'integer|exists:test_results,id',
    //         'performed_by_id' => 'required|integer|exists:users,id',
    //         'verified_by_id' => 'required|integer|exists:users,id',
    //         'pathologist_id' => 'nullable|integer|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $chemistryCategoryName = 'Chemistry';
    //         $chemistryCategory = TestCategory::where('name', $chemistryCategoryName)->firstOrFail();

    //         $testResultIds = $request->input('test_result_ids');
            
    //         // Check for QC lockout before releasing results
    //         $testResults = TestResult::whereIn('id', $testResultIds)
    //                     ->where('laboratory_request_id', $laboratoryRequest->id)
    //                     ->with(['testParameter', 'equipment'])
    //                     ->get();

    //         Log::info('QC Lockout Check - Test Result IDs:', $testResultIds);
    //         Log::info('QC Lockout Check - Found results count: ' . $testResults->count());

    //         $qcLockoutService = new QualityControlLockoutService();
    //         $lockedParameters = [];
            
    //         foreach ($testResults as $result) {
    //             Log::info("QC Lockout Check - Result ID: {$result->id}, Equipment: {$result->equipment_id}, Parameter: {$result->test_parameter_id}");
                
    //             if ($result->equipment_id && $result->test_parameter_id) {
    //                 $qcStatus = $qcLockoutService->checkQCStatus($result->equipment_id, $result->test_parameter_id);
    //                 Log::info("QC Lockout Check - QC Status:", ['is_in_control' => $qcStatus['is_in_control'], 'violations' => $qcStatus['violations']]);
                    
    //                 if (!$qcStatus['is_in_control']) {
    //                     $lockedParameters[] = [
    //                         'parameter' => $result->testParameter->test_name ?? 'Unknown',
    //                         'violations' => implode(', ', $qcStatus['violations'])
    //                     ];
    //                 }
    //             }
    //         }
            
    //         Log::info('QC Lockout Check - Locked Parameters Count: ' . count($lockedParameters));

    //         // If QC is out of control, block the release
    //         if (!empty($lockedParameters)) {
    //             // Mark affected test results as locked
    //             foreach ($testResults as $result) {
    //                 if ($result->equipment_id && $result->test_parameter_id) {
    //                     $qcStatus = $qcLockoutService->checkQCStatus($result->equipment_id, $result->test_parameter_id);
    //                     if (!$qcStatus['is_in_control']) {
    //                         $result->qc_lockout_active = true;
    //                         $result->qc_lockout_date = now();
    //                         $result->requires_review = true;
    //                         $result->save();
    //                     }
    //                 }
    //             }
                
    //             DB::rollBack();
    //             return response()->json([
    //                 'message' => 'Quality Control lockout active. Results cannot be released.',
    //                 'locked_parameters' => $lockedParameters,
    //                 'error_type' => 'qc_lockout'
    //             ], 403);
    //         }

    //         // Run CDS correlation rules for ALL categories (generic engine)
    //         $cdsViolation = $this->runCdsChecks($laboratoryRequest, $testResults);
    //         if ($cdsViolation !== null) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'message' => $cdsViolation['message'] ?? 'CDS rule triggered. Auto-verification stopped.',
    //                 'rule' => $cdsViolation['rule'] ?? null,
    //                 'action' => $cdsViolation['action'] ?? 'STOP_AUTO_VERIFY',
    //                 'error_type' => 'cds_block'
    //             ], 409);
    //         }

    //         // QC is in control and CDS passed, proceed with validation
    //         TestResult::whereIn('id', $testResultIds)
    //                     ->where('laboratory_request_id', $laboratoryRequest->id)
    //                     ->update(['status' => 'Validated']);
            
    //         $affectedTestIds = TestResult::whereIn('id', $testResultIds)->pluck('test_id')->unique();
    //         $pivotUpdates = [
    //             'item_status' => 'Result Validated',
    //             'perform_by' => $request->input('performed_by_id'),
    //             'perform_at' => now(), // Or use existing perform_at
    //             'verify_by' => $request->input('verified_by_id'),
    //             'verify_at' => now(),
    //         ];
    //         if ($request->filled('pathologist_id')) {
    //             $pivotUpdates['pathologist_id'] = $request->input('pathologist_id');
    //         }
    //         $laboratoryRequest->tests()->whereIn('tests.id', $affectedTestIds)->where('test_category_id', $chemistryCategory->id)->updateExistingPivot($affectedTestIds, $pivotUpdates);

    //         $this->updateOverallRequestStatus($laboratoryRequest, $chemistryCategory->id);

    //         DB::commit();
    //         return response()->json(['message' => 'Chemistry results validated successfully.'], 200);

    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         DB::rollBack();
    //         Log::error("Error validating Chemistry results: Category not found. " . $e->getMessage());
    //         return response()->json(['message' => 'Configuration error: Chemistry category not found.'], 500);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Error validating Chemistry results for lab request ID {$laboratoryRequest->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    //         return response()->json(['message' => 'An error occurred while validating Chemistry results.'], 500);
    //     }
    // }

    /**
     * Helper function to update the overall status of a LaboratoryRequest 
     * based on the status of its tests within a specific category.
     */
    private function updateOverallRequestStatus(LaboratoryRequest $laboratoryRequest, $categoryId)
    {
        $allCategoryTests = $laboratoryRequest->tests()->where('test_category_id', $categoryId)->get();
        if ($allCategoryTests->isEmpty()) {
            return; // No tests in this category for this request
        }

        $allValidated = $allCategoryTests->every(function ($test) {
            return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
        });

        $allPrinted = $allCategoryTests->every(function ($test) {
            return $test->pivot->item_status === 'Result Printed';
        });

        $newOverallStatus = null; // Overall status is now derived from pivot item_status only

        if ($allPrinted) {
            $newOverallStatus = 'Printed';
        } elseif ($allValidated) {
            $newOverallStatus = 'Validated';
        } else {
            // Check if any are still pending or processing to determine intermediate status
            $hasProcessing = $allCategoryTests->some(fn($test) => in_array($test->pivot->item_status, ['Processing Results', 'Checked-in', 'Accepted', 'Pending Sample']));
            if ($hasProcessing) {
                 // This part might need more granular logic based on your exact overall status flow
                 // For now, if not all validated/printed but some are processing, it might remain 'Processing' or similar
                 // Or, if the frontend status for Chemistry is derived (like in getChemistryRequests), this overall LaboratoryRequest.status might be more global.
                 // Let's assume for now that if any Chemistry test is being processed, the overall status is at least 'Processing' (if not already 'Validated' or 'Printed').
                 if(!in_array($laboratoryRequest->status, ['Validated', 'Printed'])){
                    // $newOverallStatus = 'Processing'; // Example status
                 }
            }
        }
        
        // Do not persist overall status to laboratory_requests table; frontend derives per-category status from pivot
    }

    /**
     * Execute CDS engine for a set of results within a request.
     * Returns an array with info if a blocking action is required, otherwise null.
     */
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
                        // Check if already acknowledged/created
                        $existingReflex = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                            ->where('rule_type', 'CDS')
                            ->where('rule_id', $rule->id)
                            ->where('decision', 'reflex_created')
                            ->exists();
                        
                        if (!$existingReflex) {
                            // Return special response indicating reflex test is needed
                            return [
                                'action' => 'REFLEX_TEST',
                                'message' => $rule->alert_message ?? 'CDS suggests a reflex test.',
                                'rule' => $rule,
                                'requires_approval' => true, // Frontend should show approval dialog
                                'reflex_test_id' => $rule->reflex_test_id,
                            ];
                        }
                        // Already created, don't block
                        return null;
                    }
                    if ($rule->action_type === 'ADD_COMMENT') {
                        // Not blocking; could append comments to results in future.
                        // Continue without blocking.
                        continue;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('CDS Engine error: '.$e->getMessage());
            // Fail-open: do not block release if engine fails
            return null;
        }

        return null;
    }

    /**
     * Record a rule decision audit entry.
     *
     * @param array $data
     * @return void
     */
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

    /**
     * Approve and create reflex test(s)
     * POST /api/laboratory-requests/{id}/approve-reflex-test
     *
     * @param Request $request
     * @param LaboratoryRequest $laboratoryRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveReflexTest(Request $request, LaboratoryRequest $laboratoryRequest)
    {
        Log::info('approveReflexTest called', [
            'parent_request_id' => $laboratoryRequest->id,
            'parent_sample_id' => $laboratoryRequest->sample_id,
            'parent_patient_id' => $laboratoryRequest->patient_id,
            'parent_patient_name' => $laboratoryRequest->patient ? $laboratoryRequest->patient->last_name . ', ' . $laboratoryRequest->patient->first_name : 'N/A',
            'rule_id' => $request->input('rule_id'),
            'reflex_test_id' => $request->input('reflex_test_id'),
            'user_id' => Auth::id()
        ]);

        $validated = $request->validate([
            'rule_id' => 'required|integer|exists:cds_correlation_rules,id',
            'reflex_test_id' => 'required|integer|exists:test_parameters,id',
            'auto_create' => 'boolean',
        ]);

        $rule = CdsCorrelationRule::findOrFail($validated['rule_id']);
        $user = Auth::user();
        
        if (!$user) {
            Log::warning('Reflex approval failed: User not authenticated');
            return response()->json(['message' => 'Unauthenticated. Please log in again.'], 401);
        }

        Log::info('Approving reflex test', [
            'parent_request_id' => $laboratoryRequest->id,
            'parent_sample_id' => $laboratoryRequest->sample_id,
            'parent_patient_id' => $laboratoryRequest->patient_id,
            'rule_id' => $rule->id,
            'rule_name' => $rule->rule_name,
            'reflex_test_id' => $validated['reflex_test_id'],
            'user_id' => $user->id
        ]);

        // CRITICAL SAFEGUARD: Verify that this request actually has a result for the trigger test parameter
        // This prevents creating reflex tests for the wrong request if there's a mismatch
        $triggerResultExists = TestResult::where('laboratory_request_id', $laboratoryRequest->id)
            ->where('test_parameter_id', $rule->trigger_test_id)
            ->whereNotNull('result_value')
            ->exists();

        if (!$triggerResultExists) {
            Log::warning('Reflex approval blocked: No trigger test result found for this request', [
                'request_id' => $laboratoryRequest->id,
                'sample_id' => $laboratoryRequest->sample_id,
                'patient_id' => $laboratoryRequest->patient_id,
                'rule_id' => $rule->id,
                'trigger_test_id' => $rule->trigger_test_id,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'message' => 'Cannot approve reflex: This request does not contain the required test result that triggered the rule. Please verify you are approving the correct request.'
            ], 400);
        }

        $service = new ReflexTestService();

        try {
            DB::beginTransaction();

            // Create the reflex test(s)
            $createdRequests = $service->createReflexTests(
                $laboratoryRequest,
                $rule,
                $user,
                $validated['auto_create'] ?? false
            );

            // Record the approval decision
            $this->recordRuleDecision([
                'rule_type' => 'CDS',
                'decision' => 'reflex_created',
                'rule_id' => $rule->id,
                'message' => "Reflex test approved. Created " . count($createdRequests) . " request(s).",
                'laboratory_request_id' => $laboratoryRequest->id,
                'user_id' => $user->id,
                'context' => [
                    'reflex_requests' => collect($createdRequests)->pluck('id')->toArray(),
                    'reflex_test_id' => $validated['reflex_test_id'],
                ]
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Reflex test(s) created successfully',
                'reflex_requests' => $createdRequests,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reflex test creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create reflex test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deny reflex test suggestion
     * POST /api/laboratory-requests/{id}/deny-reflex-test
     *
     * @param Request $request
     * @param LaboratoryRequest $laboratoryRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function denyReflexTest(Request $request, LaboratoryRequest $laboratoryRequest)
    {
        $validated = $request->validate([
            'rule_id' => 'required|integer|exists:cds_correlation_rules,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        $this->recordRuleDecision([
            'rule_type' => 'CDS',
            'decision' => 'reflex_denied',
            'rule_id' => $validated['rule_id'],
            'message' => $validated['reason'] ?? 'Reflex test denied by user',
            'laboratory_request_id' => $laboratoryRequest->id,
            'user_id' => $user->id,
            'context' => ['denied_at' => now()->toDateTimeString()]
        ]);

        return response()->json(['message' => 'Reflex test denied']);
    }

    /**
     * Check CDS rules for a laboratory request and return all triggered rules.
     * This endpoint is used by the frontend to display CDS alerts.
     *
     * @param LaboratoryRequest $laboratoryRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCdsRules(LaboratoryRequest $laboratoryRequest)
    {
        try {
            // Get all test results for this request
            $testResults = TestResult::where('laboratory_request_id', $laboratoryRequest->id)
                ->whereNotNull('test_parameter_id')
                ->whereNotNull('result_value')
                ->get();

            if ($testResults->isEmpty()) {
                return response()->json([
                    'triggered_rules' => [],
                    'has_blocking_rules' => false
                ]);
            }

            $parameterIds = $testResults->pluck('test_parameter_id')->filter()->unique()->values()->toArray();

            // Get all active CDS rules that might be triggered by these parameters
            $rules = CdsCorrelationRule::active()
                ->whereIn('trigger_test_id', $parameterIds)
                ->get();

            if ($rules->isEmpty()) {
                return response()->json([
                    'triggered_rules' => [],
                    'has_blocking_rules' => false
                ]);
            }

            $engine = new CdsRuleEngine();
            $triggeredRules = [];
            $hasBlockingRules = false;

            // Check if this request has already been acknowledged for CDS rules
            $isAcknowledged = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                ->where('rule_type', 'CDS')
                ->where('decision', 'acknowledged')
                ->exists();

            // Check if reflex tests have been created for this request
            $reflexCreatedRuleIds = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
                ->where('rule_type', 'CDS')
                ->where('decision', 'reflex_created')
                ->pluck('rule_id')
                ->toArray();

            foreach ($rules as $rule) {
                $correlated = collect(explode(',', (string)$rule->correlated_tests))
                    ->filter()
                    ->map(fn($v) => (int)trim($v))
                    ->values()
                    ->toArray();

                $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $parameterIds)));
                $fires = $engine->evaluate((array)$rule->rule_logic_json, $laboratoryRequest, $needed);

                if ($fires) {
                    $isBlocking = in_array($rule->action_type, ['STOP_AUTO_VERIFY', 'REFLEX_TEST']);
                    
                    // Check if this specific rule has had reflex created
                    $hasReflexCreated = in_array($rule->id, $reflexCreatedRuleIds);
                    
                    // For reflex test actions, consider it "acknowledged" if reflex was created
                    $isRuleAcknowledged = $isAcknowledged || $hasReflexCreated;
                    
                    // If it's blocking but acknowledged, we still include it in triggered_rules
                    // but we don't mark the whole request as blocked.
                    if ($isBlocking && !$isRuleAcknowledged) {
                        $hasBlockingRules = true;
                    }

                    $triggeredRules[] = [
                        'id' => $rule->id,
                        'rule_name' => $rule->rule_name,
                        'alert_message' => $rule->alert_message ?? 'CDS rule triggered.',
                        'action_type' => $rule->action_type,
                        'is_blocking' => $isBlocking,
                        'is_acknowledged' => $isRuleAcknowledged, // Pass this info to frontend
                        'reflex_created' => $hasReflexCreated, // Explicit flag for reflex tests
                        'trigger_test_id' => $rule->trigger_test_id,
                        'reflex_test_id' => $rule->reflex_test_id,
                    ];
                }
            }

            return response()->json([
                'triggered_rules' => $triggeredRules,
                'has_blocking_rules' => $hasBlockingRules,
                'is_acknowledged' => $isAcknowledged
            ]);

        } catch (\Throwable $e) {
            Log::error('CDS Check endpoint error: '.$e->getMessage());
            return response()->json([
                'triggered_rules' => [],
                'has_blocking_rules' => false,
                'error' => 'Error checking CDS rules'
            ], 500);
        }
    }

    /**
     * Bulk check rules (CDS, Delta, Critical) for multiple laboratory requests
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkCheckRules(Request $request)
    {
        try {
            $requestIds = $request->input('request_ids', []);
            if (empty($requestIds)) {
                return response()->json(['results' => []]);
            }

            $laboratoryRequests = LaboratoryRequest::whereIn('id', $requestIds)->get();
            $results = [];
            $engine = new CdsRuleEngine();

            // Pre-fetch all active CDS rules to avoid redundant queries
            $allActiveRules = CdsCorrelationRule::active()->get();

            // Pre-fetch all acknowledgments for these request IDs (CDS, Delta, Critical)
            $allAcks = RuleDecisionAudit::whereIn('laboratory_request_id', $requestIds)
                ->where('decision', 'acknowledged')
                ->get()
                ->groupBy('laboratory_request_id');

            // Pre-fetch all reflex created decisions for these request IDs
            $allReflexCreated = RuleDecisionAudit::whereIn('laboratory_request_id', $requestIds)
                ->where('decision', 'reflex_created')
                ->get()
                ->groupBy('laboratory_request_id');

            foreach ($laboratoryRequests as $laboratoryRequest) {
                $acks = $allAcks->get($laboratoryRequest->id, collect());
                $isCdsAcknowledged = $acks->where('rule_type', 'CDS')->isNotEmpty();
                $isDeltaAcknowledged = $acks->where('rule_type', 'DELTA_CHECK')->isNotEmpty();
                $isCriticalAcknowledged = $acks->where('rule_type', 'CRITICAL_VALUE')->isNotEmpty();

                // Get reflex created rule IDs for this request
                $reflexCreatedRuleIds = $allReflexCreated->get($laboratoryRequest->id, collect())
                    ->pluck('rule_id')
                    ->toArray();

                $testResults = TestResult::where('laboratory_request_id', $laboratoryRequest->id)
                    ->whereNotNull('test_parameter_id')
                    ->whereNotNull('result_value')
                    ->get();

                // 1. CDS Rule Checks
                $cdsResults = [
                    'triggered_rules' => [],
                    'has_blocking_rules' => false,
                    'is_acknowledged' => $isCdsAcknowledged
                ];

                if (!$testResults->isEmpty()) {
                    $parameterIds = $testResults->pluck('test_parameter_id')->filter()->unique()->values()->toArray();
                    $relevantRules = $allActiveRules->filter(fn($rule) => in_array($rule->trigger_test_id, $parameterIds));

                    foreach ($relevantRules as $rule) {
                        $correlated = collect(explode(',', (string)$rule->correlated_tests))
                            ->filter()
                            ->map(fn($v) => (int)trim($v))
                            ->values()
                            ->toArray();

                        $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $parameterIds)));
                        if ($engine->evaluate((array)$rule->rule_logic_json, $laboratoryRequest, $needed)) {
                            $isBlocking = in_array($rule->action_type, ['STOP_AUTO_VERIFY', 'REFLEX_TEST']);
                            
                            // Check if this specific rule has had reflex created
                            $hasReflexCreated = in_array($rule->id, $reflexCreatedRuleIds);
                            
                            // For reflex test actions, consider it "acknowledged" if reflex was created
                            $isRuleAcknowledged = $isCdsAcknowledged || $hasReflexCreated;
                            
                            if ($isBlocking && !$isRuleAcknowledged) {
                                $cdsResults['has_blocking_rules'] = true;
                            }

                            $cdsResults['triggered_rules'][] = [
                                'id' => $rule->id,
                                'rule_name' => $rule->rule_name,
                                'alert_message' => $rule->alert_message ?? 'CDS rule triggered.',
                                'action_type' => $rule->action_type,
                                'is_blocking' => $isBlocking,
                                'is_acknowledged' => $isRuleAcknowledged,
                                'reflex_created' => $hasReflexCreated,
                                'trigger_test_id' => $rule->trigger_test_id,
                                'reflex_test_id' => $rule->reflex_test_id,
                            ];
                        }
                    }
                }

                // 2. Delta Check & Critical Value info (basic presence check)
                $results[$laboratoryRequest->id] = [
                    'cds' => $cdsResults,
                    'delta' => [
                        'is_acknowledged' => $isDeltaAcknowledged,
                        // Delta flags are usually fetched separately by CategoryResults.vue via loadDeltaFlags, 
                        // but we provide acknowledgment status here
                    ],
                    'critical' => [
                        'is_acknowledged' => $isCriticalAcknowledged
                    ]
                ];
            }

            return response()->json([
                'results' => $results
            ]);

        } catch (\Throwable $e) {
            Log::error('Bulk Rules Check error: '.$e->getMessage());
            return response()->json([
                'error' => 'Error performing bulk rules check',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk check CDS rules for multiple laboratory requests
     * (Deprecated: Use bulkCheckRules instead)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkCheckCdsRules(Request $request)
    {
        return $this->bulkCheckRules($request);
    }

    /**
     * Get dashboard statistics for result management
     * Returns count per status per test category
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $categories = TestCategory::withCount('tests')->get(['id', 'name']);
            $stats = [];
            
            Log::info('Dashboard stats - Found categories: ' . $categories->count());
            
            foreach ($categories as $category) {
                // Get all requests with tests from this category, including the tests relationship
                $requests = LaboratoryRequest::whereHas('tests', function ($q) use ($category) {
                    $q->where('test_category_id', $category->id);
                })->with(['tests' => function ($testQuery) use ($category) {
                    $testQuery->where('test_category_id', $category->id)
                              ->withPivot('item_status');
                }])->get();
                
                // Count by status
                $stats[$category->id] = [
                    'total' => 0,
                    'pending' => 0,
                    'processing' => 0,
                    'validated' => 0,
                    'released' => 0
                ];
                
                Log::info("Category {$category->name} (ID: {$category->id}) - Found {$requests->count()} requests");
                
                foreach ($requests as $request) {
                    $categoryTests = $request->tests->where('test_category_id', $category->id);
                    
                    if ($categoryTests->count() > 0) {
                        $stats[$category->id]['total']++;
                        
                        // Determine the overall status for this request in this category
                        $hasValidated = $categoryTests->contains(function ($test) {
                            return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
                        });
                        $hasPrinted = $categoryTests->contains(function ($test) {
                            return $test->pivot->item_status === 'Result Printed';
                        });
                        $hasAccepted = $categoryTests->contains(function ($test) {
                            return in_array($test->pivot->item_status, ['Accepted', 'Collected', 'Send-out', 'Parked', 'Pending']);
                        });
                        $hasResults = $categoryTests->contains(function ($test) {
                            return $test->pivot->item_status === 'Processing Results';
                        });
                        $hasCheckedIn = $categoryTests->contains(function ($test) {
                            return $test->pivot->item_status === 'Checked-in';
                        });
                        
                        // Count based on the overall request status for this category
                        if($hasPrinted) {
                            $stats[$category->id]['released']++;
                        } elseif ($hasValidated) {
                            $stats[$category->id]['validated']++;
                        } elseif ($hasAccepted) {
                            $stats[$category->id]['pending']++;
                        } elseif ($hasCheckedIn) {
                            $stats[$category->id]['processing']++;
                        } elseif ($hasResults) {
                            $stats[$category->id]['processing']++;
                        }
                    }
                }
            }
            
            Log::info('Dashboard stats result: ' . json_encode($stats));
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error("Error fetching dashboard stats: " . $e->getMessage());
            return response()->json(['message' => 'Error fetching dashboard stats'], 500);
        }
    }
    
    /**
     * Get turnaround time data per test category
     */
    public function getTatData(Request $request)
    {
        try {
            $categories = TestCategory::all(['id', 'name']);
            $tatData = [];
            
            foreach ($categories as $category) {
                $requests = LaboratoryRequest::whereHas('tests', function ($q) use ($category) {
                    $q->where('test_category_id', $category->id);
                })
                ->whereNotNull('updated_at') // Use updated_at instead of completed_at
                ->get();
                
                if ($requests->count() === 0) {
                    $tatData[] = [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'avg_tat' => 0
                    ];
                    continue;
                }
                
                $totalMinutes = 0;
                foreach ($requests as $request) {
                    if ($request->request_date && $request->updated_at) {
                        $minutes = \Carbon\Carbon::parse($request->updated_at)
                            ->diffInMinutes(\Carbon\Carbon::parse($request->request_date));
                        $totalMinutes += $minutes;
                    }
                }
                
                $avgTat = $totalMinutes > 0 ? ($totalMinutes / $requests->count()) / 60 : 0; // Convert to hours
                
                $tatData[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'avg_tat' => round($avgTat, 2)
                ];
            }
            
            return response()->json($tatData);
        } catch (\Exception $e) {
            Log::error("Error fetching TAT data: " . $e->getMessage());
            return response()->json(['message' => 'Error fetching TAT data'], 500);
        }
    }
    
    /**
     * Get requests by category (generic endpoint for any category)
     */
    public function getCategoryRequests(Request $request, $categoryId)
    {
        try {
            $category = TestCategory::findOrFail($categoryId);
            $categoryName = $category->name;
            
            $query = LaboratoryRequest::query();
            
            $query->whereHas('tests', function ($testQuery) use ($categoryId) {
                $testQuery->where('test_category_id', $categoryId)
                          ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run', 'Rejected']);
            });
            
            $query->with([
                'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email',
                'patientType:id,name',
                'ward:id,name',
                'room:id,name',
                'doctor:id,name',
                'tests' => function ($testQuery) use ($categoryId) {
                    $testQuery->where('test_category_id', $categoryId)
                              ->select('tests.id', 'tests.name')
                              ->withPivot('item_status')
                              ->with('testParameters:id,test_id');
                },
                // Load reflex children to indicate if this request has spawned reflex tests
                'reflexRequests:id,is_reflex,reflex_parent_request_id,reflex_triggered_by_rule_id,request_date'
            ]);
            
            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('id', 'like', "%{$searchTerm}%")
                      ->orWhere('sample_id', 'like', "%{$searchTerm}%")
                      ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
                          $patientQuery->where('last_name', 'like', "%{$searchTerm}%")
                                       ->orWhere('first_name', 'like', "%{$searchTerm}%");
                      });
                });
            }
            
            $query->orderBy('priority_level', 'desc')
                  ->orderBy('request_date', 'asc');
            
            $perPage = $request->input('per_page', 15);
            $requests = $query->paginate($perPage);
            
            $requests->getCollection()->transform(function ($request) use ($categoryId, $categoryName) {
                // Create normalized field names (lowercase with underscores)
                $normalizedCategoryName = strtolower(str_replace(' ', '_', $categoryName));
                $statusFieldName = $normalizedCategoryName . '_status';
                $testListFieldName = $normalizedCategoryName . '_test_list';
                
                $request->{$testListFieldName} = $request->tests->pluck('name')->implode(', ');
                
                $hasValidated = $request->tests->contains(function ($test) {
                    return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
                });
                $hasPrinted = $request->tests->contains(function ($test) {
                    return $test->pivot->item_status === 'Result Printed';
                });
                $hasAccepted = $request->tests->contains(function ($test) {
                    return in_array($test->pivot->item_status, ['Accepted', 'Collected', 'Send-out', 'Parked']);
                });
                $hasResults = $request->tests->contains(function ($test) {
                    return $test->pivot->item_status === 'Processing Results';
                });
                $hasCheckedIn = $request->tests->contains(function ($test) {
                    return $test->pivot->item_status === 'Checked-in';
                });
                $hasForRerun = $request->tests->contains(function ($test) {
                    return $test->pivot->item_status === 'For Re-run';
                });
                $hasRejected = $request->tests->contains(function ($test) {
                    return $test->pivot->item_status === 'Rejected';
                });
                $allRejected = $request->tests->every(function ($test) {
                    return $test->pivot->item_status === 'Rejected';
                });

                if ($allRejected) {
                    $request->{$statusFieldName} = 'Rejected';
                } elseif ($hasRejected) {
                    $request->{$statusFieldName} = 'Partially Rejected';
                } elseif ($hasPrinted) {
                    $request->{$statusFieldName} = 'Released';
                } elseif ($hasValidated) {
                    $request->{$statusFieldName} = 'Validated';
                } elseif ($hasAccepted) {
                    $request->{$statusFieldName} = 'Pending Sample';
                } elseif ($hasCheckedIn) {
                    $request->{$statusFieldName} = 'Checked-in';
                } elseif ($hasResults) {
                    $request->{$statusFieldName} = 'Processing Results';
                } elseif ($hasForRerun) {
                    $request->hematology_status = 'For Re-run';
                } else {
                    $request->{$statusFieldName} = 'Unknown';
                }
                
                foreach ($request->tests as $test) {
                    $test->parameter_count = TestParameter::where('test_id', $test->id)->count();
                    $test->completed_parameter_count = TestResult::where([
                        'laboratory_request_id' => $request->id,
                        'test_id' => $test->id,
                    ])->whereNotNull('test_parameter_id')->count();
                    
                    if ($test->parameter_count === 0) {
                        $test->parameter_count = 1;
                        $test->completed_parameter_count = TestResult::where([
                            'laboratory_request_id' => $request->id,
                            'test_id' => $test->id,
                            'test_parameter_id' => null,
                        ])->count();
                    }
                }

                // Attach distinct analyzer ids and names used in this request (if any)
                try {
                    $equipmentIds = TestResult::where('laboratory_request_id', $request->id)
                        ->whereNotNull('equipment_id')
                        ->distinct()
                        ->pluck('equipment_id')
                        ->toArray();
                    $request->equipment_ids = $equipmentIds;
                    if (!empty($equipmentIds)) {
                        $names = DB::table('equipment')->whereIn('id', $equipmentIds)
                            ->get()
                            ->mapWithKeys(function($eq) {
                                return [$eq->id => $eq->manufacturer . ' - ' . $eq->model];
                            });
                        $request->equipment_names = $names;
                    } else {
                        $request->equipment_names = [];
                    }
                } catch (\Throwable $e) {
                    $request->equipment_ids = [];
                    $request->equipment_names = [];
                }
                
                // Add indicator if this request has spawned reflex tests
                $request->has_reflex_children = $request->reflexRequests && $request->reflexRequests->isNotEmpty();
                $request->reflex_children_count = $request->reflexRequests ? $request->reflexRequests->count() : 0;
                
                return $request;
            });
            
            return response()->json($requests);
            
        } catch (\Exception $e) {
            Log::error("Error fetching category requests: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching category requests.'], 500);
        }
    }

    /**
     * Generic method to fetch detailed information for a single request by category ID.
     * This works for any category, not just hematology or chemistry.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $categoryId
     * @param  \App\Models\LaboratoryRequest  $laboratoryRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryRequestDetails(Request $request, $categoryId, LaboratoryRequest $laboratoryRequest)
    {
        try {
            // Find the category by ID
            $category = TestCategory::findOrFail($categoryId);
            $categoryId = $category->id; // Use the category ID from the found category
            
            \Log::info('⚡️ Starting getCategoryRequestDetails for laboratory request ID: ' . $laboratoryRequest->id . ' and category ID: ' . $categoryId);

            // 1. Load the core request data and RELATIONS, including filtered tests
            $laboratoryRequest->load([
                'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email',
                'patientType:id,name',
                'ward:id,name',
                'room:id,name',
                'doctor:id,name',
                'creator:id,name',
                // Load ONLY tests from this category with basic pivot data
                'tests' => function ($testQuery) use ($categoryId) {
                    $testQuery->where('test_category_id', $categoryId)
                              ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'For Re-run', 'Result Validated', 'Result Printed', 'Rejected'])
                              ->withPivot('id', 'remarks', 'comment', 'perform_by', 'perform_at', 'verify_by', 'verify_at', 'pathologist_id');
                },
                // Load test results for this laboratory request filtered by category
                'results' => function($query) use ($categoryId) {
                    $query->whereHas('test', function($q) use ($categoryId) {
                        $q->where('test_category_id', $categoryId);
                    })
                    ->with(['test', 'testParameter', 'equipment']);
                }
            ]);

            // Process the loaded test results and associate them with their respective tests
            $tests = $laboratoryRequest->tests;
            
            // Create a map to store test parameters and their results by test_id
            $testResultsMap = [];
            
            // Add user information for tracking fields
            $laboratoryRequest->tests->each(function($test) {
                // Load performer user
                if ($test->pivot->perform_by) {
                    $test->performer_id = $test->pivot->perform_by;
                    $test->perform_at = $test->pivot->perform_at;
                    
                    try {
                        $performer = User::find($test->pivot->perform_by, ['id', 'name', 'email']);
                        if ($performer) {
                            $test->performer = $performer;
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error loading performer user: " . $e->getMessage());
                    }
                }
                
                // Load verifier user
                if ($test->pivot->verify_by) {
                    $test->verifier_id = $test->pivot->verify_by;
                    $test->verify_at = $test->pivot->verify_at;
                    
                    try {
                        $verifier = User::find($test->pivot->verify_by, ['id', 'name', 'email']);
                        if ($verifier) {
                            $test->verifier = $verifier;
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error loading verifier user: " . $e->getMessage());
                    }
                }
                
                // Load pathologist user
                if ($test->pivot->pathologist_id) {
                    $test->pathologist_id = $test->pivot->pathologist_id;
                    
                    try {
                        $pathologist = User::find($test->pivot->pathologist_id, ['id', 'name', 'email']);
                        if ($pathologist) {
                            $test->pathologist = $pathologist;
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error loading pathologist user: " . $e->getMessage());
                    }
                }
            });
            
            // Process each test result and group by test_id
            foreach ($laboratoryRequest->results as $result) {
                $testId = $result->test_id;
                
                if (!isset($testResultsMap[$testId])) {
                    $testResultsMap[$testId] = [
                        'parameters' => [],
                        'performer_id' => $result->performer_id ?? null,
                        'verifier_id' => $result->verifier_id ?? null,
                        'pathologist_id' => $result->pathologist_id ?? null,
                        'status' => $result->status ?? null
                    ];
                }
                
                // Add this result to the parameters array if it has a parameter
                if ($result->testParameter) {
                    $testResultsMap[$testId]['parameters'][] = [
                        'id' => $result->testParameter->id,
                        'name' => $result->testParameter->name,
                        'test_name' => $result->testParameter->name,
                        'resultValue' => $result->result_value,
                        'unit' => $result->unit,
                        'instrument' => $result->equipment_id,
                        'equipment_name' => $result->equipment ? ($result->equipment->manufacturer . ' - ' . $result->equipment->model) : null,
                        'resultComment' => $result->result_comment,
                        'flag' => $result->flag,
                        'result_id' => $result->id,
                        'version' => $result->version,
                        'status' => $result->status,
                    ];
                } else {
                    // If it's a non-parameterized test, set the result directly on the test
                    $test = $tests->firstWhere('id', $testId);
                    if ($test) {
                        $test->initial_resultValue = $result->result_value;
                        $test->initial_instrument = $result->equipment_id;
                        $test->initial_resultComment = $result->result_comment;
                        $test->result_id = $result->id;
                        $test->version = $result->version;
                        $test->status = $result->status;
                    }
                }
            }
            
            // Remove result_comment if it exists at laboratory_request level
            if (property_exists($laboratoryRequest, 'result_comment')) {
                unset($laboratoryRequest->result_comment);
            }

            // Now associate the parameters with each test
            foreach ($tests as $test) {
                if (isset($testResultsMap[$test->id])) {
                    $testParameters = [];
                    $testParameters[$test->id] = $testResultsMap[$test->id]['parameters'];
                    $laboratoryRequest->testParameters = $testParameters;
                    
                    // Set the signatories
                    $laboratoryRequest->performer_id = $testResultsMap[$test->id]['performer_id'];
                    $laboratoryRequest->verifier_id = $testResultsMap[$test->id]['verifier_id'];
                    $laboratoryRequest->pathologist_id = $testResultsMap[$test->id]['pathologist_id'];
                    
                    // Add test result status
                    $test->result_status = $testResultsMap[$test->id]['status'];
                    
                    // Also add the remarks and comment from the pivot
                    if ($test->pivot && $test->pivot->remarks) {
                        $laboratoryRequest->remarks = $test->pivot->remarks;
                    }
                    
                    if ($test->pivot && $test->pivot->comment) {
                        $laboratoryRequest->comment = $test->pivot->comment;
                    }
                }
            }

            // 2. Fetch User Lists for Dropdowns
            $users = User::select('id', 'name')->orderBy('name')->get();
            $verifierList = $users;
            $performerList = $users;
            $pathologistList = $users;

            // 3. Calculate Age
            if (!$laboratoryRequest->patient_age && $laboratoryRequest->patient) {
                $laboratoryRequest->patient_age = $laboratoryRequest->patient && $laboratoryRequest->patient->date_of_birth
                    ? now()->diffInYears($laboratoryRequest->patient->date_of_birth)
                    : 'N/A';
            }

            // 4. Structure the response
            return response()->json([
                'request_details' => $laboratoryRequest,
                'user_lists' => [
                    'verifiers' => $verifierList,
                    'performers' => $performerList,
                    'pathologists' => $pathologistList,
                ]
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Laboratory request or category not found.'], 404);
        } catch (\Exception $e) {
            \Log::error("⚡️ Error in getCategoryRequestDetails: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'An error occurred while fetching request details.'], 500);
        }
    }

    /**
     * Fetch patient history for the last 6 months.
     *
     * @param  Request  $request
     * @param  int  $patientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPatientHistory(Request $request, $patientId)
    {
        try {
            $sixMonthsAgo = now()->subMonths(6);
            $categoryId = $request->input('category_id');
            
            $query = TestResult::whereHas('laboratoryRequest', function ($query) use ($patientId, $sixMonthsAgo) {
                $query->where('patient_id', $patientId)
                      ->where('request_date', '>=', $sixMonthsAgo);
            });
            
            if ($categoryId) {
                $query->whereHas('test', function ($q) use ($categoryId) {
                    $q->where('test_category_id', $categoryId);
                });
            }
            
            $results = $query->with(['test:id,name', 'testParameter:id,test_name,unit', 'laboratoryRequest:id,sample_id,request_date'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($result) {
                return [
                    'id' => $result->id,
                    'request_id' => $result->laboratory_request_id,
                    'request_number' => $result->laboratoryRequest->sample_id ?? $result->laboratory_request_id,
                    'date' => $result->laboratoryRequest->request_date,
                    'test_name' => $result->test->name,
                    'parameter_name' => $result->testParameter->test_name ?? 'N/A',
                    'result' => $result->result_value,
                    'unit' => $result->unit ?? ($result->testParameter->unit ?? ''),
                    'flag' => $result->flag,
                ];
            });

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Error fetching patient history: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch patient history', 'message' => $e->getMessage()], 500);
        }
    }
}
