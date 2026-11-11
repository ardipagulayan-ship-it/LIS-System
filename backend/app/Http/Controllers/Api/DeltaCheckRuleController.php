<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeltaCheckRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DeltaCheckRuleController extends Controller
{
    /**
     * Display a listing of delta check rules.
     */
    public function index(Request $request)
    {
        $query = DeltaCheckRule::query();

        // Search by rule name
        if ($request->has('search') && $request->search) {
            $query->where('rule_name', 'like', '%' . $request->search . '%');
        }

        // Filter by test parameter
        if ($request->has('test_parameter_id') && $request->test_parameter_id) {
            $query->where('test_parameter_id', $request->test_parameter_id);
        }

        // Filter by test category using join for better performance
        if ($request->has('test_category_id') && $request->test_category_id) {
            $categoryId = is_numeric($request->test_category_id) ? (int)$request->test_category_id : $request->test_category_id;
            $query->join('test_parameters', 'delta_check_rules.test_parameter_id', '=', 'test_parameters.id')
                  ->join('tests', 'test_parameters.test_id', '=', 'tests.id')
                  ->where('tests.test_category_id', $categoryId)
                  ->select('delta_check_rules.*')
                  ->distinct();
        }

        // Filter by equipment
        if ($request->has('equipment_id') && $request->equipment_id) {
            $query->where('equipment_id', $request->equipment_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->input('per_page', 15);
        $rules = $query->orderBy('delta_check_rules.id', 'desc')->paginate($perPage);

        // Eager load relationships after pagination
        $rules->load([
            'testParameter.test.testCategory',
            'equipment',
            'severityLevel',
            'createdBy',
            'updatedBy'
        ]);

        // Add category information to each rule for easier frontend access
        $rules->getCollection()->transform(function($rule) {
            if ($rule->testParameter && $rule->testParameter->test && $rule->testParameter->test->testCategory) {
                $rule->test_category_name = $rule->testParameter->test->testCategory->name;
                $rule->test_category_id = $rule->testParameter->test->testCategory->id;
            }
            return $rule;
        });

        return response()->json($rules);
    }

    /**
     * Display the specified delta check rule.
     */
    public function show(DeltaCheckRule $deltaCheckRule)
    {
        $deltaCheckRule->load(['testParameter', 'equipment', 'severityLevel', 'createdBy', 'updatedBy']);
        return response()->json($deltaCheckRule);
    }

    /**
     * Store a newly created delta check rule.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|string|max:255',
            'test_parameter_id' => 'required|integer|exists:test_parameters,id',
            'equipment_id' => 'nullable|integer|exists:equipment,id',
            'percentage_limit' => 'nullable|numeric|min:0|max:100',
            'absolute_limit' => 'nullable|numeric|min:0',
            'critical_threshold' => 'nullable|numeric|min:0|max:100',
            'time_window_days' => 'nullable|integer|min:1|max:365',
            'comparison_type' => 'required|in:percentage,absolute,both',
            'action_on_violation' => 'required|in:flag,flag_and_stop,alert_only',
            'alert_message' => 'nullable|string',
            'severity_level_id' => 'nullable|integer|exists:delta_check_severity_levels,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure at least one limit is provided
        if (!$request->percentage_limit && !$request->absolute_limit) {
            return response()->json([
                'errors' => ['percentage_limit' => ['At least one limit (percentage or absolute) must be provided.']]
            ], 422);
        }

        $data = $request->all();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $rule = DeltaCheckRule::create($data);

        $rule->load(['testParameter', 'equipment', 'severityLevel', 'createdBy', 'updatedBy']);

        return response()->json($rule, 201);
    }

    /**
     * Update the specified delta check rule.
     */
    public function update(Request $request, DeltaCheckRule $deltaCheckRule)
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'sometimes|required|string|max:255',
            'test_parameter_id' => 'sometimes|required|integer|exists:test_parameters,id',
            'equipment_id' => 'nullable|integer|exists:equipment,id',
            'percentage_limit' => 'nullable|numeric|min:0|max:100',
            'absolute_limit' => 'nullable|numeric|min:0',
            'critical_threshold' => 'nullable|numeric|min:0|max:100',
            'time_window_days' => 'nullable|integer|min:1|max:365',
            'comparison_type' => 'sometimes|required|in:percentage,absolute,both',
            'action_on_violation' => 'sometimes|required|in:flag,flag_and_stop,alert_only',
            'alert_message' => 'nullable|string',
            'severity_level_id' => 'nullable|integer|exists:delta_check_severity_levels,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Ensure at least one limit is provided if limits are being updated
        if ($request->has('percentage_limit') || $request->has('absolute_limit')) {
            $percentageLimit = $request->has('percentage_limit') ? $request->percentage_limit : $deltaCheckRule->percentage_limit;
            $absoluteLimit = $request->has('absolute_limit') ? $request->absolute_limit : $deltaCheckRule->absolute_limit;
            
            if (!$percentageLimit && !$absoluteLimit) {
                return response()->json([
                    'errors' => ['percentage_limit' => ['At least one limit (percentage or absolute) must be provided.']]
                ], 422);
            }
        }

        $data = $request->all();
        $data['updated_by'] = Auth::id();

        $deltaCheckRule->update($data);

        $deltaCheckRule->load(['testParameter', 'equipment', 'severityLevel', 'createdBy', 'updatedBy']);

        return response()->json($deltaCheckRule);
    }

    /**
     * Remove the specified delta check rule.
     */
    public function destroy(DeltaCheckRule $deltaCheckRule)
    {
        $deltaCheckRule->delete();
        return response()->json(['message' => 'Delta check rule deleted successfully']);
    }

    /**
     * Toggle the active status of a delta check rule.
     */
    public function toggleActive(DeltaCheckRule $deltaCheckRule)
    {
        $deltaCheckRule->is_active = !$deltaCheckRule->is_active;
        $deltaCheckRule->updated_by = Auth::id();
        $deltaCheckRule->save();

        $deltaCheckRule->load(['testParameter', 'equipment', 'severityLevel', 'createdBy', 'updatedBy']);

        return response()->json([
            'message' => 'Status updated successfully',
            'rule' => $deltaCheckRule
        ]);
    }
}



