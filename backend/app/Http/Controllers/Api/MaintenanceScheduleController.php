<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class MaintenanceScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceSchedule::with(['equipment', 'assignedPersonnel']);

        // Filter by equipment
        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        // Filter by schedule type
        if ($request->has('schedule_type')) {
            $query->where('schedule_type', $request->schedule_type);
        }

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'overdue':
                    $query->overdue();
                    break;
                case 'due_soon':
                    $query->dueSoon($request->get('days', 30));
                    break;
                case 'active':
                    $query->active();
                    break;
            }
        }

        $schedules = $query->paginate($request->get('per_page', 15));

        return response()->json($schedules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'schedule_type' => ['required', Rule::in(array_keys(MaintenanceSchedule::getScheduleTypes()))],
            'frequency_months' => 'nullable|integer|min:1',
            'frequency_cycles' => 'nullable|integer|min:1',
            'frequency_hours' => 'nullable|integer|min:1',
            'assigned_personnel_id' => 'nullable|exists:users,id',
            'assigned_vendor' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        // Ensure at least one frequency is provided
        if (!$validated['frequency_months'] && !$validated['frequency_cycles'] && !$validated['frequency_hours']) {
            return response()->json([
                'message' => 'At least one frequency must be specified'
            ], 422);
        }

        $schedule = MaintenanceSchedule::create(array_merge($validated, [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]));

        return response()->json($schedule->load(['equipment', 'assignedPersonnel']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::with([
            'equipment',
            'assignedPersonnel',
            'serviceLogs' => function($q) {
                $q->latest();
            }
        ])->findOrFail($id);

        return response()->json($schedule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $validated = $request->validate([
            'equipment_id' => 'sometimes|exists:equipment,id',
            'schedule_type' => ['sometimes', Rule::in(array_keys(MaintenanceSchedule::getScheduleTypes()))],
            'frequency_months' => 'nullable|integer|min:1',
            'frequency_cycles' => 'nullable|integer|min:1',
            'frequency_hours' => 'nullable|integer|min:1',
            'assigned_personnel_id' => 'nullable|exists:users,id',
            'assigned_vendor' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $schedule->update(array_merge($validated, [
            'updated_by' => auth()->id()
        ]));

        return response()->json($schedule->load(['equipment', 'assignedPersonnel']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $schedule->delete();

        return response()->json(['message' => 'Maintenance schedule deleted successfully']);
    }

    /**
     * Get overdue schedules
     */
    public function overdue(): JsonResponse
    {
        $overdue = MaintenanceSchedule::with(['equipment', 'assignedPersonnel'])
            ->overdue()
            ->active()
            ->get();

        return response()->json($overdue);
    }

    /**
     * Get schedules due soon
     */
    public function dueSoon(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $dueSoon = MaintenanceSchedule::with(['equipment', 'assignedPersonnel'])
            ->dueSoon($days)
            ->active()
            ->get();

        return response()->json($dueSoon);
    }

    /**
     * Mark schedule as completed
     */
    public function markCompleted(Request $request, string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $validated = $request->validate([
            'completion_date' => 'nullable|date'
        ]);

        $schedule->markCompleted($validated['completion_date'] ?? null);

        return response()->json($schedule->load(['equipment', 'assignedPersonnel']));
    }

    /**
     * Generate work orders for due schedules
     */
    public function generateWorkOrders(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        
        $dueSchedules = MaintenanceSchedule::with(['equipment', 'assignedPersonnel'])
            ->where('next_due_date', '<=', Carbon::now()->addDays($days))
            ->where('next_due_date', '>', Carbon::now())
            ->active()
            ->get();

        $workOrders = $dueSchedules->map(function ($schedule) {
            return [
                'schedule_id' => $schedule->id,
                'equipment_id' => $schedule->equipment_id,
                'equipment_name' => $schedule->equipment->unique_asset_id,
                'schedule_type' => $schedule->schedule_type,
                'due_date' => $schedule->next_due_date,
                'assigned_to' => $schedule->assignedPersonnel?->name,
                'assigned_vendor' => $schedule->assigned_vendor,
                'work_order_number' => 'WO-' . str_pad($schedule->id, 6, '0', STR_PAD_LEFT)
            ];
        });

        return response()->json([
            'work_orders' => $workOrders,
            'count' => $workOrders->count()
        ]);
    }

    /**
     * Get maintenance statistics
     */
    public function statistics(): JsonResponse
    {
        $total = MaintenanceSchedule::active()->count();
        $overdue = MaintenanceSchedule::overdue()->active()->count();
        $dueSoon = MaintenanceSchedule::dueSoon(30)->active()->count();

        $byType = MaintenanceSchedule::active()
            ->selectRaw('schedule_type, COUNT(*) as count')
            ->groupBy('schedule_type')
            ->pluck('count', 'schedule_type');

        return response()->json([
            'total' => $total,
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
            'by_type' => $byType,
            'compliance_rate' => $total > 0 ? round((($total - $overdue) / $total) * 100, 2) : 0
        ]);
    }
}
