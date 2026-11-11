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
        Schema::create('reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_parameter_id');
            $table->string('si_range')->nullable()->comment('Display string for SI range, e.g., 4.0-11.0');
            $table->string('conv_range')->nullable()->comment('Display string for Conventional range');
            $table->decimal('low_critical_value', 10, 4)->nullable();
            $table->decimal('low_value', 10, 4)->nullable();
            $table->decimal('high_value', 10, 4)->nullable();
            $table->decimal('high_critical_value', 10, 4)->nullable();
            $table->integer('age_min');
            $table->integer('age_max');
            $table->enum('age_unit', ['Days', 'Months', 'Years']);
            $table->enum('gender', ['Male', 'Female', 'Any'])->default('Any');
            $table->string('analyzer')->nullable()->comment('Specific analyzer this range applies to, if any');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); // created_at and updated_at

            $table->foreign('test_parameter_id')
                  ->references('id')->on('test_parameters')
                  ->onDelete('cascade');

            // Index for faster lookups based on parameters, age, gender
            $table->index(['test_parameter_id', 'age_min', 'age_max', 'age_unit', 'gender'], 'ref_range_lookup_index');

            // Optional: Foreign key for created_by if you have a users table
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_ranges');
    }
};
