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
        Schema::create('report_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('layout_json')->nullable();
            $table->string('page_size')->default('A4');
            $table->timestamps();

            $table->index('page_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('report_layouts');
        Schema::enableForeignKeyConstraints();
    }
};
