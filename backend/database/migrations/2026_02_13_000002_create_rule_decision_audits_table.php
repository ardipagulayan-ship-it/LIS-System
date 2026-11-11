<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_decision_audits', function (Blueprint $table) {
            $table->id();
            $table->string('rule_type');
            $table->string('decision');
            $table->text('message')->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('policy')->nullable();
            $table->unsignedBigInteger('laboratory_request_id')->nullable();
            $table->unsignedBigInteger('test_result_id')->nullable();
            $table->unsignedBigInteger('test_parameter_id')->nullable();
            $table->unsignedBigInteger('test_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['rule_type', 'decision']);
            $table->index('laboratory_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_decision_audits');
    }
};
