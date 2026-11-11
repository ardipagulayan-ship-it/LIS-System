<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_request_test_id')->constrained()->cascadeOnDelete();
            $table->string('parameter')->nullable();
            $table->string('result')->nullable();
            $table->string('unit')->nullable();
            $table->string('flag')->nullable(); // H - High, L - Low, N - Normal
            $table->text('reference_range')->nullable();
            $table->text('interpretation')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['Draft', 'Final', 'Amended', 'Cancelled'])->default('Draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
}; 