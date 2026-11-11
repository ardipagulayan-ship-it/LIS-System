<?php

// Script to diagnose reflex test issue with sample_id 000006
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

echo "=== DETAILED ANALYSIS FOR SAMPLE 000006 ===\n\n";

// Get the laboratory request
$request = LaboratoryRequest::where('sample_id', '000006')->first();

if (!$request) {
    echo "No laboratory request found with sample_id 000006\n";
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
$results = TestResult::where('laboratory_request_id', $request->id)->get();
foreach ($results as $result) {
    echo "Test: " . ($result->test->name ?? 'N/A') . ", Parameter: " . ($result->testParameter->name ?? 'N/A') . ", Value: {$result->result_value}, Unit: {$result->unit}, Parameter ID: " . ($result->test_parameter_id ?? 'null') . "\n";
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

// Simulate the backend's trigger result check for each rule
echo "\n--- Trigger Result Existence Check (Simulating approveReflexTest) ---\n";
foreach ($rules as $rule) {
    $triggerResultExists = TestResult::where('laboratory_request_id', $request->id)
        ->where('test_parameter_id', $rule->trigger_test_id)
        ->whereNotNull('result_value')
        ->exists();

    echo "Rule ID: {$rule->id} ({$rule->rule_name})\n";
    echo "  Trigger Test Parameter ID: {$rule->trigger_test_id}\n";
    echo "  Trigger Result Exists: " . ($triggerResultExists ? 'YES' : 'NO') . "\n";
    if (!$triggerResultExists) {
        $anyResult = TestResult::where('laboratory_request_id', $request->id)
            ->where('test_parameter_id', $rule->trigger_test_id)
            ->first();
        echo "  Any result record found: " . ($anyResult ? 'YES' : 'NO') . "\n";
        if ($anyResult) {
            echo "  Result value: " . ($anyResult->result_value ?? 'NULL') . "\n";
        }
    }
    echo "\n";
}

// Simulate the checkCdsRules endpoint response
echo "\n--- Simulated checkCdsRules API Response ---\n";
$engine = new \App\Services\CdsRuleEngine();
$reflexCreatedRuleIds = RuleDecisionAudit::where('laboratory_request_id', $request->id)
    ->where('rule_type', 'CDS')
    ->where('decision', 'reflex_created')
    ->pluck('rule_id')
    ->toArray();

echo "Reflex Created Rule IDs: " . implode(', ', $reflexCreatedRuleIds) . "\n";

$isAcknowledged = RuleDecisionAudit::where('laboratory_request_id', $request->id)
    ->where('rule_type', 'CDS')
    ->where('decision', 'acknowledged')
    ->exists();
echo "CDS Acknowledged: " . ($isAcknowledged ? 'YES' : 'NO') . "\n";

$triggeredRules = [];
foreach ($rules as $rule) {
    $correlated = collect(explode(',', (string)$rule->correlated_tests))
        ->filter()
        ->map(fn($v) => (int)trim($v))
        ->values()
        ->toArray();

    $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $testParamIds)));
    $fires = $engine->evaluate((array)$rule->rule_logic_json, $request, $needed);

    if ($fires) {
        $isBlocking = in_array($rule->action_type, ['STOP_AUTO_VERIFY', 'REFLEX_TEST']);
        $hasReflexCreated = in_array($rule->id, $reflexCreatedRuleIds);
        $isRuleAcknowledged = $isAcknowledged || $hasReflexCreated;

        $triggeredRules[] = [
            'id' => $rule->id,
            'rule_name' => $rule->rule_name,
            'action_type' => $rule->action_type,
            'is_blocking' => $isBlocking,
            'is_acknowledged' => $isRuleAcknowledged,
            'reflex_created' => $hasReflexCreated,
            'trigger_test_id' => $rule->trigger_test_id,
            'reflex_test_id' => $rule->reflex_test_id,
        ];
    }
}

echo "\nTriggered Rules Count: " . count($triggeredRules) . "\n";
foreach ($triggeredRules as $tr) {
    echo "  Rule ID: {$tr['id']}, Name: {$tr['rule_name']}, Action: {$tr['action_type']}, Blocking: " . ($tr['is_blocking'] ? 'YES' : 'NO') . ", Acknowledged: " . ($tr['is_acknowledged'] ? 'YES' : 'NO') . ", Reflex Created: " . ($tr['reflex_created'] ? 'YES' : 'NO') . "\n";
}

echo "\n=== END OF ANALYSIS ===\n";
