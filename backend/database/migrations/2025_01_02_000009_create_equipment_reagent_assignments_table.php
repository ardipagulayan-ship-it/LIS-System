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
        Schema::create('equipment_reagent_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('lot_id');
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('test_id')->nullable();
            $table->decimal('quantity_per_test', 10, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
            $table->foreign('lot_id')->references('id')->on('lot_stock_records')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material_masters')->onDelete('cascade');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('set null');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('equipment_id');
            $table->index('lot_id');
            $table->index('material_id');
            $table->index('test_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_reagent_assignments');
    }
};
