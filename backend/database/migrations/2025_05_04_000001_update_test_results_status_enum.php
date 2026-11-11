<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // MySQL syntax to modify enum values
        DB::statement("ALTER TABLE test_results MODIFY COLUMN status ENUM('pending', 'saved', 'completed', 'validated', 'released') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE test_results MODIFY COLUMN status ENUM('pending', 'completed', 'validated', 'released') NOT NULL DEFAULT 'pending'");
    }
}; 