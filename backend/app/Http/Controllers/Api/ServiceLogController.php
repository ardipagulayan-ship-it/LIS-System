<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceLog;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ServiceLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServiceLog::with(['equipment', 'technician', 'reviewer', 'maintenanceSchedule']);

        // Filter by equipment
        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        // Filter by service type
        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Filter by outcome
        if ($request->has('outcome')) {
            $query->where('outcome', $request->outcome);
        }

        // Filter by technician
        if ($request->has('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('service_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('service_date', '<=', $request->date_to);
        }

        $serviceLogs = $query->latest('service_date')->paginate($request->get('per_page', 15));

        return response()->json($serviceLogs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'maintenance_schedule_id' => 'nullable|exists:maintenance_schedules,id',
            'service_date' => 'required|date',
            'service_type' => ['required', Rule::in(array_keys(ServiceLog::getServiceTypes()))],
            'action_taken' => 'required|string|max:500',
            'description' => 'nullable|string',
            'outcome' => ['required', Rule::in(array_keys(ServiceLog::getOutcomes()))],
            'service_provider' => 'required|string|max:255',
            'technician_id' => 'nullable|exists:users,id',
            'reviewer_id' => 'nullable|exists:users,id',
            'downtime_start' => 'nullable|date',
            'downtime_end' => 'nullable|date|after:downtime_start',
            'cost' => 'nullable|numeric|min:0',
            'parts_used' => 'nullable|array',
            'parts_used.*.name' => 'required_with:parts_used|string',
            'parts_used.*.cost' => 'required_with:parts_used|numeric|min:0',
            'documentation' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240'
        ]);

        // Handle file upload
        if ($request->hasFile('documentation')) {
            $file = $request->file('documentation');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('service_documentation', $filename, 'public');
            $validated['documentation_path'] = $path;
        }

        $serviceLog = ServiceLog::create(array_merge($validated, [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]));

        return response()->json($serviceLog->load(['equipment', 'technician', 'reviewer']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $serviceLog = ServiceLog::with([
            'equipment',
            'technician',
            'reviewer',
            'maintenanceSchedule'
        ])->findOrFail($id);

        return response()->json($serviceLog);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $serviceLog = ServiceLog::findOrFail($id);

        $validated = $request->validate([
            'equipment_id' => 'sometimes|exists:equipment,id',
            'maintenance_schedule_id' => 'nullable|exists:maintenance_schedules,id',
            'service_date' => 'sometimes|date',
            'service_type' => ['sometimes', Rule::in(array_keys(ServiceLog::getServiceTypes()))],
            'action_taken' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'outcome' => ['sometimes', Rule::in(array_keys(ServiceLog::getOutcomes()))],
            'service_provider' => 'sometimes|string|max:255',
            'technician_id' => 'nullable|exists:users,id',
            'reviewer_id' => 'nullable|exists:users,id',
            'downtime_start' => 'nullable|date',
            'downtime_end' => 'nullable|date|after:downtime_start',
            'cost' => 'nullable|numeric|min:0',
            'parts_used' => 'nullable|array',
            'parts_used.*.name' => 'required_with:parts_used|string',
            'parts_used.*.cost' => 'required_with:parts_used|numeric|min:0',
            'documentation' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240'
        ]);

        // Handle file upload
        if ($request->hasFile('documentation')) {
            $file = $request->file('documentation');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('service_documentation', $filename, 'public');
            $validated['documentation_path'] = $path;
        }

        $serviceLog->update(array_merge($validated, [
            'updated_by' => auth()->id()
        ]));

        return response()->json($serviceLog->load(['equipment', 'technician', 'reviewer']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $serviceLog = ServiceLog::findOrFail($id);
        $serviceLog->delete();

        return response()->json(['message' => 'Service log deleted successfully']);
    }

    /**
     * Add digital signature
     */
    public function addSignature(Request $request, string $id): JsonResponse
    {
        $serviceLog = ServiceLog::findOrFail($id);

        $validated = $request->validate([
            'signature_type' => ['required', Rule::in(['technician', 'reviewer'])],
            'signature_data' => 'required|string',
            'signature_timestamp' => 'required|date'
        ]);

        $field = 'digital_signature_' . $validated['signature_type'];
        $serviceLog->update([
            $field => $validated['signature_data']
        ]);

        return response()->json($serviceLog);
    }

    /**
     * Get service statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = ServiceLog::query();

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('service_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('service_date', '<=', $request->date_to);
        }

        $total = $query->count();
        $passed = $query->clone()->passed()->count();
        $failed = $query->clone()->failed()->count();

        $byType = $query->clone()
            ->selectRaw('service_type, COUNT(*) as count')
            ->groupBy('service_type')
            ->pluck('count', 'service_type');

        $byOutcome = $query->clone()
            ->selectRaw('outcome, COUNT(*) as count')
            ->groupBy('outcome')
            ->pluck('count', 'outcome');

        $totalCost = $query->clone()->sum('cost');
        $avgDowntime = $query->clone()
            ->whereNotNull('downtime_start')
            ->whereNotNull('downtime_end')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, downtime_start, downtime_end)) as avg_hours')
            ->value('avg_hours');

        return response()->json([
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
            'by_type' => $byType,
            'by_outcome' => $byOutcome,
            'total_cost' => $totalCost,
            'average_downtime_hours' => round($avgDowntime ?? 0, 2)
        ]);
    }

    /**
     * Get equipment service history
     */
    public function equipmentHistory(string $equipmentId): JsonResponse
    {
        $serviceLogs = ServiceLog::with(['technician', 'reviewer', 'maintenanceSchedule'])
            ->where('equipment_id', $equipmentId)
            ->latest('service_date')
            ->get();

        return response()->json($serviceLogs);
    }

    /**
     * Get MTBF and MTTR for equipment
     */
    public function equipmentMetrics(string $equipmentId): JsonResponse
    {
        $equipment = Equipment::findOrFail($equipmentId);
        
        $mtbf = $equipment->calculateMTBF();
        $mttr = $equipment->calculateMTTR();

        $repairLogs = ServiceLog::where('equipment_id', $equipmentId)
            ->where('service_type', 'repair')
            ->whereNotNull('downtime_start')
            ->whereNotNull('downtime_end')
            ->get();

        $totalDowntime = $repairLogs->sum(function ($log) {
            return $log->downtime_start->diffInHours($log->downtime_end);
        });

        $uptimePercentage = $equipment->runtime_hours > 0 
            ? round((($equipment->runtime_hours - $totalDowntime) / $equipment->runtime_hours) * 100, 2)
            : 0;

        return response()->json([
            'mtbf_days' => $mtbf,
            'mttr_hours' => $mttr,
            'total_downtime_hours' => $totalDowntime,
            'uptime_percentage' => $uptimePercentage,
            'repair_count' => $repairLogs->count()
        ]);
    }
}
