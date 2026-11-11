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
        Schema::create('lot_stock_records', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id')->unique();
            $table->unsignedBigInteger('material_id');
            $table->string('lot_number');
            $table->date('expiration_date');
            $table->date('date_received');
            $table->unsignedBigInteger('vendor_id');
            $table->decimal('initial_quantity', 10, 2);
            $table->decimal('current_stock_level', 10, 2);
            $table->decimal('reorder_point', 10, 2)->default(0);
            $table->decimal('reorder_quantity', 10, 2)->default(0);
            $table->string('storage_location')->nullable();
            $table->enum('status', ['quarantine', 'active', 'depleted', 'on_hold', 'expired', 'disposed'])->default('quarantine');
            $table->text('quarantine_notes')->nullable();
            $table->unsignedBigInteger('quarantine_released_by')->nullable();
            $table->timestamp('quarantine_released_at')->nullable();
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('batch_number')->nullable();
            $table->string('certificate_of_analysis')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('material_id')->references('id')->on('material_masters')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('quarantine_released_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('lot_id');
            $table->index('material_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('expiration_date');
            $table->index('lot_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_stock_records');
    }
};
