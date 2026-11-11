<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes quality_control_results.result_value from decimal(10,4) NOT NULL
     * to varchar(255) NULL.
     *
     * Rationale: The wire format sends value as a raw string — e.g. "14.2",
     * ">100", "POS", "NEG". A decimal column cannot store ">100" or "POS".
     * Widen to varchar to store all analyzer output. Application code that
     * needs numeric comparison can cast at read time.
     *
     * Rollback: php artisan migrate:rollback
     */
    public function up(): void
    {
        Schema::table('quality_control_results', function (Blueprint $table) {
            $table->string('result_value', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quality_control_results', function (Blueprint $table) {
            $table->decimal('result_value', 10, 4)->nullable(false)->change();
        });
    }
};
