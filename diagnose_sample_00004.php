<?php

// Script to diagnose reflex test issue with sample_id 00004
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\LaboratoryRequestTest;
use App\Models\TestResult;
use App\Models\CdsCorrelationRule;
use App\Models\RuleDecisionAudit;
use App\Models\Test;
use App\Models\TestParameter;

echo "=== DETAILED ANALYSIS FOR SAMPLE 000004 ===\n\n";

// Get the laboratory request
$request = LaboratoryRequest::where('sample_id', '000004')->first();

if (!$request) {
    echo "No laboratory request found with sample_id 000004\n";
    exit(1);
}

echo "--- Laboratory Request Details ---\n";
echo "ID: {$request->id}\n";
echo "Sample ID: {$request->sample_id}\n";
echo "Patient ID: {$request->patient_id}\n";
echo "Patient Name: " . ($request->patient ? $request->patient->last_name . ', ' . $request->patient->first_name : 'N/A') . "\n";
echo "Doctor ID: {$request->doctor_id}\n";
echo "Is Reflex: " . ($request->is_reflex ? 'true' : 'false') . "\n";
echo "Reflex Parent Request ID: " . ($request->reflex_parent_request_id ?? 'null') . "\n";
echo "Reflex Triggered By Rule ID: " . ($request->reflex_triggered_by_rule_id ?? 'null') . "\n";
echo "Priority Level: {$request->priority_level}\n";
echo "Created At: {$request->created_at}\n";
echo "\n";

// Get associated tests
echo "--- Laboratory Request Tests ---\n";
$tests = LaboratoryRequestTest::where('laboratory_request_id', $request->id)->get();
foreach ($tests as $test) {
    echo "Test ID: {$test->id}, Test: " . ($test->test->name ?? 'N/A') . ", Status: {$test->item_status}, Is Reflex: " . ($test->is_reflex_test ? 'true' : 'false') . ", Reflex Triggered By Rule: " . ($test->reflex_triggered_by_rule_id ?? 'null') . "\n";
}
echo "\n";

// Get test results
echo "--- Test Results ---\n";
// TestResult model uses laboratory_request_id and test_id, not laboratory_request_test_id
$results = TestResult::where('laboratory_request_id', $request->id)->get();
foreach ($results as $result) {
    echo "Test: " . ($result->test->name ?? 'N/A') . ", Parameter: " . ($result->testParameter->name ?? 'N/A') . ", Value: {$result->result_value}, Unit: {$result->unit}\n";
}
echo "\n";

// Check for CDS rules that might have been triggered
echo "--- CDS Rules Check ---\n";
$testParamIds = $tests->flatMap(function($test) {
    return $test->test->testParameters->pluck('id');
})->unique()->toArray();

echo "Test Parameter IDs in this request: " . implode(', ', $testParamIds) . "\n";

// Check for active CDS rules that match these parameters
$rules = CdsCorrelationRule::whereIn('trigger_test_id', $testParamIds)
            ->where('is_active', true)
            ->get();
echo "Matching Active CDS Rules: " . $rules->count() . "\n";
foreach ($rules as $rule) {
    echo "  Rule ID: {$rule->id}, Name: {$rule->rule_name}, Trigger Test: " . ($rule->triggerTest->name ?? 'N/A') . ", Reflex Test: " . ($rule->reflexTest->name ?? 'N/A') . ", Alert Message: {$rule->alert_message}\n";
}

// Check RuleDecisionAudit for this request
echo "\n--- Rule Decision Audit ---\n";
$audits = RuleDecisionAudit::where('laboratory_request_id', $request->id)->get();
foreach ($audits as $audit) {
    echo "Audit ID: {$audit->id}, Rule Type: {$audit->rule_type}, Decision: {$audit->decision}, Rule ID: {$audit->rule_id}, User: " . ($audit->user->name ?? 'N/A') . "\n";
}

echo "\n=== END OF ANALYSIS ===\n";
