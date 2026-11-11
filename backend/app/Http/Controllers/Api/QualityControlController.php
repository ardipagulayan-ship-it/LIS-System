<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualityControl;
use App\Models\TestParameter;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DeltaCheckGlobalRecommendation;
use App\Models\DeltaCheckQualityControl;

class QualityControlController extends Controller
{
    /**
     * Display a listing of the quality controls.
     */
    public function index()
    {
        $qualityControls = QualityControl::with(['testParameter', 'equipment.testCategory'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($qualityControls);
    }

    /**
     * Store a newly created quality control.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_parameter_id' => 'required|exists:test_parameters,id',
            'equipment_id' => 'required|exists:equipment,id',
            'control_name' => 'required|string|max:255',
            'lot_number' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'mean' => 'required|numeric',
            'sd' => 'required|numeric',
            'target' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $qualityControl = new QualityControl($request->all());
        $qualityControl->calculateSDLimits();
        $qualityControl->created_by = Auth::id();
        $qualityControl->save();

        return response()->json($qualityControl->load(['testParameter', 'equipment']), 201);
    }

    /**
     * Display the specified quality control.
     */
    public function show(QualityControl $qualityControl)
    {
        return response()->json($qualityControl->load([
            'testParameter', 
            'equipment',
            'qualityControlResults' => function($query) {
                $query->orderBy('run_date', 'asc')
                      ->orderBy('run_time', 'asc');
            },
            'qualityControlResults.operator',
            'testParameters'
        ]));
    }

    /**
     * Update the specified quality control.
     */
    public function update(Request $request, QualityControl $qualityControl)
    {
        $validator = Validator::make($request->all(), [
            'test_parameter_id' => 'exists:test_parameters,id',
            'equipment_id' => 'exists:equipment,id',
            'control_name' => 'string|max:255',
            'lot_number' => 'string|max:255',
            'expiry_date' => 'date',
            'mean' => 'numeric',
            'sd' => 'numeric',
            'target' => 'numeric',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $qualityControl->fill($request->all());
        if ($request->has('mean') || $request->has('sd')) {
            $qualityControl->calculateSDLimits();
        }
        $qualityControl->updated_by = Auth::id();
        $qualityControl->save();

        return response()->json($qualityControl->load(['testParameter', 'equipment']));
    }

    /**
     * Remove the specified quality control.
     */
    public function destroy(QualityControl $qualityControl)
    {
        $qualityControl->delete();
        return response()->json(null, 204);
    }

    /**
     * Get quality controls by test parameter.
     */
    public function getByTestParameter(TestParameter $testParameter)
    {
        $qualityControls = QualityControl::with(['equipment'])
            ->where('test_parameter_id', $testParameter->id)
            ->where('is_active', true)
            ->get();

        return response()->json($qualityControls);
    }

    /**
     * Get quality controls by equipment.
     */
    public function getByEquipment(Equipment $equipment)
    {
        $qualityControls = QualityControl::with(['testParameter'])
            ->where('equipment_id', $equipment->id)
            ->where('is_active', true)
            ->get();

        return response()->json($qualityControls);
    }

    /**
     * Get Levey-Jennings data for a specific quality control and test parameter.
     */
    public function getLeveyJenningsData(QualityControl $qualityControl, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_parameter_id' => 'required|exists:test_parameters,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $results = $qualityControl->results()
            ->where('test_parameter_id', $request->test_parameter_id)
            ->orderBy('run_date', 'asc')
            ->orderBy('run_time', 'asc')
            ->get();

        $labels = $results->map(function ($result) {
            return $result->run_date . ' ' . $result->run_time;
        });

        $resultValues = $results->pluck('result_value')->toArray();
        $meanLine = array_fill(0, count($resultValues), $qualityControl->mean);
        $sd1Plus = array_fill(0, count($resultValues), $qualityControl->sd_1_plus);
        $sd1Minus = array_fill(0, count($resultValues), $qualityControl->sd_1_minus);
        $sd2Plus = array_fill(0, count($resultValues), $qualityControl->sd_2_plus);
        $sd2Minus = array_fill(0, count($resultValues), $qualityControl->sd_2_minus);
        $sd3Plus = array_fill(0, count($resultValues), $qualityControl->sd_3_plus);
        $sd3Minus = array_fill(0, count($resultValues), $qualityControl->sd_3_minus);

        $datasets = [
            [
                'label' => 'Results',
                'data' => $resultValues,
                'borderColor' => 'rgb(75, 192, 192)',
                'tension' => 0.1,
                'fill' => false,
                'pointRadius' => 4
            ],
            [
                'label' => 'Mean',
                'data' => $meanLine,
                'borderColor' => 'rgb(255, 99, 132)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0
            ],
            [
                'label' => '+1SD',
                'data' => $sd1Plus,
                'borderColor' => 'rgb(255, 159, 64)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0
            ],
            [
                'label' => '-1SD',
                'data' => $sd1Minus,
                'borderColor' => 'rgb(255, 159, 64)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0
            ],
            [
                'label' => '+2SD',
                'data' => $sd2Plus,
                'borderColor' => 'rgb(255, 205, 86)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0
            ],
            [
                'label' => '-2SD',
                'data' => $sd2Minus,
                'borderColor' => 'rgb(255, 205, 86)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0
            ],
            [
                'label' => '+3SD',
                'data' => $sd3Plus,
                'borderColor' => 'rgb(255, 99, 132)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0
            ],
            [
                'label' => '-3SD',
                'data' => $sd3Minus,
                'borderColor' => 'rgb(255, 99, 132)',
                'borderDash' => [5, 5],
                'fill' => false,
                'pointRadius' => 0
            ]
        ];

        return response()->json([
            'labels' => $labels,
            'datasets' => $datasets
        ]);
    }

    /**
     * Get quality controls by equipment with test parameters.
     */
    public function getByEquipmentWithParameters(Equipment $equipment)
    {
        // Get all QC lots for this equipment
        $qcs = \App\Models\QualityControl::where('equipment_id', $equipment->id)
            ->where('is_active', true)
            ->get();
        $qcIds = $qcs->pluck('id');
        // Get all QC test parameter entries for these lots
        $qcTestParameters = \App\Models\QualityControlTestParameter::with(['testParameter', 'qualityControlResults'])
            ->whereIn('quality_control_id', $qcIds)
            ->get();
        // Only include if there is at least one result for that pivot+analyzer
        $filtered = $qcTestParameters->filter(function($pivot) {
            return $pivot->qualityControlResults && $pivot->qualityControlResults->count() > 0;
        });
        $resp = $filtered->map(function($pivot) {
            return [
                'id' => $pivot->id,
                'parameter_name' => $pivot->testParameter->test_name ?? $pivot->testParameter->name ?? $pivot->testParameter->test_parameter_name ?? 'Parameter',
                'test_parameter_id' => $pivot->testParameter->id ?? null,
                'quality_control_id' => $pivot->quality_control_id,
                'target' => $pivot->target,
                'tolerance' => $pivot->tolerance,
                'minus_1sd' => $pivot->minus_1sd, 'plus_1sd' => $pivot->plus_1sd,
                'minus_2sd' => $pivot->minus_2sd, 'plus_2sd' => $pivot->plus_2sd,
                'minus_3sd' => $pivot->minus_3sd, 'plus_3sd' => $pivot->plus_3sd
            ];
        })->values();
        return response()->json($resp);
    }

    /**
     * Get quality control results grouped by test parameter.
     */
    public function getResultsByParameter(QualityControl $qualityControl)
    {
        $results = $qualityControl->results()
            ->with(['testParameter', 'operator'])
            ->orderBy('run_date', 'desc')
            ->orderBy('run_time', 'desc')
            ->get()
            ->groupBy('test_parameter_id');

        return response()->json($results);
    }

    /**
     * Get parameters for a specific quality control.
     */
    public function getParameters(QualityControl $qualityControl)
    {
        $parameters = $qualityControl->testParameters()->get();

        return response()->json(['data' => $parameters]);
    }

    /**
     * Get global QC recommendations (active only, ordered by severity)
     */
    public function globalRecommendations()
    {
        $severityOrder = [
            'critical' => 1,
            'high' => 2,
            'moderate' => 3,
            'low' => 4
        ];
        $recs = DeltaCheckGlobalRecommendation::where('is_active', true)->get()->sortBy(function($rec) use ($severityOrder) {
            return $severityOrder[$rec->severity] ?? 99;
        })->values();
        return response()->json($recs);
    }

    /**
     * Get global QC recommendations (from delta_check_quality_control, not parameter-specific)
     */
    public function globalQCRecommendations()
    {
        $recommendations = DeltaCheckQualityControl::whereNull('parameter_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
        return response()->json($recommendations);
    }
} 