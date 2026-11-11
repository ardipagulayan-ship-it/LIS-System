<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumableUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumable_id',
        'quantity_used',
        'usage_type',
        'test_id',
        'used_by',
        'notes'
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'usage_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const USAGE_TYPE_AUTOMATIC = 'automatic'; // Automatic deduction from test completion
    const USAGE_TYPE_MANUAL = 'manual'; // Manual adjustment
    const USAGE_TYPE_WASTAGE = 'wastage'; // Product wastage

    // Relationships
    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
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
