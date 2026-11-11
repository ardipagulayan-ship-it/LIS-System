<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$requestId = 105;
$results = \Illuminate\Support\Facades\DB::select("SELECT * FROM test_results WHERE laboratory_request_id = ?", [$requestId]);

$output = "Results for Request 105:\n";
foreach ($results as $res) {
    $output .= "ID: {$res->id}, Parameter ID: " . ($res->test_parameter_id ?? 'NULL') . ", Value: " . ($res->result_value ?? 'NULL') . ", Flag: " . ($res->flag ?? 'NULL') . "\n";
}

file_put_contents('debug_results_105.txt', $output);
echo "Output written to debug_results_105.txt\n";
