<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestFormula extends Model
{
    use HasFactory;

    protected $table = 'test_formulas';

    protected $fillable = [
        'target_test_parameter_id',
        'formula_json',
        'compiled_formula',
        'decimal_places',
        'is_active',
    ];

    protected $casts = [
        'formula_json' => 'array',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
    ];

    /**
     * Get the target test parameter this formula calculates.
     */
    public function targetParameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'target_test_parameter_id');
    }

    /**
     * Get the source parameters (variables) used in this formula.
     */
    public function sourceVariables(): HasMany
    {
        return $this->hasMany(TestFormulaVariable::class, 'formula_id');
    }

    /**
     * Get the source test parameters through the variables relationship.
     */
    public function sourceParameters()
    {
        return $this->hasManyThrough(
            TestParameter::class,
            TestFormulaVariable::class,
            'formula_id',
            'id',
            'id',
            'source_test_parameter_id'
        );
    }
}
