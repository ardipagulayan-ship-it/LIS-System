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
        Schema::create('material_vendor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_catalog_number')->nullable();
            $table->decimal('last_purchase_price', 10, 2)->nullable();
            $table->date('last_purchase_date')->nullable();
            $table->timestamps();

            $table->foreign('material_id')->references('id')->on('material_masters')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            
            $table->unique(['material_id', 'vendor_id']);
            $table->index('material_id');
            $table->index('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_vendor');
    }
};
