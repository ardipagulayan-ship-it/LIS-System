<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_name',
        'test_id',
        'test_code',
        'specimen_type',
        'unit',
        'conversion_factor',
        'sequence_no',
        'created_by',
        'delta_check_percentage_limit',
        'delta_check_absolute_limit',
        'report_group_id',
    ];

    public function reportGroup()
    {
        return $this->belongsTo(ReportGroup::class, 'report_group_id');
    }

    /**
     * Get the test that owns the parameter.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Get the reference ranges for the test parameter.
     */
    public function referenceRanges(): HasMany
    {
        return $this->hasMany(ReferenceRange::class);
    }

    public function deltaChecks(): HasMany
    {
        return $this->hasMany(DeltaCheck::class, 'test_parameter_id');
    }
}
