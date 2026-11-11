<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;

$requests = LaboratoryRequest::where('sample_id', 'like', '%6%')->get();
echo "Laboratory Requests containing '6':\n";
foreach ($requests as $req) {
    echo "ID: {$req->id}, Sample ID: '{$req->sample_id}', Patient: " . ($req->patient ? $req->patient->last_name : 'N/A') . "\n";
}
