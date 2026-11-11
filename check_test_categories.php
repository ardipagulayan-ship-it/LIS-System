<?php

// Script to check test categories
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Test;

// Get CBC test (ID 1)
$cbc = Test::with('testCategory')->find(1);
if ($cbc) {
    echo "CBC Test (ID 1):\n";
    echo "  Name: {$cbc->name}\n";
    echo "  Test Category ID: " . ($cbc->test_category_id ?? 'null') . "\n";
    if ($cbc->testCategory) {
        echo "  Category Name: {$cbc->testCategory->name}\n";
    }
}

echo "\n";

// Get Ferritin test (ID 19)
$ferritin = Test::with('testCategory')->find(19);
if ($ferritin) {
    echo "Ferritin Test (ID 19):\n";
    echo "  Name: {$ferritin->name}\n";
    echo "  Test Category ID: " . ($ferritin->test_category_id ?? 'null') . "\n";
    if ($ferritin->testCategory) {
        echo "  Category Name: {$ferritin->testCategory->name}\n";
    }
}
