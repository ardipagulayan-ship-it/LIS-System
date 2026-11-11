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
        Schema::table('test_results', function (Blueprint $table) {
            $table->enum('version', ['R0', 'R1', 'R2'])->default('R0')->after('id');
            $table->foreignId('parent_result_id')->nullable()->constrained('test_results')->onDelete('set null');

            // Add a new temporary column with the updated ENUM values
            $table->enum('status_new', ['pending', 'completed', 'validated', 'released', 'superseded', 'replaced', 'in-process'])->default('pending')->after('status');
        });

        // Copy data from old status to new status_new
        DB::statement('UPDATE test_results SET status_new = status');

        Schema::table('test_results', function (Blueprint $table) {
            // Drop the old status column
            $table->dropColumn('status');
        });

        Schema::table('test_results', function (Blueprint $table) {
            // Rename status_new to status
            $table->renameColumn('status_new', 'status');
        });

        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->boolean('has_active_rerun')->default(false)->after('status');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->text('reason')->nullable();
            $table->foreignId('laboratory_request_id')->nullable()->constrained('laboratory_requests')->onDelete('set null');
            $table->foreignId('test_result_id')->nullable()->constrained('test_results')->onDelete('set null');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->dropColumn('has_active_rerun');
        });

        Schema::dropIfExists('audit_logs');

        Schema::table('test_results', function (Blueprint $table) {
            // Reverse the status column changes
            $table->enum('status_old', ['pending', 'completed', 'validated', 'released'])->default('pending')->after('status');
        });

        DB::statement('UPDATE test_results SET status_old = status');

        Schema::table('test_results', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('test_results', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });

        Schema::table('test_results', function (Blueprint $table) {
            $table->dropForeign(['parent_result_id']);
            $table->dropColumn(['version', 'parent_result_id']);
        });
    }
};
