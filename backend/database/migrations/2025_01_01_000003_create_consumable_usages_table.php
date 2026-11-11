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
        Schema::create('consumable_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consumable_id');
            $table->decimal('quantity_used', 10, 2);
            $table->string('usage_type')->default('automatic');
            $table->unsignedBigInteger('test_id')->nullable();
            $table->unsignedBigInteger('used_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('usage_date');
            $table->timestamps();

            $table->foreign('consumable_id')->references('id')->on('consumables')->onDelete('cascade');
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('set null');
            $table->foreign('used_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('usage_date');
            $table->index('consumable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_usages');
    }
};
