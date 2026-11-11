<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentReagentAssignment;
use App\Models\EquipmentUsage;
use App\Models\MaterialMaster;
use App\Models\LotStockRecord;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EquipmentUsageController extends Controller
{
    /**
     * Get Equipment Usage Statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $equipmentId = $request->get('equipment_id');
        $startDate = $request->get('start_date', Carbon::now()->subMonth()->startOfDay());
        $endDate = $request->get('end_date', Carbon::now()->endOfDay());

        $stats = [
            'equipment' => null,
            'total_tests' => 0,
            'total_qc_runs' => 0,
            'total_calibrations' => 0,
            'total_reagent_usage' => 0,
            'total_control_usage' => 0,
            'total_cost' => 0,
            'usage_by_material' => [],
            'usage_by_test' => [],
            'daily_usage' => []
        ];

        if ($equipmentId) {
            $equipment = Equipment::with('analyzers')->find($equipmentId);
            $stats['equipment'] = $equipment;

            $usage = EquipmentUsage::where('equipment_id', $equipmentId)
                ->whereBetween('usage_date', [$startDate, $endDate]);

            $stats['total_tests'] = (clone $usage)->patientTests()->count();
            $stats['total_qc_runs'] = (clone $usage)->QCRuns()->count();
            $stats['total_calibrations'] = (clone $usage)->calibrations()->count();
            
            // Total reagent usage
            $reagentUsage = (clone $usage)->whereHas('material', function($q) {
                $q->where('material_type', 'reagent');
            });
            $stats['total_reagent_usage'] = $reagentUsage->sum('quantity_used');
            
            // Total control usage
            $controlUsage = (clone $usage)->whereHas('material', function($q) {
                $q->where('material_type', 'control');
            });
            $stats['total_control_usage'] = $controlUsage->sum('quantity_used');
            
            // Total cost
            $stats['total_cost'] = (clone $usage)->join('lot_stock_records', 'equipment_usage.lot_id', '=', 'lot_stock_records.id')
                ->selectRaw('SUM(equipment_usage.quantity_used * lot_stock_records.cost_per_unit) as total')
                ->value('total') ?? 0;

            // Usage by material
            $stats['usage_by_material'] = (clone $usage)
                ->select('material_masters.id', 'material_masters.generic_name', 'material_masters.material_type')
                ->selectRaw('SUM(equipment_usage.quantity_used) as total_usage')
                ->selectRaw('COUNT(equipment_usage.id) as usage_count')
                ->join('material_masters', 'equipment_usage.material_id', '=', 'material_masters.id')
                ->groupBy('material_masters.id', 'material_masters.generic_name', 'material_masters.material_type')
                ->get();

            // Usage by test
            $stats['usage_by_test'] = (clone $usage)
                ->select('tests.id', 'tests.name')
                ->selectRaw('COUNT(equipment_usage.id) as test_count')
                ->selectRaw('SUM(equipment_usage.quantity_used) as total_usage')
                ->join('tests', 'equipment_usage.test_id', '=', 'tests.id')
                ->groupBy('tests.id', 'tests.name')
                ->get();

            // Daily usage for the last 30 days
            $stats['daily_usage'] = (clone $usage)
                ->selectRaw('DATE(usage_date) as date')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(quantity_used) as quantity')
                ->groupByRaw('DATE(usage_date)')
                ->orderByRaw('DATE(usage_date) DESC')
                ->limit(30)
                ->get();
        }

        return response()->json($stats);
    }

    /**
     * Get All Equipment for Card View
     */
    public function getAllEquipment(Request $request): JsonResponse
    {
        $query = Equipment::select('id', 'manufacturer', 'model', 'current_status', 'test_category_id')
            ->with([
                'testCategory',
                'reagentAssignments' => function($q) {
                    $q->where('is_active', true)
                      ->with(['material', 'lot', 'test']);
                }
            ]);

        // Filter by test category if provided
        if ($request->has('test_category_id')) {
            $query->where('test_category_id', $request->test_category_id);
        }

        $equipment = $query->get();

        return response()->json($equipment);
    }

    /**
     * Get Equipment Assignments
     */
    public function getAssignments(Request $request): JsonResponse
    {
        $query = EquipmentReagentAssignment::with([
            'equipment', 'lot', 'material', 'test'
        ]);

        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->has('material_type')) {
            $query->whereHas('material', function($q) use ($request) {
                $q->where('material_type', $request->material_type);
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $assignments = $query->orderBy('assigned_at', 'desc')
                           ->paginate($request->get('per_page', 20));

        return response()->json($assignments);
    }

    /**
     * Get Usage History for Equipment
     */
    public function getUsageHistory(Request $request): JsonResponse
    {
        $equipmentId = $request->get('equipment_id');
        
        $query = EquipmentUsage::with([
            'material', 'lot', 'test', 'usedBy'
        ]);

        if ($equipmentId) {
            $query->where('equipment_id', $equipmentId);
        }

        if ($request->has('material_type')) {
            $query->whereHas('material', function($q) use ($request) {
                $q->where('material_type', $request->material_type);
            });
        }

        $usage = $query->orderBy('usage_date', 'desc')
                      ->limit($request->get('limit', 50))
                      ->get();

        return response()->json($usage);
    }

    /**
     * Create Equipment Assignment
     */
    public function createAssignment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'lot_id' => 'required|exists:lot_stock_records,id',
            'material_id' => 'required|exists:material_masters,id',
            'test_id' => 'nullable|exists:tests,id',
            'quantity_per_test' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Verify lot belongs to material
            $lot = LotStockRecord::findOrFail($validated['lot_id']);
            if ($lot->material_id != $validated['material_id']) {
                return response()->json([
                    'message' => 'Lot does not belong to the specified material'
                ], 400);
            }

            // Check if lot is usable
            if (!$lot->isUsable()) {
                return response()->json([
                    'message' => 'Lot is not usable (expired, depleted, or in quarantine)'
                ], 400);
            }

            // Check if there's enough stock (this is for assignment tracking, actual deduction happens on usage)
            if ($lot->current_stock_level <= 0) {
                return response()->json([
                    'message' => 'Lot has no available stock'
                ], 400);
            }

            $assignment = EquipmentReagentAssignment::create($validated);

            // Create an initial usage record for the assignment
            // This tracks that the material has been assigned/loaded onto the equipment
            EquipmentUsage::create([
                'equipment_id' => $validated['equipment_id'],
                'assignment_id' => $assignment->id,
                'lot_id' => $validated['lot_id'],
                'material_id' => $validated['material_id'],
                'test_id' => $validated['test_id'],
                'quantity_used' => $validated['quantity_per_test'], // Initial assignment quantity
                'usage_type' => 'manual',
                'usage_date' => now(),
                'notes' => 'Initial assignment: ' . ($validated['notes'] ?? '')
            ]);

            DB::commit();
            return response()->json($assignment->load(['equipment', 'lot', 'material', 'test']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Usage Records
     */
    public function getUsage(Request $request): JsonResponse
    {
        $query = EquipmentUsage::with([
            'equipment', 'lot', 'material', 'test', 'usedBy'
        ]);

        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->has('material_id')) {
            $query->where('material_id', $request->material_id);
        }

        if ($request->has('usage_type')) {
            $query->where('usage_type', $request->usage_type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('usage_date', [$request->start_date, $request->end_date]);
        }

        $usage = $query->orderBy('usage_date', 'desc')
                      ->paginate($request->get('per_page', 20));

        return response()->json($usage);
    }

    /**
     * Record Equipment Usage
     */
    public function recordUsage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'lot_id' => 'required|exists:lot_stock_records,id',
            'material_id' => 'required|exists:material_masters,id',
            'test_id' => 'nullable|exists:tests,id',
            'quantity_used' => 'required|numeric|min:0.01',
            'usage_type' => 'required|in:patient_test,qc_run,calibration,maintenance,manual',
            'usage_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        try {
            $usage = EquipmentUsage::create($validated);
            return response()->json($usage->load(['equipment', 'lot', 'material', 'test']), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to record usage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Assignment
     */
    public function deleteAssignment($id): JsonResponse
    {
        $assignment = EquipmentReagentAssignment::findOrFail($id);
        
        try {
            $assignment->delete();
            return response()->json(['message' => 'Assignment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate Assignment
     */
    public function deactivateAssignment(Request $request, $id): JsonResponse
    {
        $assignment = EquipmentReagentAssignment::findOrFail($id);
        
        try {
            $assignment->deactivate();
            return response()->json([
                'message' => 'Assignment deactivated successfully',
                'assignment' => $assignment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
