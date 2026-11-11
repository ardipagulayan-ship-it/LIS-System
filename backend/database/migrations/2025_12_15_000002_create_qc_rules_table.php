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
        Schema::create('qc_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->foreignId('test_parameter_id')->nullable()->constrained('test_parameters')->onDelete('cascade');
            $table->foreignId('equipment_id')->nullable()->constrained('analyzers')->onDelete('cascade');
            $table->foreignId('quality_control_test_parameter_id')->nullable()->constrained('quality_control_test_parameter')->onDelete('cascade');
            $table->enum('westgard_rule_type', ['1-2s', '1-3s', '2-2s', 'R-4s', '4-1s', '10x']);
            $table->boolean('is_enabled')->default(true);
            $table->enum('action_on_violation', ['warning', 'reject', 'flag'])->default('reject');
            $table->enum('severity_level', ['info', 'warning', 'error', 'critical'])->default('error');
            $table->text('alert_message')->nullable();
            $table->decimal('custom_threshold_multiplier', 3, 2)->nullable()->comment('Override default SD multiplier (e.g., 2.5 for 2.5SD instead of 2SD)');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('test_parameter_id');
            $table->index('equipment_id');
            $table->index('quality_control_test_parameter_id');
            $table->index('westgard_rule_type');
            $table->index('is_active');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_rules');
    }
};



