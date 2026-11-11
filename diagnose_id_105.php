<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\TestResult;
use App\Models\CdsCorrelationRule;
use App\Services\CdsRuleEngine;

$requestId = 105;
$request = LaboratoryRequest::with(['patient', 'tests'])->find($requestId);

if (!$request) {
    echo "Laboratory Request ID $requestId NOT FOUND.\n";
    exit;
}

echo "Request ID: $requestId, Sample ID: '{$request->sample_id}', Patient: " . ($request->patient ? $request->patient->last_name : 'N/A') . "\n";
echo "Tests requested:\n";
foreach ($request->tests as $test) {
    echo " - Test ID: {$test->id}, Name: {$test->name}, Status: {$test->pivot->item_status}\n";
}

$results = TestResult::where('laboratory_request_id', $requestId)->get();
echo "\nTest Results found (" . $results->count() . "):\n";
foreach ($results as $res) {
    echo " - Parameter ID: {$res->test_parameter_id}, Value: '{$res->result_value}', Flag: '{$res->flag}', Unit: '{$res->unit}'\n";
}

// Now check CDS rules
$parameterIds = $results->pluck('test_parameter_id')->filter()->unique()->values()->toArray();
$rules = CdsCorrelationRule::whereIn('trigger_test_id', $parameterIds)->where('is_active', true)->get();

echo "\nApplicable Active CDS Rules (" . $rules->count() . "):\n";
$engine = new CdsRuleEngine();

foreach ($rules as $rule) {
    echo " - Rule ID: {$rule->id}, Name: '{$rule->rule_name}', Action: '{$rule->action_type}'\n";
    
    $correlated = collect(explode(',', (string)$rule->correlated_tests))
        ->filter()
        ->map(fn($v) => (int)trim($v))
        ->values()
        ->toArray();

    $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $parameterIds)));
    
    try {
        $fires = $engine->evaluate((array)$rule->rule_logic_json, $request, $needed);
        echo "   Result: " . ($fires ? "FIRES" : "DOES NOT FIRE") . "\n";
    } catch (\Exception $e) {
        echo "   Error evaluating rule: " . $e->getMessage() . "\n";
    }
}
