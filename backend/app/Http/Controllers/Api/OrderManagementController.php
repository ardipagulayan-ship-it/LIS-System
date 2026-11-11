<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaboratoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Needed for pivot table updates and transactions
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\LaboratoryRequestTest; // Pivot model
use App\Models\Test; // Import Test model
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Jobs\DispatchUlmOrder; // Import the job class for ULM dispatch

class OrderManagementController extends Controller
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
            $testQuery->whereIn('laboratory_request_test.item_status', ['Accepted', 'Send-out', 'Parked']);
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
             $testQuery->whereIn('laboratory_request_test.item_status', ['Accepted', 'Send-out', 'Parked'])
                       ->with('testCategory:id,name') // Load category for each test
                       ->withPivot(
                           'id', 
                           'item_status', 
                           'price_at_request', 
                           'rejection_reason', 
                           'checkin_by', 
                           'checkin_at',
                           'sendout_by', 
                           'sendout_at',
                           'park_by', 
                           'park_at',
                           'pathologist_id'
                       ); // Load relevant pivot data with new tracking fields
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
     * Mark a specific test item as Send-out.
     *
     * @param  int  $itemId The ID from the laboratory_request_test pivot table.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsCollected(Request $request, $itemId)
    {
        try {
            $item = LaboratoryRequestTest::where('id', $itemId)
                                         ->where('item_status', 'Accepted')
                                         ->firstOrFail();

            $item->item_status = 'Send-out';
            $item->sendout_by = auth()->id();
            $item->sendout_at = now();
            $item->save();

            // Fetch the related Test model and attach the updated pivot data
            $test = $item->test()->with('testCategory')->first();
            if ($test) {
                $test->setRelation('pivot', $item);
                 return response()->json(['message' => 'Item successfully marked as Send-out.', 'item' => $test]);
            } else {
                 // Should not happen if constraints are set, but handle defensively
                 Log::error("Test not found for pivot item ID: {$itemId}");
                 return response()->json(['message' => 'Marked as Send-out, but could not retrieve full test data.'], 200); // Return success but maybe partial data
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Test item not found or not in Accepted state.'], 404);
        } catch (\Exception $e) {
            Log::error("Error marking item as Send-out {$itemId}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while marking item as Send-out.'], 500);
        }
    }

    /**
     * Mark a specific test item within a request as Checked-in.
     *
     * @param  int  $itemId The ID from the laboratory_request_test pivot table.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsAccepted(Request $request, $itemId)
    {
        try {
            $item = LaboratoryRequestTest::findOrFail($itemId);

            if (!in_array($item->item_status, ['Accepted', 'Send-out', 'Parked'])) {
                return response()->json(['message' => 'Item cannot be checked in from its current status.'], 400);
            }

            $item->item_status = 'Checked-in';
            $item->checkin_by = auth()->id();
            $item->checkin_at = now();
            $item->save();

            // Dispatch ULM order for the parent laboratory request (non-throwing).
            $ulmStatus = $this->tryDispatchUlm($item->laboratory_request_id);

            // Fetch the related Test model and attach the updated pivot data
            $test = $item->test()->with('testCategory')->first();
            if ($test) {
                $test->setRelation('pivot', $item);
                 return response()->json(['message' => 'Item checked in successfully.', 'item' => $test, 'ulm_status' => $ulmStatus]);
            } else {
                 Log::error("Test not found for pivot item ID: {$itemId}");
                 return response()->json(['message' => 'Checked in, but could not retrieve full test data.', 'ulm_status' => $ulmStatus], 200);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Test item not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Error checking in item {$itemId}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while checking in the item.'], 500);
        }
    }


    /**
     * Mark a specific test item within a request as Parked.
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

            if ($item->item_status !== 'Accepted') {
                return response()->json(['message' => 'Only items in Accepted status can be parked.'], 400);
            }

            $item->item_status = 'Parked';
            $item->rejection_reason = $validated['reason'];
            $item->park_by = auth()->id();
            $item->park_at = now();
            $item->save();

            // Fetch the related Test model and attach the updated pivot data
            $test = $item->test()->with('testCategory')->first();
            if ($test) {
                $test->setRelation('pivot', $item);
                 return response()->json(['message' => 'Item parked successfully.', 'item' => $test]);
            } else {
                Log::error("Test not found for pivot item ID: {$itemId}");
                 return response()->json(['message' => 'Parked, but could not retrieve full test data.'], 200);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Test item not found.'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error("Error parking item {$itemId}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while parking the item.'], 500);
        }
    }

    // --- BATCH ACTIONS --- 

    /**
     * Mark multiple test items as Send-out in a batch operation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markBatchAsCollected(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:laboratory_request_test,id',
        ]);

        $results = [
            'success' => [],
            'failed' => []
        ];

        // Get the user who's performing this action
        $userId = auth()->id();
        $now = now();

        // Use a transaction to ensure we can roll back if anything fails
        DB::beginTransaction();
        try {
            foreach ($validated['item_ids'] as $itemId) {
                try {
                    $item = LaboratoryRequestTest::where('id', $itemId)
                                                 ->where('item_status', 'Accepted')
                                                 ->first();

                    if (!$item) {
                        $results['failed'][] = ['id' => $itemId, 'reason' => 'Item not found or not in Accepted state'];
                        continue;
                    }

                    $item->item_status = 'Send-out';
                    $item->sendout_by = $userId;
                    $item->sendout_at = $now;
                    $item->save();

                    // Load test data for the response
                    $test = $item->test()->with('testCategory')->first();
                    $test->setRelation('pivot', $item);

                    $results['success'][] = $test;

                } catch (\Exception $e) {
                    Log::error("Error sending out item {$itemId}: " . $e->getMessage());
                    $results['failed'][] = ['id' => $itemId, 'reason' => 'Processing error'];
                }
            }

            DB::commit();
            return response()->json([
                'message' => count($results['success']) . ' items marked as Send-out successfully.',
                'updatedItems' => $results['success'],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Batch send-out operation failed: " . $e->getMessage());
            return response()->json(['message' => 'Batch operation failed.'], 500);
        }
    }

    /**
     * Mark multiple test items as Checked-in in a batch operation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markBatchAsAccepted(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:laboratory_request_test,id',
        ]);

        $results = [
            'success' => [],
            'failed' => []
        ];

        // Track unique laboratory_request_ids we've enqueued ULM jobs for in this batch
        // to avoid dispatching duplicate jobs for sibling pivot rows (R3).
        $dispatchedIds = [];

        // Get the user who's performing this action
        $userId = auth()->id();
        $now = now();

        // Use a transaction to ensure we can roll back if anything fails
        DB::beginTransaction();
        try {
            foreach ($validated['item_ids'] as $itemId) {
                try {
                    $item = LaboratoryRequestTest::findOrFail($itemId);

                    if (!in_array($item->item_status, ['Accepted', 'Send-out', 'Parked'])) {
                        $results['failed'][] = ['id' => $itemId, 'reason' => 'Item cannot be checked in from its current status'];
                        continue;
                    }

                    $item->item_status = 'Checked-in';
                    $item->checkin_by = $userId;
                    $item->checkin_at = $now;
                    $item->save();

                    // Dispatch ULM order for the parent laboratory request — once per unique id (R3).
                    if (!in_array($item->laboratory_request_id, $dispatchedIds, true)) {
                        $ulmStatus = $this->tryDispatchUlm($item->laboratory_request_id);
                        $dispatchedIds[] = $item->laboratory_request_id;
                    } else {
                        $ulmStatus = 'skipped';
                    }

                    // Load test data for the response
                    $test = $item->test()->with('testCategory')->first();
                    $test->setRelation('pivot', $item);
                    
                    $results['success'][] = [
                        'item' => $test,
                        'ulm_status' => $ulmStatus,
                    ];

                } catch (ModelNotFoundException $e) {
                    $results['failed'][] = ['id' => $itemId, 'reason' => 'Item not found'];
                } catch (\Exception $e) {
                    Log::error("Error checking in item {$itemId}: " . $e->getMessage());
                    $results['failed'][] = ['id' => $itemId, 'reason' => 'Processing error'];
                }
            }

            DB::commit();
            return response()->json([
                'message' => count($results['success']) . ' items checked in successfully.',
                'ulm_status' => 'queued',
                'updatedItems' => array_map(fn ($entry) => $entry['item'], $results['success']),
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Batch check-in operation failed: " . $e->getMessage());
            return response()->json(['message' => 'Batch operation failed.'], 500);
        }
    }

    /**
     * Mark multiple test items as Parked in a batch operation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markBatchAsRejected(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:laboratory_request_test,id',
            'reason' => 'required|string|max:255',
        ]);

        $results = [
            'success' => [],
            'failed' => []
        ];

        // Get the user who's performing this action
        $userId = auth()->id();
        $now = now();

        // Use a transaction to ensure we can roll back if anything fails
        DB::beginTransaction();
        try {
            foreach ($validated['item_ids'] as $itemId) {
                try {
                    $item = LaboratoryRequestTest::findOrFail($itemId);

                    if ($item->item_status !== 'Accepted') {
                        $results['failed'][] = ['id' => $itemId, 'reason' => 'Only items in Accepted status can be parked'];
                        continue;
                    }

                    $item->item_status = 'Parked';
                    $item->rejection_reason = $validated['reason'];
                    $item->park_by = $userId;
                    $item->park_at = $now;
                    $item->save();

                    // Load test data for the response
                    $test = $item->test()->with('testCategory')->first();
                    $test->setRelation('pivot', $item);

                    $results['success'][] = $test;

                } catch (ModelNotFoundException $e) {
                    $results['failed'][] = ['id' => $itemId, 'reason' => 'Item not found'];
                } catch (\Exception $e) {
                    Log::error("Error parking item {$itemId}: " . $e->getMessage());
                    $results['failed'][] = ['id' => $itemId, 'reason' => 'Processing error'];
                }
            }

            DB::commit();
            return response()->json([
                'message' => count($results['success']) . ' items parked successfully.',
                'updatedItems' => $results['success'],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Batch park operation failed: " . $e->getMessage());
            return response()->json(['message' => 'Batch operation failed.'], 500);
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

     /**
     * Dispatch a ULM order for the given laboratory_request_id.
     * Never throws — failures are logged to the ulm channel.
     *
     * DEDUPE: One lab request must produce at most one ULM job per dedup window.
     * Cache::add() is an atomic check-and-set — the first caller wins, subsequent
     * callers within the window are skipped. This protects against the case where
     * multiple sibling pivot items of the same lab request are checked-in via
     * separate single-accept calls (e.g. user clicks Accept on CBC, then on FBS),
     * which would otherwise enqueue one DispatchUlmOrder per call.
     *
     * @param  int  $laboratoryRequestId
     * @return string 'queued'|'skipped'|'failed'
     */
    private function tryDispatchUlm(int $laboratoryRequestId): string
    {
        if (!config('ulm.enabled')) {
            return 'skipped';
        }

        // Atomic dedupe — TTL is long enough to absorb a multi-click check-in
        // session on the same lab request, short enough that a real re-sync
        // (e.g. QC retry from Phlebotomy) can still fire a fresh dispatch.
        $dedupeKey = "ulm-dispatched:{$laboratoryRequestId}";
        if (!Cache::add($dedupeKey, now()->toIso8601String(), 60)) {
            Log::channel('ulm')->info('[ULM] dispatch skipped (dedupe hit)', [
                'laboratory_request_id' => $laboratoryRequestId,
            ]);
            return 'skipped';
        }

        try {
            DispatchUlmOrder::dispatch($laboratoryRequestId);
            return 'queued';
        } catch (\Throwable $e) {
            // Roll back the cache key so a follow-up call can try once more.
            Cache::forget($dedupeKey);
            Log::channel('ulm')->warning(
                '[ULM] dispatch enqueue failed: '.$e->getMessage(),
                ['laboratory_request_id' => $laboratoryRequestId]
            );
            return 'failed';
        }
    }

}
