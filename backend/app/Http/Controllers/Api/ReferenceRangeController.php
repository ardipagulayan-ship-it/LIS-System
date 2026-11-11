<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferenceRange;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReferenceRangeController extends Controller
{
        /**
     * Fetch requests with items relevant to Phlebotomy.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $testCategoryId = $request->input('test_category_id');
        $equipmentId = $request->input('equipment_id');

        $query = ReferenceRange::with(['testParameter.test', 'equipment']);

        // Filter by test category if provided
        if ($testCategoryId) {
            $query->whereHas('testParameter.test', function ($q) use ($testCategoryId) {
                $q->where('test_category_id', $testCategoryId);
            });
        }

        // Filter by equipment_id
        if ($equipmentId) {
            $query->where('equipment_id', $equipmentId);
        }

        $ranges = $query->paginate($perPage);
        return response()->json($ranges);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_parameter_id' => 'required|exists:test_parameters,id',
            'equipment_id' => 'required|exists:equipment,id',
            'si_range' => 'nullable|string',
            'si_unit' => 'nullable|string',
            'conv_range' => 'nullable|string',
            'conv_unit' => 'nullable|string',
            'low_critical_value' => 'nullable|numeric',
            'low_value' => 'nullable|numeric',
            'high_value' => 'nullable|numeric',
            'high_critical_value' => 'nullable|numeric',
            'age_min' => 'nullable|integer',
            'age_max' => 'nullable|integer',
            'age_unit' => 'nullable|string',
            'gender' => 'nullable|string',
        ]);

        $range = ReferenceRange::create($validated);
        return response()->json($range->load('equipment'), 201);
    }

    public function show(ReferenceRange $referenceRange): JsonResponse
    {
        return response()->json($referenceRange->load('equipment'));
    }

    public function update(Request $request, ReferenceRange $referenceRange): JsonResponse
    {
        $validated = $request->validate([
            'test_parameter_id' => 'sometimes|required|exists:test_parameters,id',
            'equipment_id' => 'sometimes|required|exists:equipment,id',
            'si_range' => 'nullable|string',
            'si_unit' => 'nullable|string',
            'conv_range' => 'nullable|string',
            'conv_unit' => 'nullable|string',
            'low_critical_value' => 'nullable|numeric',
            'low_value' => 'nullable|numeric',
            'high_value' => 'nullable|numeric',
            'high_critical_value' => 'nullable|numeric',
            'age_min' => 'nullable|integer',
            'age_max' => 'nullable|integer',
            'age_unit' => 'nullable|string',
            'gender' => 'nullable|string',
        ]);

        $referenceRange->update($validated);
        return response()->json($referenceRange->load('equipment'));
    }

    public function destroy(ReferenceRange $referenceRange): JsonResponse
    {
        $referenceRange->delete();
        return response()->json(null, 204);
    }
} 