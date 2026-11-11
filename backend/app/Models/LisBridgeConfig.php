<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LIS Bridge instance configuration.
 *
 * Represents one bridge connection between the external Python forwarder and
 * this Laravel application. Each row holds URL routing, QC mapping, and
 * references to its API keys.
 *
 * Analyzers are resolved via their serial_no (equipment.serial_no) as the
 * natural key — NOT via this config's JSON map. The analyzer_id_map concept
 * was replaced by the serial_no natural key in the architecture review.
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $lis_url
 * @property string|null $patient_lis_url
 * @property string|null $qc_lis_url
 * @property array|null  $control_id_map   JSON: ["QC_NORMAL" => 7, "QC_HIGH" => 8]
 * @property bool        $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LisBridgeConfig extends Model
{
    use HasFactory;

    protected $table = 'lis_bridge_configs';

    protected $fillable = [
        'name',
        'lis_url',
        'patient_lis_url',
        'qc_lis_url',
        'control_id_map',
        'is_active',
    ];

    protected $casts = [
        'control_id_map' => 'array',
        'is_active'      => 'boolean',
    ];

    /**
     * All bearer tokens belonging to this bridge config.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(LisBridgeApiKey::class, 'lis_bridge_config_id');
    }

    /**
     * All translation rows created by this bridge.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(LisBridgeTranslation::class, 'lis_bridge_config_id');
    }

    /**
     * All ingest log entries from this bridge.
     */
    public function ingestLogs(): HasMany
    {
        return $this->hasMany(LisIngestLog::class, 'lis_bridge_config_id');
    }

    /**
     * Resolve a vendor-supplied QC control name to a local quality_controls.id.
     *
     * @param  string $controlId  Wire-format control_id, e.g. "QC_NORMAL"
     * @return int|null           Local quality_controls.id, or null if not mapped
     */
    public function resolveControlId(string $controlId): ?int
    {
        $map = $this->control_id_map ?? [];
        return $map[$controlId] ?? null;
    }

    /**
     * Return the active API key for this config, if exactly one exists.
     * Returns null if zero or more than one active key is present.
     *
     * @return LisBridgeApiKey|null
     */
    public function activeApiKey(): ?LisBridgeApiKey
    {
        return $this->apiKeys()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Scope: only active bridge configs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
