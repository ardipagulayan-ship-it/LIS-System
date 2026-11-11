<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quality_control_test_parameter_id');
            $table->foreign('quality_control_test_parameter_id', 'qc_result_param_fk')
                  ->references('id')
                  ->on('quality_control_test_parameter')
                  ->onDelete('cascade');
            $table->decimal('result_value', 10, 4);
            $table->decimal('standard_deviation_index', 10, 4)->nullable();
            $table->string('unit')->nullable();
            $table->date('run_date');
            $table->time('run_time');
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('westgard_violations')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->boolean('is_within_range')->default(true);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_results');
    }
}; 