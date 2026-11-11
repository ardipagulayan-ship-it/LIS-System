<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class MaterialMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'catalog_id',
        'generic_name',
        'manufacturer',
        'catalog_number',
        'description',
        'standard_unit_of_measure',
        'shelf_life_days',
        'safety_data_sheet_url',
        'safety_data_sheet_file',
        'material_type',
        'storage_requirements',
        'hazard_classification',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'shelf_life_days' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    const MATERIAL_TYPE_REAGENT = 'reagent';
    const MATERIAL_TYPE_CONTROL = 'control';
    const MATERIAL_TYPE_CALIBRATOR = 'calibrator';
    const MATERIAL_TYPE_CONSUMABLE = 'consumable';
    const MATERIAL_TYPE_EQUIPMENT = 'equipment';

    // Relationships
    public function lots()
    {
        return $this->hasMany(LotStockRecord::class, 'material_id');
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class, 'material_test', 'material_id', 'test_id')
                    ->withPivot('quantity_per_test', 'is_required')
                    ->withTimestamps();
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'material_vendor', 'material_id', 'vendor_id')
                    ->withPivot('vendor_catalog_number', 'last_purchase_price', 'last_purchase_date')
                    ->withTimestamps();
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

    public function scopeByType($query, $type)
    {
        return $query->where('material_type', $type);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereHas('lots', function($q) use ($days) {
            $q->whereBetween('expiration_date', [
                Carbon::now(),
                Carbon::now()->addDays($days)
            ])->where('status', '!=', 'expired');
        });
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('lots', function($q) {
            $q->whereColumn('current_stock_level', '<=', 'reorder_point')
              ->where('status', 'active');
        });
    }

    // Helper methods
    public function getTotalStockLevel()
    {
        return $this->lots()
                   ->where('status', 'active')
                   ->sum('current_stock_level');
    }

    public function getActiveLotsCount()
    {
        return $this->lots()->where('status', 'active')->count();
    }

    public function getExpiringLotsCount($days = 30)
    {
        return $this->lots()
                   ->whereBetween('expiration_date', [
                       Carbon::now(),
                       Carbon::now()->addDays($days)
                   ])
                   ->where('status', '!=', 'expired')
                   ->count();
    }

    public function getReorderStatus()
    {
        $totalStock = $this->getTotalStockLevel();
        $reorderPoint = $this->lots()->where('status', 'active')->min('reorder_point');
        
        if ($totalStock <= $reorderPoint) {
            return 'critical';
        } elseif ($totalStock <= $reorderPoint * 1.5) {
            return 'warning';
        }
        
        return 'ok';
    }

    public function getMaterialTypeLabel()
    {
        return match($this->material_type) {
            self::MATERIAL_TYPE_REAGENT => 'Reagent',
            self::MATERIAL_TYPE_CONTROL => 'Control',
            self::MATERIAL_TYPE_CALIBRATOR => 'Calibrator',
            self::MATERIAL_TYPE_CONSUMABLE => 'Consumable',
            self::MATERIAL_TYPE_EQUIPMENT => 'Equipment',
            default => 'Unknown'
        };
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($material) {
            if (auth()->check()) {
                $material->created_by = auth()->id();
            }
            
            // Auto-generate catalog ID if not provided
            if (empty($material->catalog_id)) {
                $material->catalog_id = 'MAT-' . strtoupper(uniqid());
            }
        });

        static::updating(function ($material) {
            if (auth()->check()) {
                $material->updated_by = auth()->id();
            }
        });
    }
}
