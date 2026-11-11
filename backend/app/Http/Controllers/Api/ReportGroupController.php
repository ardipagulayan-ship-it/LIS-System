<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReportGroup;
use App\Models\ReportLayout;
use App\Models\TestParameter;

class ReportGroupController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->query('test_category_id');
        $query = ReportGroup::with('layout');
        if ($categoryId) $query->where('test_category_id', $categoryId);
        $groups = $query->orderBy('sort_order')->get();
        return response()->json($groups);
    }

    public function show($id)
    {
        $group = ReportGroup::with('layout')->findOrFail($id);
        return response()->json($group);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'test_category_id' => 'nullable|integer',
            'report_layout_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer'
        ]);

        $group = ReportGroup::create([
            'name' => $data['name'],
            'test_category_id' => $data['test_category_id'] ?? null,
            'report_layout_id' => $data['report_layout_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0
        ]);

        return response()->json($group, 201);
    }

    public function update(Request $request, $id)
    {
        $group = ReportGroup::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'test_category_id' => 'nullable|integer',
            'report_layout_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer'
        ]);

        $group->update($data);
        return response()->json($group);
    }

    public function destroy($id)
    {
        $group = ReportGroup::findOrFail($id);
        $group->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Assign a test parameter to a report group
    public function assignParameter(Request $request, $parameterId)
    {
        $data = $request->validate([
            'report_group_id' => 'nullable|integer'
        ]);

        $param = TestParameter::findOrFail($parameterId);
        $param->report_group_id = $data['report_group_id'] ?? null;
        $param->save();

        return response()->json($param);
    }
}
