<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'schedule_type',
        'frequency_months',
        'frequency_cycles',
        'frequency_hours',
        'last_completed_date',
        'next_due_date',
        'assigned_personnel_id',
        'assigned_vendor',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'last_completed_date' => 'date',
        'next_due_date' => 'date',
        'frequency_months' => 'integer',
        'frequency_cycles' => 'integer',
        'frequency_hours' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Schedule type constants
    const TYPE_PREVENTATIVE_MAINTENANCE = 'preventative_maintenance';
    const TYPE_CALIBRATION = 'calibration';
    const TYPE_VALIDATION = 'validation';
    const TYPE_REPAIR = 'repair';

    public static function getScheduleTypes()
    {
        return [
            self::TYPE_PREVENTATIVE_MAINTENANCE => 'Preventative Maintenance',
            self::TYPE_CALIBRATION => 'Calibration',
            self::TYPE_VALIDATION => 'Validation',
            self::TYPE_REPAIR => 'Repair'
        ];
    }

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignedPersonnel()
    {
        return $this->belongsTo(User::class, 'assigned_personnel_id');
    }

    public function serviceLogs()
    {
        return $this->hasMany(ServiceLog::class);
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_due_date', '<', Carbon::now());
    }

    public function scopeDueSoon($query, $days = 30)
    {
        return $query->where('next_due_date', '<=', Carbon::now()->addDays($days))
                    ->where('next_due_date', '>', Carbon::now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('schedule_type', $type);
    }

    // Helper methods
    public function isOverdue()
    {
        return $this->next_due_date && $this->next_due_date < Carbon::now();
    }

    public function isDueSoon($days = 30)
    {
        return $this->next_due_date && 
               $this->next_due_date <= Carbon::now()->addDays($days) &&
               $this->next_due_date > Carbon::now();
    }

    public function calculateNextDueDate()
    {
        $baseDate = $this->last_completed_date ?: $this->equipment->purchase_date;
        
        if (!$baseDate) {
            return null;
        }

        $nextDue = Carbon::parse($baseDate);

        // Calculate based on frequency type
        if ($this->frequency_months) {
            $nextDue->addMonths($this->frequency_months);
        } elseif ($this->frequency_cycles) {
            // This would need to be updated when cycle count changes
            $nextDue->addDays(30); // Placeholder - would need cycle tracking
        } elseif ($this->frequency_hours) {
            // This would need to be updated when runtime hours change
            $nextDue->addDays(30); // Placeholder - would need runtime tracking
        }

        return $nextDue;
    }

    public function updateNextDueDate()
    {
        $this->next_due_date = $this->calculateNextDueDate();
        $this->save();
    }

    public function markCompleted($completionDate = null)
    {
        $this->last_completed_date = $completionDate ?: Carbon::now();
        $this->updateNextDueDate();
        $this->save();
    }

    public function getStatusBadgeClass()
    {
        if ($this->isOverdue()) {
            return 'badge-danger';
        } elseif ($this->isDueSoon()) {
            return 'badge-warning';
        } else {
            return 'badge-success';
        }
    }

    public function getDaysUntilDue()
    {
        if (!$this->next_due_date) {
            return null;
        }

        $now = Carbon::now();
        if ($this->next_due_date < $now) {
            return -$now->diffInDays($this->next_due_date);
        }

        return $now->diffInDays($this->next_due_date);
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($schedule) {
            if (auth()->check()) {
                $schedule->created_by = auth()->id();
            }
            
            // Calculate initial next due date
            $schedule->next_due_date = $schedule->calculateNextDueDate();
        });

        static::updating(function ($schedule) {
            if (auth()->check()) {
                $schedule->updated_by = auth()->id();
            }
        });
    }
}

