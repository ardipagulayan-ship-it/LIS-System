<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('gender')->nullable();
            $table->decimal('min_age', 5, 2)->nullable();
            $table->decimal('max_age', 5, 2)->nullable();
            $table->string('condition')->nullable();
            $table->decimal('lower_limit', 10, 4)->nullable();
            $table->decimal('upper_limit', 10, 4)->nullable();
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->text('interpretation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_ranges');
    }
}; 