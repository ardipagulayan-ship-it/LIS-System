<?php

namespace App\Jobs;

use App\Models\Equipment;
use App\Models\LisBridgeConfig;
use App\Models\TestResult;
use App\Services\CdsRuleEngine;
use App\Services\DeltaCheckService;
use App\Services\QualityControlLockoutService;
use App\Services\ReflexTestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async job for downstream processing of a patient result ingested from LIS Bridge.
 *
 * Dispatched by LisBridgeReceiverController::ingestPatient() AFTER the result row
 * has been committed to the DB and the translation row recorded.
 *
 * This job is intentionally fire-and-forget from the controller's perspective —
 * the HTTP response (204) is returned immediately after the queue dispatch.
 * The forwarder's 2xx response means "accepted" not "fully processed."
 *
 * Downstream processing chain:
 *   1. Flag computation (reference range lookup → L/H/h flag)
 *   2. Delta check evaluation
 *   3. Reflex test trigger
 *   4. CDS rule evaluation
 *   5. QC lockout check (if the analyzer has active QC rules)
 *
 * Each step is wrapped in try/catch so a failure in one step does not cascade.
 * Failed steps are logged but do not throw — the result is already saved.
 *
 * @see LisBridgeReceiverController::ingestPatient()
 * @see LisResultIngestionService
 */
class ProcessLisPatientResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @param  TestResult       $testResult
     * @param  LisBridgeConfig  $config
     */
    public function __construct(
        public TestResult $testResult,
        public LisBridgeConfig $config
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        DeltaCheckService $deltaCheck,
        ReflexTestService $reflexTest,
        CdsRuleEngine $cdsRuleEngine,
        QualityControlLockoutService $qcLockout
    ): void {
        Log::info('ProcessLisPatientResult started', [
            'test_result_id' => $this->testResult->id,
            'bridge_config_id' => $this->config->id,
            'equipment_id'    => $this->testResult->equipment_id,
        ]);

        // ─── Step 1: Compute flag (L/H/h flag from reference ranges) ─────────
        try {
            $this->computeFlag();
        } catch (\Throwable $e) {
            Log::warning('ProcessLisPatientResult: computeFlag failed', [
                'test_result_id' => $this->testResult->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ─── Step 2: Delta check evaluation ──────────────────────────────────
        try {
            $deltaCheck->evaluate($this->testResult);
        } catch (\Throwable $e) {
            Log::warning('ProcessLisPatientResult: delta check failed', [
                'test_result_id' => $this->testResult->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ─── Step 3: Reflex test trigger ─────────────────────────────────────
        try {
            $reflexTest->evaluate($this->testResult);
        } catch (\Throwable $e) {
            Log::warning('ProcessLisPatientResult: reflex test failed', [
                'test_result_id' => $this->testResult->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ─── Step 4: CDS rule evaluation ─────────────────────────────────────
        try {
            $cdsRuleEngine->evaluate($this->testResult);
        } catch (\Throwable $e) {
            Log::warning('ProcessLisPatientResult: CDS rule engine failed', [
                'test_result_id' => $this->testResult->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ─── Step 5: QC lockout check ─────────────────────────────────────────
        // If the analyzer has active QC rules and the latest QC results are invalid,
        // this may lock the analyzer (preventing new patient results from validating).
        try {
            $qcLockout->evaluate($this->testResult);
        } catch (\Throwable $e) {
            Log::warning('ProcessLisPatientResult: QC lockout check failed', [
                'test_result_id' => $this->testResult->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('ProcessLisPatientResult completed', [
            'test_result_id' => $this->testResult->id,
        ]);
    }

    /**
     * Compute the L/H/h flag by looking up the reference range for this test parameter.
     *
     * Uses the existing TestResult::calculateFlag() logic via the relationship.
     *
     * @return void
     */
    protected function computeFlag(): void
    {
        $testParameter = $this->testResult->testParameter;
        $equipment    = $this->testResult->equipment;
        $value        = $this->testResult->result_value;

        if ($testParameter === null || $value === null) {
            return;
        }

        // Parse numeric value if possible
        $numericValue = is_numeric($value) ? (float) $value : null;

        if ($numericValue === null) {
            // Qualitative result — no flag computation possible
            return;
        }

        // Look up the reference range for this test parameter + equipment (if applicable)
        $referenceRange = \App\Models\ReferenceRange::where('test_parameter_id', $testParameter->id)
            ->when($equipment, fn($q) => $q->where('equipment_id', $equipment->id))
            ->first();

        if ($referenceRange === null) {
            return;
        }

        $flag = null;
        $low  = $referenceRange->lower_limit;
        $high = $referenceRange->upper_limit;

        if ($low !== null && $numericValue < $low) {
            $flag = ($numericValue < $low * 0.8) ? 'L' : 'l'; // critically low vs low
        } elseif ($high !== null && $numericValue > $high) {
            $flag = ($numericValue > $high * 1.2) ? 'H' : 'h'; // critically high vs high
        }

        if ($flag !== null) {
            $this->testResult->update(['flag' => $flag]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable|null $exception
     * @return void
     */
    public function failed(\Throwable $exception = null): void
    {
        Log::error('ProcessLisPatientResult permanently failed', [
            'test_result_id' => $this->testResult->id,
            'bridge_config_id' => $this->config->id,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Determine the queue for this job.
     *
     * Uses the 'lis' queue so it can be processed independently from
     * the default application queue.
     *
     * @return string
     */
    public function queue(): string
    {
        return 'lis';
    }
}
