<?php

// Script to check parameter 58 and its test
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TestParameter;

// Get parameter 58 with its test
$param = TestParameter::with('test')->find(58);
if ($param) {
    echo "Parameter ID 58:\n";
    echo "  Name: {$param->name}\n";
    echo "  Test ID: {$param->test_id}\n";
    if ($param->test) {
        echo "  Test Name: {$param->test->name}\n";
        echo "  Test Category ID: " . ($param->test->test_category_id ?? 'null') . "\n";
    }
} else {
    echo "Parameter 58 NOT FOUND\n";
}

// Also check if test 19 has parameters
echo "\nParameters for test 19 (Ferritin):\n";
$params = \App\Models\Test::find(19)->testParameters;
foreach ($params as $p) {
    echo "  Parameter ID: {$p->id}, Name: {$p->name}\n";
}
