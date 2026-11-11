<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\QualityControl;
use App\Models\TestParameter;

class QualityControlTestParameter extends Model
{
    use HasFactory;

    protected $table = 'quality_control_test_parameter';

    protected $fillable = [
        'quality_control_id',
        'test_parameter_id',
        'target',
        'tolerance',
        'plus_1sd',
        'minus_1sd',
        'plus_2sd',
        'minus_2sd',
        'plus_3sd',
        'minus_3sd',
    ];

    /**
     * Get the quality control that owns the test parameter.
     */
    public function qualityControl()
    {
        return $this->belongsTo(QualityControl::class);
    }

    /**
     * Get the test parameter for the quality control.
     */
    public function testParameter()
    {
        return $this->belongsTo(TestParameter::class);
    }

    /**
     * Get the quality control results for the quality control test parameter.
     */
    public function qualityControlResults()
    {
        return $this->hasMany(QualityControlResult::class);
    }

    /**
     * Recalculate mean and SD (tolerance) from recent QC results and update SD limits.
     * Uses sample SD with denominator (n-1). Defaults to first 20 most recent points.
     */
    public function recalculateFromResults(int $numPoints = 20): void
    {
        $results = $this->qualityControlResults()
            ->orderBy('run_date', 'desc')
            ->orderBy('run_time', 'desc')
            ->orderBy('id', 'desc')
            ->limit($numPoints)
            ->pluck('result_value')
            ->map(fn($v) => (float) $v)
            ->values();

        if ($results->count() === 0) {
            return;
        }

        $n = $results->count();
        $mean = $results->avg();
        $variance = 0.0;
        if ($n > 1) {
            $variance = $results->reduce(function ($carry, $v) use ($mean) {
                $diff = $v - $mean;
                return $carry + ($diff * $diff);
            }, 0.0) / ($n - 1);
        }
        $sd = $n > 1 ? sqrt($variance) : 0.0;

        // Persist as target (mean) and tolerance (SD), and compute limits
        $this->target = round($mean, 4);
        $this->tolerance = round($sd, 4);
        $this->minus_1sd = round($this->target - $this->tolerance, 4);
        $this->plus_1sd = round($this->target + $this->tolerance, 4);
        $this->minus_2sd = round($this->target - (2 * $this->tolerance), 4);
        $this->plus_2sd = round($this->target + (2 * $this->tolerance), 4);
        $this->minus_3sd = round($this->target - (3 * $this->tolerance), 4);
        $this->plus_3sd = round($this->target + (3 * $this->tolerance), 4);

        $this->save();
    }
}
