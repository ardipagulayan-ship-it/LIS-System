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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('unique_asset_id')->unique();
            $table->string('manufacturer');
            $table->string('model');
            $table->string('serial_number');
            $table->date('purchase_date');
            $table->date('warranty_expiration_date')->nullable();
            $table->string('location_lab');
            $table->string('location_bench')->nullable();
            $table->enum('current_status', [
                'in_service',
                'under_maintenance', 
                'retired',
                'calibration_due',
                'out_of_service'
            ])->default('in_service');
            $table->text('sop_operation_manual_link')->nullable();
            $table->integer('cycle_count')->default(0);
            $table->decimal('runtime_hours', 10, 2)->default(0);
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->decimal('total_repair_cost', 10, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['current_status']);
            $table->index(['next_maintenance_date']);
            $table->index(['location_lab']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
