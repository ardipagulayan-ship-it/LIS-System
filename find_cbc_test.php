<?php

// Script to find CBC test
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Test;

// Check test ID 278
$test = Test::find(278);
echo 'Test 278: ' . ($test ? $test->name : 'NOT FOUND') . PHP_EOL;

// Find CBC by name
$cbc = Test::where('name', 'like', '%Complete Blood Count%')->first();
if ($cbc) {
    echo 'CBC found with ID: ' . $cbc->id . ', Name: ' . $cbc->name . PHP_EOL;
} else {
    echo 'CBC test not found in tests table' . PHP_EOL;
}

// Show all tests
$allTests = Test::all();
echo 'All tests count: ' . $allTests->count() . PHP_EOL;
foreach ($allTests as $t) {
    echo '  ID: ' . $t->id . ', Name: ' . $t->name . PHP_EOL;
}
