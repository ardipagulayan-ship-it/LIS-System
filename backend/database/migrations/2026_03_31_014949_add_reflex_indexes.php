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
        // Add indexes for performance on laboratory_requests table
        Schema::table('laboratory_requests', function (Blueprint $table) {
            // Single column indexes
            $table->index('is_reflex');
            $table->index('reflex_parent_request_id');
            $table->index('reflex_triggered_by_rule_id');
            
            // Composite unique index to prevent duplicate reflex creation
            // This ensures one reflex per (parent_request, rule) combination
            $table->unique(['reflex_parent_request_id', 'reflex_triggered_by_rule_id'], 'unique_reflex_per_parent_rule');
        });

        // Add indexes for performance on laboratory_request_test table
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Single column indexes
            $table->index('is_reflex_test');
            $table->index('reflex_triggered_by_rule_id');
            
            // Composite index for joining and filtering
            $table->index(['laboratory_request_id', 'test_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from laboratory_requests
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->dropUnique('unique_reflex_per_parent_rule');
            $table->dropIndex(['is_reflex']);
            $table->dropIndex(['reflex_parent_request_id']);
            $table->dropIndex(['reflex_triggered_by_rule_id']);
        });

        // Drop indexes from laboratory_request_test
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            $table->dropIndex(['is_reflex_test']);
            $table->dropIndex(['reflex_triggered_by_rule_id']);
            $table->dropIndex(['laboratory_request_id', 'test_id']);
        });
    }
};
