<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bearer-token credentials for LIS Bridge authentication.
 *
 * The raw bearer token is never stored — only its SHA-256 hash (key_hash).
 * Authentication in VerifyLisBridgeApiKey compares the incoming token's hash
 * against key_hash using hash_equals() for timing-attack safety.
 *
 * Multiple keys per bridge are supported for key rotation: the forwarder
 * can be issued a new key before the old one expires, preventing lockouts.
 *
 * @property int          $id
 * @property int          $lis_bridge_config_id
 * @property string       $key_hash          SHA-256 hex (64 chars), never plaintext
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LisBridgeApiKey extends Model
{
    use HasFactory;

    protected $table = 'lis_bridge_api_keys';

    protected $fillable = [
        'lis_bridge_config_id',
        'key_hash',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    /**
     * Hidden from serialization.
     */
    protected $hidden = [
        'key_hash',
    ];

    /**
     * The bridge config this key belongs to.
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(LisBridgeConfig::class, 'lis_bridge_config_id');
    }

    /**
     * Check if this key is currently valid (not expired and not revoked).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verify a raw bearer token against this key's hash.
     * Uses hash_equals() to prevent timing attacks.
     *
     * @param  string $rawToken
     * @return bool
     */
    public function verifyToken(string $rawToken): bool
    {
        $incomingHash = hash('sha256', $rawToken);

        return hash_equals($this->key_hash, $incomingHash);
    }

    /**
     * Create a new API key for a bridge config.
     * Returns the raw token ONCE — it cannot be retrieved after this.
     *
     * @param  LisBridgeConfig $config
     * @param  \DateTimeInterface|null $expiresAt
     * @return array  ['model' => LisBridgeApiKey, 'raw_token' => string]
     */
    public static function createForConfig(LisBridgeConfig $config, ?\DateTimeInterface $expiresAt = null): array
    {
        $rawToken   = bin2hex(random_bytes(32)); // 64-char hex = 256 bits
        $keyHash    = hash('sha256', $rawToken);

        $model = static::create([
            'lis_bridge_config_id' => $config->id,
            'key_hash'             => $keyHash,
            'expires_at'           => $expiresAt,
        ]);

        return ['model' => $model, 'raw_token' => $rawToken];
    }

    /**
     * Update last_used_at to now.
     */
    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope: only valid (non-expired) keys.
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: keys belonging to a specific config.
     */
    public function scopeForConfig($query, int $configId)
    {
        return $query->where('lis_bridge_config_id', $configId);
    }
}
