<?php

namespace App\Services;

use App\Models\QcAnalyteSigmaConfig;
use App\Models\QcSigmaMetricResult;
use App\Models\QualityControlResult;
use App\Models\QualityControlTestParameter;
use App\Models\TestParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for calculating Six Sigma metrics for quality control monitoring.
 * Implements the formula: Σ = (TE_a - |x̄ - μ|) / s
 * Where:
 * - TE_a = Total Allowable Error
 * - x̄ = Observed Mean (from rolling QC data)
 * - μ = Target Mean (from QC configuration)
 * - s = Observed Standard Deviation (from rolling QC data)
 */
class SigmaMetricCalculator
{
    /**
     * Calculate Sigma metric for a specific test parameter (analyte).
     * 
     * @param int $testParameterId The test parameter ID (analyte)
     * @param int|null $periodDays Number of days to look back for rolling QC data (defaults to config)
     * @param Carbon|null $calculationDate Optional date for calculation (defaults to now)
     * @return array|false Returns calculation result or false on failure
     */
    public function calculateSigmaMetric(int $testParameterId, ?int $periodDays = null, ?Carbon $calculationDate = null): array|false
    {
        $calculationDate = $calculationDate ?? now();
        $periodDays = $periodDays ?? config('sigma_metrics.default_period_days', 30);
        $minDataPoints = config('sigma_metrics.min_data_points', 2);
        $highSigmaValue = config('sigma_metrics.high_sigma_value', 99.99);

        try {
            // 1. Fetch TE_a from config table
            $config = QcAnalyteSigmaConfig::getTEaForParameter($testParameterId, $calculationDate);
            if (!$config) {
                Log::warning("No TE_a configuration found for test parameter {$testParameterId}");
                return false;
            }

            $teA = (float) $config->t_e_a;

            // 2. Fetch rolling QC performance data for the specified period
            $startDate = $calculationDate->copy()->subDays($periodDays);
            $qcData = $this->getRollingQCData($testParameterId, $startDate, $calculationDate);

            if ($qcData['count'] < $minDataPoints) {
                Log::warning("Insufficient QC data (count: {$qcData['count']}, required: {$minDataPoints}) for test parameter {$testParameterId}");
                return false;
            }
            
            // 3. Check data quality - warn if high invalid rate but proceed with calculation
            if ($qcData['total_results'] > 0) {
                $invalidPercentage = ($qcData['invalid_results'] / $qcData['total_results']) * 100;
                if ($invalidPercentage > 30) {
                    Log::warning("High invalid QC result rate ({$invalidPercentage}%) for test parameter {$testParameterId}. Calculation proceeds with {$qcData['valid_results']} valid results out of {$qcData['total_results']} total.");
                }
            }

            // 4. Extract core statistics
            $targetMean = $qcData['target_mean']; // μ (target mean from QC configuration)
            $observedMean = $qcData['observed_mean']; // x̄ (observed mean from rolling data)
            $observedStdDev = $qcData['observed_std_dev']; // s (observed standard deviation)

            // 5. Check for division by zero (s=0, perfect but impossible QC)
            if ($observedStdDev == 0) {
                Log::warning("Zero standard deviation for test parameter {$testParameterId} - perfect QC");
                // Return high value from configuration
                $sigmaMetric = $highSigmaValue;
            } else {
                // 6. Apply the Sigma Metric formula: Σ = (TE_a - |x̄ - μ|) / s
                $bias = abs($observedMean - $targetMean);
                $sigmaMetric = ($teA - $bias) / $observedStdDev;
                $sigmaMetric = round($sigmaMetric, 4);
            }

            // 7. Store result for trending and alerting
            $result = QcSigmaMetricResult::create([
                'test_parameter_id' => $testParameterId,
                'sigma_metric' => $sigmaMetric,
                'calculation_date' => $calculationDate,
                'observed_mean' => $observedMean,
                'observed_std_dev' => $observedStdDev,
                'target_mean' => $targetMean,
                'te_a' => $teA,
                'period_days' => $periodDays,
            ]);

            // 8. Trigger Alert Check
            $criticalThreshold = config('sigma_metrics.thresholds.critical', 4.0);
            if ($sigmaMetric < $criticalThreshold) {
                // TODO: Integrate with alerting service when available
                Log::critical("Sigma Drop Alert: Test Parameter {$testParameterId} has Sigma value {$sigmaMetric} (below {$criticalThreshold} threshold)");
            }

            return [
                'sigma_metric' => $sigmaMetric,
                'observed_mean' => $observedMean,
                'observed_std_dev' => $observedStdDev,
                'target_mean' => $targetMean,
                'te_a' => $teA,
                'bias' => $bias,
                'period_days' => $periodDays,
                'qc_data_count' => $qcData['count'],
                'total_results' => $qcData['total_results'] ?? 0,
                'valid_results' => $qcData['valid_results'] ?? $qcData['count'],
                'invalid_results' => $qcData['invalid_results'] ?? 0,
                'result_id' => $result->id,
            ];
        } catch (\Exception $e) {
            Log::error("Error calculating Sigma metric for test parameter {$testParameterId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get rolling QC data for a test parameter within a date range.
     * Aggregates results across all QC lots/analyzers for the same test parameter.
     * 
     * @param int $testParameterId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array ['target_mean', 'observed_mean', 'observed_std_dev', 'count']
     */
    protected function getRollingQCData(int $testParameterId, Carbon $startDate, Carbon $endDate): array
    {
        // Find all QC test parameters that match this test parameter ID
        $qcTestParameterIds = QualityControlTestParameter::where('test_parameter_id', $testParameterId)
            ->pluck('id');

        if ($qcTestParameterIds->isEmpty()) {
            Log::warning("No QC test parameters found for test parameter {$testParameterId}. Please ensure test parameters are linked to quality controls via the Quality Control Management page.");
            return [
                'target_mean' => 0,
                'observed_mean' => 0,
                'observed_std_dev' => 0,
                'count' => 0,
                'total_results' => 0,
                'valid_results' => 0,
                'invalid_results' => 0,
            ];
        }
        
        Log::info("Found {$qcTestParameterIds->count()} QC test parameter(s) for test parameter {$testParameterId}");

        // Get ALL QC results (both valid and invalid) for data quality assessment
        $allResults = QualityControlResult::whereIn('quality_control_test_parameter_id', $qcTestParameterIds)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('run_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->get();

        $totalResults = $allResults->count();
        $validResultsCount = $allResults->where('is_valid', true)->count();
        $invalidResultsCount = $totalResults - $validResultsCount;
        
        Log::info("QC Results for test parameter {$testParameterId} (period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}): Total={$totalResults}, Valid={$validResultsCount}, Invalid={$invalidResultsCount}");
        
        // Log data quality metrics
        if ($invalidResultsCount > 0) {
            $invalidPercentage = ($invalidResultsCount / $totalResults) * 100;
            Log::info("Sigma calculation data quality for test parameter {$testParameterId}: Total={$totalResults}, Valid={$validResultsCount}, Invalid={$invalidResultsCount} ({$invalidPercentage}%)");
            
            // Warn if more than 20% of results are invalid
            if ($invalidPercentage > 20) {
                Log::warning("High invalid QC result rate ({$invalidPercentage}%) for test parameter {$testParameterId} in period {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
            }
        }

        // Only use valid QC results for calculation
        $results = $allResults
            ->where('is_valid', true)
            ->sortBy(function($result) {
                return $result->run_date . ' ' . $result->run_time;
            })
            ->pluck('result_value')
            ->map(fn($v) => (float) $v)
            ->values();

        $count = $results->count();

        if ($count === 0) {
            // Try to get target mean from QC configuration as fallback
            $qcTestParameter = QualityControlTestParameter::where('test_parameter_id', $testParameterId)
                ->orderBy('id', 'desc')
                ->first();
            
            $targetMean = $qcTestParameter ? (float) $qcTestParameter->target : 0;

            return [
                'target_mean' => $targetMean,
                'observed_mean' => 0,
                'observed_std_dev' => 0,
                'count' => 0,
                'total_results' => $totalResults,
                'valid_results' => $validResultsCount,
                'invalid_results' => $invalidResultsCount,
            ];
        }

        // Calculate observed mean
        $observedMean = $results->avg();

        // Calculate observed standard deviation (sample SD with n-1 denominator)
        $variance = 0.0;
        if ($count > 1) {
            $variance = $results->reduce(function ($carry, $v) use ($observedMean) {
                $diff = $v - $observedMean;
                return $carry + ($diff * $diff);
            }, 0.0) / ($count - 1);
        }
        $observedStdDev = $count > 1 ? sqrt($variance) : 0.0;

        // Get target mean from QC configuration (use the most recent QC test parameter's target)
        $qcTestParameter = QualityControlTestParameter::where('test_parameter_id', $testParameterId)
            ->orderBy('id', 'desc')
            ->first();
        
        $targetMean = $qcTestParameter ? (float) $qcTestParameter->target : $observedMean;

        return [
            'target_mean' => $targetMean,
            'observed_mean' => $observedMean,
            'observed_std_dev' => $observedStdDev,
            'count' => $count,
            'total_results' => $totalResults,
            'valid_results' => $validResultsCount,
            'invalid_results' => $invalidResultsCount,
        ];
    }

    /**
     * Calculate Sigma metrics for all active test parameters that have TE_a configured.
     * 
     * @param int|null $periodDays Number of days to look back for rolling QC data (defaults to config)
     * @return array Summary of calculations
     */
    public function calculateAllMetrics(?int $periodDays = null): array
    {
        $periodDays = $periodDays ?? config('sigma_metrics.default_period_days', 30);
        
        $summary = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get all test parameters that have active TE_a configurations
        $configs = QcAnalyteSigmaConfig::active()
            ->effectiveOn()
            ->with('testParameter')
            ->get();

        $summary['total'] = $configs->count();

        foreach ($configs as $config) {
            $result = $this->calculateSigmaMetric($config->test_parameter_id, $periodDays);
            if ($result) {
                $summary['successful']++;
            } else {
                $summary['failed']++;
                $summary['errors'][] = "Failed to calculate for test parameter ID: {$config->test_parameter_id}";
            }
        }

        return $summary;
    }

    /**
     * Get the latest Sigma metric for a test parameter.
     * 
     * @param int $testParameterId
     * @return QcSigmaMetricResult|null
     */
    public function getLatestMetric(int $testParameterId): ?QcSigmaMetricResult
    {
        return QcSigmaMetricResult::forParameter($testParameterId)
            ->latest()
            ->first();
    }

    /**
     * Get historical trend data for a test parameter.
     * 
     * @param int $testParameterId
     * @param int|null $days Number of days to look back (defaults to config)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrendData(int $testParameterId, ?int $days = null)
    {
        $days = $days ?? config('sigma_metrics.default_trend_days', 90);
        $startDate = now()->subDays($days);
        return QcSigmaMetricResult::forParameter($testParameterId)
            ->inDateRange($startDate)
            ->orderBy('calculation_date', 'asc')
            ->get();
    }

    /**
     * Get data quality metrics for a test parameter without calculating Sigma metric.
     * Useful for displaying data quality info for existing metrics.
     * 
     * @param int $testParameterId
     * @param int|null $periodDays Number of days to look back (defaults to config)
     * @param Carbon|null $calculationDate Optional date for calculation (defaults to now)
     * @return array|null Returns data quality info or null if insufficient data
     */
    public function getDataQualityInfo(int $testParameterId, ?int $periodDays = null, ?Carbon $calculationDate = null): ?array
    {
        $calculationDate = $calculationDate ?? now();
        $periodDays = $periodDays ?? config('sigma_metrics.default_period_days', 30);
        
        $startDate = $calculationDate->copy()->subDays($periodDays);
        $qcData = $this->getRollingQCData($testParameterId, $startDate, $calculationDate);
        
        if ($qcData['total_results'] === 0) {
            return null;
        }
        
        $invalidPercentage = $qcData['total_results'] > 0 
            ? ($qcData['invalid_results'] / $qcData['total_results']) * 100 
            : 0;
        
        return [
            'total_results' => $qcData['total_results'],
            'valid_results' => $qcData['valid_results'],
            'invalid_results' => $qcData['invalid_results'],
            'invalid_percentage' => round($invalidPercentage, 2),
        ];
    }
}

