<?php

namespace App\Http\Controllers\Api;

use App\Models\TestFormula;
use App\Models\TestParameter;
use App\Models\TestCategory;
use App\Models\TestFormulaVariable;
use App\Services\CalculationEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FormulaController extends \App\Http\Controllers\Controller
{
    protected CalculationEngine $calculationEngine;

    public function __construct(CalculationEngine $calculationEngine)
    {
        $this->calculationEngine = $calculationEngine;
    }

    /**
     * GET /api/test-categories
     * Fetch all test categories for the dropdown in the formula builder.
     */
    public function getTestCategories(): JsonResponse
    {
        try {
            $categories = TestCategory::select('id', 'name', 'description')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/test-parameters?category_id={id}
     * Fetch test parameters filtered by category.
     */
    public function getTestParameters(Request $request): JsonResponse
    {
        try {
            $categoryId = $request->query('category_id');

            $query = TestParameter::with('test')
                ->select('test_parameters.id', 'test_parameters.test_name', 'test_parameters.test_id', 
                         'test_parameters.unit', 'test_parameters.test_code');

            if ($categoryId) {
                $query->whereHas('test', function ($q) use ($categoryId) {
                    $q->where('test_category_id', $categoryId);
                });
            }

            $parameters = $query->orderBy('test_parameters.test_name')->get();

            return response()->json([
                'success' => true,
                'data' => $parameters,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test parameters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/formulas
     * Save a new formula or update an existing one.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'target_test_parameter_id' => 'required|integer|exists:test_parameters,id',
                'formula_json' => 'required|array',
                'compiled_formula' => 'required|string',
                'decimal_places' => 'integer|min:0|max:10',
                'is_active' => 'boolean',
                'source_parameter_ids' => 'array|exists:test_parameters,id',
            ]);

            // Validate formula syntax
            $this->calculationEngine->validateFormulaSyntax($validated['compiled_formula']);

            // Check if formula already exists for this parameter
            $existingFormula = TestFormula::where('target_test_parameter_id', $validated['target_test_parameter_id'])->first();

            if ($existingFormula) {
                // Update existing formula
                $existingFormula->update([
                    'formula_json' => $validated['formula_json'],
                    'compiled_formula' => $validated['compiled_formula'],
                    'decimal_places' => $validated['decimal_places'] ?? 2,
                    'is_active' => $validated['is_active'] ?? true,
                ]);
                $formula = $existingFormula;
            } else {
                // Create new formula
                $formula = TestFormula::create([
                    'target_test_parameter_id' => $validated['target_test_parameter_id'],
                    'formula_json' => $validated['formula_json'],
                    'compiled_formula' => $validated['compiled_formula'],
                    'decimal_places' => $validated['decimal_places'] ?? 2,
                    'is_active' => $validated['is_active'] ?? true,
                ]);
            }

            // Sync source parameters (variables)
            $sourceParameterIds = $validated['source_parameter_ids'] ?? [];
            
            // Delete old variables
            TestFormulaVariable::where('formula_id', $formula->id)->delete();
            
            // Create new variables
            foreach ($sourceParameterIds as $paramId) {
                TestFormulaVariable::create([
                    'formula_id' => $formula->id,
                    'source_test_parameter_id' => $paramId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $existingFormula ? 'Formula updated successfully' : 'Formula created successfully',
                'data' => $formula->load('sourceVariables.sourceParameter'),
            ], $existingFormula ? 200 : 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save formula',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/formulas
     * Fetch all active formulas for the frontend calculator.
     */
    public function index(): JsonResponse
    {
        try {
            $formulas = TestFormula::where('is_active', true)
                ->with([
                    'targetParameter',
                    'sourceVariables.sourceParameter',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $formulas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch formulas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/formulas/{id}
     * Fetch a specific formula by ID.
     */
    public function show($id): JsonResponse
    {
        try {
            $formula = TestFormula::with([
                'targetParameter',
                'sourceVariables.sourceParameter',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $formula,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Formula not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch formula',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/formulas/{id}
     * Update a formula.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $formula = TestFormula::findOrFail($id);

            $validated = $request->validate([
                'formula_json' => 'json',
                'compiled_formula' => 'string',
                'decimal_places' => 'integer|min:0|max:10',
                'is_active' => 'boolean',
                'source_parameter_ids' => 'array|exists:test_parameters,id',
            ]);

            // Validate formula syntax if provided
            if (isset($validated['compiled_formula'])) {
                $this->calculationEngine->validateFormulaSyntax($validated['compiled_formula']);
            }

            $formula->update($validated);

            // Sync source parameters if provided
            if (isset($validated['source_parameter_ids'])) {
                TestFormulaVariable::where('formula_id', $formula->id)->delete();
                foreach ($validated['source_parameter_ids'] as $paramId) {
                    TestFormulaVariable::create([
                        'formula_id' => $formula->id,
                        'source_test_parameter_id' => $paramId,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Formula updated successfully',
                'data' => $formula->load('sourceVariables.sourceParameter'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Formula not found',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update formula',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/formulas/{id}
     * Delete a formula.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $formula = TestFormula::findOrFail($id);
            
            // Check if this parameter is a dependency in other formulas
            $dependentFormulas = TestFormulaVariable::where('source_test_parameter_id', $formula->target_test_parameter_id)
                ->count();

            if ($dependentFormulas > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete: This parameter is used in {$dependentFormulas} other formula(s)",
                ], 409);
            }

            // Delete the formula and its variables
            TestFormulaVariable::where('formula_id', $formula->id)->delete();
            $formula->delete();

            return response()->json([
                'success' => true,
                'message' => 'Formula deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Formula not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete formula',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/formulas/parameter/{parameterId}/dependents
     * Get all formulas that depend on a specific parameter.
     */
    public function getDependentFormulas($parameterId): JsonResponse
    {
        try {
            $dependentFormulas = TestFormulaVariable::where('source_test_parameter_id', $parameterId)
                ->with('formula')
                ->get()
                ->pluck('formula')
                ->unique('id');

            return response()->json([
                'success' => true,
                'data' => $dependentFormulas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dependent formulas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
