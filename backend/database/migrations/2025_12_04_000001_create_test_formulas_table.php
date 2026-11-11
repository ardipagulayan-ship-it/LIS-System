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
        Schema::create('test_formulas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('target_test_parameter_id')->unique();
            $table->foreign('target_test_parameter_id')
                  ->references('id')
                  ->on('test_parameters')
                  ->onDelete('restrict');
            $table->json('formula_json'); // Stores the visual formula structure for UI reconstruction
            $table->text('compiled_formula'); // Simplified string for calculation engine (e.g., "( [101] - [102] )")
            $table->tinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('target_test_parameter_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('test_formulas');
        Schema::enableForeignKeyConstraints();
    }
};
