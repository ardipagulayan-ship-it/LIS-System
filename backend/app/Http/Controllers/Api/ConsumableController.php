<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\ConsumableUsage;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConsumableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Consumable::with(['tests', 'usageHistory' => function($q) {
            $q->latest()->limit(10);
        }]);

        // Filter by expiration status
        if ($request->has('expired')) {
            if ($request->boolean('expired')) {
                $query->expired();
            } else {
                $query->where('is_expired', false);
            }
        }

        // Filter by stock level
        if ($request->has('low_stock')) {
            if ($request->boolean('low_stock')) {
                $query->lowStock();
            }
        }

        // Filter by locked status
        if ($request->has('locked')) {
            $query->where('is_locked', $request->boolean('locked'));
        }

        // Filter by expiring soon (optional days parameter)
        $days = $request->get('expiring_days', null);
        if ($days !== null) {
            $query->expiringSoon($days);
        }

        // Search by various fields
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('material_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%");
            });
        }

        $consumables = $query->orderBy('expiration_date', 'asc')
                            ->paginate($request->get('per_page', 15));

        return response()->json($consumables);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_id' => 'nullable|string|unique:consumables,material_id',
            'name' => 'required|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'catalog_number' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
            'current_stock_level' => 'required|numeric|min:0',
            'unit_of_measure' => 'nullable|string|max:50',
            'storage_location' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|numeric|min:0',
            'equipment_id' => 'nullable|exists:equipment,id',
            'test_ids' => 'nullable|array',
            'test_ids.*' => 'exists:tests,id',
            'quantity_per_test' => 'nullable|array',
            'quantity_per_test.*' => 'numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $consumableData = $validated;
            unset($consumableData['test_ids'], $consumableData['quantity_per_test']);
            
            // Log for debugging
            \Log::info('Creating consumable with data:', $consumableData);

            $consumable = Consumable::create($consumableData);

            // Attach tests if provided
            if (isset($validated['test_ids'])) {
                $syncData = [];
                foreach ($validated['test_ids'] as $index => $testId) {
                    $syncData[$testId] = [
                        'quantity_per_test' => $validated['quantity_per_test'][$index] ?? 1
                    ];
                }
                $consumable->tests()->attach($syncData);
            }

            DB::commit();
            return response()->json($consumable->load('tests'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create consumable', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to create consumable',
                'error' => $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : 'Check server logs for details'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $consumable = Consumable::with([
            'tests',
            'usageHistory' => function($q) {
                $q->with('test', 'usedBy')->latest()->limit(50);
            },
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);

        // Calculate stats
        $stats = [
            'total_usage' => $consumable->usageHistory()->sum('quantity_used'),
            'usage_count' => $consumable->usageHistory()->count(),
            'days_until_expiration' => $consumable->getDaysUntilExpiration(),
            'expiration_status' => $consumable->getExpirationStatus()
        ];

        return response()->json([
            'consumable' => $consumable,
            'stats' => $stats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $consumable = Consumable::findOrFail($id);

        $validated = $request->validate([
            'material_id' => 'sometimes|string|unique:consumables,material_id,' . $id,
            'name' => 'sometimes|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'catalog_number' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
            'current_stock_level' => 'sometimes|numeric|min:0',
            'unit_of_measure' => 'nullable|string|max:50',
            'storage_location' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|numeric|min:0',
            'equipment_id' => 'nullable|exists:equipment,id',
            'is_expired' => 'sometimes|boolean',
            'is_locked' => 'sometimes|boolean',
            'test_ids' => 'nullable|array',
            'test_ids.*' => 'exists:tests,id'
        ]);

        DB::beginTransaction();
        try {
            $consumableData = $validated;
            unset($consumableData['test_ids'], $consumableData['quantity_per_test']);

            $consumable->update($consumableData);

            // Sync tests if provided
            if (isset($validated['test_ids'])) {
                $syncData = [];
                foreach ($validated['test_ids'] as $index => $testId) {
                    $syncData[$testId] = [
                        'quantity_per_test' => $validated['quantity_per_test'][$index] ?? 1
                    ];
                }
                $consumable->tests()->sync($syncData);
            }

            DB::commit();
            return response()->json($consumable->load('tests'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update consumable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $consumable = Consumable::findOrFail($id);
        $consumable->delete();

        return response()->json(['message' => 'Consumable deleted successfully']);
    }

    /**
     * Deduct stock from consumable
     */
    public function deductStock(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'usage_type' => 'sometimes|in:automatic,manual,wastage',
            'test_id' => 'nullable|exists:tests,id',
            'notes' => 'nullable|string'
        ]);

        $consumable = Consumable::findOrFail($id);

        if (!$consumable->isUsable()) {
            return response()->json([
                'message' => 'Consumable is not usable'
            ], 400);
        }

        try {
            $consumable->deductStock($validated['quantity']);

            ConsumableUsage::create([
                'consumable_id' => $consumable->id,
                'quantity_used' => $validated['quantity'],
                'usage_type' => $validated['usage_type'] ?? 'manual',
                'test_id' => $validated['test_id'] ?? null,
                'notes' => $validated['notes'] ?? null
            ]);

            return response()->json([
                'message' => 'Stock deducted successfully',
                'consumable' => $consumable->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deduct stock',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Add stock to consumable
     */
    public function addStock(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01'
        ]);

        $consumable = Consumable::findOrFail($id);
        $consumable->addStock($validated['quantity']);

        return response()->json([
            'message' => 'Stock added successfully',
            'consumable' => $consumable->fresh()
        ]);
    }

    /**
     * Get expiration alerts
     */
    public function getExpirationAlerts(): JsonResponse
    {
        $alerts = [
            'critical' => Consumable::whereBetween('expiration_date', [
                Carbon::now(),
                Carbon::now()->addDays(30)
            ])->where('is_expired', false)->get(),
            
            'warning' => Consumable::whereBetween('expiration_date', [
                Carbon::now()->addDays(30),
                Carbon::now()->addDays(60)
            ])->where('is_expired', false)->get(),
            
            'alert' => Consumable::whereBetween('expiration_date', [
                Carbon::now()->addDays(60),
                Carbon::now()->addDays(90)
            ])->where('is_expired', false)->get(),
            
            'expired' => Consumable::where('is_expired', true)->get()
        ];

        return response()->json($alerts);
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(): JsonResponse
    {
        $lowStockItems = Consumable::lowStock()->get();

        return response()->json([
            'count' => $lowStockItems->count(),
            'items' => $lowStockItems
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        $stats = [
            'total_consumables' => Consumable::count(),
            'active_consumables' => Consumable::active()->count(),
            'expired_consumables' => Consumable::expired()->count(),
            'low_stock' => Consumable::lowStock()->count(),
            'expiring_in_30_days' => Consumable::whereBetween('expiration_date', [
                Carbon::now(),
                Carbon::now()->addDays(30)
            ])->where('is_expired', false)->count(),
            'total_value' => Consumable::sum(DB::raw('current_stock_level'))
        ];

        return response()->json($stats);
    }
}
