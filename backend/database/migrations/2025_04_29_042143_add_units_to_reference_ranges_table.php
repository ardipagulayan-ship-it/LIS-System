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
        Schema::table('reference_ranges', function (Blueprint $table) {
            $table->string('si_unit')->nullable()->after('si_range');
            $table->string('conv_unit')->nullable()->after('conv_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_ranges', function (Blueprint $table) {
            $table->dropColumn(['si_unit', 'conv_unit']);
        });
    }
};
