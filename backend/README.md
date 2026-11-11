<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## LIS Bridge Receiver

### Overview

The LIS Bridge Receiver exposes HTTP endpoints that accept test results and QC results forwarded from an external Python-based LIS middleware (the "bridge forwarder"). Results are validated, deduplicated, written to the internal database, and then passed to async queue jobs for downstream processing (delta checks, reflex tests, CDS, QC lockout).

**Architecture:** Python middleware (sender) → Laravel app (receiver) → async queue jobs

**Timeout constraint:** The forwarder drops connections after 10 seconds. The controller returns `2xx` synchronously; all heavy processing is deferred to queue jobs.

### Wire Format

All payloads are JSON with these fields:

| Field | Type | Required | Description |
|---|---|---|---|
| `message_type` | string | Yes | Always `"RESULT"` |
| `is_qc` | bool | Yes | `false` for patient results, `true` for QC |
| `result_id` | int | Yes | Wire-format result ID (idempotency key part 1) |
| `analyzer_serial` | string | Yes | Equipment `serial_no` — how the instrument is identified |
| `patient_id` | string | Yes | Patient external ID; `"QC"` for QC payloads |
| `sample_id` | string | Yes | Lab request `request_id` for patient; QC sample ID for QC |
| `control_id` | string | QC only | QC material identifier, e.g. `"QC_NORMAL"` |
| `test_code` | string | Yes | `test_parameters.code` — the LIS-mapped test code |
| `value` | string | Yes | Numeric or qualitative string (`">100"`, `"POS"`, `"NEG"`) |
| `unit` | string | Yes | Unit string, e.g. `"mg/dL"` |
| `result_status` | string | Yes | ASTM status: `F`=final, `P`=pending, `C`=completed, `X`=superseded |
| `run_number` | int | Yes | Re-run count. `1`=original, `2`+=rerun. Triggers supersession chain. |
| `observed_at` | string (ISO 8601) | Yes | Time result was produced on the instrument |

### Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/api/v1/patient-results` | Bearer token | Ingest patient test result |
| `POST` | `/api/v1/qc-results` | Bearer token | Ingest QC result |
| `POST` | `/api/lis-bridge/test-connection` | Bearer token | Reachability probe — returns bridge name and ID |

All endpoints require a **Bearer token** in the `Authorization` header.

### Authentication

API keys are stored as SHA-256 hashes in `lis_bridge_api_keys.key_hash`. The raw token is shown exactly once at creation time via the `LisBridgeConfigSeeder`:

```bash
php artisan db:seed --class=LisBridgeConfigSeeder
```

The seeder prints the raw token to the console and optionally writes it to `.env` as `LIS_BRIDGE_API_KEY`. 
LIS_BRIDGE_API_KEY=39efce44c44b2b906bedafc7e3f48525d521142a0ccfe00aa4c15fdd68555b81

**Token format:** 64-character hex string (32 random bytes, SHA-256 hashed at rest).
**Comparison:** Uses `hash_equals()` (constant-time) to prevent timing attacks.
**Expiry:** Optional. Set `expires_at` to null for non-expiring keys.

### Analyzer Identification

Analyzers are identified by their **`equipment.serial_no`** value — no numeric ID mapping is needed. The middleware must send the analyzer's `serial_no` as `analyzer_serial` in every payload.

Before ingesting results, ensure your `equipment` table has `serial_no` populated and constrained as `UNIQUE NOT NULL` (enforced by migration `2026_09_07_000003_ensure_equipment_serial_no_unique.php`).

### QC Control Routing

QC control materials are routed via the `control_id_map` JSON column on `lis_bridge_configs`. This maps wire-format `control_id` strings to local `quality_controls.id` integers.

```php
// Example control_id_map:
{
  "QC_NORMAL": 1,
  "QC_ABNORMAL": 2,
  "LEVEL1": 3,
  "LEVEL2": 4
}
```

Configure this per bridge via the `LisBridgeConfig` model or database editor.

### Dedup and Translation Table

Idempotency is handled by the **`lis_bridge_translation`** table. The unique key is `(bridge_id, wire_result_id)`. No `external_*` columns exist on the result tables.

- `run_number = 1`: New result inserted. If a prior result exists for the same (equipment, lab_request, test_parameter), that prior result is superseded (`status = 'superseded'`).
- `run_number > 1`: A rerun. The previous result for the same triple is superseded. The new result gets `parent_result_id` set to the superseded result.
- **Dedup:** If the exact `(bridge_id, wire_result_id)` pair already exists, the request returns `409 Conflict` without writing.

`observed_at` is stored only on `lis_bridge_translation`, not on result tables.

### Async Processing

After a successful ingestion, a queue job is dispatched immediately:

| Payload type | Queue job | Queue name |
|---|---|---|
| Patient result | `ProcessLisPatientResult` | `lis` |
| QC result | `ProcessLisQcResult` | `lis` |

**`ProcessLisPatientResult`** runs (in order, each non-cascading on failure):
1. Flag computation (L/l/H/h against reference range)
2. Delta check
3. Reflex test triggering
4. Clinical Decision Support (CDS) rule evaluation
5. QC lockout re-evaluation

**`ProcessLisQcResult`** runs:
1. Westgard rule evaluation (last 20 QC results per control/analyte)
2. Sigma metric computation
3. QC lockout re-evaluation

### Running the Queue Worker

```bash
php artisan queue:work --queue=lis,default
```

The `lis` queue should be processed continuously in production.

### Seeding a Bridge Config

```bash
# Seed a default bridge + API key (safe to re-run; updates existing config)
php artisan db:seed --class=LisBridgeConfigSeeder

# Output includes the raw API key — copy it immediately
```

### Running Tests

```bash
# All LIS Bridge tests
php artisan test --filter=LisBridge

# By category
php artisan test --filter=LisBridgeAuthenticationTest
php artisan test --filter=LisBridgeValidationTest
php artisan test --filter=LisBridgeIngestionTest
php artisan test --filter=LisBridgeModelsTest
```

### Failed Row Handling

Failed ingestion requests are logged to `lis_ingest_log` with the full request body and response. To retry a failed row:

1. Identify the failed row: `LisIngestLog::failed()->where('route', '/api/v1/patient-results')`
2. Re-submit the `request_body` as a new POST to the same endpoint with a valid Bearer token
3. Verify the translation table entry was created for the correct `(bridge_id, wire_result_id)` pair

For failed queue jobs, use Laravel's built-in failed job table:

```bash
php artisan queue:retry all
# or for a specific job:
php artisan queue:retry <job-id>
```

### Ingest Log Retention

`lis_ingest_log` is an append-only audit log. Set up a scheduled task to prune old entries:

```php
// app/Console/Kernel.php (example)
$schedule->command('model:prune', ['--model' => \App\Models\LisIngestLog::class])->daily();
```

Or manually delete logs older than N days:

```php
LisIngestLog::where('created_at', '<', now()->subDays(90))->delete();
```

### Alert Thresholds

Recommend monitoring / alerting on:

- **High 4xx rate** on `/api/v1/patient-results` or `/api/v1/qc-results` → misconfigured middleware or expired API key
- **High 5xx rate** → downstream service errors, database issues
- **Queue depth on `lis` queue > 100** → worker may be down or jobs are backing up
- **`lis_ingest_log.duration_ms` > 5000** on successful requests → possible database lock contention

### Middleware Changes Required

The Python LIS bridge forwarder must be updated to:

1. **Add `analyzer_serial` field** to the wire payload (use `equipment.serial_no` from the middleware's equipment registry)
2. Ensure all 14 wire-format fields are present in every payload
3. Handle HTTP `409 Conflict` as a normal "already transmitted" response (not a retry)
4. Handle HTTP `422` as a configuration error requiring investigation

### Files Reference

| File | Purpose |
|---|---|
| [`LisBridgeReceiverController.php`](app/Http/Controllers/Api/LisBridgeReceiverController.php) | HTTP endpoints |
| [`VerifyLisBridgeApiKey.php`](app/Http/Middleware/VerifyLisBridgeApiKey.php) | Bearer token authentication |
| [`StoreLisPatientResultRequest.php`](app/Http/Requests/Api/StoreLisPatientResultRequest.php) | Patient result validation |
| [`StoreLisQcResultRequest.php`](app/Http/Requests/Api/StoreLisQcResultRequest.php) | QC result validation |
| [`LisResultIngestionService.php`](app/Services/LisResultIngestionService.php) | Core ingestion logic, dedup, resolution |
| [`ProcessLisPatientResult.php`](app/Jobs/ProcessLisPatientResult.php) | Async downstream: flag, delta, reflex, CDS |
| [`ProcessLisQcResult.php`](app/Jobs/ProcessLisQcResult.php) | Async downstream: Westgard, sigma, lockout |
| [`LisBridgeConfig.php`](app/Models/LisBridgeConfig.php) | Bridge config model |
| [`LisBridgeApiKey.php`](app/Models/LisBridgeApiKey.php) | API key model |
| [`LisBridgeTranslation.php`](app/Models/LisBridgeTranslation.php) | Translation/dedup table model |
| [`LisIngestLog.php`](app/Models/LisIngestLog.php) | Audit log model |
| [`LisBridgeConfigSeeder.php`](database/seeders/LisBridgeConfigSeeder.php) | Seed bridge + API key |
| [`2026_09_07_000001_create_lis_bridge_infrastructure_tables.php`](database/migrations/2026_09_07_000001_create_lis_bridge_infrastructure_tables.php) | 4 infrastructure tables |
| [`2026_09_07_000002_widen_quality_control_result_value.php`](database/migrations/2026_09_07_000002_widen_quality_control_result_value.php) | `result_value` → varchar |
| [`2026_09_07_000003_ensure_equipment_serial_no_unique.php`](database/migrations/2026_09_07_000003_ensure_equipment_serial_no_unique.php) | `serial_no` NOT NULL UNIQUE |
