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
        // Create test_packages table
        Schema::create('test_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });

        // Create pivot table package_test
        Schema::create('package_test', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_package_id');
            $table->unsignedBigInteger('test_id');
            $table->timestamps();
            
            $table->foreign('test_package_id')
                  ->references('id')
                  ->on('test_packages')
                  ->onDelete('cascade');
                  
            $table->foreign('test_id')
                  ->references('id')
                  ->on('tests')
                  ->onDelete('cascade');
                  
            $table->unique(['test_package_id', 'test_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_test');
        Schema::dropIfExists('test_packages');
    }
};
