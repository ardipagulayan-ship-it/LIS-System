<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add comment field to laboratory_request_test
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            $table->text('comment')->nullable();
        });

        // If the laboratory_requests has a comment column, we need to handle migration of data
        if (Schema::hasColumn('laboratory_requests', 'comment')) {
            // Get all lab requests with non-null comments
            $labRequests = DB::table('laboratory_requests')
                ->whereNotNull('comment')
                ->get(['id', 'comment']);

            // For each lab request with a comment, copy it to all its test items
            foreach ($labRequests as $request) {
                DB::table('laboratory_request_test')
                    ->where('laboratory_request_id', $request->id)
                    ->update(['comment' => $request->comment]);
            }

            // Remove the comment field from laboratory_requests
            Schema::table('laboratory_requests', function (Blueprint $table) {
                $table->dropColumn('comment');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Add comment field back to laboratory_requests
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->text('comment')->nullable();
        });

        // If the laboratory_request_test has a comment column, migrate data back
        if (Schema::hasColumn('laboratory_request_test', 'comment')) {
            // For each lab request, take the comment from its first test
            $labRequestTests = DB::table('laboratory_request_test')
                ->whereNotNull('comment')
                ->orderBy('laboratory_request_id')
                ->get(['laboratory_request_id', 'comment']);

            // Group by laboratory_request_id and take the first comment
            $commentsByRequest = [];
            foreach ($labRequestTests as $test) {
                if (!isset($commentsByRequest[$test->laboratory_request_id])) {
                    $commentsByRequest[$test->laboratory_request_id] = $test->comment;
                }
            }

            // Update the laboratory_requests table
            foreach ($commentsByRequest as $requestId => $comment) {
                DB::table('laboratory_requests')
                    ->where('id', $requestId)
                    ->update(['comment' => $comment]);
            }
            
            // Remove the comment field from laboratory_request_test
            Schema::table('laboratory_request_test', function (Blueprint $table) {
                $table->dropColumn('comment');
            });
        }
    }
}; 