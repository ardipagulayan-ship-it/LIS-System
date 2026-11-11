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
        Schema::create('lot_usage_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lot_id');
            $table->decimal('quantity_used', 10, 2);
            $table->enum('usage_type', ['automatic', 'manual', 'wastage', 'disposal', 'transfer'])->default('manual');
            $table->unsignedBigInteger('test_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('used_by')->nullable();
            $table->timestamp('usage_date');
            $table->timestamps();

            $table->foreign('lot_id')->references('id')->on('lot_stock_records')->onDelete('cascade');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('set null');
            $table->foreign('used_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('lot_id');
            $table->index('usage_type');
            $table->index('usage_date');
            $table->index('test_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_usage_history');
    }
};
