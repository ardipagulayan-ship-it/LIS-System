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
            $table->decimal('conversion_factor', 15, 8)->nullable()->after('specimen_type')->comment('Factor to multiply SI value by to get Conventional value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_parameters', function (Blueprint $table) {
            $table->dropColumn('conversion_factor');
        });
    }
};
