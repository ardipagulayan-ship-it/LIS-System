<?php

// Script to check rule 4 configuration
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CdsCorrelationRule;
use App\Models\TestParameter;

$rule = CdsCorrelationRule::find(4);
echo "Rule ID: {$rule->id}\n";
echo "Rule Name: {$rule->rule_name}\n";
echo "Trigger Test ID: {$rule->trigger_test_id}\n";
echo "Reflex Test ID: {$rule->reflex_test_id}\n";
echo "Is Active: " . ($rule->is_active ? 'true' : 'false') . "\n";

// Check if the reflex test parameter exists
$param = TestParameter::find($rule->reflex_test_id);
echo "Reflex Parameter: " . ($param ? $param->name : 'NOT FOUND') . "\n";
if ($param) {
    echo "  Parameter Test ID: " . ($param->test_id ?? 'null') . "\n";
    echo "  Parameter Test Name: " . ($param->test->name ?? 'N/A') . "\n";
}
