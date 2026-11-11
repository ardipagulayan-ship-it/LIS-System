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
        Schema::create('material_test', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->unsignedBigInteger('test_id');
            $table->decimal('quantity_per_test', 10, 2)->default(1);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->foreign('material_id')->references('id')->on('material_masters')->onDelete('cascade');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            
            $table->unique(['material_id', 'test_id']);
            $table->index('material_id');
            $table->index('test_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_test');
    }
};
