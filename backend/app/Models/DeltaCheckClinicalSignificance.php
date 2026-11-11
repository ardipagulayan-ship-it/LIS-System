<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeltaCheckClinicalSignificance extends Model
{
    use HasFactory;

    protected $table = 'delta_check_clinical_significance';

    protected $fillable = [
        'parameter_id',
        'significance_text',
        'risk_factors',
        'clinical_conditions',
        'is_active',
    ];

    protected $casts = [
        'risk_factors' => 'array',
        'clinical_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parameter that owns this clinical significance
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(DeltaCheckParameter::class, 'parameter_id');
    }

    /**
     * Scope for active clinical significance
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}