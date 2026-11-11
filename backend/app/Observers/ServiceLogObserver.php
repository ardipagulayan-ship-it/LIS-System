<?php

namespace App\Observers;

use App\Models\ServiceLog;
use App\Models\Equipment;
use Illuminate\Support\Facades\Log;

class ServiceLogObserver
{
    /**
     * Handle the ServiceLog "created" event.
     */
    public function created(ServiceLog $serviceLog): void
    {
        $this->logActivity($serviceLog, 'created');
        
        // Update equipment status if service was successful
        if ($serviceLog->isPassed()) {
            $this->updateEquipmentAfterService($serviceLog);
        }
        
        // Check for QC failure triggers
        if ($serviceLog->isFailed()) {
            $this->handleServiceFailure($serviceLog);
        }
    }

    /**
     * Handle the ServiceLog "updated" event.
     */
    public function updated(ServiceLog $serviceLog): void
    {
        $this->logActivity($serviceLog, 'updated');
        
        // If outcome changed from fail to pass, update equipment
        if ($serviceLog->isDirty('outcome') && $serviceLog->isPassed()) {
            $this->updateEquipmentAfterService($serviceLog);
        }
    }

    /**
     * Handle the ServiceLog "deleted" event.
     */
    public function deleted(ServiceLog $serviceLog): void
    {
        $this->logActivity($serviceLog, 'deleted');
    }

    /**
     * Log service log activity
     */
    private function logActivity(ServiceLog $serviceLog, string $action): void
    {
        Log::info('Service log ' . $action, [
            'service_log_id' => $serviceLog->id,
            'equipment_id' => $serviceLog->equipment_id,
            'equipment_asset_id' => $serviceLog->equipment->unique_asset_id ?? 'Unknown',
            'service_type' => $serviceLog->service_type,
            'outcome' => $serviceLog->outcome,
            'action' => $action,
            'user_id' => auth()->id(),
            'timestamp' => now(),
            'changes' => $serviceLog->getChanges()
        ]);
    }

    /**
     * Update equipment after successful service
     */
    private function updateEquipmentAfterService(ServiceLog $serviceLog): void
    {
        $equipment = $serviceLog->equipment;
        
        if (!$equipment) {
            return;
        }

        // Update last maintenance date
        $equipment->update([
            'last_maintenance_date' => $serviceLog->service_date->toDateString()
        ]);

        // If this was a calibration or validation, update status to in_service
        if (in_array($serviceLog->service_type, ['calibration', 'validation']) && 
            $equipment->current_status === 'calibration_due') {
            $equipment->update(['current_status' => 'in_service']);
        }

        // If this was maintenance, update status to in_service
        if ($serviceLog->service_type === 'preventative_maintenance' && 
            $equipment->current_status === 'under_maintenance') {
            $equipment->update(['current_status' => 'in_service']);
        }

        Log::info('Equipment updated after successful service', [
            'equipment_id' => $equipment->id,
            'unique_asset_id' => $equipment->unique_asset_id,
            'service_log_id' => $serviceLog->id,
            'service_type' => $serviceLog->service_type,
            'new_status' => $equipment->current_status
        ]);
    }

    /**
     * Handle service failure
     */
    private function handleServiceFailure(ServiceLog $serviceLog): void
    {
        $equipment = $serviceLog->equipment;
        
        if (!$equipment) {
            return;
        }

        // If calibration failed, mark equipment as calibration_due
        if ($serviceLog->service_type === 'calibration') {
            $equipment->update(['current_status' => 'calibration_due']);
        }

        // If maintenance failed, keep equipment under maintenance
        if ($serviceLog->service_type === 'preventative_maintenance') {
            $equipment->update(['current_status' => 'under_maintenance']);
        }

        // Log the failure for investigation
        Log::warning('Service failure detected', [
            'equipment_id' => $equipment->id,
            'unique_asset_id' => $equipment->unique_asset_id,
            'service_log_id' => $serviceLog->id,
            'service_type' => $serviceLog->service_type,
            'outcome' => $serviceLog->outcome,
            'action_taken' => $serviceLog->action_taken,
            'timestamp' => now()
        ]);

        // In a real implementation, you would:
        // 1. Send notifications to maintenance team
        // 2. Create follow-up work orders
        // 3. Update equipment status appropriately
    }
}

