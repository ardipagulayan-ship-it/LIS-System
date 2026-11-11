# Laboratory Information System (LIS)

## Complete Module Documentation

This document provides a comprehensive explanation of all modules in the Laboratory Information System, grouped by functional areas.

---

## Table of Contents

1. [Patient Management Module](#patient-management-module)
2. [Laboratory Request & Test Management Module](#laboratory-request--test-management-module)
3. [Quality Control Module](#quality-control-module)
4. [Equipment & Consumables Management Module](#equipment--consumables-management-module)
5. [Reporting & Report Designer Module](#reporting--report-designer-module)
6. [Doctor & User Management Module](#doctor--user-management-module)
7. [Financial & Payment Module](#financial--payment-module)
8. [Prescription & Medical Certificate Module](#prescription--medical-certificate-module)
9. [Reference Ranges & Test Parameters Module](#reference-ranges--test-parameters-module)
10. [Clinical Decision Support (CDS) Module](#clinical-decision-support-cds-module)
11. [Formula Builder Module](#formula-builder-module)
12. [Dashboard & Analytics Module](#dashboard--analytics-module)
13. [Room & Ward Management Module](#room--ward-management-module)

---

## Patient Management Module

### Purpose
Manage patient demographics, medical history, and patient-related operations throughout the laboratory workflow.

### Core Components

#### Models
- [`Patient.php`](backend/app/Models/Patient.php) - Core patient demographic information
- [`MedicalHistory.php`](backend/app/Models/MedicalHistory.php) - Patient medical history records
- [`PhysicalExam.php`](backend/app/Models/PhysicalExam.php) - Physical examination findings
- [`Diagnosis.php`](backend/app/Models/Diagnosis.php) - Patient diagnoses
- [`Consultation.php`](backend/app/Models/Consultation.php) - Consultation records
- [`ConsultationLabRequest.php`](backend/app/Models/ConsultationLabRequest.php) - Lab requests from consultations
- [`PatientType.php`](backend/app/Models/PatientType.php) - Patient classification (inpatient/outpatient)

#### Frontend Views
- [`Frontdesk.vue`](frontend/src/views/Frontdesk.vue) - Front desk operations for patient registration and check-in
- [`Patients.vue`](frontend/src/views/Patients.vue) - Patient list and management

#### State Management
- [`patient.js`](frontend/src/stores/patient.js) - Pinia store for patient data

#### API Endpoints
```
GET    /api/patients              # List all patients
POST   /api/patients              # Create new patient
GET    /api/patients/{id}         # Get patient details
PUT    /api/patients/{id}         # Update patient
DELETE /api/patients/{id}         # Delete patient
GET    /api/patients/{id}/medical-history  # Get medical history
POST   /api/patients/{id}/medical-history  # Create medical history
```

### Workflow
1. Patient registration at front desk
2. Medical history collection
3. Consultation with doctor
4. Laboratory request generation
5. Sample collection (phlebotomy)
6. Result reporting
7. Patient notification/checkout

### Database Tables
- `patients` - Main patient demographics
- `medical_histories` - Medical history records
- `physical_exams` - Physical examination data
- `diagnoses` - Diagnosis records
- `consultations` - Consultation logs
- `consultation_lab_requests` - Lab request linkage
- `patient_types` - Patient type definitions

---

## Laboratory Request & Test Management Module

### Purpose
Handle test ordering, laboratory request processing, sample tracking, and test result management.

### Core Components

#### Models
- [`LaboratoryRequest.php`](backend/app/Models/LaboratoryRequest.php) - Main laboratory request
- [`LaboratoryRequestTest.php`](backend/app/Models/LaboratoryRequestTest.php) - Individual tests within a request
- [`Test.php`](backend/app/Models/Test.php) - Test definitions and configurations
- [`TestCategory.php`](backend/app/Models/TestCategory.php) - Test categorization
- [`TestParameter.php`](backend/app/Models/TestParameter.php) - Test parameters
- [`TestResult.php`](backend/app/Models/TestResult.php) - Individual test results
- [`TestTurnaroundTime.php`](backend/app/Models/TestTurnaroundTime.php) - TAT tracking

#### Frontend Views
- [`Phlebotomy.vue`](frontend/src/views/Phlebotomy.vue) - Sample collection and labeling
- [`Tests.vue`](frontend/src/views/Tests.vue) - Test management interface
- [`TestCategories.vue`](frontend/src/views/TestCategories.vue) - Category management
- [`TestResults.vue`](frontend/src/views/TestResults.vue) - Result entry interface
- [`CategoryResults.vue`](frontend/src/views/ResultManagement/CategoryResults.vue) - Category-based results view
- [`OrderManagement.vue`](frontend/src/views/OrderManagement.vue) - Order management
- [`LaboratoryRequests.vue`](frontend/src/views/LaboratoryRequests.vue) - Lab request list

#### State Management
- [`laboratoryRequest.js`](frontend/src/stores/laboratoryRequest.js) - Lab request state
- [`test.js`](frontend/src/stores/test.js) - Test definitions state
- [`testCategory.js`](frontend/src/stores/testCategory.js) - Category state
- [`testResult.js`](frontend/src/stores/testResult.js) - Result state

#### API Endpoints
```
GET    /api/laboratory-requests              # List lab requests
POST   /api/laboratory-requests              # Create lab request
GET    /api/laboratory-requests/{id}         # Get request details
PUT    /api/laboratory-requests/{id}         # Update request
GET    /api/laboratory-requests/{id}/tests   # Get request tests
POST   /api/laboratory-requests/{id}/tests   # Add test to request

GET    /api/tests                           # List tests
POST   /api/tests                           # Create test
GET    /api/tests/{id}                      # Get test details
PUT    /api/tests/{id}                      # Update test
GET    /api/test-categories                 # List categories
POST   /api/test-categories                 # Create category

GET    /api/test-results                    # List results
POST   /api/test-results                    # Enter result
GET    /api/test-results/{id}               # Get result
PUT    /api/test-results/{id}               # Update result
GET    /api/category-results/{categoryId}   # Get category results
```

### Complete Workflow

#### 1. Order Entry
- Doctor creates laboratory request
- Front desk verifies and processes
- Request assigned unique barcode/ID
- Priority level assigned (STAT, routine)

#### 2. Sample Collection (Phlebotomy)
- Sample collection from patient
- Barcode labeling
- Sample type verification
- Collection time recording
- Sample acceptance/rejection

#### 3. Sample Processing
- Sample accessioning
- Worklist assignment
- Analyzer/test allocation
- Sample tracking

#### 4. Test Execution
- Manual or automated testing
- Result entry
- Quality control verification
- Delta check evaluation
- Result validation/verification

#### 5. Result Reporting
- Report generation
- Result verification by pathologist
- Report signing
- Result delivery to doctor/patient

### Database Tables
- `laboratory_requests` - Main lab requests
- `laboratory_request_tests` - Request test items
- `tests` - Test definitions
- `test_categories` - Test categories
- `test_parameters` - Test parameters
- `test_results` - Test results
- `test_turnaround_times` - TAT tracking
- `sample_types` - Sample type definitions

---

## Quality Control Module

### Purpose
Comprehensive quality control management including delta checks, QC rules, sigma metrics, and statistical process control.

### Core Components

#### Models (15+ tables)
- [`QualityControl.php`](backend/app/Models/QualityControl.php) - QC records
- [`QualityControlResult.php`](backend/app/Models/QualityControlResult.php) - QC results
- [`QualityControlTestParameter.php`](backend/app/Models/QualityControlTestParameter.php) - QC test parameters
- [`QcRule.php`](backend/app/Models/QcRule.php) - QC rules (Westgard rules)
- [`QcSigmaMetricResult.php`](backend/app/Models/QcSigmaMetricResult.php) - Sigma metric calculations
- [`QcAnalyteSigmaConfig.php`](backend/app/Models/QcAnalyteSigmaConfig.php) - Sigma configuration
- [`DeltaCheckRule.php`](backend/app/Models/DeltaCheckRule.php) - Delta check rules
- [`DeltaCheckParameter.php`](backend/app/Models/DeltaCheckParameter.php) - Delta check parameters
- [`DeltaCheckQualityControl.php`](backend/app/Models/DeltaCheckQualityControl.php) - QC delta checks
- [`DeltaCheckClinicalSignificance.php`](backend/app/Models/DeltaCheckClinicalSignificance.php) - Clinical significance levels
- [`DeltaCheckSeverityLevel.php`](backend/app/Models/DeltaCheckSeverityLevel.php) - Severity classifications
- [`DeltaCheckPreventiveMeasure.php`](backend/app/Models/DeltaCheckPreventiveMeasure.php) - Preventive measures
- [`DeltaCheckAction.php`](backend/app/Models/DeltaCheckAction.php) - Actions taken
- [`DeltaCheckRecommendationTemplate.php`](backend/app/Models/DeltaCheckRecommendationTemplate.php) - Recommendation templates
- [`DeltaCheckGlobalRecommendation.php`](backend/app/Models/DeltaCheckGlobalRecommendation.php) - Global recommendations
- [`DeltaCheckRecommendationLog.php`](backend/app/Models/DeltaCheckRecommendationLog.php) - Recommendation logs
- [`CorrectiveAction.php`](backend/app/Models/CorrectiveAction.php) - Corrective actions

#### Services
- [`DeltaCheckService.php`](backend/app/Services/DeltaCheckService.php) - Delta check evaluation engine
- [`DeltaCheckRecommendationService.php`](backend/app/Services/DeltaCheckRecommendationService.php) - Recommendation generation
- [`DatabaseDeltaCheckRecommendationService.php`](backend/app/Services/DatabaseDeltaCheckRecommendationService.php) - Database-driven recommendations
- [`SigmaMetricCalculator.php`](backend/app/Services/SigmaMetricCalculator.php) - Sigma metric calculations
- [`QualityControlLockoutService.php`](backend/app/Services/QualityControlLockoutService.php) - QC lockout management
- [`CalculationEngine.php`](backend/app/Services/CalculationEngine.php) - Statistical calculations (mean, SD, CV)

#### Repository
- [`DeltaCheckRecommendationRepository.php`](backend/app/Repositories/DeltaCheckRecommendationRepository.php) - Recommendation data access

#### Frontend Views
- [`QualityControl/QualityControl.vue`](frontend/src/views/QualityControl/QualityControl.vue) - QC entry
- [`QualityControl/QualityControlDashboard.vue`](frontend/src/views/QualityControl/QualityControlDashboard.vue) - QC dashboard
- [`QualityControl/QualityControlList.vue`](frontend/src/views/QualityControl/QualityControlList.vue) - QC list
- [`QualityControl/QualityControlResults.vue`](frontend/src/views/QualityControl/QualityControlResults.vue) - QC results
- [`QualityControl/SigmaMonitor.vue`](frontend/src/views/QualityControl/SigmaMonitor.vue) - Sigma metrics monitoring
- [`ResultManagement/QCLog.vue`](frontend/src/views/ResultManagement/QCLog.vue) - QC logging
- [`ResultManagement/DeltaCheck.vue`](frontend/src/views/ResultManagement/DeltaCheck.vue) - Delta check review
- [`ResultManagement/ResultManagementDashboard.vue`](frontend/src/views/ResultManagement/ResultManagementDashboard.vue) - Results dashboard

#### Admin Views
- [`Admin/DeltaCheckRules.vue`](frontend/src/views/Admin/DeltaCheckRules.vue) - Delta check rule management
- [`Admin/DeltaCheckQcRules.vue`](frontend/src/views/Admin/DeltaCheckQcRules.vue) - QC delta check rules
- [`Admin/QcRules.vue`](frontend/src/views/Admin/QcRules.vue) - QC rule management
- [`Admin/CdsRules.vue`](frontend/src/views/Admin/CdsRules.vue) - CDS correlation rules

#### State Management
- [`qualityControl.js`](frontend/src/stores/qualityControl.js) - QC state

#### API Endpoints
```
GET    /api/quality-controls                    # List QC records
POST   /api/quality-controls                    # Create QC
GET    /api/quality-controls/{id}/results       # Get QC results
POST   /api/quality-controls/{id}/results       # Add QC result
GET    /api/delta-checks                       # List delta checks
POST   /api/delta-checks/evaluate              # Evaluate delta check
GET    /api/sigma-metrics                      # Get sigma metrics
GET    /api/qc-rules                           # List QC rules
POST   /api/qc-rules                           # Create QC rule
```

### Quality Control Features

#### 1. Multi-Rule QC (Westgard Rules)
- **1-2S** - 1 out of 2 beyond 2 SD
- **1-3S** - 1 out of 3 beyond 3 SD
- **2-2S** - 2 out of 2 beyond 2 SD (same side)
- **R-4S** - Range of 4 SD
- **4-1S** - 4 out of 4 beyond 1 SD
- **10-X** - 10 consecutive points on one side of mean

#### 2. Delta Check System
**Patient-Based Delta Checking:**
- Compare current result with previous result
- Consider time interval between tests
- Account for biological variation
- Clinical significance assessment

**Delta Check Parameters:**
- Absolute difference
- Percent change
- Reference range consideration
- Age/sex-specific criteria

**Delta Check Process:**
1. Retrieve patient's previous results
2. Calculate delta (current - previous)
3. Compare against delta check rules
4. Determine clinical significance
5. Generate recommendations
6. Log actions taken

#### 3. Sigma Metrics
**Calculation:**
```
Sigma = (TEa - Bias) / CV
Where:
- TEa = Total Allowable Error
- Bias = Systematic error
- CV = Coefficient of Variation (Imprecision)
```

**Interpretation:**
- Sigma ≥ 6: World-class
- Sigma 4-6: Good
- Sigma 3-4: Marginal
- Sigma < 3: Poor

#### 4. QC Lockout Mechanism
**Lockout Conditions:**
- QC failure beyond acceptable limits
- Multiple rule violations
- QC data integrity issues

**Lockout Prevention:**
- QC review before patient testing
- QC status monitoring
- Release conditions enforcement

### Database Tables
- `quality_controls` - QC records
- `quality_control_results` - QC results
- `quality_control_test_parameters` - QC parameters
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
- `delta_check_recommendation_templates` - Templates
- `delta_check_global_recommendations` - Global recommendations
- `delta_check_recommendation_logs` - Recommendation logs
- `corrective_actions` - Corrective actions

---

## Equipment & Consumables Management Module

### Purpose
Manage equipment lifecycle, consumables inventory, maintenance schedules, and reagent tracking.

### Core Components

#### Models
- [`Equipment.php`](backend/app/Models/Equipment.php) - Equipment master data
- [`Analyzer.php`](backend/app/Models/Analyzer.php) - Laboratory analyzers
- [`EquipmentReagentAssignment.php`](backend/app/Models/EquipmentReagentAssignment.php) - Reagent-equipment assignments
- [`EquipmentUsage.php`](backend/app/Models/EquipmentUsage.php) - Equipment usage tracking
- [`MaintenanceSchedule.php`](backend/app/Models/MaintenanceSchedule.php) - Maintenance scheduling
- [`ServiceLog.php`](backend/app/Models/ServiceLog.php) - Service records
- [`Consumable.php`](backend/app/Models/Consumable.php) - Consumable items
- [`ConsumableUsage.php`](backend/app/Models/ConsumableUsage.php) - Consumable usage tracking
- [`MaterialMaster.php`](backend/app/Models/MaterialMaster.php) - Material master data
- [`PurchaseOrder.php`](backend/app/Models/PurchaseOrder.php) - Purchase orders
- [`PurchaseOrderItem.php`](backend/app/Models/PurchaseOrderItem.php) - PO line items
- [`LotStockRecord.php`](backend/app/Models/LotStockRecord.php) - Lot-based inventory
- [`LotUsageHistory.php`](backend/app/Models/LotUsageHistory.php) - Lot usage tracking
- [`Vendor.php`](backend/app/Models/Vendor.php) - Vendor information

#### Observers
- [`EquipmentObserver.php`](backend/app/Observers/EquipmentObserver.php) - Equipment event handling
- [`ServiceLogObserver.php`](backend/app/Observers/ServiceLogObserver.php) - Service log events

#### Frontend Views
- [`EquipmentManagement/ConsumablesManagement.vue`](frontend/src/views/EquipmentManagement/ConsumablesManagement.vue) - Consumables management
- [`EquipmentManagement/EquipmentManagement.vue`](frontend/src/views/EquipmentManagement/EquipmentManagement.vue) - Equipment management
- [`EquipmentManagement/MaintenanceSchedule.vue`](frontend/src/views/EquipmentManagement/MaintenanceSchedule.vue) - Maintenance scheduling
- [`EquipmentManagement/ServiceLogs.vue`](frontend/src/views/EquipmentManagement/ServiceLogs.vue) - Service logs
- [`InventoryManagement/IMMDashboard.vue`](frontend/src/views/InventoryManagement/IMMDashboard.vue) - Inventory dashboard
- [`InventoryManagement/LotsManagement.vue`](frontend/src/views/InventoryManagement/LotsManagement.vue) - Lot tracking
- [`InventoryManagement/MaterialsManagement.vue`](frontend/src/views/InventoryManagement/MaterialsManagement.vue) - Materials management
- [`InventoryManagement/EquipmentUsage.vue`](frontend/src/views/InventoryManagement/EquipmentUsage.vue) - Equipment usage
- [`InventoryManagement/ReportsManagement.vue`](frontend/src/views/InventoryManagement/ReportsManagement.vue) - Inventory reports
- [`InventoryManagement/VendorsManagement.vue`](frontend/src/views/InventoryManagement/VendorsManagement.vue) - Vendor management
- [`ConsumablesManagement/ConsumablesDashboard.vue`](frontend/src/views/ConsumablesManagement/ConsumablesDashboard.vue) - Consumables dashboard

#### API Endpoints
```
GET    /api/equipment                    # List equipment
POST   /api/equipment                    # Create equipment
GET    /api/equipment/{id}               # Get equipment details
PUT    /api/equipment/{id}               # Update equipment
GET    /api/consumables                  # List consumables
POST   /api/consumables                  # Create consumable
GET    /api/inventory/lots               # List lot records
GET    /api/maintenance-schedules        # List maintenance schedules
POST   /api/maintenance-schedules        # Create maintenance schedule
```

### Equipment Management Features

#### 1. Equipment Lifecycle
- **Procurement:** Equipment acquisition and registration
- **Installation:** Setup and calibration
- **Operation:** Daily usage tracking
- **Maintenance:** Preventive and corrective maintenance
- **Decommissioning:** Equipment retirement

#### 2. Analyzer Management
- Analyzer-specific configurations
- Reagent compatibility
- Performance monitoring
- QC rule assignment
- Result validation rules

#### 3. Maintenance Management
**Preventive Maintenance:**
- Scheduled maintenance tasks
- Maintenance intervals (daily, weekly, monthly, yearly)
- Maintenance checklists
- Parts replacement tracking

**Corrective Maintenance:**
- Breakdown reporting
- Service request generation
- Service log documentation
- Downtime tracking

#### 4. Reagent Management
- Reagent-equipment compatibility
- Reagent lot tracking
- Expiration monitoring
- Calibration verification
- Usage tracking

### Inventory Management Features

#### 1. Lot-Based Inventory
- Lot number tracking
- Expiration date management
- First-expire-first-out (FEFO)
- Lot quarantine and release
- Lot usage history

#### 2. Consumables Tracking
- Stock level monitoring
- Reorder point alerts
- Consumption rate analysis
- Inventory valuation
- Stocktaking

#### 3. Purchase Order Management
- PO creation and approval
- Vendor selection
- Order tracking
- Goods receipt
- Invoice matching

#### 4. Vendor Management
- Vendor registration
- Performance evaluation
- Contract management
- Price negotiation tracking

### Database Tables
- `equipment` - Equipment master
- `analyzers` - Analyzers
- `equipment_reagent_assignments` - Reagent assignments
- `equipment_usage` - Usage tracking
- `maintenance_schedules` - Maintenance schedules
- `service_logs` - Service records
- `consumables` - Consumables
- `consumable_usage` - Consumable usage
- `material_master` - Materials
- `purchase_orders` - Purchase orders
- `purchase_order_items` - PO items
- `lot_stock_records` - Lot inventory
- `lot_usage_history` - Lot usage
- `vendors` - Vendors

---

## Reporting & Report Designer Module

### Purpose
Advanced report generation with WYSIWYG designer, dynamic templates, and print-ready output.

### Core Components

#### Report Designer
- [`ReportDesigner.vue`](frontend/src/components/ReportDesigner.vue) - Main report designer interface
- [`ReportRenderer.vue`](frontend/src/components/ReportRenderer.vue) - Report rendering engine
- [`ReportTemplatesList.vue`](frontend/src/components/ReportTemplatesList.vue) - Template management

#### Report Elements (12 types)
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

#### Property Editors
- [`ReportEditors/TextPropertyEditor.vue`](frontend/src/components/ReportEditors/TextPropertyEditor.vue)
- [`ReportEditors/ImagePropertyEditor.vue`](frontend/src/components/ReportEditors/ImagePropertyEditor.vue)
- [`ReportEditors/ShapePropertyEditor.vue`](frontend/src/components/ReportEditors/ShapePropertyEditor.vue)
- [`ReportEditors/DataTablePropertyEditor.vue`](frontend/src/components/ReportEditors/DataTablePropertyEditor.vue)
- [`ReportEditors/DynamicTextPropertyEditor.vue`](frontend/src/components/ReportEditors/DynamicTextPropertyEditor.vue)
- [`ReportEditors/SignaturePropertyEditor.vue`](frontend/src/components/ReportEditors/SignaturePropertyEditor.vue)
- [`ReportEditors/GridPropertyEditor.vue`](frontend/src/components/ReportEditors/GridPropertyEditor.vue)
- [`ReportEditors/ContainerPropertyEditor.vue`](frontend/src/components/ReportEditors/ContainerPropertyEditor.vue)

#### Utilities
- [`utils/layoutDefinitions.js`](frontend/src/utils/layoutDefinitions.js) - Layout definitions
- [`utils/reportFormatters.js`](frontend/src/utils/reportFormatters.js) - Report formatting utilities

#### Backend Services
- [`ReportService.php`](backend/app/Services/ReportService.php) - Report generation service
- [`PrintService.php`](backend/app/Services/PrintService.php) - Print service

#### Backend Views
- [`resources/views/reports/canvas_renderer.blade.php`](backend/resources/views/reports/canvas_renderer.blade.php) - Canvas rendering

#### Routes
- [`web_print_routes.php`](backend/routes/web_print_routes.php) - Print-specific routes

### Report Designer Features

#### 1. Drag-and-Drop Interface
- Intuitive canvas-based designer
- Element palette with all report elements
- Drag elements onto canvas
- Resize and reposition elements
- Snap-to-grid alignment
- Multi-select and group

#### 2. Report Elements

**Container:**
- Parent element for grouping
- Border and background styling
- Padding and margin control
- Nested elements support

**Text:**
- Static text content
- Font customization (family, size, style)
- Color and alignment
- Text wrapping

**Image:**
- Logo and image placement
- Image upload/selection
- Size and position control
- Aspect ratio maintenance

**Shape:**
- Rectangles, circles, lines
- Fill and stroke styling
- Border customization

**Grid:**
- Row and column layout
- Cell merging
- Border control
- Responsive design

**Data Table:**
- Dynamic data binding
- Column definitions
- Header and footer rows
- Data formatting
- Pagination support

**Dynamic Text:**
- Data field placeholders
- Format patterns
- Conditional display
- Calculated fields

**Signature Block:**
- Digital signature capture
- Signer information
- Date/time stamping
- Multiple signature support

**Header Block:**
- Report header
- Page numbering
- Date/time stamps
- Institution branding

**Patient Info Block:**
- Patient demographics
- Accession number
- Collection information
- Requesting physician

**Rich Text:**
- WYSIWYG editor
- Formatting toolbar
- Lists and tables
- Hyperlinks

#### 3. Data Binding
- **Static Data:** Fixed content
- **Dynamic Data:** Database fields
  - Patient information
  - Test results
  - Laboratory information
  - Custom fields
- **Calculated Fields:** Formulas and expressions
- **Conditional Display:** Show/hide based on conditions

#### 4. Template Management
- Create new templates
- Edit existing templates
- Template versioning
- Template categories
- Template sharing
- Import/export templates

#### 5. Report Generation
- Select template
- Bind data source (patient, lab request, etc.)
- Preview report
- Generate final report
- Export formats (PDF, HTML, etc.)
- Print directly

#### 6. Print System
**Backend Print Service:**
- Print queue management
- Printer selection
- Print job tracking
- Batch printing
- Print preview

**Print Routes:**
- `GET /reports/print/{id}` - Print report
- `POST /reports/generate` - Generate report
- `GET /reports/preview/{id}` - Preview report

### Database Tables
- `reports` - Report templates
- `report_elements` - Report elements
- `report_templates` - Template definitions
- `print_queue` - Print queue
- `print_jobs` - Print job tracking

---

## Doctor & User Management Module

### Purpose
Manage healthcare providers, system users, and role-based access control.

### Core Components

#### Models
- [`Doctor.php`](backend/app/Models/Doctor.php) - Doctor information
- [`User.php`](backend/app/Models/User.php) - System users
- [`UserRole.php`](backend/app/Models/UserRole.php) - User roles
- [`Ward.php`](backend/app/Models/Ward.php) - Ward management
- [`Room.php`](backend/app/Models/Room.php) - Room management

#### Frontend Views
- [`Doctors.vue`](frontend/src/views/Doctors.vue) - Doctor management
- [`UserManagement.vue`](frontend/src/views/UserManagement.vue) - User management
- [`DoctorsCorner/DoctorView.vue`](frontend/src/components/doctors-corner/DoctorView.vue) - Doctor portal
- [`DoctorsCorner/NurseView.vue`](frontend/src/components/doctors-corner/NurseView.vue) - Nurse portal
- [`DoctorsCorner/ConsultationList.vue`](frontend/src/components/doctors-corner/ConsultationList.vue) - Consultation list
- [`DoctorsCorner/NewConsultationModal.vue`](frontend/src/components/doctors-corner/NewConsultationModal.vue) - New consultation

#### State Management
- [`doctor.js`](frontend/src/stores/doctor.js) - Doctor data
- [`auth.js`](frontend/src/stores/auth.js) - Authentication state

#### API Endpoints
```
GET    /api/doctors                # List doctors
POST   /api/doctors                # Create doctor
GET    /api/doctors/{id}           # Get doctor details
PUT    /api/doctors/{id}           # Update doctor
DELETE /api/doctors/{id}           # Delete doctor

GET    /api/users                  # List users
POST   /api/users                  # Create user
GET    /api/users/{id}             # Get user details
PUT    /api/users/{id}             # Update user
DELETE /api/users/{id}             # Delete user

GET    /api/wards                  # List wards
POST   /api/wards                  # Create ward
GET    /api/rooms                  # List rooms
POST   /api/rooms                  # Create room
```

### User Management Features

#### 1. Role-Based Access Control (RBAC)
**User Roles:**
- Administrator
- Pathologist
- Laboratory Technician
- Phlebotomist
- Front Desk Staff
- Doctor (external)
- Nurse
- Inventory Manager
- Quality Control Officer

**Permissions:**
- View/Edit/Create/Delete per module
- Role-specific access
- Feature-level permissions
- Data-level restrictions

#### 2. Doctor Management
- Doctor registration
- Specialty tracking
- Hospital affiliation
- Contact information
- Schedule management
- Consultation tracking

#### 3. Ward & Room Management
- Ward organization
- Room assignment
- Bed management
- Location tracking

### Authentication
- Laravel Sanctum API tokens
- User registration and login
- Password reset
- Session management
- Token refresh

### Database Tables
- `users` - System users
- `user_roles` - User roles
- `doctors` - Doctor information
- `wards` - Wards
- `rooms` - Rooms

---

## Financial & Payment Module

### Purpose
Handle billing, payments, and financial tracking for laboratory services.

### Core Components

#### Models
- [`Payment.php`](backend/app/Models/Payment.php) - Payment records
- [`Invoice.php`](backend/app/Models/Invoice.php) - Invoice management
- [`Billing.php`](backend/app/Models/Billing.php) - Billing records

#### Frontend Views
- [`Payments.vue`](frontend/src/views/Payments.vue) - Payment management

#### State Management
- [`payment.js`](frontend/src/stores/payment.js) - Payment state

### Financial Features

#### 1. Billing
- Test pricing
- Package pricing
- Discount management
- Tax calculation
- Invoice generation

#### 2. Payment Processing
- Payment methods (cash, card, insurance)
- Payment recording
- Receipt generation
- Payment reconciliation

#### 3. Financial Reporting
- Revenue reports
- Outstanding balances
- Payment trends
- Daily collections

### Database Tables
- `payments` - Payment records
- `invoices` - Invoices
- `billings` - Billing records

---

## Prescription & Medical Certificate Module

### Purpose
Manage prescriptions and generate medical certificates.

### Core Components

#### Models
- [`Prescription.php`](backend/app/Models/Prescription.php) - Prescription records
- [`MedicalCertificate.php`](backend/app/Models/MedicalCertificate.php) - Medical certificates

### Features

#### 1. Prescription Management
- Drug prescriptions
- Dosage instructions
- Frequency and duration
- Prescriber information
- Pharmacy dispensing

#### 2. Medical Certificates
- Certificate templates
- Patient certification
- Doctor authorization
- Digital signatures
- Certificate printing

### Database Tables
- `prescriptions` - Prescriptions
- `medical_certificates` - Medical certificates

---

## Reference Ranges & Test Parameters Module

### Purpose
Manage reference ranges, test parameters, and sample type configurations.

### Core Components

#### Models
- [`ReferenceRange.php`](backend/app/Models/ReferenceRange.php) - Reference ranges
- [`TestParameter.php`](backend/app/Models/TestParameter.php) - Test parameters
- [`SampleType.php`](backend/app/Models/SampleType.php) - Sample types

#### Seeders
- [`ComprehensiveTestParameterSeeder.php`](backend/database/seeders/ComprehensiveTestParameterSeeder.php)
- [`ComprehensiveReferenceRangeSeeder.php`](backend/database/seeders/ComprehensiveReferenceRangeSeeder.php)
- [`TestParameterSeeder.php`](backend/database/seeders/TestParameterSeeder.php)
- [`ReferenceRangeSeeder.php`](backend/database/seeders/ReferenceRangeSeeder.php)
- [`ReferenceRangeAnalyzerSeeder.php`](backend/database/seeders/ReferenceRangeAnalyzerSeeder.php)

#### Frontend Views
- [`ReferenceRangeManagement.vue`](frontend/src/views/ReferenceRangeManagement.vue) - Reference range management

### Reference Range Features

#### 1. Reference Range Types
- **Age-specific:** Different ranges by age group
- **Sex-specific:** Different ranges by gender
- **Combined:** Age and sex specific
- **Fixed:** Single range for all
- **Analyzer-specific:** Equipment-specific ranges

#### 2. Reference Range Parameters
- Lower limit
- Upper limit
- Units
- Decimal precision
- Flagging thresholds
- Critical values

#### 3. Test Parameters
- Parameter name
- Abbreviation
- Units
- Decimal places
- Reference range linkage
- Delta check parameters
- Sigma metrics configuration

### Database Tables
- `reference_ranges` - Reference ranges
- `test_parameters` - Test parameters
- `sample_types` - Sample types

---

## Clinical Decision Support (CDS) Module

### Purpose
Provide clinical decision support through correlation rules and intelligent alerts.

### Core Components

#### Models
- [`CdsCorrelationRule.php`](backend/app/Models/CdsCorrelationRule.php) - CDS correlation rules

#### Services
- [`CdsRuleEngine.php`](backend/app/Services/CdsRuleEngine.php) - CDS rule processing engine

#### Frontend Views
- [`Admin/CdsRules.vue`](frontend/src/views/Admin/CdsRules.vue) - CDS rule management

### CDS Features

#### 1. Correlation Rules
**Rule Types:**
- Result correlation (e.g., high glucose + high HbA1c)
- Pattern recognition
- Trend analysis
- Conflict detection

**Rule Components:**
- Condition definition
- Correlation logic
- Alert message
- Severity level
- Action recommendations

#### 2. Alert Generation
- Real-time result evaluation
- Alert display in interface
- Email/SMS notifications
- Escalation procedures

#### 3. Rule Management
- Create/edit correlation rules
- Rule testing
- Rule activation/deactivation
- Rule versioning
- Performance monitoring

### Database Tables
- `cds_correlation_rules` - CDS rules

---

## Formula Builder Module

### Purpose
Create and manage calculation formulas for derived test results.

### Core Components

#### Frontend Views
- [`FormulaBuilder/FormulaBuilder.vue`](frontend/src/views/FormulaBuilder/FormulaBuilder.vue) - Formula builder interface
- [`FormulaBuilder/FormulaManagement.vue`](frontend/src/views/FormulaBuilder/FormulaManagement.vue) - Formula management

### Formula Builder Features

#### 1. Visual Formula Construction
- Drag-and-drop operators
- Parameter selection
- Function library
- Real-time validation
- Syntax highlighting

#### 2. Formula Components
**Operators:**
- Arithmetic (+, -, ×, ÷)
- Comparison (>, <, =, ≥, ≤)
- Logical (AND, OR, NOT)
- Mathematical (power, root, log, exp)

**Functions:**
- Statistical (mean, median, SD)
- Mathematical (sin, cos, tan)
- String manipulation
- Date/time functions

**Parameters:**
- Test parameters
- Constants
- Variables
- Nested formulas

#### 3. Formula Management
- Create formulas
- Edit existing formulas
- Formula categories
- Version control
- Testing and validation
- Deployment to production

#### 4. Integration
- Link formulas to tests
- Automatic calculation
- Manual override capability
- Calculation audit trail

---

## Dashboard & Analytics Module

### Purpose
Provide system-wide dashboards and analytics for monitoring laboratory performance.

### Core Components

#### Frontend Views
- [`Dashboard.vue`](frontend/src/views/Dashboard.vue) - Main dashboard
- [`ResultManagementDashboard.vue`](frontend/src/views/ResultManagement/ResultManagementDashboard.vue) - Results dashboard
- [`ConsumablesDashboard.vue`](frontend/src/views/ConsumablesManagement/ConsumablesDashboard.vue) - Consumables dashboard
- [`IMMDashboard.vue`](frontend/src/views/InventoryManagement/IMMDashboard.vue) - Inventory dashboard
- [`QualityControl/QualityControlDashboard.vue`](frontend/src/views/QualityControl/QualityControlDashboard.vue) - QC dashboard
- [`ManagementReport/Census.vue`](frontend/src/views/ManagementReport/Census.vue) - Census report
- [`ManagementReport/TurnAroundTime.vue`](frontend/src/views/ManagementReport/TurnAroundTime.vue) - TAT report
- [`ManagementReport/Worksheet.vue`](frontend/src/views/ManagementReport/Worksheet.vue) - Worksheet
- [`ManagementReport/DeltaCheckReport.vue`](frontend/src/views/ManagementReport/DeltaCheckReport.vue) - Delta check report

#### State Management
- [`dashboard.js`](frontend/src/stores/dashboard.js) - Dashboard data

### Dashboard Features

#### 1. Key Performance Indicators (KPIs)
- Test volume
- Turnaround time (TAT)
- QC metrics
- Revenue
- Patient satisfaction
- Staff productivity

#### 2. Real-Time Monitoring
- Sample status
- Instrument status
- QC status
- Staff activity
- Inventory levels

#### 3. Analytics
- Trend analysis
- Comparative reporting
- Benchmarking
- Predictive analytics

#### 4. Reports
- Daily operational reports
- Monthly management reports
- Quality reports
- Financial reports
- Regulatory reports

---

## Room & Ward Management Module

### Purpose
Manage laboratory rooms and wards for sample collection and patient management.

### Core Components

#### Models
- [`Room.php`](backend/app/Models/Room.php) - Room management
- [`Ward.php`](backend/app/Models/Ward.php) - Ward management

#### Frontend Views
- [`RoomManagement.vue`](frontend/src/views/RoomManagement.vue) - Room management interface

### Features

#### 1. Ward Management
- Ward creation and configuration
- Ward assignment
- Capacity management
- Location tracking

#### 2. Room Management
- Room inventory
- Room types (collection, processing, storage)
- Equipment assignment
- Scheduling

### Database Tables
- `wards` - Wards
- `rooms` - Rooms

---

## System Architecture Summary

### Backend (Laravel)
- **Framework:** Laravel 10+
- **Language:** PHP 8.0+
- **Database:** MySQL 8.0+
- **API:** RESTful with Sanctum authentication
- **Architecture:** MVC with service layer
- **Models:** 40+ Eloquent models
- **Services:** 10+ business logic services
- **Routes:** 50+ API endpoints

### Frontend (Vue.js)
- **Framework:** Vue.js 3
- **State Management:** Pinia
- **Router:** Vue Router 4
- **Build Tool:** Vite
- **Components:** 40+ Vue components
- **Stores:** 12 Pinia stores

### Database Design
- **Normalized schema**
- **Foreign key constraints**
- **Indexed columns**
- **Audit fields** (created_at, updated_at, deleted_at)
- **40+ tables**

---

## Technology Stack

### Backend Dependencies (composer.json)
- laravel/framework: ^10.0
- laravel/sanctum: ^3.0
- laravel/tinker: ^2.0
- spatie/laravel-permission: ^5.0 (likely)
- maatwebsite/excel: ^3.0 (likely)
- barryvdh/laravel-dompdf: ^2.0 (likely)

### Frontend Dependencies (package.json)
- vue: ^3.0.0
- pinia: ^2.0.0
- vue-router: ^4.0.0
- axios: ^1.0.0
- vite: ^5.0.0

---

## Installation & Configuration

### Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Configure database in .env
php artisan migrate
php artisan db:seed
php artisan serve
```

### Frontend Setup
```bash
cd frontend
npm install
cp .env.example .env
# Set VITE_API_URL in .env
npm run dev
```

---

## Key Features Summary

1. **Complete Laboratory Workflow** - From order to report
2. **Advanced Quality Control** - Delta checks, QC rules, sigma metrics
3. **WYSIWYG Report Designer** - Drag-and-drop report building
4. **Equipment Management** - Full lifecycle management
5. **Inventory Control** - Lot-based tracking
6. **Clinical Decision Support** - Intelligent alerts
7. **Formula Builder** - Custom calculations
8. **Comprehensive Dashboards** - Real-time analytics
9. **Role-Based Security** - Granular permissions
10. **Audit Trail** - Complete tracking

---

## Conclusion

This Laboratory Information System is a comprehensive, production-ready solution with 13 major functional modules covering all aspects of laboratory operations. The system features advanced quality control capabilities, a sophisticated report designer, and a modular architecture suitable for medium to large laboratories.

All modules are fully integrated with a RESTful API backend and a modern Vue.js frontend, providing a seamless user experience for laboratory staff, pathologists, doctors, and administrators.