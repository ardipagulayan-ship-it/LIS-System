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
            // Check-in tracking
            $table->foreignId('checkin_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('checkin_at')->nullable();
            
            // Send-out tracking
            $table->foreignId('sendout_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sendout_at')->nullable();
            
            // Park tracking
            $table->foreignId('park_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('park_at')->nullable();
            
            // Performance tracking - moving these from test_results to request level
            $table->foreignId('perform_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('perform_at')->nullable();
            
            // Verification tracking - moving these from test_results to request level
            $table->foreignId('verify_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verify_at')->nullable();
            
            // Pathologist assignment
            $table->foreignId('pathologist_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_request_test', function (Blueprint $table) {
            // Remove all added columns
            $table->dropForeign(['checkin_by']);
            $table->dropColumn('checkin_by');
            $table->dropColumn('checkin_at');
            
            $table->dropForeign(['sendout_by']);
            $table->dropColumn('sendout_by');
            $table->dropColumn('sendout_at');
            
            $table->dropForeign(['park_by']);
            $table->dropColumn('park_by');
            $table->dropColumn('park_at');
            
            $table->dropForeign(['perform_by']);
            $table->dropColumn('perform_by');
            $table->dropColumn('perform_at');
            
            $table->dropForeign(['verify_by']);
            $table->dropColumn('verify_by');
            $table->dropColumn('verify_at');
            
            $table->dropForeign(['pathologist_id']);
            $table->dropColumn('pathologist_id');
        });
    }
}; 