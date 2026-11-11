<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Consumable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_id',
        'name',
        'manufacturer',
        'catalog_number',
        'lot_number',
        'expiration_date',
        'current_stock_level',
        'unit_of_measure',
        'storage_location',
        'reorder_level',
        'equipment_id',
        'is_expired',
        'is_locked',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'current_stock_level' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'is_expired' => 'boolean',
        'is_locked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class, 'consumable_test', 'consumable_id', 'test_id');
    }

    public function usageHistory()
    {
        return $this->hasMany(ConsumableUsage::class);
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
        return $query->where('is_expired', false)->where('is_locked', false);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_expired', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock_level', '<=', 'reorder_level')
                    ->where('is_expired', false)
                    ->where('is_locked', false);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween('expiration_date', [
            Carbon::now(),
            Carbon::now()->addDays($days)
        ])->where('is_expired', false);
    }

    // Helper methods
    public function isUsable()
    {
        return !$this->is_expired && !$this->is_locked && $this->current_stock_level > 0;
    }

    public function canBeUsed()
    {
        return $this->isUsable();
    }

    public function checkExpiration()
    {
        if (!$this->is_expired && $this->expiration_date && $this->expiration_date < Carbon::now()) {
            $this->update([
                'is_expired' => true,
                'is_locked' => true
            ]);
        }
    }

    public function deductStock($quantity)
    {
        if ($this->current_stock_level < $quantity) {
            throw new \Exception('Insufficient stock level');
        }

        $this->current_stock_level -= $quantity;
        $this->save();

        // Create usage record
        ConsumableUsage::create([
            'consumable_id' => $this->id,
            'quantity_used' => $quantity,
            'used_by' => auth()->id()
        ]);
    }

    public function addStock($quantity)
    {
        $this->current_stock_level += $quantity;
        $this->save();
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

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($consumable) {
            if (auth()->check()) {
                $consumable->created_by = auth()->id();
            }
            
            // Auto-generate material ID if not provided
            if (empty($consumable->material_id)) {
                $consumable->material_id = 'MAT-' . strtoupper(uniqid());
            }
        });

        static::updating(function ($consumable) {
            if (auth()->check()) {
                $consumable->updated_by = auth()->id();
            }

            // Check expiration on update
            $consumable->checkExpiration();
        });

        // Check expiration on model retrieval
        static::retrieved(function ($consumable) {
            $consumable->checkExpiration();
        });
    }
}
