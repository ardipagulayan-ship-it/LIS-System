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
        Schema::table('equipment', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment', 'test_category_id')) {
                $table->unsignedBigInteger('test_category_id')->nullable()->after('current_status');
                $table->foreign('test_category_id')->references('id')->on('test_categories')->onDelete('set null');
                $table->index('test_category_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            if (Schema::hasColumn('equipment', 'test_category_id')) {
                $table->dropForeign(['test_category_id']);
                $table->dropIndex(['test_category_id']);
                $table->dropColumn('test_category_id');
            }
        });
    }
};
