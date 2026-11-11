<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeltaCheckRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'test_parameter_id',
        'equipment_id',
        'percentage_limit',
        'absolute_limit',
        'critical_threshold',
        'time_window_days',
        'comparison_type',
        'action_on_violation',
        'alert_message',
        'severity_level_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage_limit' => 'decimal:2',
        'absolute_limit' => 'decimal:2',
        'critical_threshold' => 'decimal:2',
        'time_window_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the test parameter that owns this rule.
     */
    public function testParameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class);
    }

    /**
     * Get the equipment that owns this rule (if equipment-specific).
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the severity level for this rule.
     */
    public function severityLevel(): BelongsTo
    {
        return $this->belongsTo(DeltaCheckSeverityLevel::class, 'severity_level_id');
    }

    /**
     * Get the user who created this rule.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this rule.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rules by test parameter.
     */
    public function scopeByTestParameter($query, $testParameterId)
    {
        return $query->where('test_parameter_id', $testParameterId);
    }

    /**
     * Scope for rules by equipment.
     */
    public function scopeByEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }
}



