<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestParameter;
use App\Models\Test;
use App\Models\ReferenceRange;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestParameterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TestParameter::with(['test.testCategory']);
        
        // Filter by test category if provided
        if ($request->has('test_category_id') && $request->test_category_id) {
            $query->whereHas('test', function($q) use ($request) {
                $q->where('test_category_id', $request->test_category_id);
            });
        }
        
        $parameters = $query->orderBy('test_id')->orderBy('sequence_no')->get();
        
        // Add category information to each parameter for easier frontend access
        $parameters->each(function($parameter) {
            if ($parameter->test && $parameter->test->testCategory) {
                $parameter->test_category_name = $parameter->test->testCategory->name;
                $parameter->test_category_id = $parameter->test->testCategory->id;
            }
        });
        
        return response()->json($parameters);
    }

    /**
     * Get parameters associated with a specific test.
     */
    public function getParametersByTest(Test $test): JsonResponse
    {
        $parameters = $test->testParameters()
                           ->with('referenceRanges')
                           ->orderBy('sequence_no')
                           ->get();

        // Make sure conversion_factor is included in the response
        $parameters->each(function($parameter) {
            // Ensure the conversion_factor is visible in the JSON response
            $parameter->makeVisible(['conversion_factor']);
        });

        return response()->json($parameters);
    }

    /**
     * Get parameters associated with a specific test.
     * @param Test $test
     * @param Request $request
     * @return JsonResponse
     */
    public function getParametersByTests(Test $test, Request $request): JsonResponse
    {
        $page = $request->input('page_size', 100);
        $parameters = $test->testParameters()
                           ->with('referenceRanges')
                           ->orderBy('sequence_no')
                           ->paginate($page);

        // Make sure conversion_factor is included in the response
        $parameters->each(function($parameter) {
            // Ensure the conversion_factor is visible in the JSON response
            $parameter->makeVisible(['conversion_factor']);
        });

        return response()->json($parameters);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'test_name' => 'required|string|max:255',
            'test_code' => 'nullable|string|max:50',
            'specimen_type' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'conversion_factor' => 'nullable|numeric',
            'sequence_no' => 'required|integer|min:0',
        ]);

        $parameter = TestParameter::create($validated);

        return response()->json($parameter, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TestParameter $testParameter): JsonResponse
    {
        return response()->json($testParameter);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TestParameter $testParameter)
    {
        $validated = $request->validate([
            'test_id' => 'sometimes|required|exists:tests,id',
            'test_name' => 'sometimes|required|string|max:255',
            'test_code' => 'nullable|string|max:50',
            'specimen_type' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'conversion_factor' => 'nullable|numeric',
            'sequence_no' => 'sometimes|required|integer|min:0',
        ]);

        $testParameter->update($validated);

        return response()->json($testParameter);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TestParameter $testParameter): JsonResponse
    {
        $testParameter->delete();
        return response()->json(null, 204);
    }

    /**
     * Get reference ranges for a specific parameter and analyzer
     * 
     * @param Test $test The test
     * @param int $parameter The parameter ID
     * @param Request $request
     * @return JsonResponse
     */
    public function getReferenceRangesByEquipment(Test $test, $parameter, Request $request): JsonResponse
    {
        $instrument = $request->query('instrument');
        $equipmentId = $request->query('equipment_id');
        $patientId = $request->query('patient_id');
        $patientAge = $request->query('patient_age');
        $patientGender = $request->query('patient_gender', 'Any');
        
        // First validate that the parameter belongs to the test
        $testParameter = $test->testParameters()->where('id', $parameter)->first();
        
        if (!$testParameter) {
            return response()->json(['error' => 'Parameter not found for this test'], 404);
        }
        
        // Query builder for reference ranges
        $query = ReferenceRange::where('test_parameter_id', $parameter);
        
        // If we have an equipment_id, filter by it
        if ($equipmentId) {
            if (is_numeric($equipmentId)) {
                $query->where('equipment_id', $equipmentId);
            }
        }
        // Otherwise, if we have an instrument value (for backward compatibility), filter by equipment_id
        elseif ($instrument) {
            if (is_numeric($instrument)) {
                $query->where('equipment_id', $instrument);
            }
        }
        
        // Get all matching reference ranges
        $referenceRanges = $query->get();
        
        // If we have patient information, filter for applicable ranges
        if ($patientAge && $patientGender) {
            // Filter the ranges by age and gender
            $referenceRanges = $referenceRanges->filter(function($range) use ($patientAge, $patientGender) {
                // Gender check
                $genderMatch = $range->gender === 'Any' || $range->gender === $patientGender;
                
                // Simple age check (assuming age is in years and age_unit is correctly set)
                $ageMatch = ($range->age_min <= $patientAge && $range->age_max >= $patientAge);
                
                return $genderMatch && $ageMatch;
            });
        }
        
        return response()->json($referenceRanges->values());
    }
}
