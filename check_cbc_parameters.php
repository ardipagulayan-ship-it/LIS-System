<?php

// Script to check CBC test and category
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Test;
use App\Models\TestCategory;

// Get CBC test (ID 278 from diagnostic)
$cbc = Test::with('testCategory')->find(278);
if ($cbc) {
    echo "CBC Test ID: {$cbc->id}\n";
    echo "Test Name: {$cbc->name}\n";
    echo "Test Category ID: " . ($cbc->test_category_id ?? 'null') . "\n";
    if ($cbc->testCategory) {
        echo "Category Name: {$cbc->testCategory->name}\n";
    }
} else {
    echo "CBC test not found\n";
}

echo "\n";

// Get all test categories
$categories = TestCategory::all();
echo "All Test Categories:\n";
foreach ($categories as $cat) {
    echo "  ID: {$cat->id}, Name: {$cat->name}\n";
}
