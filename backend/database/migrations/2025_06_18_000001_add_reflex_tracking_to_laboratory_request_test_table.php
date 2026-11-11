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
            // Add reflex tracking columns
            $table->boolean('is_reflex_test')->default(false)->after('item_status');
            $table->timestamp('reflex_triggered_at')->nullable()->after('is_reflex_test');
            $table->unsignedBigInteger('reflex_triggered_by_rule_id')->nullable()->after('reflex_triggered_at');

            // Add foreign key constraint
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
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['reflex_triggered_by_rule_id']);

            // Drop columns
            $table->dropColumn(['is_reflex_test', 'reflex_triggered_at', 'reflex_triggered_by_rule_id']);
        });
    }
};
