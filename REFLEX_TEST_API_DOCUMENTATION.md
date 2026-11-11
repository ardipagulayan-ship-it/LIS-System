# Reflex Test Workflow - API Documentation

## Table of Contents
1. [Introduction](#1-introduction)
2. [Endpoints Reference](#2-endpoints-reference)
3. [Data Models](#3-data-models)
4. [Error Handling](#4-error-handling)
5. [Rate Limiting](#5-rate-limiting)
6. [Examples](#6-examples)
7. [Frontend Integration Notes](#7-frontend-integration-notes)
8. [Testing](#8-testing)

---

## 1. Introduction

### Overview
The Reflex Test Workflow is an automated laboratory testing system that triggers additional tests based on Clinical Decision Support (CDS) rules. When initial test results meet specific criteria, the system suggests reflex tests for laboratory staff approval or denial.

### Authentication
All endpoints require authentication via Laravel Sanctum. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {your_token}
```

### Base URL Format
```
https://your-domain.com/api
```

All reflex test endpoints are prefixed with `/api/results`.

---

## 2. Endpoints Reference

### 2.1 Check CDS Rules (Modified)

**Endpoint**
```
GET /api/results/check-cds/{laboratoryRequest}
```

**Description**
Checks all CDS correlation rules for a laboratory request and returns any triggered rules, including reflex test suggestions. This endpoint is used by the frontend to display CDS alerts to users.

**Authentication**
Required

**Headers**
```
Accept: application/json
Authorization: Bearer {token}
```

**Parameters**
- `laboratoryRequest` (path parameter, required) - ID of the laboratory request to check

**Response**

Success Response (200 OK):
```json
{
    "triggered_rules": [
        {
            "id": 456,
            "rule_name": "High Glucose Reflex",
            "alert_message": "Glucose level exceeds 200 mg/dL. Consider HbA1c test.",
            "action_type": "REFLEX_TEST",
            "is_blocking": true,
            "is_acknowledged": false,
            "trigger_test_id": 123,
            "reflex_test_id": 789
        }
    ],
    "has_blocking_rules": true,
    "is_acknowledged": false
}
```

**Response Fields**
- `triggered_rules[]` - Array of triggered CDS rules
  - `id` - Rule ID
  - `rule_name` - Name of the rule
  - `alert_message` - Message to display to user
  - `action_type` - Type of action (`REFLEX_TEST`, `STOP_AUTO_VERIFY`, etc.)
  - `is_blocking` - Whether the rule blocks further processing
  - `is_acknowledged` - Whether the rule has been previously acknowledged
  - `trigger_test_id` - ID of the test parameter that triggered the rule
  - `reflex_test_id` - ID of the test parameter to be used for reflex testing
- `has_blocking_rules` - Overall flag indicating if any blocking rules exist
- `is_acknowledged` - Whether all rules for this request have been acknowledged

**Notes**
- The endpoint evaluates all active CDS rules that match the test parameters in the request
- Rules with `action_type` of `REFLEX_TEST` will include `reflex_test_id`
- The `is_acknowledged` flag helps determine if alerts should be shown again

---

### 2.2 Approve Reflex Test

**Endpoint**
```
POST /api/results/laboratory-requests/{laboratoryRequest}/approve-reflex-test
```

**Description**
Approves a reflex test suggestion and creates one or more reflex laboratory requests based on the CDS rule configuration. This is the main endpoint for initiating reflex testing.

**Authentication**
Required

**Headers**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Parameters**
- `laboratoryRequest` (path parameter, required) - ID of the parent laboratory request

**Request Body (JSON)**
```json
{
    "rule_id": 456,
    "reflex_test_id": 789,
    "auto_create": false
}
```

**Required Fields**
- `rule_id` (integer) - ID of the CDS correlation rule that triggered the reflex
- `reflex_test_id` (integer) - ID of the test_parameter that triggered the reflex test

**Optional Fields**
- `auto_create` (boolean) - If `true`, skips approval check and creates reflex automatically (for automated workflows). Default: `false`

**Responses**

*Success Response (200 OK)*:
```json
{
    "message": "Reflex test(s) created successfully",
    "reflex_requests": [
        {
            "id": 101,
            "sample_id": "SAMPLE-2025-001",
            "patient_id": 1,
            "doctor_id": 2,
            "is_reflex": true,
            "reflex_parent_request_id": 100,
            "reflex_triggered_by_rule_id": 456,
            "priority_level": "STAT",
            "request_date": "2025-06-18T10:30:00.000000Z",
            "created_at": "2025-06-18T10:30:00.000000Z",
            "updated_at": "2025-06-18T10:30:00.000000Z"
        }
    ]
}
```

*Validation Error (400 Bad Request)*:
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "rule_id": [
            "The selected rule id is invalid."
        ],
        "reflex_test_id": [
            "The selected reflex test id is invalid."
        ]
    }
}
```

*Not Found (404 Not Found)*:
```json
{
    "message": "Rule not found"
}
```

*Server Error (500 Internal Server Error)*:
```json
{
    "message": "Failed to create reflex test: Error details here"
}
```

**Notes**
- The endpoint creates reflex requests grouped by test category
- Each reflex request includes all tests in the same category as the reflex parameter
- A `RuleDecisionAudit` record is created with decision `reflex_created`
- The operation is wrapped in a database transaction for data integrity
- If a reflex already exists for this rule/parent request combination, no new requests are created (returns empty array)

---

### 2.3 Deny Reflex Test

**Endpoint**
```
POST /api/results/laboratory-requests/{laboratoryRequest}/deny-reflex-test
```

**Description**
Denies a reflex test suggestion and logs the decision with an optional reason. This prevents the reflex test from being automatically created.

**Authentication**
Required

**Headers**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Parameters**
- `laboratoryRequest` (path parameter, required) - ID of the parent laboratory request

**Request Body (JSON)**
```json
{
    "rule_id": 456,
    "reason": "Clinically not indicated based on patient history"
}
```

**Required Fields**
- `rule_id` (integer) - ID of the CDS correlation rule being denied

**Optional Fields**
- `reason` (string, max 500 characters) - Reason for denying the reflex test

**Responses**

*Success Response (200 OK)*:
```json
{
    "message": "Reflex test denied"
}
```

*Validation Error (400 Bad Request)*:
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "rule_id": [
            "The selected rule id is invalid."
        ],
        "reason": [
            "The reason may not be greater than 500 characters."
        ]
    }
}
```

*Server Error (500 Internal Server Error)*:
```json
{
    "message": "Failed to deny reflex test: Error details here"
}
```

**Notes**
- A `RuleDecisionAudit` record is created with decision `reflex_denied`
- The denial is logged for audit trail and reporting purposes
- Denial does not prevent future reflex suggestions if conditions are met again

---

### 2.4 Get Reflex Requests (Optional)

**Endpoint**
```
GET /api/laboratory-requests?reflex_parent_id={id}
```

**Description**
Filters laboratory requests to find all reflex requests for a specific parent request. This is a standard list endpoint with optional filtering.

**Authentication**
Required

**Headers**
```
Accept: application/json
Authorization: Bearer {token}
```

**Parameters**
- `reflex_parent_id` (query parameter, optional) - Parent laboratory request ID to filter by
- Standard pagination parameters: `page`, `per_page`

**Response**
Paginated list of laboratory requests with `is_reflex=true` matching the parent ID.

**Example Response**:
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 101,
            "sample_id": "SAMPLE-2025-001",
            "is_reflex": true,
            "reflex_parent_request_id": 100,
            "reflex_triggered_by_rule_id": 456,
            "patient_id": 1,
            "priority_level": "STAT",
            "request_date": "2025-06-18T10:30:00.000000Z"
        }
    ],
    "total": 1,
    "per_page": 15,
    "last_page": 1
}
```

**Notes**
- This endpoint uses the standard laboratory request list with filtering
- Only returns requests where `is_reflex = true` when `reflex_parent_id` is provided
- Can be combined with other filters (date range, status, etc.)

---

## 3. Data Models

### 3.1 LaboratoryRequest (Reflex Fields)

The `LaboratoryRequest` model includes the following reflex-specific fields:

```json
{
    "id": 101,
    "sample_id": "SAMPLE-2025-001",
    "patient_id": 1,
    "doctor_id": 2,
    "ward_id": null,
    "room_id": null,
    "priority_level": "STAT",
    "patient_type_id": 1,
    "request_date": "2025-06-18T10:30:00.000000Z",
    "is_reflex": true,
    "reflex_parent_request_id": 100,
    "reflex_triggered_by_rule_id": 456,
    "created_at": "2025-06-18T10:30:00.000000Z",
    "updated_at": "2025-06-18T10:30:00.000000Z"
}
```

**Reflex-Specific Fields**:
- `is_reflex` (boolean) - Indicates this is a reflex test request
- `reflex_parent_request_id` (integer, nullable) - ID of the parent laboratory request that triggered this reflex
- `reflex_triggered_by_rule_id` (integer, nullable) - ID of the CDS rule that caused this reflex to be created

**Relationships**:
- `belongsTo(Patient::class)`
- `belongsTo(Doctor::class)`
- `belongsToMany(Test::class, 'laboratory_request_test')` - with pivot `LaboratoryRequestTest`
- `hasMany(TestResult::class)`
- `hasOne(Payment::class)`

---

### 3.2 LaboratoryRequestTest (Reflex Fields)

The pivot model `LaboratoryRequestTest` includes:

```json
{
    "id": 201,
    "laboratory_request_id": 101,
    "test_id": 50,
    "item_status": "pending",
    "price_at_request": 150.00,
    "rejection_reason": null,
    "collected_at": null,
    "accepted_at": null,
    "rejected_at": null,
    "remarks": null,
    "comment": null,
    "checkin_by": null,
    "checkin_at": null,
    "sendout_by": null,
    "sendout_at": null,
    "park_by": null,
    "park_at": null,
    "perform_by": null,
    "perform_at": null,
    "verify_by": null,
    "verify_at": null,
    "pathologist_id": null,
    "is_reflex_test": true,
    "reflex_triggered_at": "2025-06-18T10:30:00.000000Z",
    "reflex_triggered_by_rule_id": 456,
    "created_at": "2025-06-18T10:30:00.000000Z",
    "updated_at": "2025-06-18T10:30:00.000000Z"
}
```

**Reflex-Specific Fields**:
- `is_reflex_test` (boolean) - Indicates this test entry is part of a reflex workflow
- `reflex_triggered_at` (datetime) - Timestamp when the reflex was triggered
- `reflex_triggered_by_rule_id` (integer, nullable) - ID of the CDS rule that triggered this test

---

### 3.3 RuleDecisionAudit (Reflex Decisions)

The `RuleDecisionAudit` model tracks all rule decisions including reflex approvals/denials:

```json
{
    "id": 301,
    "rule_type": "CDS",
    "decision": "reflex_created",
    "message": "Reflex test approved. Created 1 request(s).",
    "rule_id": 456,
    "policy": null,
    "laboratory_request_id": 100,
    "test_result_id": null,
    "test_parameter_id": null,
    "test_id": null,
    "user_id": 5,
    "supervisor_id": null,
    "context": {
        "reflex_requests": [101],
        "reflex_test_id": 789,
        "auto_created": false
    },
    "created_at": "2025-06-18T10:30:00.000000Z",
    "updated_at": "2025-06-18T10:30:00.000000Z"
}
```

**Key Fields**:
- `rule_type` - Always `"CDS"` for reflex workflows
- `decision` - Either `"reflex_created"` or `"reflex_denied"`
- `rule_id` - ID of the CDS correlation rule
- `laboratory_request_id` - Parent request ID
- `user_id` - User who made the decision
- `context` (JSON) - Additional context:
  - For `reflex_created`: `reflex_requests[]` (array of created request IDs), `reflex_test_id`, `auto_created`
  - For `reflex_denied`: `denied_at`

---

### 3.4 CdsCorrelationRule (Relevant Fields)

```json
{
    "id": 456,
    "rule_name": "High Glucose Reflex",
    "description": "Triggers HbA1c when glucose > 200",
    "rule_logic_json": {
        "operator": "AND",
        "conditions": [
            {
                "test_parameter_id": 123,
                "operator": ">",
                "value": 200
            }
        ]
    },
    "trigger_test_id": 123,
    "correlated_tests": "789,790",
    "reflex_test_id": 789,
    "action_type": "REFLEX_TEST",
    "alert_message": "Glucose level exceeds 200 mg/dL. Consider HbA1c test.",
    "is_active": true,
    "created_at": "2025-06-01T00:00:00.000000Z"
}
```

**Key Reflex Fields**:
- `trigger_test_id` - Test parameter that triggers the rule
- `reflex_test_id` - Test parameter to be used for reflex testing
- `correlated_tests` - Comma-separated list of additional test parameter IDs
- `action_type` - Must be `"REFLEX_TEST"` for reflex workflows
- `alert_message` - Message shown to users

---

## 4. Error Handling

### HTTP Status Codes

| Code | Description | When Used |
|------|-------------|-----------|
| 200 | Success | Reflex approved/denied successfully |
| 400 | Bad Request | Validation errors, missing required fields |
| 404 | Not Found | Rule ID doesn't exist, laboratory request not found |
| 500 | Internal Server Error | Database errors, service exceptions |

### Validation Error Format

All validation errors return a consistent format:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "Error message 1",
            "Error message 2"
        ]
    }
}
```

### Common Validation Rules

**Approve Reflex Test**:
- `rule_id`: required, integer, must exist in `cds_correlation_rules` table
- `reflex_test_id`: required, integer, must exist in `test_parameters` table
- `auto_create`: optional, boolean

**Deny Reflex Test**:
- `rule_id`: required, integer, must exist in `cds_correlation_rules` table
- `reason`: optional, string, max 500 characters

### Server Error Logging

All server errors (500) are logged with context:
- Parent request ID
- Rule ID
- User ID
- Full exception message and stack trace

Log entries include the `ReflexTestService` namespace for easy filtering.

---

## 5. Rate Limiting

Currently, no specific rate limits are applied to reflex test endpoints. The endpoints inherit the default Laravel Sanctum authentication middleware configuration.

**Recommendation**: For production deployments, consider implementing rate limiting:
- Approve/Deny endpoints: 60 requests per minute per user
- Check CDS endpoint: 120 requests per minute per user

Configuration can be added in `routes/api.php` using Laravel's `throttle` middleware.

---

## 6. Examples

### 6.1 Complete Workflow Example

#### Step 1: Check CDS Rules

When a user enters results for a test, the frontend calls the check endpoint:

```bash
curl -X GET "https://your-domain.com/api/results/check-cds/123" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci..."
```

Response:
```json
{
    "triggered_rules": [
        {
            "id": 456,
            "rule_name": "High Glucose Reflex",
            "alert_message": "Glucose level exceeds 200 mg/dL. Consider HbA1c test.",
            "action_type": "REFLEX_TEST",
            "is_blocking": true,
            "is_acknowledged": false,
            "trigger_test_id": 123,
            "reflex_test_id": 789
        }
    ],
    "has_blocking_rules": true,
    "is_acknowledged": false
}
```

#### Step 2: Approve Reflex Test

User clicks "Approve" in the UI:

```bash
curl -X POST "https://your-domain.com/api/results/laboratory-requests/123/approve-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci..." \
  -d '{
    "rule_id": 456,
    "reflex_test_id": 789,
    "auto_create": false
  }'
```

Response:
```json
{
    "message": "Reflex test(s) created successfully",
    "reflex_requests": [
        {
            "id": 101,
            "sample_id": "SAMPLE-2025-001",
            "is_reflex": true,
            "reflex_parent_request_id": 123,
            "reflex_triggered_by_rule_id": 456,
            "priority_level": "STAT",
            "request_date": "2025-06-18T10:30:00.000000Z"
        }
    ]
}
```

#### Step 3: Verify Reflex Request in Worklist

The newly created reflex request appears in the laboratory worklist with `is_reflex=true` and can be processed normally.

### 6.2 cURL Examples

#### Check CDS Rules
```bash
curl -X GET "https://your-domain.com/api/results/check-cds/{laboratoryRequestId}" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

#### Approve Reflex Test
```bash
curl -X POST "https://your-domain.com/api/results/laboratory-requests/{laboratoryRequestId}/approve-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": {ruleId},
    "reflex_test_id": {reflexTestParameterId},
    "auto_create": false
  }'
```

#### Deny Reflex Test
```bash
curl -X POST "https://your-domain.com/api/results/laboratory-requests/{laboratoryRequestId}/deny-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": {ruleId},
    "reason": "Not clinically indicated based on patient history"
  }'
```

#### Get Reflex Requests for Parent
```bash
curl -X GET "https://your-domain.com/api/laboratory-requests?reflex_parent_id={parentRequestId}&per_page=20" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

---

## 7. Frontend Integration Notes

### UniversalResultModal.vue Integration

The `UniversalResultModal.vue` component should:

1. **On result entry/save**: Automatically call `GET /api/results/check-cds/{id}` after results are saved
2. **Display alerts**: Show a modal or notification when `triggered_rules` is non-empty
3. **Handle REFLEX_TEST action**:
   - Display alert message with "Approve" and "Deny" buttons
   - On "Approve": Call `POST /approve-reflex-test` with `rule_id` and `reflex_test_id`
   - On "Deny": Call `POST /deny-reflex-test` with `rule_id` and optional reason
4. **Track acknowledged state**: Store `is_acknowledged` to avoid showing duplicate alerts
5. **Handle blocking rules**: Prevent auto-verify/print if `has_blocking_rules=true` and not acknowledged

**Example Integration Code**:
```javascript
// After saving results
const checkCdsRules = async (requestId) => {
  const response = await axios.get(`/api/results/check-cds/${requestId}`);
  if (response.data.triggered_rules.length > 0) {
    showCdsAlerts(response.data.triggered_rules);
  }
};

// Approve reflex
const approveReflex = async (requestId, ruleId, reflexTestId) => {
  await axios.post(
    `/api/results/laboratory-requests/${requestId}/approve-reflex-test`,
    { rule_id: ruleId, reflex_test_id: reflexTestId, auto_create: false }
  );
  refreshWorklist();
};
```

### CategoryResults.vue Integration

The `CategoryResults.vue` component should:

1. **Display reflex status**: Add a visual indicator (badge/icon) for requests where `is_reflex=true`
2. **Show parent link**: Display parent request ID/sample ID for reflex requests
3. **Filter support**: Add filter option to show only reflex requests or only parent requests
4. **Worklist columns**: Include `reflex_triggered_by_rule_id` and `reflex_parent_request_id` in table if needed

**Example Display**:
```vue
<template>
  <tr v-for="request in requests" :key="request.id">
    <td>
      <span v-if="request.is_reflex" class="badge badge-reflex">
        Reflex
      </span>
      <span v-else>Parent</span>
    </td>
    <td>{{ request.sample_id }}</td>
    <td v-if="request.is_reflex">
      Parent: {{ request.reflex_parent_request_id }}
    </td>
  </tr>
</template>
```

### Event Handling

**Events to Emit**:
- `reflex-approved` - When user approves a reflex test
- `reflex-denied` - When user denies a reflex test
- `cds-alert-triggered` - When CDS rules are triggered

**State Management**:
- Store `is_acknowledged` flag per request to prevent repeated alerts
- Refresh worklist after reflex approval/denial to show new requests
- Update local cache/state with newly created reflex request IDs

---

## 8. Testing

For comprehensive testing procedures, refer to:

**[REFLEX_TEST_TESTING_GUIDE.md](./REFLEX_TEST_TESTING_GUIDE.md)**

### Quick Test Scenarios

1. **Happy Path**: Enter result that triggers CDS rule → Check endpoint returns rule → Approve → Verify reflex request created
2. **Denial Path**: Trigger rule → Deny with reason → Verify audit log entry created
3. **Duplicate Prevention**: Approve same rule twice → Second approval returns empty array (no duplicate)
4. **Validation**: Send invalid `rule_id` → Receive 400 error
5. **Auto-Create**: Set `auto_create=true` → Reflex created without prior approval check

---

## Appendix

### Migration Files
- `backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php`
- `backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php`

### Key Service Class
- `backend/app/Services/ReflexTestService.php` - Core business logic for reflex test creation

### Controller
- `backend/app/Http/Controllers/Api/ResultManagementController.php` - API endpoint implementations

### Routes
- Defined in `backend/routes/api.php` under the `results` prefix group

---

**Document Version**: 1.0
**Last Updated**: 2025-06-18
**Applicable System**: LIS (Laboratory Information System)