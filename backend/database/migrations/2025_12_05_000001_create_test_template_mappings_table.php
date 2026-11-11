<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('test_template_mappings')) {
            Schema::create('test_template_mappings', function (Blueprint $table) {
                $table->id();
                // `test_type_id` is optional mapping to your test types table. The foreign key is not enforced here to avoid migration order issues.
                $table->unsignedBigInteger('test_type_id')->nullable();
                $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_template_mappings');
    }
};
