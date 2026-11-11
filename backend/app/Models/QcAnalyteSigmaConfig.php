<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QcAnalyteSigmaConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qc_analyte_sigma_config';

    protected $fillable = [
        'test_parameter_id',
        't_e_a',
        'te_a_unit',
        'date_effective',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        't_e_a' => 'decimal:4',
        'date_effective' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the test parameter (analyte) that owns this config.
     */
    public function testParameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class);
    }

    /**
     * Get the user who created this config.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this config.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active configs only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get effective configs for a given date (defaults to today).
     */
    public function scopeEffectiveOn($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('date_effective', '<=', $date)
                     ->where(function($q) use ($date) {
                         $q->whereNull('deleted_at')
                           ->orWhere('deleted_at', '>', $date);
                     })
                     ->orderBy('date_effective', 'desc');
    }

    /**
     * Get the current TE_a value for a test parameter on a given date.
     * Returns the most recent effective config that hasn't been deleted.
     */
    public static function getTEaForParameter($testParameterId, $date = null)
    {
        $date = $date ?? now();
        return self::where('test_parameter_id', $testParameterId)
                   ->where('is_active', true)
                   ->where('date_effective', '<=', $date)
                   ->where(function($q) use ($date) {
                       $q->whereNull('deleted_at')
                         ->orWhere('deleted_at', '>', $date);
                   })
                   ->orderBy('date_effective', 'desc')
                   ->first();
    }
}



