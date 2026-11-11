<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EquipmentUsage extends Model
{
    use HasFactory;

    protected $table = 'equipment_usage';

    protected $fillable = [
        'equipment_id',
        'assignment_id',
        'test_id',
        'test_result_id',
        'lot_id',
        'material_id',
        'usage_type',
        'quantity_used',
        'usage_date',
        'test_run_number',
        'qc_status',
        'notes',
        'used_by',
        'created_by'
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'usage_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const USAGE_TYPE_PATIENT_TEST = 'patient_test';
    const USAGE_TYPE_QC_RUN = 'qc_run';
    const USAGE_TYPE_CALIBRATION = 'calibration';
    const USAGE_TYPE_MAINTENANCE = 'maintenance';
    const USAGE_TYPE_MANUAL = 'manual';

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignment()
    {
        return $this->belongsTo(EquipmentReagentAssignment::class, 'assignment_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function testResult()
    {
        return $this->belongsTo(TestResult::class);
    }

    public function lot()
    {
        return $this->belongsTo(LotStockRecord::class, 'lot_id');
    }

    public function material()
    {
        return $this->belongsTo(MaterialMaster::class, 'material_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('usage_date', [$startDate, $endDate]);
    }

    public function scopeByUsageType($query, $type)
    {
        return $query->where('usage_type', $type);
    }

    public function scopeByMaterial($query, $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    public function scopePatientTests($query)
    {
        return $query->where('usage_type', self::USAGE_TYPE_PATIENT_TEST);
    }

    public function scopeQCRuns($query)
    {
        return $query->where('usage_type', self::USAGE_TYPE_QC_RUN);
    }

    public function scopeCalibrations($query)
    {
        return $query->where('usage_type', self::USAGE_TYPE_CALIBRATION);
    }

    // Helper methods
    public function getUsageTypeLabel()
    {
        return match($this->usage_type) {
            self::USAGE_TYPE_PATIENT_TEST => 'Patient Test',
            self::USAGE_TYPE_QC_RUN => 'QC Run',
            self::USAGE_TYPE_CALIBRATION => 'Calibration',
            self::USAGE_TYPE_MAINTENANCE => 'Maintenance',
            self::USAGE_TYPE_MANUAL => 'Manual Entry',
            default => 'Unknown'
        };
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($usage) {
            $usage->usage_date = now();
            if (auth()->check()) {
                $usage->used_by = auth()->id();
                $usage->created_by = auth()->id();
            }

            // Auto-deduct stock from lot
            if ($usage->lot_id) {
                $lot = LotStockRecord::find($usage->lot_id);
                if ($lot && $lot->isUsable()) {
                    try {
                        $lot->deductStock(
                            $usage->quantity_used,
                            'automatic',
                            $usage->test_id,
                            "Equipment usage for {$usage->usage_type}"
                        );
                    } catch (\Exception $e) {
                        \Log::error('Failed to deduct stock during equipment usage', [
                            'error' => $e->getMessage(),
                            'usage_id' => $usage->id
                        ]);
                    }
                }
            }
        });
    }
}

