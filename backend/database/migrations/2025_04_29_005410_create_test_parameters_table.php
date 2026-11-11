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
        Schema::create('test_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('test_name');
            $table->unsignedBigInteger('test_id');
            $table->string('test_code')->nullable();
            $table->string('specimen_type')->nullable();
            $table->integer('sequence_no')->default(0);
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); // Includes created_at and updated_at

            // Optional: Add foreign key constraint for created_by if it references a users table
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_parameters');
    }
};
