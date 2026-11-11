<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaboratoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Needed for pivot table updates and transactions
use Illuminate\Support\Facades\Log;
use App\Models\LaboratoryRequestTest; // Pivot model
use App\Models\Test; // Import Test model
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class PhlebotomyController extends Controller
{
    /**
     * Fetch requests with items relevant to Phlebotomy.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequests(Request $request)
    {
        $query = LaboratoryRequest::query();

        // Only fetch requests that have *any* phlebotomy-relevant items (modify statuses as needed)
        $query->whereHas('tests', function ($testQuery) {
            // Adjust statuses based on what Phlebotomy module should see initially
            $testQuery->whereIn('laboratory_request_test.item_status', ['Pending', 'Collected']);
        });

        // Eager load patient and necessary request details
        $query->with([
            'patient:id,last_name,first_name,middle_name,date_of_birth,gender',
            'ward:id,name',
            'room:id,name',
            'patientType:id,name',
        ]);

        // Eager load the specific tests relevant to Phlebotomy, along with their pivot data and category
        $query->with(['tests' => function ($testQuery) {
            // Filter tests again if necessary (e.g., only specific statuses for display)
             $testQuery->whereIn('laboratory_request_test.item_status', ['Pending', 'Collected'])
                       ->with('testCategory:id,name') // Load category for each test
                       ->withPivot('id', 'item_status', 'price_at_request', 'rejection_reason', 'collected_at', 'accepted_at', 'rejected_at'); // Load relevant pivot data
        }]);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                  ->orWhere('sample_id', 'like', "%{$searchTerm}%")
                  ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
                      $patientQuery->where('last_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('first_name', 'like', "%{$searchTerm}%")
                                   ->orWhere('middle_name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Add Patient Type Filter
        if ($request->filled('patient_type_id')) {
            $patientTypeId = $request->input('patient_type_id');
            $query->whereHas('patient', function ($patientQuery) use ($patientTypeId) {
                $patientQuery->where('patient_type_id', $patientTypeId);
            });
        }

        $query->orderBy('priority_level', 'desc')
              ->orderBy('request_date', 'asc');

        $perPage = $request->input('per_page', 15);
        $requests = $query->paginate($perPage);

        return response()->json($requests);
    }

    /**
     * Mark a specific test item as Collected.
     *
     * @param  int  $itemId The ID from the laboratory_request_test pivot table.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsCollected(Request $request, $itemId)
    {
        try {
            $item = LaboratoryRequestTest::where('id', $itemId)
                                         ->where('item_status', 'Pending')
                                         ->firstOrFail();

            $item->item_status = 'Collected';
            $item->collected_by = auth()->id();
            $item->collected_at = now();
            $item->save();

            // Fetch the related Test model and attach the updated pivot data
            $test = $item->test()->with('testCategory')->first();
            if ($test) {
                $test->setRelation('pivot', $item);
                 return response()->json(['message' => 'Item collected successfully.', 'item' => $test]);
            } else {
                 // Should not happen if constraints are set, but handle defensively
                 Log::error("Test not found for pivot item ID: {$itemId}");
                 return response()->json(['message' => 'Collected, but could not retrieve full test data.'], 200); // Return success but maybe partial data
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Test item not found or not in Pending state.'], 404);
        } catch (\Exception $e) {
            Log::error("Error collecting phlebotomy item {$itemId}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while collecting the item.'], 500);
        }
    }

    /**
     * Mark a specific test item within a request as Accepted.
     *
     * @param  int  $itemId The ID from the laboratory_request_test pivot table.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsAccepted(Request $request, $itemId)
    {
        try {
            $item = LaboratoryRequestTest::findOrFail($itemId);

            if ($item->item_status !== 'Collected') {
                return response()->json(['message' => 'Only collected items can be accepted.'], 400);
            }

            $item->item_status = 'Accepted';
            $item->accepted_by = auth()->id();
            $item->accepted_at = now();
            $item->save();

            // Fetch the related Test model and attach the updated pivot data
            $test = $item->test()->with('testCategory')->first();
            if ($test) {
                $test->setRelation('pivot', $item);
                 return response()->json(['message' => 'Item accepted successfully.', 'item' => $test]);
            } else {
                 Log::error("Test not found for pivot item ID: {$itemId}");
                 return response()->json(['message' => 'Accepted, but could not retrieve full test data.'], 200);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Test item not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Error accepting phlebotomy item {$itemId}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while accepting the item.'], 500);
        }
    }


    /**
     * Mark a specific test item within a request as Rejected.
     *
     * @param  int  $itemId The ID from the laboratory_request_test pivot table.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRejected(Request $request, $itemId)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $item = LaboratoryRequestTest::findOrFail($itemId);

            if (!in_array($item->item_status, ['Pending', 'Collected'])) {
                return response()->json(['message' => 'Only pending or collected items can be rejected.'], 400);
            }

            $item->item_status = 'Rejected';
            $item->rejection_reason = $validated['reason'];
            $item->rejected_by = auth()->id();
            $item->rejected_at = now();
            $item->save();

            // Fetch the related Test model and attach the updated pivot data
            $test = $item->test()->with('testCategory')->first();
            if ($test) {
                $test->setRelation('pivot', $item);
                 return response()->json(['message' => 'Item rejected successfully.', 'item' => $test]);
            } else {
                Log::error("Test not found for pivot item ID: {$itemId}");
                 return response()->json(['message' => 'Rejected, but could not retrieve full test data.'], 200);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Test item not found.'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Error rejecting phlebotomy item {$itemId}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while rejecting the item.'], 500);
        }
    }

    // --- BATCH ACTIONS --- 

    public function markBatchAsCollected(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:laboratory_request_test,id',
        ]);

        $itemIds = $validated['item_ids'];
        $results = [ 'success' => [], 'failed' => [] ];
        $updatedPivotItems = []; // Store the updated PIVOT models first

        DB::beginTransaction();
        try {
            foreach ($itemIds as $itemId) {
                $item = LaboratoryRequestTest::find($itemId);
                if ($item && $item->item_status === 'Pending') {
                    $item->item_status = 'Collected';
                    $item->collected_by = auth()->id();
                    $item->collected_at = now();
                    $item->save();
                    $results['success'][] = $itemId;
                    $updatedPivotItems[] = $item; // Add the updated pivot model
                } else {
                    $results['failed'][] = $itemId;
                }
            }
            DB::commit();

            // Now, format the response to include Test models with pivot data
            $formattedUpdatedItems = [];
            $testIds = collect($updatedPivotItems)->pluck('test_id')->unique()->all();
            // Eager load tests and categories for efficiency
            $tests = Test::with('testCategory')->whereIn('id', $testIds)->get()->keyBy('id');

            foreach ($updatedPivotItems as $updatedPivot) {
                 if (isset($tests[$updatedPivot->test_id])) {
                     $test = clone $tests[$updatedPivot->test_id]; // Clone to avoid modifying the collection for subsequent items
                     $test->setRelation('pivot', $updatedPivot);
                     $formattedUpdatedItems[] = $test;
                 } else {
                      Log::warning("Could not find Test model for pivot ID {$updatedPivot->id} during batch collect formatting.");
                 }
            }

            return response()->json([
                'message' => 'Batch collect operation completed.',
                'results' => $results,
                'updatedItems' => $formattedUpdatedItems // Send formatted data
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during batch collect: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred during batch collection.'], 500);
        }
    }

    public function markBatchAsAccepted(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:laboratory_request_test,id',
        ]);

        $itemIds = $validated['item_ids'];
        $results = [ 'success' => [], 'failed' => [] ];
        $updatedPivotItems = [];

        DB::beginTransaction();
        try {
            foreach ($itemIds as $itemId) {
                $item = LaboratoryRequestTest::find($itemId);
                if ($item && $item->item_status === 'Collected') {
                    $item->item_status = 'Accepted';
                    $item->accepted_by = auth()->id();
                    $item->accepted_at = now();
                    $item->save();
                    $results['success'][] = $itemId;
                    $updatedPivotItems[] = $item;
                } else {
                    $results['failed'][] = $itemId;
                }
            }
            DB::commit();

             // Format the response
            $formattedUpdatedItems = [];
            $testIds = collect($updatedPivotItems)->pluck('test_id')->unique()->all();
            $tests = Test::with('testCategory')->whereIn('id', $testIds)->get()->keyBy('id');
            foreach ($updatedPivotItems as $updatedPivot) {
                 if (isset($tests[$updatedPivot->test_id])) {
                     $test = clone $tests[$updatedPivot->test_id];
                     $test->setRelation('pivot', $updatedPivot);
                     $formattedUpdatedItems[] = $test;
                 } else {
                     Log::warning("Could not find Test model for pivot ID {$updatedPivot->id} during batch accept formatting.");
                 }
            }

            return response()->json([
                'message' => 'Batch accept operation completed.',
                'results' => $results,
                'updatedItems' => $formattedUpdatedItems
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during batch accept: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred during batch acceptance.'], 500);
        }
    }

    public function markBatchAsRejected(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:laboratory_request_test,id',
            'reason' => 'required|string|max:255',
        ]);

        $itemIds = $validated['item_ids'];
        $reason = $validated['reason'];
        $results = [ 'success' => [], 'failed' => [] ];
        $updatedPivotItems = [];

        DB::beginTransaction();
        try {
            foreach ($itemIds as $itemId) {
                $item = LaboratoryRequestTest::find($itemId);
                if ($item && in_array($item->item_status, ['Pending', 'Collected'])) {
                    $item->item_status = 'Rejected';
                    $item->rejection_reason = $reason;
                    $item->rejected_by = auth()->id();
                    $item->rejected_at = now();
                    $item->save();
                    $results['success'][] = $itemId;
                    $updatedPivotItems[] = $item;
                } else {
                    $results['failed'][] = $itemId;
                }
            }
            DB::commit();

             // Format the response
            $formattedUpdatedItems = [];
            $testIds = collect($updatedPivotItems)->pluck('test_id')->unique()->all();
            $tests = Test::with('testCategory')->whereIn('id', $testIds)->get()->keyBy('id');
            foreach ($updatedPivotItems as $updatedPivot) {
                 if (isset($tests[$updatedPivot->test_id])) {
                     $test = clone $tests[$updatedPivot->test_id];
                     $test->setRelation('pivot', $updatedPivot);
                     $formattedUpdatedItems[] = $test;
                 } else {
                      Log::warning("Could not find Test model for pivot ID {$updatedPivot->id} during batch reject formatting.");
                 }
            }

            return response()->json([
                'message' => 'Batch reject operation completed.',
                'results' => $results,
                'updatedItems' => $formattedUpdatedItems
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during batch reject: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred during batch rejection.'], 500);
        }
    }

    // Optional: Add getPhlebotomyStats method if needed for dashboard/stats view
    public function getPhlebotomyStats(Request $request)
    {
        // Logic to calculate stats relevant to phlebotomy (e.g., pending collections, etc.)
        // Example:
        $pendingCollectionCount = LaboratoryRequestTest::where('item_status', 'Pending')->count();
        $collectedTodayCount = LaboratoryRequestTest::where('item_status', 'Collected')
                                                 ->whereDate('collected_at', today())
                                                 ->count();

        return response()->json([
            'pending_collections' => $pendingCollectionCount,
            'collected_today' => $collectedTodayCount,
            // Add other relevant stats
        ]);
    }

}
