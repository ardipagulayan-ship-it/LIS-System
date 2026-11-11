<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\CdsCorrelationRule;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ResultManagementController;
use Illuminate\Support\Facades\Auth;

// Simulate the exact frontend call for sample 000005
$requestId = 103;  // Parent request ID
$ruleId = 4;
$reflexTestId = 58;

echo "=== Simulating Frontend API Call ===\n";
echo "Request ID: $requestId (sample_id: 000005)\n";
echo "Rule ID: $ruleId\n";
echo "Reflex Test ID: $reflexTestId\n\n";

// Get user
$user = User::find(1);
if (!$user) {
    die('User not found');
}
Auth::shouldReceive('user')->andReturn($user);

$laboratoryRequest = LaboratoryRequest::find($requestId);
if (!$laboratoryRequest) {
    die('Request not found');
}

echo "Request found: ID {$laboratoryRequest->id}, Sample {$laboratoryRequest->sample_id}\n\n";

$request = new Request();
$request->merge([
    'rule_id' => $ruleId,
    'reflex_test_id' => $reflexTestId,
    'auto_create' => false
]);

$controller = new ResultManagementController();
try {
    $response = $controller->approveReflexTest($request, $laboratoryRequest);
    echo 'Response Status: ' . $response->getStatusCode() . "\n";
    echo 'Response Content: ' . $response->getContent() . "\n";
} catch (Exception $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . "\n";
    echo 'Trace: ' . $e->getTraceAsString() . "\n";
}
