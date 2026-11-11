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
        // Table for storing test parameters and their delta check limits
        Schema::create('delta_check_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_name')->unique(); // e.g., 'hemoglobin', 'glucose'
            $table->string('display_name'); // e.g., 'Hemoglobin', 'Blood Glucose'
            $table->string('category'); // e.g., 'hematology', 'chemistry', 'coagulation'
            $table->string('unit')->nullable(); // e.g., 'g/dL', 'mg/dL'
            $table->string('specimen_type')->nullable(); // e.g., 'Whole Blood', 'Serum'
            $table->decimal('percentage_limit', 5, 2)->nullable();
            $table->decimal('absolute_limit', 8, 2)->nullable();
            $table->decimal('critical_threshold', 5, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('standards_references')->nullable(); // Store multiple standards
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for storing severity levels and their configurations
        Schema::create('delta_check_severity_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 'critical', 'high', 'moderate', 'low'
            $table->string('display_name'); // 'Critical', 'High Priority', etc.
            $table->string('color')->default('#f56c6c'); // Color for UI
            $table->integer('priority_order')->default(1); // For sorting
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for storing action recommendations
        Schema::create('delta_check_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('severity_level_id')->constrained('delta_check_severity_levels')->onDelete('cascade');
            $table->string('action_type'); // 'immediate', 'short_term', 'long_term'
            $table->string('title'); // Brief title
            $table->text('description'); // Detailed description
            $table->json('steps')->nullable(); // Array of specific steps
            $table->integer('order')->default(1); // For ordering within type
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for storing clinical significance information
        Schema::create('delta_check_clinical_significance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained('delta_check_parameters')->onDelete('cascade');
            $table->text('significance_text'); // Clinical significance description
            $table->json('risk_factors')->nullable(); // Array of risk factors
            $table->json('clinical_conditions')->nullable(); // Related conditions
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for storing preventive measures
        Schema::create('delta_check_preventive_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained('delta_check_parameters')->onDelete('cascade');
            $table->string('measure_type'); // 'pre_analytical', 'analytical', 'post_analytical', 'system_improvements'
            $table->string('title');
            $table->text('description');
            $table->json('specific_measures')->nullable(); // Array of specific measures
            $table->integer('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for storing quality control recommendations
        Schema::create('delta_check_quality_control', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained('delta_check_parameters')->onDelete('cascade');
            $table->string('qc_type'); // 'instrument_check', 'qc_review', 'method_verification', etc.
            $table->string('title');
            $table->text('description');
            $table->json('qc_steps')->nullable(); // Array of QC steps
            $table->integer('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for storing global recommendations based on statistics
        Schema::create('delta_check_global_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('recommendation_type'); // 'overall_violation_rate', 'parameter_specific', 'trend_analysis'
            $table->string('condition'); // e.g., 'violation_rate > 20'
            $table->string('severity'); // 'critical', 'warning', 'info'
            $table->string('title');
            $table->text('message');
            $table->text('action');
            $table->json('thresholds')->nullable(); // Store threshold values
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for storing recommendation templates
        Schema::create('delta_check_recommendation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name')->unique();
            $table->string('template_type'); // 'parameter_specific', 'generic', 'category_specific'
            $table->text('description');
            $table->json('template_data'); // Store template structure
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for logging recommendation usage and effectiveness
        Schema::create('delta_check_recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_result_id')->nullable()->constrained('test_results')->onDelete('set null');
            $table->foreignId('parameter_id')->nullable()->constrained('delta_check_parameters')->onDelete('set null');
            $table->string('violation_type'); // 'percentage', 'absolute', 'critical'
            $table->decimal('percentage_difference', 8, 2)->nullable();
            $table->decimal('absolute_difference', 8, 2)->nullable();
            $table->string('severity_level');
            $table->json('recommendations_given')->nullable(); // Store recommendations provided
            $table->json('actions_taken')->nullable(); // Store actions taken by staff
            $table->string('outcome')->nullable(); // 'resolved', 'ongoing', 'escalated'
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delta_check_recommendation_logs');
        Schema::dropIfExists('delta_check_recommendation_templates');
        Schema::dropIfExists('delta_check_global_recommendations');
        Schema::dropIfExists('delta_check_quality_control');
        Schema::dropIfExists('delta_check_preventive_measures');
        Schema::dropIfExists('delta_check_clinical_significance');
        Schema::dropIfExists('delta_check_actions');
        Schema::dropIfExists('delta_check_severity_levels');
        Schema::dropIfExists('delta_check_parameters');
    }
};