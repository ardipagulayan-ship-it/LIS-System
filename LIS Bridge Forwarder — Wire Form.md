LIS Bridge Forwarder — Wire Format Reference
A complete, line‑cited reference for implementing the LIS receiver. All information is extracted read‑only from the project source.

A. The HTTP Request
Method
POST — issued via httpx.AsyncClient.post(...) in send_to_lis().

URL Pattern
The URL is not hardcoded. It is selected at poll time from the active lis_bridge_config row (_process_batch()):

url = (
    cfg.qc_lis_url
    if result.is_qc
    else cfg.patient_lis_url
) or cfg.lis_url

Routing rules:

Result.is_qc	URL used
False	cfg.patient_lis_url else cfg.lis_url
True	cfg.qc_lis_url else cfg.lis_url
If neither a per‑type URL nor lis_url is configured for the type, the row is hard‑failed with sync_status='FAILED' and last_lis_error="No LIS URL configured for …" (forwarder:207‑220).

Headers
Built in send_to_lis():

headers = {"Content-Type": "application/json"}
if api_key:
    headers["Authorization"] = f"Bearer {api_key}"

Header	Value	Always sent?
Content-Type	application/json	Yes
Authorization	Bearer <cfg.api_key>	Only if api_key is set
api_key comes from the lis_bridge_config.api_key column (LisBridgeConfig.api_key). When unset, no Authorization header is sent.

Timeout
timeout=10.0 seconds (forwarder:117). Hard timeout; not retried per attempt — the attempt is treated as a network error and the retry loop kicks in.

Body
The full JSON body is built by build_json_payload(). Body length varies (~250–450 bytes typical, see worked example below). Sent with httpx json=payload, which serializes with json.dumps(payload) — keys ordered as Python dict literal order (insertion order).

B. JSON Payload Schema
Defined in build_json_payload().

Field	Type	Required	Source column	Notes
message_type	string	Yes	derived: "qc" if result.is_qc else "patient"	"patient" or "qc"
is_qc	boolean	Yes	Result.is_qc (bool(result.is_qc))	
result_id	integer	Yes	Result.id (PK)	
analyzer_id	integer	Yes	Result.analyzer_id	FK to analyzers.id
patient_id	string	Yes	Result.patient_id	For QC rows this is the sentinel string "QC" (set by result_persistence)
sample_id	string	Yes	Result.sample_id	
control_id	string | null	No	Result.control_id	Populated for QC; null for patient
test_code	string	Yes	Result.mapped_test_code	Already LIS‑mapped code
value	string	Yes	Result.raw_value	Sent as a string (raw text from analyzer)
unit	string | null	No	Result.unit	
result_status	string	Yes	Result.result_status	Default "F" (Final). Other valid: "P" Preliminary, "C" Corrected, "X" Cancelled
run_number	integer	Yes	Result.run_number	Increments on a same‑sample re‑run
observed_at	string | null	No	Result.timestamp	ISO‑8601 with +00:00, e.g. "2026-09-07T00:50:00+00:00". Null if timestamp is NULL
Example — patient result
{
  "message_type": "patient",
  "is_qc": false,
  "result_id": 4711,
  "analyzer_id": 3,
  "patient_id": "PATIENT456",
  "sample_id": "SAMPLE123",
  "control_id": null,
  "test_code": "HGB",
  "value": "14.2",
  "unit": "g/dL",
  "result_status": "F",
  "run_number": 1,
  "observed_at": "2026-09-07T00:50:00+00:00"
}

(Adapted from the documented example in docs/LIS_BRIDGE_DEPLOYMENT.md:270‑286.)

Example — QC result
{
  "message_type": "qc",
  "is_qc": true,
  "result_id": 4712,
  "analyzer_id": 3,
  "patient_id": "QC",
  "sample_id": "QC_NORMAL",
  "control_id": "QC_NORMAL",
  "test_code": "HGB",
  "value": "13.5",
  "unit": "g/dL",
  "result_status": "F",
  "run_number": 1,
  "observed_at": "2026-09-07T00:55:00+00:00"
}

C. Response Codes
Logic lives in send_to_lis() and the state machine in _process_batch().

Status range	Forwarder action
2xx — 200, 201, 202, 204	SUCCESS. result.sync_status = "SYNCED", last_lis_error = None, last_sync_at = now(). Loop exits.
4xx — 400, 401, 402, 403, 404, 405, 406, 407, 409, 410, 411, … (anything not 408/429)	HARD FAIL (no retry). err_msg = "HTTP {code}: {body[:500]}". The error message is stored; the outer loop compares result.lis_attempts >= cfg.max_retries and sets sync_status = "FAILED" (see note below).
408 Request Timeout	RETRY. Treated like 5xx/network error.
429 Too Many Requests	RETRY. Treated like 5xx/network error.
5xx — 500, 501, 502, 503, 504, …	RETRY.
Network / httpx.RequestError / httpx.HTTPError / any other exception	RETRY. err_msg = "network error: {exc}" or "unexpected error: {exc}".
How sync_status is decided
In _process_batch(), the send_to_lis() return value is reduced to two states:

ok=True → sync_status = "SYNCED", last_lis_error = None.
ok=False → increment lis_attempts. If lis_attempts >= cfg.max_retries then sync_status = "FAILED". Otherwise sync_status = "PENDING" (so the next poll cycle picks it up again).
Important nuance: "hard fail" in send_to_lis() only means "stop retrying the HTTP call this cycle"; the row stays at lis_attempts == 1 (≤ max_retries), so on the next poll_interval the batch query will re‑pick it and try again. Only after the loop accumulates max_retries failed attempts does the row become FAILED. So 400/401/… are retried up to max_retries times across poll cycles — only the inner per‑cycle retries are skipped.

If you want a 4xx to truly mark the row FAILED immediately, you have two options: (a) configure max_retries=1 (then one 4xx = FAILED), or (b) recognize 4xx and update the row externally. The forwarder itself does not distinguish hard‑fail from exhaustion on a single 4xx.

D. Retry Schedule
The inner retry loop is the send_to_lis() call for one result. The outer loop is the run_forwarder() poll cycle.

Inner loop (within a single send_to_lis() call)
See send_to_lis(). Sequence:

attempt = 1..max_retries
sleep_before_next_attempt_n+1 = min(initial_backoff * multiplier^(n-1), max_backoff)

A retry is triggered after attempt n when n < max_retries. Sleep is computed before sleeping:

sleep_for = min(backoff, max_backoff)
...
await asyncio.sleep(sleep_for)
backoff = min(backoff * backoff_multiplier, max_backoff)

So backoff starts at initial_backoff and is multiplied for the next iteration. Effective sleep before each retry:

Attempt that just failed	Sleep before the next attempt
1	min(initial_backoff, max_backoff)
2	min(initial_backoff * M, max_backoff)
3	min(initial_backoff * M², max_backoff)
n	min(initial_backoff * M^(n-1), max_backoff)
Defaults (from LisBridgeConfig)
max_retries       = 5
initial_backoff   = 1.0  s
max_backoff       = 300.0 s
backoff_multiplier= 2.0

Concrete schedule with defaults:

Attempt	Sleep before this attempt (s)	Cumulative (s)
1	0 (first try)	0
2	1	1
3	2	3
4	4	7
5	8	15
→ exhausted after 5 attempts → sync_status = "FAILED"		
(For M=2.0, max_backoff=300 doesn't kick in until attempt 10.)

If you change initial_backoff=2.0, M=3.0, max_backoff=60.0, max_retries=6:

Sleeps: 2, 6, 18, 54, 60 (capped), 60 → cumulative 2, 8, 26, 80, 140, 200 seconds.
Outer loop (poll cycle)
run_forwarder() sleeps cfg.poll_interval seconds between batches (default 5 s — LisBridgeConfig.poll_interval). On idle, it uses asyncio.wait_for(stop_event.wait(), timeout=poll_interval) so SIGTERM is responsive. After a busy batch it does not sleep — it immediately polls again. So with max_retries=5 and the default 1‑2‑4‑8 schedule, an unhealthy LIS hits FAILED in roughly:

5 (poll) + 1 + 5 (poll) + 2 + 5 + 4 + 5 + 8 ≈ 35 s

…worst‑case across cycles. After sync_status = FAILED the row is not auto‑retried; an operator must reset it (see SQL snippet in docs/LIS_BRIDGE_DEPLOYMENT.md:225‑231).

E. QC vs Patient Payload Differences
Only two payload fields differ between QC and patient results; the rest of the dict is structurally identical:

Field	Patient	QC
message_type	"patient"	"qc"
is_qc	false	true
patient_id	the real patient ID	sentinel string "QC"
control_id	typically null	the QC control identifier
URL selection also differs (Section A). All other fields — result_id, analyzer_id, sample_id, test_code, value, unit, result_status, run_number, observed_at — have identical types and meaning for both flows.

F. Worked Example
Given sample_id='000014', test_code='WBC', value='7.5', unit='10^3/uL', is_qc=False, patient_id='PATIENT014', analyzer_id=1, result_id=9001, run_number=1, result_status='F', timestamp='2026-09-07T00:50:00+00:00':

Routing
is_qc=False → URL = cfg.patient_lis_url (or cfg.lis_url if unset).
Headers: Content-Type: application/json, plus Authorization: Bearer <cfg.api_key> if configured.
Exact JSON body POSTed
{
  "message_type": "patient",
  "is_qc": false,
  "result_id": 9001,
  "analyzer_id": 1,
  "patient_id": "PATIENT014",
  "sample_id": "000014",
  "control_id": null,
  "test_code": "WBC",
  "value": "7.5",
  "unit": "10^3/uL",
  "result_status": "F",
  "run_number": 1,
  "observed_at": "2026-09-07T00:50:00+00:00"
}

(Fields appear in this exact order — that's the Python dict literal order from build_json_payload().)

Equivalent raw HTTP wire bytes
POST /api/v1/results HTTP/1.1
Host: <lis_host>
Content-Type: application/json
Authorization: Bearer <api_key>
Content-Length: 271

{"message_type":"patient","is_qc":false,"result_id":9001,"analyzer_id":1,"patient_id":"PATIENT014","sample_id":"000014","control_id":null,"test_code":"WBC","value":"7.5","unit":"10^3/uL","result_status":"F","run_number":1,"observed_at":"2026-09-07T00:50:00+00:00"}

(Content-Length is computed by httpx from the serialized JSON; the exact byte count will depend on whether unit, control_id, etc. include multibyte UTF‑8 chars. The above assumes ASCII.)

G. Implementation Sketch for the LIS Receiver
Pseudocode for the LIS team's endpoint that the forwarder POSTs to:


Choosing your status code
Per Section C:

Receiver reply	What the forwarder does
200 / 201 / 202 / 204	Row → SYNCED, retries stop.
400, 403, 404, 409, 422, …	No inner retry this cycle; row stays PENDING and is picked up again next poll. After max_retries poll‑cycles of failures the row becomes FAILED.
408 / 429 / any 5xx	Inner retry kicks in (1s, 2s, 4s, 8s by default).
Network error / timeout	Same as 5xx — retried.
Recommendation: return 204 No Content (or 200 OK with body {"ack": true}) on success. Return 400 only when the payload is genuinely malformed — be aware this will be retried max_retries times before going to FAILED, so a fast 400 does not silently fix itself. Use 503 for transient outages so the forwarder back‑off applies.

Appendix 1 — Result model: columns included vs. excluded
From app/models/result.py:12‑51:

Column	In JSON payload?	Reason / Where it goes
id	✅ as result_id	
analyzer_id	✅	
sample_id	✅	
patient_id	✅	Sentinel "QC" for QC rows
mapped_test_code	✅ as test_code	Renamed in payload
raw_value	✅ as value	Renamed; kept as string
unit	✅	
timestamp	✅ as observed_at	Renamed; ISO‑8601 with +00:00
status	❌	Not in payload — internal "final" etc.
is_current	❌	Versioning flag, not transmitted
run_number	✅	
result_status	✅	ASTM/HL7 style ("F", "P", "C", "X")
extra_metadata	❌	Free‑form JSON dict, not transmitted
is_qc	✅	
control_id	✅	null for patient
control_level	❌	Model column but omitted from payload
sync_status	❌	Local state, never sent
lis_attempts	❌	Local retry counter
last_lis_error	❌	Local error log
last_sync_at	❌	Local timestamp
Action item for LIS team: control_level (e.g. "L", "N", "H") is stored on the row but is not sent today. If you need it, ask the backend team to add it to build_json_payload().

Appendix 2 — QC detection test asserts
From tests/test_yumizen_qc_detection.py:152‑295:

The forwarder‑routing tests assert URL selection only (no payload shape assertion). The exact assertion is the URL picker:

def pick_url(cfg: LisBridgeConfig, is_qc: bool) -> Optional[str]:
    if is_qc:
        return cfg.qc_lis_url or cfg.lis_url
    return cfg.patient_lis_url or cfg.lis_url

Test cases:

# Both URLs configured
cfg = _make_cfg(patient_lis_url="https://lis.example.com/patient",
                qc_lis_url="https://lis.example.com/qc")
assert pick_url(cfg, is_qc=True)  == "https://lis.example.com/qc"
assert pick_url(cfg, is_qc=False) == "https://lis.example.com/patient"

# qc_lis_url missing -> falls back to lis_url
cfg = _make_cfg(patient_lis_url="https://lis.example.com/patient",
                qc_lis_url=None)
assert pick_url(cfg, is_qc=True)  == "https://lis.example.com/default"
assert pick_url(cfg, is_qc=False) == "https://lis.example.com/patient"

# Only lis_url -> both flows use it
cfg = _make_cfg(patient_lis_url=None, qc_lis_url=None)
assert pick_url(cfg, is_qc=True)  == "https://lis.example.com/default"
assert pick_url(cfg, is_qc=False) == "https://lis.example.com/default"

No test asserts the exact JSON shape (the test file is read‑only‑mirror style — it tests routing, not serialization). The authoritative payload definition is build_json_payload() plus the docs example at LIS_BRIDGE_DEPLOYMENT.md:270‑292.

Appendix 3 — test_lis_connection() (manager‑side ping)
From lis_bridge_manager.py:270‑301. Called by POST /api/lis-bridge/test-connection.

Method: POST
URL: config.lis_url (the single legacy URL — does not probe patient_lis_url or qc_lis_url)
Timeout: 10.0 s
Body: depends on lis_format
"hl7" → plain string body TEST
"json" (or anything else) → JSON string '{"test": true}'
Headers:
Content-Type: application/hl7 if lis_format == "hl7", else application/json
Authorization: Bearer <api_key> if api_key is set
Status codes that count as success (lis_bridge_manager.py:293‑295):
if response.status_code in (200, 201, 202, 204, 400, 401, 403):
    return True, f"Connection successful (HTTP {response.status_code})"
else:
    return False, f"LIS returned HTTP {response.status_code}"

So this is a reachability test — 2xx means the LIS accepted; 401/400/403 mean the LIS is up but rejected (still considered "reachable"); anything else means something is wrong.

Network errors are caught and reported as (False, "Connection failed: {err}").

Quick checklist for the LIS receiver
Accept POST with Content-Type: application/json.
Verify Authorization: Bearer <token> if your deployment uses api_key.
Parse the JSON body. Validate the 10 required fields listed in Section B.
Persist per message_type / is_qc.
Respond with 200/201/202/204 on success — anything else triggers retries (or, for 4xx other than 408/429, retried‑up‑to‑max_retries).
Return 408, 429, or any 5xx if you want the forwarder to back‑off and retry.
If you need control_level, request it be added to build_json_payload().

LIS Receiver API Contract — Exact Specification
This is the wire format your LIS team needs to implement. Cut-and-paste-ready.

HTTP Request
POST <lis_bridge_config.lis_url>  HTTP/1.1   (or patient_lis_url / qc_lis_url)
Host:                                <lis_host>
Content-Type:                        application/json
Authorization:                       Bearer <lis_bridge_config.api_key>     ← only if api_key is set
Content-Length:                      <auto by httpx>

{ ... JSON body ... }

Item	Value
Method	POST
URL	cfg.patient_lis_url (patient) or cfg.qc_lis_url (QC) or cfg.lis_url (fallback)
Body	application/json (always)
Auth	Bearer <token> (only if api_key is set)
Timeout	10 seconds
URL routing (which URL gets hit)
is_qc	URL used
false	patient_lis_url if set, else lis_url
true	qc_lis_url if set, else lis_url
If neither a per-type URL nor lis_url is configured, the row is immediately hard-failed with last_lis_error="No LIS URL configured for …" (no HTTP call is made).

JSON Payload Schema
This is the exact field set, in the order the forwarder sends them.

Patient result example
{
  "message_type": "patient",
  "is_qc": false,
  "result_id": 9001,
  "analyzer_id": 1,
  "patient_id": "PATIENT014",
  "sample_id": "000014",
  "control_id": null,
  "test_code": "WBC",
  "value": "7.5",
  "unit": "10^3/uL",
  "result_status": "F",
  "run_number": 1,
  "observed_at": "2026-09-07T00:50:00+00:00"
}

QC result example
{
  "message_type": "qc",
  "is_qc": true,
  "result_id": 9002,
  "analyzer_id": 1,
  "patient_id": "QC",
  "sample_id": "QC_NORMAL",
  "control_id": "QC_NORMAL",
  "test_code": "HGB",
  "value": "13.5",
  "unit": "g/dL",
  "result_status": "F",
  "run_number": 1,
  "observed_at": "2026-09-07T00:55:00+00:00"
}

Field reference
Field	Type	Required	Notes
message_type	string	✅	"patient" or "qc"
is_qc	bool	✅	
result_id	int	✅	DMW's internal Result.id (PK). Use this for idempotency / dedup on the LIS side.
analyzer_id	int	✅	FK to DMW analyzers.id
patient_id	string	✅	For QC: sentinel "QC"
sample_id	string	✅	Barcode / specimen ID
control_id	string | null	❌	For QC: the QC control name (e.g. "QC_NORMAL"). For patient: null
test_code	string	✅	Already LIS-mapped (system code)
value	string	✅	Always a string, even for numeric results. Don't try to parse as float — keep as-is.
unit	string | null	❌	
result_status	string	✅	ASTM/HL7 style: "F" Final, "P" Preliminary, "C" Corrected, "X" Cancelled
run_number	int	✅	1 for first run, 2+ for reruns/corrections. Increment only on same-sample re-run.
observed_at	string | null	❌	ISO-8601 with timezone, e.g. "2026-09-07T00:50:00+00:00". Null if DMW has no timestamp.
Important field-level notes for the LIS team:

value is always a string. Even for "7.5", "150", "NEG" — never a number. Parse to numeric on the LIS side if you need to.
patient_id = "QC" for QC results. Don't accidentally route these to a patient record. Use is_qc or message_type to dispatch.
run_number is the version key. If you receive two results for the same (analyzer_id, sample_id, test_code), the higher run_number supersedes the lower one. This is how DMW signals "this is a rerun/correction."
result_status="C" is a corrected result (DMW detected the analyzer re-emitted with a different value). It supersedes prior F results for the same (analyzer, sample, test, run_number).
Response Codes (what DMW expects)
Your response	What DMW does
200 OK	✅ Mark sync_status=SYNCED, stop retrying
201 Created	✅ Same
202 Accepted	✅ Same
204 No Content	✅ Same (recommended for cleanest contract)
400 Bad Request	🔁 Marked PENDING and re-tried up to max_retries (default 5), then FAILED
401 Unauthorized	🔁 Same — but be careful, your test_lis_connection says this is "reachable" so don't be surprised if you see lots of 401s
403 Forbidden	🔁 Same
404 Not Found	🔁 Same
408 Request Timeout	🔁 Inner retry with backoff (1s, 2s, 4s, 8s)
429 Too Many Requests	🔁 Inner retry with backoff
500 / 502 / 503 / 504	🔁 Inner retry with backoff
Network error / timeout	🔁 Inner retry with backoff
Practical recommendations for your endpoint
Return 200 or 204 on success. This is what DMW treats as SYNCED.
Return 4xx only when the payload is genuinely malformed (missing required field, bad JSON, bad auth). Be aware that DMW will retry up to 5 times before giving up, so don't be sloppy.
Return 503 (or 5xx in general) when you're temporarily down. This triggers DMW's exponential backoff retry, which is exactly what you want.
If you want to slow DMW down (e.g. you're under load), return 429 Too Many Requests. DMW will back off and retry.
Retry schedule (with default config)
max_retries=5, initial_backoff=1.0s, backoff_multiplier=2.0, max_backoff=300s:

Attempt	Sleep before this attempt (s)	Cumulative (s)
1	0 (first try)	0
2	1	1
3	2	3
4	4	7
5	8	15
→ exhausted	→ sync_status = FAILED	

Idempotency / Deduplication
The payload does not carry a UUID or message ID. Use the natural key:

result_id (DMW's Result.id PK) — stable per send attempt, changes if the row is re-armed (resets to 0 then increments again, but the row id is stable).
If you want true exactly-once delivery:

Store the last result_id you've successfully accepted.
On each request, check if result_id <= last_accepted. If so, return 200 without re-processing.
The DMW side guarantees it will not re-send a row that's SYNCED (the forwarder filters by sync_status='PENDING').
So a 200 response is the dedup boundary: DMW marks the row SYNCED on the first 200, never sends it again.

A Worked Example: Complete Wire Transaction
Scenario: Yumizen H550e (analyzer_id=1) reports a WBC result for sample 000014.

DMW persists the result with sync_status='PENDING'.
Forwarder polls and picks it up.
Forwarder POSTs to your endpoint:
POST https://lis.your-hospital.com/api/v1/patient-results HTTP/1.1
Host: lis.your-hospital.com
Content-Type: application/json
Authorization: Bearer S3cr3tT0k3nFr0mDmw

{"message_type":"patient","is_qc":false,"result_id":9001,"analyzer_id":1,"patient_id":"PATIENT014","sample_id":"000014","control_id":null,"test_code":"WBC","value":"7.5","unit":"10^3/uL","result_status":"F","run_number":1,"observed_at":"2026-09-07T00:50:00+00:00"}

Your endpoint validates, persists to your LIS, returns:
HTTP/1.1 204 No Content

DMW marks the row SYNCED. Done.
What the LIS Team Must Implement
A single HTTPS endpoint (or three: /patient-results, /qc-results, /results if you want to mirror the URL routing). Accepts the JSON above, returns 200/201/202/204 on success. Nothing else.

Summary for the LIS Team
Item	Value
Endpoint	One (or three) HTTPS POST endpoints
Path	Whatever you choose (e.g. /api/v1/results)
Method	POST
Content-Type	application/json
Auth	Bearer <token> if api_key is configured in DMW
Body	JSON, ~14 fields (10 required, 4 optional)
Success response	Any 2xx (200, 201, 202, 204)
Failure response	4xx for malformed; 5xx or 429 for transient (DMW retries)
Timeout	10 seconds per attempt
Idempotency	Use result_id as the dedup key. A 2xx response marks the row as sent; DMW won't resend.
Rerun detection	Higher run_number supersedes lower. result_status="C" signals a correction.

