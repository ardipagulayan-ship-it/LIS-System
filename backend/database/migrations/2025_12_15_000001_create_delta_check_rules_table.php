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
        Schema::create('delta_check_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->foreignId('test_parameter_id')->constrained('test_parameters')->onDelete('cascade');
            $table->foreignId('equipment_id')->nullable()->constrained('analyzers')->onDelete('set null');
            $table->decimal('percentage_limit', 5, 2)->nullable();
            $table->decimal('absolute_limit', 8, 2)->nullable();
            $table->decimal('critical_threshold', 5, 2)->nullable();
            $table->integer('time_window_days')->default(30)->comment('How far back to look for previous result in days');
            $table->enum('comparison_type', ['percentage', 'absolute', 'both'])->default('both');
            $table->enum('action_on_violation', ['flag', 'flag_and_stop', 'alert_only'])->default('flag');
            $table->text('alert_message')->nullable();
            $table->foreignId('severity_level_id')->nullable()->constrained('delta_check_severity_levels')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('test_parameter_id');
            $table->index('equipment_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delta_check_rules');
    }
};



