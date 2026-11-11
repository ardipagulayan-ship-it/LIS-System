# LIS Bridge Receiver — Implementation Plan

**Status:** READY FOR IMPLEMENTATION — Architecture locked, Phase 0 contract pending  
**Version:** 2.0 (locked)  
**Date:** 2026-09-07  
**Target codebase:** `/run/media/ardi/FE225375225331BF/Trae Projects/LIS-System` (Laravel 11 backend + Vue 3 frontend)  
**Wire format reference:** [`LIS Bridge Forwarder — Wire Form.md`](LIS%20Bridge%20Forwarder%20%E2%80%94%20Wire%20Form.md) (authoritative)  
**Architecture review reference:** Prior chat session — the following decisions are **final** and not to be revisited:

The architecture review settled three fundamental questions: (1) `observed_at` lives exclusively on `lis_bridge_translation` — no mirror column on `test_results` or `quality_control_results`. (2) Analyzer identification uses `equipment.serial_no` as a natural key; the JSON `analyzer_id_map` column is **dropped** from `lis_bridge_configs`, and the wire format is extended to carry `analyzer_serial` (string, max 128) from the middleware team. (3) `lis_bridge_translation` is the **sole source of truth** for deduplication and wire-to-internal mapping — it is written in the same DB transaction as the result row and drives every downstream dedup/supersession lookup.

---

## 2. Executive Summary

The goal is to build the Laravel receiver side of the LIS Bridge so that the Python forwarder (already in production elsewhere) can POST patient and QC analyzer results into this application and have them written to `test_results` and `quality_control_results` without modifying the existing wire contract — except for the one additive `analyzer_serial` field the middleware team must add to the payload. The receiver provides three JSON endpoints (two ingest, one reachability probe), a custom API-key middleware outside the Sanctum auth stack, a translation-table approach for dedup + mapping, idempotent ingestion in a single DB transaction, async queue jobs for downstream CDS/delta/QC rule evaluation, an admin UI for API-key and config management, and a comprehensive test suite.

The **total effort estimate is ~4 person-weeks** across 12 implementation phases. The wire format change (adding `analyzer_serial`) must be delivered by the middleware team **before Phase 5 validation rules can be finalized**; Phase 0 contract work can proceed in parallel. Once delivered, the forwarder's `send_to_lis()` call will receive a `204 No Content` within the 10-second window and the receiving app will perform all heavy downstream work on a queue.

The architecture is deliberately lean: zero additive columns on result tables (`test_results`, `quality_control_results`, `quality_controls`, `equipment`, `patients`, `test_parameters`). Every LIS-derived field lives in `lis_bridge_translation`, which is the single translation table written atomically with each result row. Analyzer lookup is via `equipment.serial_no` (already present on the Equipment model), not via a JSON map or a separate mapping table.

---

## 3. Architecture Overview

```
                                ┌──────────────────────────┐
   Python Forwarder             │  Laravel 11 Receiver      │
   (httpx.AsyncClient)         │  (this codebase)           │
   ────────────────────────►   │                            │
   POST application/json        │  Routes (api.php)          │
   Authorization: Bearer <key>  │   ├─ /api/v1/patient-results
   timeout=10s                  │   ├─ /api/v1/qc-results
   + analyzer_serial (NEW)     │   └─ /api/lis-bridge/test-connection
                                 │           │
                                 │           ▼
                                 │  VerifyLisBridgeApiKey  (custom middleware)
                                 │   • sha256(api_key) lookup in lis_bridge_api_keys
                                 │   • hash_equals() constant-time compare
                                 │   • updates last_used_at
                                 │   • attaches LisBridgeApiKey to request
                                 │           │
                                 │           ▼
                                 │  FormRequest validation
                                 │   StoreLisPatientResultRequest
                                 │   StoreLisQcResultRequest
                                 │   (prepareForValidation → trim/normalize)
                                 │           │
                                 │           ▼
                                 │  LisBridgeReceiverController
                                 │   • ingestPatient()  → 204
                                 │   • ingestQc()       → 204
                                 │   • testConnection() → 204
                                 │           │
                                 │           ▼
                                 │  LisResultIngestionService
                                 │   • dedup via lis_bridge_translation.external_result_id
                                 │   • resolveEquipment() via equipment.serial_no
                                 │   • resolveControl() via quality_controls.control_id
                                 │   • resolveLabRequest() via sample_id/patient_id
                                 │   • resolveTestParameter() via test_parameters.lis_code
                                 │   • Single DB transaction: INSERT result + INSERT translation
                                 │           │
                                 │         ┌─┴──────────────┐
                                 │         ▼                ▼
                                 │   DB write           queue dispatch
                                 │   (sync, <2s p95)   ProcessLisPatientResult
                                 │                     ProcessLisQcResult
                                 │                            │
                                 │                            ▼
                                 │                     DeltaCheck / Reflex / CDS /
                                 │                     QC Westgard / Sigma metrics /
                                 │                     QC lockout
                                 │
                                 │   Tables touched:
                                 │     • lis_bridge_configs          (NEW — settings)
                                 │     • lis_bridge_api_keys          (NEW — key rotation, hash)
                                 │     • lis_bridge_translation        (NEW — translation + dedup SOT)
                                 │     • lis_ingest_log               (NEW — every POST, status, timing)
                                 │     • test_results                 (NO additive columns)
                                 │     • quality_control_results       (NO additive columns)
```

**Component list with file paths:**

| Component | File path |
|-----------|-----------|
| Routes | [`backend/routes/api.php`](backend/routes/api.php) |
| Auth Middleware | [`backend/app/Http/Middleware/VerifyLisBridgeApiKey.php`](backend/app/Http/Middleware/VerifyLisBridgeApiKey.php) |
| Middleware Registration | [`backend/bootstrap/app.php`](backend/bootstrap/app.php) |
| Patient Form Request | [`backend/app/Http/Requests/Api/StoreLisPatientResultRequest.php`](backend/app/Http/Requests/Api/StoreLisPatientResultRequest.php) |
| QC Form Request | [`backend/app/Http/Requests/Api/StoreLisQcResultRequest.php`](backend/app/Http/Requests/Api/StoreLisQcResultRequest.php) |
| Controller | [`backend/app/Http/Controllers/Api/LisBridgeReceiverController.php`](backend/app/Http/Controllers/Api/LisBridgeReceiverController.php) |
| Ingestion Service | [`backend/app/Services/LisResultIngestionService.php`](backend/app/Services/LisResultIngestionService.php) |
| Patient Queue Job | [`backend/app/Jobs/ProcessLisPatientResult.php`](backend/app/Jobs/ProcessLisPatientResult.php) |
| QC Queue Job | [`backend/app/Jobs/ProcessLisQcResult.php`](backend/app/Jobs/ProcessLisQcResult.php) |
| Bridge Config Model | [`backend/app/Models/LisBridgeConfig.php`](backend/app/Models/LisBridgeConfig.php) |
| API Key Model | [`backend/app/Models/LisBridgeApiKey.php`](backend/app/Models/LisBridgeApiKey.php) |
| Translation Model | [`backend/app/Models/LisBridgeTranslation.php`](backend/app/Models/LisBridgeTranslation.php) |
| Ingest Log Model | [`backend/app/Models/LisIngestLog.php`](backend/app/Models/LisIngestLog.php) |
| Equipment Model (existing) | [`backend/app/Models/Equipment.php`](backend/app/Models/Equipment.php) |
| TestResult Model (existing) | [`backend/app/Models/TestResult.php`](backend/app/Models/TestResult.php) |
| QualityControlResult Model (existing) | [`backend/app/Models/QualityControlResult.php`](backend/app/Models/QualityControlResult.php) |

**Data flow summary:** Forwarder → `Authorization: Bearer <key>` → [`VerifyLisBridgeApiKey`](backend/app/Http/Middleware/VerifyLisBridgeApiKey.php) → [`StoreLisPatientResultRequest`](backend/app/Http/Requests/Api/StoreLisPatientResultRequest.php) / [`StoreLisQcResultRequest`](backend/app/Http/Requests/Api/StoreLisQcResultRequest.php) → [`LisBridgeReceiverController`](backend/app/Http/Controllers/Api/LisBridgeReceiverController.php) → [`LisResultIngestionService`](backend/app/Services/LisResultIngestionService.php) → DB transaction (INSERT result + INSERT [`lis_bridge_translation`](backend/app/Models/LisBridgeTranslation.php) row) → dispatch queue job → `204`.

---

## 4. Locked Architectural Decisions

| # | Decision | Rationale | Wire Format Reference |
|---|----------|-----------|----------------------|
| 1 | **`observed_at` lives only on `lis_bridge_translation`.** No mirror column on `test_results` or `quality_control_results`. The translation table is the SOT for all LIS-derived fields. | Keeps existing result tables clean. `test_results` schema is unchanged for backward compatibility. `observed_at` is only needed for LIS-deduplication and audit. | Wire Form lines 71–73 |
| 2 | **Analyzer identification via `equipment.serial_no` natural key.** The JSON `analyzer_id_map` column is **dropped** from `lis_bridge_configs`. The wire format adds `analyzer_serial` (string, max 128, required). Receiver prefers `analyzer_serial`; falls back to `analyzer_id` (integer, optional) for backward compatibility with existing forwarder deployments. | Eliminates a JSON column and a UI workflow. `serial_no` is already a required field on `equipment`. No separate mapping table needed. Backward compat is preserved via the optional `analyzer_id` field. | Wire Form lines 48–49; middleware team must add `analyzer_serial` |
| 3 | **`lis_bridge_translation` is the sole source of truth** for deduplication and wire-to-internal mapping. Every INSERT into `test_results` or `quality_control_results` is paired with an INSERT into `lis_bridge_translation` in the **same DB transaction**. All dedup and supersession lookups go through `lis_bridge_translation`, never directly on result table columns. | Single place to audit all LIS-derived field mappings. Simplifies dedup logic — one table to query. Avoids proliferating LIS columns across result tables. | N/A — this is an internal architecture decision |

---

## 5. Phase 0 — Joint Receiver Contract Specification

**File to create:** [`docs/LIS_BRIDGE_RECEIVER_CONTRACT.md`](docs/LIS_BRIDGE_RECEIVER_CONTRACT.md)

**Purpose:** Freeze the joint contract before any code is written. Both teams sign off.

**Three remaining Phase 0 decisions to settle:**

1. **`equipment.serial_no` data integrity check.** The migration `ensure_equipment_serial_no_unique.php` will check for duplicates before applying the UNIQUE NOT NULL constraint. The contract must specify: if duplicates exist in staging/production, what is the resolution procedure? (a) Clean up manually before migration, (b) migration fails with a clear error and requires manual intervention. Recommendation: (b) — fail fast with a SQL error listing the duplicate `serial_no` values.

2. **`control_id_map` JSON structure for QC.** For QC results, `control_id` (string) from the wire must map to `quality_controls.id`. Two options: (a) Add `lis_control_id` column to `quality_controls` table (cleanest, one-time mapping); (b) Store a JSON `control_id_map` on `lis_bridge_configs` (like the old `analyzer_id_map`). Recommendation: (a) — add `lis_control_id` column to `quality_controls` via the Phase 1 migration. This is a one-time setup, no ongoing JSON editing needed.

3. **Single global config vs per-bridge config.** Should `lis_bridge_configs` support multiple rows (one per physical bridge/analyzer farm) or be a single global config? Recommendation: multiple rows (per-bridge), since each physical forwarder installation has its own API key, URL, and serial number set. The `LisBridgeApiKey` FK enforces this isolation.

**Effort:** M. **Blocks all subsequent phases.**

---

## 6. Phase 1 — Database Schema (3 Migrations)

### Migration 1: `create_lis_bridge_infrastructure.php`

**File:** [`backend/database/migrations/2026_09_XX_create_lis_bridge_infrastructure.php`](backend/database/migrations/2026_09_XX_create_lis_bridge_infrastructure.php)  
**Purpose:** Create the 4 new bridge tables in a single migration for atomicity.

**Full CREATE TABLE SQL:**

```sql
-- lis_bridge_configs: per-bridge settings (one row per physical forwarder installation)
CREATE TABLE lis_bridge_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Human label, e.g. "main-lab-bridge"',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    lis_url VARCHAR(255) NULL COMMENT 'Legacy single URL',
    patient_lis_url VARCHAR(255) NULL COMMENT 'Per-type URL (patient)',
    qc_lis_url VARCHAR(255) NULL COMMENT 'Per-type URL (qc)',
    max_retries TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Informational only; forwarder enforces',
    poll_interval_sec TINYINT UNSIGNED NOT NULL DEFAULT 5,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- lis_bridge_api_keys: API key store with SHA-256 hash
CREATE TABLE lis_bridge_api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lis_bridge_config_id BIGINT UNSIGNED NOT NULL,
    key_hash VARCHAR(64) NOT NULL COMMENT 'sha256 hex of plaintext key; never store plaintext',
    label VARCHAR(255) NOT NULL COMMENT 'e.g. "forwarder-prod-2026-09"',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    expires_at TIMESTAMP NULL COMMENT 'Optional soft expiry',
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (lis_bridge_config_id) REFERENCES lis_bridge_configs(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_key_hash (key_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- lis_bridge_translation: sole SOT for LIS dedup + wire-to-internal mapping
CREATE TABLE lis_bridge_translation (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lis_bridge_config_id BIGINT UNSIGNED NOT NULL,
    -- Wire identity (from payload)
    external_result_id BIGINT UNSIGNED NOT NULL COMMENT 'Wire field: result_id',
    external_run_number SMALLINT UNSIGNED NOT NULL COMMENT 'Wire field: run_number',
    -- Wire metadata
    external_result_status CHAR(1) NULL COMMENT 'Wire field: result_status (F/P/C/X)',
    external_raw_value VARCHAR(255) NULL COMMENT 'Wire field: value (raw text before numeric parse)',
    external_unit VARCHAR(32) NULL COMMENT 'Wire field: unit',
    external_sample_id VARCHAR(64) NULL COMMENT 'Wire field: sample_id',
    external_patient_id VARCHAR(64) NULL COMMENT 'Wire field: patient_id',
    external_control_id VARCHAR(64) NULL COMMENT 'Wire field: control_id (QC only)',
    external_test_code VARCHAR(64) NULL COMMENT 'Wire field: test_code',
    -- Analyzer identification (both fields present for backward compat)
    analyzer_serial VARCHAR(128) NULL COMMENT 'Wire field: analyzer_serial (NEW, preferred)',
    external_analyzer_id BIGINT UNSIGNED NULL COMMENT 'Wire field: analyzer_id (legacy fallback)',
    -- Observation timestamp (SOT — NOT mirrored on result tables)
    observed_at TIMESTAMP NULL COMMENT 'Wire field: observed_at; SOT on this table only',
    -- Internal IDs resolved at ingest time
    equipment_id BIGINT UNSIGNED NULL COMMENT 'Resolved via equipment.serial_no',
    test_parameter_id BIGINT UNSIGNED NULL COMMENT 'Resolved via test_parameters.lis_code',
    lab_request_id BIGINT UNSIGNED NULL COMMENT 'Resolved via sample_id/patient_id',
    qc_id BIGINT UNSIGNED NULL COMMENT 'Resolved via quality_controls.lis_control_id (QC only)',
    -- Result row linkage (nullable until result is written)
    test_result_id BIGINT UNSIGNED NULL,
    qc_result_id BIGINT UNSIGNED NULL,
    -- Dedup / supersession
    is_current BOOLEAN NOT NULL DEFAULT TRUE,
    superseded_by_id BIGINT UNSIGNED NULL COMMENT 'FK to self; points to the newer translation row',
    -- Audit
    received_from_lis_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (lis_bridge_config_id) REFERENCES lis_bridge_configs(id) ON DELETE RESTRICT,
    -- Primary dedup index: unique(external_result_id) within a bridge
    UNIQUE INDEX idx_bridge_external_result (lis_bridge_config_id, external_result_id),
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_test_parameter_id (test_parameter_id),
    INDEX idx_lab_request_id (lab_request_id),
    INDEX idx_qc_id (qc_id),
    INDEX idx_is_current (is_current),
    INDEX idx_observed_at (observed_at),
    INDEX idx_test_result_id (test_result_id),
    INDEX idx_qc_result_id (qc_result_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- lis_ingest_log: per-request audit log
CREATE TABLE lis_ingest_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    source_ip VARCHAR(45) NULL,
    endpoint VARCHAR(255) NOT NULL COMMENT 'e.g. patient-results, qc-results',
    http_status SMALLINT UNSIGNED NOT NULL COMMENT 'Final HTTP status returned',
    request_body JSON NULL COMMENT 'Full payload for forensics (truncated if >64KB)',
    response_body JSON NULL COMMENT 'Truncated to 4KB',
    duration_ms INT UNSIGNED NOT NULL COMMENT 'microtime(true) end − start',
    external_result_id BIGINT UNSIGNED NULL COMMENT 'For dedup forensics',
    is_qc BOOLEAN NULL,
    bridge_config_id BIGINT UNSIGNED NULL,
    api_key_id BIGINT UNSIGNED NULL,
    error_class VARCHAR(255) NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (bridge_config_id) REFERENCES lis_bridge_configs(id) ON DELETE SET NULL,
    FOREIGN KEY (api_key_id) REFERENCES lis_bridge_api_keys(id) ON DELETE SET NULL,
    INDEX idx_received_at (received_at),
    INDEX idx_external_result_id (external_result_id),
    INDEX idx_http_status (http_status),
    INDEX idx_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Rollback strategy:** `DROP TABLE IF EXISTS lis_ingest_log, lis_bridge_translation, lis_bridge_api_keys, lis_bridge_configs;`

**Effort:** M.

---

### Migration 2: `widen_quality_control_result_value.php`

**File:** [`backend/database/migrations/2026_09_XX_widen_quality_control_result_value.php`](backend/database/migrations/2026_09_XX_widen_quality_control_result_value.php)  
**Purpose:** Fix the `quality_control_results.result_value` column type from `decimal(10,4) NOT NULL` to `varchar(255) NULL` to accommodate raw analyzer text (e.g. `">100"`, `"POS"`, `"NEG"`).

**Full ALTER TABLE SQL:**

```sql
ALTER TABLE quality_control_results
    CHANGE COLUMN result_value result_value VARCHAR(255) NULL COMMENT 'Raw analyzer text; varchar to accommodate non-numeric results';
```

**Rollback strategy:**

```sql
ALTER TABLE quality_control_results
    CHANGE COLUMN result_value result_value DECIMAL(10,4) NOT NULL COMMENT 'Revert to numeric-only';
```

**Effort:** S.

---

### Migration 3: `ensure_equipment_serial_no_unique.php`

**File:** [`backend/database/migrations/2026_09_XX_ensure_equipment_serial_no_unique.php`](backend/database/migrations/2026_09_XX_ensure_equipment_serial_no_unique.php)  
**Purpose:** Enforce `equipment.serial_no` as UNIQUE NOT NULL, which is the required natural key for analyzer identification.

**Full ALTER TABLE SQL:**

```sql
-- Step 1: Check for existing NULL values (must be resolved before unique constraint)
-- If NULLs exist, the migration fails with a clear error listing affected equipment IDs.
-- Example check (implemented in up() PHP method):
--   $nulls = DB::table('equipment')->whereNull('serial_no')->pluck('id');
--   if ($nulls->isNotEmpty()) {
--       throw new RuntimeException("equipment.serial_no has NULL values on rows: " . $nulls->implode(', '));
--   }

-- Step 2: Check for existing duplicate values
--   $dups = DB::table('equipment')
--       ->select('serial_no')
--       ->whereNotNull('serial_no')
--       ->groupBy('serial_no')
--       ->havingRaw('COUNT(*) > 1')
--       ->pluck('serial_no');
--   if ($dups->isNotEmpty()) {
--       throw new RuntimeException("equipment.serial_no has duplicate values: " . $dups->implode(', '));
--   }

-- Step 3: Apply constraint
ALTER TABLE equipment
    MODIFY COLUMN serial_no VARCHAR(128) NOT NULL,
    ADD UNIQUE INDEX idx_equipment_serial_no (serial_no);
```

**Rollback strategy:**

```sql
ALTER TABLE equipment
    DROP INDEX idx_equipment_serial_no,
    MODIFY COLUMN serial_no VARCHAR(128) NULL;
```

**Effort:** S.

---

### Migration 4 (not Phase 1 — mentioned for context)

No additional migrations are required. All other requirements (no additive columns on result tables, `lis_control_id` on `quality_controls` if needed) are deferred to Phase 0 sign-off or Phase 1 addendum if the `control_id_map` decision requires a schema change.

**Effort:** M.

---

## 7. Phase 2 — Models (3 New + 2 Modified)

**New models:**

| Model | File | Key attributes |
|-------|------|----------------|
| `LisBridgeConfig` | [`backend/app/Models/LisBridgeConfig.php`](backend/app/Models/LisBridgeConfig.php) | `$fillable = ['name','is_active','lis_url','patient_lis_url','qc_lis_url','max_retries','poll_interval_sec']`; `$casts = ['is_active'=>'bool','max_retries'=>'int','poll_interval_sec'=>'int']`; relations: `apiKeys()`, `ingestLogs()`, `translations()` |
| `LisBridgeApiKey` | [`backend/app/Models/LisBridgeApiKey.php`](backend/app/Models/LisBridgeApiKey.php) | `$fillable = ['lis_bridge_config_id','key_hash','label','is_active','expires_at','last_used_at']`; `$hidden = ['key_hash']`; `$casts = ['is_active'=>'bool','expires_at'=>'datetime','last_used_at'=>'datetime']`; relation: `bridgeConfig()`. Helper: `findByPlaintextKey(string $plain): ?self` — sha256 hex + lookup |
| `LisBridgeTranslation` | [`backend/app/Models/LisBridgeTranslation.php`](backend/app/Models/LisBridgeTranslation.php) | All columns from Migration 1; `$casts = ['observed_at'=>'datetime','received_from_lis_at'=>'datetime','is_current'=>'bool','request_body'=>'array','response_body'=>'array']`; relations: `bridgeConfig()`, `apiKey()`, `equipment()`, `testParameter()`, `labRequest()`, `qualityControl()`, `testResult()`, `qcResult()` |
| `LisIngestLog` | [`backend/app/Models/LisIngestLog.php`](backend/app/Models/LisIngestLog.php) | All columns from Migration 1; `$casts = ['received_at'=>'datetime','duration_ms'=>'int','is_qc'=>'bool']`; relations: `bridgeConfig()`, `apiKey()` |

**Modified models — NO changes (zero additive columns):**

- [`backend/app/Models/TestResult.php`](backend/app/Models/TestResult.php) — **NO CHANGES**. `test_results` remains untouched. LIS-derived fields live on `lis_bridge_translation`.
- [`backend/app/Models/QualityControlResult.php`](backend/app/Models/QualityControlResult.php) — **NO CHANGES** to fillable/casts. Only the column type fix via Migration 2 (from `decimal` to `varchar`).

**Effort:** S. **Depends on:** Phase 1.

---

## 8. Phase 3 — Authentication Middleware

**File to create:** [`backend/app/Http/Middleware/VerifyLisBridgeApiKey.php`](backend/app/Http/Middleware/VerifyLisBridgeApiKey.php)

**Middleware registration in** [`backend/bootstrap/app.php`](backend/bootstrap/app.php):

```php
// Inside withMiddleware(function (Middleware $middleware) { ... })
$middleware->alias([
    'lis.bridge' => \App\Http\Middleware\VerifyLisBridgeApiKey::class,
]);
```

**Middleware behavior:**

1. Read `Authorization` header. If missing → `401 {"error":"missing_api_key"}`.
2. Strip `Bearer ` prefix. If missing → `401 {"error":"malformed_authorization"}`.
3. If `strlen($token) > 256` → `401 {"error":"token_too_long"}`.
4. Compute `sha256($token)`. Lookup `LisBridgeApiKey::where('key_hash', $hash)->where('is_active', true)->first()`. Use `hash_equals()` for constant-time comparison.
5. If row not found → `401 {"error":"invalid_api_key"}`.
6. If `expires_at` is past → `401 {"error":"expired_api_key"}`.
7. Attach resolved `LisBridgeApiKey` to request via `$request->attributes->set('lis_bridge_api_key', $apiKey)`.
8. Fire-and-forget: update `last_used_at = now()` (do not block response).
9. Continue.

**Acceptance criteria:**

- Missing header → 401.
- Bad prefix → 401.
- Unknown key → 401.
- Inactive key → 401.
- Expired key → 401.
- Valid key → request reaches controller; `last_used_at` updated within 60s.
- Constant-time compare confirmed (`hash_equals` present in code).
- Plaintext key is **never** logged.

**Effort:** S. **Depends on:** Phase 2.

---

## 9. Phase 4 — Routes (3 Endpoints)

**File to modify:** [`backend/routes/api.php`](backend/routes/api.php)

**Additions** (placed **before** any `Route::middleware('auth:sanctum')->group(...)` block):

```php
use App\Http\Middleware\VerifyLisBridgeApiKey;

Route::middleware([VerifyLisBridgeApiKey::class])
    ->prefix('v1')
    ->group(function () {
        Route::post('patient-results', [LisBridgeReceiverController::class, 'ingestPatient']);
        Route::post('qc-results',      [LisBridgeReceiverController::class, 'ingestQc']);
    });

Route::middleware([VerifyLisBridgeApiKey::class])
    ->post('lis-bridge/test-connection', [LisBridgeReceiverController::class, 'testConnection']);
```

> The receiver routes are **deliberately outside** the `auth:sanctum` group — the Python forwarder has no Sanctum tokens and never will.

**Acceptance criteria:**

- `php artisan route:list` shows the three routes under the `api` middleware group.
- Each route shows `VerifyLisBridgeApiKey` in its middleware list.
- No route shows `auth:sanctum` or `auth:web`.

**Effort:** S. **Depends on:** Phase 3.

---

## 10. Phase 5 — Form Request Validation (2 Classes)

**Files to create:**

- [`backend/app/Http/Requests/Api/StoreLisPatientResultRequest.php`](backend/app/Http/Requests/Api/StoreLisPatientResultRequest.php)
- [`backend/app/Http/Requests/Api/StoreLisQcResultRequest.php`](backend/app/Http/Requests/Api/StoreLisQcResultRequest.php)

**Validation rules — all 14 wire-format fields:**

| Field | Rule | Type | Justification |
|-------|------|------|---------------|
| `message_type` | `required|string\|in:patient,qc` | string | Discriminator; must match `is_qc` |
| `is_qc` | `required\|boolean` | boolean | Primary QC flag |
| `result_id` | `required\|integer\|min:1` | integer | Forwarder PK; dedup key part 1 |
| `analyzer_serial` | `required\|string\|max:128` | string, **NEW** | Preferred analyzer identifier; maps to `equipment.serial_no` |
| `analyzer_id` | `nullable\|integer\|min:1` | integer | **Backward compat only**; fallback if `analyzer_serial` not yet deployed |
| `patient_id` | `required\|string\|max:64` | string | Patient identifier; `"QC"` sentinel for QC rows |
| `sample_id` | `required\|string\|max:64` | string | Sample identifier |
| `control_id` | `nullable\|string\|max:64` | string | QC control material ID; null for patient |
| `test_code` | `required\|string\|max:64` | string | LIS-mapped test code; maps to `test_parameters.lis_code` |
| `value` | `required\|string\|max:255` | string | Raw analyzer text; kept as string (e.g. `">100"`, `"POS"`) |
| `unit` | `nullable\|string\|max:32` | string | e.g. `"g/dL"`, `"10^3/uL"` |
| `result_status` | `required\|string\|size:1\|in:F,P,C,X` | string | ASTM-style: Final/Preliminary/Corrected/Cancelled |
| `run_number` | `required\|integer\|min:1\|max:65535` | integer | Re-run counter; drives supersession |
| `observed_at` | `nullable\|date` | string | ISO-8601 with `+00:00`; stored on `lis_bridge_translation` only |

**Cross-field invariants:**

- QC form: `is_qc == true`, `control_id != null`, `patient_id == "QC"`.
- Patient form: `is_qc == false`, `control_id == null`.

**`prepareForValidation()` (both classes):**

- Trim all string fields.
- Cast `is_qc` from int/string `"0"`/`"1"`/`"true"`/`"false"` → bool.
- If `observed_at` is present, normalize to ISO-8601 via `Carbon::parse($value)->toIso8601String()`.
- Lowercase `message_type`.

**Custom error messages:**

```php
public function messages(): array {
    return [
        'result_status.in' => 'result_status must be F (Final), P (Preliminary), C (Corrected), or X (Cancelled).',
        'is_qc.required'   => 'is_qc must be a boolean (true for QC, false for patient).',
        'observed_at.date' => 'observed_at must be ISO-8601, e.g. 2026-09-07T00:50:00+00:00.',
        'analyzer_serial.required' => 'analyzer_serial is required (string, max 128). Middleware team must update build_json_payload().',
    ];
}
```

**Acceptance criteria:**

- Missing `result_id` → 422 with field-level error.
- `result_status="Z"` → 422 with custom message.
- QC payload without `control_id` → 422.
- Patient payload with `control_id="X"` → 422.
- `is_qc="true"` (string) is accepted and coerced.
- A payload that is valid JSON but not an object → 422.
- Payload without `analyzer_serial` but with `analyzer_id` → 422 (because `analyzer_serial` is `required`).

**Effort:** S. **Depends on:** Phase 4.

---

## 11. Phase 6 — Ingestion Service

**File to create:** [`backend/app/Services/LisResultIngestionService.php`](backend/app/Services/LisResultIngestionService.php)

**Public API:**

```php
class LisResultIngestionService {
    public function __construct(
        protected LisBridgeApiKey $apiKey,
        protected LisBridgeConfig $bridgeConfig,
    ) {}

    public function ingestPatient(array $payload): IngestResult;
    public function ingestQc(array $payload): IngestResult;
}
```

`IngestResult` is a DTO: `{ status: 'CREATED'|'UPDATED'|'SUPERSEDED'|'DUPLICATE'|'UNMAPPED', test_result_id?: int, qc_result_id?: int, warnings: string[] }`.

**Full translation table (wire field → internal column, stored on `lis_bridge_translation`):**

| Wire field | `lis_bridge_translation` column | Notes |
|------------|--------------------------------|-------|
| `result_id` | `external_result_id` | Dedup key part 1 |
| `run_number` | `external_run_number` | Dedup/supersession key part 2 |
| `result_status` | `external_result_status` | `"F"`/`"P"`/`"C"`/`"X"` |
| `value` | `external_raw_value` | Raw text before numeric parse |
| `unit` | `external_unit` | e.g. `"g/dL"` |
| `sample_id` | `external_sample_id` | |
| `patient_id` | `external_patient_id` | `"QC"` for QC rows |
| `control_id` | `external_control_id` | QC only |
| `test_code` | `external_test_code` | LIS-mapped code |
| `analyzer_serial` | `analyzer_serial` | **NEW**; maps to `equipment.serial_no` |
| `analyzer_id` | `external_analyzer_id` | Legacy fallback |
| `observed_at` | `observed_at` | SOT on this table; NOT on result tables |
| `equipment_id` | Resolved internally | Via `equipment.serial_no` lookup |
| `test_parameter_id` | Resolved internally | Via `test_parameters.lis_code` |
| `lab_request_id` | Resolved internally | Via `sample_id` + `patient_id` |
| `qc_id` | Resolved internally | Via `quality_controls.lis_control_id` (QC only) |

**`dedupCheck()` — lookup via translation table:**

```php
// Check lis_bridge_translation for this (bridge_config_id, external_result_id)
$existing = LisBridgeTranslation::where('lis_bridge_config_id', $this->bridgeConfig->id)
    ->where('external_result_id', $payload['result_id'])
    ->lockForUpdate()  // Prevent race between concurrent retries
    ->first();

if ($existing) {
    if ($existing->external_run_number < $payload['run_number']) {
        // SUPERSESSION: mark old translation is_current=false, create new row
        $existing->update(['is_current' => false]);
        return $this->createTranslationRow($payload, $isQc: false);
    }
    return new IngestResult(status: 'DUPLICATE', warnings: []);
}
```

**`resolveEquipment()` — serial-based lookup:**

```php
protected function resolveEquipment(string $serialNo): ?Equipment {
    // Primary: lookup by serial_no (preferred, new deployments)
    $equipment = Equipment::where('serial_no', $serialNo)->first();
    if ($equipment) return $equipment;

    // Fallback: if analyzer_id provided and serial not found,
    // this is an unmapped case — return null and let ingestion log it
    return null;
}
```

**`resolveControl()` — QC control material lookup:**

```php
protected function resolveControl(string $controlId): ?QualityControl {
    // Lookup by lis_control_id column on quality_controls table
    // (Phase 0 decision: add lis_control_id column, or use control_id directly)
    return QualityControl::where('lis_control_id', $controlId)->first();
}
```

**`translateStatus()` — wire ASTM status → internal:**

```php
protected function translateStatus(string $wireStatus): string {
    return match ($wireStatus) {
        'F' => 'final',
        'P' => 'preliminary',
        'C' => 'corrected',
        'X' => 'cancelled',
        default => 'preliminary',
    };
}
```

**`buildRunNumberChain()`:**

```php
protected function buildRunNumberChain(int $labRequestId, int $testParameterId): array {
    return LisBridgeTranslation::where('lab_request_id', $labRequestId)
        ->where('test_parameter_id', $testParameterId)
        ->whereNotNull('test_result_id')
        ->orderBy('external_run_number', 'desc')
        ->pluck('external_run_number')
        ->toArray();
}
```

**Single-transaction flow for `ingestPatient()`:**

```php
public function ingestPatient(array $payload): IngestResult {
    return DB::transaction(function () use ($payload) {
        // 1. Dedup check (SELECT FOR UPDATE on lis_bridge_translation)
        $dedup = $this->dedupCheck($payload['result_id'], $payload['run_number'], isQc: false);
        if ($dedup->status === 'DUPLICATE') return $dedup;

        // 2. Resolve equipment via serial_no
        $equipment = $this->resolveEquipment($payload['analyzer_serial']);
        if (!$equipment) {
            $this->logUnmapped($payload, 'equipment', "serial_no={$payload['analyzer_serial']}");
            return new IngestResult(status: 'UNMAPPED', warnings: ['analyzer_serial not found in equipment table']);
        }

        // 3. Resolve test_parameter via lis_code
        $testParam = TestParameter::where('lis_code', $payload['test_code'])->first();
        if (!$testParam) {
            $this->logUnmapped($payload, 'test_parameter', "lis_code={$payload['test_code']}");
            return new IngestResult(status: 'UNMAPPED', warnings: ['test_code not found in test_parameters.lis_code']);
        }

        // 4. Resolve lab_request via sample_id + patient_id
        $labRequest = $this->resolveLabRequest($payload['sample_id'], $payload['patient_id']);
        if (!$labRequest) {
            return new IngestResult(status: 'UNMAPPED', warnings: ['Could not resolve lab_request for sample_id + patient_id']);
        }

        // 5. Parse numeric value (nullable; raw string stored on translation)
        $numericValue = $this->parseNumeric($payload['value']);

        // 6. Insert test_results row
        $testResult = TestResult::create([
            'laboratory_request_id' => $labRequest->id,
            'test_parameter_id'    => $testParam->id,
            'result_value'         => $numericValue,
            'result_status'        => $this->translateStatus($payload['result_status']),
            'is_current'          => true,
            // No LIS columns added — all LIS data lives on lis_bridge_translation
        ]);

        // 7. Mark previous current row is_current=false (supersession path)
        if ($dedup->status === 'SUPERSEDED' && $dedup->superseded_translation_id) {
            LisBridgeTranslation::where('id', $dedup->superseded_translation_id)
                ->update(['is_current' => false, 'superseded_by_id' => $newTranslationId]);
            // Also mark previous test_result is_current=false
            TestResult::where('id', $dedup->superseded_test_result_id)
                ->update(['is_current' => false]);
        }

        // 8. Insert lis_bridge_translation row (SOT for all LIS fields)
        $translation = LisBridgeTranslation::create([
            'lis_bridge_config_id'   => $this->bridgeConfig->id,
            'external_result_id'     => $payload['result_id'],
            'external_run_number'    => $payload['run_number'],
            'external_result_status' => $payload['result_status'],
            'external_raw_value'     => $payload['value'],
            'external_unit'         => $payload['unit'] ?? null,
            'external_sample_id'     => $payload['sample_id'],
            'external_patient_id'    => $payload['patient_id'],
            'external_test_code'     => $payload['test_code'],
            'analyzer_serial'        => $payload['analyzer_serial'],
            'external_analyzer_id'   => $payload['analyzer_id'] ?? null,
            'observed_at'            => $payload['observed_at'] ?? null,
            'equipment_id'           => $equipment->id,
            'test_parameter_id'     => $testParam->id,
            'lab_request_id'        => $labRequest->id,
            'test_result_id'        => $testResult->id,
            'is_current'            => true,
            'received_from_lis_at'   => now(),
        ]);

        // 9. Dispatch async job
        ProcessLisPatientResult::dispatch($testResult->id, $translation->id);

        return new IngestResult(
            status: $dedup->status === 'SUPERSEDED' ? 'SUPERSEDED' : 'CREATED',
            test_result_id: $testResult->id,
            warnings: []
        );
    });
}
```

**Acceptance criteria:**

- First POST with `result_id=9001` → `CREATED`, translation row + result row in DB, job dispatched.
- Second POST with same `result_id=9001` (same `run_number`) → `DUPLICATE`, no DB write, `204` returned.
- Second POST with `result_id=9001, run_number=2` → `SUPERSEDED`, old translation `is_current=false`, new rows inserted.
- POST with unknown `analyzer_serial` → `UNMAPPED`, log row with `http_status=422`.
- Concurrent POSTs with same `result_id` → only one wins (`lockForUpdate()`).
- Every branch writes one row to `lis_ingest_log`.

**Effort:** L. **Depends on:** Phase 5.

---

## 12. Phase 7 — Controller

**File to create:** [`backend/app/Http/Controllers/Api/LisBridgeReceiverController.php`](backend/app/Http/Controllers/Api/LisBridgeReceiverController.php)

**Methods:**

```php
public function ingestPatient(StoreLisPatientResultRequest $request): Response {
    $started = microtime(true);
    try {
        $apiKey    = $request->attributes->get('lis_bridge_api_key');
        $bridgeCfg = $apiKey->bridgeConfig;
        $service   = app(LisResultIngestionService::class, [
            'apiKey' => $apiKey,
            'bridgeConfig' => $bridgeCfg,
        ]);
        $result = $service->ingestPatient($request->validated());
        return $this->respond($started, $request, $result, isQc: false);
    } catch (Throwable $e) {
        return $this->respondError($started, $request, $e);
    }
}

public function ingestQc(StoreLisQcResultRequest $request): Response {
    // Mirror of ingestPatient, calls $service->ingestQc()
}

public function testConnection(Request $request): Response {
    // No payload. Confirms API key + reachability.
    // Always returns 204 if middleware passed.
    return response()->noContent();
}
```

**Response rules:**

| Branch | Status | Body |
|--------|--------|------|
| `CREATED`/`SUPERSEDED` | `204` | No body |
| `DUPLICATE` | `204` | No body |
| `UNMAPPED` | `422` | `{"error":"unmapped","missing":["analyzer_serial"|"test_code"|"control_id"]}` |
| Validation failure | `422` | Laravel default |
| DB / queue down | `503` | `{"error":"transient"}` |
| Anything else | `500` | Logged to `lis_ingest_log` + `laravel.log` |

**Response time requirement:** All responses < 10s. Measured via `lis_ingest_log.duration_ms`. p95 target < 2s.

**Effort:** M. **Depends on:** Phase 6.

---

## 13. Phase 8 — Async Queue Jobs (2 Jobs)

**Files to create:**

- [`backend/app/Jobs/ProcessLisPatientResult.php`](backend/app/Jobs/ProcessLisPatientResult.php)
- [`backend/app/Jobs/ProcessLisQcResult.php`](backend/app/Jobs/ProcessLisQcResult.php)

**`ProcessLisPatientResult`:** `tries=3`, `backoff=[10,30,60]`, `timeout=120`. Loads the `TestResult`, then runs:

1. `DeltaCheckService::evaluate($result)` — may flag clinical significance
2. `ReflexTestService::evaluate($result)` — may insert new `LaboratoryRequestTest` rows (must be idempotent)
3. `CdsRuleEngine::evaluate($result)` — clinical decision support
4. `QualityControlLockoutService::evaluateLockout($result->equipment_id, $result->test_parameter_id)` — if applicable

**`ProcessLisQcResult`:** Same retry/timeout policy. Runs:

1. `SigmaMetricCalculator::recomputeFor($qcResult)` — sigma metrics per control level
2. Westgard rule evaluation (via existing `QcRule` engine)
3. `QualityControlLockoutService::evaluateLockout($qcResult->equipment_id, $qcResult->test_parameter_id)`

**Re-entrancy requirements:**

- All services must tolerate being called on a row that has already been processed. Use a `processed_at` timestamp that the service short-circuits on.
- Reflex test insertion must be idempotent — re-running must not create duplicate reflex orders.

**Acceptance criteria:**

- A job that fails its first attempt retries per the schedule.
- A job that succeeds completes within 60s p95 on a 2-vCPU worker.
- Re-entrant calls do not produce duplicate side effects.

**Effort:** L. **Depends on:** Phase 7.

---

## 14. Phase 9 — Admin UI (Optional)

**Files to create:**

- [`frontend/src/views/Admin/LisBridgeConfig.vue`](frontend/src/views/Admin/LisBridgeConfig.vue) — CRUD for `lis_bridge_configs`; API key rotation (generates 32-byte random token, stores sha256 hex, shows plaintext once).
- [`frontend/src/stores/lisBridgeConfig.js`](frontend/src/stores/lisBridgeConfig.js) — Pinia store.

**Files to modify:**

- [`frontend/src/router/index.js`](frontend/src/router/index.js) — add route under admin role guard.

**UI features:**

- List of `lis_bridge_configs` with active/inactive toggle.
- Edit: `name`, `lis_url`, `patient_lis_url`, `qc_lis_url`, `max_retries`, `poll_interval_sec`. **Note:** No `analyzer_id_map` — analyzer mapping is via `equipment.serial_no` (managed in Equipment management, not here).
- API keys panel per bridge: list (hash prefix last 8 chars for display), `label`, `is_active`, `expires_at`, `last_used_at`. **Rotate** action: generates `Str::random(48)`, stores `sha256()`, shows plaintext exactly once.
- Unmapped ingest log viewer: rows with `http_status=422` and `error_class=UnmappedException`.

**Acceptance criteria:**

- Admin user can create a bridge config and an API key.
- After rotation, old key returns 401 within next request.
- New key plaintext shown once is never retrievable again from the UI.

**Effort:** M. **Depends on:** Phases 1–3.

---

## 15. Phase 10 — Tests (10 Test Cases)

**Files to create** (under `backend/tests/`):

| # | File | Type |
|---|------|------|
| T1 | `Feature/Api/LisBridge/PatientResultIngestTest.php` | Feature |
| T2 | `Feature/Api/LisBridge/QcResultIngestTest.php` | Feature |
| T3 | `Feature/Api/LisBridge/AuthenticationTest.php` | Feature |
| T4 | `Feature/Api/LisBridge/IdempotencyTest.php` | Feature |
| T5 | `Feature/Api/LisBridge/SupersessionTest.php` | Feature |
| T6 | `Feature/Api/LisBridge/ResponseTimingTest.php` | Feature |
| T7 | `Feature/Api/LisBridge/TestConnectionTest.php` | Feature |
| T8 | `Feature/Api/LisBridge/WireFormatTest.php` | Feature |
| T9 | `Feature/Api/LisBridge/AnalyzerSerialLookupTest.php` | Feature |
| T10 | `Unit/Services/LisResultIngestionServiceTest.php` | Unit |

**Test case descriptions:**

| # | Test | Description |
|---|------|-------------|
| 1 | `PatientResultIngestTest::test_happy_path` | POST `/api/v1/patient-results` with canonical wire payload → `204`, translation row + result row in DB, job dispatched |
| 2 | `QcResultIngestTest::test_happy_path` | POST `/api/v1/qc-results` with QC payload → `204`, translation row + QC result row in DB, job dispatched |
| 3 | `AuthenticationTest::test_rejects_missing_key` | POST without `Authorization` header → `401` |
| 4 | `AuthenticationTest::test_rejects_invalid_key` | POST with `Authorization: Bearer invalid` → `401` |
| 5 | `IdempotencyTest::test_duplicate_returns_204` | POST same payload twice (same `result_id`, same `run_number`) → `204` ×2, 1 DB row |
| 6 | `SupersessionTest::test_higher_run_number_supersedes` | POST `result_id=9001, run_number=1` then `run_number=2` → 2 translation rows, old `is_current=false` |
| 7 | `WireFormatTest::test_requires_analyzer_serial` | Payload without `analyzer_serial` → `422` |
| 8 | `AnalyzerSerialLookupTest::test_maps_via_equipment_serial_no` | Equipment with matching `serial_no` resolves to correct `equipment_id` in translation |
| 9 | `ResponseTimingTest::test_returns_within_10_seconds` | Sync response returns < 10s even when downstream services are slow (mocked `sleep(8)`) |
| 10 | `LisResultIngestionServiceTest::test_unmapped_analyzer_returns_unmapped_status` | Unknown `analyzer_serial` → `UNMAPPED` IngestResult, 422 response |

**Acceptance criteria:**

- All 10 test cases green.
- Test suite runs in `php artisan test` with no warnings.
- Timing test uses `Http::fake()` or service mock — does NOT wait 10 real seconds.

**Effort:** L. **Depends on:** Phases 1–8.

---

## 16. Phase 11 — Documentation & Ops

**Files to modify:**

- [`backend/README.md`](backend/README.md) — add "LIS Bridge Receiver" section with setup instructions, env vars, and endpoint signatures.

**Files to create:**

- `docs/LIS_BRIDGE_RUNBOOK.md` — handling UNMAPPED rows, rotating API keys, replaying from `lis_ingest_log`.
- `docs/LIS_BRIDGE_LOG_ROTATION.md` — `lis_ingest_log` retention policy (recommended: keep 90 days hot, archive to cold storage).
- `docs/LIS_BRIDGE_ALERTS.md` — alert thresholds (e.g. `lis_ingest_log` 5xx rate > 1% over 5 min, dedup-rate > 10%, queue depth > 1000, unmapped rate > 5%).

**Acceptance criteria:**

- README section copy-paste-able by a new developer.
- Runbook covers: where to find UNMAPPED rows, how to add `equipment.serial_no` mapping, how to rotate keys, how to replay a payload.

**Effort:** S. **Depends on:** Phases 1–10.

---

## 17. Effort Summary Table

| Phase | Title | Effort | Dependencies | Deliverable |
|-------|-------|--------|--------------|-------------|
| 0 | Receiver Contract Spec | M | — | `docs/LIS_BRIDGE_RECEIVER_CONTRACT.md` signed by both teams |
| 1 | Database Schema | M | 0 | 3 migration files, runnable forward + rollback |
| 2 | Models | S | 1 | 4 new models (no model changes to TestResult/QualityControlResult) |
| 3 | Authentication Middleware | S | 2 | `VerifyLisBridgeApiKey` registered in `bootstrap/app.php` |
| 4 | Routes | S | 3 | 3 routes in `api.php` |
| 5 | Form Request Validation | S | 4 | 2 FormRequest classes with `analyzer_serial` (required) |
| 6 | Ingestion Service | L | 5 | `LisResultIngestionService` with translation-table dedup + single-transaction flow |
| 7 | Controller | M | 6 | `LisBridgeReceiverController` with 3 methods |
| 8 | Async Queue Jobs | L | 7 | 2 jobs + service re-entrancy hardening |
| 9 | Admin UI (Optional) | M | 1–3 | `LisBridgeConfig.vue` + Pinia store + router entry |
| 10 | Tests | L | 1–8 | 10 test files, all green |
| 11 | Documentation & Ops | S | 1–10 | README section, runbook, log-rotation, alerts |

**Total required path (Phases 0–8 + 10):** ≈ 4 person-weeks.  
**With optional phases (9 + 11):** ≈ 5 person-weeks.

---

## 18. File-by-File Change Manifest

### 21 New Files

| # | Path | Purpose |
|---|------|---------|
| 1 | `docs/LIS_BRIDGE_RECEIVER_CONTRACT.md` | Joint contract spec (Phase 0) |
| 2 | `backend/database/migrations/2026_09_XX_create_lis_bridge_infrastructure.php` | 4 new tables (Phase 1) |
| 3 | `backend/database/migrations/2026_09_XX_widen_quality_control_result_value.php` | `result_value` type fix (Phase 1) |
| 4 | `backend/database/migrations/2026_09_XX_ensure_equipment_serial_no_unique.php` | UNIQUE NOT NULL on `equipment.serial_no` (Phase 1) |
| 5 | `backend/app/Models/LisBridgeConfig.php` | Eloquent model (Phase 2) |
| 6 | `backend/app/Models/LisBridgeApiKey.php` | Eloquent model with `findByPlaintextKey()` (Phase 2) |
| 7 | `backend/app/Models/LisBridgeTranslation.php` | Translation table model (Phase 2) |
| 8 | `backend/app/Models/LisIngestLog.php` | Ingest log model (Phase 2) |
| 9 | `backend/app/Http/Middleware/VerifyLisBridgeApiKey.php` | Custom auth middleware (Phase 3) |
| 10 | `backend/app/Http/Requests/Api/StoreLisPatientResultRequest.php` | Patient validation (Phase 5) |
| 11 | `backend/app/Http/Requests/Api/StoreLisQcResultRequest.php` | QC validation (Phase 5) |
| 12 | `backend/app/Services/LisResultIngestionService.php` | Core ingestion logic with translation-table approach (Phase 6) |
| 13 | `backend/app/Http/Controllers/Api/LisBridgeReceiverController.php` | Receiver controller (Phase 7) |
| 14 | `backend/app/Jobs/ProcessLisPatientResult.php` | Patient async job (Phase 8) |
| 15 | `backend/app/Jobs/ProcessLisQcResult.php` | QC async job (Phase 8) |
| 16 | `frontend/src/views/Admin/LisBridgeConfig.vue` | Admin UI (Phase 9) |
| 17 | `frontend/src/stores/lisBridgeConfig.js` | Pinia store (Phase 9) |
| 18 | `docs/LIS_BRIDGE_RUNBOOK.md` | Ops runbook (Phase 11) |
| 19 | `docs/LIS_BRIDGE_LOG_ROTATION.md` | Log retention policy (Phase 11) |
| 20 | `docs/LIS_BRIDGE_ALERTS.md` | Alert thresholds (Phase 11) |
| 21 | `backend/app/DTOs/IngestResult.php` | Result DTO shared by ingestion service |

### 8 Test Files

| # | Path |
|---|------|
| T1 | `backend/tests/Feature/Api/LisBridge/PatientResultIngestTest.php` |
| T2 | `backend/tests/Feature/Api/LisBridge/QcResultIngestTest.php` |
| T3 | `backend/tests/Feature/Api/LisBridge/AuthenticationTest.php` |
| T4 | `backend/tests/Feature/Api/LisBridge/IdempotencyTest.php` |
| T5 | `backend/tests/Feature/Api/LisBridge/SupersessionTest.php` |
| T6 | `backend/tests/Feature/Api/LisBridge/ResponseTimingTest.php` |
| T7 | `backend/tests/Feature/Api/LisBridge/TestConnectionTest.php` |
| T8 | `backend/tests/Feature/Api/LisBridge/WireFormatTest.php` |
| T9 | `backend/tests/Feature/Api/LisBridge/AnalyzerSerialLookupTest.php` |
| T10 | `backend/tests/Unit/Services/LisResultIngestionServiceTest.php` |

### 7 Modified Files

| Path | Change |
|------|--------|
| [`backend/bootstrap/app.php`](backend/bootstrap/app.php) | Add `->withMiddleware(...)` to register `lis.bridge` alias |
| [`backend/routes/api.php`](backend/routes/api.php) | Add 3 routes outside Sanctum group |
| [`backend/app/Models/TestResult.php`](backend/app/Models/TestResult.php) | **NO CHANGES** — zero additive columns |
| [`backend/app/Models/QualityControlResult.php`](backend/app/Models/QualityControlResult.php) | **NO CHANGES** — only column type fix via migration |
| [`frontend/src/router/index.js`](frontend/src/router/index.js) | Add admin route for LisBridgeConfig |
| [`backend/README.md`](backend/README.md) | Add "LIS Bridge Receiver" section |
| `backend/app/Services/{DeltaCheck,ReflexTest,CdsRuleEngine,SigmaMetric,QualityControlLockout}Service.php` | Make re-entrant for queued re-runs (Phase 8) |

**Total file changes:** 21 new + 10 test + 7 modified = **38 file changes**.

---

## 19. Acceptance Criteria for "Done"

The implementation is **complete** when **all** of the following are true:

1. **Wire format happy path end-to-end.** POST the canonical payload with `analyzer_serial` → `204`, translation row + result row in DB, job dispatched and completes.
2. **All 10 test cases green**, including the 10-second response-time bound test (sync response before 10s even when downstream services are slow).
3. **Async jobs complete within 60s p95**, measured via `queue_job_durations` log channel.
4. **Sync response time p95 < 2s** (well under the 10s forwarder timeout), measured via `lis_ingest_log.duration_ms`.
5. **Dedup via translation table.** Duplicate `result_id` with same `run_number` returns `204` without DB write. Higher `run_number` supersedes correctly.
6. **Analyzer lookup via serial.** `equipment.serial_no` is the primary lookup key. `lis_bridge_translation.analyzer_serial` is populated from the payload.
7. **`observed_at` only on translation table.** `test_results` and `quality_control_results` have no `observed_at` column.
8. **Admin UI can create and rotate API keys.** Old key returns 401; new key accepted.
9. **`POST /api/lis-bridge/test-connection` returns 204** within 100ms with valid API key, 401 otherwise.
10. **`lis_ingest_log` captures every request** with HTTP status, duration_ms, request body, response body, source IP, and bridge/key IDs.
11. **No plaintext API key** appears in `laravel.log`, the DB, or any HTTP response body.
12. **`php artisan migrate:fresh`** succeeds with all 3 new migrations. `equipment.serial_no` is UNIQUE NOT NULL after Migration 3.

---

## 20. Open Questions / Out-of-Scope

### Out of scope (explicitly NOT covered by this plan)

- **Full bidirectional ASTM / HL7 protocol support.** This plan covers the JSON wire format only.
- **Real-time WebSocket push** of results from Laravel to LIS. The forwarder pulls.
- **Bi-directional LIS polling** (e.g. Laravel-initiated sample order send). Today's flow is one-way.
- **Sample ID generation from the LIS side.** Samples are created by the analyzer/forwarder.
- **HL7 ORU^R01 message construction.** JSON wire format is the contract.
- **Multi-tenant key scoping** beyond the FK relationship. Each `lis_bridge_config` is one logical tenant in v1.
- **Audit-log retention beyond 90 days.** Hot retention is 90 days; cold archival is an Ops decision.
- **Backfill of historical analyzer data.** Out of scope; this plan covers forward-only ingestion.

---

## 21. Wire Format Delta for Middleware Team

**Requirement statement:**

The middleware team must add one new field to the JSON payload built by `build_json_payload()`. The receiver will accept the existing 14 fields plus the new `analyzer_serial` field. The existing `analyzer_id` field is retained for backward compatibility with existing forwarder deployments but is no longer required — the receiver prefers `analyzer_serial`. Implementations that have already deployed `analyzer_id` can continue sending it; new deployments should send `analyzer_serial` instead.

**Code-level diff for `build_json_payload()`:**

```python
# BEFORE (Wire Form lines 55–74):
def build_json_payload(self) -> dict:
    return {
        "message_type": "patient",
        "is_qc": False,
        "result_id": self.id,
        "analyzer_id": self.analyzer_id,  # <-- existing
        "patient_id": self.patient_id,
        "sample_id": self.sample_id,
        "control_id": None,
        "test_code": self.mapped_test_code,
        "value": self.value,
        "unit": self.unit,
        "result_status": self.status,
        "run_number": self.run_number,
        "observed_at": self.timestamp.isoformat() if self.timestamp else None,
    }

# AFTER (add analyzer_serial):
def build_json_payload(self) -> dict:
    return {
        "message_type": "patient",
        "is_qc": False,
        "result_id": self.id,
        "analyzer_id": self.analyzer_id,  # retained for backward compat
        "analyzer_serial": self.analyzer.serial_no,  # <-- NEW (required from middleware)
        "patient_id": self.patient_id,
        "sample_id": self.sample_id,
        "control_id": None,
        "test_code": self.mapped_test_code,
        "value": self.value,
        "unit": self.unit,
        "result_status": self.status,
        "run_number": self.run_number,
        "observed_at": self.timestamp.isoformat() if self.timestamp else None,
    }
```

**Validation rule (add to FormRequest):**

```php
'analyzer_serial' => 'required|string|max:128',
```

**Test case for the new payload:**

```python
def test_build_json_payload_includes_analyzer_serial(self):
    result = Result(analyzer=Analyzer(id=5, serial_no="SN-AUTO-001"))
    payload = result.build_json_payload()
    assert payload["analyzer_serial"] == "SN-AUTO-001"
    assert "analyzer_id" in payload  # backward compat still present
```

---

## 22. References

| Document | Path | Purpose |
|----------|------|---------|
| Wire format specification | [`LIS Bridge Forwarder — Wire Form.md`](LIS%20Bridge%20Forwarder%20%E2%80%94%20Wire%20Form.md) | Authoritative payload schema and field definitions |
| LIS system specification | [`LIS_SYSTEM_SPECIFICATION.md`](LIS_SYSTEM_SPECIFICATION.md) | High-level LIS architecture and design decisions |
| Limitations and feedback | [`LIMITATIONS_AND_FEEDBACK.md`](LIMITATIONS_AND_FEEDBACK.md) | Known system limitations and user feedback |
| Result management review | [`RESULT_MANAGEMENT_SYSTEM_REVIEW.md`](RESULT_MANAGEMENT_SYSTEM_REVIEW.md) | Result management system architecture |
| This document | `LIS_BRIDGE_RECEIVER_IMPLEMENTATION_PLAN.md` | Self-reference: implementation plan (this file) |
| System README | [`README.md`](README.md) | Project overview and setup instructions |
| Backend README | [`backend/README.md`](backend/README.md) | Backend-specific setup and operation |
