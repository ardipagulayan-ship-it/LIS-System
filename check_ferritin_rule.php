<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CdsCorrelationRule;
use App\Models\TestParameter;

$rule4 = CdsCorrelationRule::find(4);
if ($rule4) {
    echo "Rule 4: '{$rule4->rule_name}'\n";
    echo "Trigger Parameter ID: {$rule4->trigger_test_id}\n";
    echo "Reflex Parameter ID: {$rule4->reflex_test_id}\n";
    
    $trigger = TestParameter::find($rule4->trigger_test_id);
    echo "Trigger Name: " . ($trigger ? $trigger->test_name : 'NOT FOUND') . "\n";
    
    $reflex = TestParameter::find($rule4->reflex_test_id);
    echo "Reflex Name: " . ($reflex ? $reflex->test_name : 'NOT FOUND') . "\n";
} else {
    echo "Rule 4 NOT FOUND.\n";
}

$ferritin = TestParameter::find(58);
if ($ferritin) {
    echo "\nParameter 58: '{$ferritin->test_name}'\n";
    echo "Test ID: {$ferritin->test_id}\n";
    $test = $ferritin->test;
    echo "Test Name: " . ($test ? $test->name : 'NOT FOUND') . "\n";
} else {
    echo "\nParameter 58 NOT FOUND.\n";
}
