<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_control_id')->constrained()->cascadeOnDelete();
            $table->decimal('result', 10, 4);
            $table->decimal('standard_deviation_index', 10, 4)->nullable();
            $table->boolean('is_within_range')->default(true);
            $table->text('remarks')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_results');
    }
}; 