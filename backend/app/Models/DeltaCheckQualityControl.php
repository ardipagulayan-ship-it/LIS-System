<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeltaCheckQualityControl extends Model
{
    use HasFactory;

    protected $table = 'delta_check_quality_control';

    protected $fillable = [
        'parameter_id',
        'qc_type',
        'title',
        'description',
        'qc_steps',
        'order',
        'is_active',
    ];

    protected $casts = [
        'qc_steps' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the parameter that owns this quality control
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(DeltaCheckParameter::class, 'parameter_id');
    }

    /**
     * Scope for active quality control
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific QC type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('qc_type', $type);
    }

    /**
     * Scope ordered by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}