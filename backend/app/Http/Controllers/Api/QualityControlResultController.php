<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualityControlResult;
use App\Models\QualityControl;
use App\Models\TestParameter;
use App\Models\QualityControlTestParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QualityControlResultController extends Controller
{
    /**
     * Display a listing of the quality control results.
     */
    public function index(Request $request)
    {
        $query = QualityControlResult::with(['qualityControlTestParameter.qualityControl.equipment', 'qualityControlTestParameter.testParameter', 'operator']);

        if ($request->has('quality_control_test_parameter_id')) {
            $query->where('quality_control_test_parameter_id', $request->quality_control_test_parameter_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('run_date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('is_valid')) {
            $query->where('is_valid', $request->is_valid);
        }

        $results = $query->orderBy('run_date', 'desc')
            ->orderBy('run_time', 'desc')
            ->paginate(10);

        return response()->json($results);
    }

    /**
     * Store a newly created quality control result.
     */
    public function store(Request $request, QualityControlTestParameter $qualityControlTestParameter)
    {
        $validator = Validator::make($request->all(), [
            'result_value' => 'required|numeric',
            'unit' => 'nullable|string',
            'run_date' => 'required|date',
            'run_time' => 'required',
            'operator_id' => 'nullable|exists:users,id',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = new QualityControlResult($request->all());
        $result->quality_control_test_parameter_id = $qualityControlTestParameter->id;
        // Default operator to the authenticated user if not provided
        if (!$request->filled('operator_id')) {
            $result->operator_id = Auth::id();
        }
        $result->created_by = Auth::id();
        $result->save();

        // Check Westgard rules after saving
        $result->checkWestgardRules();
        $result->save();

        return response()->json($result, 201);
    }

    /**
     * Display the specified quality control result.
     */
    public function show(QualityControlResult $qualityControlResult)
    {
        return response()->json($qualityControlResult->load(['qualityControlTestParameter.qualityControl.equipment', 'qualityControlTestParameter.testParameter', 'operator']));
    }

    /**
     * Update the specified quality control result.
     */
    public function update(Request $request, QualityControlResult $qualityControlResult)
    {
        $validator = Validator::make($request->all(), [
            'result_value' => 'numeric',
            'unit' => 'nullable|string|max:50',
            'run_date' => 'date',
            'run_time' => 'string',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $qualityControlResult->fill($request->all());
        if ($request->has('result_value')) {
            $qualityControlResult->checkWestgardRules();
        }
        $qualityControlResult->updated_by = Auth::id();
        $qualityControlResult->save();

        return response()->json($qualityControlResult->load(['qualityControlTestParameter.qualityControl.equipment', 'qualityControlTestParameter.testParameter', 'operator']));
    }

    /**
     * Accept a quality control result.
     */
    public function accept(QualityControlResult $qualityControlResult)
    {
        try {
            $qualityControlResult->is_valid = true;
            $qualityControlResult->updated_by = Auth::id();
            $qualityControlResult->save();

            return response()->json([
                'message' => 'Result accepted successfully',
                'data' => $qualityControlResult->load(['qualityControlTestParameter.qualityControl.equipment', 'qualityControlTestParameter.testParameter', 'operator'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error accepting result: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject a quality control result.
     */
    public function reject(QualityControlResult $qualityControlResult)
    {
        try {
            $qualityControlResult->is_valid = false;
            $qualityControlResult->updated_by = Auth::id();
            $qualityControlResult->save();

            return response()->json([
                'message' => 'Result rejected successfully',
                'data' => $qualityControlResult->load(['qualityControlTestParameter.qualityControl.equipment', 'qualityControlTestParameter.testParameter', 'operator'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error rejecting result: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified quality control result.
     */
    public function destroy(QualityControlResult $qualityControlResult)
    {
        $qualityControlResult->delete();
        return response()->json(null, 204);
    }

    /**
     * Get results for a specific quality control and test parameter.
     */
    public function getResultsByControlAndParameter(QualityControlTestParameter $qualityControlTestParameter)
    {
        $perPage = request()->input('per_page', 10); // Default to 10 if not provided
        $results = QualityControlResult::with(['operator'])
            ->where('quality_control_test_parameter_id', $qualityControlTestParameter->id)
            ->orderBy('run_date', 'desc')
            ->orderBy('run_time', 'desc')
            ->paginate($perPage);

        return response()->json($results);
    }

    /**
     * Get Levey-Jennings chart data for a specific quality control and test parameter.
     */
    public function getLeveyJenningsDataByControlAndParameter(QualityControlTestParameter $qualityControlTestParameter)
    {
        // Fetch results filtered by quality control test parameter ID
        $results = QualityControlResult::where('quality_control_test_parameter_id', $qualityControlTestParameter->id)
            ->orderBy('run_date', 'asc')
            ->orderBy('run_time', 'asc')
            ->get(['result_value', 'run_date', 'run_time', 'is_valid']); // Select specific columns including is_valid

        // Retrieve the specific target and SD values from the QualityControlTestParameter model
        // (which now directly holds these values)
        $target = $qualityControlTestParameter->target;
        $tolerance = $qualityControlTestParameter->tolerance;
        $minus1sd = $qualityControlTestParameter->minus_1sd;
        $plus1sd = $qualityControlTestParameter->plus_1sd;
        $minus2sd = $qualityControlTestParameter->minus_2sd;
        $plus2sd = $qualityControlTestParameter->plus_2sd;
        $minus3sd = $qualityControlTestParameter->minus_3sd;
        $plus3sd = $qualityControlTestParameter->plus_3sd;
        
        // Calculate mean from target (common in QC settings)
        $mean = $target;

        $dates = $results->map(function ($result) {
            // Combine them into a standard parsable format
            $combinedDateTime = $result->run_date . ' ' . $result->run_time;
            
            // Return the combined string, frontend will parse it
            return $combinedDateTime;
        })->toArray();

        $resultValues = $results->map(function ($result) {
            return $result->result_value;
        });

        $isValid = $results->map(function ($result) {
            return (bool) $result->is_valid;
        });

        return response()->json([
            'dates' => $dates,
            'results' => $resultValues,
            'is_valid' => $isValid,
            'target' => $target,
            'mean' => $mean,
            'sd' => $tolerance,
            'minus_1sd' => $minus1sd,
            'plus_1sd' => $plus1sd,
            'minus_2sd' => $minus2sd,
            'plus_2sd' => $plus2sd,
            'minus_3sd' => $minus3sd,
            'plus_3sd' => $plus3sd,
        ]);
    }

    /**
     * Recalculate mean/SD and control limits from recent QC results for a QC Test Parameter.
     * Optional query param: num_points (default 20)
     */
    public function recalculateStatistics(QualityControlTestParameter $qualityControlTestParameter, Request $request)
    {
        $numPoints = (int) ($request->input('num_points', 20));
        if ($numPoints < 2) {
            $numPoints = 2;
        }

        $qualityControlTestParameter->recalculateFromResults($numPoints);

        return response()->json([
            'message' => 'QC statistics recalculated',
            'data' => $qualityControlTestParameter->only([
                'target', 'tolerance', 'minus_1sd', 'plus_1sd', 'minus_2sd', 'plus_2sd', 'minus_3sd', 'plus_3sd'
            ])
        ]);
    }
} 