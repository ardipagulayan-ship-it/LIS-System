<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotUsageHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'quantity_used',
        'usage_type',
        'test_id',
        'notes',
        'used_by',
        'usage_date'
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'usage_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const USAGE_TYPE_AUTOMATIC = 'automatic';
    const USAGE_TYPE_MANUAL = 'manual';
    const USAGE_TYPE_WASTAGE = 'wastage';
    const USAGE_TYPE_DISPOSAL = 'disposal';
    const USAGE_TYPE_TRANSFER = 'transfer';

    // Relationships
    public function lot()
    {
        return $this->belongsTo(LotStockRecord::class, 'lot_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('usage_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('usage_date', [$startDate, $endDate]);
    }

    // Helper methods
    public function getUsageTypeLabel()
    {
        return match($this->usage_type) {
            self::USAGE_TYPE_AUTOMATIC => 'Automatic',
            self::USAGE_TYPE_MANUAL => 'Manual',
            self::USAGE_TYPE_WASTAGE => 'Wastage',
            self::USAGE_TYPE_DISPOSAL => 'Disposal',
            self::USAGE_TYPE_TRANSFER => 'Transfer',
            default => 'Unknown'
        };
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($usage) {
            $usage->usage_date = now();
            if (auth()->check()) {
                $usage->used_by = auth()->id();
            }
        });
    }
}
