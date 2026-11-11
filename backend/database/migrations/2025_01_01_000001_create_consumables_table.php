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
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('material_id')->unique();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('catalog_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->decimal('current_stock_level', 10, 2)->default(0);
            $table->string('unit_of_measure')->default('unit');
            $table->string('storage_location')->nullable();
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->unsignedBigInteger('equipment_id')->nullable();
            $table->boolean('is_expired')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('equipment_id');
            $table->index('is_expired');
            $table->index('is_locked');
            $table->index('expiration_date');
            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};
