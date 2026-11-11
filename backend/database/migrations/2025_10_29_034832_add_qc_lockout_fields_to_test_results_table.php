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
        Schema::table('test_results', function (Blueprint $table) {
            $table->boolean('qc_lockout_active')->default(false)->after('status');
            $table->timestamp('qc_lockout_date')->nullable()->after('qc_lockout_active');
            $table->foreignId('qc_corrective_action_id')->nullable()->after('qc_lockout_date')
                  ->constrained('corrective_actions')->nullOnDelete();
            $table->boolean('requires_review')->default(false)->after('qc_corrective_action_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropForeign(['qc_corrective_action_id']);
            $table->dropColumn(['qc_lockout_active', 'qc_lockout_date', 'qc_corrective_action_id', 'requires_review']);
        });
    }
};
