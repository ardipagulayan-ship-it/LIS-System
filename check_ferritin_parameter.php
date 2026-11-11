<?php

// Script to check ferritin parameter configuration
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TestParameter;
use App\Models\Test;

// Check parameter 58
$param = TestParameter::with('test')->find(58);
if ($param) {
    echo "Parameter ID 58 found: {$param->name}\n";
    echo "  Test ID: {$param->test_id}\n";
    if ($param->test) {
        echo "  Test Name: {$param->test->name}\n";
        echo "  Test Category ID: " . ($param->test->test_category_id ?? 'null') . "\n";
    }
} else {
    echo "Parameter ID 58 NOT FOUND\n";
}
