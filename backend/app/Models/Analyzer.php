<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analyzer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'brand',
        'model',
        'serial_no',
        'remarks',
        'test_category_id',
        'isActive',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'isActive' => 'boolean',
    ];

    /**
     * Get the test category that owns the analyzer.
     */
    public function testCategory(): BelongsTo
    {
        return $this->belongsTo(TestCategory::class);
    }

    /**
     * Get the user who created the analyzer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the reference ranges for this analyzer.
     */
    public function referenceRanges(): HasMany
    {
        return $this->hasMany(ReferenceRange::class);
    }
}
