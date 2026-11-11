<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\LisBridgeConfig;
use App\Models\LisBridgeApiKey;
use App\Models\LisBridgeTranslation;
use App\Models\TestResult;
use App\Models\QualityControlResult;
use App\Models\LisIngestLog;
use Tests\TestCase;

class LisBridgeModelsTest extends TestCase
{
    use RefreshDatabase;

    // ========================================================================
    // LisBridgeConfig
    // ========================================================================

    public function test_resolve_control_id_returns_id_for_known_control(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'control_id_map' => [
                'QC_NORMAL' => 10,
                'QC_ABNORMAL' => 20,
            ],
            'is_active' => true,
        ]);

        $this->assertEquals(10, $config->resolveControlId('QC_NORMAL'));
        $this->assertEquals(20, $config->resolveControlId('QC_ABNORMAL'));
    }

    public function test_resolve_control_id_returns_null_for_unknown_control(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'control_id_map' => ['QC_NORMAL' => 10],
            'is_active' => true,
        ]);

        $this->assertNull($config->resolveControlId('QC_UNKNOWN'));
    }

    public function test_resolve_control_id_returns_null_when_map_is_empty(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'control_id_map' => [],
            'is_active' => true,
        ]);

        $this->assertNull($config->resolveControlId('QC_NORMAL'));
    }

    public function test_active_scope_returns_only_active_configs(): void
    {
        $active = LisBridgeConfig::create([
            'name' => 'Active Bridge',
            'lis_url' => 'http://active',
            'is_active' => true,
        ]);

        LisBridgeConfig::create([
            'name' => 'Inactive Bridge',
            'lis_url' => 'http://inactive',
            'is_active' => false,
        ]);

        $activeConfigs = LisBridgeConfig::active()->get();

        $this->assertCount(1, $activeConfigs);
        $this->assertEquals('Active Bridge', $activeConfigs->first()->name);
    }

    public function test_control_id_map_is_cast_to_array(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'control_id_map' => ['QC_NORMAL' => 5],
            'is_active' => true,
        ]);

        $config->refresh();

        $this->assertIsArray($config->control_id_map);
        $this->assertEquals(5, $config->control_id_map['QC_NORMAL']);
    }

    public function test_active_api_key_returns_first_valid_key(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        // Expired key
        LisBridgeApiKey::create([
            'lis_bridge_config_id' => $config->id,
            'key_hash' => hash('sha256', 'expired-key'),
            'expires_at' => now()->subDay(),
        ]);

        // Valid key
        $validResult = LisBridgeApiKey::createForConfig($config, null);
        $validKey = $validResult['model'];

        $this->assertEquals($validKey->id, $config->activeApiKey()->id);
    }

    public function test_active_api_key_returns_null_when_no_valid_keys(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        // Only expired key
        LisBridgeApiKey::create([
            'lis_bridge_config_id' => $config->id,
            'key_hash' => hash('sha256', 'expired-key'),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertNull($config->activeApiKey());
    }

    // ========================================================================
    // LisBridgeApiKey
    // ========================================================================

    public function test_verify_token_uses_constant_time_comparison(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $result = LisBridgeApiKey::createForConfig($config, null);
        /** @var LisBridgeApiKey $apiKey */
        $apiKey = $result['model'];
        $rawToken = $result['raw_token'];

        // Valid token
        $this->assertTrue($apiKey->verifyToken($rawToken));

        // Wrong token
        $this->assertFalse($apiKey->verifyToken('wrong-token'));

        // Empty token
        $this->assertFalse($apiKey->verifyToken(''));
    }

    public function test_key_hash_is_hidden_from_serialization(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $result = LisBridgeApiKey::createForConfig($config, null);
        $apiKey = $result['model'];
        $array = $apiKey->toArray();

        $this->assertArrayNotHasKey('key_hash', $array);
    }

    public function test_create_for_config_generates_unique_raw_tokens(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $result1 = LisBridgeApiKey::createForConfig($config, null);
        $result2 = LisBridgeApiKey::createForConfig($config, null);

        $this->assertNotEquals($result1['raw_token'], $result2['raw_token']);
        $this->assertEquals(64, strlen($result1['raw_token'])); // 32 bytes hex = 64 chars
    }

    public function test_valid_scope_excludes_expired_keys(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        LisBridgeApiKey::create([
            'lis_bridge_config_id' => $config->id,
            'key_hash' => hash('sha256', 'valid-key'),
            'expires_at' => null, // Never expires
        ]);

        LisBridgeApiKey::create([
            'lis_bridge_config_id' => $config->id,
            'key_hash' => hash('sha256', 'expired-key'),
            'expires_at' => now()->subDay(),
        ]);

        LisBridgeApiKey::create([
            'lis_bridge_config_id' => $config->id,
            'key_hash' => hash('sha256', 'future-key'),
            'expires_at' => now()->addDay(), // Future — still valid
        ]);

        $validKeys = LisBridgeApiKey::valid()->get();

        $this->assertCount(2, $validKeys);
    }

    public function test_touch_last_used_updates_timestamp(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $result = LisBridgeApiKey::createForConfig($config, null);
        /** @var LisBridgeApiKey $apiKey */
        $apiKey = $result['model'];

        $this->assertNull($apiKey->last_used_at);

        $apiKey->touchLastUsed();
        $apiKey->refresh();

        $this->assertNotNull($apiKey->last_used_at);
    }

    // ========================================================================
    // LisBridgeTranslation
    // ========================================================================

    public function test_already_processed_returns_true_for_existing_combination(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        LisBridgeTranslation::create([
            'lis_bridge_config_id' => $config->id,
            'wire_result_id' => 12345,
            'is_qc' => false,
            'internal_table' => 'test_results',
            'internal_id' => 1,
        ]);

        $this->assertTrue(
            LisBridgeTranslation::alreadyProcessed($config->id, 12345)
        );
    }

    public function test_already_processed_returns_false_for_new_combination(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $this->assertFalse(
            LisBridgeTranslation::alreadyProcessed($config->id, 99999)
        );
    }

    public function test_find_by_wire_result_returns_translation(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        LisBridgeTranslation::create([
            'lis_bridge_config_id' => $config->id,
            'wire_result_id' => 54321,
            'is_qc' => false,
            'internal_table' => 'test_results',
            'internal_id' => 42,
        ]);

        $translation = LisBridgeTranslation::findByWireResult($config->id, 54321);

        $this->assertNotNull($translation);
        $this->assertEquals(42, $translation->internal_id);
    }

    public function test_find_by_wire_result_returns_null_when_not_found(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $this->assertNull(
            LisBridgeTranslation::findByWireResult($config->id, 0)
        );
    }

    public function test_patient_scope_filters_qc_false(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        LisBridgeTranslation::create([
            'lis_bridge_config_id' => $config->id,
            'wire_result_id' => 1,
            'is_qc' => false,
            'internal_table' => 'test_results',
            'internal_id' => 1,
        ]);

        LisBridgeTranslation::create([
            'lis_bridge_config_id' => $config->id,
            'wire_result_id' => 2,
            'is_qc' => true,
            'internal_table' => 'quality_control_results',
            'internal_id' => 1,
        ]);

        $patientTranslations = LisBridgeTranslation::patient()->get();

        $this->assertCount(1, $patientTranslations);
        $this->assertEquals(1, $patientTranslations->first()->wire_result_id);
    }

    public function test_qc_scope_filters_qc_true(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        LisBridgeTranslation::create([
            'lis_bridge_config_id' => $config->id,
            'wire_result_id' => 1,
            'is_qc' => false,
            'internal_table' => 'test_results',
            'internal_id' => 1,
        ]);

        LisBridgeTranslation::create([
            'lis_bridge_config_id' => $config->id,
            'wire_result_id' => 2,
            'is_qc' => true,
            'internal_table' => 'quality_control_results',
            'internal_id' => 1,
        ]);

        $qcTranslations = LisBridgeTranslation::qc()->get();

        $this->assertCount(1, $qcTranslations);
        $this->assertEquals(2, $qcTranslations->first()->wire_result_id);
    }

    // ========================================================================
    // LisIngestLog
    // ========================================================================

    public function test_log_success_creates_entry_with_204_status(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $log = LisIngestLog::logSuccess(
            $config,
            '/api/v1/patient-results',
            204,
            ['ok' => true],
            15.5
        );

        $this->assertEquals($config->id, $log->lis_bridge_config_id);
        $this->assertEquals('/api/v1/patient-results', $log->route);
        $this->assertEquals(204, $log->status_code);
        $this->assertEquals(15.5, $log->duration_ms);
    }

    public function test_log_validation_error_creates_entry(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $log = LisIngestLog::logValidationError(
            $config,
            '/api/v1/patient-results',
            422,
            ['message_type' => ['The message type field is required.']]
        );

        $this->assertEquals(422, $log->status_code);
    }

    public function test_log_auth_failure_creates_entry_without_config(): void
    {
        $log = LisIngestLog::logAuthFailure(
            '/api/v1/patient-results',
            401,
            'Invalid API key'
        );

        $this->assertEquals('/api/v1/patient-results', $log->route);
        $this->assertEquals(401, $log->status_code);
        $this->assertNull($log->lis_bridge_config_id);
    }

    public function test_log_error_creates_entry_with_exception(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $log = LisIngestLog::logError(
            $config,
            '/api/v1/patient-results',
            500,
            'Equipment not found'
        );

        $this->assertEquals(500, $log->status_code);
    }

    public function test_successful_scope_filters_2xx_status(): void
    {
        LisIngestLog::create([
            'route' => '/api/v1/patient-results',
            'status_code' => 204,
            'duration_ms' => 10,
        ]);

        LisIngestLog::create([
            'route' => '/api/v1/patient-results',
            'status_code' => 422,
            'duration_ms' => 10,
        ]);

        $successful = LisIngestLog::successful()->get();

        $this->assertCount(1, $successful);
        $this->assertEquals(204, $successful->first()->status_code);
    }

    public function test_failed_scope_filters_non_2xx_status(): void
    {
        LisIngestLog::create([
            'route' => '/api/v1/patient-results',
            'status_code' => 204,
            'duration_ms' => 10,
        ]);

        LisIngestLog::create([
            'route' => '/api/v1/patient-results',
            'status_code' => 422,
            'duration_ms' => 10,
        ]);

        $failed = LisIngestLog::failed()->get();

        $this->assertCount(1, $failed);
        $this->assertEquals(422, $failed->first()->status_code);
    }

    public function test_updated_at_is_null_for_ingest_log(): void
    {
        $config = LisBridgeConfig::create([
            'name' => 'Test',
            'lis_url' => 'http://test',
            'is_active' => true,
        ]);

        $log = LisIngestLog::logSuccess($config, '/api/test', 204, [], 0);

        $this->assertNull($log->updated_at);
    }
}
