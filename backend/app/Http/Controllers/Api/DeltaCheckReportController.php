<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeltaCheckService;
use Illuminate\Http\Request;

class DeltaCheckReportController extends Controller
{
    protected $deltaCheckService;

    public function __construct(DeltaCheckService $deltaCheckService)
    {
        $this->deltaCheckService = $deltaCheckService;
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'patient_id',
            'test_id', 
            'test_parameter_id',
            'status',
            'start_date',
            'end_date'
        ]);

        // Remove empty values
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        $deltaChecks = $this->deltaCheckService->getDeltaChecksForReport($filters);

        // Convert to paginated response format for frontend compatibility
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        
        $total = count($deltaChecks);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($deltaChecks, $offset, $perPage);

        // Get global statistics
        $globalStats = $this->deltaCheckService->getGlobalStatistics($filters);

        return response()->json([
            'data' => $paginatedData,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'per_page' => $perPage,
            'total' => $total,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
            'prev_page_url' => $page > 1 ? $request->url() . '?page=' . ($page - 1) : null,
            'next_page_url' => $page < ceil($total / $perPage) ? $request->url() . '?page=' . ($page + 1) : null,
            'global_statistics' => $globalStats,
        ]);
    }

    /**
     * Get global statistics only
     */
    public function statistics(Request $request)
    {
        $filters = $request->only([
            'patient_id',
            'test_id', 
            'test_parameter_id',
            'status',
            'start_date',
            'end_date'
        ]);

        // Remove empty values
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        $globalStats = $this->deltaCheckService->getGlobalStatistics($filters);

        return response()->json($globalStats);
    }

    /**
     * Get recommendations for a specific test parameter
     */
    public function getRecommendations($testParameterId)
    {
        $testParameter = \App\Models\TestParameter::find($testParameterId);
        
        if (!$testParameter) {
            return response()->json(['error' => 'Test parameter not found'], 404);
        }

        $recommendedLimits = $this->deltaCheckService->getRecommendedLimits($testParameter->test_name);
        
        return response()->json([
            'test_parameter' => $testParameter,
            'current_limits' => [
                'percentage_limit' => $testParameter->delta_check_percentage_limit,
                'absolute_limit' => $testParameter->delta_check_absolute_limit,
            ],
            'recommended_limits' => $recommendedLimits,
            'all_parameters' => \App\Repositories\DeltaCheckRecommendationRepository::getAllParameters(),
        ]);
    }

    /**
     * Update limits for a test parameter
     * 
     * @deprecated This method is deprecated. Use Delta Check Rules configuration instead.
     * Creating rules via /api/delta-check-rules is the preferred method.
     * This method is kept for backward compatibility only.
     * 
     * Note: The new rules system provides more flexibility:
     * - Analyzer-specific rules
     * - Time window configuration
     * - Comparison type selection
     * - Action on violation configuration
     */
    public function updateLimits($testParameterId, Request $request)
    {
        $testParameter = \App\Models\TestParameter::find($testParameterId);
        
        if (!$testParameter) {
            return response()->json(['error' => 'Test parameter not found'], 404);
        }

        $request->validate([
            'percentage_limit' => 'nullable|numeric|min:0|max:100',
            'absolute_limit' => 'nullable|numeric|min:0',
        ]);

        // Update legacy limits in test_parameters table
        // Note: These are still used as fallback if no rules are configured
        $testParameter->update([
            'delta_check_percentage_limit' => $request->input('percentage_limit'),
            'delta_check_absolute_limit' => $request->input('absolute_limit'),
        ]);

        return response()->json([
            'message' => 'Limits updated successfully (legacy method). Consider using Delta Check Rules instead.',
            'test_parameter' => $testParameter,
            'deprecation_notice' => 'This endpoint is deprecated. Use /api/delta-check-rules to create rules instead.',
        ]);
    }
}
