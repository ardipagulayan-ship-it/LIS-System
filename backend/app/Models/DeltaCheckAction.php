<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeltaCheckAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'severity_level_id',
        'action_type',
        'title',
        'description',
        'steps',
        'order',
        'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the severity level that owns this action
     */
    public function severityLevel(): BelongsTo
    {
        return $this->belongsTo(DeltaCheckSeverityLevel::class, 'severity_level_id');
    }

    /**
     * Scope for active actions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific action type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope ordered by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}