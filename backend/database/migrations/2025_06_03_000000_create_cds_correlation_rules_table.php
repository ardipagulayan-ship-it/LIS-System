<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cds_correlation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            // Triggering analyte (test parameter) that initiates the rule check
            $table->unsignedBigInteger('trigger_test_id'); // FK to test_parameters.id
            // Full nested AND/OR boolean logic as JSON
            $table->json('rule_logic_json');
            // Comma-separated list of correlated test_parameter IDs referenced by the rule
            $table->string('correlated_tests')->nullable();
            $table->string('alert_message')->nullable();
            $table->enum('action_type', ['STOP_AUTO_VERIFY', 'REFLEX_TEST', 'ADD_COMMENT'])->default('STOP_AUTO_VERIFY');
            $table->unsignedBigInteger('reflex_test_id')->nullable(); // If action is REFLEX_TEST, which parameter/test to add
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('trigger_test_id')->references('id')->on('test_parameters')->onDelete('cascade');
            $table->foreign('reflex_test_id')->references('id')->on('test_parameters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cds_correlation_rules');
    }
};



