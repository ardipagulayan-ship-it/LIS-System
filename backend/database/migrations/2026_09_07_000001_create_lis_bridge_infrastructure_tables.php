<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the four LIS Bridge infrastructure tables:
     *   - lis_bridge_configs        : bridge instance configuration
     *   - lis_bridge_api_keys       : bearer-token authentication
     *   - lis_bridge_translation    : sole source of truth for dedup + wire-to-internal mapping
     *   - lis_ingest_log            : forensics only (request body, response, timing)
     *
     * Rollback: php artisan migrate:rollback
     */
    public function up(): void
    {
        // ─── 1. lis_bridge_configs ─────────────────────────────────────────────
        Schema::create('lis_bridge_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('lis_url', 255)->nullable();
            $table->string('patient_lis_url', 255)->nullable();
            $table->string('qc_lis_url', 255)->nullable();
            // control_id_map: JSON map of vendor-supplied control names → local quality_controls.id
            // Example: { "QC_NORMAL": 7, "QC_HIGH": 8, "QC_LOW": 9 }
            $table->json('control_id_map')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─── 2. lis_bridge_api_keys ─────────────────────────────────────────────
        Schema::create('lis_bridge_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lis_bridge_config_id')
                ->constrained('lis_bridge_configs')
                ->onDelete('cascade');
            // sha-256 hex of the raw bearer token — stored hashed, never plaintext
            $table->char('key_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // ─── 3. lis_bridge_translation ───────────────────────────────────────────
        // Sole source of truth for wire-to-internal mapping and idempotency.
        // Result tables (test_results, quality_control_results) carry NO additive
        // LIS Bridge columns — this table is the only record of the bridge origin.
        Schema::create('lis_bridge_translation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lis_bridge_config_id')
                ->constrained('lis_bridge_configs')
                ->onDelete('cascade');
            // Middleware's Result.id — used for idempotency dedup
            $table->unsignedBigInteger('wire_result_id');
            $table->boolean('is_qc');
            // Which internal table and PK this wire row was stored into
            $table->string('internal_table', 64);   // 'test_results' | 'quality_control_results'
            $table->unsignedBigInteger('internal_id');
            // Wire-field mirrors — stored for operator forensics (read-only from LIS side)
            $table->string('wire_analyzer_serial', 128)->nullable();
            $table->string('wire_patient_id', 64)->nullable();
            $table->string('wire_sample_id', 64)->nullable();
            $table->string('wire_test_code', 64)->nullable();
            $table->timestamp('wire_observed_at')->nullable();
            $table->string('wire_value_raw', 255)->nullable();
            $table->char('wire_result_status', 1)->nullable();   // F / P / C / X
            $table->unsignedInteger('wire_run_number')->nullable();
            $table->string('wire_control_id', 64)->nullable();
            $table->timestamp('ingested_at')->useCurrent();

            // Constraints
            $table->unique(
                ['lis_bridge_config_id', 'wire_result_id'],
                'uniq_bridge_wire'
            );
            $table->index(['internal_table', 'internal_id'], 'idx_internal');
            $table->index('ingested_at', 'idx_ingested_at');
            $table->index('wire_analyzer_serial', 'idx_analyzer_serial');
        });

        // ─── 4. lis_ingest_log ──────────────────────────────────────────────────
        // Append-only forensics table. Every inbound request is logged regardless
        // of success or failure. Used for debugging, auditing, and replay.
        Schema::create('lis_ingest_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lis_bridge_config_id')
                ->nullable()
                ->constrained('lis_bridge_configs')
                ->nullOnDelete();
            $table->string('route', 128);           // e.g. '/api/v1/patient-results'
            $table->unsignedSmallInteger('status_code');
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['lis_bridge_config_id', 'created_at'], 'idx_bridge_created');
            $table->index('status_code', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lis_ingest_log');
        Schema::dropIfExists('lis_bridge_translation');
        Schema::dropIfExists('lis_bridge_api_keys');
        Schema::dropIfExists('lis_bridge_configs');
    }
};
