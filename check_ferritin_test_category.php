<?php

// Script to check Ferritin test category
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Test;

// Get Ferritin test (ID 19)
$ferritin = Test::with('testCategory')->find(19);
if ($ferritin) {
    echo "Ferritin Test (ID 19):\n";
    echo "  Name: {$ferritin->name}\n";
    echo "  test_category_id: " . ($ferritin->test_category_id ?? 'NULL') . "\n";
    if ($ferritin->testCategory) {
        echo "  Category: {$ferritin->testCategory->name}\n";
    } else {
        echo "  Category: NOT SET\n";
    }
} else {
    echo "Ferritin test not found\n";
}
