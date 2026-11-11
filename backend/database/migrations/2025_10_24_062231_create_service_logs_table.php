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
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('maintenance_schedule_id')->nullable()->constrained()->onDelete('set null');
            $table->datetime('service_date');
            $table->enum('service_type', [
                'preventative_maintenance',
                'calibration',
                'validation',
                'repair',
                'inspection'
            ]);
            $table->string('action_taken');
            $table->text('description')->nullable();
            $table->enum('outcome', ['pass', 'fail', 'partial'])->default('pass');
            $table->string('service_provider');
            $table->foreignId('technician_id')->nullable()->constrained('users');
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->datetime('downtime_start')->nullable();
            $table->datetime('downtime_end')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->json('parts_used')->nullable();
            $table->string('documentation_path')->nullable();
            $table->text('digital_signature_technician')->nullable();
            $table->text('digital_signature_reviewer')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['equipment_id', 'service_date']);
            $table->index(['service_type']);
            $table->index(['outcome']);
            $table->index(['technician_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
