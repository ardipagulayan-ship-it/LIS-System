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
        Schema::create('qc_analyte_sigma_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_parameter_id'); // AnalyteID - FK to test_parameters
            $table->decimal('t_e_a', 10, 4)->comment('Total Allowable Error'); // TE_a value
            $table->string('te_a_unit', 10)->comment('Unit of TE_a (e.g., %, mg/dL)');
            $table->dateTime('date_effective')->comment('Start date when this TE_a value applies');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('test_parameter_id')->references('id')->on('test_parameters')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Index for efficient lookups
            $table->index('test_parameter_id');
            $table->index('date_effective');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_analyte_sigma_config');
    }
};
