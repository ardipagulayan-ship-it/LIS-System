<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeltaCheckParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'parameter_name',
        'display_name',
        'category',
        'unit',
        'specimen_type',
        'percentage_limit',
        'absolute_limit',
        'critical_threshold',
        'description',
        'standards_references',
        'is_active',
    ];

    protected $casts = [
        'standards_references' => 'array',
        'percentage_limit' => 'decimal:2',
        'absolute_limit' => 'decimal:2',
        'critical_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get clinical significance for this parameter
     */
    public function clinicalSignificance(): HasMany
    {
        return $this->hasMany(DeltaCheckClinicalSignificance::class, 'parameter_id');
    }

    /**
     * Get preventive measures for this parameter
     */
    public function preventiveMeasures(): HasMany
    {
        return $this->hasMany(DeltaCheckPreventiveMeasure::class, 'parameter_id');
    }

    /**
     * Get quality control recommendations for this parameter
     */
    public function qualityControl(): HasMany
    {
        return $this->hasMany(DeltaCheckQualityControl::class, 'parameter_id');
    }

    /**
     * Get recommendation logs for this parameter
     */
    public function recommendationLogs(): HasMany
    {
        return $this->hasMany(DeltaCheckRecommendationLog::class, 'parameter_id');
    }

    /**
     * Scope for active parameters
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for parameters by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the current active clinical significance
     */
    public function getActiveClinicalSignificance()
    {
        return $this->clinicalSignificance()->where('is_active', true)->first();
    }

    /**
     * Get all active preventive measures grouped by type
     */
    public function getActivePreventiveMeasures()
    {
        return $this->preventiveMeasures()
            ->where('is_active', true)
            ->orderBy('measure_type')
            ->orderBy('order')
            ->get()
            ->groupBy('measure_type');
    }

    /**
     * Get all active quality control recommendations
     */
    public function getActiveQualityControl()
    {
        return $this->qualityControl()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }
}