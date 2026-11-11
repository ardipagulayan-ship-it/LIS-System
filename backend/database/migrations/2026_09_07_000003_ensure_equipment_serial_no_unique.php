<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ensures equipment.serial_no is:
     *   1. NOT NULL   — every equipment record used for LIS ingestion must have a serial
     *   2. UNIQUE     — serial_no is the natural key for the LIS Bridge
     *
     * Steps:
     *   1. Check for NULL serials — fail with a clear message if found
     *   2. Check for duplicate serials — fail with a clear message if found
     *   3. Add NOT NULL + UNIQUE
     *
     * Rationale: The LIS Bridge resolves analyzers via Equipment::where('serial_no', $serial).
     * A NULL serial or a duplicate serial would make the lookup unreliable.
     * Requiring these constraints upfront prevents silent data-quality failures at ingestion time.
     *
     * Rollback: php artisan migrate:rollback
     */
    public function up(): void
    {
        // Step 1: Fail if any equipment row has a NULL serial_no
        $nullCount = DB::table('equipment')
            ->whereNull('serial_no')
            ->count();

        if ($nullCount > 0) {
            throw new \RuntimeException(
                "LIS Bridge requires every equipment row to have a serial_no. "
                . "Found {$nullCount} row(s) with NULL serial_no. "
                . "Populate the serial_no column before running this migration."
            );
        }

        // Step 2: Fail if any serial_no value is duplicated
        $duplicates = DB::table('equipment')
            ->select('serial_no')
            ->groupBy('serial_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('serial_no');

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                "LIS Bridge requires equipment.serial_no to be globally unique. "
                . "Found duplicate serial numbers: " . $duplicates->join(', ') . ". "
                . "Resolve duplicates before running this migration."
            );
        }

        // Step 3: Apply constraints
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('serial_no', 128)->nullable(false)->change();
            $table->string('serial_no', 128)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('serial_no', 128)->nullable()->change();
            $table->dropUnique(['serial_no']);
        });
    }
};
