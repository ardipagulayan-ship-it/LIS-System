# Reflex Test Workflow - Migration and Deployment Guide

## 1. Overview

### Summary of Changes

This implementation adds a comprehensive Reflex Test Workflow to the Laboratory Information System (LIS). The system automatically suggests additional tests based on Clinical Decision Support (CDS) rules when initial test results meet specific criteria. Laboratory staff can then approve or deny these reflex test suggestions.

**Key Features:**
- Automatic CDS rule evaluation when results are entered
- Reflex test approval/denial workflow
- Audit trail for all reflex decisions
- Parent-child relationship between laboratory requests
- Support for package/group test expansion
- Priority inheritance from parent requests

### Files Modified/Created

**Backend:**
- `backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php`
- `backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php`
- `backend/app/Services/ReflexTestService.php` (new)
- `backend/app/Http/Controllers/Api/ResultManagementController.php` (modified - added reflex endpoints)
- `backend/routes/api.php` (modified - added reflex routes)

**Frontend:**
- `frontend/src/components/modals/result/UniversalResultModal.vue` (modified - CDS alert integration)
- `frontend/src/components/modals/result/ViolationsSummaryModal.vue` (modified - reflex approval UI)
- `frontend/src/views/ResultManagement/CategoryResults.vue` (modified - reflex display and filtering)

**Documentation:**
- `REFLEX_TEST_API_DOCUMENTATION.md` (new)
- `REFLEX_TEST_TESTING_GUIDE.md` (new)

### Database Schema Changes

#### Table: `laboratory_requests`

| Column Name | Data Type | Nullable | Default | Description |
|-------------|-----------|----------|---------|-------------|
| `is_reflex` | tinyint(1) | NO | 0 | Indicates this is a reflex test request |
| `reflex_parent_request_id` | bigint unsigned | YES | NULL | ID of parent request that triggered this reflex |
| `reflex_triggered_by_rule_id` | bigint unsigned | YES | NULL | ID of CDS rule that caused this reflex |

**Foreign Keys:**
- `reflex_parent_request_id` → `laboratory_requests.id` (ON DELETE SET NULL)
- `reflex_triggered_by_rule_id` → `cds_correlation_rules.id` (ON DELETE SET NULL)

#### Table: `laboratory_request_test`

| Column Name | Data Type | Nullable | Default | Description |
|-------------|-----------|----------|---------|-------------|
| `is_reflex_test` | tinyint(1) | NO | 0 | Indicates this test is part of reflex workflow |
| `reflex_triggered_at` | timestamp | YES | NULL | Timestamp when reflex was triggered |
| `reflex_triggered_by_rule_id` | bigint unsigned | YES | NULL | ID of CDS rule that triggered this test |

**Foreign Key:**
- `reflex_triggered_by_rule_id` → `cds_correlation_rules.id` (ON DELETE SET NULL)

---

## 2. Prerequisites

### Laravel Version Requirements
- **Laravel 10.x** or higher
- Compatible with Laravel Sanctum for authentication

### PHP Version Requirements
- **PHP 8.1** or higher
- Required extensions:
  - `pdo_mysql` or `pdo_pgsql` (depending on database)
  - `mbstring`
  - `openssl`
  - `tokenizer`
  - `xml`
  `curl`
  `bcmath`

### Database Requirements

**MySQL:**
- Version 8.0+ recommended
- InnoDB storage engine
- UTF-8mb4 character set

**PostgreSQL:**
- Version 12+ recommended
- Compatible with Laravel 10.x

### Frontend Dependencies
- **Node.js**: 18.x or higher
- **npm**: 9.x or higher (or yarn)
- **Vue.js**: 3.5.x (already in project)
- **Vite**: 6.2.x (already in project)
- **Element Plus**: 2.9.x (already in project)

---

## 3. Database Migration Steps

### 3.1 Pre-Migration Checklist

**⚠️ MANDATORY: Backup Database**

```bash
# MySQL backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL backup
pg_dump -U username database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Other Checks:**
- [ ] Verify no pending migrations: `php artisan migrate:status`
- [ ] Check database user has CREATE, ALTER, INDEX privileges
- [ ] Ensure application is in maintenance mode (optional but recommended):
  ```bash
  php artisan down
  ```

### 3.2 Run Migrations

```bash
cd backend
php artisan migrate
```

**Expected Output:**
```
Migrating: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
Migrated:  2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
Migrating: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
Migrated:  2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
```

**Verify:**
```bash
php artisan migrate:status | grep reflex
# Should show both migrations as "Yes"
```

### 3.3 Verify Migration Success

**Check laboratory_requests table structure:**
```sql
DESCRIBE laboratory_requests;
```

**Check for reflex columns:**
```sql
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'laboratory_requests'
  AND COLUMN_NAME IN ('is_reflex', 'reflex_parent_request_id', 'reflex_triggered_by_rule_id')
ORDER BY ORDINAL_POSITION;
```

**Expected Result:**
```
+---------------------------+--------------+------------+----------------+---------+
| COLUMN_NAME               | DATA_TYPE    | IS_NULLABLE| COLUMN_DEFAULT | EXTRA   |
+---------------------------+--------------+------------+----------------+---------+
| is_reflex                 | tinyint(1)   | NO         | 0              |         |
| reflex_parent_request_id  | bigint       | YES        | NULL           |         |
| reflex_triggered_by_rule_id| bigint      | YES        | NULL           |         |
+---------------------------+--------------+------------+----------------+---------+
```

**Check laboratory_request_test table structure:**
```sql
DESCRIBE laboratory_request_test;
```

**Check for reflex test columns:**
```sql
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'laboratory_request_test'
  AND COLUMN_NAME IN ('is_reflex_test', 'reflex_triggered_at', 'reflex_triggered_by_rule_id');
```

**Expected Result:**
```
+---------------------------+--------------+------------+----------------+---------+
| COLUMN_NAME               | DATA_TYPE    | IS_NULLABLE| COLUMN_DEFAULT | EXTRA   |
+---------------------------+--------------+------------+----------------+---------+
| is_reflex_test            | tinyint(1)   | NO         | 0              |         |
| reflex_triggered_at       | timestamp    | YES        | NULL           |         |
| reflex_triggered_by_rule_id| bigint      | YES        | NULL           |         |
+---------------------------+--------------+------------+----------------+---------+
```

**Verify foreign keys:**
```sql
-- For laboratory_requests
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
  AND rc.TABLE_NAME = 'laboratory_requests'
  AND kcu.COLUMN_NAME IN ('reflex_parent_request_id', 'reflex_triggered_by_rule_id');

-- For laboratory_request_test
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
  AND rc.TABLE_NAME = 'laboratory_request_test'
  AND kcu.COLUMN_NAME = 'reflex_triggered_by_rule_id';
```

**Expected Foreign Keys:**
- `laboratory_requests.reflex_parent_request_id` → `laboratory_requests.id` (SET NULL)
- `laboratory_requests.reflex_triggered_by_rule_id` → `cds_correlation_rules.id` (SET NULL)
- `laboratory_request_test.reflex_triggered_by_rule_id` → `cds_correlation_rules.id` (SET NULL)

### 3.4 Rollback Procedure (if needed)

```bash
# Rollback the last 2 migrations
php artisan migrate:rollback --step=2
```

**Expected Output:**
```
Rolling back: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
Rolled back:  2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
Rolling back: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
Rolled back:  2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
```

**Verify rollback:**
```sql
-- Columns should no longer exist
SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
-- Should return empty set

SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
-- Should return empty set
```

**Re-apply migrations if needed:**
```bash
php artisan migrate
```

---

## 4. Seeding Test Data

### 4.1 Create Test CDS Rule

**Option A: Using Laravel Seeder (Recommended)**

Create `backend/database/seeders/ReflexTestSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TestCategory;
use App\Models\Test;
use App\Models\TestParameter;
use App\Models\CdsCorrelationRule;
use App\Models\User;

class ReflexTestSeeder extends Seeder
{
    public function run()
    {
        // Get or create a test user (for created_by fields)
        $user = User::firstOrCreate(
            ['email' => 'test@lab.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now()
            ]
        );

        // Create Chemistry test category if not exists
        $category = TestCategory::firstOrCreate(
            ['name' => 'Chemistry'],
            ['description' => 'Chemistry Tests', 'display_order' => 1]
        );

        // Create Glucose test
        $glucoseTest = Test::firstOrCreate(
            ['name' => 'Glucose', 'test_category_id' => $category->id],
            [
                'description' => 'Blood glucose measurement',
                'normal_range' => '70-100',
                'unit' => 'mg/dL'
            ]
        );

        // Create Glucose parameter
        $glucoseParam = TestParameter::firstOrCreate(
            ['test_id' => $glucoseTest->id, 'name' => 'Glucose'],
            [
                'normal_range' => '70-100',
                'unit' => 'mg/dL',
                'display_order' => 1
            ]
        );

        // Create HbA1c parameter (reflex target)
        $hba1cParam = TestParameter::firstOrCreate(
            ['test_id' => $glucoseTest->id, 'name' => 'HbA1c'],
            [
                'normal_range' => '4.0-5.6',
                'unit' => '%',
                'display_order' => 2
            ]
        );

        // Create CDS correlation rule for reflex testing
        $rule = CdsCorrelationRule::firstOrCreate(
            ['name' => 'Low Hemoglobin - Reflex Reticulocyte'],
            [
                'description' => 'When HGB < 5 g/dL, suggest reticulocyte count',
                'trigger_parameter_id' => $glucoseParam->id,
                'condition' => '<',
                'threshold_value' => 5,
                'action_type' => 'REFLEX_TEST',
                'reflex_test_id' => $hba1cParam->id,
                'alert_message' => 'Critical low hemoglobin detected. Reflex reticulocyte count recommended.',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]
        );

        echo "✅ Test CDS Rule Created:\n";
        echo "   - Rule ID: {$rule->id}\n";
        echo "   - Rule Name: {$rule->name}\n";
        echo "   - Trigger Parameter: {$glucoseParam->name} (ID: {$glucoseParam->id})\n";
        echo "   - Reflex Parameter: {$hba1cParam->name} (ID: {$hba1cParam->id})\n";
        echo "   - Condition: {$rule->condition} {$rule->threshold_value}\n";
    }
}
```

**Run the seeder:**
```bash
cd backend
php artisan db:seed --class=ReflexTestSeeder
```

**Option B: Direct SQL Insert**

```sql
-- First, find or create the test parameter IDs
-- Replace <test_parameter_id_for_HGB> and <test_parameter_id_for_RETIC> with actual IDs

INSERT INTO cds_correlation_rules (
    rule_name,
    description,
    trigger_parameter_id,
    condition,
    threshold_value,
    action_type,
    reflex_test_id,
    alert_message,
    is_active,
    created_at,
    updated_at
) VALUES (
    'Low Hemoglobin - Reflex Reticulocyte',
    'When HGB < 5 g/dL, suggest reticulocyte count',
    <test_parameter_id_for_HGB>,
    '<',
    5,
    'REFLEX_TEST',
    <test_parameter_id_for_RETIC>,
    'Critical low hemoglobin detected. Reflex reticulocyte count recommended.',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    alert_message = VALUES(alert_message),
    reflex_test_id = VALUES(reflex_test_id),
    is_active = VALUES(is_active);
```

### 4.2 Create Complete Test Data

**Using SQL (Full Test Setup):**

```sql
-- 1. Create test patient
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
    first_name = VALUES(first_name),
    last_name = VALUES(last_name);

-- 2. Create test doctor
INSERT INTO doctors (
    id,
    name,
    specialization,
    email,
    phone,
    created_at,
    updated_at
) VALUES (
    1,
    'Dr. Test Physician',
    'Internal Medicine',
    'test@doctor.com',
    '1234567890',
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    name = VALUES(name);

-- 3. Create patient type
INSERT INTO patient_types (name, description, created_at, updated_at)
VALUES ('Inpatient', 'Hospital admitted patient', NOW(), NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- 4. Create test category (Chemistry)
INSERT INTO test_categories (name, description, display_order, created_at, updated_at)
VALUES ('Chemistry', 'Chemistry Tests', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Get category ID
SET @category_id = (SELECT id FROM test_categories WHERE name = 'Chemistry');

-- 5. Create test (Glucose)
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
    description = VALUES(description);

-- Get test ID
SET @test_id = (SELECT id FROM tests WHERE name = 'Glucose' AND test_category_id = @category_id);

-- 6. Create test parameters
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
(@test_id, 'HbA1c', '4.0-5.6', '%', 2, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    normal_range = VALUES(normal_range),
    unit = VALUES(unit);

-- Get parameter IDs
SET @glucose_param_id = (SELECT id FROM test_parameters WHERE test_id = @test_id AND name = 'Glucose');
SET @hba1c_param_id = (SELECT id FROM test_parameters WHERE test_id = @test_id AND name = 'HbA1c');

-- 7. Create CDS rule
INSERT INTO cds_correlation_rules (
    rule_name,
    description,
    trigger_parameter_id,
    condition,
    threshold_value,
    action_type,
    reflex_test_id,
    alert_message,
    is_active,
    created_at,
    updated_at
) VALUES (
    'High Glucose Reflex',
    'Triggers when glucose > 200',
    @glucose_param_id,
    '>',
    200,
    'REFLEX_TEST',
    @hba1c_param_id,
    'Glucose level is critically high. Recommend HbA1c test to assess long-term control.',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    alert_message = VALUES(alert_message),
    reflex_test_id = VALUES(reflex_test_id),
    is_active = VALUES(is_active);

-- 8. Create laboratory request
INSERT INTO laboratory_requests (
    patient_id,
    doctor_id,
    patient_type_id,
    sample_id,
    priority_level,
    request_date,
    created_by,
    created_at,
    updated_at
) VALUES (
    'TEST001',
    1,
    1,
    CONCAT('SAMPLE-', DATE_FORMAT(NOW(), '%Y%m%d-%H%i%s')),
    'ROUTINE',
    NOW(),
    1,
    NOW(),
    NOW()
);

-- Get request ID
SET @request_id = LAST_INSERT_ID();

-- 9. Create laboratory request test entry
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

-- 10. Create a test result with high glucose value to trigger reflex
INSERT INTO test_results (
    laboratory_request_id,
    test_id,
    test_parameter_id,
    result_value,
    unit,
    flag,
    status,
    created_at,
    updated_at
) VALUES (
    @request_id,
    @test_id,
    @glucose_param_id,
    250.00,
    'mg/dL',
    'HIGH',
    'Saved',
    NOW(),
    NOW()
);

-- Output summary
SELECT 
    'Test Data Created:' as Message
UNION ALL
SELECT CONCAT('Patient: ', id, ' - ', last_name, ', ', first_name) FROM patients WHERE id = 'TEST001'
UNION ALL
SELECT CONCAT('Doctor: ', id, ' - ', name) FROM doctors WHERE id = 1
UNION ALL
SELECT CONCAT('Test Category: ', id, ' - ', name) FROM test_categories WHERE name = 'Chemistry'
UNION ALL
SELECT CONCAT('Test: ', id, ' - ', name) FROM tests WHERE name = 'Glucose'
UNION ALL
SELECT CONCAT('Glucose Parameter: ', id) FROM test_parameters WHERE name = 'Glucose' AND test_id = @test_id
UNION ALL
SELECT CONCAT('HbA1c Parameter: ', id) FROM test_parameters WHERE name = 'HbA1c' AND test_id = @test_id
UNION ALL
SELECT CONCAT('CDS Rule: ', id, ' - ', rule_name) FROM cds_correlation_rules WHERE rule_name = 'High Glucose Reflex'
UNION ALL
SELECT CONCAT('Laboratory Request: ', @request_id, ' - Sample: ', sample_id) FROM laboratory_requests WHERE id = @request_id;
```

### 4.3 Verify Test Setup

**Check rule exists:**
```sql
SELECT 
    r.id,
    r.rule_name,
    r.action_type,
    r.reflex_test_id,
    r.trigger_parameter_id,
    r.condition,
    r.threshold_value,
    r.is_active
FROM cds_correlation_rules r
WHERE r.rule_name = 'High Glucose Reflex';
```

**Check test parameters exist:**
```sql
SELECT 
    t.name as test_name,
    tp.id as parameter_id,
    tp.name as parameter_name,
    tp.normal_range,
    tp.unit
FROM test_parameters tp
JOIN tests t ON tp.test_id = t.id
WHERE t.name = 'Glucose'
ORDER BY tp.display_order;
```

**Check laboratory request and result:**
```sql
SELECT 
    lr.id as request_id,
    lr.sample_id,
    lr.patient_id,
    tr.result_value,
    tp.name as parameter_name
FROM laboratory_requests lr
JOIN laboratory_request_test lrt ON lr.id = lrt.laboratory_request_id
JOIN test_results tr ON lr.id = tr.laboratory_request_id
JOIN test_parameters tp ON tr.test_parameter_id = tp.id
WHERE lr.id = <your_request_id>
  AND tp.name = 'Glucose';
```

---

## 5. Frontend Build and Deployment

### 5.1 Update Frontend Dependencies

```bash
cd frontend
npm install
```

**Expected:** All dependencies from `package.json` installed in `node_modules/`

### 5.2 Build for Production

```bash
npm run build
```

**Expected Output:**
```
vite v6.2.0 building for production...
✓ 123 modules transformed.
dist/index.html                   0.45 kB
dist/assets/index-abc123.css      15.2 kB
dist/assets/index-def456.js       89.7 kB
...
```

**Build artifacts created in:** `frontend/dist/`

### 5.3 Deploy Frontend Assets

**Option A: Laravel Mix/Vite Integration (Recommended)**

If using Vite with Laravel integration:

1. **Backend configuration** (`backend/vite.config.js`):
   ```javascript
   import { defineConfig } from 'vite';
   import laravel from 'laravel-vite-plugin';

   export default defineConfig({
       plugins: [
           laravel({
               input: ['../frontend/resources/js/app.js', '../frontend/resources/css/app.css'],
               refresh: true,
           }),
       ],
   });
   ```

2. **Build and link:**
   ```bash
   cd backend
   npm run build  # Builds both frontend and backend assets
   ```

3. **Production build:**
   ```bash
   npm run build -- --production
   ```

**Option B: Manual Copy**

```bash
# Build frontend
cd frontend
npm run build

# Copy to backend public directory
cp -r dist/* ../backend/public/
```

**Option C: Serve Separately (SPA Mode)**

If frontend is served separately (e.g., Nginx, Apache):

1. Build frontend: `npm run build`
2. Deploy `frontend/dist/` to web server directory
3. Configure API proxy to `backend/public/` or CORS in Laravel

**Configure CORS in Laravel** (`backend/config/cors.php`):
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://your-frontend-domain.com'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

---

## 6. Configuration Changes

### 6.1 Environment Variables

Check `.env` file in backend directory:

```env
# Application
APP_NAME="LIS"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lis_database
DB_USERNAME=lis_user
DB_PASSWORD=your_password

# Sanctum (API Authentication)
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=your-domain.com

# Queue (optional, for async processing)
QUEUE_CONNECTION=database
```

**Important Settings:**
- `APP_DEBUG=false` in production
- `APP_URL` must match your actual domain
- Database credentials must have proper privileges
- Sanctum configuration for API authentication

### 6.2 API Routes

Verify routes are registered in `backend/routes/api.php`:

```php
use App\Http\Controllers\Api\ResultManagementController;

Route::middleware('auth:sanctum')->group(function () {
    // ... existing routes
    
    // Reflex test endpoints
    Route::post('/results/laboratory-requests/{laboratoryRequest}/approve-reflex-test', 
        [ResultManagementController::class, 'approveReflexTest']);
    Route::post('/results/laboratory-requests/{laboratoryRequest}/deny-reflex-test', 
        [ResultManagementController::class, 'denyReflexTest']);
    Route::get('/results/check-cds/{laboratoryRequest}', 
        [ResultManagementController::class, 'checkCdsRules']);
});
```

**Verify routes are loaded:**
```bash
cd backend
php artisan route:list | grep reflex
```

Should show:
```
POST | api/results/laboratory-requests/{laboratoryRequest}/approve-reflex-test
POST | api/results/laboratory-requests/{laboratoryRequest}/deny-reflex-test
GET | api/results/check-cds/{laboratoryRequest}
```

---

## 7. Post-Deployment Verification

### 7.1 Smoke Tests

**1. Login Test:**
- Obtain API token via `/api/login` or Sanctum
- Verify token works with a test endpoint

**2. Navigate to Result Management:**
- Open frontend application
- Navigate to Result Management section
- Select a test category (e.g., Chemistry, Hematology)

**3. Trigger Reflex Workflow:**
- Open a laboratory request with test results
- Enter a result that triggers the CDS rule (e.g., Glucose > 200)
- Save the result
- Verify CDS check is automatically called

**4. Verify Approval Modal:**
- Check that a modal/alert appears with reflex suggestion
- Modal should show:
  - Alert message from CDS rule
  - "Approve" and "Deny" buttons
  - Information about the suggested reflex test

**5. Approve Reflex Test:**
- Click "Approve" button
- Verify API call succeeds (200 OK)
- Verify success message appears

**6. Verify New Reflex Request:**
- Refresh worklist
- Check for new request with:
  - "REFLEX" badge or indicator
  - Parent request ID displayed
  - Correct test (e.g., HbA1c) included

### 7.2 Database Queries for Verification

**Check reflex requests were created:**
```sql
-- Replace <parent_request_id> with actual ID
SELECT 
    lr.id,
    lr.sample_id,
    lr.is_reflex,
    lr.reflex_parent_request_id,
    lr.reflex_triggered_by_rule_id,
    lr.priority_level,
    lr.created_at
FROM laboratory_requests lr
WHERE lr.is_reflex = 1 
  AND lr.reflex_parent_request_id = <parent_request_id>
ORDER BY lr.created_at DESC;
```

**Check reflex test items:**
```sql
SELECT 
    lrt.id,
    lrt.laboratory_request_id,
    lrt.test_id,
    t.name as test_name,
    lrt.is_reflex_test,
    lrt.reflex_triggered_at,
    lrt.reflex_triggered_by_rule_id
FROM laboratory_request_test lrt
JOIN tests t ON lrt.test_id = t.id
WHERE lrt.is_reflex_test = 1
  AND lrt.laboratory_request_id IN (
    SELECT id FROM laboratory_requests 
    WHERE is_reflex = 1 
      AND reflex_parent_request_id = <parent_request_id>
  )
ORDER BY lrt.laboratory_request_id, lrt.id;
```

**Check audit trail:**
```sql
SELECT 
    rda.id,
    rda.rule_type,
    rda.decision,
    rda.message,
    rda.rule_id,
    rda.laboratory_request_id,
    rda.user_id,
    rda.created_at,
    JSON_PRETTY(rda.context) as context
FROM rule_decision_audit rda
WHERE rda.laboratory_request_id = <parent_request_id>
  AND rda.decision IN ('reflex_created', 'reflex_denied')
ORDER BY rda.created_at DESC;
```

**Verify parent-child relationship:**
```sql
-- Check that reflex request inherits correct fields from parent
SELECT 
    parent.id as parent_id,
    parent.sample_id as parent_sample,
    parent.patient_id,
    parent.doctor_id,
    parent.priority_level as parent_priority,
    child.id as child_id,
    child.sample_id as child_sample,
    child.priority_level as child_priority,
    child.is_reflex
FROM laboratory_requests parent
JOIN laboratory_requests child ON child.reflex_parent_request_id = parent.id
WHERE parent.id = <parent_request_id>
  AND child.is_reflex = 1;
```

---

## 8. Monitoring and Logging

### 8.1 Check Application Logs

```bash
cd backend
tail -f storage/logs/laravel.log
```

**Look for reflex-related entries:**
```
[2025-06-18 10:30:00] local.INFO: Reflex laboratory request created
[2025-06-18 10:30:00] local.INFO: Reflex test already exists
[2025-06-18 10:30:00] local.ERROR: Failed to create reflex tests
```

**Filter reflex logs:**
```bash
tail -f storage/logs/laravel.log | grep -i reflex
```

### 8.2 Monitor Reflex Activity

**Daily reflex test count:**
```sql
SELECT 
    DATE(created_at) as date,
    COUNT(*) as reflex_count
FROM laboratory_requests
WHERE is_reflex = 1
  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

**Most common reflex rules:**
```sql
SELECT 
    r.name as rule_name,
    COUNT(lr.id) as reflex_count
FROM laboratory_requests lr
JOIN cds_correlation_rules r ON lr.reflex_triggered_by_rule_id = r.id
WHERE lr.is_reflex = 1
GROUP BY r.id, r.name
ORDER BY reflex_count DESC
LIMIT 10;
```

**Reflex approval/denial ratio:**
```sql
SELECT 
    decision,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
FROM rule_decision_audit
WHERE rule_type = 'CDS'
  AND decision IN ('reflex_created', 'reflex_denied')
  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY decision;
```

**Reflex requests by priority:**
```sql
SELECT 
    priority_level,
    COUNT(*) as count
FROM laboratory_requests
WHERE is_reflex = 1
GROUP BY priority_level
ORDER BY FIELD(priority_level, 'STAT', 'URGENT', 'ROUTINE');
```

---

## 9. Troubleshooting

### 9.1 Common Issues

#### Issue: Reflex approval modal not appearing

**Possible Causes & Solutions:**

1. **CDS rule not configured correctly**
   ```sql
   -- Check rule has action_type = 'REFLEX_TEST'
   SELECT id, rule_name, action_type, reflex_test_id 
   FROM cds_correlation_rules 
   WHERE is_active = 1;
   
   -- Ensure reflex_test_id is populated
   ```

2. **Rule decision audit already has reflex_created entry**
   ```sql
   SELECT * FROM rule_decision_audit 
   WHERE laboratory_request_id = <request_id>
     AND rule_id = <rule_id>
     AND decision = 'reflex_created';
   ```

3. **Frontend JavaScript errors**
   - Open browser console (F12)
   - Check for errors in UniversalResultModal.vue or ViolationsSummaryModal.vue
   - Verify API call to `check-cds` endpoint succeeds

4. **CDS check endpoint not returning reflex rules**
   ```bash
   # Test endpoint manually
   curl -H "Authorization: Bearer {token}" \
        http://your-domain.com/api/results/check-cds/{requestId}
   
   # Should return triggered_rules with action_type: 'REFLEX_TEST'
   ```

#### Issue: Reflex test not created after approval

**Possible Causes & Solutions:**

1. **API call failed**
   - Check network tab in browser dev tools
   - Verify response status code
   - Check `laravel.log` for errors

2. **Database transaction rolled back**
   ```bash
   tail -f backend/storage/logs/laravel.log | grep -A 5 "Failed to create reflex"
   ```

3. **Test parameter doesn't exist or invalid**
   ```sql
   -- Verify reflex_test_id points to valid test_parameter
   SELECT id FROM test_parameters WHERE id = <reflex_test_id>;
   ```

4. **Package expansion logic issue**
   - Check if test belongs to package
   - Verify package has associated tests
   - See `ReflexTestService::getTestsForReflexParameter()` logs

#### Issue: Reflex request not appearing in worklist

**Possible Causes & Solutions:**

1. **Database query filtering**
   - Verify `is_reflex = true` in database
   - Check worklist query includes reflex requests
   - In `CategoryResults.vue`, ensure filter logic is correct

2. **Frontend not refreshing**
   - Check network calls after approval
   - Verify worklist reload or WebSocket update
   - Check Vue component reactivity

3. **API endpoint not returning reflex requests**
   ```bash
   # Test worklist API
   curl -H "Authorization: Bearer {token}" \
        "http://your-domain.com/api/results/category/{categoryId}?include_reflex=1"
   ```

#### Issue: Foreign key constraint violation

**Solution:**
```sql
-- Ensure referenced records exist
SELECT id FROM cds_correlation_rules WHERE id = {rule_id};
SELECT id FROM laboratory_requests WHERE id = {parent_id};

-- If missing, create them or fix the IDs
```

### 9.2 Enable Debug Mode

**Temporarily enable debug:**
```env
# In .env
APP_DEBUG=true
```

**Check detailed logs:**
```bash
cd backend
php artisan tail  # If using Laravel Telescope or similar
# Or
tail -f storage/logs/laravel.log
```

**Add debug logging to ReflexTestService:**
```php
// In backend/app/Services/ReflexTestService.php
Log::debug('ReflexTestService debug', [
    'parent_request_id' => $parentRequest->id,
    'rule_id' => $rule->id,
    'reflex_test_id' => $rule->reflex_test_id,
    'user_id' => $user->id
]);
```

---

## 10. Rollback Plan

### 10.1 Code Rollback

**Using Git:**
```bash
cd /path/to/project
git status  # Check current state
git log --oneline -5  # See recent commits

# Revert to previous commit
git revert HEAD  # Creates new commit undoing changes
# OR
git reset --hard HEAD~1  # Discards last commit (use with caution)
```

**Manual rollback (if not using Git):**
1. Backup current modified files
2. Restore original files from backup
3. Remove reflex-specific routes from `backend/routes/api.php`
4. Remove reflex-related components from frontend

### 10.2 Database Rollback

```bash
cd backend
php artisan migrate:rollback --step=2
```

**Expected Output:**
```
Rolling back: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
Rolled back:  2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
Rolling back: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
Rolled back:  2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
```

**Verify rollback:**
```sql
SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
-- Should return empty set

SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
-- Should return empty set
```

**Application behavior after rollback:**
- Reflex endpoints return 404 or method not found
- Frontend reflex UI elements should be hidden/disabled
- Core LIS functionality continues to work
- No crashes or database errors

### 10.3 Data Cleanup (if needed)

**Remove all reflex test data:**
```sql
-- Delete reflex requests
DELETE FROM laboratory_requests 
WHERE is_reflex = 1;

-- Delete reflex test items
DELETE FROM laboratory_request_test 
WHERE is_reflex_test = 1;

-- Delete reflex audit entries
DELETE FROM rule_decision_audit 
WHERE decision IN ('reflex_created', 'reflex_denied');
```

**⚠️ Warning:** This permanently deletes all reflex-related data. Ensure you have backups if needed.

---

## 11. Performance Considerations

### 11.1 Database Indexes

**Add indexes for better query performance:**

```sql
-- laboratory_requests indexes
CREATE INDEX idx_laboratory_requests_reflex_parent 
    ON laboratory_requests(reflex_parent_request_id);
    
CREATE INDEX idx_laboratory_requests_reflex_rule 
    ON laboratory_requests(reflex_triggered_by_rule_id);
    
CREATE INDEX idx_laboratory_requests_is_reflex 
    ON laboratory_requests(is_reflex);

-- laboratory_request_test indexes
CREATE INDEX idx_laboratory_request_test_reflex_rule 
    ON laboratory_request_test(reflex_triggered_by_rule_id);
    
CREATE INDEX idx_laboratory_request_test_is_reflex 
    ON laboratory_request_test(is_reflex_test);

-- rule_decision_audit indexes
CREATE INDEX idx_rule_decision_audit_parent_rule 
    ON rule_decision_audit(laboratory_request_id, rule_id, decision);
    
CREATE INDEX idx_rule_decision_audit_created_at 
    ON rule_decision_audit(created_at);
```

**Verify indexes exist:**
```sql
SHOW INDEX FROM laboratory_requests;
SHOW INDEX FROM laboratory_request_test;
SHOW INDEX FROM rule_decision_audit;
```

### 11.2 Query Optimization

**Worklist queries should include:**
- Index on `is_reflex` for filtering
- Composite index on `(reflex_parent_request_id, is_reflex)` for parent-child queries
- Index on `laboratory_request_test.laboratory_request_id` for joining

**Monitor slow queries:**
```sql
-- Enable slow query log if not already
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Check slow queries
SELECT * FROM mysql.slow_log 
WHERE query LIKE '%is_reflex%'
ORDER BY start_time DESC;
```

### 11.3 Caching Considerations

**Cache frequently accessed data:**
- CDS rules (active rules cache)
- Test parameters and categories
- User permissions

**Laravel cache configuration:**
```php
// In config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
],
```

**Cache CDS rules in ReflexTestService:**
```php
public function getActiveCdsRules()
{
    return Cache::remember('active_cds_rules', 3600, function () {
        return CdsCorrelationRule::active()->get();
    });
}
```

---

## 12. Security Considerations

### 12.1 Authentication & Authorization

- All reflex endpoints require `auth:sanctum` middleware
- Verify user is authenticated before processing
- Check user permissions to order tests:
  ```php
  // In approveReflexTest()
  if (!$user->can('order-tests')) {
      return response()->json(['message' => 'Unauthorized'], 403);
  }
  ```

### 12.2 Input Validation

**Laravel validation rules:**
```php
$validated = $request->validate([
    'rule_id' => 'required|integer|exists:cds_correlation_rules,id',
    'reflex_test_id' => 'required|integer|exists:test_parameters,id',
    'auto_create' => 'boolean',
    'reason' => 'nullable|string|max:500',
]);
```

**Prevent SQL injection:**
- Use Eloquent ORM or parameterized queries
- Never concatenate user input into SQL strings

### 12.3 Audit Trail

All reflex decisions are logged in `rule_decision_audit`:
- `user_id` - Who made the decision
- `laboratory_request_id` - Parent request
- `rule_id` - Which rule triggered
- `decision` - 'reflex_created' or 'reflex_denied'
- `context` - JSON with additional details
- `created_at` - Timestamp

**Verify audit entries:**
```sql
SELECT * FROM rule_decision_audit 
WHERE decision IN ('reflex_created', 'reflex_denied')
ORDER BY created_at DESC
LIMIT 10;
```

### 12.4 Data Integrity

- Foreign keys with `ON DELETE SET NULL` prevent orphaned records
- Database transactions ensure atomic operations
- Duplicate prevention via `hasExistingReflex()` check

---

## 13. Training and Documentation

### 13.1 End-User Training

**For Clinicians:**
1. How to enter results that trigger CDS rules
2. How to interpret reflex suggestions
3. How to approve or deny reflex tests
4. Understanding audit trail

**For Laboratory Staff:**
1. Identifying reflex requests in worklist
2. Processing reflex tests normally
3. Viewing parent request context
4. Using "Show Only Reflex Tests" filter

### 13.2 Reference Documentation

- **API Documentation**: `REFLEX_TEST_API_DOCUMENTATION.md`
- **Testing Guide**: `REFLEX_TEST_TESTING_GUIDE.md`
- **This Document**: `REFLEX_TEST_MIGRATION_AND_DEPLOYMENT.md`

### 13.3 Support Contacts

- **Development Team**: [Your team contact]
- **System Administrator**: [Admin contact]
- **Documentation Issues**: [Report to]

---

## 14. Appendix

### 14.1 Migration File Details

**Migration 1: `2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php`**
- Adds 3 columns to `laboratory_requests`
- Creates 2 foreign key constraints
- Provides rollback (drops columns and foreign keys)

**Migration 2: `2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php`**
- Adds 3 columns to `laboratory_request_test`
- Creates 1 foreign key constraint
- Provides rollback

### 14.2 Key Service Methods

**ReflexTestService:**
- `createReflexTests()` - Main entry point for creating reflex requests
- `getTestsForReflexParameter()` - Resolves test parameter to tests (handles packages)
- `groupTestsByCategory()` - Groups tests by category for multi-request creation
- `createSingleReflexRequest()` - Creates one reflex laboratory request
- `hasExistingReflex()` - Prevents duplicate reflex creation

### 14.3 API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/results/check-cds/{id}` | GET | Check CDS rules for a request |
| `/api/results/laboratory-requests/{id}/approve-reflex-test` | POST | Approve and create reflex tests |
| `/api/results/laboratory-requests/{id}/deny-reflex-test` | POST | Deny reflex test suggestion |

### 14.4 Database Tables Summary

| Table | New Columns | Purpose |
|-------|-------------|---------|
| `laboratory_requests` | `is_reflex`, `reflex_parent_request_id`, `reflex_triggered_by_rule_id` | Track reflex requests and lineage |
| `laboratory_request_test` | `is_reflex_test`, `reflex_triggered_at`, `reflex_triggered_by_rule_id` | Track reflex test items |
| `rule_decision_audit` | (no new columns) | Audit reflex decisions |

---

## Document Information

**Version**: 1.0  
**Last Updated**: 2025-06-18  
**Applicable System**: LIS (Laboratory Information System)  
**Author**: Development Team  
**Review Date**: [Insert review date]

---

**End of Migration and Deployment Guide**
