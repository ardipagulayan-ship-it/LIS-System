<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeltaCheckSeverityLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'color',
        'priority_order',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority_order' => 'integer',
    ];

    /**
     * Get actions for this severity level
     */
    public function actions(): HasMany
    {
        return $this->hasMany(DeltaCheckAction::class, 'severity_level_id');
    }

    /**
     * Scope for active severity levels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority_order');
    }

    /**
     * Get actions grouped by type
     */
    public function getActionsByType()
    {
        return $this->actions()
            ->where('is_active', true)
            ->orderBy('action_type')
            ->orderBy('order')
            ->get()
            ->groupBy('action_type');
    }
}