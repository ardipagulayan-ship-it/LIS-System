<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QcRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class QcRuleController extends Controller
{
    /**
     * Display a listing of QC rules.
     */
    public function index(Request $request)
    {
        $query = QcRule::with(['testParameter', 'equipment', 'qualityControlTestParameter', 'createdBy', 'updatedBy']);

        // Search by rule name
        if ($request->has('search') && $request->search) {
            $query->where('rule_name', 'like', '%' . $request->search . '%');
        }

        // Filter by test parameter
        if ($request->has('test_parameter_id') && $request->test_parameter_id) {
            $query->where('test_parameter_id', $request->test_parameter_id);
        }

        // Filter by equipment
        if ($request->has('equipment_id') && $request->equipment_id) {
            $query->where('equipment_id', $request->equipment_id);
        }

        // Filter by Westgard rule type
        if ($request->has('westgard_rule_type') && $request->westgard_rule_type) {
            $query->where('westgard_rule_type', $request->westgard_rule_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active == 'true' || $request->is_active === true);
        }

        // Filter by enabled status
        if ($request->has('is_enabled')) {
            $query->where('is_enabled', $request->is_enabled == 'true' || $request->is_enabled === true);
        }

        $perPage = $request->input('per_page', 15);
        $rules = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($rules);
    }

    /**
     * Display the specified QC rule.
     */
    public function show(QcRule $qcRule)
    {
        $qcRule->load(['testParameter', 'equipment', 'qualityControlTestParameter', 'createdBy', 'updatedBy']);
        return response()->json($qcRule);
    }

    /**
     * Store a newly created QC rule.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|string|max:255',
            'test_parameter_id' => 'nullable|integer|exists:test_parameters,id',
            'equipment_id' => 'nullable|integer|exists:equipment,id',
            'quality_control_test_parameter_id' => 'nullable|integer|exists:quality_control_test_parameter,id',
            'westgard_rule_type' => 'required|in:1-2s,1-3s,2-2s,R-4s,4-1s,10x',
            'is_enabled' => 'boolean',
            'action_on_violation' => 'required|in:warning,reject,flag',
            'severity_level' => 'required|in:info,warning,error,critical',
            'alert_message' => 'nullable|string',
            'custom_threshold_multiplier' => 'nullable|numeric|min:0.1|max:10',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $rule = QcRule::create($data);

        $rule->load(['testParameter', 'equipment', 'qualityControlTestParameter', 'createdBy', 'updatedBy']);

        return response()->json($rule, 201);
    }

    /**
     * Update the specified QC rule.
     */
    public function update(Request $request, QcRule $qcRule)
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'sometimes|required|string|max:255',
            'test_parameter_id' => 'nullable|integer|exists:test_parameters,id',
            'equipment_id' => 'nullable|integer|exists:equipment,id',
            'quality_control_test_parameter_id' => 'nullable|integer|exists:quality_control_test_parameter,id',
            'westgard_rule_type' => 'sometimes|required|in:1-2s,1-3s,2-2s,R-4s,4-1s,10x',
            'is_enabled' => 'boolean',
            'action_on_violation' => 'sometimes|required|in:warning,reject,flag',
            'severity_level' => 'sometimes|required|in:info,warning,error,critical',
            'alert_message' => 'nullable|string',
            'custom_threshold_multiplier' => 'nullable|numeric|min:0.1|max:10',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['updated_by'] = Auth::id();

        $qcRule->update($data);

        $qcRule->load(['testParameter', 'equipment', 'qualityControlTestParameter', 'createdBy', 'updatedBy']);

        return response()->json($qcRule);
    }

    /**
     * Remove the specified QC rule.
     */
    public function destroy(QcRule $qcRule)
    {
        $qcRule->delete();
        return response()->json(['message' => 'QC rule deleted successfully']);
    }

    /**
     * Toggle the active status of a QC rule.
     */
    public function toggleActive(QcRule $qcRule)
    {
        $qcRule->is_active = !$qcRule->is_active;
        $qcRule->updated_by = Auth::id();
        $qcRule->save();

        $qcRule->load(['testParameter', 'equipment', 'qualityControlTestParameter', 'createdBy', 'updatedBy']);

        return response()->json([
            'message' => 'Status updated successfully',
            'rule' => $qcRule
        ]);
    }
}



