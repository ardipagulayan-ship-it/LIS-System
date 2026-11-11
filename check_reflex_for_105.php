<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parentId = 105;
$results = \Illuminate\Support\Facades\DB::select("SELECT id, sample_id, reflex_parent_request_id, reflex_triggered_by_rule_id FROM laboratory_requests WHERE reflex_parent_request_id = ?", [$parentId]);

if (empty($results)) {
    echo "No reflex requests found for parent ID $parentId.\n";
} else {
    echo "Reflex requests found for parent ID $parentId:\n";
    foreach ($results as $row) {
        echo "ID: {$row->id}, Sample ID: '{$row->sample_id}', Parent ID: {$row->reflex_parent_request_id}, Rule ID: {$row->reflex_triggered_by_rule_id}\n";
    }
}
