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
            if (!Schema::hasColumn('reference_ranges', 'equipment_id')) {
                $table->unsignedBigInteger('equipment_id')->nullable()->after('test_parameter_id');
                $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('set null');
                $table->index('equipment_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_ranges', function (Blueprint $table) {
            if (Schema::hasColumn('reference_ranges', 'equipment_id')) {
                $table->dropForeign(['equipment_id']);
                $table->dropIndex(['equipment_id']);
                $table->dropColumn('equipment_id');
            }
        });
    }
};
