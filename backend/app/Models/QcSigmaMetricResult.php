<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcSigmaMetricResult extends Model
{
    use HasFactory;

    protected $table = 'qc_sigma_metric_results';

    protected $fillable = [
        'test_parameter_id',
        'sigma_metric',
        'calculation_date',
        'observed_mean',
        'observed_std_dev',
        'target_mean',
        'te_a',
        'period_days',
    ];

    protected $casts = [
        'sigma_metric' => 'decimal:4',
        'calculation_date' => 'datetime',
        'observed_mean' => 'decimal:4',
        'observed_std_dev' => 'decimal:4',
        'target_mean' => 'decimal:4',
        'te_a' => 'decimal:4',
        'period_days' => 'integer',
    ];

    /**
     * Get the test parameter (analyte) that this result applies to.
     */
    public function testParameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class);
    }

    /**
     * Scope to get results for a specific test parameter.
     */
    public function scopeForParameter($query, $testParameterId)
    {
        return $query->where('test_parameter_id', $testParameterId);
    }

    /**
     * Scope to get results within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate = null)
    {
        $query->where('calculation_date', '>=', $startDate);
        if ($endDate) {
            $query->where('calculation_date', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to get the latest result for each parameter.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('calculation_date', 'desc');
    }
}



