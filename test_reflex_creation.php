<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\CdsCorrelationRule;
use App\Models\User;
use App\Services\ReflexTestService;

$requestId = 103;
$ruleId = 4;
$userId = 1; // Admin

$request = LaboratoryRequest::find($requestId);
$rule = CdsCorrelationRule::find($ruleId);
$user = User::find($userId);

if (!$request || !$rule || !$user) {
    die("Missing data\n");
}

echo "=== Testing Reflex Test Creation ===\n";
echo "Parent Request ID: {$request->id}, Sample ID: {$request->sample_id}\n";
echo "Rule ID: {$rule->id}, Rule Name: {$rule->rule_name}\n";
echo "User ID: {$user->id}, Name: {$user->name}\n\n";

$service = new ReflexTestService();

try {
    $result = $service->createReflexTests($request, $rule, $user, false);
    echo "SUCCESS! Created " . count($result) . " reflex request(s):\n";
    foreach ($result as $reflexReq) {
        echo " - Reflex Request ID: {$reflexReq->id}, Sample ID: {$reflexReq->sample_id}\n";
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n\n";
    echo "FULL TRACE:\n" . $e->getTraceAsString() . "\n";
}
