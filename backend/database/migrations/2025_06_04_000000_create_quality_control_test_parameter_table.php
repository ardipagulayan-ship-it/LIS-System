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
        Schema::create('quality_control_test_parameter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quality_control_id');
            $table->foreign('quality_control_id', 'qc_param_qc_fk')
                  ->references('id')
                  ->on('quality_controls')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('test_parameter_id');
            $table->foreign('test_parameter_id', 'qc_param_tp_fk')
                  ->references('id')
                  ->on('test_parameters')
                  ->onDelete('cascade');
            $table->decimal('target', 10, 4)->nullable();
            $table->decimal('tolerance', 10, 4)->nullable();
            $table->decimal('minus_3sd', 10, 4)->nullable();
            $table->decimal('minus_2sd', 10, 4)->nullable();
            $table->decimal('minus_1sd', 10, 4)->nullable();
            $table->decimal('plus_1sd', 10, 4)->nullable();
            $table->decimal('plus_2sd', 10, 4)->nullable();
            $table->decimal('plus_3sd', 10, 4)->nullable();
            $table->timestamps();

            // Add unique constraint with a shorter name
            $table->unique(['quality_control_id', 'test_parameter_id'], 'qc_param_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_control_test_parameter');
    }
}; 