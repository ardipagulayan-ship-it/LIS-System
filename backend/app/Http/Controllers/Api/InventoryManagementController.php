<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialMaster;
use App\Models\LotStockRecord;
use App\Models\Vendor;
use App\Models\PurchaseOrder;
use App\Models\LotUsageHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryManagementController extends Controller
{
    /**
     * Get IMM Dashboard Statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        $stats = [
            'materials' => [
                'total' => MaterialMaster::count(),
                'active' => MaterialMaster::active()->count(),
                'by_type' => MaterialMaster::select('material_type', DB::raw('count(*) as count'))
                    ->groupBy('material_type')
                    ->get()
                    ->pluck('count', 'material_type')
            ],
            'lots' => [
                'total' => LotStockRecord::count(),
                'active' => LotStockRecord::active()->count(),
                'quarantine' => LotStockRecord::quarantine()->count(),
                'expired' => LotStockRecord::expired()->count(),
                'low_stock' => LotStockRecord::lowStock()->count(),
                'expiring_soon' => LotStockRecord::expiringSoon(30)->count()
            ],
            'vendors' => [
                'total' => Vendor::count(),
                'active' => Vendor::active()->count()
            ],
            'alerts' => [
                'expiring_lots' => LotStockRecord::expiringSoon(30)->count(),
                'low_stock_lots' => LotStockRecord::lowStock()->count(),
                'quarantine_lots' => LotStockRecord::quarantine()->count(),
                'expired_lots' => LotStockRecord::expired()->count()
            ],
            'financial' => [
                'total_inventory_value' => LotStockRecord::sum('total_cost'),
                'pending_po_value' => PurchaseOrder::whereIn('status', ['pending', 'approved', 'ordered'])
                    ->sum('total_amount')
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Get Material Master List
     */
    public function getMaterials(Request $request): JsonResponse
    {
        $query = MaterialMaster::with(['lots', 'tests', 'vendors']);

        // Filter by material type
        if ($request->has('material_type')) {
            $query->where('material_type', $request->material_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by various fields
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('catalog_id', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('catalog_number', 'like', "%{$search}%");
            });
        }

        $materials = $query->orderBy('generic_name', 'asc')
                          ->paginate($request->get('per_page', 15));

        return response()->json($materials);
    }

    /**
     * Create Material Master
     */
    public function createMaterial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'generic_name' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'catalog_number' => 'required|string|max:255',
            'description' => 'nullable|string',
            'standard_unit_of_measure' => 'nullable|string|max:50',
            'shelf_life_days' => 'nullable|integer|min:1',
            'material_type' => 'required|in:reagent,control,calibrator,consumable,equipment',
            'storage_requirements' => 'nullable|string',
            'hazard_classification' => 'nullable|string|max:255',
            'test_ids' => 'nullable|array',
            'test_ids.*' => 'exists:tests,id',
            'vendor_ids' => 'nullable|array',
            'vendor_ids.*' => 'exists:vendors,id'
        ]);

        DB::beginTransaction();
        try {
            $material = MaterialMaster::create($validated);

            // Attach tests if provided
            if (isset($validated['test_ids'])) {
                $material->tests()->attach($validated['test_ids']);
            }

            // Attach vendors if provided
            if (isset($validated['vendor_ids'])) {
                $material->vendors()->attach($validated['vendor_ids']);
            }

            DB::commit();
            return response()->json($material->load(['tests', 'vendors']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Lot Stock Records
     */
    public function getLots(Request $request): JsonResponse
    {
        $query = LotStockRecord::with(['material', 'vendor', 'usageHistory']);

        // Filter by material
        if ($request->has('material_id')) {
            $query->where('material_id', $request->material_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by expiration status
        if ($request->has('expiring_soon')) {
            $query->expiringSoon($request->get('days', 30));
        }

        // Filter by stock level
        if ($request->has('low_stock')) {
            $query->lowStock();
        }

        // Search by lot number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhere('lot_id', 'like', "%{$search}%")
                  ->orWhereHas('material', function($subQ) use ($search) {
                      $subQ->where('generic_name', 'like', "%{$search}%");
                  });
            });
        }

        $lots = $query->orderBy('expiration_date', 'asc')
                     ->paginate($request->get('per_page', 15));

        return response()->json($lots);
    }

    /**
     * Create Lot Stock Record (Receipt Logging)
     */
    public function createLot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:material_masters,id',
            'lot_number' => 'required|string|max:255',
            'expiration_date' => 'required|date|after:today',
            'date_received' => 'required|date',
            'vendor_id' => 'required|exists:vendors,id',
            'initial_quantity' => 'required|numeric|min:0.01',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'storage_location' => 'nullable|string|max:255',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:255',
            'certificate_of_analysis' => 'nullable|string|max:255',
            'quarantine_notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Calculate total cost
            if (isset($validated['cost_per_unit']) && isset($validated['initial_quantity'])) {
                $validated['total_cost'] = $validated['cost_per_unit'] * $validated['initial_quantity'];
            }

            // Set current stock level to initial quantity
            $validated['current_stock_level'] = $validated['initial_quantity'];

            $lot = LotStockRecord::create($validated);

            DB::commit();
            return response()->json($lot->load(['material', 'vendor']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Release Lot from Quarantine
     */
    public function releaseFromQuarantine(Request $request, $lotId): JsonResponse
    {
        $lot = LotStockRecord::findOrFail($lotId);

        if ($lot->status !== LotStockRecord::STATUS_QUARANTINE) {
            return response()->json([
                'message' => 'Lot is not in quarantine'
            ], 400);
        }

        try {
            $lot->releaseFromQuarantine($request->get('notes'));
            
            return response()->json([
                'message' => 'Lot released from quarantine successfully',
                'lot' => $lot->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to release lot from quarantine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deduct Stock from Lot
     */
    public function deductStock(Request $request, $lotId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'usage_type' => 'sometimes|in:automatic,manual,wastage,disposal,transfer',
            'test_id' => 'nullable|exists:tests,id',
            'notes' => 'nullable|string'
        ]);

        $lot = LotStockRecord::findOrFail($lotId);

        try {
            $lot->deductStock(
                $validated['quantity'],
                $validated['usage_type'] ?? 'manual',
                $validated['test_id'],
                $validated['notes']
            );

            return response()->json([
                'message' => 'Stock deducted successfully',
                'lot' => $lot->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deduct stock',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get Expiration Alerts
     */
    public function getExpirationAlerts(): JsonResponse
    {
        $alerts = [
            'critical' => LotStockRecord::whereBetween('expiration_date', [
                Carbon::now(),
                Carbon::now()->addDays(30)
            ])->where('status', '!=', LotStockRecord::STATUS_EXPIRED)
              ->with(['material', 'vendor'])
              ->get(),
            
            'warning' => LotStockRecord::whereBetween('expiration_date', [
                Carbon::now()->addDays(30),
                Carbon::now()->addDays(60)
            ])->where('status', '!=', LotStockRecord::STATUS_EXPIRED)
              ->with(['material', 'vendor'])
              ->get(),
            
            'alert' => LotStockRecord::whereBetween('expiration_date', [
                Carbon::now()->addDays(60),
                Carbon::now()->addDays(90)
            ])->where('status', '!=', LotStockRecord::STATUS_EXPIRED)
              ->with(['material', 'vendor'])
              ->get(),
            
            'expired' => LotStockRecord::expired()
              ->with(['material', 'vendor'])
              ->get()
        ];

        return response()->json($alerts);
    }

    /**
     * Get Low Stock Alerts
     */
    public function getLowStockAlerts(): JsonResponse
    {
        $lowStockLots = LotStockRecord::lowStock()
            ->with(['material', 'vendor'])
            ->get();

        return response()->json([
            'count' => $lowStockLots->count(),
            'lots' => $lowStockLots
        ]);
    }

    /**
     * Get Lot Performance Metrics
     */
    public function getLotPerformance($lotId): JsonResponse
    {
        $lot = LotStockRecord::findOrFail($lotId);
        $metrics = $lot->getLotPerformanceMetrics();

        return response()->json([
            'lot' => $lot,
            'performance' => $metrics
        ]);
    }

    /**
     * Generate Reorder Report
     */
    public function getReorderReport(): JsonResponse
    {
        $lowStockLots = LotStockRecord::lowStock()
            ->with(['material', 'vendor'])
            ->get()
            ->groupBy('material_id');

        $reorderItems = [];
        foreach ($lowStockLots as $materialId => $lots) {
            $material = $lots->first()->material;
            $totalStock = $lots->sum('current_stock_level');
            $minReorderPoint = $lots->min('reorder_point');
            $maxReorderQuantity = $lots->max('reorder_quantity');

            $reorderItems[] = [
                'material' => $material,
                'current_stock' => $totalStock,
                'reorder_point' => $minReorderPoint,
                'reorder_quantity' => $maxReorderQuantity,
                'shortage' => $minReorderPoint - $totalStock,
                'lots' => $lots
            ];
        }

        return response()->json([
            'items' => $reorderItems,
            'total_items' => count($reorderItems)
        ]);
    }

    /**
     * Update Material Master
     */
    public function updateMaterial(Request $request, $id): JsonResponse
    {
        $material = MaterialMaster::findOrFail($id);
        
        $validated = $request->validate([
            'generic_name' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'catalog_number' => 'required|string|max:255',
            'description' => 'nullable|string',
            'standard_unit_of_measure' => 'nullable|string|max:50',
            'shelf_life_days' => 'nullable|integer|min:1',
            'material_type' => 'required|in:reagent,control,calibrator,consumable,equipment',
            'storage_requirements' => 'nullable|string',
            'hazard_classification' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'test_ids' => 'nullable|array',
            'test_ids.*' => 'exists:tests,id',
            'vendor_ids' => 'nullable|array',
            'vendor_ids.*' => 'exists:vendors,id'
        ]);

        DB::beginTransaction();
        try {
            $material->update($validated);

            // Sync tests if provided
            if (isset($validated['test_ids'])) {
                $material->tests()->sync($validated['test_ids']);
            }

            // Sync vendors if provided
            if (isset($validated['vendor_ids'])) {
                $material->vendors()->sync($validated['vendor_ids']);
            }

            DB::commit();
            return response()->json($material->load(['tests', 'vendors']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Material Master
     */
    public function deleteMaterial($id): JsonResponse
    {
        $material = MaterialMaster::findOrFail($id);
        
        try {
            $material->delete();
            return response()->json(['message' => 'Material deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Lot Stock Record
     */
    public function updateLot(Request $request, $id): JsonResponse
    {
        $lot = LotStockRecord::findOrFail($id);
        
        $validated = $request->validate([
            'material_id' => 'required|exists:material_masters,id',
            'lot_number' => 'required|string|max:255',
            'expiration_date' => 'required|date|after:today',
            'date_received' => 'required|date',
            'vendor_id' => 'required|exists:vendors,id',
            'initial_quantity' => 'required|numeric|min:0.01',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'storage_location' => 'nullable|string|max:255',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:255',
            'certificate_of_analysis' => 'nullable|string|max:255',
            'quarantine_notes' => 'nullable|string'
        ]);

        try {
            // Recalculate total cost if cost_per_unit or initial_quantity changed
            if (isset($validated['cost_per_unit']) && isset($validated['initial_quantity'])) {
                $validated['total_cost'] = $validated['cost_per_unit'] * $validated['initial_quantity'];
            }

            $lot->update($validated);
            return response()->json($lot->load(['material', 'vendor']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Lot Stock Record
     */
    public function deleteLot($id): JsonResponse
    {
        $lot = LotStockRecord::findOrFail($id);
        
        try {
            $lot->delete();
            return response()->json(['message' => 'Lot deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Vendors
     */
    public function getVendors(Request $request): JsonResponse
    {
        $query = Vendor::with(['materials', 'lots']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $vendors = $query->orderBy('vendor_name', 'asc')
                        ->paginate($request->get('per_page', 15));

        return response()->json($vendors);
    }

    /**
     * Create Vendor
     */
    public function createVendor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string'
        ]);

        try {
            $vendor = Vendor::create($validated);
            return response()->json($vendor, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
