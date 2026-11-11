# Laboratory Information System (LIS)

## System Overview

A comprehensive Laboratory Information System built with a modern web stack, designed to manage laboratory operations including patient management, test ordering, result processing, quality control, equipment management, and report generation.

**Technology Stack:**
- **Backend:** Laravel (PHP 8+)
- **Frontend:** Vue.js 3 with Pinia state management
- **Database:** MySQL
- **Authentication:** Laravel Sanctum
- **Styling:** Custom CSS with global design system

---

## System Architecture

### High-Level Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Vue.js        │    │   Laravel       │    │   MySQL        │
│   Frontend      │◄──►│   REST API      │◄──►│   Database     │
│   (Port 5173)   │    │   (Port 8000)   │    │                │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Project Structure

```
lis-system/
├── backend/                 # Laravel backend application
│   ├── app/
│   │   ├── Models/         # Eloquent ORM models (40+ models)
│   │   ├── Services/       # Business logic services
│   │   ├── Repositories/   # Data access layer
│   │   ├── Observers/      # Model event observers
│   │   └── Providers/      # Service providers
│   ├── database/
│   │   ├── migrations/     # Database schema definitions
│   │   └── seeders/        # Database seeders
│   ├── routes/
│   │   ├── api.php         # REST API routes
│   │   ├── web.php         # Web routes
│   │   └── web_print_routes.php  # Print-specific routes
│   ├── config/             # Configuration files
│   └── resources/views/    # Blade templates
│
├── frontend/               # Vue.js frontend application
│   ├── src/
│   │   ├── components/     # Reusable Vue components
│   │   │   ├── ReportElements/  # Report builder components
│   │   │   └── ReportRenderer.vue
│   │   ├── views/          # Page components
│   │   ├── stores/         # Pinia state stores
│   │   ├── utils/          # Utility functions
│   │   ├── layouts/        # Layout components
│   │   └── router/         # Vue Router configuration
│   └── public/
│
├── db/                     # Database SQL files
│   ├── lis_db.sql          # Complete database schema
│   └── lis_db_v1.sql       # Version 1 schema
│
└── Documentation/          # Various documentation files
    ├── WYSIWYG_QUICK_REFERENCE.md
    ├── WYSIWYG_REPORT_BUILDER_IMPLEMENTATION.md
    ├── REPORT_PREVIEW_FIX.md
    └── PRINT_*.md          # Print system documentation
```

---

## Core Modules (Grouped by Function)

### 1. PATIENT MANAGEMENT MODULE

**Purpose:** Manage patient demographics, medical history, and patient-related operations.

#### Models:
- [`Patient.php`](backend/app/Models/Patient.php) - Core patient information
- [`MedicalHistory.php`](backend/app/Models/MedicalHistory.php) - Patient medical history
- [`PhysicalExam.php`](backend/app/Models/PhysicalExam.php) - Physical examination records
- [`Diagnosis.php`](backend/app/Models/Diagnosis.php) - Patient diagnoses
- [`Consultation.php`](backend/app/Models/Consultation.php) - Consultation records
- [`ConsultationLabRequest.php`](backend/app/Models/ConsultationLabRequest.php) - Lab requests from consultations
- [`PatientType.php`](backend/app/Models/PatientType.php) - Patient type classification (inpatient/outpatient)

#### Frontend Views:
- [`Frontdesk.vue`](frontend/src/views/Frontdesk.vue) - Front desk operations
- Patient registration and management interface

#### Stores:
- [`patient.js`](frontend/src/stores/patient.js) - Patient state management

---

### 2. LABORATORY REQUEST & TEST MANAGEMENT MODULE

**Purpose:** Handle test ordering, laboratory requests, and test management.

#### Models:
- [`LaboratoryRequest.php`](backend/app/Models/LaboratoryRequest.php) - Main lab request
- [`LaboratoryRequestTest.php`](backend/app/Models/LaboratoryRequestTest.php) - Individual tests within a request
- [`Test.php`](backend/app/Models/Test.php) - Test definitions
- [`TestCategory.php`](backend/app/Models/TestCategory.php) - Test categorization
- [`TestParameter.php`](backend/app/Models/TestParameter.php) - Test parameters
- [`TestResult.php`](backend/app/Models/TestResult.php) - Individual test results
- [`TestTurnaroundTime.php`](backend/app/Models/TestTurnaroundTime.php) - TAT tracking

#### Frontend Views:
- [`Phlebotomy.vue`](frontend/src/views/Phlebotomy.vue) - Sample collection
- [`Tests.vue`](frontend/src/views/Tests.vue) - Test management
- [`TestCategories.vue`](frontend/src/views/TestCategories.vue) - Category management
- [`TestResults.vue`](frontend/src/views/TestResults.vue) - Result entry
- [`CategoryResults.vue`](frontend/src/views/ResultManagement/CategoryResults.vue) - Category-based results

#### Stores:
- [`laboratoryRequest.js`](frontend/src/stores/laboratoryRequest.js) - Lab request state
- [`test.js`](frontend/src/stores/test.js) - Test data state
- [`testCategory.js`](frontend/src/stores/testCategory.js) - Category state
- [`testResult.js`](frontend/src/stores/testResult.js) - Result state

---

### 3. QUALITY CONTROL MODULE

**Purpose:** Comprehensive quality control management with delta checks, QC rules, and sigma metrics.

#### Models:
- [`QualityControl.php`](backend/app/Models/QualityControl.php) - QC records
- [`QualityControlResult.php`](backend/app/Models/QualityControlResult.php) - QC results
- [`QualityControlTestParameter.php`](backend/app/Models/QualityControlTestParameter.php) - QC test parameters
- [`QcRule.php`](backend/app/Models/QcRule.php) - QC rules (Westgard rules)
- [`QcSigmaMetricResult.php`](backend/app/Models/QcSigmaMetricResult.php) - Sigma metric calculations
- [`QcAnalyteSigmaConfig.php`](backend/app/Models/QcAnalyteSigmaConfig.php) - Sigma configuration
- [`DeltaCheckRule.php`](backend/app/Models/DeltaCheckRule.php) - Delta check rules
- [`DeltaCheckParameter.php`](backend/app/Models/DeltaCheckParameter.php) - Delta check parameters
- [`DeltaCheckQualityControl.php`](backend/app/Models/DeltaCheckQualityControl.php) - QC delta checks
- [`DeltaCheckClinicalSignificance.php`](backend/app/Models/DeltaCheckClinicalSignificance.php) - Clinical significance
- [`DeltaCheckSeverityLevel.php`](backend/app/Models/DeltaCheckSeverityLevel.php) - Severity levels
- [`DeltaCheckPreventiveMeasure.php`](backend/app/Models/DeltaCheckPreventiveMeasure.php) - Preventive measures
- [`DeltaCheckAction.php`](backend/app/Models/DeltaCheckAction.php) - Actions taken
- [`DeltaCheckRecommendationTemplate.php`](backend/app/Models/DeltaCheckRecommendationTemplate.php) - Recommendation templates
- [`DeltaCheckGlobalRecommendation.php`](backend/app/Models/DeltaCheckGlobalRecommendation.php) - Global recommendations
- [`DeltaCheckRecommendationLog.php`](backend/app/Models/DeltaCheckRecommendationLog.php) - Recommendation logs
- [`CorrectiveAction.php`](backend/app/Models/CorrectiveAction.php) - Corrective actions

#### Services:
- [`DeltaCheckService.php`](backend/app/Services/DeltaCheckService.php) - Delta check processing
- [`DeltaCheckRecommendationService.php`](backend/app/Services/DeltaCheckRecommendationService.php) - Recommendation engine
- [`DatabaseDeltaCheckRecommendationService.php`](backend/app/Services/DatabaseDeltaCheckRecommendationService.php) - DB-based recommendations
- [`SigmaMetricCalculator.php`](backend/app/Services/SigmaMetricCalculator.php) - Sigma metric calculations
- [`QualityControlLockoutService.php`](backend/app/Services/QualityControlLockoutService.php) - QC lockout management
- [`CalculationEngine.php`](backend/app/Services/CalculationEngine.php) - Statistical calculations

#### Repositories:
- [`DeltaCheckRecommendationRepository.php`](backend/app/Repositories/DeltaCheckRecommendationRepository.php) - Recommendation data access

#### Frontend Views:
- [`QCLog.vue`](frontend/src/views/ResultManagement/QCLog.vue) - QC logging
- [`DeltaCheck.vue`](frontend/src/views/ResultManagement/DeltaCheck.vue) - Delta check interface
- [`ResultManagementDashboard.vue`](frontend/src/views/ResultManagement/ResultManagementDashboard.vue) - Results dashboard

#### Stores:
- [`qualityControl.js`](frontend/src/stores/qualityControl.js) - QC state management

#### Admin Views:
- [`Admin/DeltaCheckRules.vue`](frontend/src/views/Admin/DeltaCheckRules.vue) - Delta check rule management
- [`Admin/DeltaCheckQcRules.vue`](frontend/src/views/Admin/DeltaCheckQcRules.vue) - QC delta check rules
- [`Admin/QcRules.vue`](frontend/src/views/Admin/QcRules.vue) - QC rule management
- [`Admin/CdsRules.vue`](frontend/src/views/Admin/CdsRules.vue) - CDS correlation rules

---

### 4. EQUIPMENT & CONSUMABLES MANAGEMENT MODULE

**Purpose:** Equipment lifecycle management, consumables tracking, and inventory control.

#### Models:
- [`Equipment.php`](backend/app/Models/Equipment.php) - Equipment master data
- [`Analyzer.php`](backend/app/Models/Analyzer.php) - Laboratory analyzers
- [`EquipmentReagentAssignment.php`](backend/app/Models/EquipmentReagentAssignment.php) - Reagent-equipment assignments
- [`EquipmentUsage.php`](backend/app/Models/EquipmentUsage.php) - Equipment usage tracking
- [`MaintenanceSchedule.php`](backend/app/Models/MaintenanceSchedule.php) - Maintenance scheduling
- [`ServiceLog.php`](backend/app/Models/ServiceLog.php) - Service records
- [`Consumable.php`](backend/app/Models/Consumable.php) - Consumable items
- [`ConsumableUsage.php`](backend/app/Models/ConsumableUsage.php) - Consumable usage
- [`MaterialMaster.php`](backend/app/Models/MaterialMaster.php) - Material master data
- [`PurchaseOrder.php`](backend/app/Models/PurchaseOrder.php) - Purchase orders
- [`PurchaseOrderItem.php`](backend/app/Models/PurchaseOrderItem.php) - PO line items
- [`LotStockRecord.php`](backend/app/Models/LotStockRecord.php) - Lot-based inventory
- [`LotUsageHistory.php`](backend/app/Models/LotUsageHistory.php) - Lot usage tracking
- [`Vendor.php`](backend/app/Models/Vendor.php) - Vendor management

#### Frontend Views:
- [`EquipmentManagement/ConsumablesManagement.vue`](frontend/src/views/EquipmentManagement/ConsumablesManagement.vue) - Consumables management
- [`InventoryManagement/`](frontend/src/views/InventoryManagement/) - Inventory submodule
  - `IMMDashboard.vue` - Inventory dashboard
  - `LotsManagement.vue` - Lot tracking
  - `MaterialsManagement.vue` - Materials management
  - `EquipmentUsage.vue` - Equipment usage
  - `ReportsManagement.vue` - Inventory reports
  - `VendorsManagement.vue` - Vendor management
- [`ConsumablesManagement/ConsumablesDashboard.vue`](frontend/src/views/ConsumablesManagement/ConsumablesDashboard.vue) - Consumables dashboard

#### Observers:
- [`EquipmentObserver.php`](backend/app/Observers/EquipmentObserver.php) - Equipment event handling
- [`ServiceLogObserver.php`](backend/app/Observers/ServiceLogObserver.php) - Service log events

---

### 5. REPORTING & REPORT DESIGNER MODULE

**Purpose:** Advanced report generation with WYSIWYG designer and dynamic templates.

#### Components:
- [`ReportDesigner.vue`](frontend/src/components/ReportDesigner.vue) - Main report designer
- [`ReportRenderer.vue`](frontend/src/components/ReportRenderer.vue) - Report rendering engine
- [`ReportTemplatesList.vue`](frontend/src/components/ReportTemplatesList.vue) - Template management

#### Report Elements:
- [`ReportElements/ReportContainer.vue`](frontend/src/components/ReportElements/ReportContainer.vue) - Container element
- [`ReportElements/ReportText.vue`](frontend/src/components/ReportElements/ReportText.vue) - Text element
- [`ReportElements/ReportImage.vue`](frontend/src/components/ReportElements/ReportImage.vue) - Image element
- [`ReportElements/ReportShape.vue`](frontend/src/components/ReportElements/ReportShape.vue) - Shape element
- [`ReportElements/ReportGrid.vue`](frontend/src/components/ReportElements/ReportGrid.vue) - Grid layout
- [`ReportElements/ReportDataTable.vue`](frontend/src/components/ReportElements/ReportDataTable.vue) - Data tables
- [`ReportElements/ReportDynamicText.vue`](frontend/src/components/ReportElements/ReportDynamicText.vue) - Dynamic text
- [`ReportElements/ReportSignatureBlock.vue`](frontend/src/components/ReportElements/ReportSignatureBlock.vue) - Signature blocks
- [`ReportElements/HeaderBlock.vue`](frontend/src/components/ReportElements/HeaderBlock.vue) - Header blocks
- [`ReportElements/PatientInfoBlock.vue`](frontend/src/components/ReportElements/PatientInfoBlock.vue) - Patient info blocks
- [`ReportElements/RichTextBlock.vue`](frontend/src/components/ReportElements/RichTextBlock.vue) - Rich text editor
- [`ReportElements/DesignerElementWrapper.vue`](frontend/src/components/ReportElements/DesignerElementWrapper.vue) - Designer wrapper

#### Utilities:
- [`utils/layoutDefinitions.js`](frontend/src/utils/layoutDefinitions.js) - Layout definitions
- [`utils/reportFormatters.js`](frontend/src/utils/reportFormatters.js) - Report formatting utilities

#### Services:
- [`ReportService.php`](backend/app/Services/ReportService.php) - Report generation service
- [`PrintService.php`](backend/app/Services/PrintService.php) - Print service

#### Backend Views:
- [`resources/views/reports/canvas_renderer.blade.php`](backend/resources/views/reports/canvas_renderer.blade.php) - Canvas rendering

#### Routes:
- [`web_print_routes.php`](backend/routes/web_print_routes.php) - Print-specific routes

---

### 6. DOCTOR & USER MANAGEMENT MODULE

**Purpose:** Healthcare provider and system user management.

#### Models:
- [`Doctor.php`](backend/app/Models/Doctor.php) - Doctor information
- [`User.php`](backend/app/Models/User.php) - System users
- [`UserRole.php`](backend/app/Models/UserRole.php) - User roles
- [`Ward.php`](backend/app/Models/Ward.php) - Ward management
- [`Room.php`](backend/app/Models/Room.php) - Room management

#### Stores:
- [`doctor.js`](frontend/src/stores/doctor.js) - Doctor state management

---

### 7. FINANCIAL & PAYMENT MODULE

**Purpose:** Billing, payments, and financial tracking.

#### Models:
- [`Payment.php`](backend/app/Models/Payment.php) - Payment records
- [`Invoice.php`](backend/app/Models/Invoice.php) - Invoice management
- [`Billing.php`](backend/app/Models/Billing.php) - Billing records

#### Stores:
- [`payment.js`](frontend/src/stores/payment.js) - Payment state management

---

### 8. PRESCRIPTION & MEDICAL CERTIFICATE MODULE

**Purpose:** Prescription management and medical certificate generation.

#### Models:
- [`Prescription.php`](backend/app/Models/Prescription.php) - Prescription records
- [`MedicalCertificate.php`](backend/app/Models/MedicalCertificate.php) - Medical certificates

---

### 9. REFERENCE RANGES & TEST PARAMETERS MODULE

**Purpose:** Manage reference ranges and test parameter configurations.

#### Models:
- [`ReferenceRange.php`](backend/app/Models/ReferenceRange.php) - Reference ranges
- [`TestParameter.php`](backend/app/Models/TestParameter.php) - Test parameters
- [`SampleType.php`](backend/app/Models/SampleType.php) - Sample types

#### Seeders:
- [`ComprehensiveTestParameterSeeder.php`](backend/database/seeders/ComprehensiveTestParameterSeeder.php)
- [`ComprehensiveReferenceRangeSeeder.php`](backend/database/seeders/ComprehensiveReferenceRangeSeeder.php)
- [`TestParameterSeeder.php`](backend/database/seeders/TestParameterSeeder.php)
- [`ReferenceRangeSeeder.php`](backend/database/seeders/ReferenceRangeSeeder.php)
- [`ReferenceRangeAnalyzerSeeder.php`](backend/database/seeders/ReferenceRangeAnalyzerSeeder.php)

---

### 10. CDS (CLINICAL DECISION SUPPORT) MODULE

**Purpose:** Clinical decision support with correlation rules and alerts.

#### Models:
- [`CdsCorrelationRule.php`](backend/app/Models/CdsCorrelationRule.php) - CDS correlation rules

#### Services:
- [`CdsRuleEngine.php`](backend/app/Services/CdsRuleEngine.php) - CDS rule processing

#### Admin Views:
- [`Admin/CdsRules.vue`](frontend/src/views/Admin/CdsRules.vue) - CDS rule management

---

### 11. FORMULA BUILDER MODULE

**Purpose:** Create and manage calculation formulas for test results.

#### Frontend Views:
- [`FormulaBuilder/FormulaBuilder.vue`](frontend/src/views/FormulaBuilder/FormulaBuilder.vue) - Formula builder interface
- [`FormulaBuilder/FormulaManagement.vue`](frontend/src/views/FormulaBuilder/FormulaManagement.vue) - Formula management

---

### 12. DASHBOARD & ANALYTICS MODULE

**Purpose:** System-wide dashboard and analytics.

#### Frontend Views:
- [`Dashboard.vue`](frontend/src/views/Dashboard.vue) - Main dashboard
- [`Dashboard-old.vue`](frontend/src/views/Dashboard-old.vue) - Legacy dashboard
- [`ResultManagementDashboard.vue`](frontend/src/views/ResultManagement/ResultManagementDashboard.vue) - Results dashboard
- [`ConsumablesDashboard.vue`](frontend/src/views/ConsumablesManagement/ConsumablesDashboard.vue) - Consumables dashboard
- [`IMMDashboard.vue`](frontend/src/views/InventoryManagement/IMMDashboard.vue) - Inventory dashboard

#### Stores:
- [`dashboard.js`](frontend/src/stores/dashboard.js) - Dashboard state management

---

### 13. ROOM & WARD MANAGEMENT MODULE

**Purpose:** Manage laboratory rooms and wards.

#### Models:
- [`Room.php`](backend/app/Models/Room.php) - Room management
- [`Ward.php`](backend/app/Models/Ward.php) - Ward management

#### Frontend Views:
- [`RoomManagement.vue`](frontend/src/views/RoomManagement.vue) - Room management interface

---

## Database Schema

### Core Tables (40+ tables)

**Patient Management:**
- `patients` - Patient demographics
- `medical_histories` - Medical history
- `physical_exams` - Physical examinations
- `diagnoses` - Diagnoses
- `consultations` - Consultations
- `consultation_lab_requests` - Consultation lab requests
- `patient_types` - Patient type classifications

**Laboratory Operations:**
- `laboratory_requests` - Lab requests
- `laboratory_request_tests` - Request test items
- `tests` - Test definitions
- `test_categories` - Test categories
- `test_parameters` - Test parameters
- `test_results` - Test results
- `test_turnaround_times` - TAT tracking
- `sample_types` - Sample type definitions

**Quality Control:**
- `quality_controls` - QC records
- `quality_control_results` - QC results
- `quality_control_test_parameters` - QC test parameters
- `qc_rules` - QC rules
- `qc_sigma_metric_results` - Sigma metrics
- `qc_analyte_sigma_configs` - Sigma configurations
- `delta_check_rules` - Delta check rules
- `delta_check_parameters` - Delta check parameters
- `delta_check_quality_controls` - QC delta checks
- `delta_check_clinical_significances` - Clinical significance
- `delta_check_severity_levels` - Severity levels
- `delta_check_preventive_measures` - Preventive measures
- `delta_check_actions` - Actions
- `delta_check_recommendation_templates` - Recommendation templates
- `delta_check_global_recommendations` - Global recommendations
- `delta_check_recommendation_logs` - Recommendation logs
- `corrective_actions` - Corrective actions

**Equipment & Inventory:**
- `equipment` - Equipment master
- `analyzers` - Laboratory analyzers
- `equipment_reagent_assignments` - Reagent assignments
- `equipment_usage` - Usage tracking
- `maintenance_schedules` - Maintenance schedules
- `service_logs` - Service records
- `consumables` - Consumable items
- `consumable_usage` - Consumable usage
- `material_master` - Material master
- `purchase_orders` - Purchase orders
- `purchase_order_items` - PO items
- `lot_stock_records` - Lot inventory
- `lot_usage_history` - Lot usage
- `vendors` - Vendor information

**Users & Access:**
- `users` - System users
- `user_roles` - User roles
- `doctors` - Doctor information
- `wards` - Wards
- `rooms` - Rooms

**Financial:**
- `payments` - Payment records
- `invoices` - Invoices
- `billings` - Billing records

**Documents:**
- `medical_certificates` - Medical certificates
- `prescriptions` - Prescriptions
- `reports` - Report templates
- `report_elements` - Report elements

**CDS:**
- `cds_correlation_rules` - CDS rules

---

## API Structure

### REST API Endpoints

**Authentication:**
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `POST /api/user` - Get authenticated user

**Patient Management:**
- `GET /api/patients` - List patients
- `POST /api/patients` - Create patient
- `GET /api/patients/{id}` - Get patient
- `PUT /api/patients/{id}` - Update patient
- `DELETE /api/patients/{id}` - Delete patient
- `GET /api/patients/{id}/medical-history` - Get medical history
- `POST /api/patients/{id}/medical-history` - Create medical history

**Laboratory Requests:**
- `GET /api/laboratory-requests` - List lab requests
- `POST /api/laboratory-requests` - Create lab request
- `GET /api/laboratory-requests/{id}` - Get request
- `PUT /api/laboratory-requests/{id}` - Update request
- `GET /api/laboratory-requests/{id}/tests` - Get request tests
- `POST /api/laboratory-requests/{id}/tests` - Add test to request

**Tests:**
- `GET /api/tests` - List tests
- `POST /api/tests` - Create test
- `GET /api/tests/{id}` - Get test
- `PUT /api/tests/{id}` - Update test
- `GET /api/test-categories` - List categories
- `POST /api/test-categories` - Create category

**Test Results:**
- `GET /api/test-results` - List results
- `POST /api/test-results` - Enter result
- `GET /api/test-results/{id}` - Get result
- `PUT /api/test-results/{id}` - Update result
- `GET /api/category-results/{categoryId}` - Get category results

**Quality Control:**
- `GET /api/quality-controls` - List QC records
- `POST /api/quality-controls` - Create QC
- `GET /api/quality-controls/{id}/results` - Get QC results
- `POST /api/quality-controls/{id}/results` - Add QC result
- `GET /api/delta-checks` - List delta checks
- `POST /api/delta-checks/evaluate` - Evaluate delta check
- `GET /api/sigma-metrics` - Get sigma metrics
- `GET /api/qc-rules` - List QC rules
- `POST /api/qc-rules` - Create QC rule

**Equipment:**
- `GET /api/equipment` - List equipment
- `POST /api/equipment` - Create equipment
- `GET /api/equipment/{id}` - Get equipment
- `PUT /api/equipment/{id}` - Update equipment
- `GET /api/consumables` - List consumables
- `POST /api/consumables` - Create consumable
- `GET /api/inventory/lots` - List lot records
- `GET /api/maintenance-schedules` - List maintenance schedules

**Doctors:**
- `GET /api/doctors` - List doctors
- `POST /api/doctors` - Create doctor
- `GET /api/doctors/{id}` - Get doctor

**Reports:**
- `GET /api/reports/templates` - List report templates
- `POST /api/reports/templates` - Create template
- `GET /api/reports/templates/{id}` - Get template
- `PUT /api/reports/templates/{id}` - Update template
- `POST /api/reports/generate` - Generate report
- `GET /api/reports/print/{id}` - Print report

**Rooms:**
- `GET /api/rooms` - List rooms
- `POST /api/rooms` - Create room

---

## Frontend Application Structure

### Vue Router Routes

**Public Routes:**
- `/login` - Login page
- `/register` - Registration page

**Authenticated Routes:**
- `/dashboard` - Main dashboard
- `/frontdesk` - Front desk operations
- `/phlebotomy` - Sample collection
- `/tests` - Test management
- `/test-categories` - Category management
- `/test-results` - Result entry
- `/room-management` - Room management

**Result Management:**
- `/results/dashboard` - Results dashboard
- `/results/category/:id` - Category results
- `/results/delta-check` - Delta check review
- `/results/qc-log` - QC logging

**Admin:**
- `/admin/cds-rules` - CDS rule management
- `/admin/delta-check-rules` - Delta check rules
- `/admin/delta-check-qc-rules` - QC delta check rules
- `/admin/qc-rules` - QC rule management

**Equipment & Inventory:**
- `/equipment/consumables` - Consumables management
- `/inventory/dashboard` - Inventory dashboard
- `/inventory/lots` - Lot management
- `/inventory/materials` - Materials management
- `/inventory/equipment-usage` - Equipment usage
- `/inventory/reports` - Inventory reports
- `/inventory/vendors` - Vendor management
- `/consumables/dashboard` - Consumables dashboard

**Formula Builder:**
- `/formula-builder` - Formula builder
- `/formula-management` - Formula management

**Report Designer:**
- `/report-designer` - Report designer
- `/report-templates` - Template list

---

## Business Logic Services

### Backend Services

1. **[`CalculationEngine.php`](backend/app/Services/CalculationEngine.php)**
   - Statistical calculations for QC
   - Mean, SD, CV calculations
   - Reference range calculations

2. **[`CdsRuleEngine.php`](backend/app/Services/CdsRuleEngine.php)**
   - Clinical Decision Support rule processing
   - Correlation rule evaluation
   - Alert generation

3. **[`DeltaCheckService.php`](backend/app/Services/DeltaCheckService.php)**
   - Delta check evaluation
   - Patient result comparison
   - Change detection

4. **[`DeltaCheckRecommendationService.php`](backend/app/Services/DeltaCheckRecommendationService.php)**
   - Recommendation generation
   - Rule-based suggestions
   - Action recommendations

5. **[`DatabaseDeltaCheckRecommendationService.php`](backend/app/Services/DatabaseDeltaCheckRecommendationService.php)**
   - Database-driven recommendations
   - Template-based suggestions

6. **[`PrintService.php`](backend/app/Services/PrintService.php)**
   - Report printing
   - PDF generation
   - Print queue management

7. **[`QualityControlLockoutService.php`](backend/app/Services/QualityControlLockoutService.php)**
   - QC lockout management
   - Release conditions
   - Lockout prevention

8. **[`ReportService.php`](backend/app/Services/ReportService.php)**
   - Report generation
   - Template rendering
   - Data formatting

9. **[`SigmaMetricCalculator.php`](backend/app/Services/SigmaMetricCalculator.php)**
   - Sigma metric calculations
   - Process capability analysis
   - Quality specification evaluation

10. **[`WorksheetColumnService.php`](backend/app/Services/WorksheetColumnService.php)**
    - Worksheet column management
    - Column configuration

---

## Configuration Files

### Backend Configuration

- [`config/app.php`](backend/config/app.php) - Application configuration
- [`config/auth.php`](backend/config/auth.php) - Authentication configuration
- [`config/database.php`](backend/config/database.php) - Database configuration
- [`config/sanctum.php`](backend/config/sanctum.php) - API authentication
- [`config/cors.php`](backend/config/cors.php) - CORS settings
- [`config/sigma_metrics.php`](backend/config/sigma_metrics.php) - Sigma metrics configuration
- [`config/logging.php`](backend/config/logging.php) - Logging configuration
- [`config/mail.php`](backend/config/mail.php) - Email configuration
- [`config/queue.php`](backend/config/queue.php) - Queue configuration
- [`config/cache.php`](backend/config/cache.php) - Cache configuration
- [`config/session.php`](backend/config/session.php) - Session configuration
- [`config/filesystems.php`](backend/config/filesystems.php) - File system configuration
- [`config/broadcasting.php`](backend/config/broadcasting.php) - Broadcasting configuration
- [`config/services.php`](backend/config/services.php) - Third-party services

### Frontend Configuration

- [`frontend/.env`](frontend/.env) - Frontend environment variables
- [`frontend/package.json`](frontend/package.json) - NPM dependencies
- [`frontend/jsconfig.json`](frontend/jsconfig.json) - JS configuration
- [`frontend/vite.config.js`](frontend/vite.config.js) - Vite build configuration (if present)

---

## Key Features

### 1. Patient-Centric Workflow
- Patient registration with demographics
- Medical history tracking
- Consultation management
- Laboratory request generation
- Sample collection tracking (phlebotomy)
- Result reporting and delivery

### 2. Advanced Quality Control
- Multi-rule QC (Westgard rules)
- Delta check automation
- Sigma metric evaluation
- QC lockout mechanisms
- Statistical process control
- Real-time QC monitoring

### 3. Intelligent Delta Checks
- Patient-based delta checking
- Population-based reference
- Clinical significance assessment
- Automated recommendations
- Severity classification
- Action tracking

### 4. Equipment Management
- Equipment lifecycle tracking
- Maintenance scheduling
- Calibration management
- Reagent assignments
- Usage monitoring
- Service history

### 5. Inventory Control
- Lot-based inventory tracking
- Consumables management
- Stock level monitoring
- Purchase order management
- Vendor management
- Usage history

### 6. WYSIWYG Report Designer
- Drag-and-drop report building
- Multiple element types
- Dynamic data binding
- Template management
- Real-time preview
- Print-ready output

### 7. Clinical Decision Support
- Correlation rule engine
- Result pattern detection
- Alert generation
- Rule-based recommendations

### 8. Formula Builder
- Custom calculation formulas
- Parameter mapping
- Formula validation
- Reusable formulas

### 9. Comprehensive Reporting
- Report templates
- Dynamic report generation
- Multiple output formats
- Scheduled reports
- Inventory reports
- QC reports
- TAT reports

### 10. Multi-User System
- Role-based access control
- User management
- Doctor profiles
- Ward/room assignment
- Audit trails

---

## Installation & Setup

### Prerequisites
- PHP 8.0+
- Composer
- Node.js 16+
- MySQL 8.0+
- Nginx or Apache

### Backend Setup

1. Navigate to backend directory:
   ```bash
   cd backend
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Update `.env` with database credentials and other settings

5. Run migrations:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. Start development server:
   ```bash
   php artisan serve
   ```

### Frontend Setup

1. Navigate to frontend directory:
   ```bash
   cd frontend
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   ```

4. Update `.env` with API URL:
   ```
   VITE_API_URL=http://localhost:8000
   ```

5. Start development server:
   ```bash
   npm run dev
   ```

### Database Import

Alternative to migrations, you can import the complete schema:
```bash
mysql -u username -p database_name < db/lis_db.sql
```

---

## Testing

### Backend Tests
```bash
cd backend
php artisan test
```

### Frontend Tests
```bash
cd frontend
npm run test
```

---

## API Authentication

The system uses Laravel Sanctum for API authentication:

1. Register user via `POST /api/register`
2. Login via `POST /api/login` to get API token
3. Include token in subsequent requests:
   ```
   Authorization: Bearer {token}
   ```

---

## State Management (Frontend)

Uses Pinia stores for centralized state:

- [`auth.js`](frontend/src/stores/auth.js) - Authentication state
- [`patient.js`](frontend/src/stores/patient.js) - Patient data
- [`doctor.js`](frontend/src/stores/doctor.js) - Doctor data
- [`laboratoryRequest.js`](frontend/src/stores/laboratoryRequest.js) - Lab requests
- [`test.js`](frontend/src/stores/test.js) - Test definitions
- [`testCategory.js`](frontend/src/stores/testCategory.js) - Test categories
- [`testResult.js`](frontend/src/stores/testResult.js) - Test results
- [`qualityControl.js`](frontend/src/stores/qualityControl.js) - QC data
- [`payment.js`](frontend/src/stores/payment.js) - Payment data
- [`dashboard.js`](frontend/src/stores/dashboard.js) - Dashboard data

---

## Design System

### Global Styles
- [`global-design-system.css`](frontend/src/global-design-system.css) - Design tokens and global styles
- [`style.css`](frontend/src/style.css) - Component-specific styles

### Component Styling
- Consistent color palette
- Typography system
- Spacing scale
- Responsive breakpoints
- Component variants

---

## Security Features

- CSRF protection (Laravel)
- Input validation and sanitization
- SQL injection prevention (Eloquent ORM)
- XSS protection
- Role-based access control
- API rate limiting
- Secure password hashing (bcrypt)

---

## Performance Considerations

- Database indexing on frequently queried columns
- Eager loading relationships to prevent N+1 queries
- API response caching where appropriate
- Frontend component lazy loading
- Asset optimization via Vite
- Database query optimization

---

## Scalability Features

- Modular architecture
- Service-oriented backend
- Stateless API design
- Database connection pooling ready
- Queue support for background jobs
- Microservice-ready structure

---

## Monitoring & Logging

- Laravel logging to `storage/logs/laravel.log`
- QC debug logging to `storage/logs/qc_debug.log`
- Error tracking and reporting
- Database query logging
- API request logging

---

## Deployment Notes

### Production Build (Frontend)
```bash
cd frontend
npm run build
```

### Environment Configuration
- Set `APP_ENV=production` in backend `.env`
- Set `NODE_ENV=production` in frontend `.env`
- Configure database for production
- Set up SSL certificates
- Configure web server (Nginx/Apache)
- Set up queue workers if using queues
- Configure backup strategy

### Database Backup
Regular backups of the LIS database are critical due to medical data.

---

## Maintenance

### Regular Tasks
- Database backups
- Log rotation
- QC data archival
- Old result purging (per retention policy)
- Equipment maintenance scheduling
- Reference range updates
- Test parameter reviews

### Database Seeding
The system includes comprehensive seeders for:
- Users and roles
- Test categories and parameters
- Reference ranges
- QC rules and configurations
- Delta check rules
- Equipment and consumables
- Sample types
- Wards and rooms
- Doctors

---

## Known Limitations & Considerations

1. **Data Retention:** Medical data retention policies must be defined and implemented
2. **Audit Trail:** Full audit trail required for regulatory compliance (HIPAA, etc.)
3. **Electronic Signature:** Not yet implemented for result verification
4. **HL7 Integration:** No HL7 interface for external system integration
5. **Barcode Integration:** Limited barcode support for sample tracking
6. **Mobile Access:** No dedicated mobile application
7. **Multi-facility:** Single facility design, no multi-site support
8. **Language:** English only (no i18n support)
9. **Interfaces:** No interface to hospital information systems (HIS)
10. **Middleware:** No middleware for instrument interfaces (LIS-LIS or LIS-analyzer)

---

## Future Enhancements

- HL7 interface engine
- Barcode/RFID sample tracking
- Mobile application for result review
- Multi-language support
- Multi-facility support
- Advanced analytics dashboard
- AI/ML for result interpretation
- Interfacing with hospital information systems
- Electronic signature implementation
- Enhanced audit trail
- Patient portal
- API documentation (OpenAPI/Swagger)
- Microservices migration path

---

## Support & Documentation

### Internal Documentation
- `WYSIWYG_QUICK_REFERENCE.md` - Report designer quick reference
- `WYSIWYG_REPORT_BUILDER_IMPLEMENTATION.md` - Report builder implementation details
- `REPORT_PREVIEW_FIX.md` - Report preview fixes
- `PRINT_*.md` - Print system documentation

### External Resources
- Laravel Documentation: https://laravel.com/docs
- Vue.js Documentation: https://vuejs.org/guide/
- Pinia Documentation: https://pinia.vuejs.org/

---

## License

[Specify license information]

---

## Contact

[Specify contact information for support]

---

## Version History

- **Version 1.0** - Initial release with core LIS functionality
  - Patient management
  - Laboratory request processing
  - Test result entry
  - Quality control with delta checks
  - Equipment and inventory management
  - WYSIWYG report designer
  - Formula builder
  - Clinical decision support

---

**Last Updated:** 2025-03-21

**Status:** Production Ready (with noted limitations)