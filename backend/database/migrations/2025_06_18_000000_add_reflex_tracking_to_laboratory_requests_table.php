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
        Schema::table('laboratory_requests', function (Blueprint $table) {
            // Add reflex tracking columns
            $table->boolean('is_reflex')->default(false)->after('priority_level');
            $table->unsignedBigInteger('reflex_parent_request_id')->nullable()->after('is_reflex');
            $table->unsignedBigInteger('reflex_triggered_by_rule_id')->nullable()->after('reflex_parent_request_id');

            // Add foreign key constraints
            $table->foreign('reflex_parent_request_id')
                  ->references('id')
                  ->on('laboratory_requests')
                  ->onDelete('set null');

            $table->foreign('reflex_triggered_by_rule_id')
                  ->references('id')
                  ->on('cds_correlation_rules')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['reflex_parent_request_id']);
            $table->dropForeign(['reflex_triggered_by_rule_id']);

            // Drop columns
            $table->dropColumn(['is_reflex', 'reflex_parent_request_id', 'reflex_triggered_by_rule_id']);
        });
    }
};
