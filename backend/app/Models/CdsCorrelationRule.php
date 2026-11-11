<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CdsCorrelationRule extends Model
{
    use HasFactory;

    protected $table = 'cds_correlation_rules';

    protected $fillable = [
        'rule_name',
        'trigger_test_id',
        'rule_logic_json',
        'correlated_tests',
        'alert_message',
        'action_type',
        'reflex_test_id',
        'is_active',
    ];

    protected $casts = [
        'rule_logic_json' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the trigger test parameter that owns this rule.
     */
    public function triggerTest(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'trigger_test_id');
    }

    /**
     * Get the reflex test parameter (if applicable).
     */
    public function reflexTest(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'reflex_test_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}



