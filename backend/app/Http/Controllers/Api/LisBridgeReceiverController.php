<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LisBridgeAuthenticationException;
use App\Exceptions\LisIngestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLisPatientResultRequest;
use App\Http\Requests\Api\StoreLisQcResultRequest;
use App\Jobs\ProcessLisPatientResult;
use App\Jobs\ProcessLisQcResult;
use App\Models\LisBridgeConfig;
use App\Models\LisIngestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controller for the LIS Bridge receiver endpoints.
 *
 * Receives POST requests from the Python forwarder middleware and orchestrates:
 *   1. Authentication (handled by VerifyLisBridgeApiKey middleware — already passed)
 *   2. Validation (FormRequest — already passed)
 *   3. Ingestion via LisResultIngestionService
 *   4. Async queue job dispatch for downstream processing (delta checks, reflex, CDS, QC)
 *   5. Forensic logging to lis_ingest_log
 *
 * Design principles:
 *   - Sync response target: p95 < 2s (well under the forwarder's 10s timeout).
 *   - All heavy work (delta checks, reflex tests, QC sigma metrics) goes to queue.
 *   - Every request is logged regardless of outcome.
 *   - Return 204 on success — per the wire format spec, any 2xx marks SYNCED.
 *
 * HTTP response semantics (per wire format spec, Section C):
 *   200/201/202/204 → forwarder marks SYNCED, stops retrying
 *   400/401/403/404/422 → forwarder skips inner retry, row stays PENDING for next poll
 *   408/429/5xx → forwarder retries with exponential backoff
 *
 * @see VerifyLisBridgeApiKey
 * @see StoreLisPatientResultRequest
 * @see StoreLisQcResultRequest
 * @see LisResultIngestionService
 */
class LisBridgeReceiverController extends Controller
{
    /**
     * POST /api/v1/patient-results
     *
     * Receives and ingests a patient (non-QC) result payload.
     *
     * @param  StoreLisPatientResultRequest $request  Validated payload
     * @return JsonResponse
     *
     * @throws LisIngestException  Maps to HTTP 422 or 503
     */
    public function ingestPatient(StoreLisPatientResultRequest $request): JsonResponse
    {
        $startMs = hrtime(true) / 1_000_000; // microsecond-precision timer
        $config  = $this->getBridgeConfig($request);
        $payload = $request->validated();

        try {
            $service = app(\App\Services\LisResultIngestionService::class);
            $result = $service->ingestPatient($config, $payload);

            // Dispatch async job for downstream processing (delta checks, reflex, CDS)
            if ($result['is_new']) {
                ProcessLisPatientResult::dispatch($result['result'], $config);
            }

            $durationMs = (int) ((hrtime(true) / 1_000_000) - $startMs);

            // Log success
            LisIngestLog::logSuccess(
                $config->id,
                '/api/v1/patient-results',
                $payload,
                $durationMs
            );

            return response()->json(['ok' => true, 'result_id' => $result['result']->id], 204);

        } catch (LisIngestException $e) {
            return $this->handleIngestException(
                $e, $config, '/api/v1/patient-results', $payload, $startMs
            );
        }
    }

    /**
     * POST /api/v1/qc-results
     *
     * Receives and ingests a QC result payload.
     *
     * @param  StoreLisQcResultRequest $request  Validated payload
     * @return JsonResponse
     *
     * @throws LisIngestException  Maps to HTTP 422 or 503
     */
    public function ingestQc(StoreLisQcResultRequest $request): JsonResponse
    {
        $startMs = hrtime(true) / 1_000_000;
        $config  = $this->getBridgeConfig($request);
        $payload = $request->validated();

        try {
            $service = app(\App\Services\LisResultIngestionService::class);
            $result = $service->ingestQc($config, $payload);

            // Dispatch async job for downstream QC processing (Westgard rules, sigma metrics)
            if ($result['is_new']) {
                ProcessLisQcResult::dispatch($result['result'], $config);
            }

            $durationMs = (int) ((hrtime(true) / 1_000_000) - $startMs);

            LisIngestLog::logSuccess(
                $config->id,
                '/api/v1/qc-results',
                $payload,
                $durationMs
            );

            return response()->json(['ok' => true, 'result_id' => $result['result']->id], 204);

        } catch (LisIngestException $e) {
            return $this->handleIngestException(
                $e, $config, '/api/v1/qc-results', $payload, $startMs
            );
        }
    }

    /**
     * POST /api/lis-bridge/test-connection
     *
     * Reachability probe used by the forwarder's admin UI.
     *
     * Per the wire format spec (Appendix 3), this endpoint returns 200/201/202/204
     * on success regardless of body. It proves the endpoint exists and auth is valid.
     * The test_lis_connection() on the forwarder side treats 2xx and 400/401/403
     * as "reachable."
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function testConnection(Request $request): JsonResponse
    {
        $startMs = hrtime(true) / 1_000_000;
        $config  = $this->getBridgeConfig($request);
        $payload = $request->all();
        $durationMs = (int) ((hrtime(true) / 1_000_000) - $startMs);

        LisIngestLog::logSuccess(
            $config->id,
            '/api/lis-bridge/test-connection',
            $payload,
            $durationMs
        );

        return response()->json([
            'ok'          => true,
            'bridge_name' => $config->name,
            'bridge_id'   => $config->id,
        ], 200);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Extract the verified LisBridgeConfig from the request attributes.
     *
     * Set by VerifyLisBridgeApiKey middleware.
     *
     * @param  Request $request
     * @return LisBridgeConfig
     *
     * @throws LisBridgeAuthenticationException  Should not happen — middleware already validated
     */
    protected function getBridgeConfig(Request $request): LisBridgeConfig
    {
        $config = $request->attributes->get('lis_bridge_config');

        if ($config === null) {
            // This should never happen — middleware should have thrown first
            throw new LisBridgeAuthenticationException(
                'LIS Bridge configuration not found. Authentication may have failed.'
            );
        }

        return $config;
    }

    /**
     * Handle a LisIngestException and return the appropriate JSON response.
     *
     * Logs the failure to lis_ingest_log, then returns the exception's JSON response.
     *
     * @param  LisIngestException $e
     * @param  LisBridgeConfig    $config
     * @param  string            $route
     * @param  array            $payload
     * @param  float            $startMs
     * @return JsonResponse
     */
    protected function handleIngestException(
        LisIngestException $e,
        LisBridgeConfig $config,
        string $route,
        array $payload,
        float $startMs
    ): JsonResponse {
        $durationMs = (int) ((hrtime(true) / 1_000_000) - $startMs);

        // Log the failure
        LisIngestLog::logError(
            $config->id,
            $route,
            $payload,
            $e->getStatusCode(),
            $e->getMessage(),
            $durationMs
        );

        // Re-throw to let Laravel's exception handler render the JSON response
        throw $e;
    }
}
