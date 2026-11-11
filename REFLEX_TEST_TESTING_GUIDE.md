# Reflex Test Workflow - Comprehensive Testing & Integration Guide

## Table of Contents
1. [Pre-Testing Setup](#1-pre-testing-setup)
2. [Database Migration Testing](#2-database-migration-testing)
3. [Backend Service Testing (ReflexTestService)](#3-backend-service-testing-reflextestservice)
4. [API Endpoint Testing](#4-api-endpoint-testing)
5. [Frontend Testing](#5-frontend-testing)
6. [Integration Testing](#6-integration-testing)
7. [Data Integrity Checks](#7-data-integrity-checks)
8. [Performance Testing](#8-performance-testing)
9. [User Acceptance Testing (UAT)](#9-user-acceptance-testing-uat)
10. [Rollback Testing](#10-rollback-testing)
11. [Troubleshooting](#troubleshooting)

---

## 1. Pre-Testing Setup

### 1.1 Environment Preparation

```bash
# 1. Navigate to backend directory
cd backend

# 2. Install dependencies (if not already installed)
composer install
npm install

# 3. Configure environment
cp .env.example .env
# Edit .env with database credentials and app settings

# 4. Generate application key
php artisan key:generate
```

### 1.2 Database Setup

```bash
# 1. Run all migrations
php artisan migrate

# 2. Seed essential data (if needed)
php artisan db:seed --class=TestCategorySeeder
php artisan db:seed --class=TestParameterSeeder
php artisan db:seed --class=PatientTypeSeeder
php artisan db:seed --class=WardSeeder
php artisan db:seed --class=RoomSeeder
php artisan db:seed --class=DoctorSeeder
```

### 1.3 Verify Reflex Columns Exist

```sql
-- Check laboratory_requests table
DESCRIBE laboratory_requests;

-- Expected new columns:
-- - is_reflex (TINYINT(1), default: 0)
-- - reflex_parent_request_id (BIGINT, unsigned, nullable)
-- - reflex_triggered_by_rule_id (BIGINT, unsigned, nullable)

-- Check laboratory_request_test table
DESCRIBE laboratory_request_test;

-- Expected new columns:
-- - is_reflex_test (TINYINT(1), default: 0)
-- - reflex_triggered_at (TIMESTAMP, nullable)
-- - reflex_triggered_by_rule_id (BIGINT, unsigned, nullable)
```

### 1.4 Verify Foreign Key Constraints

```sql
-- Check foreign keys in laboratory_requests
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'laboratory_requests'
    AND COLUMN_NAME IN ('reflex_parent_request_id', 'reflex_triggered_by_rule_id');

-- Check foreign keys in laboratory_request_test
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'laboratory_request_test'
    AND COLUMN_NAME = 'reflex_triggered_by_rule_id';
```

### 1.5 Create Test Data

#### Step 1: Create Required Reference Data

```sql
-- Ensure these records exist:

-- 1. Patient Type
INSERT INTO patient_types (name, description, created_at, updated_at)
VALUES ('Inpatient', 'Hospital admitted patient', NOW(), NOW())
ON DUPLICATE KEY UPDATE description=description;

-- 2. Ward (if using)
INSERT INTO wards (name, description, created_at, updated_at)
VALUES ('General Ward', 'General medical ward', NOW(), NOW())
ON DUPLICATE KEY UPDATE description=description;

-- 3. Room (if using)
INSERT INTO rooms (name, ward_id, capacity, created_at, updated_at)
SELECT 'Room 101', id, 4 FROM wards WHERE name = 'General Ward'
ON DUPLICATE KEY UPDATE capacity=capacity;

-- 4. Doctor
INSERT INTO doctors (name, specialization, email, phone, created_at, updated_at)
VALUES ('Dr. Test Physician', 'Internal Medicine', 'test@doctor.com', '1234567890', NOW(), NOW())
ON DUPLICATE KEY UPDATE specialization=specialization;
```

#### Step 2: Create Test Patient

```sql
INSERT INTO patients (
    id, 
    last_name, 
    first_name, 
    middle_name, 
    date_of_birth, 
    gender, 
    civil_status, 
    address, 
    phone, 
    email, 
    created_at, 
    updated_at
) VALUES (
    'TEST001',
    'TestPatient',
    'John',
    'Doe',
    '1990-05-15',
    'Male',
    'Single',
    '123 Test Street',
    '9876543210',
    'test.patient@email.com',
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    first_name=VALUES(first_name),
    last_name=VALUES(last_name);
```

#### Step 3: Create Test Category and Tests

```sql
-- Create a test category (e.g., Chemistry)
INSERT INTO test_categories (name, description, display_order, created_at, updated_at)
VALUES ('Chemistry', 'Chemistry Tests', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE description=description;

-- Get the category ID
SET @category_id = (SELECT id FROM test_categories WHERE name = 'Chemistry');

-- Create a test
INSERT INTO tests (
    name, 
    test_category_id, 
    description, 
    normal_range, 
    unit, 
    created_at, 
    updated_at
) VALUES (
    'Glucose',
    @category_id,
    'Blood glucose measurement',
    '70-100',
    'mg/dL',
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    description=VALUES(description);

-- Get the test ID
SET @test_id = (SELECT id FROM tests WHERE name = 'Glucose' AND test_category_id = @category_id);

-- Create test parameters for the test
INSERT INTO test_parameters (
    test_id,
    name,
    normal_range,
    unit,
    display_order,
    created_at,
    updated_at
) VALUES
(@test_id, 'Glucose', '70-100', 'mg/dL', 1, NOW(), NOW()),
(@test_id, 'HbA1c', '4.0-5.6', '%', 2, NOW(), NOW());
```

#### Step 4: Create CDS Correlation Rule for Reflex Testing

```sql
-- Ensure CdsCorrelationRule records have reflex_test_id populated
INSERT INTO cds_correlation_rules (
    name,
    description,
    trigger_condition,
    alert_message,
    action_type,
    reflex_test_id,
    requires_approval,
    is_active,
    created_at,
    updated_at
) VALUES (
    'High Glucose Reflex',
    'When glucose is very high, reflex to HbA1c test',
    '{"parameter_name": "Glucose", "operator": ">", "value": 200}',
    'Glucose level is critically high. Recommend HbA1c test to assess long-term control.',
    'REFLEX_TEST',
    (SELECT id FROM test_parameters WHERE name = 'HbA1c' LIMIT 1),
    1, -- requires_approval
    1, -- is_active
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    alert_message=VALUES(alert_message),
    reflex_test_id=VALUES(reflex_test_id);
```

#### Step 5: Create Laboratory Request with Test

```sql
-- Get references
SET @patient_id = (SELECT id FROM patients WHERE last_name = 'TestPatient' LIMIT 1);
SET @doctor_id = (SELECT id FROM doctors WHERE name = 'Dr. Test Physician' LIMIT 1);
SET @patient_type_id = (SELECT id FROM patient_types WHERE name = 'Inpatient' LIMIT 1);
SET @ward_id = (SELECT id FROM wards WHERE name = 'General Ward' LIMIT 1);
SET @room_id = (SELECT id FROM rooms WHERE name = 'Room 101' LIMIT 1);
SET @test_id = (SELECT id FROM tests WHERE name = 'Glucose' LIMIT 1);

-- Create laboratory request
INSERT INTO laboratory_requests (
    patient_id,
    doctor_id,
    ward_id,
    room_id,
    patient_type_id,
    sample_id,
    priority_level,
    request_date,
    created_by,
    created_at,
    updated_at
) VALUES (
    @patient_id,
    @doctor_id,
    @ward_id,
    @room_id,
    @patient_type_id,
    CONCAT('SAMPLE-', DATE_FORMAT(NOW(), '%Y%m%d-%H%i%s')),
    'ROUTINE',
    NOW(),
    1, -- Assuming user ID 1 exists
    NOW(),
    NOW()
);

-- Get the request ID
SET @request_id = LAST_INSERT_ID();

-- Create laboratory request test entry
INSERT INTO laboratory_request_test (
    laboratory_request_id,
    test_id,
    item_status,
    created_at,
    updated_at
) VALUES (
    @request_id,
    @test_id,
    'pending',
    NOW(),
    NOW()
);
```

---

## 2. Database Migration Testing

### 2.1 Migration Execution Tests

#### Test Case 1: Fresh Migration
```bash
# Test command
php artisan migrate
```

**Expected Result:**
- Migrations run without errors
- Output shows: "Migrated: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table"
- Output shows: "Migrated: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table"

**Verification:**
```sql
-- Check columns exist
SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
SHOW COLUMNS FROM laboratory_requests LIKE 'reflex_parent_request_id';
SHOW COLUMNS FROM laboratory_requests LIKE 'reflex_triggered_by_rule_id';

SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
SHOW COLUMNS FROM laboratory_request_test LIKE 'reflex_triggered_at';
SHOW COLUMNS FROM laboratory_request_test LIKE 'reflex_triggered_by_rule_id';
```

#### Test Case 2: Column Data Types and Positions

```sql
-- Verify laboratory_requests columns
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'laboratory_requests'
    AND COLUMN_NAME IN ('is_reflex', 'reflex_parent_request_id', 'reflex_triggered_by_rule_id')
ORDER BY ORDINAL_POSITION;

-- Expected:
-- is_reflex: tinyint(1), NO, 0
-- reflex_parent_request_id: bigint, YES, NULL
-- reflex_triggered_by_rule_id: bigint, YES, NULL

-- Verify laboratory_request_test columns
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'laboratory_request_test'
    AND COLUMN_NAME IN ('is_reflex_test', 'reflex_triggered_at', 'reflex_triggered_by_rule_id');
```

#### Test Case 3: Foreign Key Constraints

```sql
-- Check laboratory_requests foreign keys
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
    AND rc.TABLE_NAME = 'laboratory_requests'
    AND kcu.COLUMN_NAME IN ('reflex_parent_request_id', 'reflex_triggered_by_rule_id');

-- Expected:
-- reflex_parent_request_id -> laboratory_requests.id (SET NULL)
-- reflex_triggered_by_rule_id -> cds_correlation_rules.id (SET NULL)

-- Check laboratory_request_test foreign key
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
    AND rc.TABLE_NAME = 'laboratory_request_test'
    AND kcu.COLUMN_NAME = 'reflex_triggered_by_rule_id';

-- Expected:
-- reflex_triggered_by_rule_id -> cds_correlation_rules.id (SET NULL)
```

#### Test Case 4: Rollback Testing

```bash
# Test rollback
php artisan migrate:rollback
```

**Expected Result:**
- Output shows: "Rolled back: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table"
- Output shows: "Rolled back: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table"

**Verification:**
```sql
-- Columns should no longer exist
SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
-- Should return empty set

SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
-- Should return empty set
```

```bash
# Re-apply migrations
php artisan migrate
```

---

## 3. Backend Service Testing (ReflexTestService)

### 3.1 Testing getTestsForReflexParameter()

#### Test Case 1.1: Parameter exists and test is standalone
```php
// Test setup
$parameterId = 1; // ID of a test parameter that belongs to a standalone test
$service = new ReflexTestService();
$result = $service->getTestsForReflexParameter($parameterId);

// Expected result:
// - Collection with 1 Test model
// - Test contains the parameter
// - Test does not include package tests
```

#### Test Case 1.2: Parameter belongs to a test that is part of a package
```php
// Test setup
$parameterId = 2; // ID of a parameter that belongs to a test in a package
$result = $service->getTestsForReflexParameter($parameterId);

// Expected result:
// - Collection with multiple Test models (all tests in the package)
// - Original test + all other tests in the same package
// - No duplicate tests (unique by ID)
```

#### Test Case 1.3: Parameter does not exist
```php
$parameterId = 999999; // Non-existent ID
$result = $service->getTestsForReflexParameter($parameterId);

// Expected result:
// - Empty collection
// - Warning logged: "Reflex parameter not found"
```

#### Test Case 1.4: Parameter exists but test not found
```php
// Edge case: parameter record exists but test relationship is broken
$parameterId = 123; // Parameter with test_id pointing to non-existent test
$result = $service->getTestsForReflexParameter($parameterId);

// Expected result:
// - Empty collection
// - Warning logged: "Test not found for parameter"
```

### 3.2 Testing groupTestsByCategory()

#### Test Case 2.1: Single test
```php
$tests = collect([$test1]); // Single test with test_category_id = 1
$result = $service->groupTestsByCategory($tests);

// Expected result:
// array(1) {
//   [1] => Collection([$test1])
// }
```

#### Test Case 2.2: Multiple tests in same category
```php
$tests = collect([$test1, $test2]); // Both with test_category_id = 1
$result = $service->groupTestsByCategory($tests);

// Expected result:
// array(1) {
//   [1] => Collection([$test1, $test2])
// }
```

#### Test Case 2.3: Multiple tests in different categories
```php
$tests = collect([$test1, $test2, $test3]); // test1, test2 in cat 1; test3 in cat 2
$result = $service->groupTestsByCategory($tests);

// Expected result:
// array(2) {
//   [1] => Collection([$test1, $test2])
//   [2] => Collection([$test3])
// }
```

#### Test Case 2.4: Test with null category
```php
$test = new Test(['test_category_id' => null]);
$tests = collect([$test]);
$result = $service->groupTestsByCategory($tests);

// Expected result:
// array(1) {
//   [null] => Collection([$test])
// }
```

### 3.3 Testing hasExistingReflex()

#### Test Case 3.1: No existing reflex
```php
$parentRequest = LaboratoryRequest::find(1);
$rule = CdsCorrelationRule::find(1);
$result = $service->hasExistingReflex($parentRequest, $rule);

// Expected result: false
```

#### Test Case 3.2: Reflex request exists
```php
// Setup: Create a reflex request
LaboratoryRequest::create([
    'patient_id' => $parentRequest->patient_id,
    'is_reflex' => true,
    'reflex_parent_request_id' => $parentRequest->id,
    'reflex_triggered_by_rule_id' => $rule->id,
    // ... other required fields
]);

$result = $service->hasExistingReflex($parentRequest, $rule);

// Expected result: true
```

#### Test Case 3.3: RuleDecisionAudit entry exists with reflex_created
```php
// Setup: No reflex request, but audit entry exists
RuleDecisionAudit::create([
    'rule_type' => 'cds_correlation',
    'decision' => 'reflex_created',
    'rule_id' => $rule->id,
    'laboratory_request_id' => $parentRequest->id,
    'user_id' => 1,
    'context' => []
]);

$result = $service->hasExistingReflex($parentRequest, $rule);

// Expected result: true
```

#### Test Case 3.4: RuleDecisionAudit entry exists with different decision
```php
// Setup: Audit entry with 'reflex_denied' decision
RuleDecisionAudit::create([
    'rule_type' => 'cds_correlation',
    'decision' => 'reflex_denied',
    'rule_id' => $rule->id,
    'laboratory_request_id' => $parentRequest->id,
    'user_id' => 1,
    'context' => []
]);

$result = $service->hasExistingReflex($parentRequest, $rule);

// Expected result: false (denied is not created)
```

### 3.4 Testing createSingleReflexRequest()

#### Test Case 4.1: Successful creation
```php
$parentRequest = LaboratoryRequest::find(1);
$test = Test::find(1);
$reflexParameter = TestParameter::find(1);
$rule = CdsCorrelationRule::find(1);
$user = User::find(1);

$result = $service->createSingleReflexRequest(
    $parentRequest, $test, $reflexParameter, $rule, $user
);

// Expected result:
// - LaboratoryRequest model returned
// - $result->is_reflex = true
// - $result->reflex_parent_request_id = $parentRequest->id
// - $result->reflex_triggered_by_rule_id = $rule->id
// - $result->patient_id = $parentRequest->patient_id
// - $result->doctor_id = $parentRequest->doctor_id
// - $result->ward_id = $parentRequest->ward_id
// - $result->room_id = $parentRequest->room_id
// - $result->patient_type_id = $parentRequest->patient_type_id
// - $result->priority_level = $parentRequest->priority_level
// - $result->sample_id is unique (not same as parent)
// - $result->created_by = $user->id
```

#### Test Case 4.2: Transaction rollback on failure
```php
// Simulate failure (e.g., database constraint violation)
// Mock or create scenario where LaboratoryRequest::create() throws exception

try {
    $result = $service->createSingleReflexRequest(...);
} catch (\Exception $e) {
    // Expected: Exception thrown
}

// Verify:
// - No LaboratoryRequest record created
// - Database transaction rolled back
// - Error logged: "Failed to create single reflex request"
```

### 3.5 Testing createReflexTests()

#### Test Case 5.1: Single category, auto_create = false
```php
$parentRequest = LaboratoryRequest::find(1);
$rule = CdsCorrelationRule::find(1); // reflex_test_id points to parameter in single category
$user = User::find(1);

$result = $service->createReflexTests($parentRequest, $rule, $user, false);

// Expected result:
// - Array of 1 LaboratoryRequest
// - Request has is_reflex = true
// - Request has correct reflex_parent_request_id and reflex_triggered_by_rule_id
// - LaboratoryRequestTest entries created for all tests in category
// - Each LaboratoryRequestTest has:
//   * is_reflex_test = true
//   * reflex_triggered_at = current timestamp
//   * reflex_triggered_by_rule_id = $rule->id
// - RuleDecisionAudit entry created with decision = 'reflex_created'
```

#### Test Case 5.2: Multiple categories (package test)
```php
// Setup: reflex_test_id points to parameter in a test that belongs to a package
// Package contains tests from Chemistry and Hematology categories
$parentRequest = LaboratoryRequest::find(1);
$rule = CdsCorrelationRule::find(2); // Points to package test parameter
$user = User::find(1);

$result = $service->createReflexTests($parentRequest, $rule, $user, false);

// Expected result:
// - Array of 2 LaboratoryRequest models (one per category)
// - First request: category = Chemistry, contains Chemistry tests from package
// - Second request: category = Hematology, contains Hematology tests from package
// - All requests have is_reflex = true
// - All requests link to same parent and rule
// - Each request has separate unique sample_id
// - RuleDecisionAudit entry created with context containing all request IDs
```

#### Test Case 5.3: Duplicate prevention
```php
// First call
$result1 = $service->createReflexTests($parentRequest, $rule, $user, false);
// Expected: Creates requests

// Second call (same parent + rule)
$result2 = $service->createReflexTests($parentRequest, $rule, $user, false);
// Expected: Returns empty array []
// Log: "Reflex test already exists"
```

#### Test Case 5.4: No tests found for parameter
```php
// Setup: rule.reflex_test_id points to non-existent parameter
$rule->reflex_test_id = 999999;
$result = $service->createReflexTests($parentRequest, $rule, $user, false);

// Expected result: empty array []
// Warning logged: "No tests found for reflex parameter"
```

#### Test Case 5.5: Auto-create flag
```php
$result = $service->createReflexTests($parentRequest, $rule, $user, true);

// Expected:
// - Same as normal creation
// - RuleDecisionAudit context includes 'auto_created' => true
```

---

## 4. API Endpoint Testing

### 4.1 CDS Check Endpoint

#### Endpoint: GET /api/laboratory-requests/{id}/check-cds-rules

**Test Case 4.1.1: Request with no CDS alerts**
```bash
curl -X GET "http://lis.test/api/results/check-cds/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Expected Response (200 OK):**
```json
{
  "alerts": [],
  "has_blocking_alerts": false
}
```

**Test Case 4.1.2: Request with CDS alert (non-reflex)**
```bash
curl -X GET "http://lis.test/api/results/check-cds/2" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Expected Response (200 OK):**
```json
{
  "alerts": [
    {
      "id": 1,
      "rule_id": 1,
      "rule_name": "High Glucose Warning",
      "alert_message": "Glucose level is elevated",
      "is_blocking": false,
      "action_type": "WARNING",
      "is_acknowledged": false
    }
  ],
  "has_blocking_alerts": false
}
```

**Test Case 4.1.3: Request with reflex-triggering CDS alert**
```bash
curl -X GET "http://lis.test/api/results/check-cds/3" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

**Expected Response (200 OK):**
```json
{
  "alerts": [
    {
      "id": 2,
      "rule_id": 1,
      "rule_name": "High Glucose Reflex",
      "alert_message": "Glucose level is critically high. Recommend HbA1c test.",
      "is_blocking": true,
      "action_type": "REFLEX_TEST",
      "reflex_test_id": 5,
      "requires_approval": true,
      "is_acknowledged": false,
      "reflex_created": false
    }
  ],
  "has_blocking_alerts": true
}
```

### 4.2 Approve Reflex Test Endpoint

#### Endpoint: POST /api/laboratory-requests/{id}/approve-reflex-test

**Test Case 4.2.1: Successful approval**
```bash
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": 1,
    "reflex_test_id": 5,
    "auto_create": false
  }'
```

**Expected Response (200 OK):**
```json
{
  "message": "Reflex test(s) created successfully",
  "reflex_requests": [
    {
      "id": 2,
      "sample_id": "uuid-generated-id",
      "patient_id": 1,
      "doctor_id": 1,
      "is_reflex": true,
      "reflex_parent_request_id": 1,
      "reflex_triggered_by_rule_id": 1,
      "priority_level": "ROUTINE",
      "created_at": "2025-06-18T10:30:00.000000Z"
    }
  ]
}
```

**Database Verification:**
```sql
-- Check reflex request created
SELECT * FROM laboratory_requests 
WHERE reflex_parent_request_id = 1 
  AND reflex_triggered_by_rule_id = 1;

-- Should return 1 row with is_reflex = 1

-- Check reflex test items
SELECT * FROM laboratory_request_test 
WHERE laboratory_request_id = 2 
  AND is_reflex_test = 1 
  AND reflex_triggered_by_rule_id = 1;

-- Should return all tests for the reflex parameter

-- Check audit entry
SELECT * FROM rule_decision_audit 
WHERE laboratory_request_id = 1 
  AND rule_id = 1 
  AND decision = 'reflex_created';

-- Should return 1 row
```

**Test Case 4.2.2: Invalid rule_id**
```bash
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": 999999,
    "reflex_test_id": 5
  }'
```

**Expected Response (404 Not Found):**
```json
{
  "message": "No query results for model [CdsCorrelationRule] 999999"
}
```

**Test Case 4.2.3: Missing required parameters**
```bash
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": 1
    // missing reflex_test_id
  }'
```

**Expected Response (422 Unprocessable Entity):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "reflex_test_id": [
      "The reflex test id field is required."
    ]
  }
}
```

**Test Case 4.2.4: Approve same reflex twice**
```bash
# First approval
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Authorization: Bearer {token}" \
  -d '{"rule_id": 1, "reflex_test_id": 5}'

# Second approval (same parent + rule)
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Authorization: Bearer {token}" \
  -d '{"rule_id": 1, "reflex_test_id": 5}'
```

**Expected Second Response (200 OK but no creation):**
```json
{
  "message": "Reflex test(s) created successfully",
  "reflex_requests": []
}
```

**Log Entry:**
```
Reflex test already exists, parent_request_id: 1, rule_id: 1
```

### 4.3 Deny Reflex Test Endpoint

#### Endpoint: POST /api/laboratory-requests/{id}/deny-reflex-test

**Test Case 4.3.1: Successful denial**
```bash
curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": 1,
    "reason": "Clinically not indicated"
  }'
```

**Expected Response (200 OK):**
```json
{
  "message": "Reflex test denied"
}
```

**Database Verification:**
```sql
-- Check audit entry
SELECT * FROM rule_decision_audit 
WHERE laboratory_request_id = 1 
  AND rule_id = 1 
  AND decision = 'reflex_denied';

-- Should return 1 row with reason in message or context
```

**Test Case 4.3.2: Denial without reason**
```bash
curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": 1
  }'
```

**Expected Response (200 OK):**
```json
{
  "message": "Reflex test denied"
}
```

**Audit Entry:**
```sql
-- message should be "Reflex test denied by user"
```

**Test Case 4.3.3: Invalid rule_id**
```bash
curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "rule_id": 999999
  }'
```

**Expected Response (404 Not Found):**

### 4.4 Edge Cases and Error Handling

**Test Case 4.4.1: Deny then approve**
```bash
# Step 1: Deny
curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
  -H "Authorization: Bearer {token}" \
  -d '{"rule_id": 1}'

# Step 2: Try to approve
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Authorization: Bearer {token}" \
  -d '{"rule_id": 1, "reflex_test_id": 5}'
```

**Expected:**
- Approval succeeds but returns empty array (hasExistingReflex returns true from audit)
- No new reflex requests created

**Test Case 4.4.2: Approve then deny**
```bash
# Step 1: Approve
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Authorization: Bearer {token}" \
  -d '{"rule_id": 1, "reflex_test_id": 5}'

# Step 2: Try to deny
curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
  -H "Authorization: Bearer {token}" \
  -d '{"rule_id": 1, "reason": "Second denial attempt"}'
```

**Expected:**
- Denial succeeds (audit entry created)
- But hasExistingReflex still returns true (reflex was created)
- Both audit entries exist: 'reflex_created' and 'reflex_denied'

**Test Case 4.4.3: Unauthorized access**
```bash
curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
  -H "Content-Type: application/json" \
  -d '{"rule_id": 1, "reflex_test_id": 5}'
  # No Authorization header
```

**Expected Response (401 Unauthorized)**

---

## 5. Frontend Testing

### 5.1 UniversalResultModal.vue

#### Test Case 5.1.1: Modal opens with reflex-triggering results
1. Open result modal for a request that has results triggering a CDS rule with REFLEX_TEST action
2. CDS check should automatically run
3. Reflex approval modal should appear automatically

**Expected Behavior:**
- Modal displays patient information correctly
- CDS banner shows with reflex alert
- Alert shows: rule name, reflex test parameter name, requires approval
- "Approve & Order" and "Deny" buttons appear

#### Test Case 5.1.2: Click "Approve & Order"
1. With reflex alert showing, click "Approve & Order" button
2. API call should be made to `/api/results/laboratory-requests/{id}/approve-reflex-test`

**Expected Behavior:**
- Success message appears: "Reflex test(s) created successfully"
- Modal closes
- Request details refresh (if viewing parent request, new reflex requests may appear in worklist)
- CDS alerts are cleared

**Network Request Verification:**
```javascript
// Request payload
{
  "rule_id": 1,
  "reflex_test_id": 5,
  "auto_create": false
}

// Response handling
{
  "message": "Reflex test(s) created successfully",
  "reflex_requests": [{ "id": 2, "sample_id": "..." }]
}
```

#### Test Case 5.1.3: Click "Deny"
1. With reflex alert showing, click "Deny" button
2. Confirmation dialog should appear
3. Confirm denial
4. API call to `/api/results/laboratory-requests/{id}/deny-reflex-test`

**Expected Behavior:**
- Info message appears: "Reflex test denied"
- Modal closes
- CDS alerts are re-checked
- Alert should no longer appear (or show as denied)

**Network Request Verification:**
```javascript
// Request payload
{
  "rule_id": 1,
  "reason": "" // optional, user may not provide
}

// Response
{
  "message": "Reflex test denied"
}
```

#### Test Case 5.1.4: Error handling
1. Simulate API error (500 or network failure) on approval
2. Click "Approve & Order"

**Expected Behavior:**
- Error message displayed: "Failed to create reflex test: {error}"
- Modal remains open
- User can retry

### 5.2 ViolationsSummaryModal.vue

#### Test Case 5.2.1: Reflex alert styling
1. Open Violations Summary modal for a request with reflex alerts
2. Navigate to "CDS Rules" section

**Expected Behavior:**
- Reflex alerts have yellow/warning background
- Badge shows "REFLEX TEST" (not just "WARNING" or "INFO")
- "Approve" and "Deny" buttons visible (not "Acknowledge")

#### Test Case 5.2.2: Approve from violations modal
1. In Violations Summary, find reflex alert
2. Click "Approve" button

**Expected Behavior:**
- API call to approve-reflex-test endpoint
- Success message
- Alert marked as processed (removed from list or marked acknowledged)
- Modal remains open for other alerts

#### Test Case 5.2.3: Deny from violations modal
1. In Violations Summary, find reflex alert
2. Click "Deny" button
3. Confirmation dialog appears
4. Confirm denial

**Expected Behavior:**
- API call to deny-reflex-test endpoint
- Alert marked as denied
- Success message
- Alert removed or marked denied in UI

### 5.3 CategoryResults.vue (Worklist)

#### Test Case 5.3.1: Reflex request display
1. Navigate to worklist (e.g., Chemistry results)
2. Reflex requests should appear in the list

**Expected Behavior:**
- "Type" column shows red "REFLEX" badge
- Sample ID displayed normally
- Patient information displayed normally
- Status pill shows appropriate status (not blocked by parent)

#### Test Case 5.3.2: "Show Only Reflex Tests" filter
1. Check "Show Only Reflex Tests" checkbox
2. Worklist should filter to show only reflex requests

**Expected Behavior:**
- Only requests with `is_reflex = true` are shown
- Count in triage tabs updates accordingly
- "Reflex Origin" column appears showing parent request ID
- Parent request ID is clickable

#### Test Case 5.3.3: Navigate to parent request
1. With reflex filter active, click parent request ID in "Reflex Origin" column
2. Should navigate to parent request details

**Expected Behavior:**
- Router navigates to parent request view
- Parent request modal/opens correctly
- Can view original tests and results

#### Test Case 5.3.4: Reflex in correct category
1. Create reflex tests that span multiple categories
2. Navigate to Chemistry worklist
3. Navigate to Hematology worklist

**Expected Behavior:**
- Chemistry reflex requests appear in Chemistry worklist
- Hematology reflex requests appear in Hematology worklist
- Each in correct category tab

#### Test Case 5.3.5: Reflex request status flow
1. Create reflex request via approval
2. Check worklist
3. Process reflex request through normal workflow (collect, accept, enter results, etc.)

**Expected Behavior:**
- Reflex request follows same status flow as normal requests
- Can enter results for reflex tests
- Can validate and release reflex results
- No special restrictions beyond normal workflow

---

## 6. Integration Testing

### 6.1 Full End-to-End Workflow

**Scenario: Complete Reflex Test Lifecycle**

1. **Setup**: Create test patient, doctor, laboratory request with Glucose test
2. **Enter Result**: Clinician enters Glucose result = 250 mg/dL (above 200 threshold)
3. **Save Result**: System saves result and triggers CDS check
4. **CDS Alert**: CDS rule "High Glucose Reflex" triggers with REFLEX_TEST action
5. **Frontend Display**: UniversalResultModal shows reflex approval prompt
6. **User Action**: Clinician clicks "Approve & Order"
7. **Backend Processing**:
   - `approveReflexTest()` validates request
   - `ReflexTestService::createReflexTests()` creates reflex request(s)
   - Audit entry recorded
8. **Worklist Update**: New reflex request appears in appropriate category worklist
9. **Reflex Processing**: Technologist processes reflex HbA1c test
10. **Result Entry**: HbA1c result entered and validated
11. **Report Generation**: Final report includes both parent and reflex results

**Verification Points:**
- Database: `laboratory_requests.is_reflex = 1` for reflex request
- Database: `laboratory_request_test.is_reflex_test = 1` for reflex test items
- Database: `rule_decision_audit` has 'reflex_created' entry
- Frontend: Reflex badge visible in worklist
- Frontend: Parent request ID link works
- Audit trail complete with timestamps and user IDs

### 6.2 Package/Group Test Handling

**Scenario: Reflex to Package Test**

1. **Setup**: Create a test package "Diabetes Panel" containing:
   - Glucose test (Chemistry category)
   - HbA1c test (Chemistry category)
   - Fructosamine test (Chemistry category)
2. **CDS Rule**: Trigger reflex to a parameter in Glucose test
3. **Approval**: Clinician approves reflex
4. **Verification**:
   - All tests in package are included
   - All tests belong to same category (Chemistry)
   - Single reflex request created with all 3 tests
   - If package spanned categories, multiple requests created

**Test SQL:**
```sql
-- Check all tests created
SELECT lrt.test_id, t.name, tc.name as category
FROM laboratory_request_test lrt
JOIN tests t ON lrt.test_id = t.id
JOIN test_categories tc ON t.test_category_id = tc.id
WHERE lrt.laboratory_request_id = {reflex_request_id};
```

### 6.3 Priority Inheritance

**Scenario: Priority Propagation**

1. **Setup**: Create parent request with priority = "STAT"
2. **Trigger**: Result triggers reflex
3. **Approve**: Create reflex test
4. **Verify**:
   ```sql
   SELECT priority_level FROM laboratory_requests 
   WHERE id = {reflex_request_id};
   -- Should return "STAT"
   ```

**Test with different priorities:**
- STAT → STAT
- ROUTINE → ROUTINE
- URGENT → URGENT

**Also verify inherited fields:**
- patient_id (same as parent)
- doctor_id (same as parent)
- ward_id (same as parent)
- room_id (same as parent)
- patient_type_id (same as parent)

**Verify NOT inherited:**
- sample_id (different, unique)
- request_date (current timestamp)
- created_by (current user)

### 6.4 Duplicate Prevention

**Scenario: Prevent Duplicate Reflex Creation**

1. **First Approval**:
   ```bash
   curl -X POST ".../approve-reflex-test" \
     -d '{"rule_id": 1, "reflex_test_id": 5}'
   ```
   - Creates reflex request(s)
   - Audit entry: 'reflex_created'

2. **Second Approval Attempt**:
   ```bash
   curl -X POST ".../approve-reflex-test" \
     -d '{"rule_id": 1, "reflex_test_id": 5}'
   ```
   - Returns empty array
   - No new requests created
   - Log: "Reflex test already exists"

3. **Verification**:
   ```sql
   -- Count reflex requests for this parent+rule
   SELECT COUNT(*) FROM laboratory_requests 
   WHERE reflex_parent_request_id = 1 
     AND reflex_triggered_by_rule_id = 1;
   -- Should be 1
   ```

### 6.5 Denial Workflow

**Scenario: Denied Reflex Cannot Be Re-Approved**

1. **Denial**:
   ```bash
   curl -X POST ".../deny-reflex-test" \
     -d '{"rule_id": 1, "reason": "Not clinically needed"}'
   ```
   - Audit entry: 'reflex_denied'

2. **Attempt Approval**:
   ```bash
   curl -X POST ".../approve-reflex-test" \
     -d '{"rule_id": 1, "reflex_test_id": 5}'
   ```
   - Returns empty array (hasExistingReflex returns true from audit)
   - No requests created

3. **Verification**:
   ```sql
   -- Should have only 'reflex_denied' entry, no 'reflex_created'
   SELECT decision FROM rule_decision_audit 
   WHERE laboratory_request_id = 1 AND rule_id = 1;
   ```

---

## 7. Data Integrity Checks

### 7.1 Reflex Request Validation

```sql
-- 1. All reflex requests have is_reflex = true
SELECT COUNT(*) as non_reflex_orphans
FROM laboratory_requests 
WHERE is_reflex = 0 
  AND (reflex_parent_request_id IS NOT NULL OR reflex_triggered_by_rule_id IS NOT NULL);

-- Expected: 0

-- 2. Reflex parent references are valid
SELECT COUNT(*) as invalid_parent_refs
FROM laboratory_requests 
WHERE reflex_parent_request_id IS NOT NULL 
  AND reflex_parent_request_id NOT IN (SELECT id FROM laboratory_requests);

-- Expected: 0

-- 3. Reflex rule references are valid
SELECT COUNT(*) as invalid_rule_refs
FROM laboratory_requests 
WHERE reflex_triggered_by_rule_id IS NOT NULL 
  AND reflex_triggered_by_rule_id NOT IN (SELECT id FROM cds_correlation_rules);

-- Expected: 0

-- 4. Reflex parent is not itself (circular reference prevention)
SELECT COUNT(*) as circular_refs
FROM laboratory_requests 
WHERE id = reflex_parent_request_id;

-- Expected: 0
```

### 7.2 Reflex Test Items Validation

```sql
-- 1. All reflex test items have is_reflex_test = true
SELECT COUNT(*) as non_reflex_test_items
FROM laboratory_request_test 
WHERE is_reflex_test = 1 
  AND (reflex_triggered_at IS NULL OR reflex_triggered_by_rule_id IS NULL);

-- Expected: 0

-- 2. Reflex test items reference valid requests
SELECT COUNT(*) as invalid_request_refs
FROM laboratory_request_test 
WHERE is_reflex_test = 1 
  AND laboratory_request_id NOT IN (SELECT id FROM laboratory_requests);

-- Expected: 0

-- 3. Reflex test items reference valid rules
SELECT COUNT(*) as invalid_rule_refs
FROM laboratory_request_test 
WHERE is_reflex_test = 1 
  AND reflex_triggered_by_rule_id NOT IN (SELECT id FROM cds_correlation_rules);

-- Expected: 0

-- 4. Reflex test items have timestamps
SELECT COUNT(*) as missing_timestamps
FROM laboratory_request_test 
WHERE is_reflex_test = 1 
  AND reflex_triggered_at IS NULL;

-- Expected: 0
```

### 7.3 Audit Trail Completeness

```sql
-- 1. All reflex creations have audit entries
SELECT COUNT(*) as reflex_requests_without_audit
FROM laboratory_requests lr
WHERE lr.is_reflex = 1
  AND NOT EXISTS (
    SELECT 1 FROM rule_decision_audit rda
    WHERE rda.laboratory_request_id = lr.reflex_parent_request_id
      AND rda.rule_id = lr.reflex_triggered_by_rule_id
      AND rda.decision = 'reflex_created'
  );

-- Expected: 0

-- 2. All reflex denials have audit entries
SELECT COUNT(*) as denied_reflexes_without_audit
FROM laboratory_requests lr
WHERE lr.is_reflex = 1
  AND NOT EXISTS (
    SELECT 1 FROM rule_decision_audit rda
    WHERE rda.laboratory_request_id = lr.reflex_parent_request_id
      AND rda.rule_id = lr.reflex_triggered_by_rule_id
      AND rda.decision = 'reflex_denied'
  );

-- Expected: 0 (or count of denials you expect)

-- 3. Audit entries have user_id
SELECT COUNT(*) as orphaned_audit_entries
FROM rule_decision_audit 
WHERE user_id IS NULL 
  OR user_id NOT IN (SELECT id FROM users);

-- Expected: 0
```

### 7.4 Sample ID Uniqueness

```sql
-- Verify all sample IDs are unique
SELECT sample_id, COUNT(*) as count
FROM laboratory_requests
GROUP BY sample_id
HAVING count > 1;

-- Expected: empty result set

-- Verify reflex sample IDs differ from parent
SELECT COUNT(*) as matching_sample_ids
FROM laboratory_requests lr
JOIN laboratory_requests parent ON lr.reflex_parent_request_id = parent.id
WHERE lr.sample_id = parent.sample_id;

-- Expected: 0
```

---

## 8. Performance Testing

### 8.1 Bulk Reflex Creation

**Test Scenario:** Create multiple reflex requests simultaneously

```bash
# Use a script to trigger multiple reflex approvals
for i in {1..10}; do
  curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
    -H "Authorization: Bearer {token}" \
    -d "{\"rule_id\": 1, \"reflex_test_id\": 5}" &
done
wait
```

**Expected Behavior:**
- No race conditions in `hasExistingReflex()` check
- Only first request creates reflex, others return empty array
- Database locks handled properly
- No duplicate entries

**Monitoring:**
```sql
-- Check count after concurrent requests
SELECT COUNT(*) FROM laboratory_requests 
WHERE reflex_parent_request_id = 1 AND reflex_triggered_by_rule_id = 1;
-- Should be 1
```

### 8.2 Worklist Loading with Many Reflex Requests

**Test Scenario:** Load worklist with 100+ reflex requests

```sql
-- Generate test data
INSERT INTO laboratory_requests (
    patient_id, doctor_id, patient_type_id, sample_id, 
    priority_level, is_reflex, reflex_parent_request_id, 
    reflex_triggered_by_rule_id, created_at
)
SELECT 
    patient_id, doctor_id, patient_type_id,
    CONCAT('BULK-', UUID()),
    'ROUTINE',
    1, -- is_reflex
    FLOOR(RAND() * 10) + 1, -- random parent
    1, -- rule_id
    NOW()
FROM laboratory_requests
WHERE is_reflex = 0
LIMIT 100;
```

**Expected Behavior:**
- Worklist loads in < 2 seconds
- Pagination works correctly
- Filter "Show Only Reflex Tests" works efficiently
- No timeouts or memory issues

### 8.3 Database Indexing

**Recommended Indexes:**
```sql
-- Add these indexes if not present for performance

-- laboratory_requests
CREATE INDEX idx_laboratory_requests_reflex_parent ON laboratory_requests(reflex_parent_request_id);
CREATE INDEX idx_laboratory_requests_reflex_rule ON laboratory_requests(reflex_triggered_by_rule_id);
CREATE INDEX idx_laboratory_requests_is_reflex ON laboratory_requests(is_reflex);

-- laboratory_request_test
CREATE INDEX idx_laboratory_request_test_reflex_rule ON laboratory_request_test(reflex_triggered_by_rule_id);
CREATE INDEX idx_laboratory_request_test_is_reflex ON laboratory_request_test(is_reflex_test);

-- rule_decision_audit
CREATE INDEX idx_rule_decision_audit_parent_rule ON rule_decision_audit(laboratory_request_id, rule_id, decision);
```

**Verification:**
```sql
-- Check if indexes exist
SHOW INDEX FROM laboratory_requests;
SHOW INDEX FROM laboratory_request_test;
```

---

## 9. User Acceptance Testing (UAT)

### 9.1 Clinical Staff Workflow

**Test Participants:**
- Clinicians (doctors, residents)
- Laboratory technologists
- Pathologists

**Scenario 1: Clinician Approves Reflex**
1. Clinician enters patient result (e.g., high glucose)
2. System shows reflex suggestion modal automatically
3. Clinician reviews suggested reflex test (HbA1c)
4. Clinician clicks "Approve & Order"
5. System confirms success
6. Clinician sees new test order in worklist

**Acceptance Criteria:**
- Modal appears automatically without manual refresh
- Information is clear: what test, why it's recommended
- Approval is one-click
- Confirmation is immediate and clear
- New order appears in correct worklist

**Scenario 2: Clinician Denies Reflex**
1. Clinician sees reflex suggestion
2. Clinician clicks "Deny"
3. Optional: Enter reason (e.g., "Patient already has recent HbA1c")
4. System confirms denial
5. No new order created

**Acceptance Criteria:**
- Denial is simple
- Reason field is optional but available
- No new order cluttered the worklist
- Denial is logged for audit

**Scenario 3: Technologist Processes Reflex**
1. Reflex order appears in worklist with "REFLEX" badge
2. Technologist sees it's a reflex (not original order)
3. Can view parent request by clicking "Reflex Origin" link
4. Processes reflex test normally
5. Results appear in final report

**Acceptance Criteria:**
- Reflex orders are clearly marked
- Easy to distinguish from regular orders
- Can trace back to parent request
- Normal workflow applies

### 9.2 Usability Checklist

- [ ] Reflex alerts are noticeable but not obtrusive
- [ ] "Approve" and "Deny" buttons are clearly labeled
- [ ] Reflex orders in worklist have distinct visual styling
- [ ] "REFLEX" badge is prominently displayed
- [ ] Parent request ID link is clickable and intuitive
- [ ] Filter "Show Only Reflex Tests" works as expected
- [ ] No confusion between reflex and non-reflex tests
- [ ] Audit trail is accessible for compliance

### 9.3 Compliance and Audit

**Test: Complete Audit Trail**
1. Perform reflex approval
2. Perform reflex denial
3. Query database for audit entries

**Expected:**
```sql
SELECT * FROM rule_decision_audit 
WHERE laboratory_request_id = {parent_id}
ORDER BY created_at;
```

Should show:
- Entry 1: decision = 'reflex_created', user_id = approver, timestamp
- Entry 2: decision = 'reflex_denied', user_id = denier, timestamp (if both actions performed)

Each entry should have:
- rule_id
- rule_type = 'CDS' or 'cds_correlation'
- message describing action
- context with relevant IDs
- user_id (who performed action)

---

## 10. Rollback Testing

### 10.1 Migration Rollback

```bash
# 1. Backup database (important!)
mysqldump -u username -p database_name > backup_before_rollback.sql

# 2. Rollback reflex migrations
cd backend
php artisan migrate:rollback --step=2

# Expected output:
# Rolled back: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
# Rolled back: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
```

**Verification:**
```sql
-- Columns should be removed
SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
-- Should return empty set

SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
-- Should return empty set
```

### 10.2 Application Behavior After Rollback

**Test: Application Still Functions**

1. **Backend API:**
   - Existing endpoints should still work
   - Reflex-specific endpoints (approve-reflex-test, deny-reflex-test) should return 404 or graceful error
   - No crashes or exceptions

2. **Frontend:**
   - Pages load without JavaScript errors
   - Reflex-related UI elements should be hidden or disabled
   - No broken features

**Expected Graceful Degradation:**
- Reflex features silently disabled
- No impact on core LIS functionality
- Users can still use all non-reflex features

### 10.3 Re-apply Migrations

```bash
# Re-apply migrations
php artisan migrate

# Verify columns restored
SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';

# Expected: columns exist with correct definitions
```

### 10.4 Data Preservation

**Check: No Data Loss**

```sql
-- After rollback and re-migrate, existing non-reflex data should be intact
SELECT COUNT(*) FROM patients;
SELECT COUNT(*) FROM laboratory_requests WHERE is_reflex = 0;
-- Should match pre-rollback counts

-- Reflex-specific data will be lost (columns dropped)
-- This is expected - ensure this is acceptable or backup first
```

---

## Troubleshooting

### Issue: "Column not found: 1054 Unknown column 'is_reflex' in 'field list'"

**Solution:** Migrations not run. Execute:
```bash
php artisan migrate
```

### Issue: "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row"

**Solution:** Foreign key constraint fails. Ensure referenced data exists:
```sql
SELECT id FROM cds_correlation_rules WHERE id = {rule_id};
SELECT id FROM laboratory_requests WHERE id = {parent_id};
```

### Issue: Reflex request not created, but no error

**Check:**
1. Verify `hasExistingReflex()` returns false
2. Check logs: `storage/logs/laravel.log`
3. Verify rule has `reflex_test_id` populated
4. Check test parameter exists and belongs to a test

### Issue: Reflex request created but no tests attached

**Check:**
1. Verify `getTestsForReflexParameter()` returns tests
2. Check if test belongs to package and package has tests
3. Verify test_category_id is set on tests

### Issue: Frontend modal doesn't show reflex approval

**Check:**
1. CDS check endpoint returns reflex alerts with `action_type: 'REFLEX_TEST'`
2. Frontend receives and processes alerts correctly
3. Check browser console for JavaScript errors
4. Verify `showViolationsModal` is triggered on modal open

### Issue: "Reflex Origin" column not showing

**Check:**
1. `showOnlyReflex` checkbox is checked
2. Worklist requests have `is_reflex = true`
3. `reflex_parent_request_id` is set on reflex requests
4. Component logic for column visibility

---

## Appendix

### A. Test Data Seeder

Create `database/seeders/ReflexTestSeeder.php` for consistent test data:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\TestCategory;
use App\Models\Test;
use App\Models\TestParameter;
use App\Models\CdsCorrelationRule;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryRequestTest;
use App\Models\User;

class ReflexTestSeeder extends Seeder
{
    public function run()
    {
        // Create user for testing
        $user = User::firstOrCreate(
            ['email' => 'test@lab.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        // Create patient
        $patient = Patient::firstOrCreate(
            ['id' => 'TEST001'],
            [
                'last_name' => 'TestPatient',
                'first_name' => 'John',
                'date_of_birth' => '1990-05-15',
                'gender' => 'Male',
            ]
        );

        // Create doctor
        $doctor = Doctor::firstOrCreate(
            ['email' => 'test.doctor@hospital.com'],
            ['name' => 'Dr. Test Physician']
        );

        // Create category and test
        $category = TestCategory::firstOrCreate(
            ['name' => 'Chemistry'],
            ['description' => 'Chemistry Tests']
        );

        $test = Test::firstOrCreate(
            ['name' => 'Glucose', 'test_category_id' => $category->id],
            ['description' => 'Blood glucose', 'normal_range' => '70-100', 'unit' => 'mg/dL']
        );

        // Create parameters
        $glucoseParam = TestParameter::firstOrCreate(
            ['test_id' => $test->id, 'name' => 'Glucose'],
            ['normal_range' => '70-100', 'unit' => 'mg/dL', 'display_order' => 1]
        );

        $hbA1cParam = TestParameter::firstOrCreate(
            ['test_id' => $test->id, 'name' => 'HbA1c'],
            ['normal_range' => '4.0-5.6', 'unit' => '%', 'display_order' => 2]
        );

        // Create CDS rule
        $rule = CdsCorrelationRule::firstOrCreate(
            ['name' => 'High Glucose Reflex'],
            [
                'description' => 'Triggers when glucose > 200',
                'trigger_condition' => json_encode(['parameter_name' => 'Glucose', 'operator' => '>', 'value' => 200]),
                'alert_message' => 'High glucose detected. Consider HbA1c test.',
                'action_type' => 'REFLEX_TEST',
                'reflex_test_id' => $hbA1cParam->id,
                'requires_approval' => 1,
                'is_active' => 1,
            ]
        );

        // Create laboratory request
        $request = LaboratoryRequest::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'patient_type_id' => 1,
            'sample_id' => 'TEST-SAMPLE-' . time(),
            'priority_level' => 'ROUTINE',
            'created_by' => $user->id,
        ]);

        // Create test item
        LaboratoryRequestTest::create([
            'laboratory_request_id' => $request->id,
            'test_id' => $test->id,
            'item_status' => 'pending',
        ]);

        echo "Test data created:\n";
        echo "- Patient: {$patient->id} - {$patient->last_name}, {$patient->first_name}\n";
        echo "- Request: {$request->id} - Sample: {$request->sample_id}\n";
        echo "- CDS Rule: {$rule->id} - {$rule->name}\n";
        echo "- Reflex Parameter: {$hbA1cParam->id} - {$hbA1cParam->name}\n";
    }
}
```

Run seeder:
```bash
php artisan db:seed --class=ReflexTestSeeder
```

### B. API Testing with Postman Collection

Import this collection into Postman:

```json
{
  "info": {
    "name": "Reflex Test API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "CDS Check",
      "request": {
        "method": "GET",
        "header": [
          { "key": "Authorization", "value": "Bearer {{token}}" }
        ],
        "url": { "raw": "{{base_url}}/api/results/check-cds/{{request_id}}" }
      }
    },
    {
      "name": "Approve Reflex",
      "request": {
        "method": "POST",
        "header": [
          { "key": "Content-Type", "value": "application/json" },
          { "key": "Authorization", "value": "Bearer {{token}}" }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"rule_id\": {{rule_id}},\n  \"reflex_test_id\": {{reflex_test_id}},\n  \"auto_create\": false\n}"
        },
        "url": { "raw": "{{base_url}}/api/results/laboratory-requests/{{request_id}}/approve-reflex-test" }
      }
    },
    {
      "name": "Deny Reflex",
      "request": {
        "method": "POST",
        "header": [
          { "key": "Content-Type", "value": "application/json" },
          { "key": "Authorization", "value": "Bearer {{token}}" }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"rule_id\": {{rule_id}},\n  \"reason\": \"Not clinically indicated\"\n}"
        },
        "url": { "raw": "{{base_url}}/api/results/laboratory-requests/{{request_id}}/deny-reflex-test" }
      }
    }
  ]
}
```

### C. Useful Queries for Monitoring

```sql
-- Daily reflex creation count
SELECT 
    DATE(created_at) as date,
    COUNT(*) as reflex_count
FROM laboratory_requests
WHERE is_reflex = 1
  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Most common reflex rules
SELECT 
    r.name as rule_name,
    COUNT(lr.id) as reflex_count
FROM laboratory_requests lr
JOIN cds_correlation_rules r ON lr.reflex_triggered_by_rule_id = r.id
WHERE lr.is_reflex = 1
GROUP BY r.id, r.name
ORDER BY reflex_count DESC
LIMIT 10;

-- Reflex approval/denial ratio
SELECT 
    decision,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
FROM rule_decision_audit
WHERE rule_type = 'CDS'
  AND decision IN ('reflex_created', 'reflex_denied')
  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY decision;

-- Reflex requests by priority
SELECT 
    priority_level,
    COUNT(*) as count
FROM laboratory_requests
WHERE is_reflex = 1
GROUP BY priority_level
ORDER BY FIELD(priority_level, 'STAT', 'URGENT', 'ROUTINE');
```

---

## Document Summary

This comprehensive testing guide covers:

✅ **Pre-Testing Setup** - Database preparation, test data creation
✅ **Database Migration Testing** - Migration execution, rollback, constraints
✅ **Backend Service Testing** - All ReflexTestService methods with edge cases
✅ **API Endpoint Testing** - CDS check, approve, deny with curl examples
✅ **Frontend Testing** - UniversalResultModal, ViolationsSummaryModal, CategoryResults
✅ **Integration Testing** - Full workflows, package handling, priority inheritance, duplicates
✅ **Data Integrity Checks** - SQL queries to validate data consistency
✅ **Performance Testing** - Concurrent requests, bulk operations, indexing
✅ **User Acceptance Testing** - Clinical workflows, usability checklist
✅ **Rollback Testing** - Safe rollback procedure and verification

**Deliverable:** `REFLEX_TEST_TESTING_GUIDE.md` - Complete testing documentation with executable test cases, expected outcomes, and troubleshooting guidance.

---

**Next Steps:**
1. Execute pre-testing setup to create test environment
2. Run database migration tests
3. Execute backend service unit tests (if PHPUnit tests exist)
4. Perform API endpoint tests using Postman or curl
5. Conduct frontend component testing in browser
6. Run full integration test scenarios
7. Perform data integrity validation queries
8. Execute performance tests
9. Schedule UAT sessions with clinical staff
10. Document rollback procedure validation

All test results should be logged and any issues tracked in your issue tracking system.