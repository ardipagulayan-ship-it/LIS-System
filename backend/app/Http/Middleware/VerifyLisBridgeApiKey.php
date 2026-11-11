<?php

namespace App\Http\Middleware;

use App\Exceptions\LisBridgeAuthenticationException;
use App\Models\LisBridgeApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the Authorization: Bearer token against the LIS Bridge API key table.
 *
 * This middleware is EXEMPT from auth:sanctum — it is registered as a route
 * middleware alias and applied ONLY to the three LIS Bridge receiver routes.
 *
 * How it works:
 *   1. Extracts the Bearer token from the Authorization header.
 *   2. Hashes it with SHA-256.
 *   3. Looks up the hash in lis_bridge_api_keys.key_hash.
 *   4. If found, non-expired, and the bridge is active → passes.
 *   5. If not found or expired → throws LisBridgeAuthenticationException → HTTP 401.
 *
 * Security notes:
 *   - Uses hash_equals() for timing-attack-safe comparison.
 *   - Stores only the hash, never the raw token.
 *   - Updates last_used_at on successful verification.
 *   - Throws a generic "Unauthorized" message to avoid leaking key existence.
 *
 * @see LisBridgeApiKey::verifyToken()
 * @see LisBridgeAuthenticationException
 */
class VerifyLisBridgeApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Request     $request
     * @param  Closure     $next
     * @return Response
     *
     * @throws LisBridgeAuthenticationException  When token is missing, invalid, or expired
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            throw new LisBridgeAuthenticationException(
                'Authorization header missing or malformed.'
            );
        }

        $keyHash = hash('sha256', $token);

        $apiKey = LisBridgeApiKey::where('key_hash', $keyHash)->first();

        if ($apiKey === null) {
            // Deliberately generic — do not reveal whether the key exists
            throw new LisBridgeAuthenticationException('Invalid API key.');
        }

        if (!$apiKey->isValid()) {
            throw new LisBridgeAuthenticationException('API key has expired or been revoked.');
        }

        // Verify the hash using constant-time comparison
        if (!hash_equals($apiKey->key_hash, $keyHash)) {
            throw new LisBridgeAuthenticationException('Invalid API key.');
        }

        // Check the bridge config is active
        $config = $apiKey->config;
        if ($config === null || !$config->is_active) {
            throw new LisBridgeAuthenticationException(
                'LIS Bridge is not active. Check the configuration.'
            );
        }

        // Mark last_used_at
        $apiKey->touchLastUsed();

        // Attach the verified config and apiKey to the request for downstream use
        $request->attributes->set('lis_bridge_config', $config);
        $request->attributes->set('lis_bridge_api_key', $apiKey);

        return $next($request);
    }

    /**
     * Extract the Bearer token from the Authorization header.
     *
     * @param  Request $request
     * @return string|null  Raw token, or null if header is absent/malformed
     */
    protected function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if ($header === null) {
            return null;
        }

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7); // strip "Bearer "

        // Reject empty tokens
        if (trim($token) === '') {
            return null;
        }

        return $token;
    }
}
