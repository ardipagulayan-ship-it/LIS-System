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
        Schema::create('equipment_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->unsignedBigInteger('test_id')->nullable();
            $table->unsignedBigInteger('test_result_id')->nullable();
            $table->unsignedBigInteger('lot_id');
            $table->unsignedBigInteger('material_id');
            $table->enum('usage_type', ['patient_test', 'qc_run', 'calibration', 'maintenance', 'manual'])->default('manual');
            $table->decimal('quantity_used', 10, 2);
            $table->timestamp('usage_date');
            $table->string('test_run_number')->nullable();
            $table->enum('qc_status', ['passed', 'failed', 'n/a'])->default('n/a');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('used_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
            $table->foreign('assignment_id')->references('id')->on('equipment_reagent_assignments')->onDelete('set null');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('set null');
            $table->foreign('test_result_id')->references('id')->on('test_results')->onDelete('set null');
            $table->foreign('lot_id')->references('id')->on('lot_stock_records')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_masters')->onDelete('cascade');
            $table->foreign('used_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('equipment_id');
            $table->index('lot_id');
            $table->index('material_id');
            $table->index('test_id');
            $table->index('usage_type');
            $table->index('usage_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_usage');
    }
};

