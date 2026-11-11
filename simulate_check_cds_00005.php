<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\TestResult;
use App\Models\CdsCorrelationRule;
use App\Models\RuleDecisionAudit;

// Simulate checkCdsRules for request ID 103 (sample 000005)
$requestId = 103;
$laboratoryRequest = LaboratoryRequest::find($requestId);

if (!$laboratoryRequest) {
    die("Request not found\n");
}

echo "=== Simulating checkCdsRules for Sample ID: {$laboratoryRequest->sample_id} (Request ID: {$requestId}) ===\n\n";

// Get all test results for this request
$testResults = TestResult::where('laboratory_request_id', $requestId)
    ->whereNotNull('test_parameter_id')
    ->whereNotNull('result_value')
    ->get();

echo "Test Results count: " . $testResults->count() . "\n";

$parameterIds = $testResults->pluck('test_parameter_id')->filter()->unique()->values()->toArray();
echo "Parameter IDs: " . implode(', ', $parameterIds) . "\n\n";

// Get active CDS rules that might be triggered
$rules = CdsCorrelationRule::active()
    ->whereIn('trigger_test_id', $parameterIds)
    ->get();

echo "Matching rules count: " . $rules->count() . "\n";

// Check if reflex already created
$reflexCreatedRuleIds = RuleDecisionAudit::where('laboratory_request_id', $requestId)
    ->where('rule_type', 'CDS')
    ->where('decision', 'reflex_created')
    ->pluck('rule_id')
    ->toArray();

echo "Reflex already created for rule IDs: " . implode(', ', $reflexCreatedRuleIds) . "\n\n";

// Simulate the response structure
$triggeredRules = [];
foreach ($rules as $rule) {
    $triggeredRules[] = [
        'id' => $rule->id,
        'rule_name' => $rule->rule_name,
        'action_type' => $rule->action_type,
        'is_acknowledged' => false,
        'reflex_created' => in_array($rule->id, $reflexCreatedRuleIds),
        'trigger_test_id' => $rule->trigger_test_id,
        'reflex_test_id' => $rule->reflex_test_id,
    ];
}

echo "=== Response that would be sent to frontend ===\n";
echo json_encode([
    'triggered_rules' => $triggeredRules,
    'has_blocking_rules' => false,
    'is_acknowledged' => false
], JSON_PRETTY_PRINT);
echo "\n";

// Check if there would be a pendingReflexAlert
$pendingReflexAlert = null;
foreach ($triggeredRules as $rule) {
    if ($rule['action_type'] === 'REFLEX_TEST' &&
        !$rule['is_acknowledged'] &&
        !$rule['reflex_created']) {
        $pendingReflexAlert = $rule;
        break;
    }
}

echo "\n=== Pending Reflex Alert that frontend would detect ===\n";
if ($pendingReflexAlert) {
    echo "YES - pendingReflexAlert would be set:\n";
    echo json_encode($pendingReflexAlert, JSON_PRETTY_PRINT);
    echo "\n";
    echo "Approve button SHOULD appear in CdsAlertBanner\n";
} else {
    echo "NO - pendingReflexAlert would be null\n";
    echo "Approve button would NOT appear\n";
}
