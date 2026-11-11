<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaboratoryRequest;
use App\Models\TestCategory;
use App\Models\TestResult;
use App\Models\PatientType;
use App\Models\Test;
use App\Services\WorksheetColumnService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManagementReportController extends Controller
{
    /**
     * Get worksheet data for management report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWorksheetData(Request $request): JsonResponse
    {
        try {
            $sectionId = $request->input('section_id');
            $patientType = $request->input('patient_type');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            if (!$sectionId) {
                return response()->json([
                    'message' => 'Section ID is required'
                ], 400);
            }

            // Build the base query
            $query = LaboratoryRequest::with([
                'patient:id,last_name,first_name,middle_name,date_of_birth,gender',
                'patientType:id,name',
                'ward:id,name',
                'doctor:id,name',
                'creator:id,name',
                'tests' => function ($testQuery) use ($sectionId) {
                    $testQuery->where('test_category_id', $sectionId)
                              ->whereIn('laboratory_request_test.item_status', [
                                  'Accepted', 'Checked-in', 'Processing Results', 
                                  'Result Validated', 'Result Printed'
                              ]);
                },
                'results' => function ($resultQuery) use ($sectionId) {
                    $resultQuery->whereHas('test', function ($q) use ($sectionId) {
                        $q->where('test_category_id', $sectionId);
                    })->with(['test', 'testParameter', 'performer', 'verifier']);
                }
            ]);

            // Apply date filters
            if ($dateFrom) {
                $query->where('created_at', '>=', Carbon::parse($dateFrom));
            }
            if ($dateTo) {
                $query->where('created_at', '<=', Carbon::parse($dateTo));
            }

            // Apply patient type filter
            if ($patientType) {
                $query->where('patient_type_id', $patientType);
            }

            // Only get requests that have tests in the selected section
            $query->whereHas('tests', function ($q) use ($sectionId) {
                $q->where('test_category_id', $sectionId);
            });

            $laboratoryRequests = $query->orderBy('created_at', 'desc')->get();

            // Transform the data for the worksheet
            $worksheetData = $this->transformWorksheetData($laboratoryRequests, $sectionId);

            return response()->json([
                'data' => $worksheetData,
                'total' => count($worksheetData)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching worksheet data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform laboratory requests data for worksheet display
     *
     * @param \Illuminate\Database\Eloquent\Collection $laboratoryRequests
     * @param int $sectionId
     * @return array
     */
    private function transformWorksheetData($laboratoryRequests, $sectionId): array
    {
        $worksheetData = [];

        foreach ($laboratoryRequests as $request) {
            // Get the first test result for this request in the selected section
            // Eager-loaded 'results' may not include parameter relation consistently.
            // Ensure we pull results scoped to the section with parameter relation available.
            $testResults = $request->results
                ->filter(function ($res) use ($sectionId) {
                    return optional($res->test)->test_category_id == $sectionId;
                });
            
            // Show all requests even if they don't have results yet
            // This allows us to see pending tests in the worksheet

            // Calculate age
            $age = $request->patient->date_of_birth 
                ? Carbon::parse($request->patient->date_of_birth)->age 
                : null;

            // Get performer and verifier from test results or pivot table
            $performer = $testResults->first()?->performer?->name ?? 
                        $request->tests->first()?->pivot?->perform_by ?? 'N/A';
            $verifier = $testResults->first()?->verifier?->name ?? 
                       $request->tests->first()?->pivot?->verify_by ?? 'N/A';

            // Get collection and release dates from pivot table
            $firstTest = $request->tests->first();
            $collectedAt = $firstTest?->pivot?->collected_at 
                ? Carbon::parse($firstTest->pivot->collected_at)->format('Y-m-d H:i:s')
                : null;
            $releasedAt = $firstTest?->pivot?->verify_at 
                ? Carbon::parse($firstTest->pivot->verify_at)->format('Y-m-d H:i:s')
                : null;

            $row = [
                'id' => $request->id,
                'sample_id' => $request->sample_id ?? 'N/A',
                'patient_id' => $request->patient->id ?? 'N/A',
                'patient_name' => $this->formatPatientName($request->patient),
                'date_of_birth' => $request->patient->date_of_birth 
                    ? Carbon::parse($request->patient->date_of_birth)->format('m/d/Y')
                    : 'N/A',
                'sex' => $request->patient->gender ?? 'N/A',
                'age' => $age,
                'requestor' => $request->doctor?->name ?? 'N/A',
                'med_tech' => $performer,
                'verified_by' => $verifier,
                'date_collected' => $collectedAt,
                'date_released' => $releasedAt,
                'patient_type' => $request->patientType?->name ?? 'N/A',
                'ward' => $request->ward?->name ?? 'N/A',
            ];

            // Add dynamic test result columns based on section
            $row = array_merge($row, $this->getTestResultColumns($testResults, $sectionId));

            $worksheetData[] = $row;
        }

        return $worksheetData;
    }

    /**
     * Get test result columns based on section type using dynamic service
     *
     * @param \Illuminate\Database\Eloquent\Collection $testResults
     * @param int $sectionId
     * @return array
     */
    private function getTestResultColumns($testResults, $sectionId): array
    {
        $columnService = new WorksheetColumnService();
        return $columnService->getTestResultColumns($sectionId, $testResults);
    }

    /**
     * Format patient name for display
     *
     * @param \App\Models\Patient $patient
     * @return string
     */
    private function formatPatientName($patient): string
    {
        if (!$patient) {
            return 'N/A';
        }

        $name = trim(($patient->last_name ?? '') . ', ' . ($patient->first_name ?? ''));
        if ($patient->middle_name) {
            $name .= ' ' . substr($patient->middle_name, 0, 1) . '.';
        }

        return $name ?: 'N/A';
    }



    /**
     * Get dynamic columns for a test category
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getColumnMetadata(Request $request): JsonResponse
    {
        try {
            $categoryId = $request->input('category_id');
            
            if (!$categoryId) {
                return response()->json([
                    'message' => 'Category ID is required'
                ], 400);
            }

            $columnService = new WorksheetColumnService();
            $metadata = $columnService->getColumnMetadata($categoryId);

            return response()->json($metadata);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching column metadata',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get census data for management report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCensusData(Request $request): JsonResponse
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));
            $testCategoryId = $request->input('test_category_id');

            // Validate inputs
            if (!$year || !$month) {
                return response()->json([
                    'message' => 'Year and month are required'
                ], 400);
            }

            // Create date range for the selected month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Build the base query for laboratory requests with "Result Printed" status
            $query = LaboratoryRequest::with([
                'tests' => function ($testQuery) use ($testCategoryId) {
                    $testQuery->where('laboratory_request_test.item_status', 'Result Printed');
                    if ($testCategoryId) {
                        $testQuery->where('test_category_id', $testCategoryId);
                    }
                },
                'results' => function ($resultQuery) use ($testCategoryId) {
                    $resultQuery->with(['test', 'testParameter']);
                    if ($testCategoryId) {
                        $resultQuery->whereHas('test', function ($q) use ($testCategoryId) {
                            $q->where('test_category_id', $testCategoryId);
                        });
                    }
                }
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('tests', function ($q) use ($testCategoryId) {
                $q->where('laboratory_request_test.item_status', 'Result Printed');
                if ($testCategoryId) {
                    $q->where('test_category_id', $testCategoryId);
                }
            });

            $laboratoryRequests = $query->get();

            // Process the data to create census report
            $censusData = $this->processCensusData($laboratoryRequests, $year, $month);

            return response()->json([
                'data' => $censusData,
                'meta' => [
                    'year' => $year,
                    'month' => $month,
                    'test_category_id' => $testCategoryId,
                    'total_tests' => count($censusData),
                    'days_in_month' => $endDate->day
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching census data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process laboratory requests data to create census report
     *
     * @param \Illuminate\Database\Eloquent\Collection $laboratoryRequests
     * @param int $year
     * @param int $month
     * @return array
     */
    private function processCensusData($laboratoryRequests, $year, $month): array
    {
        $censusData = [];
        $testCounts = [];

        // Initialize day columns for the month
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $dayColumns = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayColumns[$day] = 0;
        }

        foreach ($laboratoryRequests as $request) {
            $requestDate = Carbon::parse($request->created_at);
            $dayOfMonth = $requestDate->day;

            // Process each test in the request
            foreach ($request->tests as $test) {
                $testId = $test->id;
                $testName = $test->name;

                // Initialize test data if not exists
                if (!isset($testCounts[$testId])) {
                    $testCounts[$testId] = [
                        'test_id' => $testId,
                        'test_name' => $testName,
                        'test_month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                        'test_year' => $year,
                        'total' => 0,
                        'days' => $dayColumns
                    ];
                }

                // Increment count for this day
                $testCounts[$testId]['days'][$dayOfMonth]++;
                $testCounts[$testId]['total']++;
            }
        }

        // Convert to array format for frontend
        foreach ($testCounts as $testData) {
            $censusData[] = $testData;
        }

        // Sort by test name
        usort($censusData, function ($a, $b) {
            return strcmp($a['test_name'], $b['test_name']);
        });

        return $censusData;
    }

    /**
     * Get test categories for census report filter
     *
     * @return JsonResponse
     */
    public function getTestCategories(): JsonResponse
    {
        try {
            $categories = TestCategory::select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching test categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Turn Around Time (TAT) data for management report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTATData(Request $request): JsonResponse
    {
        try {
            $sectionId = $request->input('section_id');
            $patientType = $request->input('patient_type');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $groupBy = $request->input('group_by', 'test'); // test, day, week, month

            if (!$sectionId) {
                return response()->json([
                    'message' => 'Section ID is required'
                ], 400);
            }

            // Build the base query for completed tests only
            $query = LaboratoryRequest::with([
                'patient:id,last_name,first_name,middle_name,date_of_birth,gender',
                'patientType:id,name',
                'ward:id,name',
                'doctor:id,name',
                'tests' => function ($testQuery) use ($sectionId) {
                    $testQuery->where('test_category_id', $sectionId)
                              ->where('laboratory_request_test.item_status', 'Result Printed')
                              ->withPivot('collected_at', 'checkin_at', 'perform_at', 'verify_at', 'item_status');
                }
            ]);

            // Apply date filters
            if ($dateFrom) {
                $query->where('created_at', '>=', Carbon::parse($dateFrom));
            }
            if ($dateTo) {
                $query->where('created_at', '<=', Carbon::parse($dateTo));
            }

            // Apply patient type filter
            if ($patientType) {
                $query->where('patient_type_id', $patientType);
            }

            // Only get requests that have completed tests in the selected section
            $query->whereHas('tests', function ($q) use ($sectionId) {
                $q->where('test_category_id', $sectionId)
                  ->where('laboratory_request_test.item_status', 'Result Printed');
            });

            $laboratoryRequests = $query->orderBy('created_at', 'desc')->get();

            // Calculate TAT data
            $tatData = $this->calculateTATData($laboratoryRequests, $sectionId, $groupBy);

            // Debug logging removed - data structure confirmed correct

            return response()->json([
                'data' => $tatData,
                'meta' => [
                    'section_id' => $sectionId,
                    'patient_type' => $patientType,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'group_by' => $groupBy,
                    'total_tests' => count($tatData)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching TAT data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate TAT data from laboratory requests
     *
     * @param \Illuminate\Database\Eloquent\Collection $laboratoryRequests
     * @param int $sectionId
     * @param string $groupBy
     * @return array
     */
    private function calculateTATData($laboratoryRequests, $sectionId, $groupBy): array
    {
        $tatData = [];
        $testTATs = [];

        foreach ($laboratoryRequests as $request) {
            foreach ($request->tests as $test) {
                if ($test->test_category_id != $sectionId || $test->pivot->item_status !== 'Result Printed') {
                    continue;
                }

                // Calculate different TAT metrics
                $requestTime = Carbon::parse($request->created_at);
                $collectedTime = $test->pivot->collected_at ? Carbon::parse($test->pivot->collected_at) : null;
                $checkinTime = $test->pivot->checkin_at ? Carbon::parse($test->pivot->checkin_at) : null;
                $performTime = $test->pivot->perform_at ? Carbon::parse($test->pivot->perform_at) : null;
                $verifyTime = $test->pivot->verify_at ? Carbon::parse($test->pivot->verify_at) : null;

                // Calculate TAT in minutes
                $totalTAT = $verifyTime ? $requestTime->diffInMinutes($verifyTime) : null;
                $collectionTAT = $collectedTime ? $requestTime->diffInMinutes($collectedTime) : null;
                $processingTAT = $collectedTime && $verifyTime ? $collectedTime->diffInMinutes($verifyTime) : null;
                $verificationTAT = $performTime && $verifyTime ? $performTime->diffInMinutes($verifyTime) : null;

                $testTAT = [
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'test_category_id' => $test->test_category_id,
                    'laboratory_request_id' => $request->id,
                    'patient_id' => $request->patient_id,
                    'patient_name' => $this->formatPatientName($request->patient),
                    'patient_type' => $request->patientType?->name ?? 'N/A',
                    'ward' => $request->ward?->name ?? 'N/A',
                    'doctor' => $request->doctor?->name ?? 'N/A',
                    'request_date' => $requestTime->format('Y-m-d H:i:s'),
                    'collected_at' => $collectedTime?->format('Y-m-d H:i:s'),
                    'checkin_at' => $checkinTime?->format('Y-m-d H:i:s'),
                    'perform_at' => $performTime?->format('Y-m-d H:i:s'),
                    'verify_at' => $verifyTime?->format('Y-m-d H:i:s'),
                    'total_tat_minutes' => $totalTAT,
                    'total_tat_hours' => $totalTAT ? round($totalTAT / 60, 2) : null,
                    'collection_tat_minutes' => $collectionTAT,
                    'processing_tat_minutes' => $processingTAT,
                    'verification_tat_minutes' => $verificationTAT,
                    'status' => $test->pivot->item_status,
                    'group_key' => $this->getGroupKey($groupBy, $requestTime, $test),
                    'target_tat_minutes' => $test->turnaround_time_minutes,
                    'target_tat_hours' => $test->turnaround_time_minutes ? round($test->turnaround_time_minutes / 60, 2) : null,
                    'tat_performance' => $this->calculateTATPerformance($totalTAT, $test->turnaround_time_minutes)
                ];

                $testTATs[] = $testTAT;
            }
        }

        // Group data based on group_by parameter
        $groupedData = $this->groupTATData($testTATs, $groupBy);

        return $groupedData;
    }

    /**
     * Get group key for TAT data grouping
     *
     * @param string $groupBy
     * @param Carbon $requestTime
     * @param Test $test
     * @return string
     */
    private function getGroupKey($groupBy, $requestTime, $test): string
    {
        switch ($groupBy) {
            case 'day':
                return $requestTime->format('Y-m-d');
            case 'week':
                return $requestTime->format('Y-W');
            case 'month':
                return $requestTime->format('Y-m');
            case 'test':
            default:
                return $test->name;
        }
    }

    /**
     * Group TAT data and calculate statistics
     *
     * @param array $testTATs
     * @param string $groupBy
     * @return array
     */
    private function groupTATData($testTATs, $groupBy): array
    {
        $grouped = [];
        
        foreach ($testTATs as $tat) {
            $key = $tat['group_key'];
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'group_key' => $key,
                    'group_name' => $this->getGroupName($groupBy, $key),
                    'tests' => [],
                    'tat_minutes' => [],
                    'tat_hours' => [],
                    'collection_tat_minutes' => [],
                    'processing_tat_minutes' => [],
                    'verification_tat_minutes' => [],
                    'count' => 0
                ];
            }
            
            $grouped[$key]['tests'][] = $tat;
            $grouped[$key]['count']++;
            
            if ($tat['total_tat_minutes'] !== null) {
                $grouped[$key]['tat_minutes'][] = $tat['total_tat_minutes'];
                $grouped[$key]['tat_hours'][] = $tat['total_tat_hours'];
            }
            
            if ($tat['collection_tat_minutes'] !== null) {
                $grouped[$key]['collection_tat_minutes'][] = $tat['collection_tat_minutes'];
            }
            
            if ($tat['processing_tat_minutes'] !== null) {
                $grouped[$key]['processing_tat_minutes'][] = $tat['processing_tat_minutes'];
            }
            
            if ($tat['verification_tat_minutes'] !== null) {
                $grouped[$key]['verification_tat_minutes'][] = $tat['verification_tat_minutes'];
            }
        }

        // Calculate statistics for each group
        $result = [];
        foreach ($grouped as $group) {
            $result[] = $this->calculateTATStatistics($group);
        }

        // Sort by group name
        usort($result, function ($a, $b) {
            return strcmp($a['group_name'], $b['group_name']);
        });

        return $result;
    }

    /**
     * Get group name for display
     *
     * @param string $groupBy
     * @param string $key
     * @return string
     */
    private function getGroupName($groupBy, $key): string
    {
        switch ($groupBy) {
            case 'day':
                return Carbon::parse($key)->format('M d, Y');
            case 'week':
                return 'Week ' . substr($key, -2) . ', ' . substr($key, 0, 4);
            case 'month':
                return Carbon::parse($key . '-01')->format('M Y');
            case 'test':
            default:
                return $key;
        }
    }

    /**
     * Calculate TAT statistics for a group
     *
     * @param array $group
     * @return array
     */
    private function calculateTATStatistics($group): array
    {
        $tatMinutes = $group['tat_minutes'];
        $tatHours = $group['tat_hours'];
        
        $stats = [
            'group_key' => $group['group_key'],
            'group_name' => $group['group_name'],
            'count' => $group['count'],
            'total_tests' => count($group['tests']),
            'tests' => $group['tests'] // Preserve the tests array for detailed view
        ];

        if (!empty($tatMinutes)) {
            $stats['tat_statistics'] = [
                'mean_minutes' => round(array_sum($tatMinutes) / count($tatMinutes), 2),
                'mean_hours' => round(array_sum($tatHours) / count($tatHours), 2),
                'median_minutes' => $this->calculateMedian($tatMinutes),
                'min_minutes' => min($tatMinutes),
                'max_minutes' => max($tatMinutes),
                'std_dev_minutes' => $this->calculateStandardDeviation($tatMinutes),
                'p95_minutes' => $this->calculatePercentile($tatMinutes, 95),
                'p99_minutes' => $this->calculatePercentile($tatMinutes, 99)
            ];
        }

        // Calculate 6 Sigma metrics with test-specific targets
        if (!empty($tatMinutes)) {
            $testTargets = [];
            foreach ($group['tests'] as $test) {
                if (isset($test['target_tat_minutes']) && $test['target_tat_minutes']) {
                    $testTargets[] = $test['target_tat_minutes'];
                }
            }
            
            // Debug logging removed - test targets confirmed working
            
            $stats['six_sigma'] = $this->calculateSixSigmaMetrics($tatMinutes, $testTargets);
        }

        return $stats;
    }

    /**
     * Calculate median value
     *
     * @param array $values
     * @return float
     */
    private function calculateMedian($values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor(($count - 1) / 2);
        
        if ($count % 2) {
            return $values[$middle];
        } else {
            return ($values[$middle] + $values[$middle + 1]) / 2;
        }
    }

    /**
     * Calculate standard deviation
     *
     * @param array $values
     * @return float
     */
    private function calculateStandardDeviation($values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        
        return round(sqrt($variance), 2);
    }

    /**
     * Calculate percentile
     *
     * @param array $values
     * @param int $percentile
     * @return float
     */
    private function calculatePercentile($values, $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        } else {
            $lower = $values[floor($index)];
            $upper = $values[ceil($index)];
            return $lower + ($upper - $lower) * ($index - floor($index));
        }
    }

    /**
     * Calculate 6 Sigma metrics with outlier detection and proper statistical methods
     *
     * @param array $tatMinutes
     * @param array $testTargets (optional) - array of test-specific targets
     * @return array
     */
    private function calculateSixSigmaMetrics($tatMinutes, $testTargets = []): array
    {
        // Check minimum sample size for reliable analysis
        if (count($tatMinutes) < 3) {
            return [
                'sigma_level' => 'Insufficient Data',
                'target_tat_minutes' => !empty($testTargets) ? array_sum($testTargets) / count($testTargets) : 0,
                'standard_deviation' => 0,
                'defects_per_million' => 'N/A',
                'yield_percentage' => 'N/A',
                'process_capability' => 'Cannot Determine',
                'process_capability_index' => 'N/A',
                'recommendations' => ['Collect at least 3 data points for reliable statistical analysis'],
                'uses_test_specific_targets' => !empty($testTargets),
                'mean_tat_minutes' => array_sum($tatMinutes) / count($tatMinutes),
                'defect_count' => 0,
                'total_tests' => count($tatMinutes),
                'upper_control_limit' => 'N/A',
                'lower_control_limit' => 'N/A',
                'outliers_detected' => 0,
                'outliers_removed' => false
            ];
        }

        // Detect and handle outliers
        $outlierInfo = $this->detectAndHandleOutliers($tatMinutes, $testTargets);
        $cleanedTAT = $outlierInfo['cleaned_data'];
        $outliersRemoved = $outlierInfo['outliers_removed'];
        
        // Recalculate statistics with cleaned data
        $mean = array_sum($cleanedTAT) / count($cleanedTAT);
        $stdDev = $this->calculateStandardDeviation($cleanedTAT);
        
        // Use test-specific targets if available, otherwise use mean
        $targetTAT = !empty($testTargets) ? array_sum($testTargets) / count($testTargets) : $mean;
        
        // Use industry-standard control limits instead of sample-based limits
        $defects = $this->calculateDefectsWithFixedLimits($cleanedTAT, $testTargets, $targetTAT);
        
        $dpm = count($cleanedTAT) > 0 ? ($defects / count($cleanedTAT)) * 1000000 : 0;
        
        // Calculate yield percentage
        $yield = count($cleanedTAT) > 0 ? (1 - $defects / count($cleanedTAT)) * 100 : 100;
        
        // Calculate sigma level from DPMO (proper 6 Sigma method)
        $sigmaLevel = $this->calculateSigmaLevelFromDPMO($dpm);
        
        // Calculate process capability index (Cp) with fixed limits
        $processCapabilityIndex = $this->calculateProcessCapabilityIndex($cleanedTAT, $targetTAT);
        
        return [
            'sigma_level' => round($sigmaLevel, 2),
            'target_tat_minutes' => round($targetTAT, 2),
            'standard_deviation' => round($stdDev, 2),
            'defects_per_million' => round($dpm, 2),
            'yield_percentage' => round($yield, 2),
            'process_capability' => $this->getProcessCapabilityFromYield($yield),
            'process_capability_index' => round($processCapabilityIndex, 2),
            'recommendations' => $this->getSixSigmaRecommendations($sigmaLevel, $yield, $outliersRemoved),
            'uses_test_specific_targets' => !empty($testTargets),
            'mean_tat_minutes' => round($mean, 2),
            'defect_count' => $defects,
            'total_tests' => count($cleanedTAT),
            'upper_control_limit' => round($targetTAT * 2, 2), // Fixed 2x target limit
            'lower_control_limit' => round($targetTAT * 0.5, 2), // Fixed 0.5x target limit
            'outliers_detected' => $outlierInfo['outlier_count'],
            'outliers_removed' => $outliersRemoved
        ];
    }

    /**
     * Detect and handle outliers in TAT data
     *
     * @param array $tatMinutes
     * @param array $testTargets
     * @return array
     */
    private function detectAndHandleOutliers($tatMinutes, $testTargets = []): array
    {
        $outliers = [];
        $cleanedData = $tatMinutes;
        
        // Calculate target for outlier detection
        $targetTAT = !empty($testTargets) ? array_sum($testTargets) / count($testTargets) : array_sum($tatMinutes) / count($tatMinutes);
        
        // Detect outliers using multiple methods
        foreach ($tatMinutes as $index => $tat) {
            $isOutlier = false;
            
            // Method 1: Extreme ratio to target (10x target is clearly an outlier)
            if ($tat > ($targetTAT * 10)) {
                $isOutlier = true;
            }
            
            // Method 2: IQR method for larger datasets
            if (count($tatMinutes) >= 4) {
                $sortedTAT = $tatMinutes;
                sort($sortedTAT);
                $q1Index = floor(count($sortedTAT) * 0.25);
                $q3Index = floor(count($sortedTAT) * 0.75);
                $q1 = $sortedTAT[$q1Index];
                $q3 = $sortedTAT[$q3Index];
                $iqr = $q3 - $q1;
                $upperBound = $q3 + (1.5 * $iqr);
                
                if ($tat > $upperBound) {
                    $isOutlier = true;
                }
            }
            
            // Method 3: Z-score method (if standard deviation is reasonable)
            if (count($tatMinutes) >= 3) {
                $mean = array_sum($tatMinutes) / count($tatMinutes);
                $stdDev = $this->calculateStandardDeviation($tatMinutes);
                
                if ($stdDev > 0) {
                    $zScore = abs($tat - $mean) / $stdDev;
                    if ($zScore > 3) { // 3-sigma rule
                        $isOutlier = true;
                    }
                }
            }
            
            if ($isOutlier) {
                $outliers[] = $index;
            }
        }
        
        // Remove outliers if detected
        if (!empty($outliers)) {
            $cleanedData = array_values(array_filter($tatMinutes, function($index) use ($outliers) {
                return !in_array($index, $outliers);
            }, ARRAY_FILTER_USE_KEY));
        }
        
        return [
            'cleaned_data' => $cleanedData,
            'outliers_removed' => !empty($outliers),
            'outlier_count' => count($outliers),
            'outlier_indices' => $outliers
        ];
    }

    /**
     * Calculate defects using fixed industry-standard control limits
     *
     * @param array $tatMinutes
     * @param array $testTargets
     * @param float $targetTAT
     * @return int
     */
    private function calculateDefectsWithFixedLimits($tatMinutes, $testTargets, $targetTAT): int
    {
        $defects = 0;
        
        if (!empty($testTargets) && count($testTargets) === count($tatMinutes)) {
            // Use individual test targets with fixed 2x limit
            foreach ($tatMinutes as $index => $tat) {
                $testTarget = $testTargets[$index];
                $upperLimit = $testTarget * 2; // Fixed 2x target limit
                if ($tat > $upperLimit) {
                    $defects++;
                }
            }
        } else {
            // Use overall target with fixed 2x limit
            $upperLimit = $targetTAT * 2; // Fixed 2x target limit
            $defects = count(array_filter($tatMinutes, function($tat) use ($upperLimit) {
                return $tat > $upperLimit;
            }));
        }
        
        return $defects;
    }

    /**
     * Calculate process capability index with fixed limits
     *
     * @param array $tatMinutes
     * @param float $targetTAT
     * @return float
     */
    private function calculateProcessCapabilityIndex($tatMinutes, $targetTAT): float
    {
        if (count($tatMinutes) < 2) {
            return 0;
        }
        
        $stdDev = $this->calculateStandardDeviation($tatMinutes);
        if ($stdDev <= 0) {
            return 0;
        }
        
        // Use fixed specification limits (2x target as upper, 0.5x target as lower)
        $upperSpecLimit = $targetTAT * 2;
        $lowerSpecLimit = $targetTAT * 0.5;
        
        // Cp = (USL - LSL) / (6 * σ)
        return ($upperSpecLimit - $lowerSpecLimit) / (6 * $stdDev);
    }

    /**
     * Calculate sigma level from DPMO using proper 6 Sigma methodology
     *
     * @param float $dpm
     * @return float
     */
    private function calculateSigmaLevelFromDPMO($dpm): float
    {
        // 6 Sigma conversion table from DPMO to Sigma Level
        if ($dpm <= 3.4) {
            return 6.0;  // 6 Sigma
        } elseif ($dpm <= 233) {
            return 5.0;  // 5 Sigma
        } elseif ($dpm <= 6210) {
            return 4.0;  // 4 Sigma
        } elseif ($dpm <= 66807) {
            return 3.0;  // 3 Sigma
        } elseif ($dpm <= 308537) {
            return 2.0;  // 2 Sigma
        } elseif ($dpm <= 690000) {
            return 1.0;  // 1 Sigma
        } else {
            return 0.0;  // Below 1 Sigma
        }
    }

    /**
     * Get process capability assessment based on yield percentage (consistent with recommendations)
     *
     * @param float $yield
     * @return string
     */
    private function getProcessCapabilityFromYield($yield): string
    {
        if ($yield >= 99.9) {
            return 'World Class';  // 99.9%+ yield
        } elseif ($yield >= 99.0) {
            return 'Excellent';   // 99%+ yield
        } elseif ($yield >= 95.0) {
            return 'Good';         // 95%+ yield
        } elseif ($yield >= 90.0) {
            return 'Average';      // 90%+ yield
        } elseif ($yield >= 80.0) {
            return 'Below Average'; // 80%+ yield
        } else {
            return 'Poor';         // <80% yield
        }
    }

    /**
     * Get process capability assessment based on Cp (Process Capability Index)
     *
     * @param float $processCapabilityIndex
     * @return string
     */
    private function getProcessCapability($processCapabilityIndex): string
    {
        if ($processCapabilityIndex >= 1.67) {
            return 'World Class';  // Cp >= 1.67 (6 Sigma)
        } elseif ($processCapabilityIndex >= 1.33) {
            return 'Excellent';   // Cp >= 1.33 (5 Sigma)
        } elseif ($processCapabilityIndex >= 1.0) {
            return 'Good';        // Cp >= 1.0 (4 Sigma)
        } elseif ($processCapabilityIndex >= 0.83) {
            return 'Average';      // Cp >= 0.83 (3 Sigma)
        } elseif ($processCapabilityIndex >= 0.67) {
            return 'Below Average'; // Cp >= 0.67 (2 Sigma)
        } else {
            return 'Poor';         // Cp < 0.67 (1 Sigma)
        }
    }

    /**
     * Get 6 Sigma recommendations with outlier handling
     *
     * @param float $sigmaLevel
     * @param float $yield
     * @param bool $outliersRemoved
     * @return array
     */
    private function getSixSigmaRecommendations($sigmaLevel, $yield, $outliersRemoved = false): array
    {
        $recommendations = [];
        
        // Add outlier-specific recommendations
        if ($outliersRemoved) {
            $recommendations[] = '⚠️ OUTLIERS DETECTED AND REMOVED: Review data quality and investigate extreme delays';
            $recommendations[] = 'Implement data validation to prevent future outliers';
            $recommendations[] = 'Check for system errors or data entry mistakes';
        }
        
        // Recommendations based on yield percentage (more accurate than sigma level)
        if ($yield >= 99.9) {
            $recommendations[] = 'Excellent performance - maintain current standards';
        } elseif ($yield >= 99.0) {
            $recommendations[] = 'Very good performance - minor optimizations possible';
        } elseif ($yield >= 95.0) {
            $recommendations[] = 'Good performance - focus on reducing variation';
        } elseif ($yield >= 90.0) {
            $recommendations[] = 'Average performance - implement process improvements';
        } else {
            $recommendations[] = 'Below average performance - major process overhaul needed';
        }
        
        // Specific recommendations based on yield
        if ($yield < 95) {
            $recommendations[] = 'Investigate and eliminate root causes of delays';
            $recommendations[] = 'Implement standardized work procedures';
        }
        
        if ($yield < 90) {
            $recommendations[] = 'Review and optimize test workflow';
            $recommendations[] = 'Implement quality control checkpoints';
        }
        
        if ($yield >= 99) {
            $recommendations[] = 'Consider implementing advanced analytics for predictive optimization';
        }
        
        return $recommendations;
    }

    /**
     * Calculate TAT performance against target
     *
     * @param int|null $actualTAT
     * @param int|null $targetTAT
     * @return array
     */
    private function calculateTATPerformance($actualTAT, $targetTAT): array
    {
        if (!$actualTAT || !$targetTAT) {
            return [
                'status' => 'unknown',
                'percentage' => null,
                'variance_minutes' => null,
                'meets_target' => null
            ];
        }

        $variance = $actualTAT - $targetTAT;
        $percentage = round(($actualTAT / $targetTAT) * 100, 1);
        $meetsTarget = $actualTAT <= $targetTAT;

        // Calculate status based on statistical performance thresholds
        $status = 'excellent';
        if ($percentage > 200) {
            $status = 'critical';      // >200% of target
        } elseif ($percentage > 150) {
            $status = 'poor';          // >150% of target
        } elseif ($percentage > 120) {
            $status = 'below_target';  // >120% of target
        } elseif ($percentage > 100) {
            $status = 'slightly_over'; // >100% of target
        } elseif ($percentage >= 90) {
            $status = 'good';          // 90-100% of target
        } elseif ($percentage >= 80) {
            $status = 'acceptable';    // 80-90% of target
        } else {
            $status = 'excellent';     // <80% of target (very fast)
        }

        return [
            'status' => $status,
            'percentage' => $percentage,
            'variance_minutes' => $variance,
            'meets_target' => $meetsTarget
        ];
    }

    /**
     * Get detailed TAT data for detailed view
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDetailedTATData(Request $request): JsonResponse
    {
        try {
            $sectionId = $request->input('section_id');
            $patientType = $request->input('patient_type');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            if (!$sectionId) {
                return response()->json([
                    'message' => 'Section ID is required'
                ], 400);
            }

            // Build the base query for completed tests only
            $query = LaboratoryRequest::with([
                'patient:id,last_name,first_name,middle_name,date_of_birth,gender',
                'patientType:id,name',
                'ward:id,name',
                'doctor:id,name',
                'tests' => function ($testQuery) use ($sectionId) {
                    $testQuery->where('test_category_id', $sectionId)
                              ->where('laboratory_request_test.item_status', 'Result Printed')
                              ->withPivot('collected_at', 'checkin_at', 'perform_at', 'verify_at', 'item_status');
                }
            ]);

            // Apply date filters
            if ($dateFrom) {
                $query->where('created_at', '>=', Carbon::parse($dateFrom));
            }
            if ($dateTo) {
                $query->where('created_at', '<=', Carbon::parse($dateTo));
            }

            // Apply patient type filter
            if ($patientType) {
                $query->where('patient_type_id', $patientType);
            }

            // Only get requests that have completed tests in the selected section
            $query->whereHas('tests', function ($q) use ($sectionId) {
                $q->where('test_category_id', $sectionId)
                  ->where('laboratory_request_test.item_status', 'Result Printed');
            });

            $laboratoryRequests = $query->orderBy('created_at', 'desc')->get();

            // Calculate detailed TAT data
            $detailedData = $this->calculateDetailedTATData($laboratoryRequests, $sectionId);

            return response()->json([
                'data' => $detailedData,
                'meta' => [
                    'section_id' => $sectionId,
                    'patient_type' => $patientType,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'total_tests' => count($detailedData)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching detailed TAT data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate detailed TAT data from laboratory requests
     *
     * @param \Illuminate\Database\Eloquent\Collection $laboratoryRequests
     * @param int $sectionId
     * @return array
     */
    private function calculateDetailedTATData($laboratoryRequests, $sectionId): array
    {
        $detailedData = [];

        foreach ($laboratoryRequests as $request) {
            foreach ($request->tests as $test) {
                if ($test->test_category_id != $sectionId || $test->pivot->item_status !== 'Result Printed') {
                    continue;
                }

                // Calculate different TAT metrics
                $requestTime = Carbon::parse($request->created_at);
                $collectedTime = $test->pivot->collected_at ? Carbon::parse($test->pivot->collected_at) : null;
                $checkinTime = $test->pivot->checkin_at ? Carbon::parse($test->pivot->checkin_at) : null;
                $performTime = $test->pivot->perform_at ? Carbon::parse($test->pivot->perform_at) : null;
                $verifyTime = $test->pivot->verify_at ? Carbon::parse($test->pivot->verify_at) : null;

                // Calculate TAT in minutes
                $totalTAT = $verifyTime ? $requestTime->diffInMinutes($verifyTime) : null;
                $collectionTAT = $collectedTime ? $requestTime->diffInMinutes($collectedTime) : null;
                $processingTAT = $collectedTime && $verifyTime ? $collectedTime->diffInMinutes($verifyTime) : null;
                $verificationTAT = $performTime && $verifyTime ? $performTime->diffInMinutes($verifyTime) : null;

                $detailedData[] = [
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'test_category_id' => $test->test_category_id,
                    'laboratory_request_id' => $request->id,
                    'patient_id' => $request->patient_id,
                    'patient_name' => $this->formatPatientName($request->patient),
                    'patient_type' => $request->patientType?->name ?? 'N/A',
                    'ward' => $request->ward?->name ?? 'N/A',
                    'doctor' => $request->doctor?->name ?? 'N/A',
                    'request_date' => $requestTime->format('Y-m-d H:i:s'),
                    'collected_at' => $collectedTime?->format('Y-m-d H:i:s'),
                    'checkin_at' => $checkinTime?->format('Y-m-d H:i:s'),
                    'perform_at' => $performTime?->format('Y-m-d H:i:s'),
                    'verify_at' => $verifyTime?->format('Y-m-d H:i:s'),
                    'total_tat_minutes' => $totalTAT,
                    'total_tat_hours' => $totalTAT ? round($totalTAT / 60, 2) : null,
                    'collection_tat_minutes' => $collectionTAT,
                    'processing_tat_minutes' => $processingTAT,
                    'verification_tat_minutes' => $verificationTAT,
                    'target_tat_minutes' => $test->turnaround_time_minutes,
                    'target_tat_hours' => $test->turnaround_time_minutes ? round($test->turnaround_time_minutes / 60, 2) : null,
                    'tat_performance' => $this->calculateTATPerformance($totalTAT, $test->turnaround_time_minutes),
                    'status' => $test->pivot->item_status
                ];
            }
        }

        return $detailedData;
    }

    /**
     * Get comprehensive census data including both Result Printed and Send-out data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getComprehensiveCensusData(Request $request): JsonResponse
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));
            $testCategoryId = $request->input('test_category_id');

            // Validate inputs
            if (!$year || !$month) {
                return response()->json([
                    'message' => 'Year and month are required'
                ], 400);
            }

            // Create date range for the selected month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Get Result Printed data (use verify_at within month)
            $resultPrintedData = $this->getResultPrintedCensusByVerifyAt($startDate, $endDate, $testCategoryId);
            
            // Get Send-out data (using sendout_at timestamp)
            $sendoutData = $this->getSendoutCensusDataByTimestamp($startDate, $endDate, $testCategoryId);

            // Combine the data
            $combinedData = $this->combineCensusData($resultPrintedData, $sendoutData, $year, $month);

            return response()->json([
                'data' => $combinedData,
                'meta' => [
                    'year' => $year,
                    'month' => $month,
                    'test_category_id' => $testCategoryId,
                    'total_tests' => count($combinedData),
                    'days_in_month' => $endDate->day,
                    'result_printed_count' => count($resultPrintedData),
                    'sendout_count' => count($sendoutData)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching comprehensive census data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get result printed census using pivot verify_at within the selected month
     */
    private function getResultPrintedCensusByVerifyAt($startDate, $endDate, $testCategoryId)
    {
        $query = LaboratoryRequest::with([
            'tests' => function ($testQuery) use ($testCategoryId, $startDate, $endDate) {
                $testQuery->where('laboratory_request_test.item_status', 'Result Printed')
                         ->whereNotNull('laboratory_request_test.verify_at')
                         ->whereBetween('laboratory_request_test.verify_at', [$startDate, $endDate]);
                if ($testCategoryId) {
                    $testQuery->where('test_category_id', $testCategoryId);
                }
            }
        ])
        ->whereHas('tests', function ($q) use ($testCategoryId, $startDate, $endDate) {
            $q->where('laboratory_request_test.item_status', 'Result Printed')
              ->whereNotNull('laboratory_request_test.verify_at')
              ->whereBetween('laboratory_request_test.verify_at', [$startDate, $endDate]);
            if ($testCategoryId) {
                $q->where('test_category_id', $testCategoryId);
            }
        });

        return $query->get();
    }

    /**
     * Get send-out census data using sendout_at timestamp
     */
    private function getSendoutCensusDataByTimestamp($startDate, $endDate, $testCategoryId)
    {
        $query = LaboratoryRequest::with([
            'tests' => function ($testQuery) use ($testCategoryId, $startDate, $endDate) {
                $testQuery->whereNotNull('laboratory_request_test.sendout_at')
                          ->whereBetween('laboratory_request_test.sendout_at', [$startDate, $endDate]);
                if ($testCategoryId) {
                    $testQuery->where('test_category_id', $testCategoryId);
                }
            }
        ])
        ->whereHas('tests', function ($q) use ($testCategoryId, $startDate, $endDate) {
            $q->whereNotNull('laboratory_request_test.sendout_at')
              ->whereBetween('laboratory_request_test.sendout_at', [$startDate, $endDate]);
            if ($testCategoryId) {
                $q->where('test_category_id', $testCategoryId);
            }
        });

        return $query->get();
    }

    /**
     * Combine Result Printed and Send-out data into comprehensive census
     */
    private function combineCensusData($resultPrintedRequests, $sendoutRequests, $year, $month)
    {
        $censusData = [];
        $testCounts = [];

        // Initialize day columns for the month
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $dayColumns = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayColumns[$day] = 0;
        }

        // Process Result Printed data (All Samples)
        foreach ($resultPrintedRequests as $request) {
            foreach ($request->tests as $test) {
                $testId = $test->id;
                $testName = $test->name;
                $verifyDate = $test->pivot->verify_at ? Carbon::parse($test->pivot->verify_at) : null;
                if (!$verifyDate) { continue; }
                $dayOfMonth = $verifyDate->day;

                if (!isset($testCounts[$testId])) {
                    $testCounts[$testId] = [
                        'test_id' => $testId,
                        'test_name' => $testName,
                        'test_month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                        'test_year' => $year,
                        'all_total' => 0,               // All Samples == Result Printed
                        'processed_total' => 0,         // Result Printed excluding month send-outs
                        'sendout_total' => 0,           // Send-out in month
                        'all_days' => $dayColumns,
                        'processed_days' => $dayColumns,
                        'sendout_days' => $dayColumns
                    ];
                }

                // All Samples (Result Printed)
                $testCounts[$testId]['all_days'][$dayOfMonth]++;
                $testCounts[$testId]['all_total']++;
            }
        }

        // Process Send-out data (sendout_at within month)
        foreach ($sendoutRequests as $request) {
            foreach ($request->tests as $test) {
                $testId = $test->id;
                $testName = $test->name;
                $sendoutDate = Carbon::parse($test->pivot->sendout_at);
                $dayOfMonth = $sendoutDate->day;

                if (!isset($testCounts[$testId])) {
                    $testCounts[$testId] = [
                        'test_id' => $testId,
                        'test_name' => $testName,
                        'test_month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                        'test_year' => $year,
                        'all_total' => 0,
                        'processed_total' => 0,
                        'sendout_total' => 0,
                        'all_days' => $dayColumns,
                        'processed_days' => $dayColumns,
                        'sendout_days' => $dayColumns
                    ];
                }

                $testCounts[$testId]['sendout_days'][$dayOfMonth]++;
                $testCounts[$testId]['sendout_total']++;
            }
        }

        // Compute Processed by Lab = All Samples minus those sent out in the selected month
        // We compare per test and per day; processed day = verify_at day, excluded if same pivot has sendout_at within month
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();
        foreach ($resultPrintedRequests as $request) {
            foreach ($request->tests as $test) {
                $testId = $test->id;
                $verifyDate = $test->pivot->verify_at ? Carbon::parse($test->pivot->verify_at) : null;
                if (!$verifyDate) { continue; }
                $dayOfMonth = $verifyDate->day;

                $sendoutAt = $test->pivot->sendout_at ? Carbon::parse($test->pivot->sendout_at) : null;
                $sentOutThisMonth = $sendoutAt && $sendoutAt->between($start, $end);

                if (!isset($testCounts[$testId])) { continue; }

                if (!$sentOutThisMonth) {
                    $testCounts[$testId]['processed_days'][$dayOfMonth]++;
                    $testCounts[$testId]['processed_total']++;
                }
            }
        }

        // Convert to array format for frontend
        foreach ($testCounts as $testData) {
            $censusData[] = $testData;
        }

        // Sort by test name
        usort($censusData, function ($a, $b) {
            return strcmp($a['test_name'], $b['test_name']);
        });

        return $censusData;
    }
}
