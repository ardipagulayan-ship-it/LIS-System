<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeltaCheckRecommendationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_result_id',
        'parameter_id',
        'violation_type',
        'percentage_difference',
        'absolute_difference',
        'severity_level',
        'recommendations_given',
        'actions_taken',
        'outcome',
        'notes',
    ];

    protected $casts = [
        'percentage_difference' => 'decimal:2',
        'absolute_difference' => 'decimal:2',
        'recommendations_given' => 'array',
        'actions_taken' => 'array',
    ];

    /**
     * Get the test result that owns this log
     */
    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class, 'test_result_id');
    }

    /**
     * Get the parameter that owns this log
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(DeltaCheckParameter::class, 'parameter_id');
    }

    /**
     * Scope for specific violation type
     */
    public function scopeByViolationType($query, $type)
    {
        return $query->where('violation_type', $type);
    }

    /**
     * Scope for specific severity level
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity_level', $severity);
    }

    /**
     * Scope for specific outcome
     */
    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }
}