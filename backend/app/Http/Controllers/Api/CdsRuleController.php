<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CdsCorrelationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CdsRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = CdsCorrelationRule::query();

        // Search by rule name
        if ($request->has('search') && $request->search) {
            $query->where('rule_name', 'like', '%' . $request->search . '%');
        }

        // Filter by test category using join for better performance
        if ($request->has('test_category_id') && $request->test_category_id) {
            $categoryId = is_numeric($request->test_category_id) ? (int)$request->test_category_id : $request->test_category_id;
            $query->join('test_parameters', 'cds_correlation_rules.trigger_test_id', '=', 'test_parameters.id')
                  ->join('tests', 'test_parameters.test_id', '=', 'tests.id')
                  ->where('tests.test_category_id', $categoryId)
                  ->select('cds_correlation_rules.*')
                  ->distinct();
        }

        $rules = $query->orderBy('cds_correlation_rules.id', 'desc')->paginate($request->input('per_page', 15));

        // Eager load relationships after pagination
        $rules->load(['triggerTest.test.testCategory']);

        // Add category information to each rule for easier frontend access
        $rules->getCollection()->transform(function($rule) {
            if ($rule->triggerTest && $rule->triggerTest->test && $rule->triggerTest->test->testCategory) {
                $rule->test_category_name = $rule->triggerTest->test->testCategory->name;
                $rule->test_category_id = $rule->triggerTest->test->testCategory->id;
            }
            return $rule;
        });

        return response()->json($rules);
    }

    public function show(CdsCorrelationRule $cdsRule)
    {
        return response()->json($cdsRule);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|string|max:255',
            'trigger_test_id' => 'required|integer|exists:test_parameters,id',
            'rule_logic_json' => 'required', // JSON
            'correlated_tests' => 'nullable|string',
            'alert_message' => 'nullable|string',
            'action_type' => 'required|in:STOP_AUTO_VERIFY,REFLEX_TEST,ADD_COMMENT',
            'reflex_test_id' => 'nullable|integer|exists:test_parameters,id',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payload = $request->all();
        if (is_string($payload['rule_logic_json'])) {
            $payload['rule_logic_json'] = json_decode($payload['rule_logic_json'], true);
        }

        $rule = CdsCorrelationRule::create($payload);
        return response()->json($rule, 201);
    }

    public function update(Request $request, CdsCorrelationRule $cdsRule)
    {
        $validator = Validator::make($request->all(), [
            'rule_name' => 'sometimes|required|string|max:255',
            'trigger_test_id' => 'sometimes|required|integer|exists:test_parameters,id',
            'rule_logic_json' => 'sometimes|required',
            'correlated_tests' => 'nullable|string',
            'alert_message' => 'nullable|string',
            'action_type' => 'sometimes|required|in:STOP_AUTO_VERIFY,REFLEX_TEST,ADD_COMMENT',
            'reflex_test_id' => 'nullable|integer|exists:test_parameters,id',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payload = $request->all();
        if (array_key_exists('rule_logic_json', $payload) && is_string($payload['rule_logic_json'])) {
            $payload['rule_logic_json'] = json_decode($payload['rule_logic_json'], true);
        }
        $cdsRule->update($payload);
        return response()->json($cdsRule);
    }

    public function destroy(CdsCorrelationRule $cdsRule)
    {
        $cdsRule->delete();
        return response()->json(['message' => 'Deleted']);
    }
}



