<?php

namespace App\Jobs;

use App\Models\LisBridgeConfig;
use App\Models\QualityControlResult;
use App\Services\SigmaMetricCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async job for downstream processing of a QC result ingested from LIS Bridge.
 *
 * Dispatched by LisBridgeReceiverController::ingestQc() AFTER the result row
 * has been committed to the DB and the translation row recorded.
 *
 * Downstream processing chain:
 *   1. Westgard rule evaluation (per QC rule configuration)
 *   2. Sigma metric recalculation for the affected QC material + analyte
 *   3. QC lockout re-evaluation (a new valid QC result may un-lock an analyzer)
 *
 * Each step is wrapped in try/catch so a failure in one step does not cascade.
 *
 * @see LisBridgeReceiverController::ingestQc()
 * @see LisResultIngestionService
 */
class ProcessLisQcResult implements ShouldQueue
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
     * @param  QualityControlResult $qcResult
     * @param  LisBridgeConfig     $config
     */
    public function __construct(
        public QualityControlResult $qcResult,
        public LisBridgeConfig $config
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SigmaMetricCalculator $sigmaMetric): void
    {
        Log::info('ProcessLisQcResult started', [
            'qc_result_id'      => $this->qcResult->id,
            'bridge_config_id'  => $this->config->id,
            'qc_test_param_id' => $this->qcResult->quality_control_test_parameter_id,
        ]);

        // ─── Step 1: Westgard rule evaluation ────────────────────────────────
        try {
            $this->evaluateWestgardRules();
        } catch (\Throwable $e) {
            Log::warning('ProcessLisQcResult: Westgard evaluation failed', [
                'qc_result_id' => $this->qcResult->id,
                'error'        => $e->getMessage(),
            ]);
        }

        // ─── Step 2: Sigma metric recalculation ──────────────────────────────
        try {
            $sigmaMetric->calculateForTestParameter(
                $this->qcResult->qualityControlResult?->qualityControlTestParameter?->test_parameter_id
                    ?? $this->qcResult->qcTestParameter?->test_parameter_id
            );
        } catch (\Throwable $e) {
            Log::warning('ProcessLisQcResult: sigma metric calculation failed', [
                'qc_result_id' => $this->qcResult->id,
                'error'        => $e->getMessage(),
            ]);
        }

        // ─── Step 3: QC lockout re-evaluation ───────────────────────────────
        // A new valid QC result may un-lock an analyzer that was previously locked.
        // We trigger lockout re-evaluation for the equipment that produced this QC.
        try {
            $this->reEvaluateLockout();
        } catch (\Throwable $e) {
            Log::warning('ProcessLisQcResult: lockout re-evaluation failed', [
                'qc_result_id' => $this->qcResult->id,
                'error'        => $e->getMessage(),
            ]);
        }

        Log::info('ProcessLisQcResult completed', [
            'qc_result_id' => $this->qcResult->id,
        ]);
    }

    /**
     * Evaluate Westgard rules for the new QC result.
     *
     * The QC result is already stored. Westgard violations are computed by looking
     * at the rolling window of recent QC results for the same QC material + analyte.
     *
     * @return void
     */
    protected function evaluateWestgardRules(): void
    {
        $qcResultParam = $this->qcResult->qualityControlTestParameter
            ?? $this->qcResult->qcTestParameter;

        if ($qcResultParam === null) {
            Log::warning('ProcessLisQcResult: QC test parameter not found for result', [
                'qc_result_id' => $this->qcResult->id,
            ]);
            return;
        }

        $testParameterId = $qcResultParam->test_parameter_id;
        $qcId = $qcResultParam->quality_control_id;

        // Load recent QC results for this QC material + test parameter
        $recentResults = QualityControlResult::whereHas('qualityControlTestParameter', function ($q) use ($qcId, $testParameterId) {
            $q->where('quality_control_id', $qcId)
                ->where('test_parameter_id', $testParameterId);
        })
            ->orderBy('created_at', 'desc')
            ->limit(20) // Westgard typically needs 20 points
            ->pluck('result_value')
            ->map(fn($v) => is_numeric($v) ? (float) $v : null)
            ->filter()
            ->values()
            ->toArray();

        if (count($recentResults) < 2) {
            return; // Not enough data for Westgard evaluation
        }

        // Load QC rules for this test parameter
        $rules = \App\Models\QcRule::where('test_parameter_id', $testParameterId)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        // Compute mean and SD from the rolling window
        $mean = array_sum($recentResults) / count($recentResults);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $recentResults)) / count($recentResults);
        $sd = sqrt($variance);

        $violations = [];

        foreach ($rules as $rule) {
            $violated = $this->checkWestgardRule(
                $rule->rule_code,
                $recentResults,
                $mean,
                $sd,
                $rule->params ?? []
            );

            if ($violated) {
                $violations[] = $rule->rule_code;
            }
        }

        if (!empty($violations)) {
            $this->qcResult->update([
                'westgard_violations' => $violations,
                'is_valid' => false,
            ]);

            Log::info('ProcessLisQcResult: Westgard violations detected', [
                'qc_result_id' => $this->qcResult->id,
                'violations'   => $violations,
            ]);
        } else {
            $this->qcResult->update(['is_valid' => true]);
        }
    }

    /**
     * Check a single Westgard rule against the recent QC results.
     *
     * @param  string   $ruleCode  e.g. '1-2S', '1-3S', '2-2R', '4-1T', '10X'
     * @param  array    $results   Array of recent numeric QC values
     * @param  float    $mean
     * @param  float    $sd
     * @param  array    $params   Rule parameters (e.g. threshold multipliers)
     * @return bool
     */
    protected function checkWestgardRule(
        string $ruleCode,
        array $results,
        float $mean,
        float $sd,
        array $params = []
    ): bool {
        if ($sd <= 0 || empty($results)) {
            return false;
        }

        return match ($ruleCode) {
            // 1-2S: One result > 2 SD from mean
            '1-2S' => match ($params['direction'] ?? 'both') {
                'high' => end($results) > ($mean + 2 * $sd),
                'low'  => end($results) < ($mean - 2 * $sd),
                default => abs(end($results) - $mean) > 2 * $sd,
            },

            // 1-3S: One result > 3 SD from mean
            '1-3S' => match ($params['direction'] ?? 'both') {
                'high' => end($results) > ($mean + 3 * $sd),
                'low'  => end($results) < ($mean - 3 * $sd),
                default => abs(end($results) - $mean) > 3 * $sd,
            },

            // 2-2R: Two consecutive results > 2 SD on same side of mean
            '2-2R' => count($results) >= 2
                && abs($results[0] - $mean) > 2 * $sd
                && abs($results[1] - $mean) > 2 * $sd
                && (($results[0] - $mean) * ($results[1] - $mean)) > 0,

            // 4-1T: Four consecutive results > 1 SD on same side of mean
            '4-1T' => count($results) >= 4
                && abs($results[0] - $mean) > $sd
                && abs($results[1] - $mean) > $sd
                && abs($results[2] - $mean) > $sd
                && abs($results[3] - $mean) > $sd
                && (($results[0] - $mean) * ($results[1] - $mean)) > 0
                && (($results[1] - $mean) * ($results[2] - $mean)) > 0
                && (($results[2] - $mean) * ($results[3] - $mean)) > 0,

            // 10X: Ten consecutive results on same side of mean
            '10X' => count($results) >= 10
                && $this->allSameSide($results, $mean),

            default => false,
        };
    }

    /**
     * Check if all results in an array are on the same side of the mean.
     *
     * @param  array $results
     * @param  float $mean
     * @return bool
     */
    protected function allSameSide(array $results, float $mean): bool
    {
        $n = min(10, count($results));
        for ($i = 0; $i < $n - 1; $i++) {
            if (($results[$i] - $mean) * ($results[$i + 1] - $mean) <= 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Re-evaluate QC lockout for the equipment that produced this QC result.
     *
     * @return void
     */
    protected function reEvaluateLockout(): void
    {
        $qcResultParam = $this->qcResult->qualityControlTestParameter
            ?? $this->qcResult->qcTestParameter;

        if ($qcResultParam === null) {
            return;
        }

        $qc = $qcResultParam->qualityControl;
        if ($qc === null) {
            return;
        }

        $equipmentId = $qc->equipment_id;
        if ($equipmentId === null) {
            return;
        }

        $lockoutService = app(\App\Services\QualityControlLockoutService::class);
        $lockoutService->reEvaluateEquipment($equipmentId);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable|null $exception
     * @return void
     */
    public function failed(\Throwable $exception = null): void
    {
        Log::error('ProcessLisQcResult permanently failed', [
            'qc_result_id'     => $this->qcResult->id,
            'bridge_config_id'  => $this->config->id,
            'error'             => $exception?->getMessage(),
        ]);
    }

    /**
     * Determine the queue for this job.
     *
     * @return string
     */
    public function queue(): string
    {
        return 'lis';
    }
}
