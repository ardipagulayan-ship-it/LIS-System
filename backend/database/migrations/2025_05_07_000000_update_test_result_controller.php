<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a migration to transfer performer_id, verifier_id, and pathologist_id from test_results to laboratory_request_test
        if (Schema::hasTable('test_results') && Schema::hasTable('laboratory_request_test')) {
            // Get all test results with performer, verifier or pathologist data
            $results = DB::table('test_results')
                ->whereNotNull('performer_id')
                ->orWhereNotNull('verifier_id')
                ->orWhereNotNull('pathologist_id')
                ->get();
                
            // Group results by laboratory_request_id and test_id
            $groupedResults = [];
            foreach ($results as $result) {
                $key = $result->laboratory_request_id . '-' . $result->test_id;
                if (!isset($groupedResults[$key])) {
                    $groupedResults[$key] = [
                        'laboratory_request_id' => $result->laboratory_request_id,
                        'test_id' => $result->test_id,
                        'performer_id' => $result->performer_id,
                        'verifier_id' => $result->verifier_id,
                        'pathologist_id' => $result->pathologist_id,
                        'created_at' => $result->created_at,
                        'updated_at' => $result->updated_at
                    ];
                }
            }
            
            // Update laboratory_request_test records with transferred data
            foreach ($groupedResults as $data) {
                // Find the pivot record
                $pivotRecord = DB::table('laboratory_request_test')
                    ->where('laboratory_request_id', $data['laboratory_request_id'])
                    ->where('test_id', $data['test_id'])
                    ->first();
                    
                if ($pivotRecord) {
                    $updateData = [];
                    
                    // Only update fields that are NULL in the pivot table but have values in test_results
                    if (is_null($pivotRecord->perform_by) && !is_null($data['performer_id'])) {
                        $updateData['perform_by'] = $data['performer_id'];
                        $updateData['perform_at'] = $data['updated_at'];
                    }
                    
                    if (is_null($pivotRecord->verify_by) && !is_null($data['verifier_id'])) {
                        $updateData['verify_by'] = $data['verifier_id'];
                        $updateData['verify_at'] = $data['updated_at'];
                    }
                    
                    if (is_null($pivotRecord->pathologist_id) && !is_null($data['pathologist_id'])) {
                        $updateData['pathologist_id'] = $data['pathologist_id'];
                    }
                    
                    // Only update if we have data to update
                    if (!empty($updateData)) {
                        DB::table('laboratory_request_test')
                            ->where('id', $pivotRecord->id)
                            ->update($updateData);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse migration needed as we're only copying data
        // The fields will be dropped in the main tracking fields migration if needed
    }
}; 