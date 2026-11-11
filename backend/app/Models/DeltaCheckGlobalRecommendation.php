<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeltaCheckGlobalRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'recommendation_type',
        'condition',
        'severity',
        'title',
        'message',
        'action',
        'thresholds',
        'is_active',
    ];

    protected $casts = [
        'thresholds' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active global recommendations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific recommendation type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('recommendation_type', $type);
    }

    /**
     * Scope for specific severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }
}