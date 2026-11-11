<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualityControlTestParameter;
use App\Models\QualityControl;
use App\Models\TestParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class QualityControlTestParameterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quality_control_id' => 'required|exists:quality_controls,id',
            'test_parameter_id' => 'required|exists:test_parameters,id',
            'target' => 'nullable|numeric',
            'tolerance' => 'nullable|numeric',
            'minus_3sd' => 'nullable|numeric',
            'minus_2sd' => 'nullable|numeric',
            'minus_1sd' => 'nullable|numeric',
            'plus_1sd' => 'nullable|numeric',
            'plus_2sd' => 'nullable|numeric',
            'plus_3sd' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the relationship already exists
        $existing = QualityControlTestParameter::where('quality_control_id', $request->quality_control_id)
            ->where('test_parameter_id', $request->test_parameter_id)
            ->first();

        if ($existing) {
            return response()->json(['errors' => ['test_parameter_id' => ['This test parameter is already associated with this quality control.']]], 422);
        }

        $qualityControlTestParameter = QualityControlTestParameter::create($request->all());

        return response()->json($qualityControlTestParameter->load(['qualityControl', 'testParameter']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(QualityControlTestParameter $qualityControlTestParameter)
    {
        return response()->json($qualityControlTestParameter->load(['qualityControl.equipment', 'testParameter']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QualityControlTestParameter $qualityControlTestParameter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QualityControlTestParameter $qualityControlTestParameter)
    {
        $validator = Validator::make($request->all(), [
            'target' => 'nullable|numeric',
            'tolerance' => 'nullable|numeric',
            'minus_3sd' => 'nullable|numeric',
            'minus_2sd' => 'nullable|numeric',
            'minus_1sd' => 'nullable|numeric',
            'plus_1sd' => 'nullable|numeric',
            'plus_2sd' => 'nullable|numeric',
            'plus_3sd' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $qualityControlTestParameter->update($request->all());

        return response()->json($qualityControlTestParameter->load(['qualityControl', 'testParameter']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QualityControlTestParameter $qualityControlTestParameter)
    {
        $qualityControlTestParameter->delete();
        return response()->json(null, 204);
    }
}
