<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeltaCheckRecommendationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_name',
        'template_type',
        'description',
        'template_data',
        'is_active',
    ];

    protected $casts = [
        'template_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific template type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('template_type', $type);
    }
}