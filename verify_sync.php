<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 105;
$existing = \App\Models\LaboratoryRequest::where('reflex_parent_request_id', $id)->get();
echo "Found " . $existing->count() . " reflex requests for parent ID $id\n";
foreach ($existing as $req) {
    echo "ID: {$req->id}, Rule ID: {$req->reflex_triggered_by_rule_id}\n";
}

$audit = \App\Models\RuleDecisionAudit::where('laboratory_request_id', $id)->where('rule_type', 'CDS')->get();
echo "Found " . $audit->count() . " audit records for request ID $id\n";
foreach ($audit as $a) {
    echo "ID: {$a->id}, Decision: {$a->decision}, Rule ID: {$a->rule_id}\n";
}
