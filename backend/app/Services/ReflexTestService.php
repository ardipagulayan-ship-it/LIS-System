<?php

namespace App\Services;

use App\Models\CdsCorrelationRule;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryRequestTest;
use App\Models\Test;
use App\Models\TestCategory;
use App\Models\TestParameter;
use App\Models\User;
use App\Models\RuleDecisionAudit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReflexTestService
{
    /**
     * Create reflex tests based on a CDS rule trigger.
     *
     * @param LaboratoryRequest $parentRequest The parent laboratory request that triggered the rule
     * @param CdsCorrelationRule $rule The CDS correlation rule that was triggered
     * @param User $user The user who initiated/approved the reflex
     * @param bool $autoCreate If true, skips approval check (for automatic workflows)
     * @return array Array of created LaboratoryRequest models
     *
     * @throws \Exception If reflex creation fails
     */
    public function createReflexTests(
        LaboratoryRequest $parentRequest,
        CdsCorrelationRule $rule,
        User $user,
        bool $autoCreate = false
    ): array {
        try {
            Log::info('Starting reflex test creation', [
                'parent_request_id' => $parentRequest->id,
                'parent_sample_id' => $parentRequest->sample_id,
                'parent_patient_id' => $parentRequest->patient_id,
                'parent_patient_name' => $parentRequest->patient ? $parentRequest->patient->last_name . ', ' . $parentRequest->patient->first_name : 'N/A',
                'rule_id' => $rule->id,
                'rule_name' => $rule->rule_name,
                'reflex_test_id' => $rule->reflex_test_id,
                'auto_create' => $autoCreate,
                'user_id' => $user->id
            ]);

            // Validate if reflex test can be created
            if ($this->hasExistingReflex($parentRequest, $rule)) {
                Log::info('Reflex test already exists', [
                    'parent_request_id' => $parentRequest->id,
                    'rule_id' => $rule->id
                ]);
                return [];
            }

            // Get tests for reflex parameter
            $tests = $this->getTestsForReflexParameter($rule->reflex_test_id);

            if ($tests->isEmpty()) {
                Log::warning('No tests found for reflex parameter', [
                    'reflex_test_id' => $rule->reflex_test_id
                ]);
                return [];
            }

            Log::info('Tests found for reflex parameter', [
                'reflex_test_id' => $rule->reflex_test_id,
                'tests_count' => $tests->count(),
                'tests' => $tests->pluck('name')->toArray()
            ]);

            // Group tests by category
            $groupedTests = $this->groupTestsByCategory($tests);

            Log::info('Tests grouped by category', [
                'grouped_tests_count' => $groupedTests->count(),
                'categories' => $groupedTests->keys()->toArray()
            ]);

            // Create ONE reflex laboratory request for ALL tests
            // to satisfy the unique index (parent_request, rule_id) in the database
            $reflexRequest = $this->createSingleReflexRequest(
                $parentRequest,
                $tests->first(), // Use first test as representative for metadata
                TestParameter::find($rule->reflex_test_id),
                $rule,
                $user
            );

            Log::info('Created reflex request', [
                'reflex_request_id' => $reflexRequest->id,
                'reflex_sample_id' => $reflexRequest->sample_id,
                'reflex_patient_id' => $reflexRequest->patient_id,
                'reflex_patient_name' => $reflexRequest->patient ? $reflexRequest->patient->last_name . ', ' . $reflexRequest->patient->first_name : 'N/A',
                'parent_request_id' => $parentRequest->id,
                'rule_id' => $rule->id,
                'tests_count' => $tests->count()
            ]);

            // Create LaboratoryRequestTest entries for all tests
            foreach ($tests as $test) {
                LaboratoryRequestTest::create([
                    'laboratory_request_id' => $reflexRequest->id,
                    'test_id' => $test->id,
                    'item_status' => 'Pending',
                    'is_reflex_test' => true,
                    'reflex_triggered_at' => now(),
                    'reflex_triggered_by_rule_id' => $rule->id,
                ]);

                Log::debug('Created LaboratoryRequestTest entry', [
                    'reflex_request_id' => $reflexRequest->id,
                    'test_id' => $test->id,
                    'test_name' => $test->name
                ]);
            }

            $createdRequests = [$reflexRequest];

            Log::info('Reflex laboratory request completed', [
                'reflex_request_id' => $reflexRequest->id,
                'parent_request_id' => $parentRequest->id,
                'rule_id' => $rule->id,
                'tests_count' => $tests->count()
            ]);

            // Create audit entry for reflex creation
            RuleDecisionAudit::create([
                'rule_type' => 'CDS',
                'decision' => 'reflex_created',
                'message' => $rule->alert_message ?? 'Reflex tests created automatically',
                'rule_id' => $rule->id,
                'laboratory_request_id' => $parentRequest->id,
                'user_id' => $user->id,
                'context' => [
                    'reflex_request_ids' => collect($createdRequests)->pluck('id')->toArray(),
                    'auto_created' => $autoCreate,
                ]
            ]);

            return $createdRequests;
        } catch (\Exception $e) {
            Log::error('Failed to create reflex tests', [
                'parent_request_id' => $parentRequest->id,
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve tests associated with a reflex parameter.
     * Handles both standalone parameters and parameters that are part of a test package.
     *
     * @param int $reflexParameterId The ID of the test parameter
     * @return \Illuminate\Support\Collection Collection of Test models
     */
    public function getTestsForReflexParameter(int $reflexParameterId): Collection
    {
        try {
            $parameter = TestParameter::find($reflexParameterId);

            if (!$parameter) {
                Log::warning('Reflex parameter not found', [
                    'reflex_parameter_id' => $reflexParameterId
                ]);
                return collect([]);
            }

            // Get the test that contains this parameter
            $test = $parameter->test;

            if (!$test) {
                Log::warning('Test not found for parameter', [
                    'reflex_parameter_id' => $reflexParameterId
                ]);
                return collect([]);
            }

            $tests = collect([$test]);

            // Check if the test belongs to a package/group
            // Look for test_packages relationship or similar
            if (method_exists($test, 'packages') && $test->packages()->exists()) {
                // If it's a package, expand to all tests in the package
                $packageTests = $test->packages()->with('tests')->get()->flatMap(function ($package) {
                    return $package->tests;
                });

                $tests = $tests->merge($packageTests)->unique('id');
            }

            return $tests;
        } catch (\Exception $e) {
            Log::error('Failed to get tests for reflex parameter', [
                'reflex_parameter_id' => $reflexParameterId,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Group tests by their test category.
     *
     * @param \Illuminate\Support\Collection $tests Collection of Test models
     * @return \Illuminate\Support\Collection Collection grouped by category ID
     */
    public function groupTestsByCategory(Collection $tests): Collection
    {
        return $tests->groupBy(function ($test) {
            return $test->test_category_id ?? $test->category_id ?? null;
        });
    }

    /**
     * Create a single reflex laboratory request.
     * Private helper method for createReflexTests.
     *
     * @param LaboratoryRequest $parentRequest The parent request
     * @param Test $test The test to include (used for category reference)
     * @param TestParameter $reflexParameter The triggering parameter
     * @param CdsCorrelationRule $rule The rule that triggered this reflex
     * @param User $user The user creating the request
     * @return LaboratoryRequest The created reflex request
     *
     * @throws \Exception If creation fails
     */
    private function createSingleReflexRequest(
        LaboratoryRequest $parentRequest,
        Test $test,
        TestParameter $reflexParameter,
        CdsCorrelationRule $rule,
        User $user
    ): LaboratoryRequest {
        try {
            DB::beginTransaction();

            // Inherit sample_id from parent request (reflex tests use same sample)
            $sampleId = $parentRequest->sample_id;

            // Create new LaboratoryRequest with inherited fields
            $reflexRequest = LaboratoryRequest::create([
                'patient_id' => $parentRequest->patient_id,
                'doctor_id' => $parentRequest->doctor_id,
                'ward_id' => $parentRequest->ward_id,
                'room_id' => $parentRequest->room_id,
                'patient_type_id' => $parentRequest->patient_type_id,
                'sample_id' => $sampleId,
                'request_date' => now(),
                'priority_level' => $parentRequest->priority_level,
                'created_by' => $user->id,
                'is_reflex' => true,
                'reflex_parent_request_id' => $parentRequest->id,
                'reflex_triggered_by_rule_id' => $rule->id,
            ]);

            DB::commit();

            return $reflexRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create single reflex request', [
                'parent_request_id' => $parentRequest->id,
                'rule_id' => $rule->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if a reflex request already exists for the given parent and rule.
     *
     * @param LaboratoryRequest $parentRequest The parent request
     * @param CdsCorrelationRule $rule The rule to check
     * @return bool True if reflex already exists
     */
    public function hasExistingReflex(LaboratoryRequest $parentRequest, CdsCorrelationRule $rule): bool
    {
        // Check LaboratoryRequest table
        $existingRequest = LaboratoryRequest::where(
            'reflex_parent_request_id',
            $parentRequest->id
        )->where(
            'reflex_triggered_by_rule_id',
            $rule->id
        )->exists();

        if ($existingRequest) {
            return true;
        }

        // Check RuleDecisionAudit table for previous reflex creation
        $auditEntry = RuleDecisionAudit::where(
            'rule_id',
            $rule->id
        )->where(
            'laboratory_request_id',
            $parentRequest->id
        )->where(
            'decision',
            'reflex_created'
        )->exists();

        return $auditEntry;
    }

    /**
     * Generate a unique sample ID for a laboratory request.
     *
     * @return string Unique sample ID
     */
    private function generateUniqueSampleId(): string
    {
        // Generate a unique sample ID using UUID or timestamp-based approach
        // Using Str::uuid() for uniqueness
        $sampleId = (string) Str::uuid();

        // Ensure uniqueness (though UUID v4 is extremely unlikely to collide)
        while (LaboratoryRequest::where('sample_id', $sampleId)->exists()) {
            $sampleId = (string) Str::uuid();
        }

        return $sampleId;
    }
}
