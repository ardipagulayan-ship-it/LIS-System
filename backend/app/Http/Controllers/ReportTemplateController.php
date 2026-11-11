<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportTemplateController extends Controller
{
    /**
     * List all report templates
     */
    public function index(): JsonResponse
    {
        $templates = ReportTemplate::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($templates);
    }

    /**
     * Get a specific template
     */
    public function show($id): JsonResponse
    {
        $template = ReportTemplate::findOrFail($id);

        return response()->json($template);
    }

    /**
     * Create a new template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:report_templates,code|max:100',
            'schema_data' => 'required|array',
            'page_settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template = ReportTemplate::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'schema_data' => $validated['schema_data'],
            'page_settings' => $validated['page_settings'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($template, 201);
    }

    /**
     * Update a template
     */
    public function update(Request $request, $id): JsonResponse
    {
        $template = ReportTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:report_templates,code,' . $id . '|max:100',
            'schema_data' => 'required|array',
            'page_settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    /**
     * Delete a template
     */
    public function destroy($id): JsonResponse
    {
        $template = ReportTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }

    /**
     * Clone a template
     */
    public function clone(Request $request, $id): JsonResponse
    {
        $original = ReportTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:report_templates,code|max:100',
        ]);

        $cloned = ReportTemplate::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'schema_data' => $original->schema_data,
            'page_settings' => $original->page_settings,
            'is_active' => true,
        ]);

        return response()->json($cloned, 201);
    }

    /**
     * Search templates
     */
    public function search(Request $request): JsonResponse
    {
        $query = ReportTemplate::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('code')) {
            $query->where('code', 'like', '%' . $request->input('code') . '%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        $results = $query->paginate(15);

        return response()->json($results);
    }

    /**
     * Get template schema only
     */
    public function getSchema($id): JsonResponse
    {
        $template = ReportTemplate::findOrFail($id);

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'code' => $template->code,
            'schema_data' => $template->schema_data,
            'page_settings' => $template->page_settings,
        ]);
    }

    /**
     * Provide preview data for a template. Attempts to use a recent laboratory
     * request and the ReportService to prepare realistic data. Falls back to
     * synthetic sample data if no laboratory request exists.
     */
    public function previewData($id): JsonResponse
    {
        $template = ReportTemplate::findOrFail($id);

        $reportService = app()->make('\App\\Services\\ReportService');

        // Try to use a recent laboratory request as sample data
        $labModel = '\App\\Models\\LaboratoryRequest';
        $lab = $labModel::latest()->first();

        if ($lab) {
            try {
                $prepared = $reportService->prepareDataForTemplate($template, $lab->id);
                return response()->json($prepared);
            } catch (\Illuminate\Database\Eloquent\RelationNotFoundException $e) {
                // Some environments may not have all relationships defined on the
                // LaboratoryRequest model (for example 'sample'). Fall through to
                // the synthetic fallback below instead of crashing the preview.
                logger()->warning('previewData: relation not found when preparing template', ['error' => $e->getMessage()]);
            } catch (\Exception $e) {
                // Any other error should also fallback to synthetic data for preview.
                logger()->warning('previewData: error preparing template preview', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: return a simple synthetic data shape that the frontend can use
        $sample = [
            'schema' => $template->schema_data ?? [],
            'data' => [
                'patient' => [
                    'id' => null,
                    'pid' => 'SAMPLE-0001',
                    'name' => 'Sample Patient',
                    'first_name' => 'Sample',
                    'last_name' => 'Patient',
                    'date_of_birth' => null,
                    'age' => 30,
                    'gender' => 'Male',
                    'phone' => null,
                    'email' => null,
                ],
                'sample' => [
                    'id' => null,
                    'request_id' => 'REQ-SAMPLE-1',
                    'collected_at' => now()->toDateTimeString(),
                    'processed_at' => null,
                    'reported_at' => now()->toDateTimeString(),
                ],
                'lab' => [
                    'name' => config('app.name', 'Laboratory'),
                    'address' => config('app.address', ''),
                    'phone' => config('app.phone', ''),
                    'email' => config('app.email', ''),
                ],
                'doctor' => [],
                'tests' => [
                    [
                        'id' => 1,
                        'test_name' => 'Glucose',
                        'result_value' => '5.6',
                        'unit' => 'mmol/L',
                        'ref_range' => '3.9-5.8',
                        'ref_low' => '3.9',
                        'ref_high' => '5.8',
                        'flag' => null,
                        'status' => 'normal',
                    ],
                    [
                        'id' => 2,
                        'test_name' => 'Hemoglobin',
                        'result_value' => '13.2',
                        'unit' => 'g/dL',
                        'ref_range' => '13.0-17.0',
                        'ref_low' => '13.0',
                        'ref_high' => '17.0',
                        'flag' => null,
                        'status' => 'normal',
                    ],
                ],
                'tests_by_category' => [],
                'pathologist' => [
                    'name' => null,
                    'role' => 'Pathologist',
                    'signature_path' => null,
                ],
            ],
        ];

        return response()->json($sample);
    }

    /**
     * Validate template schema
     */
    public function validateSchema(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schema_data' => 'required|array',
        ]);

        // Basic validation - check required structure
        $schema = $validated['schema_data'];
        $errors = [];

        if (!isset($schema['canvas'])) {
            $errors[] = 'Schema must have a canvas element';
        }

        if (isset($schema['canvas']) && !isset($schema['canvas']['elements'])) {
            $errors[] = 'Canvas must have elements array';
        }

        if (empty($errors)) {
            return response()->json(['valid' => true, 'message' => 'Schema is valid']);
        }

        return response()->json(['valid' => false, 'errors' => $errors], 422);
    }
}
