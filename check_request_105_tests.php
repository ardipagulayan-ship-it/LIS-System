<?php

// Script to check tests for request 105
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequestTest;

// Get all tests for request 105
$tests = LaboratoryRequestTest::where('laboratory_request_id', 105)->get();
echo 'Tests for request 105:' . PHP_EOL;
foreach ($tests as $test) {
    echo '  Test ID: ' . $test->test_id . ', Is Reflex: ' . ($test->is_reflex_test ? 'true' : 'false') . PHP_EOL;
}
