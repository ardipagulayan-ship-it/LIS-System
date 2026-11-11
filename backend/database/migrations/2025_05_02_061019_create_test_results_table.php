<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If the table already exists, alter it instead of creating it
        if (Schema::hasTable('test_results')) {
            // Use try/catch for each operation to make the migration more robust
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    // Add columns if they don't exist
                    if (!Schema::hasColumn('test_results', 'test_parameter_id')) {
                        $table->foreignId('test_parameter_id')->nullable()->constrained()->onDelete('set null');
                    }
                });
            } catch (\Exception $e) {
                // Log the error but continue
                error_log('Error adding test_parameter_id: ' . $e->getMessage());
            }
            
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    if (!Schema::hasColumn('test_results', 'equipment_id')) {
                        $table->foreignId('equipment_id')->nullable()->constrained()->onDelete('set null');
                    }
                });
            } catch (\Exception $e) {
                error_log('Error adding equipment_id: ' . $e->getMessage());
            }
            
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    if (!Schema::hasColumn('test_results', 'performer_id')) {
                        $table->foreignId('performer_id')->nullable()->constrained('users')->onDelete('set null');
                    }
                });
            } catch (\Exception $e) {
                error_log('Error adding performer_id: ' . $e->getMessage());
            }
            
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    if (!Schema::hasColumn('test_results', 'verifier_id')) {
                        $table->foreignId('verifier_id')->nullable()->constrained('users')->onDelete('set null');
                    }
                });
            } catch (\Exception $e) {
                error_log('Error adding verifier_id: ' . $e->getMessage());
            }
            
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    if (!Schema::hasColumn('test_results', 'pathologist_id')) {
                        $table->foreignId('pathologist_id')->nullable()->constrained('users')->onDelete('set null');
                    }
                });
            } catch (\Exception $e) {
                error_log('Error adding pathologist_id: ' . $e->getMessage());
            }
            
            // Rename columns if they exist with different names
            try {
                if (Schema::hasColumn('test_results', 'result') && !Schema::hasColumn('test_results', 'result_value')) {
                    Schema::table('test_results', function (Blueprint $table) {
                        $table->renameColumn('result', 'result_value');
                    });
                }
            } catch (\Exception $e) {
                error_log('Error renaming result to result_value: ' . $e->getMessage());
            }
            
            try {
                if (Schema::hasColumn('test_results', 'interpretation') && !Schema::hasColumn('test_results', 'result_comment')) {
                    Schema::table('test_results', function (Blueprint $table) {
                        $table->renameColumn('interpretation', 'result_comment');
                    });
                }
            } catch (\Exception $e) {
                error_log('Error renaming interpretation to result_comment: ' . $e->getMessage());
            }
            
            // Add new result_comment field if it doesn't exist
            try {
                if (!Schema::hasColumn('test_results', 'result_comment')) {
                    Schema::table('test_results', function (Blueprint $table) {
                        $table->text('result_comment')->nullable()->after('unit');
                        error_log('Added new result_comment column');
                    });
                }
            } catch (\Exception $e) {
                error_log('Error adding result_comment column: ' . $e->getMessage());
            }
            
            // Add other columns if needed
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    if (!Schema::hasColumn('test_results', 'status')) {
                        $table->enum('status', ['pending', 'completed', 'validated', 'released'])->default('pending');
                    }
                });
            } catch (\Exception $e) {
                error_log('Error adding status column: ' . $e->getMessage());
            }
            
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    if (!Schema::hasColumn('test_results', 'flag')) {
                        $table->string('flag')->nullable();
                    }
                });
            } catch (\Exception $e) {
                error_log('Error adding flag column: ' . $e->getMessage());
            }
            
            try {
                Schema::table('test_results', function (Blueprint $table) {
                    if (!Schema::hasColumn('test_results', 'unit')) {
                        $table->string('unit')->nullable();
                    }
                });
            } catch (\Exception $e) {
                error_log('Error adding unit column: ' . $e->getMessage());
            }
        } else {
            // Create the table if it doesn't exist
            Schema::create('test_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('laboratory_request_id')->constrained()->onDelete('cascade');
                $table->foreignId('test_id')->constrained()->onDelete('cascade');
                $table->foreignId('test_parameter_id')->nullable()->constrained()->onDelete('set null');
                $table->string('result_value')->nullable();
                $table->string('flag')->nullable(); // L, H, l, h, null
                $table->string('unit')->nullable();
                $table->foreignId('equipment_id')->nullable()->constrained()->onDelete('set null');
                $table->text('result_comment')->nullable();
                $table->foreignId('performer_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('verifier_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('pathologist_id')->nullable()->constrained('users')->onDelete('set null');
                $table->enum('status', ['pending', 'completed', 'validated', 'released'])->default('pending');
                $table->timestamps();
                
                // Try to add a unique constraint - may fail if schema doesn't support it
                try {
                    $table->unique(['laboratory_request_id', 'test_id', 'test_parameter_id'], 'unique_test_parameter_result');
                } catch (\Exception $e) {
                    error_log('Failed to add unique constraint: ' . $e->getMessage());
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If this was a new table (created in this migration), drop it
        if (!Schema::hasTable('test_results')) {
            return;
        }
        
        // Since we were adding columns to an existing table, let's just try to remove the columns we added
        $columnsToCheck = [
            'test_parameter_id', 'equipment_id', 'performer_id',
            'verifier_id', 'pathologist_id', 'flag', 'result_value', 'result_comment', 'status', 'unit'
        ];
        
        foreach ($columnsToCheck as $column) {
            try {
                if (Schema::hasColumn('test_results', $column)) {
                    Schema::table('test_results', function (Blueprint $table) use ($column) {
                        if (in_array($column, ['test_parameter_id', 'equipment_id', 'performer_id', 'verifier_id', 'pathologist_id'])) {
                            $table->dropForeign([$column]);
                        }
                        $table->dropColumn($column);
                    });
                }
            } catch (\Exception $e) {
                error_log("Error dropping column $column: " . $e->getMessage());
            }
        }
    }
};
