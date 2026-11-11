<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Add columns for tracking who performed the action and when
            // Assuming 'users' table exists and uses standard primary keys (bigIncrements)
            // Use nullable foreign keys in case tracking isn't always required or user is deleted
            // Place these after existing columns like 'item_status' or 'rejection_reason' if desired

            // Collected By/At
            $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('collected_at')->nullable();

            // Accepted By/At
            $table->foreignId('accepted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accepted_at')->nullable();

            // Rejected By/At
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Drop foreign keys first to avoid errors
            $table->dropForeign(['collected_by']);
            $table->dropForeign(['accepted_by']);
            $table->dropForeign(['rejected_by']);

            // Drop the columns
            $table->dropColumn(['collected_by', 'collected_at']);
            $table->dropColumn(['accepted_by', 'accepted_at']);
            $table->dropColumn(['rejected_by', 'rejected_at']);
        });
    }
};
