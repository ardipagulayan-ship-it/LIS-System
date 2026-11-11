<?php

// Script to test the fixed approval flow
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ResultManagementController;
use App\Models\LaboratoryRequest;
use App\Models\CdsCorrelationRule;
use App\Models\User;

echo "=== TESTING FIXED APPROVAL FLOW ===\n\n";

// Get parent request (sample 000006)
$parent = LaboratoryRequest::where('sample_id', '000006')->first();
if (!$parent) {
    echo "Parent request not found\n";
    exit(1);
}
echo "Parent Request ID: {$parent->id}, Sample ID: {$parent->sample_id}\n";

// Get rule 4
$rule = CdsCorrelationRule::find(4);
echo "Rule: {$rule->rule_name} (ID: {$rule->id})\n";
echo "Reflex Test ID: {$rule->reflex_test_id}\n";

// Get user
$user = User::first();
echo "User: {$user->name} (ID: {$user->id})\n\n";

// Simulate the API call with CORRECT payload (rule_id at top level)
$request = new Request();
$request->merge([
    'rule_id' => $rule->id,  // This is what the frontend should send
    'reflex_test_id' => $rule->reflex_test_id,
    'auto_create' => false
]);

$controller = new ResultManagementController();

try {
    $response = $controller->approveReflexTest($request, $parent);
    $data = json_decode($response->getContent(), true);
    
    echo "API Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Data:\n";
    print_r($data);
    
    if ($response->getStatusCode() === 200) {
        echo "\n✓ SUCCESS! Reflex approval worked correctly.\n";
        
        // Verify the reflex request was created
        $reflexRequest = LaboratoryRequest::where('reflex_parent_request_id', $parent->id)
            ->where('reflex_triggered_by_rule_id', $rule->id)
            ->first();
        if ($reflexRequest) {
            echo "✓ Reflex request created: ID {$reflexRequest->id}, Sample ID {$reflexRequest->sample_id}\n";
        } else {
            echo "✗ ERROR: No reflex request found in database\n";
        }
        
        // Verify audit entry
        $audit = \App\Models\RuleDecisionAudit::where('laboratory_request_id', $parent->id)
            ->where('rule_id', $rule->id)
            ->where('decision', 'reflex_created')
            ->first();
        if ($audit) {
            echo "✓ Audit entry created: ID {$audit->id}\n";
        } else {
            echo "✗ ERROR: No audit entry found\n";
        }
    } else {
        echo "\n✗ FAILED: API returned error\n";
    }
    
} catch (\Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
