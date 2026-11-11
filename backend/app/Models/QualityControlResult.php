<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityControlResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_control_test_parameter_id',
        'result_value',
        'unit',
        'run_date',
        'run_time',
        'operator_id',
        'westgard_violations',
        'is_valid',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'result_value' => 'decimal:4',
        'westgard_violations' => 'array',
        'is_valid' => 'boolean'
    ];

    /**
     * Get the quality control test parameter that owns the result.
     */
    public function qualityControlTestParameter(): BelongsTo
    {
        return $this->belongsTo(QualityControlTestParameter::class);
    }

    /**
     * Get the operator who performed the test.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Get corrective actions for this QC result.
     */
    public function correctiveActions()
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    /**
     * Check Westgard rules and update violations.
     * Implements: 1-2s (warning), 1-3s, 2-2s, R-4s, 4-1s, 10x.
     * Now uses QC rules from qc_rules table for configuration.
     */
    public function checkWestgardRules(): void
    {
        $qcTestParameter = $this->qualityControlTestParameter;
        if (!$qcTestParameter) {
            return;
        }

        // Load relationships
        $qcTestParameter->load(['qualityControl', 'testParameter']);
        $qualityControl = $qcTestParameter->qualityControl;
        $testParameter = $qcTestParameter->testParameter;
        // Use equipment_id instead of analyzer_id
        $equipmentId = $qualityControl ? $qualityControl->equipment_id : null;
        $testParameterId = $testParameter ? $testParameter->id : null;

        // Find applicable QC rules
        // Priority: Lot-specific > Parameter+Equipment > Parameter-only > Equipment-only > Global
        $qcRules = \App\Models\QcRule::active()
            ->enabled()
            ->where(function($query) use ($qcTestParameter, $testParameterId, $equipmentId) {
                // Lot-specific
                $query->where('quality_control_test_parameter_id', $qcTestParameter->id)
                    // Parameter + Equipment
                    ->orWhere(function($q) use ($testParameterId, $equipmentId) {
                        if ($testParameterId && $equipmentId) {
                            $q->where('test_parameter_id', $testParameterId)
                              ->where('equipment_id', $equipmentId)
                              ->whereNull('quality_control_test_parameter_id');
                        }
                    })
                    // Parameter-only
                    ->orWhere(function($q) use ($testParameterId) {
                        if ($testParameterId) {
                            $q->where('test_parameter_id', $testParameterId)
                              ->whereNull('equipment_id')
                              ->whereNull('quality_control_test_parameter_id');
                        }
                    })
                    // Equipment-only
                    ->orWhere(function($q) use ($equipmentId) {
                        if ($equipmentId) {
                            $q->where('equipment_id', $equipmentId)
                              ->whereNull('test_parameter_id')
                              ->whereNull('quality_control_test_parameter_id');
                        }
                    })
                    // Global (all null)
                    ->orWhere(function($q) {
                        $q->whereNull('test_parameter_id')
                          ->whereNull('equipment_id')
                          ->whereNull('quality_control_test_parameter_id');
                    });
            })
            ->get();

        // Create a map of enabled rules by type
        $enabledRules = [];
        $ruleConfigs = [];
        foreach ($qcRules as $rule) {
            $enabledRules[$rule->westgard_rule_type] = true;
            $ruleConfigs[$rule->westgard_rule_type] = $rule;
        }

        // If no rules found, use default behavior (all rules enabled)
        $useDefaultRules = $qcRules->isEmpty();
        if ($useDefaultRules) {
            $enabledRules = ['1-2s' => true, '1-3s' => true, '2-2s' => true, 'R-4s' => true, '4-1s' => true, '10x' => true];
        }

        $violations = [];
        $value = (float) $this->result_value;

        // Mean and SD are configured per QC lot on the pivot
        $mean = (float) $qcTestParameter->target; // laboratory target is the mean
        $sd = (float) $qcTestParameter->tolerance; // tolerance configured as SD

        // Calculate SD limits, using custom multipliers if specified
        $getMultiplier = function($ruleType, $defaultMultiplier) use ($ruleConfigs) {
            if (isset($ruleConfigs[$ruleType]) && $ruleConfigs[$ruleType]->custom_threshold_multiplier) {
                return $ruleConfigs[$ruleType]->custom_threshold_multiplier;
            }
            return $defaultMultiplier;
        };

        $sd1Multiplier = $getMultiplier('1-2s', 1.0);
        $sd2Multiplier = $getMultiplier('1-2s', 2.0); // 1-2s and 2-2s use 2SD
        $sd3Multiplier = $getMultiplier('1-3s', 3.0);

        $sd1Plus = (float) $qcTestParameter->target + ($sd * $sd1Multiplier);
        $sd1Minus = (float) $qcTestParameter->target - ($sd * $sd1Multiplier);
        $sd2Plus = (float) $qcTestParameter->plus_2sd; // Use stored value or calculate
        $sd2Minus = (float) $qcTestParameter->minus_2sd;
        $sd3Plus = (float) $qcTestParameter->plus_3sd;
        $sd3Minus = (float) $qcTestParameter->minus_3sd;

        // Recalculate if using custom multipliers
        if (isset($ruleConfigs['1-2s']) || isset($ruleConfigs['2-2s'])) {
            $sd2Plus = $mean + ($sd * $sd2Multiplier);
            $sd2Minus = $mean - ($sd * $sd2Multiplier);
        }
        if (isset($ruleConfigs['1-3s'])) {
            $sd3Plus = $mean + ($sd * $sd3Multiplier);
            $sd3Minus = $mean - ($sd * $sd3Multiplier);
        }

        // Helper closures
        $isAbove = fn(float $v, float $t) => $v > $t;
        $isBelow = fn(float $v, float $t) => $v < $t;

        // 1-2s (warning only) - check if enabled
        if (isset($enabledRules['1-2s'])) {
            $oneTwoS = $isAbove($value, $sd2Plus) || $isBelow($value, $sd2Minus);
            if ($oneTwoS) {
                $violations[] = '1-2s';
            }
        }

        // 1-3s (reject) - check if enabled
        if (isset($enabledRules['1-3s'])) {
            if ($isAbove($value, $sd3Plus) || $isBelow($value, $sd3Minus)) {
                $violations[] = '1-3s';
            }
        }

        // Fetch recent history including this result (ordered oldest->newest)
        $recent = self::where('quality_control_test_parameter_id', $this->quality_control_test_parameter_id)
            ->orderBy('run_date', 'asc')
            ->orderBy('run_time', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'result_value']);

        // Locate index of current result in the series
        $index = $recent->search(function ($r) {
            return (int) $r->id === (int) $this->id;
        });

        // 2-2s (reject): current and immediate previous on same side beyond 2 SD
        if (isset($enabledRules['2-2s']) && $index !== false && $index > 0) {
            $prev = (float) $recent[$index - 1]->result_value;
            $currentBeyond2sHigh = $value > $sd2Plus;
            $currentBeyond2sLow = $value < $sd2Minus;
            $prevBeyond2sHigh = $prev > $sd2Plus;
            $prevBeyond2sLow = $prev < $sd2Minus;
            if (($currentBeyond2sHigh && $prevBeyond2sHigh) || ($currentBeyond2sLow && $prevBeyond2sLow)) {
                $violations[] = '2-2s';
            }

            // R-4s (reject): range of two consecutive controls exceeds 4 SD with opposite sides
            if (isset($enabledRules['R-4s'])) {
                if ((($currentBeyond2sHigh && $prevBeyond2sLow) || ($currentBeyond2sLow && $prevBeyond2sHigh))
                    && (abs($value - $prev) > (4.0 * $sd))) {
                    $violations[] = 'R-4s';
                }
            }
        }

        // 4-1s (reject): four consecutive on same side beyond 1 SD
        if (isset($enabledRules['4-1s']) && $index !== false && $index >= 3) {
            $window = $recent->slice($index - 3, 4)->pluck('result_value')->map(fn($v) => (float) $v)->values();
            $allHigh = $window->every(fn($v) => $v > $sd1Plus);
            $allLow = $window->every(fn($v) => $v < $sd1Minus);
            if ($allHigh || $allLow) {
                $violations[] = '4-1s';
            }
        }

        // 10x (reject): ten consecutive on same side of mean
        if (isset($enabledRules['10x']) && $index !== false && $index >= 9) {
            $window10 = $recent->slice($index - 9, 10)->pluck('result_value')->map(fn($v) => (float) $v)->values();
            $allAboveMean = $window10->every(fn($v) => $v > $mean);
            $allBelowMean = $window10->every(fn($v) => $v < $mean);
            if ($allAboveMean || $allBelowMean) {
                $violations[] = '10x';
            }
        }

        // Determine validity based on rule configurations
        // 1-2s alone is a warning; others reject (unless configured differently)
        $rejectingRules = [];
        $warningRules = [];
        
        foreach ($violations as $violation) {
            $ruleConfig = $ruleConfigs[$violation] ?? null;
            if ($ruleConfig) {
                if ($ruleConfig->action_on_violation === 'reject') {
                    $rejectingRules[] = $violation;
                } elseif ($ruleConfig->action_on_violation === 'warning') {
                    $warningRules[] = $violation;
                } else {
                    // 'flag' action - treat as reject for validity check
                    $rejectingRules[] = $violation;
                }
            } else {
                // Default behavior: 1-2s is warning, others reject
                if ($violation === '1-2s') {
                    $warningRules[] = $violation;
                } else {
                    $rejectingRules[] = $violation;
                }
            }
        }

        $this->westgard_violations = array_values($violations);
        $this->is_valid = empty($rejectingRules);
    }
} 