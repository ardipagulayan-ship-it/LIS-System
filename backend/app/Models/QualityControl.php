<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QualityControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_parameter_id',
        'equipment_id',
        'control_name',
        'lot_number',
        'expiry_date',
        'mean',
        'sd',
        'target',
        'sd_1_plus',
        'sd_1_minus',
        'sd_2_plus',
        'sd_2_minus',
        'sd_3_plus',
        'sd_3_minus',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'mean' => 'decimal:4',
        'sd' => 'decimal:4',
        'target' => 'decimal:4',
        'sd_1_plus' => 'decimal:4',
        'sd_1_minus' => 'decimal:4',
        'sd_2_plus' => 'decimal:4',
        'sd_2_minus' => 'decimal:4',
        'sd_3_plus' => 'decimal:4',
        'sd_3_minus' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the test parameter that owns the quality control.
     * This might represent the primary parameter for this control lot.
     */
    public function testParameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class);
    }

    /**
     * Get the equipment that owns the quality control.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the quality control results for this control.
     */
    public function qualityControlResults(): HasMany
    {
        return $this->hasMany(QualityControlResult::class);
    }

    /**
     * The test parameters associated with the quality control.
     */
    public function testParameters(): BelongsToMany
    {
        return $this->belongsToMany(TestParameter::class, 'quality_control_test_parameter')
                    ->withPivot(
                        'id',
                        'target',
                        'tolerance',
                        'minus_3sd',
                        'minus_2sd',
                        'minus_1sd',
                        'plus_1sd',
                        'plus_2sd',
                        'plus_3sd'
                    );
    }

    /**
     * Calculate SD limits based on mean and SD
     */
    public function calculateSDLimits(): void
    {
        $this->sd_1_plus = $this->mean + $this->sd;
        $this->sd_1_minus = $this->mean - $this->sd;
        $this->sd_2_plus = $this->mean + (2 * $this->sd);
        $this->sd_2_minus = $this->mean - (2 * $this->sd);
        $this->sd_3_plus = $this->mean + (3 * $this->sd);
        $this->sd_3_minus = $this->mean - (3 * $this->sd);
    }
} 