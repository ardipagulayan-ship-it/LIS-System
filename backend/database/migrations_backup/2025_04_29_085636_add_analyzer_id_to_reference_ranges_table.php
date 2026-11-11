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
            if (!Schema::hasColumn('reference_ranges', 'analyzer_id')) {
                $table->unsignedBigInteger('analyzer_id')->nullable()->after('test_parameter_id');
                $table->foreign('analyzer_id')->references('id')->on('analyzers')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_ranges', function (Blueprint $table) {
            if (Schema::hasColumn('reference_ranges', 'analyzer_id')) {
                $table->dropForeign(['analyzer_id']);
                $table->dropColumn('analyzer_id');
            }
        });
    }
};
