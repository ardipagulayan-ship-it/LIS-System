<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds equipment_id column to qc_rules table with proper foreign key to equipment table
     */
    public function up(): void
    {
        Schema::table('qc_rules', function (Blueprint $table) {
            // Add equipment_id if it doesn't exist
            if (!Schema::hasColumn('qc_rules', 'equipment_id')) {
                $table->unsignedBigInteger('equipment_id')->nullable()->after('test_parameter_id');
                $table->index('equipment_id');
            }
        });

        // Fix foreign key: drop old reference to analyzers if exists, add reference to equipment
        try {
            // Drop existing foreign key if it exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'qc_rules'
                AND COLUMN_NAME = 'equipment_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE `qc_rules` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        // Add new foreign key to equipment table
        Schema::table('qc_rules', function (Blueprint $table) {
            $table->foreign('equipment_id')
                  ->references('id')
                  ->on('equipment')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_rules', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
        });
    }
};
