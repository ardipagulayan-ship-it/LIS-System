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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->enum('schedule_type', [
                'preventative_maintenance',
                'calibration',
                'validation',
                'repair'
            ]);
            $table->integer('frequency_months')->nullable();
            $table->integer('frequency_cycles')->nullable();
            $table->integer('frequency_hours')->nullable();
            $table->date('last_completed_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->foreignId('assigned_personnel_id')->nullable()->constrained('users');
            $table->string('assigned_vendor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['equipment_id', 'is_active']);
            $table->index(['next_due_date']);
            $table->index(['schedule_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
