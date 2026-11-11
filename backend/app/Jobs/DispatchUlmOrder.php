<?php

namespace App\Jobs;

use App\Models\LaboratoryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchUlmOrder implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = PHP_INT_MAX;
    public int $timeout = 60;

    /**
     * Exponential backoff per attempt (seconds).
     *   attempt 1 → 5s   (absorbs transient blips)
     *   attempt 2 → 30s
     *   attempt 3 → 1m   (covers short maintenance windows)
     *   attempt 4 → 5m
     *   attempt 5 → 15m
     *   attempt 6 → 30m
     *   attempt 7+ → 1h (capped — survives multi-hour outages)
     */
    public function backoff(): array
    {
        return [5, 30, 60, 300, 900, 1800, 3600];
    }

    public function __construct(public int $laboratoryRequestId)
    {
    }

    public function handle(): void
    {
        if (!config('ulm.enabled')) {
            Log::channel('ulm')->info('[ULM] dispatch skipped (disabled)', [
                'sample_id' => $this->laboratoryRequestId,
            ]);
            return;
        }

        $labRequest = $this->resolveRequest();
        if (!$labRequest) {
            return;
        }

        $this->process($labRequest);
    }

    protected function resolveRequest()
    {
        return LaboratoryRequest::with([
            'tests' => fn ($q) => $q->whereNotNull('tests.panel_code'),
            'patient:id,last_name,first_name,date_of_birth,gender',
            'ward:id,name',
            'room:id,name',
        ])->find($this->laboratoryRequestId);
    }

    public function process($labRequest): void
    {
        $payload = $this->buildPayload($labRequest);

        if (empty($payload['test_codes'])) {
            Log::channel('ulm')->info('[ULM] dispatch skipped (no eligible tests)', [
                'sample_id' => $payload['sample_id'],
            ]);
            return;
        }

        Log::channel('ulm')->info('[ULM] outbound payload', [
            'sample_id' => $payload['sample_id'],
            'payload'   => $this->safePayloadForLog($payload),
        ]);

        // $url = rtrim((string) config('ulm.endpoint'), '/').'/api/lis/orders';

        $base = rtrim((string) config('ulm.endpoint'), '/');
        $path = ltrim((string) config('ulm.endpoint_path', '/orders/lis'), '/');
        $url = $base.'/'.$path;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('ulm.token'),
            'Accept'        => 'application/json',
        ])
            ->timeout((int) config('ulm.timeout', 15))
            ->asJson()
            ->post($url, $payload);

        if (!$response->successful()) {
            // Log as WARNING, not error — this is attempt N of an effectively
            // unbounded retry chain; only the FINAL attempt (which now lives
            // exclusively in failed_jobs) should be considered an error.
            Log::channel('ulm')->warning('[ULM] POST failed (will retry)', [
                'sample_id' => $payload['sample_id'],
                'status'    => $response->status(),
                'body'      => substr($response->body(), 0, 500),
            ]);
            // Throwing hands the job back to the queue worker, which honors
            // $tries (=PHP_INT_MAX) and backoff(). The job stays in the queue
            // and recycles until ULM accepts it. No human intervention needed.
            throw new \RuntimeException('ULM HTTP '.$response->status());
        }
    }

    public function buildPayload($req): array
    {
        $included = [];
        $excluded = [];

        foreach ($req->tests as $pivotTest) {
            $code = $pivotTest->panel_code;
            if (is_null($code) || $code === '') {
                $excluded[] = [
                    'test_id'   => $pivotTest->id,
                    'sample_id' => $req->sample_id ?? (string) $req->id,
                    'reason'    => 'panel_code_null',
                ];
                continue;
            }
            $included[] = (string) $code;
        }

        foreach ($excluded as $row) {
            Log::channel('ulm')->info(
                "[ULM] excluded test_id={$row['test_id']} sample_id={$row['sample_id']} reason=panel_code_null"
            );
        }

        $p = $req->patient;

        $patientName = trim(($p->last_name ?? '').'^'.($p->first_name ?? ''));

        $ward = $req->ward?->name;
        $room = $req->room?->name;
        $requestingLocation = trim(implode(' - ', array_filter([$ward, $room])));
        if ($requestingLocation === '') {
            $requestingLocation = 'OPD';
        }

        // collection_time: COMPACT YYYYMMDDHHMMSS (14 chars)
        // Note: `collected_at` lives on the pivot table, not on `laboratory_requests`.
        // We therefore use `request_date` (the parent request timestamp) as the source.
        $collectionTime = $req->request_date;
        if ($collectionTime instanceof \DateTimeInterface) {
            $collectionCompact = Carbon::instance($collectionTime)->format('YmdHis');
        } else {
            $collectionCompact = Carbon::now()->format('YmdHis');
        }

        $payload = [
            'sample_id'           => $req->sample_id ?? (string) $req->id,
            'patient_id'          => (string) ($p->id ?? ''),
            'patient_name'        => $patientName,
            'test_codes'          => array_values(array_unique($included)),
            'priority'            => $req->priority_level === 'STAT' ? 'S' : 'R',
            'collection_time'     => $collectionCompact,
            'requesting_location' => $requestingLocation,
        ];

        // // OPTIONAL: gender — only if M or F
        // $storedGender = (string) ($p->gender ?? '');
        // $sex = strtoupper(substr($storedGender, 0, 1));
        // if (in_array($sex, ['M', 'F'], true)) {
        //     $payload['gender'] = $sex;
        // }

        // // OPTIONAL: DoB — only if date_of_birth is set, COMPACT YYYYMMDD (8 chars)
        // if (!empty($p->date_of_birth)) {
        //     $dob = $p->date_of_birth instanceof \DateTimeInterface
        //         ? Carbon::instance($p->date_of_birth)
        //         : Carbon::parse($p->date_of_birth);
        //     $payload['DoB'] = $dob->format('Ymd');
        // }

        return $payload;
    }

    public function safePayloadForLog(array $payload): array
    {
        $copy = $payload;
        $copy['patient_name'] = '[REDACTED]';
        return $copy;
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('ulm')->error('[ULM] job exhausted retries', [
            'laboratory_request_id' => $this->laboratoryRequestId,
            'error'                 => $e->getMessage(),
        ]);
    }
}