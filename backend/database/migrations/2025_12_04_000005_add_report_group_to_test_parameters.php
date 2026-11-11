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
            $table->unsignedBigInteger('report_group_id')->nullable()->after('test_id');

            $table->foreign('report_group_id')
                  ->references('id')
                  ->on('report_groups')
                  ->onDelete('set null');

            $table->index('report_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_parameters', function (Blueprint $table) {
            if (Schema::hasColumn('test_parameters', 'report_group_id')) {
                $table->dropForeign(['report_group_id']);
                $table->dropIndex(['report_group_id']);
                $table->dropColumn('report_group_id');
            }
        });
    }
};
