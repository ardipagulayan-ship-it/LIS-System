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
        Schema::create('material_masters', function (Blueprint $table) {
            $table->id();
            $table->string('catalog_id')->unique();
            $table->string('generic_name');
            $table->string('manufacturer');
            $table->string('catalog_number');
            $table->text('description')->nullable();
            $table->string('standard_unit_of_measure')->default('unit');
            $table->integer('shelf_life_days')->nullable();
            $table->string('safety_data_sheet_url')->nullable();
            $table->string('safety_data_sheet_file')->nullable();
            $table->enum('material_type', ['reagent', 'control', 'calibrator', 'consumable', 'equipment'])->default('consumable');
            $table->text('storage_requirements')->nullable();
            $table->string('hazard_classification')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('catalog_id');
            $table->index('material_type');
            $table->index('is_active');
            $table->index('manufacturer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_masters');
    }
};
