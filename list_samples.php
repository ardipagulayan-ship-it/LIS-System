<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$results = \Illuminate\Support\Facades\DB::select("SELECT id, sample_id FROM laboratory_requests LIMIT 50");
echo "Sample IDs found:\n";
foreach ($results as $row) {
    echo "ID: {$row->id}, Sample ID: '{$row->sample_id}'\n";
}
