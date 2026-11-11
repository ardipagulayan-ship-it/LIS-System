<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;

$requests = LaboratoryRequest::orderBy('id', 'desc')->take(20)->get();
echo "Recent Laboratory Requests:\n";
foreach ($requests as $req) {
    echo "ID: {$req->id}, Sample ID: '{$req->sample_id}', Patient: " . ($req->patient ? $req->patient->last_name : 'N/A') . "\n";
}

$target = '000006';
$req6 = LaboratoryRequest::where('sample_id', $target)->first();
if ($req6) {
    echo "\nFound Sample ID '{$target}':\n";
    echo "ID: {$req6->id}, Patient ID: {$req6->patient_id}\n";
} else {
    echo "\nSample ID '{$target}' NOT FOUND.\n";
    
    $target2 = '00006';
    $req6_2 = LaboratoryRequest::where('sample_id', $target2)->first();
    if ($req6_2) {
        echo "Found Sample ID '{$target2}':\n";
        echo "ID: {$req6_2->id}, Patient ID: {$req6_2->patient_id}\n";
    } else {
        echo "Sample ID '{$target2}' NOT FOUND either.\n";
    }
}
