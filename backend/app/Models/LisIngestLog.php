<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only forensics log for LIS Bridge ingestion requests.
 *
 * Every inbound request is logged here regardless of success or failure.
 * This table is for debugging, auditing, and replay — NOT for idempotency
 * (use LisBridgeTranslation for that).
 *
 * The table is expected to grow indefinitely. Implement a scheduled task
 * to archive or truncate old rows based on the site's log-retention policy.
 *
 * @property int          $id
 * @property int|null     $lis_bridge_config_id
 * @property string       $route                 e.g. '/api/v1/patient-results'
 * @property int          $status_code           HTTP response code
 * @property array|null   $request_body          Parsed JSON of the inbound payload
 * @property array|null   $response_body         Parsed JSON of the outbound response
 * @property int|null    $duration_ms           Request processing time in ms
 * @property \Carbon\Carbon $created_at
 */
class LisIngestLog extends Model
{
    use HasFactory;

    protected $table = 'lis_ingest_log';

    /**
     * Disable updated_at — this is an append-only log.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'lis_bridge_config_id',
        'route',
        'status_code',
        'request_body',
        'response_body',
        'duration_ms',
    ];

    protected $casts = [
        'status_code'   => 'integer',
        'request_body'  => 'array',
        'response_body' => 'array',
        'duration_ms'   => 'integer',
    ];

    /**
     * The bridge config this log belongs to (nullable — may be unknown on auth failures).
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(LisBridgeConfig::class, 'lis_bridge_config_id');
    }

    /**
     * Log a successful ingestion.
     *
     * @param  int|null    $configId
     * @param  string      $route
     * @param  array       $requestBody
     * @param  int         $durationMs
     * @return static
     */
    public static function logSuccess(
        ?int $configId,
        string $route,
        array $requestBody,
        int $durationMs
    ): self {
        return static::create([
            'lis_bridge_config_id' => $configId,
            'route'               => $route,
            'status_code'         => 204,
            'request_body'        => $requestBody,
            'response_body'       => ['ok' => true],
            'duration_ms'         => $durationMs,
        ]);
    }

    /**
     * Log a validation failure.
     *
     * @param  int|null    $configId
     * @param  string      $route
     * @param  array       $requestBody
     * @param  array       $errors      Validation errors
     * @param  int         $durationMs
     * @return static
     */
    public static function logValidationError(
        ?int $configId,
        string $route,
        array $requestBody,
        array $errors,
        int $durationMs
    ): self {
        return static::create([
            'lis_bridge_config_id' => $configId,
            'route'               => $route,
            'status_code'         => 422,
            'request_body'        => $requestBody,
            'response_body'       => ['errors' => $errors],
            'duration_ms'         => $durationMs,
        ]);
    }

    /**
     * Log an authentication failure.
     *
     * @param  string      $route
     * @param  array|null  $requestBody
     * @param  int         $durationMs
     * @return static
     */
    public static function logAuthFailure(
        string $route,
        ?array $requestBody,
        int $durationMs
    ): self {
        return static::create([
            'lis_bridge_config_id' => null,
            'route'               => $route,
            'status_code'         => 401,
            'request_body'        => $requestBody,
            'response_body'       => ['error' => 'Unauthorized'],
            'duration_ms'         => $durationMs,
        ]);
    }

    /**
     * Log a transient / server error.
     *
     * @param  int|null    $configId
     * @param  string      $route
     * @param  array       $requestBody
     * @param  int         $statusCode
     * @param  string      $error
     * @param  int         $durationMs
     * @return static
     */
    public static function logError(
        ?int $configId,
        string $route,
        array $requestBody,
        int $statusCode,
        string $error,
        int $durationMs
    ): self {
        return static::create([
            'lis_bridge_config_id' => $configId,
            'route'               => $route,
            'status_code'         => $statusCode,
            'request_body'        => $requestBody,
            'response_body'       => ['error' => $error],
            'duration_ms'         => $durationMs,
        ]);
    }

    /**
     * Scope: only successful requests.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('status_code', [200, 299]);
    }

    /**
     * Scope: only failed requests.
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('status_code', '>=', 400)
                ->orWhere('status_code', '<', 200);
        });
    }

    /**
     * Scope: by route.
     */
    public function scopeByRoute($query, string $route)
    {
        return $query->where('route', $route);
    }

    /**
     * Scope: by date range.
     */
    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
