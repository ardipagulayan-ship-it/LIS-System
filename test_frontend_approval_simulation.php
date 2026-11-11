<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Models\LaboratoryRequest;
use App\Models\CdsCorrelationRule;
use App\Models\User;
use App\Http\Controllers\Api\ResultManagementController;

// Simulate the exact frontend API call for sample 00005
$requestId = 103; // This is the database ID for sample 000005
$ruleId = 4;
$reflexTestId = 58; // Ferritin parameter

echo "=== Simulating Frontend API Call ===\n";
echo "Request ID (laboratory_request.id): {$requestId}\n";
echo "Rule ID: {$ruleId}\n";
echo "Reflex Test ID (test_parameter.id): {$reflexTestId}\n\n";

// Check if the request exists
$laboratoryRequest = LaboratoryRequest::find($requestId);
if (!$laboratoryRequest) {
    die("ERROR: Laboratory Request ID {$requestId} not found\n");
}
echo "Found Laboratory Request: Sample ID {$laboratoryRequest->sample_id}, Patient: {$laboratoryRequest->patient->last_name}, {$laboratoryRequest->patient->first_name}\n\n";

// Check if the rule exists
$rule = CdsCorrelationRule::find($ruleId);
if (!$rule) {
    die("ERROR: Rule ID {$ruleId} not found\n");
}
echo "Found Rule: {$rule->rule_name}\n";
echo "Trigger Test Parameter ID: {$rule->trigger_test_id}\n";
echo "Reflex Test Parameter ID: {$rule->reflex_test_id}\n\n";

// Check if trigger result exists (the safeguard)
$triggerResultExists = \App\Models\TestResult::where('laboratory_request_id', $requestId)
    ->where('test_parameter_id', $rule->trigger_test_id)
    ->whereNotNull('result_value')
    ->exists();

echo "Safeguard Check - Trigger Result Exists: " . ($triggerResultExists ? 'YES' : 'NO') . "\n\n";

if (!$triggerResultExists) {
    echo "ERROR: The safeguard would block this approval!\n";
    exit(1);
}

// Get user
$user = User::find(1); // Admin user
if (!$user) {
    die("ERROR: User not found\n");
}

// Create a fake Request object with validated data
$request = new Request();
$request->merge([
    'rule_id' => $ruleId,
    'reflex_test_id' => $reflexTestId,
    'auto_create' => false
]);

// Call the actual controller method
$controller = new ResultManagementController();
try {
    $response = $controller->approveReflexTest($request, $laboratoryRequest);
    echo "CONTROLLER RESPONSE:\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n\n";
    
    // Check if reflex was created
    $reflexRequests = \App\Models\LaboratoryRequest::where('reflex_parent_request_id', $requestId)
        ->where('reflex_triggered_by_rule_id', $ruleId)
        ->get();
    
    echo "After approval, reflex requests found: " . $reflexRequests->count() . "\n";
    foreach ($reflexRequests as $reflex) {
        echo " - Reflex Request ID: {$reflex->id}, Sample ID: {$reflex->sample_id}\n";
    }
    
    // Check audit trail
    $audits = \App\Models\RuleDecisionAudit::where('laboratory_request_id', $requestId)
        ->where('rule_id', $ruleId)
        ->get();
    echo "\nRuleDecisionAudit entries: " . $audits->count() . "\n";
    foreach ($audits as $audit) {
        echo " - ID: {$audit->id}, Decision: {$audit->decision}, User: {$audit->user->name}\n";
    }
    
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
