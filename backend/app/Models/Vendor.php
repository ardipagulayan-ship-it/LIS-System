<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'vendor_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'website',
        'tax_id',
        'payment_terms',
        'currency',
        'is_active',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function lots()
    {
        return $this->hasMany(LotStockRecord::class);
    }

    public function materials()
    {
        return $this->belongsToMany(MaterialMaster::class, 'material_vendor', 'vendor_id', 'material_id')
                    ->withPivot('vendor_catalog_number', 'last_purchase_price', 'last_purchase_date')
                    ->withTimestamps();
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
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

    // Helper methods
    public function getAverageLeadTime()
    {
        $lots = $this->lots()->whereNotNull('date_received')->get();
        
        if ($lots->count() === 0) {
            return null;
        }

        $totalDays = 0;
        foreach ($lots as $lot) {
            // Assuming we have order_date in purchase orders
            // For now, we'll use a placeholder calculation
            $totalDays += 7; // Default 7 days
        }

        return $totalDays / $lots->count();
    }

    public function getTotalSpent()
    {
        return $this->lots()->sum('total_cost');
    }

    public function getMaterialsCount()
    {
        return $this->materials()->count();
    }

    public function getActiveLotsCount()
    {
        return $this->lots()->where('status', LotStockRecord::STATUS_ACTIVE)->count();
    }

    public function getFullAddress()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (auth()->check()) {
                $vendor->created_by = auth()->id();
            }
            
            // Auto-generate vendor ID if not provided
            if (empty($vendor->vendor_id)) {
                $vendor->vendor_id = 'VEN-' . strtoupper(uniqid());
            }
        });

        static::updating(function ($vendor) {
            if (auth()->check()) {
                $vendor->updated_by = auth()->id();
            }
        });
    }
}
