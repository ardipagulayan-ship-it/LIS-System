<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LisBridgeConfig;
use App\Models\LisBridgeApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LisBridgeConfigController extends Controller
{
    // =========================================================================
    // Bridge Config CRUD
    // =========================================================================

    /**
     * List all bridge configs with their active API key count.
     */
    public function index(): JsonResponse
    {
        $configs = LisBridgeConfig::withCount('apiKeys')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($configs);
    }

    /**
     * Get a single bridge config with its full API key list.
     */
    public function show(int $id): JsonResponse
    {
        $config = LisBridgeConfig::with(['apiKeys' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        return response()->json($config);
    }

    /**
     * Create a new bridge config.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255|unique:lis_bridge_configs,name',
            'lis_url'        => 'nullable|url|max:500',
            'patient_lis_url'=> 'nullable|url|max:500',
            'qc_lis_url'     => 'nullable|url|max:500',
            'control_id_map' => 'nullable|array',
            'is_active'      => 'boolean',
        ]);

        $config = LisBridgeConfig::create([
            'name'           => $data['name'],
            'lis_url'        => $data['lis_url'] ?? null,
            'patient_lis_url'=> $data['patient_lis_url'] ?? null,
            'qc_lis_url'     => $data['qc_lis_url'] ?? null,
            'control_id_map' => $data['control_id_map'] ?? [],
            'is_active'      => $data['is_active'] ?? true,
        ]);

        return response()->json($config, 201);
    }

    /**
     * Update an existing bridge config.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $config = LisBridgeConfig::findOrFail($id);

        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255', Rule::unique('lis_bridge_configs')->ignore($config->id)],
            'lis_url'        => 'nullable|url|max:500',
            'patient_lis_url'=> 'nullable|url|max:500',
            'qc_lis_url'     => 'nullable|url|max:500',
            'control_id_map' => 'nullable|array',
            'is_active'      => 'boolean',
        ]);

        $config->fill($data);
        $config->save();

        return response()->json($config);
    }

    /**
     * Delete a bridge config (soft-blocked if it has active translations).
     */
    public function destroy(int $id): JsonResponse
    {
        $config = LisBridgeConfig::findOrFail($id);

        // Optionally check for existing translations — warn but allow deletion
        $translationCount = DB::table('lis_bridge_translation')
            ->where('lis_bridge_config_id', $config->id)
            ->count();

        if ($translationCount > 0) {
            // Delete translations first (cascade)
            DB::table('lis_bridge_translation')
                ->where('lis_bridge_config_id', $config->id)
                ->delete();
        }

        // Delete associated API keys
        $config->apiKeys()->delete();

        $config->delete();

        return response()->json(['message' => 'Bridge config deleted.', 'translations_deleted' => $translationCount]);
    }

    // =========================================================================
    // API Key Management
    // =========================================================================

    /**
     * List all API keys for a specific bridge config.
     */
    public function listKeys(int $configId): JsonResponse
    {
        $config = LisBridgeConfig::findOrFail($configId);

        $keys = $config->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                return [
                    'id'           => $key->id,
                    'key_prefix'   => substr($key->key_hash, 0, 8) . '...' . substr($key->key_hash, -4),
                    'last_used_at' => $key->last_used_at,
                    'expires_at'   => $key->expires_at,
                    'is_valid'    => $key->isValid(),
                    'revoked_at'  => $key->revoked_at,
                    'created_at'  => $key->created_at,
                ];
            });

        return response()->json($keys);
    }

    /**
     * Generate a new API key for a bridge config.
     *
     * The raw token is returned ONLY in this response — it cannot be recovered.
     */
    public function createKey(Request $request, int $configId): JsonResponse
    {
        $config = LisBridgeConfig::findOrFail($configId);

        $data = $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $expiresAt = isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null;

        $result = LisBridgeApiKey::createForConfig($config, $expiresAt);
        /** @var LisBridgeApiKey $apiKey */
        $apiKey = $result['model'];
        $rawToken = $result['raw_token'];

        return response()->json([
            'id'           => $apiKey->id,
            'raw_token'    => $rawToken, // Shown ONLY here — store it securely
            'key_prefix'   => substr($apiKey->key_hash, 0, 8) . '...' . substr($apiKey->key_hash, -4),
            'expires_at'   => $apiKey->expires_at,
            'created_at'   => $apiKey->created_at,
            'warning'      => 'This raw token will NOT be shown again. Copy it now and store it securely.',
        ], 201);
    }

    /**
     * Revoke an API key (sets revoked_at timestamp).
     */
    public function revokeKey(int $configId, int $keyId): JsonResponse
    {
        $config = LisBridgeConfig::findOrFail($configId);

        $key = $config->apiKeys()->findOrFail($keyId);
        $key->revoked_at = now();
        $key->save();

        return response()->json(['message' => 'API key revoked.', 'revoked_at' => $key->revoked_at]);
    }

    /**
     * Delete (permanently remove) an API key.
     */
    public function deleteKey(int $configId, int $keyId): JsonResponse
    {
        $config = LisBridgeConfig::findOrFail($configId);

        $key = $config->apiKeys()->findOrFail($keyId);
        $key->delete();

        return response()->json(['message' => 'API key deleted.']);
    }

    /**
     * Test connectivity to the bridge receiver endpoint.
     * Calls /api/lis-bridge/test-connection internally.
     */
    public function testConnection(int $configId): JsonResponse
    {
        $config = LisBridgeConfig::findOrFail($configId);

        $activeKey = $config->activeApiKey();

        if (!$activeKey) {
            return response()->json([
                'ok'     => false,
                'message' => 'No active API key found for this bridge config.',
            ], 422);
        }

        try {
            // Use the configured URLs or fall back to the app's own URL
            $baseUrl = rtrim($config->lis_url ?: config('app.url'), '/');
            $url = $baseUrl . '/api/lis-bridge/test-connection';

            $response = \Illuminate\Support\Facades\Http::withToken(
                $activeKey->key_hash,
                'sha256'
            )->timeout(5)->post($url);

            return response()->json([
                'ok'           => $response->successful(),
                'status_code'  => $response->status(),
                'response_body'=> $response->successful() ? $response->json() : $response->body(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message'  => 'Connection failed: ' . $e->getMessage(),
            ], 503);
        }
    }

    // =========================================================================
    // Ingest Log
    // =========================================================================

    /**
     * Return recent ingest log entries, optionally filtered by config.
     */
    public function ingestLog(Request $request): JsonResponse
    {
        $data = $request->validate([
            'config_id'  => 'nullable|integer|exists:lis_bridge_configs,id',
            'status_code'=> 'nullable|integer',
            'route'      => 'nullable|string',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date',
            'per_page'   => 'nullable|integer|min:1|max:200',
        ]);

        $query = \App\Models\LisIngestLog::query()
            ->when($data['config_id'] ?? null, fn($q, $id) => $q->where('lis_bridge_config_id', $id))
            ->when($data['status_code'] ?? null, fn($q, $code) => $q->where('status_code', $code))
            ->when($data['route'] ?? null, fn($q, $r) => $q->where('route', $r))
            ->when($data['from'] ?? null, fn($q, $from) => $q->where('created_at', '>=', $from))
            ->when($data['to'] ?? null, fn($q, $to) => $q->where('created_at', '<=', $to))
            ->orderBy('created_at', 'desc');

        $perPage = $data['per_page'] ?? 50;
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }
}
