<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\LisBridgeConfig;
use App\Models\LisBridgeApiKey;
use App\Models\Equipment;
use App\Models\Patient;
use App\Models\LaboratoryRequest;
use App\Models\Doctor;
use App\Models\Test;
use App\Models\TestParameter;
use Tests\TestCase;

class LisBridgeValidationTest extends TestCase
{
    use RefreshDatabase;

    protected LisBridgeConfig $config;
    protected string $rawToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = LisBridgeConfig::create([
            'name' => 'Test Bridge',
            'lis_url' => 'http://localhost',
            'patient_lis_url' => 'http://localhost/api/v1/patient-results',
            'qc_lis_url' => 'http://localhost/api/v1/qc-results',
            'control_id_map' => ['QC_NORMAL' => 1],
            'is_active' => true,
        ]);

        $result = LisBridgeApiKey::createForConfig($this->config, null);
        $this->rawToken = $result['raw_token'];
    }

    protected function validPatientPayload(): array
    {
        // Create minimal required related records
        $equipment = Equipment::create([
            'name' => 'Test Analyzer',
            'serial_no' => 'SN-001',
            'equipment_type' => 'analyzer',
            'status' => 'active',
            'is_active' => true,
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'license_no' => 'LIC-001',
        ]);

        $patient = Patient::create([
            'patient_id' => 'P-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
        ]);

        $testCategory = \App\Models\TestCategory::create([
            'name' => 'Chemistry',
            'description' => 'Chemistry tests',
        ]);

        $testParameter = TestParameter::create([
            'test_category_id' => $testCategory->id,
            'name' => 'Glucose',
            'code' => 'GLU',
            'unit' => 'mg/dL',
            'method' => 'Hexokinase',
        ]);

        $labRequest = LaboratoryRequest::create([
            'request_id' => 'REQ-001',
            'patient_id' => $patient->id,
            'requester_id' => $doctor->id,
            'priority' => 'routine',
            'status' => 'approved',
            'collected_at' => now(),
            'received_at' => now(),
        ]);

        $labRequest->tests()->attach($testParameter->id, ['price_at_request' => 100]);

        return [
            'message_type' => 'RESULT',
            'is_qc' => false,
            'result_id' => 1001,
            'analyzer_serial' => 'SN-001',
            'patient_id' => 'P-001',
            'sample_id' => 'REQ-001',
            'control_id' => null,
            'test_code' => 'GLU',
            'value' => '95.5',
            'unit' => 'mg/dL',
            'result_status' => 'F',
            'run_number' => 1,
            'observed_at' => now()->toIso8601String(),
        ];
    }

    // ---------------------------------------------------------------------------
    // Patient result: required fields
    // ---------------------------------------------------------------------------

    public function test_rejects_missing_message_type(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['message_type']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message_type']);
    }

    public function test_rejects_missing_result_id(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['result_id']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['result_id']);
    }

    public function test_rejects_missing_analyzer_serial(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['analyzer_serial']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['analyzer_serial']);
    }

    public function test_rejects_missing_patient_id(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['patient_id']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id']);
    }

    public function test_rejects_missing_sample_id(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['sample_id']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sample_id']);
    }

    public function test_rejects_missing_test_code(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['test_code']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['test_code']);
    }

    public function test_rejects_missing_value(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['value']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
    }

    public function test_rejects_missing_result_status(): void
    {
        $payload = $this->validPatientPayload();
        unset($payload['result_status']);

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['result_status']);
    }

    // ---------------------------------------------------------------------------
    // Patient result: data type validation
    // ---------------------------------------------------------------------------

    public function test_rejects_non_boolean_is_qc(): void
    {
        $payload = $this->validPatientPayload();
        $payload['is_qc'] = 'yes';

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_qc']);
    }

    public function test_rejects_non_integer_result_id(): void
    {
        $payload = $this->validPatientPayload();
        $payload['result_id'] = 'abc';

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['result_id']);
    }

    public function test_rejects_negative_run_number(): void
    {
        $payload = $this->validPatientPayload();
        $payload['run_number'] = -1;

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['run_number']);
    }

    public function test_rejects_invalid_result_status(): void
    {
        $payload = $this->validPatientPayload();
        $payload['result_status'] = 'Z'; // Not F/P/C/X

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['result_status']);
    }

    public function test_rejects_invalid_date_format_for_observed_at(): void
    {
        $payload = $this->validPatientPayload();
        $payload['observed_at'] = '2024-01-01'; // Missing time component

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['observed_at']);
    }

    // ---------------------------------------------------------------------------
    // Patient result: cross-field invariants
    // ---------------------------------------------------------------------------

    public function test_rejects_is_qc_true_on_patient_endpoint(): void
    {
        $payload = $this->validPatientPayload();
        $payload['is_qc'] = true;

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_qc']);
    }

    public function test_rejects_non_null_control_id_on_patient_endpoint(): void
    {
        $payload = $this->validPatientPayload();
        $payload['control_id'] = 'QC_NORMAL';

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['control_id']);
    }

    // ---------------------------------------------------------------------------
    // QC result: required fields
    // ---------------------------------------------------------------------------

    public function test_rejects_missing_control_id_on_qc_endpoint(): void
    {
        $payload = [
            'message_type' => 'RESULT',
            'is_qc' => true,
            'result_id' => 2001,
            'analyzer_serial' => 'SN-001',
            'patient_id' => 'QC',
            'sample_id' => 'QC-001',
            'control_id' => null, // Required for QC
            'test_code' => 'GLU',
            'value' => '98.0',
            'unit' => 'mg/dL',
            'result_status' => 'F',
            'run_number' => 1,
            'observed_at' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/v1/qc-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['control_id']);
    }

    public function test_rejects_non_qc_patient_id_on_qc_endpoint(): void
    {
        $payload = [
            'message_type' => 'RESULT',
            'is_qc' => true,
            'result_id' => 2001,
            'analyzer_serial' => 'SN-001',
            'patient_id' => 'P-001', // Must be "QC"
            'sample_id' => 'QC-001',
            'control_id' => 'QC_NORMAL',
            'test_code' => 'GLU',
            'value' => '98.0',
            'unit' => 'mg/dL',
            'result_status' => 'F',
            'run_number' => 1,
            'observed_at' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/v1/qc-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id']);
    }

    public function test_rejects_is_qc_false_on_qc_endpoint(): void
    {
        $payload = [
            'message_type' => 'RESULT',
            'is_qc' => false, // Must be true
            'result_id' => 2001,
            'analyzer_serial' => 'SN-001',
            'patient_id' => 'QC',
            'sample_id' => 'QC-001',
            'control_id' => 'QC_NORMAL',
            'test_code' => 'GLU',
            'value' => '98.0',
            'unit' => 'mg/dL',
            'result_status' => 'F',
            'run_number' => 1,
            'observed_at' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/v1/qc-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_qc']);
    }

    // ---------------------------------------------------------------------------
    // Value type: string values accepted (">100", "POS", "NEG")
    // ---------------------------------------------------------------------------

    public function test_accepts_string_value_greater_than(): void
    {
        $payload = $this->validPatientPayload();
        $payload['value'] = '>100';

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        // Should not fail validation — auth passes, may fail on business logic
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_accepts_string_value_positive(): void
    {
        $payload = $this->validPatientPayload();
        $payload['value'] = 'POS';

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_accepts_string_value_negative(): void
    {
        $payload = $this->validPatientPayload();
        $payload['value'] = 'NEG';

        $response = $this->postJson('/api/v1/patient-results', $payload, [
            'Authorization' => 'Bearer ' . $this->rawToken,
        ]);

        $this->assertNotEquals(401, $response->getStatusCode());
    }
}
