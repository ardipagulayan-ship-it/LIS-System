<?php

namespace App\Observers;

use App\Models\TestResult;
// Note: DeltaCheck model is deprecated - delta checks are now calculated on-demand
// use App\Models\DeltaCheck;

class TestResultObserverWithCleanup
{
    protected $deltaCheckService;

    public function __construct(\App\Services\DeltaCheckService $deltaCheckService)
    {
        $this->deltaCheckService = $deltaCheckService;
    }

    public function saving(TestResult $testResult): void
    {
        $testResult->loadMissing(['testParameter', 'laboratoryRequest.patient']);
        $testResult->calculateFlag();

        // Perform delta check if the result has been set or changed
        if ($testResult->isDirty('result_value') && !is_null($testResult->result_value)) {
            $this->deltaCheckService->performDeltaCheck($testResult);
        }
    }

    /**
     * Clean up delta checks when a test result is deleted
     * 
     * @deprecated Delta checks are now calculated on-demand, no cleanup needed
     */
    public function deleted(TestResult $testResult): void
    {
        // Delta checks are now calculated on-demand, no stored records to clean up
        // This method is kept for backward compatibility but does nothing
    }

    /**
     * Update delta checks when a test result is updated
     * 
     * @deprecated Delta checks are now calculated on-demand when needed
     */
    public function updated(TestResult $testResult): void
    {
        // Delta checks are now calculated on-demand via DeltaCheckService::calculateDeltaCheck()
        // This method is kept for backward compatibility but does nothing
        // The main TestResultObserver handles delta check calculation on save
    }
}
