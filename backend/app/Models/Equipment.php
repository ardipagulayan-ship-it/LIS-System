<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_asset_id',
        'manufacturer',
        'model',
        'serial_number',
        'purchase_date',
        'warranty_expiration_date',
        'location_lab',
        'location_bench',
        'current_status',
        'test_category_id',
        'sop_operation_manual_link',
        'cycle_count',
        'runtime_hours',
        'last_maintenance_date',
        'next_maintenance_date',
        'total_repair_cost',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiration_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'cycle_count' => 'integer',
        'runtime_hours' => 'decimal:2',
        'total_repair_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Status constants
    const STATUS_IN_SERVICE = 'in_service';
    const STATUS_UNDER_MAINTENANCE = 'under_maintenance';
    const STATUS_RETIRED = 'retired';
    const STATUS_CALIBRATION_DUE = 'calibration_due';
    const STATUS_OUT_OF_SERVICE = 'out_of_service';

    public static function getStatuses()
    {
        return [
            self::STATUS_IN_SERVICE => 'In Service',
            self::STATUS_UNDER_MAINTENANCE => 'Under Maintenance',
            self::STATUS_RETIRED => 'Retired',
            self::STATUS_CALIBRATION_DUE => 'Calibration Due',
            self::STATUS_OUT_OF_SERVICE => 'Out of Service'
        ];
    }

    // Relationships
    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function serviceLogs()
    {
        return $this->hasMany(ServiceLog::class);
    }

    public function testResults()
    {
        return $this->hasMany(TestResult::class, 'equipment_id');
    }

    /**
     * Get quality controls for this equipment.
     */
    public function qualityControls()
    {
        return $this->hasMany(\App\Models\QualityControl::class, 'equipment_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reagentAssignments()
    {
        return $this->hasMany(EquipmentReagentAssignment::class, 'equipment_id');
    }

    public function usageRecords()
    {
        return $this->hasMany(EquipmentUsage::class, 'equipment_id');
    }

    /**
     * Get the test category that this equipment belongs to.
     */
    public function testCategory()
    {
        return $this->belongsTo(TestCategory::class);
    }

    // Scopes
    public function scopeInService($query)
    {
        return $query->where('current_status', self::STATUS_IN_SERVICE);
    }

    public function scopeUnderMaintenance($query)
    {
        return $query->where('current_status', self::STATUS_UNDER_MAINTENANCE);
    }

    public function scopeCalibrationDue($query)
    {
        return $query->where('current_status', self::STATUS_CALIBRATION_DUE);
    }

    public function scopeOverdueForMaintenance($query)
    {
        return $query->where('next_maintenance_date', '<', Carbon::now());
    }

    // Helper methods
    public function isOperational()
    {
        return $this->current_status === self::STATUS_IN_SERVICE;
    }

    public function isUnderMaintenance()
    {
        return $this->current_status === self::STATUS_UNDER_MAINTENANCE;
    }

    public function isCalibrationDue()
    {
        return $this->current_status === self::STATUS_CALIBRATION_DUE;
    }

    public function isOverdueForMaintenance()
    {
        return $this->next_maintenance_date && $this->next_maintenance_date < Carbon::now();
    }

    public function canBeUsedForTesting()
    {
        return $this->isOperational() && !$this->isOverdueForMaintenance();
    }

    public function getStatusBadgeClass()
    {
        return match($this->current_status) {
            self::STATUS_IN_SERVICE => 'badge-success',
            self::STATUS_UNDER_MAINTENANCE => 'badge-warning',
            self::STATUS_CALIBRATION_DUE => 'badge-danger',
            self::STATUS_OUT_OF_SERVICE => 'badge-secondary',
            self::STATUS_RETIRED => 'badge-dark',
            default => 'badge-secondary'
        };
    }

    public function calculateMTBF()
    {
        $serviceLogs = $this->serviceLogs()
            ->where('service_type', 'repair')
            ->where('outcome', 'pass')
            ->orderBy('service_date')
            ->get();

        if ($serviceLogs->count() < 2) {
            return null;
        }

        $totalUptime = 0;
        $repairCount = 0;

        for ($i = 0; $i < $serviceLogs->count() - 1; $i++) {
            $current = $serviceLogs[$i];
            $next = $serviceLogs[$i + 1];
            
            $uptime = $current->service_date->diffInDays($next->service_date);
            $totalUptime += $uptime;
            $repairCount++;
        }

        return $repairCount > 0 ? $totalUptime / $repairCount : null;
    }

    public function calculateMTTR()
    {
        $repairLogs = $this->serviceLogs()
            ->where('service_type', 'repair')
            ->whereNotNull('downtime_start')
            ->whereNotNull('downtime_end')
            ->get();

        if ($repairLogs->isEmpty()) {
            return null;
        }

        $totalDowntime = $repairLogs->sum(function ($log) {
            return $log->downtime_start->diffInHours($log->downtime_end);
        });

        return $totalDowntime / $repairLogs->count();
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($equipment) {
            if (auth()->check()) {
                $equipment->created_by = auth()->id();
            }
        });

        static::updating(function ($equipment) {
            if (auth()->check()) {
                $equipment->updated_by = auth()->id();
            }
        });
    }
}

