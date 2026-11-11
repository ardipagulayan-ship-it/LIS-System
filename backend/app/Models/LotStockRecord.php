<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class LotStockRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lot_id',
        'material_id',
        'lot_number',
        'expiration_date',
        'date_received',
        'vendor_id',
        'initial_quantity',
        'current_stock_level',
        'reorder_point',
        'reorder_quantity',
        'storage_location',
        'status',
        'quarantine_notes',
        'quarantine_released_by',
        'quarantine_released_at',
        'cost_per_unit',
        'total_cost',
        'batch_number',
        'certificate_of_analysis',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'date_received' => 'date',
        'initial_quantity' => 'decimal:2',
        'current_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'quarantine_released_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    const STATUS_QUARANTINE = 'quarantine';
    const STATUS_ACTIVE = 'active';
    const STATUS_DEPLETED = 'depleted';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_EXPIRED = 'expired';
    const STATUS_DISPOSED = 'disposed';

    // Relationships
    public function material()
    {
        return $this->belongsTo(MaterialMaster::class, 'material_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function usageHistory()
    {
        return $this->hasMany(LotUsageHistory::class, 'lot_id');
    }

    public function qualityControlResults()
    {
        return $this->hasMany(QualityControlResult::class, 'lot_id');
    }

    public function quarantineReleasedBy()
    {
        return $this->belongsTo(User::class, 'quarantine_released_by');
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
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeQuarantine($query)
    {
        return $query->where('status', self::STATUS_QUARANTINE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween('expiration_date', [
            Carbon::now(),
            Carbon::now()->addDays($days)
        ])->where('status', '!=', self::STATUS_EXPIRED);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock_level', '<=', 'reorder_point')
                    ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeUsable($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('current_stock_level', '>', 0)
                    ->where('expiration_date', '>', Carbon::now());
    }

    // Helper methods
    public function isUsable()
    {
        return $this->status === self::STATUS_ACTIVE 
               && $this->current_stock_level > 0 
               && $this->expiration_date > Carbon::now();
    }

    public function isExpired()
    {
        return $this->expiration_date <= Carbon::now();
    }

    public function isInQuarantine()
    {
        return $this->status === self::STATUS_QUARANTINE;
    }

    public function getDaysUntilExpiration()
    {
        if (!$this->expiration_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiration_date, false);
    }

    public function getExpirationStatus()
    {
        if (!$this->expiration_date) {
            return 'no_date';
        }

        $daysUntil = $this->getDaysUntilExpiration();

        if ($daysUntil < 0) {
            return 'expired';
        } elseif ($daysUntil <= 30) {
            return 'critical';
        } elseif ($daysUntil <= 60) {
            return 'warning';
        } elseif ($daysUntil <= 90) {
            return 'alert';
        }

        return 'ok';
    }

    public function getStockStatus()
    {
        if ($this->current_stock_level <= 0) {
            return 'depleted';
        } elseif ($this->current_stock_level <= $this->reorder_point) {
            return 'low';
        } elseif ($this->current_stock_level <= $this->reorder_point * 1.5) {
            return 'warning';
        }

        return 'ok';
    }

    public function deductStock($quantity, $usageType = 'automatic', $testId = null, $notes = null)
    {
        if ($this->current_stock_level < $quantity) {
            throw new \Exception('Insufficient stock level');
        }

        if (!$this->isUsable()) {
            throw new \Exception('Lot is not usable');
        }

        $this->current_stock_level -= $quantity;
        
        // Update status if depleted
        if ($this->current_stock_level <= 0) {
            $this->status = self::STATUS_DEPLETED;
        }

        $this->save();

        // Create usage record
        LotUsageHistory::create([
            'lot_id' => $this->id,
            'quantity_used' => $quantity,
            'usage_type' => $usageType,
            'test_id' => $testId,
            'notes' => $notes,
            'used_by' => auth()->id()
        ]);
    }

    public function addStock($quantity)
    {
        $this->current_stock_level += $quantity;
        $this->save();
    }

    public function releaseFromQuarantine($notes = null)
    {
        if ($this->status !== self::STATUS_QUARANTINE) {
            throw new \Exception('Lot is not in quarantine');
        }

        $this->status = self::STATUS_ACTIVE;
        $this->quarantine_released_by = auth()->id();
        $this->quarantine_released_at = now();
        if ($notes) {
            $this->quarantine_notes = $notes;
        }
        $this->save();
    }

    public function getLotPerformanceMetrics()
    {
        $qcResults = $this->qualityControlResults();
        
        return [
            'total_qc_runs' => $qcResults->count(),
            'passed_qc_runs' => $qcResults->where('status', 'accepted')->count(),
            'failed_qc_runs' => $qcResults->where('status', 'rejected')->count(),
            'success_rate' => $qcResults->count() > 0 
                ? ($qcResults->where('status', 'accepted')->count() / $qcResults->count()) * 100 
                : 0
        ];
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            self::STATUS_QUARANTINE => 'Quarantine',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DEPLETED => 'Depleted',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_DISPOSED => 'Disposed',
            default => 'Unknown'
        };
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lot) {
            if (auth()->check()) {
                $lot->created_by = auth()->id();
            }
            
            // Auto-generate lot ID if not provided
            if (empty($lot->lot_id)) {
                $lot->lot_id = 'LOT-' . strtoupper(uniqid());
            }

            // Set default status to quarantine
            if (empty($lot->status)) {
                $lot->status = self::STATUS_QUARANTINE;
            }
        });

        static::updating(function ($lot) {
            if (auth()->check()) {
                $lot->updated_by = auth()->id();
            }

            // Auto-expire if past expiration date
            if ($lot->expiration_date && $lot->expiration_date <= Carbon::now() && $lot->status !== self::STATUS_EXPIRED) {
                $lot->status = self::STATUS_EXPIRED;
            }
        });

        // Check expiration on model retrieval
        static::retrieved(function ($lot) {
            if ($lot->expiration_date && $lot->expiration_date <= Carbon::now() && $lot->status !== self::STATUS_EXPIRED) {
                $lot->status = self::STATUS_EXPIRED;
                $lot->save();
            }
        });
    }
}
