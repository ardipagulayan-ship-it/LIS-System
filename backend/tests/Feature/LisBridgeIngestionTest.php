<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\LisBridgeConfig;
use App\Models\LisBridgeApiKey;
use App\Models\LisBridgeTranslation;
use App\Models\Equipment;
use App\Models\Patient;
use App\Models\LaboratoryRequest;
use App\Models\Doctor;
use App\Models\Test;
use App\Models\TestCategory;
use App\Models\TestParameter;
use App\Models\TestResult;
use App\Models\QualityControl;
use App\Models\QualityControlResult;
use App\Jobs\ProcessLisPatientResult;
use App\Jobs\ProcessLisQcResult;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LisBridgeIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected LisBridgeConfig $config;
    protected string $rawToken;
    protected Equipment $equipment;
    protected Patient $patient;
    protected Doctor $doctor;
    protected TestCategory $testCategory;
    protected TestParameter $testParameter;
    protected LaboratoryRequest $labRequest;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        // Create bridge config
        $this->config = LisBridgeConfig::create([
            'name' => 'Test Bridge',
            'lis_url' => 'http://localhost',
            'patient_lis_url' => 'http://localhost/api/v1/patient-results',
            'qc_lis_url' => 'http://localhost/api/v1/qc-results',
            'control_id_map' => ['QC_NORMAL' => 1, 'QC_ABNORMAL' => 2],
            'is_active' => true,
        ]);

        $result = LisBridgeApiKey::createForConfig($this->config, null);
        $this->rawToken = $result['raw_token'];

        // Create related records
        $this->equipment = Equipment::create([
            'name' => 'Test Analyzer',
            'serial_no' => 'SN-ANALYZER-001',
            'equipment_type' => 'analyzer',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name' => 'Dr. Smith',
            'license_no' => 'LIC-12345',
        ]);

        $this->patient = Patient::create([
            'patient_id' => 'PAT-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'date_of_birth' => '1985-06-15',
            'gender' => 'female',
        ]);

        $this->testCategory = TestCategory::create([
            'name' => 'Clinical Chemistry',
            'description' => 'Chemistry panel',
        ]);

        $this->testParameter = TestParameter::create([
            'test_category_id' => $this->testCategory->id,
            'name' => 'Glucose',
            'code' => 'GLU',
            'unit' => 'mg/dL',
            'method' => 'Hexokinase',
        ]);

        $this->labRequest = LaboratoryRequest::create([
            'request_id' => 'LAB-REQ-001',
            'patient_id' => $this->patient->id,
            'requester_id' => $this->doctor->id,
            'priority' => 'routine',
            'status' => 'approved',
            'collected_at' => now()->subHour(),
            'received_at' => now(),
        ]);

        $this->labRequest->tests()->attach($this->testParameter->id, ['price_at_request' => 150]);

        // Create QC material
        QualityControl::create([
            'name' => 'QC Normal',
            'lot_no' => 'LOT-NORMAL-001',
            'expiry_date' => now()->addYear(),
            'level' => 'normal',
            'is_active' => true,
        ]);

        QualityControl::create([
            'name' => 'QC Abnormal',
            'lot_no' => 'LOT-ABNORMAL-001',
            'expiry_date' => now()->addYear(),
            'level' => 'abnormal',
            'is_active' => true,
        ]);
    }

    // ---------------------------------------------------------------------------
    // Happy path: patient result ingestion
    // ---------------------------------------------------------------------------

    public function test_ingests_valid_patient_result_and_returns_204(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 999001,
            'sample_id' => 'LAB-REQ-001',
            'test_code' => 'GLU',
            'value' => '105.5',
            'result_status' => 'F',
            'run_number' => 1,
        ]);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(204);

        // Verify TestResult was created
        $this->assertDatabaseHas('test_results', [
            'value' => '105.5',
            'unit' => 'mg/dL',
        ]);

        // Verify translation row was created
        $this->assertDatabaseHas('lis_bridge_translation', [
            'lis_bridge_config_id' => $this->config->id,
            'wire_result_id' => 999001,
            'is_qc' => false,
            'internal_table' => 'test_results',
        ]);
    }

    public function test_ingestion_returns_result_id_in_response(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 999002,
        ]);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(204);

        // The response body should be JSON with ok=true and result_id
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['ok'] ?? false);
        $this->assertArrayHasKey('result_id', $data);
    }

    public function test_dispatches_process_lis_patient_result_job_on_new_ingestion(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 999003,
        ]);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(204);

        Queue::assertPushed(ProcessLisPatientResult::class, function ($job) {
            return $job->testResultId > 0;
        });
    }

    public function test_does_not_dispatch_job_on_duplicate_ingestion(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 999004,
        ]);

        // First request — new
        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        // Second request — duplicate (same result_id for same bridge)
        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        // Job should only be dispatched once
        Queue::assertPushed(ProcessLisPatientResult::class, 1);
    }

    // ---------------------------------------------------------------------------
    // Happy path: QC result ingestion
    // ---------------------------------------------------------------------------

    public function test_ingests_valid_qc_result_and_returns_204(): void
    {
        $payload = $this->buildQcPayload([
            'result_id' => 888001,
            'control_id' => 'QC_NORMAL',
            'value' => '100.0',
        ]);

        $response = $this->postJson('/api/v1/qc-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(204);

        // Verify QC result was created
        $this->assertDatabaseHas('quality_control_results', [
            'result_value' => '100.0',
        ]);

        // Verify translation row
        $this->assertDatabaseHas('lis_bridge_translation', [
            'wire_result_id' => 888001,
            'is_qc' => true,
            'internal_table' => 'quality_control_results',
        ]);
    }

    public function test_dispatches_process_lis_qc_result_job_on_new_qc_ingestion(): void
    {
        $payload = $this->buildQcPayload([
            'result_id' => 888002,
        ]);

        $this->postJson('/api/v1/qc-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        Queue::assertPushed(ProcessLisQcResult::class);
    }

    // ---------------------------------------------------------------------------
    // Dedup: same bridge + same wire_result_id = duplicate
    // ---------------------------------------------------------------------------

    public function test_rejects_duplicate_result_id_for_same_bridge(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 777001,
        ]);

        // First request
        $r1 = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);
        $r1->assertStatus(204);

        // Second request with same result_id
        $r2 = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);
        $r2->assertStatus(409); // Conflict

        // Only ONE translation row should exist
        $count = LisBridgeTranslation::where('lis_bridge_config_id', $this->config->id)
            ->where('wire_result_id', 777001)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_same_result_id_different_bridge_is_not_duplicate(): void
    {
        // Create a second bridge config
        $config2 = LisBridgeConfig::create([
            'name' => 'Bridge 2',
            'lis_url' => 'http://bridge2',
            'patient_lis_url' => 'http://bridge2/api/v1/patient-results',
            'qc_lis_url' => 'http://bridge2/api/v1/qc-results',
            'control_id_map' => [],
            'is_active' => true,
        ]);

        $result2 = LisBridgeApiKey::createForConfig($config2, null);
        $token2 = $result2['raw_token'];

        $payload = $this->buildPatientPayload([
            'result_id' => 666001,
        ]);

        // Ingest via bridge 1
        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        // Same result_id via bridge 2 — should succeed (not a duplicate)
        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $token2,
        ])->assertStatus(204);

        // Two translation rows should exist (one per bridge)
        $count = LisBridgeTranslation::where('wire_result_id', 666001)->count();
        $this->assertEquals(2, $count);
    }

    // ---------------------------------------------------------------------------
    // Re-run supersession (run_number > 1)
    // ---------------------------------------------------------------------------

    public function test_rerun_supersedes_previous_result(): void
    {
        // First run
        $payload1 = $this->buildPatientPayload([
            'result_id' => 555001,
            'run_number' => 1,
            'value' => '90.0',
        ]);

        $this->postJson('/api/v1/patient-results', $payload1, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        // Second run (rerun)
        $payload2 = $this->buildPatientPayload([
            'result_id' => 555002,
            'run_number' => 2,
            'value' => '92.0',
        ]);

        $response = $this->postJson('/api/v1/patient-results', $payload2, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(204);

        // Original result should be superseded
        $original = TestResult::where('value', '90.0')->first();
        $this->assertEquals('superseded', $original->status);

        // New result should be final
        $new = TestResult::where('value', '92.0')->first();
        $this->assertEquals('final', $new->status);

        // Translation chain should link run 2 to run 1
        $translation = LisBridgeTranslation::where('wire_result_id', 555002)->first();
        $this->assertEquals(555001, $translation->parent_wire_result_id);
    }

    // ---------------------------------------------------------------------------
    // Status translation: wire format → internal enum
    // ---------------------------------------------------------------------------

    public function test_translates_final_status(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 444001,
            'result_status' => 'F',
        ]);

        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        $result = TestResult::latest('id')->first();
        $this->assertEquals('final', $result->status);
    }

    public function test_translates_pending_status(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 444002,
            'result_status' => 'P',
        ]);

        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        $result = TestResult::latest('id')->first();
        $this->assertEquals('pending', $result->status);
    }

    // ---------------------------------------------------------------------------
    // observed_at stored only on translation table
    // ---------------------------------------------------------------------------

    public function test_observed_at_is_not_on_test_results_table(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 333001,
            'observed_at' => '2024-06-15T10:30:00+08:00',
        ]);

        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        $result = TestResult::latest('id')->first();

        // observed_at should NOT be a column on test_results
        $this->assertArrayNotHasKey('observed_at', $result->getAttributes());

        // observed_at should be on lis_bridge_translation
        $translation = LisBridgeTranslation::where('wire_result_id', 333001)->first();
        $this->assertEquals('2024-06-15T10:30:00+08:00', $translation->wire_observed_at);
    }

    // ---------------------------------------------------------------------------
    // Ingest log: successful request logged
    // ---------------------------------------------------------------------------

    public function test_successful_ingestion_is_logged(): void
    {
        $payload = $this->buildPatientPayload(['result_id' => 222001]);

        $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ])->assertStatus(204);

        $this->assertDatabaseHas('lis_ingest_logs', [
            'lis_bridge_config_id' => $this->config->id,
            'route' => '/api/v1/patient-results',
            'status_code' => 204,
        ]);
    }

    // ---------------------------------------------------------------------------
    // Unknown equipment returns 422
    // ---------------------------------------------------------------------------

    public function test_returns_422_when_equipment_not_found_by_serial(): void
    {
        $payload = $this->buildPatientPayload([
            'result_id' => 111001,
            'analyzer_serial' => 'UNKNOWN-SERIAL-999',
        ]);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422);
    }

    // ---------------------------------------------------------------------------
    // 10-second timeout: response must be fast (synchronous portion)
    // ---------------------------------------------------------------------------

    public function test_returns_204_before_queue_processing(): void
    {
        $payload = $this->buildPatientPayload(['result_id' => 111002]);

        // Time the request — should return in well under 10 seconds
        $start = microtime(true);
        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);
        $duration = microtime(true) - $start;

        $response->assertStatus(204);
        $this->assertLessThan(5, $duration, 'Request took too long — may breach 10s forwarder timeout');

        // Queue job should be dispatched AFTER response sent
        Queue::assertNotPushed(ProcessLisPatientResult::class);
        // (It IS pushed, but to the queue — fake() above catches it)
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    protected function buildPatientPayload(array $overrides = []): array
    {
        return array_merge([
            'message_type' => 'RESULT',
            'is_qc' => false,
            'result_id' => 100000 + random_int(0, 999999),
            'analyzer_serial' => 'SN-ANALYZER-001',
            'patient_id' => 'PAT-001',
            'sample_id' => 'LAB-REQ-001',
            'control_id' => null,
            'test_code' => 'GLU',
            'value' => '95.5',
            'unit' => 'mg/dL',
            'result_status' => 'F',
            'run_number' => 1,
            'observed_at' => now()->toIso8601String(),
        ], $overrides);
    }

    protected function buildQcPayload(array $overrides = []): array
    {
        return array_merge([
            'message_type' => 'RESULT',
            'is_qc' => true,
            'result_id' => 200000 + random_int(0, 999999),
            'analyzer_serial' => 'SN-ANALYZER-001',
            'patient_id' => 'QC',
            'sample_id' => 'QC-SAMPLE-001',
            'control_id' => 'QC_NORMAL',
            'test_code' => 'GLU',
            'value' => '100.0',
            'unit' => 'mg/dL',
            'result_status' => 'F',
            'run_number' => 1,
            'observed_at' => now()->toIso8601String(),
        ], $overrides);
    }
}
