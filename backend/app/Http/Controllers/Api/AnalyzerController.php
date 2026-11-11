<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analyzer;
use App\Models\TestParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AnalyzerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $analyzers = Analyzer::with('testCategory')->get();
        return response()->json($analyzers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_no' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'test_category_id' => 'nullable|exists:test_categories,id',
            'isActive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $analyzer = new Analyzer($request->all());
        $analyzer->created_by = Auth::id();
        $analyzer->save();

        return response()->json($analyzer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $analyzer = Analyzer::with('testCategory')->findOrFail($id);
        return response()->json($analyzer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $analyzer = Analyzer::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_no' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'test_category_id' => 'nullable|exists:test_categories,id',
            'isActive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $analyzer->update($request->all());
        
        return response()->json($analyzer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $analyzer = Analyzer::findOrFail($id);
        $analyzer->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Get analyzers for a specific test category
     */
    public function getByTestCategory($testCategoryId)
    {
        $analyzers = Analyzer::where('test_category_id', $testCategoryId)
                            ->where('isActive', true)
                            ->get();
        
        return response()->json($analyzers);
    }

    /**
     * Create standard analyzers and convert existing string values
     */
    public function migrateExistingAnalyzers(Request $request)
    {
        // Define standard analyzers to create
        $standardAnalyzers = [
            ['name' => 'Other Test', 'brand' => 'Generic', 'isActive' => true],
            ['name' => 'Automated', 'brand' => 'Generic', 'isActive' => true],
            ['name' => 'Manual', 'brand' => 'Generic', 'isActive' => true],
            ['name' => 'Microscopy', 'brand' => 'Generic', 'isActive' => true],
            ['name' => 'Hematology Analyzer', 'brand' => 'Generic', 'isActive' => true],
        ];
        
        $createdAnalyzers = [];
        
        // Create these standard analyzers if they don't exist
        foreach ($standardAnalyzers as $analyzerData) {
            $analyzer = Analyzer::firstOrCreate(
                ['name' => $analyzerData['name']],
                array_merge($analyzerData, ['created_by' => Auth::id()])
            );
            $createdAnalyzers[] = $analyzer;
        }
        
        // If migration flag is set, update reference ranges with string analyzer values
        $migrateExisting = $request->input('migrate', false);
        $migrationCount = 0;
        
        if ($migrateExisting) {
            // Get a mapping of names to IDs for the analyzers
            $analyzerMap = Analyzer::pluck('id', 'name')->toArray();
            
            // Find reference ranges with string analyzer values but no analyzer_id
            $ranges = \App\Models\ReferenceRange::whereNotNull('analyzer')
                                              ->whereNull('analyzer_id')
                                              ->get();
            
            foreach ($ranges as $range) {
                // Look for an analyzer ID that matches the string name
                if (isset($analyzerMap[$range->analyzer])) {
                    $range->analyzer_id = $analyzerMap[$range->analyzer];
                    $range->save();
                    $migrationCount++;
                } else {
                    // If no match, create a new analyzer for this string
                    $newAnalyzer = Analyzer::create([
                        'name' => $range->analyzer,
                        'brand' => 'Migrated',
                        'isActive' => true,
                        'created_by' => Auth::id()
                    ]);
                    
                    $range->analyzer_id = $newAnalyzer->id;
                    $range->save();
                    $migrationCount++;
                    $createdAnalyzers[] = $newAnalyzer;
                }
            }
        }
        
        return response()->json([
            'created_analyzers' => $createdAnalyzers,
            'migrated_ranges' => $migrationCount
        ]);
    }

    /**
     * Get test parameters for a specific analyzer
     */
    public function getTestParameters($id)
    {
        $analyzer = Analyzer::findOrFail($id);
        $testParameters = TestParameter::where('test_category_id', $analyzer->test_category_id)
                                     ->orderBy('test_name')
                                     ->get();
        
        return response()->json($testParameters);
    }
}
