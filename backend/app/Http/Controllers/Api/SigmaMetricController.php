<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QcAnalyteSigmaConfig;
use App\Models\QcSigmaMetricResult;
use App\Models\TestParameter;
use App\Models\QualityControlTestParameter;
use App\Models\QualityControlResult;
use App\Services\SigmaMetricCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SigmaMetricController extends Controller
{
    protected $calculator;

    public function __construct(SigmaMetricCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Get the Sigma Monitor dashboard data - all analytes with their current Sigma metrics.
     */
    public function index(Request $request)
    {
        try {
            // Handle GET query parameters - convert string booleans to actual booleans
            $requestData = $request->all();
            if (isset($requestData['recalculate'])) {
                // Convert string "true"/"false" or "1"/"0" to boolean
                $recalculateValue = $requestData['recalculate'];
                if (is_string($recalculateValue)) {
                    $recalculateValue = in_array(strtolower($recalculateValue), ['true', '1', 'yes'], true);
                }
                $requestData['recalculate'] = (bool) $recalculateValue;
            }
            
            $validator = Validator::make($requestData, [
                'period_days' => 'sometimes|integer|min:1|max:365',
                'recalculate' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $periodDays = $requestData['period_days'] ?? config('sigma_metrics.default_period_days', 30);
            $recalculate = $requestData['recalculate'] ?? false;

            // Get all test parameters that have TE_a configured
            // Group by test_parameter_id to get only the most recent effective config per parameter
            $configs = QcAnalyteSigmaConfig::active()
                ->effectiveOn()
                ->with(['testParameter.test'])
                ->get()
                ->groupBy('test_parameter_id')
                ->map(function ($group) {
                    // Return the most recent config for each test parameter
                    return $group->sortByDesc('date_effective')->first();
                })
                ->values();

            $dashboardData = [];

            foreach ($configs as $config) {
                $testParameter = $config->testParameter;
                if (!$testParameter) {
                    continue;
                }

                // Get or calculate latest Sigma metric
                $latestMetric = $this->calculator->getLatestMetric($config->test_parameter_id);
                $calculationResult = null;
                
                // Recalculate if requested or if no metric exists
                if ($recalculate || !$latestMetric) {
                    $calculationResult = $this->calculator->calculateSigmaMetric(
                        $config->test_parameter_id,
                        $periodDays
                    );
                    
                    if ($calculationResult) {
                        $latestMetric = $this->calculator->getLatestMetric($config->test_parameter_id);
                    }
                }

                if ($latestMetric) {
                    $status = $this->getStatusColor($latestMetric->sigma_metric);
                    // Get data quality info
                    $dataQualityInfo = null;
                    // If we just calculated, use data from calculation result
                    if ($recalculate && $calculationResult) {
                        $dataQualityInfo = [
                            'total_results' => $calculationResult['total_results'] ?? null,
                            'valid_results' => $calculationResult['valid_results'] ?? null,
                            'invalid_results' => $calculationResult['invalid_results'] ?? null,
                        ];
                        
                        // Calculate invalid percentage if we have the data
                        if ($dataQualityInfo['total_results'] && $dataQualityInfo['total_results'] > 0) {
                            $dataQualityInfo['invalid_percentage'] = ($dataQualityInfo['invalid_results'] / $dataQualityInfo['total_results']) * 100;
                        }
                    }
                    else {
                        // For existing metrics, calculate data quality on-the-fly using the same period
                        $dataQualityInfo = $this->calculator->getDataQualityInfo(
                            $config->test_parameter_id,
                            $periodDays,
                            $latestMetric->calculation_date
                        );
                    }
                    
                    $dashboardData[] = [
                        'test_parameter_id' => $testParameter->id,
                        'analyte_name' => $testParameter->test_name ?? 'Unknown',
                        'test_name' => $testParameter->test->name ?? null,
                        'te_a' => $config->t_e_a,
                        'te_a_unit' => $config->te_a_unit,
                        'target_mean' => $latestMetric->target_mean,
                        'observed_mean' => $latestMetric->observed_mean,
                        'observed_sd' => $latestMetric->observed_std_dev,
                        'sigma_metric' => $latestMetric->sigma_metric,
                        'status' => $status,
                        'status_label' => $status['label'],
                        'last_calculation' => $latestMetric->calculation_date->format('Y-m-d H:i:s'),
                        'data_quality' => $dataQualityInfo,
                    ];
                }
                else {
                    // Include in dashboard even if calculation failed (for visibility)
                    // This helps users see which analytes are configured but don't have data yet
                    $dashboardData[] = [
                        'test_parameter_id' => $testParameter->id,
                        'analyte_name' => $testParameter->test_name ?? 'Unknown',
                        'test_name' => $testParameter->test->name ?? null,
                        'te_a' => $config->t_e_a,
                        'te_a_unit' => $config->te_a_unit,
                        'target_mean' => null,
                        'observed_mean' => null,
                        'observed_sd' => null,
                        'sigma_metric' => null,
                        'status' => ['color' => 'gray', 'label' => 'No Data'],
                        'status_label' => 'No Data',
                        'last_calculation' => null,
                        'data_quality' => null,
                        'error_message' => 'Calculation failed. Check if QC parameters are linked and QC results exist for the selected period.',
                    ];
                }
            }
            return response()->json([
                'data' => $dashboardData,
                'period_days' => $periodDays,
                'total_configs' => $configs->count(),
                'items_with_data' => collect($dashboardData)->whereNotNull('sigma_metric')->count(),
                'items_without_data' => collect($dashboardData)->whereNull('sigma_metric')->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An internal server error occurred.'], 500);
        }
    }

    /**
     * Calculate Sigma metric for a specific test parameter.
     */
    public function calculate(Request $request, TestParameter $testParameter)
    {
        $validator = Validator::make($request->all(), [
            'period_days' => 'sometimes|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $periodDays = $request->input('period_days', config('sigma_metrics.default_period_days', 30));
        $result = $this->calculator->calculateSigmaMetric($testParameter->id, $periodDays);

        if (!$result) {
            return response()->json([
                'message' => 'Failed to calculate Sigma metric. Check if TE_a is configured and sufficient QC data exists.',
            ], 400);
        }

        return response()->json([
            'message' => 'Sigma metric calculated successfully',
            'data' => $result,
        ]);
    }

    /**
     * Get historical trend data for a test parameter.
     */
    public function getTrend(TestParameter $testParameter, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $days = $request->input('days', config('sigma_metrics.default_trend_days', 90));
        $trendData = $this->calculator->getTrendData($testParameter->id, $days);

        return response()->json([
            'data' => $trendData->map(function ($result) {
                return [
                    'date' => $result->calculation_date->format('Y-m-d'),
                    'sigma_metric' => $result->sigma_metric,
                    'observed_mean' => $result->observed_mean,
                    'observed_std_dev' => $result->observed_std_dev,
                ];
            }),
            'days' => $days,
        ]);
    }

    /**
     * Calculate Sigma metrics for all configured analytes.
     */
    public function calculateAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_days' => 'sometimes|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $periodDays = $request->input('period_days', config('sigma_metrics.default_period_days', 30));
        $summary = $this->calculator->calculateAllMetrics($periodDays);

        return response()->json([
            'message' => 'Batch calculation completed',
            'summary' => $summary,
        ]);
    }

    /**
     * Get status color and label based on Sigma value.
     */
    protected function getStatusColor($sigmaMetric): array
    {
        if ($sigmaMetric === null) {
            return ['color' => 'gray', 'label' => 'No Data'];
        }

        $excellentThreshold = config('sigma_metrics.thresholds.excellent', 6.0);
        $goodThreshold = config('sigma_metrics.thresholds.good', 4.0);

        if ($sigmaMetric >= $excellentThreshold) {
            return ['color' => 'green', 'label' => 'Excellent'];
        } elseif ($sigmaMetric >= $goodThreshold) {
            return ['color' => 'yellow', 'label' => 'Good'];
        } else {
            return ['color' => 'red', 'label' => 'Critical'];
        }
    }

    /**
     * Manage TE_a configuration (CRUD operations).
     */

    /**
     * List all TE_a configurations.
     */
    public function getConfigs(Request $request)
    {
        $configs = QcAnalyteSigmaConfig::with(['testParameter.test'])
            ->orderBy('test_parameter_id')
            ->orderBy('date_effective', 'desc')
            ->get();

        return response()->json(['data' => $configs]);
    }

    /**
     * Get TE_a configuration for a specific test parameter.
     */
    public function getConfig(TestParameter $testParameter)
    {
        $config = QcAnalyteSigmaConfig::where('test_parameter_id', $testParameter->id)
            ->effectiveOn()
            ->first();

        return response()->json(['data' => $config]);
    }

    /**
     * Create or update TE_a configuration.
     */
    public function storeConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_parameter_id' => 'required|exists:test_parameters,id',
            't_e_a' => 'required|numeric|min:0',
            'te_a_unit' => 'required|string|max:10',
            'date_effective' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $config = QcAnalyteSigmaConfig::create([
            'test_parameter_id' => $request->test_parameter_id,
            't_e_a' => $request->t_e_a,
            'te_a_unit' => $request->te_a_unit,
            'date_effective' => $request->date_effective,
            'is_active' => $request->input('is_active', true),
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'TE_a configuration created successfully',
            'data' => $config->load('testParameter'),
        ], 201);
    }

    /**
     * Update TE_a configuration.
     */
    public function updateConfig(Request $request, QcAnalyteSigmaConfig $config)
    {
        $validator = Validator::make($request->all(), [
            't_e_a' => 'sometimes|numeric|min:0',
            'te_a_unit' => 'sometimes|string|max:10',
            'date_effective' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $config->fill($request->only(['t_e_a', 'te_a_unit', 'date_effective', 'is_active']));
        $config->updated_by = Auth::id();
        $config->save();

        return response()->json([
            'message' => 'TE_a configuration updated successfully',
            'data' => $config->load('testParameter'),
        ]);
    }

    /**
     * Delete TE_a configuration (soft delete).
     */
    public function deleteConfig(QcAnalyteSigmaConfig $config)
    {
        $config->delete();

        return response()->json([
            'message' => 'TE_a configuration deleted successfully',
        ], 204);
    }

    /**
     * Diagnostic endpoint to check why Sigma metrics might not be showing data.
     */
    public function diagnostics(Request $request)
    {
        $periodDays = $request->input('period_days', config('sigma_metrics.default_period_days', 30));
        $startDate = now()->subDays($periodDays);
        
        $diagnostics = [
            'te_a_configs' => [
                'total' => QcAnalyteSigmaConfig::count(),
                'active' => QcAnalyteSigmaConfig::active()->count(),
                'effective' => QcAnalyteSigmaConfig::active()->effectiveOn()->count(),
            ],
            'test_parameters' => [
                'total' => TestParameter::count(),
                'with_te_a' => QcAnalyteSigmaConfig::active()->effectiveOn()->distinct('test_parameter_id')->count(),
            ],
            'qc_parameters' => [
                'total' => QualityControlTestParameter::count(),
                'with_results' => QualityControlTestParameter::whereHas('qualityControlResults', function($q) use ($startDate) {
                    $q->whereBetween('run_date', [$startDate->format('Y-m-d'), now()->format('Y-m-d')]);
                })->distinct('test_parameter_id')->count(),
            ],
            'qc_results' => [
                'total' => QualityControlResult::count(),
                'in_period' => QualityControlResult::whereBetween('run_date', [$startDate->format('Y-m-d'), now()->format('Y-m-d')])->count(),
                'valid_in_period' => QualityControlResult::whereBetween('run_date', [$startDate->format('Y-m-d'), now()->format('Y-m-d')])
                    ->where('is_valid', true)->count(),
            ],
            'sigma_results' => [
                'total' => QcSigmaMetricResult::count(),
                'recent' => QcSigmaMetricResult::where('calculation_date', '>=', $startDate)->count(),
            ],
            'sample_configs' => QcAnalyteSigmaConfig::active()->effectiveOn()->with('testParameter')->limit(5)->get()->map(function($config) {
                $qcParams = QualityControlTestParameter::where('test_parameter_id', $config->test_parameter_id)->count();
                $qcResults = QualityControlResult::whereIn('quality_control_test_parameter_id', 
                    QualityControlTestParameter::where('test_parameter_id', $config->test_parameter_id)->pluck('id')
                )->whereBetween('run_date', [$startDate->format('Y-m-d'), now()->format('Y-m-d')])->count();
                return [
                    'test_parameter_id' => $config->test_parameter_id,
                    'test_name' => $config->testParameter->test_name ?? 'N/A',
                    'te_a' => $config->t_e_a,
                    'qc_parameters_linked' => $qcParams,
                    'qc_results_in_period' => $qcResults,
                ];
            }),
        ];

        return response()->json($diagnostics);
    }
}
