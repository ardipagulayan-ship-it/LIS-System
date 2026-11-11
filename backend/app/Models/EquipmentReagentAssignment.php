<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class EquipmentReagentAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment_reagent_assignments';

    protected $fillable = [
        'equipment_id',
        'lot_id',
        'material_id',
        'test_id',
        'quantity_per_test',
        'is_active',
        'assigned_by',
        'assigned_at',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity_per_test' => 'decimal:2',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function lot()
    {
        return $this->belongsTo(LotStockRecord::class, 'lot_id');
    }

    public function material()
    {
        return $this->belongsTo(MaterialMaster::class, 'material_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function usageRecords()
    {
        return $this->hasMany(EquipmentUsage::class, 'assignment_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    public function scopeByTest($query, $testId)
    {
        return $query->where('test_id', $testId);
    }

    // Helper methods
    public function getTotalUsage()
    {
        return $this->usageRecords()->sum('quantity_used');
    }

    public function getRemainingQuantity()
    {
        $lotStock = $this->lot->current_stock_level ?? 0;
        return max(0, $lotStock - $this->getTotalUsage());
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assignment) {
            if (auth()->check()) {
                $assignment->created_by = auth()->id();
                if (!$assignment->assigned_by) {
                    $assignment->assigned_by = auth()->id();
                }
            }
            if (!$assignment->assigned_at) {
                $assignment->assigned_at = now();
            }
        });

        static::updating(function ($assignment) {
            if (auth()->check()) {
                $assignment->updated_by = auth()->id();
            }
        });
    }
}

