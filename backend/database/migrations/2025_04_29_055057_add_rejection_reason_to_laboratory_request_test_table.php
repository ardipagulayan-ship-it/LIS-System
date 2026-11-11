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
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Add rejection_reason column, nullable text
            $table->text('rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Drop the column if migration is rolled back
            $table->dropColumn('rejection_reason');
        });
    }
};
