<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TestController extends Controller
{
    /**
     * Get all test categories with their tests
     * @param Request $request
     * @return JsonResponse
     */
    public function getTests(): JsonResponse
    {
        try {
            $tests = Test::with(['testCategory', 'laboratoryRequests'])
            ->withCount('laboratoryRequests')
            ->latest()
            ->get();

            return response()->json($tests);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page_size', 100);
            $tests = Test::with(['category', 'laboratoryRequests'])
            ->withCount('testParameters')
            ->withCount('laboratoryRequests')
            ->orderBy('id')
            ->paginate($page);

            return response()->json($tests);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'test_category_id' => 'required|exists:test_categories,id',
            'normal_range' => 'nullable|string',
            'unit' => 'nullable|string',
            'turnaround_time' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $test = Test::create($request->all());
        $test->load('category');
        return response()->json($test, 201);
    }

    public function show(Test $test): JsonResponse
    {
        $test->load(['category', 'laboratoryRequests']);
        return response()->json($test);
    }

    public function update(Request $request, Test $test): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'test_category_id' => 'required|exists:test_categories,id',
            'normal_range' => 'nullable|string',
            'unit' => 'nullable|string',
            'turnaround_time' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $test->update($request->all());
        $test->load('category');
        return response()->json($test);
    }

    public function destroy(Test $test): JsonResponse
    {
        if ($test->laboratoryRequests()->exists()) {
            return response()->json([
                'message' => 'Cannot delete test with associated laboratory requests'
            ], 422);
        }

        $test->delete();
        return response()->json(null, 204);
    }

    public function getCategories(): JsonResponse
    {
        $categories = TestCategory::select('id', 'name')->get();
        return response()->json($categories);
    }
} 