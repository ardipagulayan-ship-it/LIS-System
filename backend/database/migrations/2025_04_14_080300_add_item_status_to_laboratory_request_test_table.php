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
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Add the item_status column
            $table->string('item_status')->default('Pending')->after('price_at_request'); // Adjust ->after() if needed
            // Add an index for potentially faster lookups by status
            $table->index('item_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Remove the index first (important for some DBs)
            $table->dropIndex(['item_status']); // Or $table->dropIndex('laboratory_request_test_item_status_index');
            // Remove the column
            $table->dropColumn('item_status');
        });
    }
}; 