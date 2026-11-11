<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TestCategoryController extends Controller
{
    /**
     * Get all test categories with their tests
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page_size', 10);
            $categories = TestCategory::with(['tests' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->paginate($page);

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching test categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
     * Get all test categories with their tests
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page_size', 10);
            $categories = TestCategory::with(['tests' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching test categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tests for a specific category
     *
     * @param int $categoryId
     * @return JsonResponse
     */
    public function getTests(int $categoryId): JsonResponse
    {
        try {
            $category = TestCategory::with(['tests' => function ($query) {
                $query->orderBy('name');
            }])
            ->findOrFail($categoryId);

            return response()->json($category->tests);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tests for category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:test_categories',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = TestCategory::create($request->all());
        return response()->json($category, 201);
    }

    // public function show(TestCategory $testCategory): JsonResponse
    // {
    //     $testCategory->load('tests');
    //     return response()->json($testCategory);
    // }

    public function update(Request $request, TestCategory $testCategory): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:test_categories,name,' . $testCategory->id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $testCategory->update($request->all());
        return response()->json($testCategory);
    }

    public function destroy(TestCategory $testCategory): JsonResponse
    {
        if ($testCategory->tests()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with associated tests'
            ], 422);
        }

        $testCategory->delete();
        return response()->json(null, 204);
    }
} 