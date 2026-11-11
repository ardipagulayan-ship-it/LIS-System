<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\MaintenanceSchedule;
use App\Models\ServiceLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EIMDashboardController extends Controller
{
    /**
     * Get comprehensive EIM dashboard data
     */
    public function index(): JsonResponse
    {
        // Equipment statistics
        $equipmentStats = $this->getEquipmentStatistics();
        
        // Maintenance statistics
        $maintenanceStats = $this->getMaintenanceStatistics();
        
        // Service statistics
        $serviceStats = $this->getServiceStatistics();
        
        // Compliance metrics
        $complianceMetrics = $this->getComplianceMetrics();
        
        // Recent activities
        $recentActivities = $this->getRecentActivities();
        
        // Upcoming maintenance
        $upcomingMaintenance = $this->getUpcomingMaintenance();
        
        // Overdue items
        $overdueItems = $this->getOverdueItems();

        return response()->json([
            'equipment' => $equipmentStats,
            'maintenance' => $maintenanceStats,
            'service' => $serviceStats,
            'compliance' => $complianceMetrics,
            'recent_activities' => $recentActivities,
            'upcoming_maintenance' => $upcomingMaintenance,
            'overdue_items' => $overdueItems
        ]);
    }

    /**
     * Get equipment statistics
     */
    private function getEquipmentStatistics(): array
    {
        $total = Equipment::count();
        $inService = Equipment::inService()->count();
        $underMaintenance = Equipment::underMaintenance()->count();
        $calibrationDue = Equipment::calibrationDue()->count();
        $overdue = Equipment::overdueForMaintenance()->count();

        $statusBreakdown = Equipment::selectRaw('current_status, COUNT(*) as count')
            ->groupBy('current_status')
            ->pluck('count', 'current_status');

        $locationBreakdown = Equipment::selectRaw('location_lab, COUNT(*) as count')
            ->groupBy('location_lab')
            ->pluck('count', 'location_lab');

        return [
            'total' => $total,
            'in_service' => $inService,
            'under_maintenance' => $underMaintenance,
            'calibration_due' => $calibrationDue,
            'overdue' => $overdue,
            'operational_percentage' => $total > 0 ? round(($inService / $total) * 100, 2) : 0,
            'status_breakdown' => $statusBreakdown,
            'location_breakdown' => $locationBreakdown
        ];
    }

    /**
     * Get maintenance statistics
     */
    private function getMaintenanceStatistics(): array
    {
        $total = MaintenanceSchedule::active()->count();
        $overdue = MaintenanceSchedule::overdue()->active()->count();
        $dueSoon = MaintenanceSchedule::dueSoon(30)->active()->count();

        $byType = MaintenanceSchedule::active()
            ->selectRaw('schedule_type, COUNT(*) as count')
            ->groupBy('schedule_type')
            ->pluck('count', 'schedule_type');

        $complianceRate = $total > 0 ? round((($total - $overdue) / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
            'compliance_rate' => $complianceRate,
            'by_type' => $byType
        ];
    }

    /**
     * Get service statistics
     */
    private function getServiceStatistics(): array
    {
        $total = ServiceLog::count();
        $passed = ServiceLog::passed()->count();
        $failed = ServiceLog::failed()->count();

        $byType = ServiceLog::selectRaw('service_type, COUNT(*) as count')
            ->groupBy('service_type')
            ->pluck('count', 'service_type');

        $byOutcome = ServiceLog::selectRaw('outcome, COUNT(*) as count')
            ->groupBy('outcome')
            ->pluck('count', 'outcome');

        $totalCost = ServiceLog::sum('cost');
        $avgDowntime = ServiceLog::whereNotNull('downtime_start')
            ->whereNotNull('downtime_end')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, downtime_start, downtime_end)) as avg_hours')
            ->value('avg_hours');

        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
            'by_type' => $byType,
            'by_outcome' => $byOutcome,
            'total_cost' => $totalCost,
            'average_downtime_hours' => round($avgDowntime ?? 0, 2)
        ];
    }

    /**
     * Get compliance metrics
     */
    private function getComplianceMetrics(): array
    {
        $totalEquipment = Equipment::count();
        $compliantEquipment = Equipment::where('current_status', 'in_service')
            ->where(function($q) {
                $q->whereNull('next_maintenance_date')
                  ->orWhere('next_maintenance_date', '>', Carbon::now());
            })
            ->count();

        $totalSchedules = MaintenanceSchedule::active()->count();
        $compliantSchedules = MaintenanceSchedule::active()
            ->where(function($q) {
                $q->whereNull('next_due_date')
                  ->orWhere('next_due_date', '>', Carbon::now());
            })
            ->count();

        return [
            'equipment_compliance_rate' => $totalEquipment > 0 ? round(($compliantEquipment / $totalEquipment) * 100, 2) : 0,
            'schedule_compliance_rate' => $totalSchedules > 0 ? round(($compliantSchedules / $totalSchedules) * 100, 2) : 0,
            'overall_compliance_rate' => $totalEquipment > 0 && $totalSchedules > 0 
                ? round((($compliantEquipment + $compliantSchedules) / ($totalEquipment + $totalSchedules)) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        $recentServiceLogs = ServiceLog::with(['equipment', 'technician'])
            ->latest('service_date')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => 'service',
                    'equipment' => $log->equipment->unique_asset_id,
                    'action' => $log->action_taken,
                    'technician' => $log->technician?->name,
                    'date' => $log->service_date,
                    'outcome' => $log->outcome
                ];
            });

        $recentStatusChanges = Equipment::with(['updatedBy'])
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($equipment) {
                return [
                    'id' => $equipment->id,
                    'type' => 'status_change',
                    'equipment' => $equipment->unique_asset_id,
                    'action' => "Status changed to {$equipment->current_status}",
                    'user' => $equipment->updatedBy?->name,
                    'date' => $equipment->updated_at,
                    'status' => $equipment->current_status
                ];
            });

        return $recentServiceLogs->concat($recentStatusChanges)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Get upcoming maintenance
     */
    private function getUpcomingMaintenance(): array
    {
        return MaintenanceSchedule::with(['equipment', 'assignedPersonnel'])
            ->where('next_due_date', '<=', Carbon::now()->addDays(30))
            ->where('next_due_date', '>', Carbon::now())
            ->active()
            ->orderBy('next_due_date')
            ->limit(10)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'equipment' => $schedule->equipment->unique_asset_id,
                    'type' => $schedule->schedule_type,
                    'due_date' => $schedule->next_due_date,
                    'assigned_to' => $schedule->assignedPersonnel?->name ?? $schedule->assigned_vendor,
                    'days_until_due' => Carbon::now()->diffInDays($schedule->next_due_date, false)
                ];
            })
            ->toArray();
    }

    /**
     * Get overdue items
     */
    private function getOverdueItems(): array
    {
        $overdueSchedules = MaintenanceSchedule::with(['equipment', 'assignedPersonnel'])
            ->overdue()
            ->active()
            ->orderBy('next_due_date')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'type' => 'schedule',
                    'equipment' => $schedule->equipment->unique_asset_id,
                    'schedule_type' => $schedule->schedule_type,
                    'due_date' => $schedule->next_due_date,
                    'assigned_to' => $schedule->assignedPersonnel?->name ?? $schedule->assigned_vendor,
                    'days_overdue' => Carbon::now()->diffInDays($schedule->next_due_date)
                ];
            });

        $overdueEquipment = Equipment::overdueForMaintenance()
            ->get()
            ->map(function ($equipment) {
                return [
                    'id' => $equipment->id,
                    'type' => 'equipment',
                    'equipment' => $equipment->unique_asset_id,
                    'status' => $equipment->current_status,
                    'next_maintenance_date' => $equipment->next_maintenance_date,
                    'days_overdue' => $equipment->next_maintenance_date 
                        ? Carbon::now()->diffInDays($equipment->next_maintenance_date) 
                        : 0
                ];
            });

        return $overdueSchedules->concat($overdueEquipment)
            ->sortBy('days_overdue')
            ->values()
            ->toArray();
    }

    /**
     * Get compliance dashboard data
     */
    public function compliance(): JsonResponse
    {
        $complianceData = $this->getComplianceMetrics();
        
        $equipmentCompliance = Equipment::selectRaw('
            current_status,
            COUNT(*) as count,
            AVG(CASE 
                WHEN next_maintenance_date IS NULL OR next_maintenance_date > NOW() THEN 1 
                ELSE 0 
            END) as compliance_rate
        ')
        ->groupBy('current_status')
        ->get();

        $scheduleCompliance = MaintenanceSchedule::selectRaw('
            schedule_type,
            COUNT(*) as count,
            AVG(CASE 
                WHEN next_due_date IS NULL OR next_due_date > NOW() THEN 1 
                ELSE 0 
            END) as compliance_rate
        ')
        ->active()
        ->groupBy('schedule_type')
        ->get();

        return response()->json([
            'overall' => $complianceData,
            'equipment_by_status' => $equipmentCompliance,
            'schedules_by_type' => $scheduleCompliance
        ]);
    }

    /**
     * Get cost analysis
     */
    public function costAnalysis(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', Carbon::now()->subYear());
        $dateTo = $request->get('date_to', Carbon::now());

        $serviceCosts = ServiceLog::whereBetween('service_date', [$dateFrom, $dateTo])
            ->selectRaw('
                equipment_id,
                service_type,
                SUM(cost) as total_cost,
                COUNT(*) as service_count,
                AVG(cost) as avg_cost
            ')
            ->groupBy(['equipment_id', 'service_type'])
            ->with('equipment')
            ->get();

        $equipmentCosts = Equipment::with(['serviceLogs' => function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('service_date', [$dateFrom, $dateTo]);
        }])
        ->get()
        ->map(function ($equipment) {
            $totalCost = $equipment->serviceLogs->sum('cost');
            $serviceCount = $equipment->serviceLogs->count();
            
            return [
                'equipment_id' => $equipment->id,
                'unique_asset_id' => $equipment->unique_asset_id,
                'manufacturer' => $equipment->manufacturer,
                'model' => $equipment->model,
                'total_cost' => $totalCost,
                'service_count' => $serviceCount,
                'avg_cost_per_service' => $serviceCount > 0 ? $totalCost / $serviceCount : 0
            ];
        });

        return response()->json([
            'service_costs' => $serviceCosts,
            'equipment_costs' => $equipmentCosts,
            'total_cost' => $serviceCosts->sum('total_cost'),
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ]);
    }
}
