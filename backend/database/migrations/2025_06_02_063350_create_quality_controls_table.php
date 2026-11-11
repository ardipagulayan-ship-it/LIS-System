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
        Schema::create('quality_controls', function (Blueprint $table) {
            $table->id();
            $table->integer('test_parameter_id');
            $table->integer('equipment_id');
            $table->string('control_name');
            $table->string('lot_number')->unique();
            $table->date('expiry_date')->nullable();
            $table->integer('mean')->nullable();
            $table->integer('sd')->nullable();
            $table->integer('target')->nullable();
            $table->integer('sd_1_plus')->nullable();
            $table->integer('sd_1_minus')->nullable();
            $table->integer('sd_2_plus')->nullable();
            $table->integer('sd_2_minus')->nullable()   ;
            $table->integer('sd_3_plus')->nullable();
            $table->integer('sd_3_minus')->nullable();
            $table->string('level')->nullable(); // e.g. Normal, Abnormal, High, Low
            $table->boolean('is_active');
            $table->integer('created_by');
            $table->integer('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_controls');
    }
}; 