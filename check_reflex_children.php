<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;

echo "Checking for reflex child requests of parent ID 103:\n";
$children = LaboratoryRequest::where('reflex_parent_request_id', 103)->get();
echo "Count: " . $children->count() . "\n";
foreach ($children as $child) {
    echo "  Child ID: {$child->id}, Sample ID: {$child->sample_id}, Is Reflex: " . ($child->is_reflex ? 'true' : 'false') . "\n";
}
