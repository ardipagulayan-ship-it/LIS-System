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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // $table->string('name'); // Remove single name column
            $table->string('first_name'); // Add first name
            $table->string('middle_name')->nullable(); // Add nullable middle name
            $table->string('last_name'); // Add last name
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->string('civil_status')->nullable(); // Added based on previous frontend
            $table->timestamps();
            $table->softDeletes(); // Optional: for soft deleting patients
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
