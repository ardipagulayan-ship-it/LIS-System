<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sole source of truth for LIS Bridge wire-to-internal mapping and idempotency.
 *
 * Every successful ingestion creates exactly one row here. The result tables
 * (test_results, quality_control_results) carry NO additive LIS Bridge columns —
 * this table is the only record of bridge origin and the only idempotency key.
 *
 * Dedup rule: SELECT * FROM lis_bridge_translation
 *             WHERE lis_bridge_config_id = ? AND wire_result_id = ?
 * If a row exists → the wire row has already been processed → return 204.
 *
 * @property int          $id
 * @property int          $lis_bridge_config_id
 * @property int          $wire_result_id         Middleware's Result.id (idempotency key)
 * @property bool        $is_qc
 * @property string       $internal_table         'test_results' | 'quality_control_results'
 * @property int         $internal_id            PK of the inserted row in the internal table
 * @property string|null  $wire_analyzer_serial   Equipment.serial_no (forensic mirror)
 * @property string|null  $wire_patient_id
 * @property string|null  $wire_sample_id
 * @property string|null  $wire_test_code
 * @property \Carbon\Carbon|null $wire_observed_at
 * @property string|null  $wire_value_raw
 * @property string|null  $wire_result_status     F / P / C / X
 * @property int|null     $wire_run_number
 * @property string|null  $wire_control_id
 * @property \Carbon\Carbon $ingested_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LisBridgeTranslation extends Model
{
    use HasFactory;

    protected $table = 'lis_bridge_translation';

    /**
     * Disable timestamps — this is an append-only log.
     */
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $fillable = [
        'lis_bridge_config_id',
        'wire_result_id',
        'is_qc',
        'internal_table',
        'internal_id',
        'wire_analyzer_serial',
        'wire_patient_id',
        'wire_sample_id',
        'wire_test_code',
        'wire_observed_at',
        'wire_value_raw',
        'wire_result_status',
        'wire_run_number',
        'wire_control_id',
        'ingested_at',
    ];

    protected $casts = [
        'is_qc'            => 'boolean',
        'wire_result_id'   => 'integer',
        'internal_id'      => 'integer',
        'wire_run_number'  => 'integer',
        'wire_observed_at' => 'datetime',
        'ingested_at'      => 'datetime',
    ];

    /**
     * The bridge config that created this translation row.
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(LisBridgeConfig::class, 'lis_bridge_config_id');
    }

    /**
     * Return the internal result model (TestResult or QualityControlResult).
     * Dynamic relationship based on internal_table.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getInternalModelAttribute(): ?\Illuminate\Database\Eloquent\Model
    {
        if ($this->internal_table === 'test_results') {
            return TestResult::find($this->internal_id);
        }

        if ($this->internal_table === 'quality_control_results') {
            return QualityControlResult::find($this->internal_id);
        }

        return null;
    }

    /**
     * Check if a wire row has already been processed.
     *
     * @param  int $configId
     * @param  int $wireResultId
     * @return bool
     */
    public static function alreadyProcessed(int $configId, int $wireResultId): bool
    {
        return static::where('lis_bridge_config_id', $configId)
            ->where('wire_result_id', $wireResultId)
            ->exists();
    }

    /**
     * Find the translation row for a wire result.
     *
     * @param  int $configId
     * @param  int $wireResultId
     * @return static|null
     */
    public static function findByWireResult(int $configId, int $wireResultId): ?self
    {
        return static::where('lis_bridge_config_id', $configId)
            ->where('wire_result_id', $wireResultId)
            ->first();
    }

    /**
     * Scope: patient results only.
     */
    public function scopePatient($query)
    {
        return $query->where('is_qc', false);
    }

    /**
     * Scope: QC results only.
     */
    public function scopeQc($query)
    {
        return $query->where('is_qc', true);
    }

    /**
     * Scope: by analyzer serial.
     */
    public function scopeByAnalyzerSerial($query, string $serial)
    {
        return $query->where('wire_analyzer_serial', $serial);
    }

    /**
     * Scope: by ingestion date range.
     */
    public function scopeIngestedBetween($query, $from, $to)
    {
        return $query->whereBetween('ingested_at', [$from, $to]);
    }
}
