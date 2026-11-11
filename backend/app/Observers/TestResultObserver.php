<?php

namespace App\Observers;

use App\Models\TestResult;
use App\Services\DeltaCheckService;
use Illuminate\Support\Facades\Log;

class TestResultObserver
{
    protected $deltaCheckService;

    public function __construct(DeltaCheckService $deltaCheckService)
    {
        $this->deltaCheckService = $deltaCheckService;
    }

    /**
     * Handle the TestResult "saving" event.
     * 
     * This event fires just before the model is saved (created or updated).
     */
    public function saving(TestResult $testResult): void
    {
        // Attempt to load necessary relationships if they aren't already
        // This helps ensure data is available but avoid N+1 if possible
        $testResult->loadMissing(['testParameter', 'laboratoryRequest.patient']);

        // Be defensive: never allow flag calculation to 500 the request
        try {
            $testResult->calculateFlag();
        } catch (\Throwable $e) {
            Log::warning('calculateFlag failed in TestResultObserver::saving', [
                'error' => $e->getMessage(),
                'test_result_id' => $testResult->id,
                'test_id' => $testResult->test_id,
                'test_parameter_id' => $testResult->test_parameter_id,
            ]);
            $testResult->flag = null; // proceed without flag
        }

        // Perform delta check if the result has been set or changed
        // Now using on-demand calculation with logging instead of storing data
        try {
            // Moved to 'created' and 'updated' events to ensure created_at is available
        } catch (\Throwable $e) {
            Log::warning('Delta check failed in TestResultObserver::saving', [
                'error' => $e->getMessage(),
                'test_result_id' => $testResult->id,
            ]);
        }
    }

    /**
     * Handle the TestResult "created" event.
     */
    public function created(TestResult $testResult): void
    {
        Log::debug('TestResultObserver: created event fired', ['id' => $testResult->id, 'status' => $testResult->status]);

        // Perform delta check after the model has been created and has an ID and created_at timestamp
        if (!is_null($testResult->result_value)) {
            $this->deltaCheckService->performDeltaCheck($testResult);
            Log::debug('TestResultObserver: status after delta check in updated', ['id' => $testResult->id, 'status' => $testResult->status]);
            Log::debug('TestResultObserver: status after delta check in created', ['id' => $testResult->id, 'status' => $testResult->status]);
        }
    }

    /**
     * Handle the TestResult "updated" event.
     */
    public function updated(TestResult $testResult): void
    {
        Log::debug('TestResultObserver: updated event fired', ['id' => $testResult->id, 'status' => $testResult->status]);

        // Perform delta check after the model has been updated and has an ID and created_at timestamp
        if ($testResult->isDirty('result_value') && !is_null($testResult->result_value)) {
            $this->deltaCheckService->performDeltaCheck($testResult);
        }
    }

    /**
     * Handle the TestResult "deleted" event.
     */
    public function deleted(TestResult $testResult): void
    {
        //
    }

    /**
     * Handle the TestResult "restored" event.
     */
    public function restored(TestResult $testResult): void
    {
        //
    }

    /**
     * Handle the TestResult "force deleted" event.
     */
    public function forceDeleted(TestResult $testResult): void
    {
        //
    }
}
