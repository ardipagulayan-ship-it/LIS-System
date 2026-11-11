<?php

// Script to simulate reflex approval for sample 000006
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\CdsCorrelationRule;
use App\Models\User;
use App\Services\ReflexTestService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

try {
    // Get parent request
    $parent = LaboratoryRequest::where('sample_id', '000006')->first();
    if (!$parent) {
        echo "Parent request not found\n";
        exit(1);
    }
    echo "Parent Request ID: {$parent->id}, Sample ID: {$parent->sample_id}\n";
    
    // Get rule 4
    $rule = CdsCorrelationRule::find(4);
    if (!$rule) {
        echo "Rule 4 not found\n";
        exit(1);
    }
    echo "Rule: {$rule->rule_name}, Trigger Test ID: {$rule->trigger_test_id}, Reflex Test ID: {$rule->reflex_test_id}\n";
    
    // Get any user
    $user = User::first();
    if (!$user) {
        echo "No user found\n";
        exit(1);
    }
    echo "User: {$user->name} (ID: {$user->id})\n";
    
    // Check trigger result exists
    $triggerResultExists = \App\Models\TestResult::where('laboratory_request_id', $parent->id)
        ->where('test_parameter_id', $rule->trigger_test_id)
        ->whereNotNull('result_value')
        ->exists();
    echo "Trigger result exists: " . ($triggerResultExists ? 'YES' : 'NO') . "\n";
    
    if (!$triggerResultExists) {
        echo "ERROR: Trigger result not found - approval would be blocked\n";
        exit(1);
    }
    
    // Check for existing reflex
    $service = new ReflexTestService();
    $hasExisting = $service->hasExistingReflex($parent, $rule);
    echo "Has existing reflex: " . ($hasExisting ? 'YES' : 'NO') . "\n";
    
    if ($hasExisting) {
        echo "ERROR: Reflex already exists - would return empty array\n";
        exit(1);
    }
    
    // Try to create reflex tests
    echo "\nAttempting to create reflex tests...\n";
    
    $createdRequests = $service->createReflexTests(
        $parent,
        $rule,
        $user,
        false // auto_create
    );
    
    echo "SUCCESS! Created " . count($createdRequests) . " request(s):\n";
    foreach ($createdRequests as $req) {
        echo "  - Reflex Request ID: {$req->id}, Sample ID: {$req->sample_id}\n";
        $tests = $req->tests;
        echo "    Tests: " . $tests->pluck('name')->implode(', ') . "\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
