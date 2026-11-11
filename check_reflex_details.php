<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\LaboratoryRequestTest;
use App\Models\TestResult;
use App\Models\RuleDecisionAudit;

echo "=== DETAILED CHECK FOR SAMPLE 000005 REFLEX ===\n\n";

// Parent request
$parent = LaboratoryRequest::find(103);
echo "PARENT REQUEST:\n";
echo "  ID: {$parent->id}\n";
echo "  Sample ID: {$parent->sample_id}\n";
echo "  Patient: {$parent->patient->last_name}, {$parent->patient->first_name}\n";
echo "  Tests: " . $parent->tests->count() . "\n";

// Child reflex request
$child = LaboratoryRequest::where('reflex_parent_request_id', 103)->first();
if ($child) {
    echo "\nCHILD REFLEX REQUEST:\n";
    echo "  ID: {$child->id}\n";
    echo "  Sample ID: {$child->sample_id}\n";
    echo "  Is Reflex: " . ($child->is_reflex ? 'true' : 'false') . "\n";
    echo "  Reflex Parent ID: {$child->reflex_parent_request_id}\n";
    echo "  Reflex Triggered By Rule ID: " . ($child->reflex_triggered_by_rule_id ?? 'null') . "\n";
    echo "  Tests: " . $child->tests->count() . "\n";
    
    echo "\n  Child Request Tests:\n";
    foreach ($child->tests as $test) {
        echo "    - Test ID: {$test->id}, Test: " . ($test->test->name ?? 'N/A') . ", Status: {$test->item_status}\n";
    }
    
    echo "\n  Child Request Test Results:\n";
    $childResults = TestResult::where('laboratory_request_id', $child->id)->get();
    foreach ($childResults as $result) {
        echo "    - Test: " . ($result->test->name ?? 'N/A') . ", Parameter: " . ($result->testParameter->name ?? 'N/A') . ", Value: {$result->result_value}\n";
    }
} else {
    echo "\nNO CHILD REFLEX REQUEST FOUND!\n";
}

// RuleDecisionAudit entries
echo "\nRULE DECISION AUDIT ENTRIES for parent request 103:\n";
$audits = RuleDecisionAudit::where('laboratory_request_id', 103)->get();
foreach ($audits as $audit) {
    echo "  - ID: {$audit->id}, Rule Type: {$audit->rule_type}, Decision: {$audit->decision}, Rule ID: {$audit->rule_id}, User: " . ($audit->user->name ?? 'N/A') . "\n";
    if ($audit->context) {
        echo "    Context: " . json_encode($audit->context) . "\n";
    }
}
