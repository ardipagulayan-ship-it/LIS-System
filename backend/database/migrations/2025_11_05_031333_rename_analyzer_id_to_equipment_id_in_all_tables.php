<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Renames analyzer_id to equipment_id in all tables and updates foreign keys
     */
    public function up(): void
    {
        $tables = [
            'quality_controls',
            'test_results',
            'reference_ranges',
            'delta_check_rules',
            'qc_rules'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                // If equipment_id already exists, we might need to merge data from analyzer_id
                $hasEquipmentId = Schema::hasColumn($tableName, 'equipment_id');
                $hasAnalyzerId = Schema::hasColumn($tableName, 'analyzer_id');
                
                if ($hasAnalyzerId && !$hasEquipmentId) {
                    // Drop foreign key constraint if it exists
                    try {
                        $foreignKeys = DB::select("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = ? 
                            AND COLUMN_NAME = 'analyzer_id' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL
                        ", [$tableName]);
                        
                        foreach ($foreignKeys as $fk) {
                            DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                        }
                    } catch (\Exception $e) {
                        // Try common foreign key name patterns
                        $commonNames = [
                            "{$tableName}_analyzer_id_foreign",
                            "fk_{$tableName}_analyzer_id"
                        ];
                        foreach ($commonNames as $fkName) {
                            try {
                                DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fkName}`");
                                break;
                            } catch (\Exception $e2) {
                                // Continue
                            }
                        }
                    }

                    // Make sure the column is nullable
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->unsignedBigInteger('analyzer_id')->nullable()->change();
                    });

                    // Rename the column
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->renameColumn('analyzer_id', 'equipment_id');
                    });

                    // Add new foreign key to equipment table
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->foreign('equipment_id')
                              ->references('id')
                              ->on('equipment')
                              ->onDelete('set null');
                    });
                } elseif ($hasAnalyzerId && $hasEquipmentId) {
                    // Both exist - copy data from analyzer_id to equipment_id where equipment_id is null
                    DB::statement("
                        UPDATE `{$tableName}` 
                        SET `equipment_id` = `analyzer_id` 
                        WHERE `equipment_id` IS NULL AND `analyzer_id` IS NOT NULL
                    ");
                    
                    // Drop analyzer_id column and its foreign key
                    try {
                        $foreignKeys = DB::select("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = ? 
                            AND COLUMN_NAME = 'analyzer_id' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL
                        ", [$tableName]);
                        
                        foreach ($foreignKeys as $fk) {
                            DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                        }
                    } catch (\Exception $e) {
                        // Continue
                    }
                    
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropColumn('analyzer_id');
                    });
                } elseif (!$hasAnalyzerId && !$hasEquipmentId) {
                    // Neither exists - add equipment_id
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->unsignedBigInteger('equipment_id')->nullable()->after('test_parameter_id');
                        $table->foreign('equipment_id')
                              ->references('id')
                              ->on('equipment')
                              ->onDelete('set null');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'quality_controls',
            'test_results',
            'reference_ranges',
            'delta_check_rules',
            'qc_rules'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'equipment_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Drop foreign key constraint
                    try {
                        $foreignKeys = DB::select("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = ? 
                            AND COLUMN_NAME = 'equipment_id' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL
                        ", [$tableName]);
                        
                        foreach ($foreignKeys as $fk) {
                            $table->dropForeign([$fk->CONSTRAINT_NAME]);
                        }
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign([$tableName . '_equipment_id_foreign']);
                        } catch (\Exception $e2) {
                            // Continue
                        }
                    }
                });

                // Rename back to analyzer_id
                Schema::table($tableName, function (Blueprint $table) {
                    $table->renameColumn('equipment_id', 'analyzer_id');
                });

                // Add foreign key back to analyzers (if analyzers table still exists)
                if (Schema::hasTable('analyzers')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->foreign('analyzer_id')
                              ->references('id')
                              ->on('analyzers')
                              ->onDelete('set null');
                    });
                }
            }
        }
    }
};
