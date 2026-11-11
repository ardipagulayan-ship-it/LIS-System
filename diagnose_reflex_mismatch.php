<?php
/**
 * Diagnostic script to trace reflex test creation and identify data mismatches
 * 
 * Usage: php diagnose_reflex_mismatch.php <parent_request_id>
 */

if ($argc < 2) {
    echo "Usage: php diagnose_reflex_mismatch.php <parent_request_id>\n";
    exit(1);
}

$parentRequestId = $argc > 1 ? $argv[1] : null;

require __DIR__.'/backend/vendor/autoload.php';

$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaboratoryRequest;
use App\Models\CdsCorrelationRule;
use App\Models\User;
use App\Services\ReflexTestService;
use Illuminate\Support\Facades\DB;

echo "=== REFLEX TEST DATA MISMATCH DIAGNOSTIC ===\n\n";

// 1. Check the parent request
echo "1. PARENT REQUEST (ID: {$parentRequestId}):\n";
$parent = LaboratoryRequest::with(['patient', 'tests'])->find($parentRequestId);
if (!$parent) {
    echo "   ERROR: Parent request not found!\n";
    exit(1);
}

echo "   Sample ID: {$parent->sample_id}\n";
echo "   Patient ID: {$parent->patient_id}\n";
if ($parent->patient) {
    echo "   Patient Name: {$parent->patient->last_name}, {$parent->patient->first_name}\n";
}
echo "   Tests: " . $parent->tests->pluck('name')->implode(', ') . "\n\n";

// 2. Check if there's an existing reflex for this request
echo "2. EXISTING REFLEX REQUESTS:\n";
$existingReflexes = LaboratoryRequest::where('reflex_parent_request_id', $parentRequestId)
    ->with(['patient', 'tests'])
    ->get();
echo "   Count: " . $existingReflexes->count() . "\n";
foreach ($existingReflexes as $reflex) {
    echo "   Reflex Request ID: {$reflex->id}\n";
    echo "     Sample ID: {$reflex->sample_id}\n";
    echo "     Patient ID: {$reflex->patient_id}\n";
    if ($reflex->patient) {
        echo "     Patient Name: {$reflex->patient->last_name}, {$reflex->patient->first_name}\n";
    }
    echo "     Tests: " . $reflex->tests->pluck('name')->implode(', ') . "\n";
    echo "     Rule ID: {$reflex->reflex_triggered_by_rule_id}\n";
}
echo "\n";

// 3. Check CDS rules that could trigger
echo "3. CDS RULES THAT MIGHT TRIGGER:\n";
$testResultParamIds = \App\Models\TestResult::where('laboratory_request_id', $parentRequestId)
    ->whereNotNull('test_parameter_id')
    ->whereNotNull('result_value')
    ->pluck('test_parameter_id')
    ->unique()
    ->toArray();

$rules = CdsCorrelationRule::whereIn('trigger_test_id', $testResultParamIds)
    ->where('is_active', true)
    ->get();
echo "   Found " . $rules->count() . " active rules\n";
foreach ($rules as $rule) {
    $triggerParam = \App\Models\TestParameter::find($rule->trigger_test_id);
    $reflexParam = \App\Models\TestParameter::find($rule->reflex_test_id);
    echo "   Rule ID: {$rule->id} - {$rule->rule_name}\n";
    echo "     Trigger: " . ($triggerParam ? $triggerParam->test_name : 'N/A') . "\n";
    echo "     Reflex: " . ($reflexParam ? $reflexParam->test_name : 'N/A') . "\n";
    echo "     Action: {$rule->action_type}\n";
}
echo "\n";

// 4. Check audit records
echo "4. RULE DECISION AUDIT RECORDS:\n";
$audits = DB::table('rule_decision_audit')
    ->where('laboratory_request_id', $parentRequestId)
    ->where('rule_type', 'CDS')
    ->orderBy('created_at', 'desc')
    ->get();
echo "   Count: " . $audits->count() . "\n";
foreach ($audits as $audit) {
    echo "   ID: {$audit->id}\n";
    echo "     Decision: {$audit->decision}\n";
    echo "     Rule ID: {$audit->rule_id}\n";
    echo "     Created: {$audit->created_at}\n";
    $context = json_decode($audit->context, true);
    if ($context) {
        echo "     Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
}
echo "\n";

// 5. Simulate reflex creation with detailed logging
if (count($rules) > 0 && $existingReflexes->count() == 0) {
    echo "5. SIMULATING REFLEX CREATION:\n";
    $rule = $rules->first();
    $user = User::first();
    
    if ($rule && $user) {
        echo "   Using Rule ID: {$rule->id}\n";
        echo "   User: {$user->name} (ID: {$user->id})\n";
        
        $service = new ReflexTestService();
        
        // Add detailed logging
        echo "\n   --- Starting service call ---\n";
        try {
            $result = $service->createReflexTests($parent, $rule, $user, false);
            echo "   Created " . count($result) . " reflex request(s)\n";
            
            foreach ($result as $reflexReq) {
                echo "   Reflex Request ID: {$reflexReq->id}\n";
                echo "     Sample ID: {$reflexReq->sample_id}\n";
                echo "     Patient ID: {$reflexReq->patient_id}\n";
                if ($reflexReq->patient) {
                    echo "     Patient Name: {$reflexReq->patient->last_name}, {$reflexReq->patient->first_name}\n";
                }
                echo "     Parent Request ID: {$reflexReq->reflex_parent_request_id}\n";
                echo "     Tests: " . $reflexReq->tests->pluck('name')->implode(', ') . "\n";
            }
        } catch (Exception $e) {
            echo "   ERROR: " . $e->getMessage() . "\n";
            echo "   Trace: " . $e->getTraceAsString() . "\n";
        }
    }
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
