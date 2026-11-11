<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 105;
$audits = \App\Models\RuleDecisionAudit::where('laboratory_request_id', $id)->get();
echo "Total audit records for request ID $id: " . $audits->count() . "\n";
foreach ($audits as $a) {
    echo "ID: {$a->id}, Type: {$a->rule_type}, Decision: {$a->decision}, Rule: {$a->rule_id}, Message: {$a->message}\n";
}
