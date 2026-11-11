<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            // Add comment field
            $table->text('comment')->nullable();
            
            // Remove remarks field if it exists
            if (Schema::hasColumn('laboratory_requests', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            // Remove comment field
            $table->dropColumn('comment');
            
            // Add back remarks field if it was removed
            if (!Schema::hasColumn('laboratory_requests', 'remarks')) {
                $table->text('remarks')->nullable();
            }
        });
    }
}; 