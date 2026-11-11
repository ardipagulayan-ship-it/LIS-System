<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_parameter_id',
        'equipment_id',
        'si_range',
        'si_unit',
        'conv_range',
        'conv_unit',
        'low_critical_value',
        'low_value',
        'high_value',
        'high_critical_value',
        'age_min',
        'age_max',
        'age_unit',
        'gender',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'low_critical_value' => 'decimal:4',
        'low_value' => 'decimal:4',
        'high_value' => 'decimal:4',
        'high_critical_value' => 'decimal:4',
        'age_min' => 'integer',
        'age_max' => 'integer',
    ];

    /**
     * Get the test parameter that this reference range belongs to.
     */
    public function testParameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class);
    }

    /**
     * Get the equipment that this reference range belongs to.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

}
