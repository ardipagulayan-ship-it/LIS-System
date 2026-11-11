<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_request_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['Pending', 'In Progress', 'For Verification', 'Verified', 'For Pathologist Review', 'Released', 'Cancelled'])
                ->default('Pending');
            $table->text('remarks')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pathologist_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->dateTime('performed_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_request_tests');
    }
}; 