<?php

// Script to check for existing reflex for sample 000006
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\RuleDecisionAudit;

// Get parent request
$parent = LaboratoryRequest::where('sample_id', '000006')->first();
if (!$parent) {
    echo "Parent request not found\n";
    exit(1);
}

echo "Parent Request ID: {$parent->id}\n";

// Check for existing reflex requests for parent 105 with rule 4
$existing = LaboratoryRequest::where('reflex_parent_request_id', $parent->id)
    ->where('reflex_triggered_by_rule_id', 4)
    ->first();

echo 'Existing reflex request: ' . ($existing ? 'YES (ID: ' . $existing->id . ')' : 'NO') . PHP_EOL;

// Check for audit entry
$audit = RuleDecisionAudit::where('laboratory_request_id', $parent->id)
    ->where('rule_id', 4)
    ->where('decision', 'reflex_created')
    ->first();

echo 'Audit entry for reflex_created: ' . ($audit ? 'YES (ID: ' . $audit->id . ')' : 'NO') . PHP_EOL;

// Show all reflex requests for parent
$allReflexes = LaboratoryRequest::where('reflex_parent_request_id', $parent->id)->get();
echo 'All reflex requests for parent: ' . $allReflexes->count() . PHP_EOL;
foreach ($allReflexes as $r) {
    echo '  - Request ID: ' . $r->id . ', Rule ID: ' . ($r->reflex_triggered_by_rule_id ?? 'null') . ', Sample ID: ' . $r->sample_id . ', Is Reflex: ' . ($r->is_reflex ? 'true' : 'false') . PHP_EOL;
}
