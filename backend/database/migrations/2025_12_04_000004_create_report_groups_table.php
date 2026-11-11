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
        Schema::create('report_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('test_category_id')->nullable();
            $table->unsignedBigInteger('report_layout_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('test_category_id')
                  ->references('id')
                  ->on('test_categories')
                  ->onDelete('set null');

            $table->foreign('report_layout_id')
                  ->references('id')
                  ->on('report_layouts')
                  ->onDelete('set null');

            $table->index(['test_category_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('report_groups');
        Schema::enableForeignKeyConstraints();
    }
};
