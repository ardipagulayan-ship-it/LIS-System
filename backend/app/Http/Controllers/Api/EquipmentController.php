<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\MaintenanceSchedule;
use App\Models\ServiceLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Equipment::with(['maintenanceSchedules', 'serviceLogs' => function($q) {
            $q->latest()->limit(5);
        }, 'testCategory']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }

        // Filter by test category
        if ($request->filled('test_category_id')) {
            $query->where('test_category_id', $request->test_category_id);
        }

        // Filter by location
        if ($request->filled('location_lab')) {
            $query->where('location_lab', $request->location_lab);
        }

        // Filter by overdue maintenance
        if ($request->boolean('overdue_only')) {
            $query->overdueForMaintenance();
        }

        // Search by asset ID, manufacturer, model, or serial number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('unique_asset_id', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $equipment = $query->paginate($request->get('per_page', 15));

        return response()->json($equipment);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unique_asset_id' => 'required|string|unique:equipment,unique_asset_id',
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'warranty_expiration_date' => 'nullable|date|after:purchase_date',
            'location_lab' => 'required|string|max:255',
            'location_bench' => 'nullable|string|max:255',
            'current_status' => ['required', Rule::in(array_keys(Equipment::getStatuses()))],
            'test_category_id' => 'nullable|exists:test_categories,id',
            'sop_operation_manual_link' => 'nullable|url',
            'cycle_count' => 'nullable|integer|min:0',
            'runtime_hours' => 'nullable|numeric|min:0',
        ]);

        $equipment = Equipment::create(array_merge($validated, [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]));

        return response()->json($equipment->load(['maintenanceSchedules', 'serviceLogs']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $equipment = Equipment::with([
            'maintenanceSchedules',
            'serviceLogs' => function($q) {
                $q->latest();
            },
            'testResults' => function($q) {
                $q->latest()->limit(10);
            }
        ])->findOrFail($id);

        // Manually load quality control results since the relationship goes through multiple tables
        $qualityControlIds = \App\Models\QualityControl::where('equipment_id', $equipment->id)->pluck('id');
        $qualityControlTestParameterIds = \App\Models\QualityControlTestParameter::whereIn('quality_control_id', $qualityControlIds)->pluck('id');
        $qualityControlResults = \App\Models\QualityControlResult::whereIn('quality_control_test_parameter_id', $qualityControlTestParameterIds)
            ->latest()
            ->limit(10)
            ->get();
        
        $equipment->setRelation('qualityControlResults', $qualityControlResults);

        return response()->json($equipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);

        $validated = $request->validate([
            'unique_asset_id' => 'sometimes|string|unique:equipment,unique_asset_id,' . $id,
            'manufacturer' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'serial_number' => 'sometimes|string|max:255',
            'purchase_date' => 'sometimes|date',
            'warranty_expiration_date' => 'nullable|date|after:purchase_date',
            'location_lab' => 'sometimes|string|max:255',
            'location_bench' => 'nullable|string|max:255',
            'current_status' => ['sometimes', Rule::in(array_keys(Equipment::getStatuses()))],
            'test_category_id' => 'nullable|exists:test_categories,id',
            'sop_operation_manual_link' => 'nullable|url',
            'cycle_count' => 'nullable|integer|min:0',
            'runtime_hours' => 'nullable|numeric|min:0',
        ]);

        $equipment->update(array_merge($validated, [
            'updated_by' => auth()->id()
        ]));

        return response()->json($equipment->load(['maintenanceSchedules', 'serviceLogs']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();

        return response()->json(['message' => 'Equipment deleted successfully']);
    }

    /**
     * Get equipment statistics for dashboard
     */
    public function statistics(): JsonResponse
    {
        $total = Equipment::count();
        $inService = Equipment::inService()->count();
        $underMaintenance = Equipment::underMaintenance()->count();
        $calibrationDue = Equipment::calibrationDue()->count();
        $overdue = Equipment::overdueForMaintenance()->count();

        $statusBreakdown = Equipment::selectRaw('current_status, COUNT(*) as count')
            ->groupBy('current_status')
            ->pluck('count', 'current_status');

        $upcomingMaintenance = Equipment::where('next_maintenance_date', '<=', Carbon::now()->addDays(30))
            ->where('next_maintenance_date', '>', Carbon::now())
            ->count();

        return response()->json([
            'total' => $total,
            'in_service' => $inService,
            'under_maintenance' => $underMaintenance,
            'calibration_due' => $calibrationDue,
            'overdue' => $overdue,
            'status_breakdown' => $statusBreakdown,
            'upcoming_maintenance' => $upcomingMaintenance,
            'operational_percentage' => $total > 0 ? round(($inService / $total) * 100, 2) : 0
        ]);
    }

    /**
     * Update equipment status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);

        $validated = $request->validate([
            'current_status' => ['required', Rule::in(array_keys(Equipment::getStatuses()))],
            'reason' => 'nullable|string|max:500'
        ]);

        $oldStatus = $equipment->current_status;
        $equipment->update(['current_status' => $validated['current_status']]);

        // Log status change in service log
        ServiceLog::create([
            'equipment_id' => $equipment->id,
            'service_date' => Carbon::now(),
            'service_type' => 'inspection',
            'action_taken' => "Status changed from {$oldStatus} to {$validated['current_status']}",
            'description' => $validated['reason'] ?? 'Status change',
            'outcome' => 'pass',
            'service_provider' => 'Internal',
            'technician_id' => auth()->id(),
        ]);

        return response()->json($equipment);
    }

    /**
     * Get equipment by test category
     */
    public function getByTestCategory(string $testCategoryId): JsonResponse
    {
        $equipment = Equipment::with('testCategory')
            ->where('test_category_id', $testCategoryId)
            ->get();

        return response()->json($equipment);
    }

    /**
     * Generate barcode/QR code for equipment
     */
    public function generateBarcode(string $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);
        
        // In a real implementation, you would generate actual barcode/QR code
        // For now, we'll return the asset ID and a placeholder
        return response()->json([
            'asset_id' => $equipment->unique_asset_id,
            'barcode_data' => "EIM-{$equipment->unique_asset_id}",
            'qr_code_url' => null // Would be generated by a barcode service
        ]);
    }

    /**
     * Bulk import equipment from CSV/Excel
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240' // 10MB max
        ]);

        // In a real implementation, you would process the file
        // For now, return a placeholder response
        return response()->json([
            'message' => 'Bulk import functionality would be implemented here',
            'imported_count' => 0,
            'errors' => []
        ]);
    }
}
