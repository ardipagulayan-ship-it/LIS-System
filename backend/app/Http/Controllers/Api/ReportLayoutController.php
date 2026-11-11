<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReportLayout;

class ReportLayoutController extends Controller
{
    public function index(Request $request)
    {
        $layouts = ReportLayout::orderBy('name')->get();
        return response()->json($layouts);
    }

    public function show($id)
    {
        $layout = ReportLayout::findOrFail($id);
        return response()->json($layout);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'layout_json' => 'nullable',
            'sections_json' => 'nullable',
            'page_config' => 'nullable',
            'page_size' => 'nullable|string|max:20'
        ]);

        // Prefer explicit sections_json/page_config if provided; keep backward compatibility with layout_json
        $sections = $data['sections_json'] ?? null;
        if (is_string($sections)) {
            $decoded = json_decode($sections, true);
            $sections = $decoded ?? $sections;
        }

        $pageConfig = $data['page_config'] ?? null;
        if (is_string($pageConfig)) {
            $decoded = json_decode($pageConfig, true);
            $pageConfig = $decoded ?? $pageConfig;
        }

        $layout = ReportLayout::create([
            'name' => $data['name'],
            'layout_json' => $data['layout_json'] ?? null,
            'sections_json' => $sections,
            'page_config' => $pageConfig,
            'page_size' => $data['page_size'] ?? ($pageConfig['page_size'] ?? 'A4')
        ]);

        return response()->json($layout, 201);
    }

    public function update(Request $request, $id)
    {
        $layout = ReportLayout::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'layout_json' => 'nullable',
            'sections_json' => 'nullable',
            'page_config' => 'nullable',
            'page_size' => 'nullable|string|max:20'
        ]);

        $sections = $data['sections_json'] ?? null;
        if (is_string($sections)) {
            $decoded = json_decode($sections, true);
            $sections = $decoded ?? $sections;
        }

        $pageConfig = $data['page_config'] ?? null;
        if (is_string($pageConfig)) {
            $decoded = json_decode($pageConfig, true);
            $pageConfig = $decoded ?? $pageConfig;
        }

        $layout->update([
            'name' => $data['name'],
            'layout_json' => $data['layout_json'] ?? $layout->layout_json,
            'sections_json' => $sections ?? $layout->sections_json,
            'page_config' => $pageConfig ?? $layout->page_config,
            'page_size' => $data['page_size'] ?? ($pageConfig['page_size'] ?? $layout->page_size)
        ]);

        return response()->json($layout);
    }

    public function destroy($id)
    {
        $layout = ReportLayout::findOrFail($id);
        $layout->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
