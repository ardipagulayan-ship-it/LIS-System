<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'maintenance_schedule_id',
        'service_date',
        'service_type',
        'action_taken',
        'description',
        'outcome',
        'service_provider',
        'technician_id',
        'reviewer_id',
        'downtime_start',
        'downtime_end',
        'cost',
        'parts_used',
        'documentation_path',
        'digital_signature_technician',
        'digital_signature_reviewer',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'service_date' => 'datetime',
        'downtime_start' => 'datetime',
        'downtime_end' => 'datetime',
        'cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Service type constants
    const TYPE_PREVENTATIVE_MAINTENANCE = 'preventative_maintenance';
    const TYPE_CALIBRATION = 'calibration';
    const TYPE_VALIDATION = 'validation';
    const TYPE_REPAIR = 'repair';
    const TYPE_INSPECTION = 'inspection';

    // Outcome constants
    const OUTCOME_PASS = 'pass';
    const OUTCOME_FAIL = 'fail';
    const OUTCOME_PARTIAL = 'partial';

    public static function getServiceTypes()
    {
        return [
            self::TYPE_PREVENTATIVE_MAINTENANCE => 'Preventative Maintenance',
            self::TYPE_CALIBRATION => 'Calibration',
            self::TYPE_VALIDATION => 'Validation',
            self::TYPE_REPAIR => 'Repair',
            self::TYPE_INSPECTION => 'Inspection'
        ];
    }

    public static function getOutcomes()
    {
        return [
            self::OUTCOME_PASS => 'Pass',
            self::OUTCOME_FAIL => 'Fail',
            self::OUTCOME_PARTIAL => 'Partial'
        ];
    }

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function maintenanceSchedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    public function scopePassed($query)
    {
        return $query->where('outcome', self::OUTCOME_PASS);
    }

    public function scopeFailed($query)
    {
        return $query->where('outcome', self::OUTCOME_FAIL);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('service_date', [$startDate, $endDate]);
    }

    public function scopeByTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    // Helper methods
    public function isPassed()
    {
        return $this->outcome === self::OUTCOME_PASS;
    }

    public function isFailed()
    {
        return $this->outcome === self::OUTCOME_FAIL;
    }

    public function isPartial()
    {
        return $this->outcome === self::OUTCOME_PARTIAL;
    }

    public function getDowntimeHours()
    {
        if (!$this->downtime_start || !$this->downtime_end) {
            return null;
        }

        return $this->downtime_start->diffInHours($this->downtime_end);
    }

    public function getDowntimeDays()
    {
        if (!$this->downtime_start || !$this->downtime_end) {
            return null;
        }

        return $this->downtime_start->diffInDays($this->downtime_end);
    }

    public function getOutcomeBadgeClass()
    {
        return match($this->outcome) {
            self::OUTCOME_PASS => 'badge-success',
            self::OUTCOME_FAIL => 'badge-danger',
            self::OUTCOME_PARTIAL => 'badge-warning',
            default => 'badge-secondary'
        };
    }

    public function getServiceTypeBadgeClass()
    {
        return match($this->service_type) {
            self::TYPE_PREVENTATIVE_MAINTENANCE => 'badge-primary',
            self::TYPE_CALIBRATION => 'badge-info',
            self::TYPE_VALIDATION => 'badge-warning',
            self::TYPE_REPAIR => 'badge-danger',
            self::TYPE_INSPECTION => 'badge-secondary',
            default => 'badge-secondary'
        };
    }

    public function hasDocumentation()
    {
        return !empty($this->documentation_path);
    }

    public function isSignedByTechnician()
    {
        return !empty($this->digital_signature_technician);
    }

    public function isSignedByReviewer()
    {
        return !empty($this->digital_signature_reviewer);
    }

    public function isFullySigned()
    {
        return $this->isSignedByTechnician() && $this->isSignedByReviewer();
    }

    public function getTotalCost()
    {
        $totalCost = $this->cost ?? 0;
        
        // Add parts cost if available
        if ($this->parts_used) {
            $parts = json_decode($this->parts_used, true);
            if (is_array($parts)) {
                foreach ($parts as $part) {
                    if (isset($part['cost'])) {
                        $totalCost += $part['cost'];
                    }
                }
            }
        }

        return $totalCost;
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($serviceLog) {
            if (auth()->check()) {
                $serviceLog->created_by = auth()->id();
            }
        });

        static::updating(function ($serviceLog) {
            if (auth()->check()) {
                $serviceLog->updated_by = auth()->id();
            }
        });

        static::created(function ($serviceLog) {
            // Update equipment's last maintenance date
            if ($serviceLog->equipment && $serviceLog->isPassed()) {
                $serviceLog->equipment->update([
                    'last_maintenance_date' => $serviceLog->service_date
                ]);
            }

            // Update maintenance schedule if linked
            if ($serviceLog->maintenanceSchedule && $serviceLog->isPassed()) {
                $serviceLog->maintenanceSchedule->markCompleted($serviceLog->service_date);
            }
        });
    }
}

