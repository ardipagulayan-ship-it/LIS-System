<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestFormulaVariable extends Model
{
    use HasFactory;

    protected $table = 'test_formula_variables';

    protected $fillable = [
        'formula_id',
        'source_test_parameter_id',
    ];

    /**
     * Get the formula this variable belongs to.
     */
    public function formula(): BelongsTo
    {
        return $this->belongsTo(TestFormula::class, 'formula_id');
    }

    /**
     * Get the source test parameter.
     */
    public function sourceParameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'source_test_parameter_id');
    }
}
