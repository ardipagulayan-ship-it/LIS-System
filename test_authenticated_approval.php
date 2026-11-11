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
use Illuminate\Support\Facades\Auth;

// Simulate authenticated request
$requestId = 103;
$ruleId = 4;
$reflexTestId = 58;

echo "=== Testing Authenticated Reflex Approval ===\n";

// Get user and authenticate
$user = User::find(1);
if (!$user) {
    die("ERROR: Admin user not found\n");
}

// Manually set the user as authenticated (simulate Sanctum)
Auth::shouldReceive('user')->andReturn($user);
echo "Authenticated as: {$user->name} (ID: {$user->id})\n\n";

$laboratoryRequest = LaboratoryRequest::find($requestId);
$rule = CdsCorrelationRule::find($ruleId);

echo "Request: {$laboratoryRequest->sample_id}\n";
echo "Rule: {$rule->rule_name}\n\n";

$request = new Request();
$request->merge([
    'rule_id' => $ruleId,
    'reflex_test_id' => $reflexTestId,
    'auto_create' => false
]);

$controller = new ResultManagementController();
try {
    $response = $controller->approveReflexTest($request, $laboratoryRequest);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n\n";
    
    // Check results
    $reflexRequests = \App\Models\LaboratoryRequest::where('reflex_parent_request_id', $requestId)->get();
    echo "Reflex requests created: " . $reflexRequests->count() . "\n";
    
    $audits = \App\Models\RuleDecisionAudit::where('laboratory_request_id', $requestId)
        ->where('rule_id', $ruleId)->get();
    echo "Audit entries: " . $audits->count() . "\n";
    
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
