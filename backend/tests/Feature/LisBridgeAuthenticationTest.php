<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\LisBridgeConfig;
use App\Models\LisBridgeApiKey;
use App\Models\Equipment;
use App\Models\Patient;
use App\Models\LaboratoryRequest;
use App\Models\TestResult;
use App\Models\QualityControl;
use App\Models\QualityControlResult;
use App\Models\TestParameter;
use App\Models\Test;
use App\Models\Doctor;
use App\Jobs\ProcessLisPatientResult;
use App\Jobs\ProcessLisQcResult;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LisBridgeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected LisBridgeConfig $config;
    protected LisBridgeApiKey $apiKey;
    protected string $rawToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a bridge config and API key for each test
        $this->config = LisBridgeConfig::create([
            'name' => 'Test Bridge',
            'lis_url' => 'http://localhost',
            'patient_lis_url' => 'http://localhost/api/v1/patient-results',
            'qc_lis_url' => 'http://localhost/api/v1/qc-results',
            'control_id_map' => ['QC_NORMAL' => 1, 'QC_ABNORMAL' => 2],
            'is_active' => true,
        ]);

        $result = LisBridgeApiKey::createForConfig($this->config, null);
        $this->apiKey = $result['model'];
        $this->rawToken = $result['raw_token'];
    }

    // ---------------------------------------------------------------------------
    // Authentication: Missing / Invalid / Expired tokens
    // ---------------------------------------------------------------------------

    public function test_rejects_request_without_authorization_header(): void
    {
        $response = $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    }

    public function test_rejects_request_with_malformed_bearer_token(): void
    {
        $response = $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ], [
            'Authorization' => 'Bearer not-a-valid-token-format',
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_request_with_wrong_token(): void
    {
        $response = $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ], [
            'Authorization' => 'Bearer ' . bin2hex(random_bytes(32)),
        ]);

        $response->assertStatus(401);
    }

    public function test_accepts_valid_bearer_token(): void
    {
        // Use a properly structured payload that will fail validation
        // (missing required fields), but NOT auth — proving auth passed.
        $response = $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ], [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        // We expect 422 (validation failure), not 401 (auth failure)
        // This proves the token was accepted.
        $response->assertStatus(422);
    }

    public function test_rejects_expired_api_key(): void
    {
        // Expire the key
        $this->apiKey->expires_at = now()->subDay();
        $this->apiKey->save();

        $response = $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ], [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_inactive_bridge_config(): void
    {
        $this->config->is_active = false;
        $this->config->save();

        $response = $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ], [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_revoked_api_key(): void
    {
        $this->apiKey->revoked_at = now();
        $this->apiKey->save();

        $response = $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ], [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(401);
    }

    // ---------------------------------------------------------------------------
    // Reachability probe
    // ---------------------------------------------------------------------------

    public function test_test_connection_endpoint_returns_bridge_info(): void
    {
        $response = $this->postJson('/api/lis-bridge/test-connection', [], [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'bridge_name' => 'Test Bridge',
            'bridge_id' => $this->config->id,
        ]);
    }

    public function test_test_connection_requires_auth(): void
    {
        $response = $this->postJson('/api/lis-bridge/test-connection', []);

        $response->assertStatus(401);
    }

    // ---------------------------------------------------------------------------
    // API Key model: verifyToken and createForConfig
    // ---------------------------------------------------------------------------

    public function test_api_key_verify_token_returns_true_for_valid_token(): void
    {
        $this->assertTrue($this->apiKey->verifyToken($this->rawToken));
    }

    public function test_api_key_verify_token_returns_false_for_wrong_token(): void
    {
        $this->assertFalse($this->apiKey->verifyToken('wrong-token'));
    }

    public function test_api_key_create_for_config_hashes_the_raw_token(): void
    {
        $result = LisBridgeApiKey::createForConfig($this->config, null);

        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('raw_token', $result);

        $rawToken = $result['raw_token'];
        $savedHash = $result['model']->key_hash;

        $this->assertEquals(hash('sha256', $rawToken), $savedHash);
    }

    public function test_api_key_is_valid_scope_excludes_expired_keys(): void
    {
        $expiredKey = LisBridgeApiKey::create([
            'lis_bridge_config_id' => $this->config->id,
            'key_hash' => hash('sha256', 'expired-key'),
            'expires_at' => now()->subDay(),
        ]);

        $validKeys = LisBridgeApiKey::valid()->get();

        $this->assertFalse($validKeys->contains($expiredKey->id));
        $this->assertTrue($validKeys->contains($this->apiKey->id));
    }

    // ---------------------------------------------------------------------------
    // last_used_at is updated on successful auth
    // ---------------------------------------------------------------------------

    public function test_last_used_at_is_updated_on_successful_request(): void
    {
        $this->assertNull($this->apiKey->last_used_at);

        $this->postJson('/api/v1/patient-results', [
            'message_type' => 'RESULT',
            'is_qc' => false,
        ], [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $this->apiKey->refresh();
        $this->assertNotNull($this->apiKey->last_used_at);
    }
}
