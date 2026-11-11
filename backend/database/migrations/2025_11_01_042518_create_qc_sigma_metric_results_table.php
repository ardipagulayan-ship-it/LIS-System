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
        Schema::create('qc_sigma_metric_results', function (Blueprint $table) {
            $table->id(); // ResultID (BIGINT PK)
            $table->unsignedBigInteger('test_parameter_id')->comment('The Analyte (test parameter) the Sigma Metric applies to');
            $table->decimal('sigma_metric', 10, 4)->comment('The calculated Σ value');
            $table->dateTime('calculation_date')->comment('Date/Time the metric was calculated');
            $table->decimal('observed_mean', 10, 4)->nullable()->comment('The x̄ (observed mean) used in the calculation');
            $table->decimal('observed_std_dev', 10, 4)->nullable()->comment('The s (observed standard deviation) used in the calculation');
            $table->decimal('target_mean', 10, 4)->nullable()->comment('The μ (target mean) used in the calculation');
            $table->decimal('te_a', 10, 4)->nullable()->comment('The TE_a value used in the calculation');
            $table->integer('period_days')->nullable()->comment('Number of days of QC data used');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('test_parameter_id')->references('id')->on('test_parameters')->onDelete('cascade');
            
            // Indexes for efficient queries
            $table->index('test_parameter_id');
            $table->index('calculation_date');
            $table->index(['test_parameter_id', 'calculation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_sigma_metric_results');
    }
};
