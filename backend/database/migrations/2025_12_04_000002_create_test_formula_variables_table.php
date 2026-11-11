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
        Schema::create('test_formula_variables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formula_id');
            $table->unsignedBigInteger('source_test_parameter_id');
            
            $table->foreign('formula_id')
                  ->references('id')
                  ->on('test_formulas')
                  ->onDelete('cascade');
            
            $table->foreign('source_test_parameter_id')
                  ->references('id')
                  ->on('test_parameters')
                  ->onDelete('restrict');
            
            $table->timestamps();

            // Composite unique to prevent duplicate dependencies
            $table->unique(['formula_id', 'source_test_parameter_id'], 'tfv_formula_param_unique');
            $table->index('source_test_parameter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('test_formula_variables');
        Schema::enableForeignKeyConstraints();
    }
};
