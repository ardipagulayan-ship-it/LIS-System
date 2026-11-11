<?php

namespace App\Observers;

use App\Models\Equipment;
use Illuminate\Support\Facades\Log;

class EquipmentObserver
{
    /**
     * Handle the Equipment "created" event.
     */
    public function created(Equipment $equipment): void
    {
        $this->logActivity($equipment, 'created');
    }

    /**
     * Handle the Equipment "updated" event.
     */
    public function updated(Equipment $equipment): void
    {
        $this->logActivity($equipment, 'updated');
        
        // Check if status changed
        if ($equipment->isDirty('current_status')) {
            $this->logStatusChange($equipment);
        }
        
        // Check if maintenance date changed
        if ($equipment->isDirty('next_maintenance_date')) {
            $this->logMaintenanceDateChange($equipment);
        }
    }

    /**
     * Handle the Equipment "deleted" event.
     */
    public function deleted(Equipment $equipment): void
    {
        $this->logActivity($equipment, 'deleted');
    }

    /**
     * Handle the Equipment "restored" event.
     */
    public function restored(Equipment $equipment): void
    {
        $this->logActivity($equipment, 'restored');
    }

    /**
     * Handle the Equipment "force deleted" event.
     */
    public function forceDeleted(Equipment $equipment): void
    {
        $this->logActivity($equipment, 'force_deleted');
    }

    /**
     * Log equipment activity
     */
    private function logActivity(Equipment $equipment, string $action): void
    {
        Log::info('Equipment ' . $action, [
            'equipment_id' => $equipment->id,
            'unique_asset_id' => $equipment->unique_asset_id,
            'action' => $action,
            'user_id' => auth()->id(),
            'timestamp' => now(),
            'changes' => $equipment->getChanges()
        ]);
    }

    /**
     * Log status change
     */
    private function logStatusChange(Equipment $equipment): void
    {
        $oldStatus = $equipment->getOriginal('current_status');
        $newStatus = $equipment->current_status;

        Log::info('Equipment status changed', [
            'equipment_id' => $equipment->id,
            'unique_asset_id' => $equipment->unique_asset_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);

        // Send notification if equipment becomes unavailable
        if (in_array($newStatus, ['under_maintenance', 'calibration_due', 'out_of_service'])) {
            $this->notifyEquipmentUnavailable($equipment);
        }
    }

    /**
     * Log maintenance date change
     */
    private function logMaintenanceDateChange(Equipment $equipment): void
    {
        $oldDate = $equipment->getOriginal('next_maintenance_date');
        $newDate = $equipment->next_maintenance_date;

        Log::info('Equipment maintenance date changed', [
            'equipment_id' => $equipment->id,
            'unique_asset_id' => $equipment->unique_asset_id,
            'old_date' => $oldDate,
            'new_date' => $newDate,
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);
    }

    /**
     * Notify that equipment is unavailable
     */
    private function notifyEquipmentUnavailable(Equipment $equipment): void
    {
        // In a real implementation, you would send notifications to relevant users
        Log::warning('Equipment unavailable for testing', [
            'equipment_id' => $equipment->id,
            'unique_asset_id' => $equipment->unique_asset_id,
            'status' => $equipment->current_status,
            'timestamp' => now()
        ]);
    }
}

