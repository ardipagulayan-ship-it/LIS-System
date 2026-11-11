<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeltaCheckPreventiveMeasure extends Model
{
    use HasFactory;

    protected $table = 'delta_check_preventive_measures';

    protected $fillable = [
        'parameter_id',
        'measure_type',
        'title',
        'description',
        'specific_measures',
        'order',
        'is_active',
    ];

    protected $casts = [
        'specific_measures' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the parameter that owns this preventive measure
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(DeltaCheckParameter::class, 'parameter_id');
    }

    /**
     * Scope for active preventive measures
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific measure type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('measure_type', $type);
    }

    /**
     * Scope ordered by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}