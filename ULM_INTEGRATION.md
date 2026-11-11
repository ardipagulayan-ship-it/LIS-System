# ULM Integration (LIS → Analyzer)

This document describes the LIS-to-ULM (analyzer) integration. The LIS posts
a compact JSON order to the ULM whenever a specimen is checked in by
phlebotomy. Dispatch is **queued** (non-blocking) and runs through the
Laravel queue worker.

---

## 1. Endpoints

| Direction | Method | URL | Auth |
|---|---|---|---|
| LIS → ULM | POST | `${ULM_ENDPOINT}/api/lis/orders` | `Authorization: Bearer ${ULM_TOKEN}` |

**Content-Type:** `application/json`

---

## 2. Outbound Payload

```json
{
  "sample_id": "S-100",
  "patient_id": "41",
  "patient_name": "BALISTRERI^OTTLIE",
  "test_codes": ["CBC", "CHEM10"],
  "priority": "R",
  "collection_time": "20250411132425",
  "requesting_location": "ICU - 4",
  "gender": "F",
  "DoB": "19750207"
}
```

### Field rules

| Field | Type | Format | Source |
|---|---|---|---|
| `sample_id` | string | stable id | `lab_requests.sample_id ?? (string)lab_requests.id` |
| `patient_id` | string | numeric | `(string)patients.id` |
| `patient_name` | string | `LASTNAME^FIRSTNAME` | `patients.last_name ^ patients.first_name` |
| `test_codes` | array<string> | non-null `panel_code` only | `tests.panel_code` |
| `priority` | string | `R` or `S` | `STAT → S`, else `R` |
| `collection_time` | string | **`YYYYMMDDHHMMSS`** (14 chars) | `lab_requests.request_date` |
| `requesting_location` | string | `WARD - ROOM` or `OPD` | `wards.name - rooms.name` |
| `gender` | string | `M` or `F` (optional) | `patients.gender` first char |
| `DoB` | string | **`YYYYMMDD`** (8 chars, optional) | `patients.date_of_birth` |

**Excluded tests** (those with `panel_code = NULL`) are dropped from
`test_codes` and individually logged to the `ulm` channel.

---

## 3. Environment Variables

```
ULM_ENABLED=false          # Set to true to activate dispatch
ULM_ENDPOINT=              # e.g. http://ulm-host:8000
ULM_TOKEN=                 # Bearer token issued by ULM team
ULM_TIMEOUT=15             # HTTP timeout seconds
ULM_LOG_LEVEL=debug        # Verbosity for ulm channel
QUEUE_CONNECTION=database   # Must be 'database' for retries to work
```

---

## 4. Configuration

- `backend/config/ulm.php` — reads env, exposes `enabled`, `endpoint`,
  `token`, `timeout`, `log_channel`.
- `backend/config/logging.php` — defines the `ulm` channel writing to
  `storage/logs/ulm.log`.

---

## 5. Dispatch Flow

1. User checks in a specimen via Order Management → frontend calls
   `POST /api/order/requests/accept/{id}` (or batch).
2. `OrderManagementController::markAsAccepted` /
   `markBatchAsAccepted` saves the pivot and calls
   `tryDispatchUlm(int $labRequestId)`.
3. `tryDispatchUlm()` returns `'queued'` / `'skipped'` / `'failed'`
   without throwing, and includes `ulm_status` in the HTTP response.
4. `DispatchUlmOrder` Job is enqueued in the `jobs` table.
5. Queue worker (`php artisan queue:work`) picks up the job and POSTs the
   payload to ULM. On HTTP failure, the Job throws and Laravel retries
   per `$tries = 3` with `$backoff = 5` seconds.
6. After `$tries` failures the Job moves to `failed_jobs`. The `failed()`
   hook logs to the `ulm` channel.

---

## 6. Logs

All ULM-related logs go to `storage/logs/ulm.log`:

| Level | When |
|---|---|
| info | Dispatch skipped (disabled / no eligible tests) |
| info | Outbound payload (with `patient_name` REDACTED) |
| info | Per-test exclusion (`reason=panel_code_null`) |
| error | ULM POST failed (non-2xx) |
| error | Job exhausted retries |

`patient_name` is **redacted** to `[REDACTED]` in the outbound-payload
log line via `safePayloadForLog()` on the Job. `sample_id` is always
preserved for traceability.

The `Authorization` header is **never logged**.

---

## 7. Idempotency

`sample_id` is the stable key the ULM uses to dedupe retries. The Job
always sends the same `sample_id` for the same `lab_request`, so a
retry of the same order will be deduplicated by ULM.

---

## 8. Security

- Bearer token + IP allowlist at network layer (LAN deployment).
- Production endpoint must be `https://`.
- API key lives in `.env` only — never in repo.