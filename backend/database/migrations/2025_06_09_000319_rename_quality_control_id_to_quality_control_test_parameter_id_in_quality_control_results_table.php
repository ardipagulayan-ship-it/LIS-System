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
        // Schema::table('quality_control_results', function (Blueprint $table) {
        //     // Drop existing foreign key if it exists
        //     //$table->dropForeign(['quality_control_id']);
            
        //     // Rename the column
        //     $table->renameColumn('quality_control_id', 'quality_control_test_parameter_id');

        //     // Add new foreign key constraint
        //     $table->foreign('quality_control_test_parameter_id', 'qc_result_qc_param_foreign')->references('id')->on('quality_control_test_parameter')->onDelete('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('quality_control_results', function (Blueprint $table) {
        //     // Drop the new foreign key constraint
        //     //$table->dropForeign(['qc_result_qc_param_foreign']);

        //     // Rename the column back
        //     $table->renameColumn('quality_control_test_parameter_id', 'quality_control_id');
        // });
    }
};
