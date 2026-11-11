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
        Schema::table('test_parameters', function (Blueprint $table) {
            $table->decimal('delta_check_percentage_limit', 5, 2)->nullable()->after('test_name');
            $table->decimal('delta_check_absolute_limit', 8, 2)->nullable()->after('delta_check_percentage_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_parameters', function (Blueprint $table) {
            $table->dropColumn(['delta_check_percentage_limit', 'delta_check_absolute_limit']);
        });
    }
};
