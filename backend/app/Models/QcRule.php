<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcRule extends Model
{
    use HasFactory;

    protected $table = 'qc_rules';

    protected $fillable = [
        'rule_name',
        'test_parameter_id',
        'equipment_id',
        'quality_control_test_parameter_id',
        'westgard_rule_type',
        'is_enabled',
        'action_on_violation',
        'severity_level',
        'alert_message',
        'custom_threshold_multiplier',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_active' => 'boolean',
        'custom_threshold_multiplier' => 'decimal:2',
    ];

    /**
     * Get the test parameter that owns this rule (if parameter-specific).
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
     * Get the quality control test parameter (if lot-specific).
     */
    public function qualityControlTestParameter(): BelongsTo
    {
        return $this->belongsTo(QualityControlTestParameter::class);
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
     * Scope for enabled rules.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
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

    /**
     * Scope for rules by Westgard rule type.
     */
    public function scopeByWestgardRuleType($query, $ruleType)
    {
        return $query->where('westgard_rule_type', $ruleType);
    }
}



