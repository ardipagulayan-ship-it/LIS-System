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
        Schema::table('laboratory_requests', function (Blueprint $table) {
            // Add new columns
            $table->string('sample_id')->nullable()->after('doctor_id');
            $table->unsignedBigInteger('ward_id')->nullable()->after('sample_id');
            $table->unsignedBigInteger('room_id')->nullable()->after('ward_id');
            $table->string('referring_doctor')->nullable()->after('room_id');
            $table->string('priority_level')->nullable()->index()->after('referring_doctor'); // Added index
            $table->unsignedBigInteger('patient_type_id')->nullable()->after('priority_level');
            $table->unsignedBigInteger('created_by')->nullable()->after('patient_type_id'); // Assuming link to users table

            // Add foreign key constraints
            $table->foreign('ward_id')->references('id')->on('wards')->onDelete('set null');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
            $table->foreign('patient_type_id')->references('id')->on('patient_types')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null'); // Assumes users table exists

            // Drop old columns
            $table->dropColumn('status');
            $table->dropColumn('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            // Add old columns back (reverse of up)
            $table->string('status')->default('Pending'); // Re-add with a default if needed
            $table->decimal('total_amount', 10, 2)->default(0.00);

            // Drop foreign keys (order might matter depending on DB)
            // Note: Need to provide the conventional FK names or specify them
            $table->dropForeign(['ward_id']);
            $table->dropForeign(['room_id']);
            $table->dropForeign(['patient_type_id']);
            $table->dropForeign(['created_by']);

            // Drop new columns
            $table->dropColumn(['sample_id', 'ward_id', 'room_id', 'referring_doctor', 'priority_level', 'patient_type_id', 'created_by']);
        });
    }
};
