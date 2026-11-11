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
        Schema::create('corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_control_result_id')->constrained('quality_control_results')->cascadeOnDelete();
            $table->foreignId('quality_control_test_parameter_id')->constrained('quality_control_test_parameter')->cascadeOnDelete();
            $table->string('rule_violated'); // e.g., "1-3s", "2-2s", "R-4s", "4-1s", "10x"
            $table->string('suspected_error'); // e.g., "Random", "Systematic", "Trend/Shift"
            $table->text('investigation'); // Free-text description of actions taken
            $table->text('resolution')->nullable(); // How the issue was resolved
            $table->foreignId('investigated_by')->nullable()->constrained('users')->nullOnDelete(); // User who performed investigation
            $table->timestamp('investigation_date'); // When investigation was completed
            $table->boolean('issue_resolved')->default(false); // Whether corrective action was successful
            $table->timestamp('resolution_date')->nullable(); // When the issue was resolved
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corrective_actions');
    }
};
