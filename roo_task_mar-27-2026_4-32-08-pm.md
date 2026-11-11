**User:**

<user_message>
Analyze the current reflex alert implementation in the LIS system and implement the required improvements.

**Context:**
The system has a reflex testing feature where lab results trigger automatic follow-up tests. The current implementation appears to have issues with duplicate alerts and broken UI.

**Requirements to implement:**
1. ✅ Centralized Alert Banner - Single unified component, no duplicates
2. ✅ Redesigned Reflex UI - Concise format: "Rule: HGB < 5 (1 g/dL). Reflex: Add Reticulocyte Count"
3. ✅ Action Buttons - Green APPROVE, VIEW LOGIC, DENY/IGNORE
4. ✅ Visual State Matching - Red (action needed) → Blue/Green (success) → Gray (denied)
5. ✅ Transient Feedback - Success/denied banners auto-dismiss after 5s with countdown
6. ✅ No Overlap/Clutter - Proper spacing, single banner
7. ✅ Bug-Free Lifecycle - All async operations guarded with isMounted checks
8. ✅ Priority & Sample Inheritance - From parent request
9. ✅ Full Audit Trail - All decisions recorded
10. ✅ Worklist Integration - Reflex badges, filtering, parent navigation

**Files to examine:**
- frontend/src/components/modals/result/UniversalResultModal.vue (main component)
- frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue (broken version for reference)
- frontend/src/components/CdsAlertBanner.vue (existing alert component)
- backend/app/Http/Controllers/Api/ResultManagementController.php (API endpoints)
- backend/app/Models/LaboratoryRequest.php and LaboratoryRequestTest.php (models with reflex tracking)
- REFLEX_TEST_API_DOCUMENTATION.md and REFLEX_TEST_TESTING_GUIDE.md (specs)

**Your task:**
1. Read and analyze all the above files to understand current implementation
2. Identify what's broken or missing
3. Create a comprehensive plan to fix all issues
4. Implement the complete solution ensuring:
   - Single alert banner component used everywhere
   - Proper Vue lifecycle management (isMounted checks)
   - Clean UI with action buttons
   - State management for visual feedback
   - Auto-dismiss with countdown
   - Audit trail logging
   - Worklist integration with badges
5. Test mentally for edge cases and async operation safety
6. Ensure all functions remain intact and no assumptions are made

**Important:** Do not make assumptions. If the current code is unclear, read related files to understand the full context. Make sure all async operations have proper isMounted guards to prevent memory leaks. The final implementation must be bug-free and production-ready.

Start by reading the key files and then implement the complete solution.
</user_message>
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:20:34.944Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
<model>stepfun/step-3.5-flash:free</model>


# Current Workspace Directory (/run/media/ardi/FE225375225331BF/Trae Projects/LIS-System) Files
.directory
LIMITATIONS_AND_FEEDBACK.md
LIS_SYSTEM_SPECIFICATION.md
MUST_HAVE_FEATURES_BEYOND_CDS_DELTA_QC.md
README.md
REFLEX_TEST_API_DOCUMENTATION.md
REFLEX_TEST_MIGRATION_AND_DEPLOYMENT.md
REFLEX_TEST_TESTING_GUIDE.md
backend/
backend/.editorconfig
backend/.env
backend/.env.example
backend/.gitattributes
backend/.gitignore
backend/artisan
backend/composer.json
backend/composer.lock
backend/package-lock.json
backend/package.json
backend/phpunit.xml
backend/README.md
backend/vite.config.js
backend/app/Console/Kernel.php
backend/app/Exceptions/Handler.php
backend/app/Models/Analyzer.php
backend/app/Models/Assessment.php
backend/app/Models/CdsCorrelationRule.php
backend/app/Models/Consultation.php
backend/app/Models/ConsultationLabRequest.php
backend/app/Models/Consumable.php
backend/app/Models/ConsumableUsage.php
backend/app/Models/CorrectiveAction.php
backend/app/Models/DeltaCheckAction.php
backend/app/Models/DeltaCheckClinicalSignificance.php
backend/app/Models/DeltaCheckGlobalRecommendation.php
backend/app/Models/DeltaCheckParameter.php
backend/app/Models/DeltaCheckPreventiveMeasure.php
backend/app/Models/DeltaCheckQualityControl.php
backend/app/Models/DeltaCheckRecommendationLog.php
backend/app/Models/DeltaCheckRecommendationTemplate.php
backend/app/Models/DeltaCheckRule.php
backend/app/Models/DeltaCheckSeverityLevel.php
backend/app/Models/Diagnosis.php
backend/app/Models/Diagnostic.php
backend/app/Models/Doctor.php
backend/app/Models/Equipment.php
backend/app/Models/EquipmentReagentAssignment.php
backend/app/Models/EquipmentUsage.php
backend/app/Models/LaboratoryRequest.php
backend/app/Models/LaboratoryRequestTest.php
backend/app/Models/LotStockRecord.php
backend/app/Models/LotUsageHistory.php
backend/app/Models/MaintenanceSchedule.php
backend/app/Models/MaterialMaster.php
backend/app/Models/MedicalCertificate.php
backend/app/Models/MedicalHistory.php
backend/app/Models/Patient.php
backend/app/Models/PatientType.php
backend/app/Models/Payment.php
backend/app/Models/PhysicalExam.php
backend/app/Models/Prescription.php
backend/app/Models/PurchaseOrder.php
backend/app/Models/PurchaseOrderItem.php
backend/app/Models/QcAnalyteSigmaConfig.php
backend/app/Models/QcRule.php
backend/app/Models/QcSigmaMetricResult.php
backend/app/Models/QualityControl.php
backend/app/Models/QualityControlResult.php
backend/app/Models/QualityControlTestParameter.php
backend/app/Observers/EquipmentObserver.php
backend/app/Observers/ServiceLogObserver.php
backend/app/Observers/TestResultObserver.php
backend/app/Observers/TestResultObserverWithCleanup.php
backend/app/Providers/AppServiceProvider.php
backend/app/Providers/AuthServiceProvider.php
backend/app/Providers/BroadcastServiceProvider.php
backend/app/Providers/EventServiceProvider.php
backend/app/Providers/RouteServiceProvider.php
backend/app/Repositories/DeltaCheckRecommendationRepository.php
backend/app/Services/CalculationEngine.php
backend/app/Services/CdsRuleEngine.php
backend/app/Services/DatabaseDeltaCheckRecommendationService.php
backend/app/Services/DeltaCheckRecommendationService.php
backend/app/Services/DeltaCheckService.php
backend/app/Services/PrintService.php
backend/app/Services/QualityControlLockoutService.php
backend/app/Services/ReflexTestService.php
backend/app/Services/ReportService.php
backend/app/Services/SigmaMetricCalculator.php
backend/app/Services/WorksheetColumnService.php
backend/bootstrap/app.php
backend/bootstrap/cache/.gitignore
backend/bootstrap/cache/packages.php
backend/bootstrap/cache/services.php
backend/database/seeders/AnalyzerSeeder.php
backend/database/seeders/CdsCorrelationRuleSeeder.php
backend/database/seeders/ComprehensiveReferenceRangeSeeder.php
backend/database/seeders/ComprehensiveTestParameterSeeder.php
backend/database/seeders/DatabaseSeeder.php
backend/database/seeders/DeltaCheckRecommendationSeeder.php
backend/database/seeders/DeltaCheckRuleSeeder.php
backend/database/seeders/DoctorSeeder.php
backend/database/seeders/EquipmentSeeder.php
backend/database/seeders/IMMSeeder.php
backend/database/seeders/PatientSeeder.php
backend/database/seeders/PatientTypeSeeder.php
backend/database/seeders/QcAnalyteSigmaConfigSeeder.php
backend/database/seeders/QcRuleSeeder.php
backend/database/seeders/QcSigmaMetricResultSeeder.php
backend/database/seeders/QualityControlRecommendationsSeeder.php
backend/database/seeders/QualityControlResultSeeder.php
backend/database/seeders/QualityControlSeeder.php
backend/database/seeders/QualityControlTestParameterSeeder.php
backend/database/seeders/README_SEEDING.md
backend/database/seeders/ReferenceRangeAnalyzerSeeder.php
backend/database/seeders/ReferenceRangeSeeder.php
backend/database/seeders/RoomSeeder.php
backend/database/seeders/SampleTypeSeeder.php
backend/database/seeders/TestCategorySeeder.php
backend/database/seeders/TestParameterSeeder.php
backend/database/seeders/TestTurnaroundTimeSeeder.php
backend/database/seeders/UpdateQualityControlResultSeeder.php
backend/database/seeders/UserRoleSeeder.php
backend/database/seeders/WardSeeder.php
backend/public/.htaccess
backend/public/favicon.ico
backend/public/index.php
backend/public/robots.txt
backend/resources/css/app.css
backend/resources/js/app.js
backend/resources/js/bootstrap.js
backend/resources/views/welcome.blade.php
backend/resources/views/reports/canvas_renderer.blade.php
backend/routes/api_backup.php
backend/routes/api.php
backend/routes/channels.php
backend/routes/console.php
backend/routes/web_print_routes.php
backend/routes/web.php
backend/storage/app/.gitignore
backend/storage/app/public/.gitignore
backend/storage/logs/.gitignore
backend/storage/logs/laravel.log
backend/storage/logs/qc_debug.log
backend/tests/CreatesApplication.php
backend/tests/TestCase.php
backend/tests/Feature/ExampleTest.php
backend/tests/Unit/ExampleTest.php
db/
db/lis_db_v1.sql
db/lis_db.sql
frontend/
frontend/.env
frontend/.gitignore
frontend/index.html
frontend/jsconfig.json
frontend/package-lock.json
frontend/package.json
frontend/README.md
frontend/vite.config.js
frontend/public/vite.svg
frontend/src/App.vue
frontend/src/stores/auth.js
frontend/src/stores/dashboard.js
frontend/src/stores/doctor.js
frontend/src/stores/laboratoryRequest.js
frontend/src/stores/patient.js
frontend/src/stores/payment.js
frontend/src/stores/qualityControl.js
frontend/src/stores/test.js
frontend/src/stores/testCategory.js
frontend/src/stores/testResult.js
frontend/src/views/Login.vue
frontend/src/views/OrderManagement.vue
frontend/src/views/Patients.vue
frontend/src/views/Payments.vue
frontend/src/views/Phlebotomy_back20250422.vue
frontend/src/views/Phlebotomy_backup20251201.vue
frontend/src/views/Phlebotomy.vue
frontend/src/views/ReferenceRangeManagement.vue
frontend/src/views/RoomManagement.vue
frontend/src/views/TestCategories.vue
frontend/src/views/TestResults.vue
frontend/src/views/Tests.vue
frontend/src/views/UserManagement.vue
frontend/src/views/WardManagement.vue
frontend/src/views/FormulaBuilder/FormulaBuilder.vue
frontend/src/views/FormulaBuilder/FormulaManagement.vue
frontend/src/views/ManagementReport/Census.vue
frontend/src/views/ManagementReport/DeltaCheckReport.vue
frontend/src/views/ManagementReport/TurnAroundTime.vue
frontend/src/views/ManagementReport/Worksheet.vue
frontend/src/views/ResultManagement/CategoryResults Backup.vue
frontend/src/views/ResultManagement/CategoryResults copy.vue
frontend/src/views/ResultManagement/CategoryResults.vue
frontend/src/views/ResultManagement/DeltaCheck.vue
frontend/src/views/ResultManagement/QCLog.vue
frontend/src/views/ResultManagement/ResultManagementDashboard.vue
frontend/src/views/results/Chemistry.vue
frontend/src/views/results/Hematology.vue

(File list truncated. Use list_files on specific subdirectories if you need to explore further.)
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Read and analyze UniversalResultModal.vue | Pending |
| 2 | Read UniversalResultModal-Reflex-broken.vue for comparison | Pending |
| 3 | Read CdsAlertBanner.vue to understand existing alert system | Pending |
| 4 | Read backend controllers and models for reflex tracking | Pending |
| 5 | Read API documentation and testing guide | Pending |
| 6 | Identify all issues in current implementation | Pending |
| 7 | Design comprehensive solution | Pending |
| 8 | Implement centralized alert banner component | Pending |
| 9 | Update UniversalResultModal with proper reflex handling | Pending |
| 10 | Add isMounted guards to all async operations | Pending |
| 11 | Implement visual state management (red→blue/green→gray) | Pending |
| 12 | Add auto-dismiss with countdown timer | Pending |
| 13 | Ensure audit trail logging | Pending |
| 14 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 15 | Verify all functions remain intact | Pending |
| 16 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

I'll analyze the current reflex alert implementation and implement the required improvements. Let me start by reading the key files to understand the current state.
[Tool Use: read_file]
Path: frontend/src/components/modals/result/UniversalResultModal.vue
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 0,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 0,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: frontend/src/components/CdsAlertBanner.vue
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 0,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}

---

**User:**

[Tool]
Error: anchor_line must be a 1-indexed line number (got 0). Line numbers start at 1.
[Tool]
Error: anchor_line must be a 1-indexed line number (got 0). Line numbers start at 1.
[Tool]
Error: anchor_line must be a 1-indexed line number (got 0). Line numbers start at 1.
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:20:45.269Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Read and analyze UniversalResultModal.vue | Pending |
| 2 | Read UniversalResultModal-Reflex-broken.vue for comparison | Pending |
| 3 | Read CdsAlertBanner.vue to understand existing alert system | Pending |
| 4 | Read backend controllers and models for reflex tracking | Pending |
| 5 | Read API documentation and testing guide | Pending |
| 6 | Identify all issues in current implementation | Pending |
| 7 | Design comprehensive solution | Pending |
| 8 | Implement centralized alert banner component | Pending |
| 9 | Update UniversalResultModal with proper reflex handling | Pending |
| 10 | Add isMounted guards to all async operations | Pending |
| 11 | Implement visual state management (red→blue/green→gray) | Pending |
| 12 | Add auto-dismiss with countdown timer | Pending |
| 13 | Ensure audit trail logging | Pending |
| 14 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 15 | Verify all functions remain intact | Pending |
| 16 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Let me read the files with correct parameters:
[Tool Use: read_file]
Path: frontend/src/components/modals/result/UniversalResultModal.vue
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: frontend/src/components/CdsAlertBanner.vue
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}

---

**User:**

[Tool]
File: frontend/src/components/modals/result/UniversalResultModal.vue
IMPORTANT: File content truncated.
	Status: Showing lines 1-2000 of 6022 total lines.
	To read more: Use the read_file tool with offset=2001 and limit=2000.
	
	   1 | <template>
   2 |   <Teleport to="#app">
   3 | <el-dialog
   4 |   :model-value="props.visible"
   5 |   :fullscreen="true"
   6 |   class="hematology-modal"
   7 |   @close="handleClose"
   8 |   v-loading="props.loading || parametersLoading"
   9 | >
  10 |   <!-- Sticky Header / Toolbar -->
  11 |   <div class="result-header">
  12 |     <div class="header-left">
  13 |       <div class="brand">
  14 |         <div class="brand-logo-wrapper">
  15 |           <span class="brand-logo">🧪</span>
  16 |         </div>
  17 |         <div class="brand-text">
  18 |           <span class="brand-name">{{ modalTitle }}</span>
  19 |           <span class="brand-tagline">Laboratory Information System</span>
  20 |         </div>
  21 |       </div>
  22 |     </div>
  23 | 
  24 |     <div class="global-actions">
  25 |       <el-button-group>
  26 |         <el-button type="danger" plain @click="handleRejectAction" :disabled="disableRejectButton">
  27 |           <el-icon><Close /></el-icon>
  28 |           <span>{{ rejectButtonLabel }}</span>
  29 |         </el-button>
  30 |         <el-button type="warning" plain @click="handleRerunAction" :disabled="disableRerunButton">
  31 |           <el-icon><Refresh /></el-icon>
  32 |           <span>{{ rerunButtonLabel }}</span>
  33 |         </el-button>
  34 |         <el-button type="success" plain @click="handleAcceptResult" :disabled="disableAcceptButton">
  35 |           <el-icon><Check /></el-icon>
  36 |           <span>Accept & Acknowledge</span>
  37 |         </el-button>
  38 |       </el-button-group>
  39 |       <div class="header-divider"></div>
  40 |       <el-button-group>
  41 |         <el-button type="warning" plain @click="triggerSave" :disabled="disableSaveButton">
  42 |           <el-icon><Document /></el-icon>
  43 |           <span>Save Draft</span>
  44 |         </el-button>
  45 |         <el-button type="primary" @click="handlePrintRelease" :disabled="disablePrintButton">
  46 |           <el-icon><Printer /></el-icon>
  47 |           <span>Print & Export</span>
  48 |         </el-button>
  49 |         <el-button type="success" @click="handleValidateRelease" :disabled="disableValidateButton">
  50 |           <el-icon><Check /></el-icon>
  51 |           <span>Validate & Release</span>
  52 |         </el-button>
  53 |       </el-button-group>
  54 |       <div class="header-divider"></div>
  55 |       <el-button type="danger" circle @click="handleClose" class="close-btn">
  56 |         <el-icon><Close /></el-icon>
  57 |       </el-button>
  58 |     </div>
  59 |   </div>
  60 | 
  61 |   <!-- Rejected Banner (Read-only lock) -->
  62 |   <div v-if="isFullyRejected" class="status-banner banner-rejected">
  63 |     <div class="banner-content">
  64 |       <div class="banner-icon">
  65 |         <el-icon><Lock /></el-icon>
  66 |       </div>
  67 |       <div class="banner-text">
  68 |         <div class="banner-title">REQUEST REJECTED: READ-ONLY</div>
  69 |         <div class="banner-detail">
  70 |           No further actions allowed.
  71 |           <span v-if="latestRejectedAt"> Rejected on {{ formatDateTime(latestRejectedAt) }}</span>
  72 |           • <a href="#" @click.prevent="openAuditTrail" class="audit-link">View Audit Trail</a>
  73 |         </div>
  74 |       </div>
  75 |     </div>
  76 |   </div>
  77 | 
  78 |   <!-- Critical Action Banner (Sticky) -->
  79 |   <div
  80 |     v-else-if="hasHoldStatus || qcHoldBannerMessage || (cdsAlerts && cdsAlerts.length) || criticalValues.length || hasAnyRuleAcknowledgment"
  81 |     class="status-banner"
  82 |     :class="{ 
  83 |       'banner-qc': qcHoldBannerMessage,
  84 |       'banner-critical': !qcHoldBannerMessage && hasUnacknowledgedAlerts,
  85 |       'banner-success': !qcHoldBannerMessage && !hasUnacknowledgedAlerts && hasAnyRuleAcknowledgment
  86 |     }"
  87 |   >
  88 |     <div class="banner-content">
  89 |       <div class="banner-icon">
  90 |         <el-icon v-if="qcHoldBannerMessage || (hasUnacknowledgedAlerts && (hasHoldStatus || criticalValues.length))"><WarningFilled /></el-icon>
  91 |         <el-icon v-else-if="hasAnyRuleAcknowledgment && !hasUnacknowledgedAlerts"><SuccessFilled /></el-icon>
  92 |         <el-icon v-else><InfoFilled /></el-icon>
  93 |       </div>
  94 |       <div class="banner-text">
  95 |         <div class="banner-title">
  96 |           {{ 
  97 |             qcHoldBannerMessage ? 'QC LOCKOUT ACTIVE' : 
  98 |             (hasUnacknowledgedAlerts ? 
  99 |               (hasHoldStatus && !deltaAcknowledgeData ? 'CRITICAL HOLD: ACTION REQUIRED' : 
 100 |                (criticalValues.length && !criticalAcknowledgeData ? 'CRITICAL RESULT: ACTION REQUIRED' : 'CDS RULE EXCEPTION: ACTION REQUIRED')) 
 101 |               : 'RULES ACKNOWLEDGED')
 102 |           }}
 103 |         </div>
 104 |         <div class="banner-detail">
 105 |           <span v-if="qcHoldBannerMessage">Reason: <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">{{ qcHoldBannerMessage }}</a></span>
 106 |           <span v-else-if="hasUnacknowledgedAlerts">
 107 |             Reason: <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">
 108 |               This request has unacknowledged violations. Click here to Review.
 109 |             </a>
 110 |           </span>
 111 |           <span v-else-if="hasAnyRuleAcknowledgment">
 112 |             Alerts/Flags were acknowledged.
 113 |             <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">View Audit Trail</a>
 114 |           </span>
 115 |         </div>
 116 |       </div>
 117 |     </div>
 118 |   </div>
 119 | 
 120 |   <div class="content-container" :class="{ 'with-banner': isFullyRejected || hasHoldStatus || qcHoldBannerMessage || (cdsAlerts && cdsAlerts.length) || criticalValues.length || cdsAcknowledgeData || deltaAcknowledgeData || criticalAcknowledgeData }">
 121 |     <!-- Unified Patient & Request Information Banner (Professional Polish) -->
 122 |     <div class="patient-card-premium-unified">
 123 |       <!-- Left Side: Patient Information (Pill-based) -->
 124 |       <div class="patient-info-section">
 125 |         <div class="patient-main-info-wrapper">
 126 |           <div class="patient-avatar-wrapper">
 127 |             <div class="avatar-premium">
 128 |               {{ props.requestDetails.patient?.last_name?.[0] }}{{ props.requestDetails.patient?.first_name?.[0] }}
 129 |             </div>
 130 |             <div class="patient-gender-icon" :class="props.requestDetails.patient?.gender?.toLowerCase()">
 131 |               <el-icon v-if="props.requestDetails.patient?.gender?.toLowerCase() === 'male'"><User /></el-icon>
 132 |               <el-icon v-else><User /></el-icon>
 133 |             </div>
 134 |           </div>
 135 |           <div class="patient-info-content">
 136 |             <div class="patient-name-row">
 137 |               <h2 class="patient-name-premium">{{ props.requestDetails.patient?.last_name }}, {{ props.requestDetails.patient?.first_name }}</h2>
 138 |               <el-tag size="small" effect="dark" :type="props.requestDetails.priority_level?.toLowerCase() === 'stat' ? 'danger' : 'info'" class="priority-tag">
 139 |                 {{ props.requestDetails.priority_level || 'Routine' }}
 140 |               </el-tag>
 141 |             </div>
 142 |             <div class="patient-meta-container-premium">
 143 |               <div class="patient-meta-row-premium">
 144 |                 <span class="meta-pill"><el-icon><Memo /></el-icon> <b>ID:</b> {{ props.requestDetails.patient?.id || 'N/A' }}</span>
 145 |                 <span class="meta-pill"><el-icon><Calendar /></el-icon> <b>BIRTHDAY:</b> {{ formattedBirthday }}</span>
 146 |                 <span class="meta-pill"><el-icon><User /></el-icon> <b>AGE / SEX:</b> {{ formattedAgeGender }}</span>
 147 |               </div>
 148 |               <!-- Vertical Divider -->
 149 |               <div class="premium-horizontal-divider"></div>
 150 |               <div class="patient-meta-row-premium">
 151 |                 <span class="meta-pill"><el-icon><Phone /></el-icon> <b>CONTACT:</b> {{ formattedContact }}</span>
 152 |                 <span class="meta-pill"><el-icon><Location /></el-icon> <b>ADDRESS:</b> {{ formattedAddress }}</span>
 153 |                 <span class="meta-pill"><el-icon><Message /></el-icon> <b>EMAIL:</b> {{ formattedEmail }}</span>
 154 |               </div>
 155 |             </div>
 156 |           </div>
 157 |         </div>
 158 |       </div>
 159 | 
 160 |       <!-- Vertical Divider -->
 161 |       <div class="premium-vertical-divider"></div>
 162 | 
 163 |       <!-- Right Side: Request Details (Row-based, 2-column grid) -->
 164 |       <div class="request-details-section">
 165 |         <div class="clinical-context-v2-grid-unified">
 166 |           <div class="context-item-v2">
 167 |             <span class="c-label">ACCESSION NO.:</span>
 168 |             <span class="c-value">{{ props.requestDetails.accession_id || props.requestDetails.sample_id || 'N/A' }}</span>
 169 |           </div>
 170 |           <div class="context-item-v2">
 171 |             <span class="c-label">PRIORITY LEVEL:</span>
 172 |             <el-tag size="small" effect="dark" :type="props.requestDetails.priority_level?.toLowerCase() === 'stat' ? 'danger' : 'info'" class="priority-tag-inline">
 173 |               {{ props.requestDetails.priority_level || 'Routine' }}
 174 |             </el-tag>
 175 |           </div>
 176 |           <div class="context-item-v2">
 177 |             <span class="c-label">REQUESTING DOCTOR:</span>
 178 |             <span class="c-value">{{ requestingDoctor }}</span>
 179 |           </div>
 180 |           <div class="context-item-v2">
 181 |             <span class="c-label">REFERRED BY:</span>
 182 |             <span class="c-value">{{ referringDoctorName }}</span>
 183 |           </div>
 184 |           <div class="context-item-v2">
 185 |             <span class="c-label">PATIENT TYPE:</span>
 186 |             <span class="c-value">{{ patientTypeName }}</span>
 187 |           </div>
 188 |           <div class="context-item-v2">
 189 |             <span class="c-label">OR NO.:</span>
 190 |             <span class="c-value">{{ orNumber }}</span>
 191 |           </div>
 192 |           <div class="context-item-v2">
 193 |             <span class="c-label">LOCATION:</span>
 194 |             <span class="c-value">{{ props.requestDetails.ward?.name || '—' }} / {{ props.requestDetails.room?.name || '—' }}</span>
 195 |           </div>
 196 |           <div class="context-item-v2">
 197 |             <span class="c-label">RECEIVED:</span>
 198 |             <span class="c-value">{{ formatDateTime(props.requestDetails.request_date) }}</span>
 199 |           </div>
 200 |           <div class="context-item-v2">
 201 |             <span class="c-label">QC STATUS:</span>
 202 |             <div :class="['qc-badge-v2', qcHoldBannerMessage ? 'fail' : 'pass']">
 203 |               <el-icon><Check v-if="!qcHoldBannerMessage" /><Close v-else /></el-icon>
 204 |               {{ qcHoldBannerMessage ? 'FAIL' : 'PASS' }}
 205 |             </div>
 206 |           </div>
 207 |         </div>
 208 |       </div>
 209 |     </div>
 210 | 
 211 |     <!-- Results Table Toolbar (Professional Integrated Style) -->
 212 |     <div class="table-actions-toolbar">
 213 |       <div class="quick-links">
 214 |         <el-button-group>
 215 |           <el-button type="primary"  @click="showPatientHistory = true">
 216 |             <el-icon><Document /></el-icon> 
 217 |             <span>Delta Check (6m)</span>
 218 |           </el-button>
 219 |           <!-- <el-button type="info" plain size="small" @click="openAuditTrail">
 220 |             <el-icon><Search /></el-icon> Audit Trail
 221 |           </el-button> -->
 222 |           <el-button type="info" >
 223 |             <el-icon><Plus /></el-icon> 
 224 |             <span>Add Test</span>
 225 |           </el-button>
 226 |         </el-button-group>
 227 |       </div>
 228 | 
 229 |     </div>
 230 | 
 231 |     <!-- 2. Action Bar & Secondary Meta -->
 232 | 
 233 |     <!-- 3. Main Results Table -->
 234 |     <div class="results-table-card">
 235 |       <div class="table-header-premium">
 236 |         <div class="table-header-info">
 237 |           <el-icon class="table-icon"><DocumentCopy /></el-icon>
 238 |           <span class="table-title">TEST PARAMETERS & RESULTS</span>
 239 |         </div>
 240 |         <div class="table-header-legend">
 241 |           <div class="legend-pill normal"><span></span> Normal</div>
 242 |           <div class="legend-pill abnormal"><span></span> Abnormal</div>
 243 |           <div class="legend-pill critical"><span></span> Critical</div>
 244 |         </div>
 245 |       </div>
 246 |       <el-table 
 247 |         :data="formattedTestResults" 
 248 |         style="width: 100%"
 249 |         :row-key="getRowKey"
 250 |         :row-class-name="getModalRowClassName"
 251 |         @selection-change="handleSelectionChange"
 252 |         size="small"
 253 |         class="premium-table"
 254 |       >
 255 |         <el-table-column type="selection" width="48" align="center" />
 256 |         
 257 |         <el-table-column label="VER" width="60" align="center">
 258 |           <template #default="{ row }">
 259 |             <span class="version-badge" :class="row.versionStatus?.toLowerCase()">{{ row.versionStatus }}</span>
 260 |           </template>
 261 |         </el-table-column>
 262 | 
 263 |         <el-table-column label="PARAMETER" min-width="220">
 264 |           <template #default="{ row }">
 265 |             <div class="parameter-cell-premium">
 266 |               <span class="p-name">{{ row.parameterName }}</span>
 267 |               <el-icon v-if="row.flag === 'L' || row.flag === 'H'" class="status-icon-critical"><WarningFilled /></el-icon>
 268 |               <el-icon v-else-if="row.flag === 'l' || row.flag === 'h'" class="status-icon-abnormal"><InfoFilled /></el-icon>
 269 |               <el-tooltip v-if="row.is_auto_calculated" content="Auto-calculated" placement="top">
 270 |                 <span class="calc-indicator">fx</span>
 271 |               </el-tooltip>
 272 |             </div>
 273 |           </template>
 274 |         </el-table-column>
 275 | 
 276 |         <el-table-column label="FLAG" width="80" align="center">
 277 |           <template #default="{ row }">
 278 |             <div v-if="row.flag" class="flag-indicator" :class="getFlagCharClass(row.flag)">
 279 |               {{ row.flag }}
 280 |             </div>
 281 |           </template>
 282 |         </el-table-column>
 283 | 
 284 |         <el-table-column label="S.I. UNITS" align="center">
 285 |           <el-table-column label="RESULT" width="130" align="center">
 286 |             <template #default="{ row }">
 287 |               <div class="result-input-container">
 288 |                 <el-input
 289 |                   v-if="row.isParameter"
 290 |                   v-model="row.resultValue"
 291 |                   @change="updateResultValue(row, $event)"
 292 |                   :disabled="row.is_auto_calculated || isFullyRejected"
 293 |                   size="small"
 294 |                   :class="['premium-input-center', getFlagClass(row.flag), { 'is-bold': isResultValueBold(row.flag) }]"
 295 |                   placeholder="—"
 296 |                 >
 297 |                   <template #suffix v-if="row.isParameter && row.is_auto_calculated">
 298 |                     <el-button link @click.stop="overrideAutoCalculated(row)" class="override-btn">
 299 |                       <el-icon><Edit /></el-icon>
 300 |                     </el-button>
 301 |                   </template>
 302 |                 </el-input>
 303 |               </div>
 304 |             </template>
 305 |           </el-table-column>
 306 | 
 307 |           <el-table-column v-if="isForRerun" label="ORIGINAL" width="100" align="center">
 308 |             <template #default="{ row }">
 309 |               <span class="original-value-text" v-if="row.isParameter">{{ row.originalValue ?? '—' }}</span>
 310 |             </template>
 311 |           </el-table-column>
 312 | 
 313 |           <el-table-column label="PREVIOUS" width="120" align="center">
 314 |             <template #default="{ row }">
 315 |               <div class="previous-value-group" v-if="row.previousValue !== undefined">
 316 |                 <span class="prev-val">{{ row.previousValue }}</span>
 317 |                 <span class="delta-badge" v-if="row.deltaPercent !== undefined" :class="{ 'delta-warning': Math.abs(row.deltaPercent) > 20, 'delta-normal': Math.abs(row.deltaPercent) <= 20 }">
 318 |                   {{ Number(row.deltaPercent) > 0 ? '+' : '' }}{{ Number(row.deltaPercent).toFixed(0) }}%
 319 |                 </span>
 320 |               </div>
 321 |               <span v-else class="text-placeholder">—</span>
 322 |             </template>
 323 |           </el-table-column>
 324 | 
 325 |           <el-table-column prop="unit" label="UNITS" width="90" align="center">
 326 |             <template #default="{ row }">
 327 |                <span class="unit-text">{{ row.unit }}</span>
 328 |             </template>
 329 |           </el-table-column>
 330 | 
 331 |           <el-table-column label="REF RANGE" width="140" align="center">
 332 |             <template #default="{ row }">
 333 |               <span class="ref-range-pill">{{ row.referenceRange }}</span>
 334 |             </template>
 335 |           </el-table-column>
 336 |         </el-table-column>
 337 | 
 338 |         <el-table-column label="CONVENTIONAL" align="center">
 339 |           <el-table-column label="RESULT" width="100" align="center">
 340 |             <template #default="{ row }">
 341 |               <span class="conventional-value">{{ row.conventional || '—' }}</span>
 342 |             </template>
 343 |           </el-table-column>
 344 |           <el-table-column prop="units" label="UNITS" width="90" align="center">
 345 |              <template #default="{ row }">
 346 |                <span class="unit-text-secondary">{{ row.units }}</span>
 347 |             </template>
 348 |           </el-table-column>
 349 |           <el-table-column prop="refRange" label="REF RANGE" width="90" align="center">
 350 |              <template #default="{ row }">
 351 |                <span class="unit-text-secondary">{{ row.refRange }}</span>
 352 |             </template>
 353 |           </el-table-column>
 354 |         </el-table-column>
 355 | 
 356 |         <el-table-column label="INSTRUMENT" width="180" align="center">
 357 |           <template #default="{ row }">
 358 |             <el-select 
 359 |               v-if="row.isParameter"
 360 |               v-model="row.instrument" 
 361 |               size="small"
 362 |               @change="(value) => handleInstrumentChange(row, value)"
 363 |               :disabled="isFullyRejected"
 364 |               class="premium-select"
 365 |             >
 366 |               <el-option v-for="eq in equipment" :key="eq.id" :label="eq.model" :value="eq.id" />
 367 |             </el-select>
 368 |           </template>
 369 |         </el-table-column>
 370 | 
 371 |         <el-table-column label="COMMENTS" min-width="180">
 372 |           <template #default="{ row }">
 373 |             <el-input
 374 |               v-if="row.isParameter"
 375 |               v-model="row.resultComment"
 376 |               @blur="updateParameterComment(row)"
 377 |               placeholder="Technical note..."
 378 |               size="small"
 379 |               class="comment-input-minimal"
 380 |             >
 381 |                <template #prefix><el-icon><EditPen /></el-icon></template>
 382 |             </el-input>
 383 |           </template>
 384 |         </el-table-column>
 385 |       </el-table>
 386 |     </div>
 387 | 
 388 |     <!-- 4. Footer: Notes & Signatories -->
 389 |     <div class="bottom-organization">
 390 |       <div class="note-box-premium">
 391 |         <div class="box-header-premium">
 392 |           <el-icon><EditPen /></el-icon>
 393 |           <span>Technical Remarks</span>
 394 |         </div>
 395 |         <el-input 
 396 |           v-model="localRemarks" 
 397 |           type="textarea" 
 398 |           :rows="3" 
 399 |           placeholder="Enter internal technical observations..." 
 400 |           class="premium-textarea"
 401 |         />
 402 |       </div>
 403 |       <div class="note-box-premium">
 404 |         <div class="box-header-premium">
 405 |           <el-icon><ChatDotRound /></el-icon>
 406 |           <span>Clinical Comments</span>
 407 |         </div>
 408 |         <el-input 
 409 |           v-model="localComments" 
 410 |           type="textarea" 
 411 |           :rows="3" 
 412 |           placeholder="Enter comments for the official report..." 
 413 |           class="premium-textarea"
 414 |         />
 415 |       </div>
 416 |     </div>
 417 | 
 418 |     <div class="bottom-organization">
 419 |       <div class="signatories-card-premium">
 420 |         <div class="box-header-premium">
 421 |           <el-icon><Avatar /></el-icon>
 422 |           <span>AUTHORIZED SIGNATORIES</span>
 423 |         </div>
 424 |         <div class="sig-grid-v2">
 425 |           <div class="sig-field-v2">
 426 |             <label>Performed By</label>
 427 |             <el-select v-model="localSignatories.performedBy" size="small" filterable class="premium-select">
 428 |               <el-option v-for="user in loadedPerformers" :key="user.id" :label="user.name" :value="user.id" />
 429 |             </el-select>
 430 |           </div>
 431 |           <div class="sig-field-v2">
 432 |             <label>Verified By</label>
 433 |             <el-select v-model="localSignatories.verifiedBy" size="small" filterable class="premium-select">
 434 |               <el-option v-for="user in loadedVerifiers" :key="user.id" :label="user.name" :value="user.id" />
 435 |             </el-select>
 436 |           </div>
 437 |           <div class="sig-field-v2">
 438 |             <label>Pathologist</label>
 439 |             <el-select v-model="localSignatories.pathologist" size="small" filterable class="premium-select">
 440 |               <el-option v-for="user in loadedPathologists" :key="user.id" :label="user.name" :value="user.id" />
 441 |             </el-select>
 442 |           </div>
 443 |         </div>
 444 |       </div>
 445 |     </div>
 446 |   </div>
 447 | 
 448 |     <!-- Sub-Modal Components -->
 449 | 
 450 |     <RejectResultModal
 451 |       v-model:visible="showRejectModal"
 452 |       :loading="loading"
 453 |       :current-user="currentUser"
 454 |       :request-details="requestDetails"
 455 |       :selected-tests="selectedTests"
 456 |       @submit="refreshData"
 457 |     />
 458 | 
 459 |     <RerunModal
 460 |       v-model:visible="showRerunModal"
 461 |       :loading="loading"
 462 |       :request-details="requestDetails"
 463 |       :current-user="currentUser"
 464 |       :selected-tests="selectedTests"
 465 |       @submit="refreshData"
 466 |     />
 467 | 
 468 |     <ViolationsSummaryModal
 469 |       v-model:visible="showViolationsModal"
 470 |       :request-details="requestDetails"
 471 |       :cds-alerts="cdsAlerts"
 472 |       :flagged-delta-checks="flaggedDeltaChecks"
 473 |       :critical-values="criticalValues"
 474 |       :audit-trail="auditTrail"
 475 |       :active-tab="activeViolationTab"
 476 |       :is-acknowledged="hasAnyRuleAcknowledgment"
 477 |       @close="showViolationsModal = false"
 478 |       @acknowledge="handleAcknowledgeViolations"
 479 |       @validate="handleValidateRelease"
 480 |     />
 481 | 
 482 |     <TemplatePrintDialog
 483 |       v-model:visible="showTemplatePrintDialog"
 484 |       :request-details="requestDetails"
 485 |     />
 486 | 
 487 |     <PatientHistoryModal
 488 |       v-model:visible="showPatientHistory"
 489 |       :patient-id="requestDetails?.patient?.id"
 490 |       :category-id="categoryId"
 491 |     />
 492 |   </el-dialog>
 493 | </Teleport>
 494 | </template>
 495 | 
 496 | <script>
 497 | // Global cache for static/shared data to prevent 429 errors
 498 | const staticCache = {
 499 |   roles: {}, // { roleType: { data: [], timestamp: 0 } }
 500 |   equipment: {}, // { categoryId: { data: [], timestamp: 0 } }
 501 |   formulas: { data: [], timestamp: 0 },
 502 |   deltaChecks: {}, // { requestId: { data: [], timestamp: 0 } }
 503 |   cdsRules: {}, // { requestId: { data: [], timestamp: 0 } }
 504 |   allFlaggedDeltaChecks: { data: [], timestamp: 0 },
 505 |   qcStatus: {}, // { categoryId: { data: [], timestamp: 0 } }
 506 |   auditTrail: {} // { requestId: { data: [], timestamp: 0 } }
 507 | };
 508 | const CACHE_TTL = 30000; // 30 seconds cache
 509 | </script>
 510 | 
 511 | <script setup>
 512 | // Note: staticCache moved to parent script block for persistence across instances
 513 | 
 514 | /**
 515 |  * BUTTON STATE MANAGEMENT SYSTEM
 516 |  * 
 517 |  * This component uses computed properties to directly control button states:
 518 |  * - disableSaveButton → Save button
 519 |  * - disablePrintButton → Print & Release button
 520 |  * - disableValidateButton → Validate & Release button
 521 |  * 
 522 |  * These computed properties automatically react to changes in test parameters
 523 |  * and test statuses. No additional reactive variables are used for button states.
 524 |  */
 525 | import { ref, computed, reactive, watch, onMounted, onUnmounted, nextTick } from 'vue';
 526 | import {
 527 |   View,
 528 |   Document,
 529 |   Printer,
 530 |   Check,
 531 |   Close,
 532 |   User,
 533 |   Refresh,
 534 |   Plus,
 535 |   Loading,
 536 |   Lock,
 537 |   WarningFilled,
 538 |   SuccessFilled,
 539 |   InfoFilled,
 540 |   Calendar,
 541 |   Location,
 542 |   DocumentCopy,
 543 |   Memo,
 544 |   Avatar,
 545 |   FolderOpened,
 546 |   Filter,
 547 |   CircleClose,
 548 |   EditPen,
 549 |   ChatDotRound,
 550 |   Edit,
 551 |   Search,
 552 |   Warning,
 553 |   Operation
 554 | } from '@element-plus/icons-vue';
 555 | import { format, parseISO } from 'date-fns';
 556 | import { useAuthStore } from '@/stores/auth';
 557 | import api from '@/utils/axios';
 558 | import { ElMessage, ElMessageBox } from 'element-plus';
 559 | import { differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';
 560 | import TemplatePrintDialog from '../TemplatePrintDialog.vue';
 561 | import PatientHistoryModal from './PatientHistoryModal.vue';
 562 | import RejectResultModal from './RejectResultModal.vue';
 563 | import RerunModal from './RerunModal.vue';
 564 | import ViolationsSummaryModal from './ViolationsSummaryModal.vue';
 565 | 
 566 | const props = defineProps({
 567 |   visible: {
 568 |     type: Boolean,
 569 |     default: false,
 570 |   },
 571 |   requestDetails: {
 572 |     type: Object,
 573 |     default: () => ({ tests: [], patient: {}, performer_id: null, verifier_id: null, pathologist_id: null, remarks: '', comment: '' }),
 574 |   },
 575 |   loading: {
 576 |     type: Boolean,
 577 |     default: false,
 578 |   },
 579 |   verifiers: {
 580 |     type: Array,
 581 |     default: () => [],
 582 |   },
 583 |   performers: {
 584 |     type: Array,
 585 |     default: () => [],
 586 |   },
 587 |   pathologists: {
 588 |     type: Array,
 589 |     default: () => [],
 590 |   },
 591 |   categoryName: {
 592 |     type: String,
 593 |     default: '',
 594 |   },
 595 |   categoryId: {
 596 |     type: [String, Number],
 597 |     default: null,
 598 |   },
 599 | });
 600 | 
 601 | const emit = defineEmits(['update:visible', 'close', 'save', 'validate', 'print']);
 602 | 
 603 | const authStore = useAuthStore();
 604 | const currentUser = ref(null);
 605 | // Override data source for saved results to ensure version/status accuracy after re-run
 606 | const overrideResultsDataSource = ref(null);
 607 | // Show Original Result column only when request is tagged For Re-run
 608 | const isForRerun = ref(false);
 609 | 
 610 | onMounted(async () => {
 611 |   currentUser.value = authStore.user;
 612 | });
 613 | 
 614 | // Re-run state
 615 | const showRerunModal = ref(false);
 616 | const rerunningResult = ref(false);
 617 | const rerunFormRef = ref(null);
 618 | const rerunForm = reactive({
 619 |   reason: '',
 620 |   comments: ''
 621 | });
 622 | 
 623 | // Template print state
 624 | const showTemplatePrintDialog = ref(false);
 625 | const showPatientHistory = ref(false);
 626 | const rerunRules = {
 627 |   reason: [{ required: true, message: 'Please select a reason for the re-run.', trigger: 'change' }]
 628 | };
 629 | 
 630 | const selectedTests = ref([]);
 631 | 
 632 | // --- Dynamic Button Logic ---
 633 | const rejectButtonLabel = computed(() => {
 634 |   const count = selectedTests.value.length;
 635 |   return count > 0 ? `Reject Selected (${count})` : 'Reject Request';
 636 | });
 637 | 
 638 | const rerunButtonLabel = computed(() => {
 639 |   const count = selectedTests.value.length;
 640 |   return count > 0 ? `Rerun Selected (${count})` : 'Rerun Request';
 641 | });
 642 | 
 643 | const disableRejectButton = computed(() => {
 644 |   if (isFullyRejected.value || isFullyValidatedOrReleased.value) return true;
 645 |   return false;
 646 | });
 647 | 
 648 | const disableRerunButton = computed(() => {
 649 |   if (isFullyRejected.value || isFullyValidatedOrReleased.value) return true;
 650 |   return false;
 651 | });
 652 | 
 653 | const disableAcceptButton = computed(() => {
 654 |   if (isFullyRejected.value) return true;
 655 |   return !hasUnacknowledgedAlerts.value;
 656 | });
 657 | 
 658 | const handleRejectAction = async () => {
 659 |   if (isFullyRejected.value) {
 660 |     ElMessage.warning('This request is fully rejected and read-only.');
 661 |     return;
 662 |   }
 663 | 
 664 |   if (selectedTests.value.length === 0) {
 665 |     try {
 666 |       await ElMessageBox.confirm(
 667 |         `
 668 |         <div class="bulk-action-warning">
 669 |           <div style="color: #f56c6c; font-weight: bold; font-size: 16px; margin-bottom: 12px;">Warning: Bulk Action Detected</div>
 670 |           <p>You are about to reject the entire request (<b>#${props.requestDetails.accession_id || props.requestDetails.sample_id || 'N/A'}</b>). This cannot be undone.</p>
 671 |           <div style="margin-top: 16px; padding: 12px; background: #fef0f0; border-radius: 4px; border-left: 4px solid #f56c6c;">
 672 |             <p><b>To Reject Everything:</b> Click "Confirm Global Reject" and provide a reason in the next step.</p>
 673 |             <p style="margin-top: 8px;"><b>To Reject Specific Tests:</b> Close this window, select the individual parameters in the table, and click "Reject Selected."</p>
 674 |           </div>
 675 |         </div>
 676 |         `,
 677 |         'Bulk Action Detected',
 678 |         {
 679 |           confirmButtonText: 'Confirm Global Reject',
 680 |           cancelButtonText: 'Close',
 681 |           type: 'warning',
 682 |           dangerouslyUseHTMLString: true,
 683 |           confirmButtonClass: 'el-button--danger'
 684 |         }
 685 |       );
 686 |       
 687 |       showRejectModal.value = true;
 688 |     } catch (e) {
 689 |       // User cancelled
 690 |     }
 691 |   } else {
 692 |     showRejectModal.value = true;
 693 |   }
 694 | };
 695 | 
 696 | const handleRerunAction = async () => {
 697 |   if (isFullyRejected.value) {
 698 |     ElMessage.warning('This request is fully rejected and read-only.');
 699 |     return;
 700 |   }
 701 | 
 702 |   if (selectedTests.value.length === 0) {
 703 |     try {
 704 |       await ElMessageBox.confirm(
 705 |         `
 706 |         <div class="bulk-action-warning">
 707 |           <div style="color: #e6a23c; font-weight: bold; font-size: 16px; margin-bottom: 12px;">Warning: Bulk Action Detected</div>
 708 |           <p>You are about to re-run the entire request (<b>#${props.requestDetails.accession_id || props.requestDetails.sample_id || 'N/A'}</b>).</p>
 709 |           <div style="margin-top: 16px; padding: 12px; background: #fdf6ec; border-radius: 4px; border-left: 4px solid #e6a23c;">
 710 |             <p><b>To Re-run Everything:</b> Click "Confirm Global Re-run" and provide a reason in the next step.</p>
 711 |             <p style="margin-top: 8px;"><b>To Re-run Specific Tests:</b> Close this window, select the individual parameters in the table, and click "Rerun Selected."</p>
 712 |           </div>
 713 |         </div>
 714 |         `,
 715 |         'Bulk Action Detected',
 716 |         {
 717 |           confirmButtonText: 'Confirm Global Re-run',
 718 |           cancelButtonText: 'Close',
 719 |           type: 'warning',
 720 |           dangerouslyUseHTMLString: true,
 721 |           confirmButtonClass: 'el-button--warning'
 722 |         }
 723 |       );
 724 |       
 725 |       // For global rerun, we might need to select all results first or handle it in the modal
 726 |       // For now, just show the modal
 727 |       showRerunModal.value = true;
 728 |     } catch (e) {
 729 |       // User cancelled
 730 |     }
 731 |   } else {
 732 |     showRerunModal.value = true;
 733 |   }
 734 | };
 735 | const requestTests = computed(() => props.requestDetails?.tests || []);
 736 | 
 737 | // Read-only lock if all tests are Rejected
 738 | const isFullyRejected = computed(() => {
 739 |   const tests = requestTests.value || [];
 740 |   if (tests.length === 0) return false;
 741 |   return tests.every(t => t?.pivot?.item_status === 'Rejected');
 742 | });
 743 | 
 744 | const isFullyValidatedOrReleased = computed(() => {
 745 |   const tests = requestTests.value || [];
 746 |   if (tests.length === 0) return false;
 747 |   return tests.every(t => 
 748 |     t?.pivot?.item_status === 'Result Validated' || 
 749 |     t?.pivot?.item_status === 'Result Printed' || 
 750 |     t?.pivot?.item_status === 'Released'
 751 |   );
 752 | });
 753 | 
 754 | const latestRejectedAt = computed(() => {
 755 |   const tests = requestTests.value || [];
 756 |   const stamps = tests
 757 |     .filter(t => t?.pivot?.item_status === 'Rejected' && t?.pivot?.rejected_at)
 758 |     .map(t => t.pivot.rejected_at);
 759 |   if (stamps.length === 0) return null;
 760 |   return stamps.sort((a, b) => new Date(b) - new Date(a))[0];
 761 | });
 762 | 
 763 | const openAuditTrail = () => {
 764 |   showViolationsModal.value = true;
 765 |   activeViolationTab.value = 'audit';
 766 |   fetchAuditTrail();
 767 | };
 768 | 
 769 | const getSelectedTestIds = () => {
 770 |   const ids = selectedTests.value
 771 |     .map(item => item.testId || item.test_id || item.test?.id)
 772 |     .filter(id => !!id);
 773 |   return Array.from(new Set(ids));
 774 | };
 775 | 
 776 | const handleSelectionChange = (selection) => {
 777 |   selectedTests.value = selection;
 778 | };
 779 | 
 780 | const getRowKey = (row) => {
 781 |   // Use the unique ID generated in formattedTestResults (e.g., param-123 or test-456)
 782 |   return row.id;
 783 | };
 784 | 
 785 | const submitRerun = async () => {
 786 |   if (!rerunFormRef.value) return;
 787 |   await rerunFormRef.value.validate(async (valid) => {
 788 |     if (valid) {
 789 |       rerunningResult.value = true;
 790 |       try {
 791 |         const flagged_results = selectedTests.value
 792 |           .filter(item => item.result_id) // Only include items with a result_id
 793 |           .map(item => ({
 794 |             test_result_id: item.result_id,
 795 |             test_parameter_id: item.parameterId
 796 |           }));
 797 | 
 798 |         if (flagged_results.length === 0) {
 799 |           ElMessage.error('Please select at least one valid test result to re-run.');
 800 |           rerunningResult.value = false;
 801 |           return;
 802 |         }
 803 |         const payload = {
 804 |           flagged_results: flagged_results,
 805 |           reason: rerunForm.reason,
 806 |           comments: rerunForm.comments,
 807 |           requested_by_id: currentUser.value?.id
 808 |         };
 809 | 
 810 |         // Call the API to initiate re-run and capture new results
 811 |         const rerunResp = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/rerun`, payload);
 812 | 
 813 |         ElMessage.success('Re-run initiated successfully.');
 814 |         showRerunModal.value = false;
 815 | 
 816 |         try {
 817 |           // Build latestResultsDataSource from backend new_test_results
 818 |           const latestResultsDataSource = {};
 819 |           const newResults = rerunResp?.data?.new_test_results || [];
 820 |           newResults.forEach(item => {
 821 |             const testId = item.test_id;
 822 |             const paramId = item.test_parameter_id;
 823 |             if (!testId || !paramId) return;
 824 |             if (!latestResultsDataSource[testId]) latestResultsDataSource[testId] = [];
 825 |             latestResultsDataSource[testId].push({
 826 |               id: paramId,
 827 |               result_id: item.id,
 828 |               resultValue: item.result_value,
 829 |               unit: item.unit || '',
 830 |               instrument: item.equipment_id || null,
 831 |               resultComment: item.result_comment || '',
 832 |               flag: item.flag || null,
 833 |               conventional: '',
 834 |               conventional_unit: '',
 835 |               conversion_factor: null,
 836 |               version: item.version || 'R1',
 837 |               status: item.status || 'pending'
 838 |             });
 839 |             // Immediate in-place UI update for version/status
 840 |             const params = testParameters.value[testId] || [];
 841 |             const idx = params.findIndex(p => p.id === paramId || p.parameterId === paramId);
 842 |             if (idx !== -1) {
 843 |               params[idx].version = item.version || 'R1';
 844 |               params[idx].status = item.status || 'pending';
 845 |               params[idx].result_id = item.id;
 846 |               params[idx].resultValue = item.result_value || '';
 847 |             }
 848 |           });
 849 |           // Persist override so subsequent refreshes use the correct version/status
 850 |           overrideResultsDataSource.value = latestResultsDataSource;
 851 |           // Re-fetch parameters, merging with latestResultsDataSource to inject version/status
 852 |           await fetchAllTestParameters(props.requestDetails.tests, latestResultsDataSource);
 853 |         } catch (e) {
 854 |           console.warn('UI refresh after re-run failed:', e);
 855 |         }
 856 | 
 857 |         // You might want to refresh the data or update the UI accordingly
 858 |         emit('save', props.requestDetails.id, flagged_results);
 859 | 
 860 |       } catch (error) {
 861 |         console.error('Error initiating re-run:', error);
 862 |         ElMessage.error('Failed to initiate re-run.');
 863 |       } finally {
 864 |         rerunningResult.value = false;
 865 |       }
 866 |     }
 867 |   });
 868 | };
 869 | 
 870 | // Computed property for dynamic modal title
 871 | const modalTitle = computed(() => {
 872 |   const categoryName = props.categoryName.toLocaleUpperCase() || 'Result';
 873 |   return `${categoryName} Result Window`;
 874 | });
 875 | 
 876 | const localSignatories = ref({ performedBy: null, verifiedBy: null, pathologist: null });
 877 | const localRemarks = ref('');
 878 | const localComments = ref('');
 879 | const resultFields = ref([]);
 880 | const auditTrail = ref([]);
 881 | const auditTrailLoading = ref(false);
 882 | 
 883 | const cdsAcknowledgeData = computed(() => {
 884 |   if (!auditTrail.value || auditTrail.value.length === 0) return null;
 885 |   return auditTrail.value.find(a => a.rule_type === 'CDS' && a.decision === 'acknowledged');
 886 | });
 887 | 
 888 | const deltaAcknowledgeData = computed(() => {
 889 |   if (!auditTrail.value || auditTrail.value.length === 0) return null;
 890 |   return auditTrail.value.find(a => a.rule_type === 'DELTA_CHECK' && a.decision === 'acknowledged');
 891 | });
 892 | 
 893 | const criticalAcknowledgeData = computed(() => {
 894 |   if (!auditTrail.value || auditTrail.value.length === 0) return null;
 895 |   return auditTrail.value.find(a => a.rule_type === 'CRITICAL_VALUE' && a.decision === 'acknowledged');
 896 | });
 897 | 
 898 | const hasAnyRuleAcknowledgment = computed(() => {
 899 |   return !!(cdsAcknowledgeData.value || deltaAcknowledgeData.value || criticalAcknowledgeData.value);
 900 | });
 901 | const hasUnacknowledgedAlerts = computed(() => {
 902 |   const hasCds = cdsAlerts.value.length > 0;
 903 |   const hasDelta = flaggedDeltaChecks.value.length > 0;
 904 |   const hasCritical = criticalValues.value.length > 0;
 905 |   return (hasCds && !cdsAcknowledgeData.value)
 906 |     || (hasDelta && !deltaAcknowledgeData.value)
 907 |     || (hasCritical && !criticalAcknowledgeData.value);
 908 | });
 909 | const testParameters = ref({});
 910 | const parametersLoading = ref(false);
 911 | const equipment = ref([]);
 912 | const loadingEquipment = ref(false);
 913 | // Formula auto-computation state
 914 | const formulas = ref([]);
 915 | const formulasByTarget = ref({});
 916 | 
 917 | const buildFormulasIndex = () => {
 918 |   const idx = {};
 919 |   (formulas.value || []).forEach(f => {
 920 |     const targetId = f.target_test_parameter_id || f.target_parameter_id || (f.target_parameter && f.target_parameter.id);
 921 |     if (targetId) idx[targetId] = f;
 922 |   });
 923 |   formulasByTarget.value = idx;
 924 | };
 925 | 
 926 | const fetchFormulas = async () => {
 927 |   const now = Date.now();
 928 |   if (staticCache.formulas.data.length > 0 && (now - staticCache.formulas.timestamp < CACHE_TTL)) {
 929 |     console.log('Using cached formulas');
 930 |     formulas.value = staticCache.formulas.data;
 931 |     buildFormulasIndex();
 932 |     return;
 933 |   }
 934 | 
 935 |   try {
 936 |     const resp = await api.get('/formulas');
 937 |     // support either { data: [...] } or [...] response shapes
 938 |     const data = resp.data && Array.isArray(resp.data) ? resp.data : (resp.data?.data || resp.data || []);
 939 |     formulas.value = data;
 940 |     
 941 |     // Update cache
 942 |     staticCache.formulas.data = data;
 943 |     staticCache.formulas.timestamp = now;
 944 |     
 945 |     buildFormulasIndex();
 946 |   } catch (e) {
 947 |     if (e.response?.status === 429) {
 948 |       console.warn('Rate limited while fetching formulas, using stale cache if available');
 949 |       if (staticCache.formulas.data.length > 0) {
 950 |         formulas.value = staticCache.formulas.data;
 951 |         buildFormulasIndex();
 952 |         return;
 953 |       }
 954 |     }
 955 |     console.error('Failed to load formulas for auto-computation:', e);
 956 |     formulas.value = [];
 957 |     formulasByTarget.value = {};
 958 |   }
 959 | };
 960 | 
 961 | const buildResultsMap = () => {
 962 |   const map = {};
 963 |   Object.values(testParameters.value).flat().forEach(p => {
 964 |     if (!p) return;
 965 |     const raw = p.resultValue;
 966 |     if (raw === undefined || raw === null) return;
 967 |     const s = String(raw).trim();
 968 |     if (s === '' || isNaN(parseFloat(s))) return;
 969 |     map[p.id] = parseFloat(s);
 970 |   });
 971 |   return map;
 972 | };
 973 | 
 974 | const evaluateCompiledFormula = (compiled, resultsMap) => {
 975 |   if (!compiled) return null;
 976 |   // Replace tokens like [123] or {123} with numeric values from resultsMap
 977 |   let expr = String(compiled).replace(/[\[\{](\d+)[\]\}]/g, (m, id) => {
 978 |     const v = resultsMap[id];
 979 |     if (v === undefined) throw new Error('Missing value for parameter ' + id);
 980 |     return String(Number(v));
 981 |   });
 982 | 
 983 |   // Disallow letters (to prevent identifiers) and only allow safe math chars
 984 |   if (/[A-Za-z]/.test(expr)) throw new Error('Invalid characters in formula');
 985 |   if (/[^0-9+\-*/().%\s]/.test(expr)) throw new Error('Disallowed characters in formula');
 986 | 
 987 |   // Simple balanced parentheses check
 988 |   let depth = 0;
 989 |   for (let ch of expr) {
 990 |     if (ch === '(') depth++;
 991 |     if (ch === ')') {
 992 |       depth--;
 993 |       if (depth < 0) throw new Error('Unbalanced parentheses');
 994 |     }
 995 |   }
 996 |   if (depth !== 0) throw new Error('Unbalanced parentheses');
 997 | 
 998 |   // Evaluate in a strict function scope
 999 |   try {
1000 |     // eslint-disable-next-line no-new-func
1001 |     const val = Function('"use strict";return (' + expr + ')')();
1002 |     if (typeof val !== 'number' || !isFinite(val)) throw new Error('Non-numeric result');
1003 |     return val;
1004 |   } catch (e) {
1005 |     throw new Error('Evaluation error: ' + e.message);
1006 |   }
1007 | };
1008 | 
1009 | const applyComputedValue = (formula, targetParamId, numericValue) => {
1010 |   if (numericValue === null || numericValue === undefined || isNaN(Number(numericValue))) return;
1011 |   for (const testId of Object.keys(testParameters.value)) {
1012 |     const params = testParameters.value[testId] || [];
1013 |     const idx = params.findIndex(p => p.id === targetParamId);
1014 |     if (idx === -1) continue;
1015 |     const param = params[idx];
1016 |     const decimals = (formula && (formula.decimal_places || formula.decimal_places === 0)) ? Number(formula.decimal_places) : null;
1017 |     const rounded = decimals !== null ? Number(Number(numericValue).toFixed(decimals)) : numericValue;
1018 |     param.resultValue = String(rounded);
1019 |     param.result_id = null;
1020 |     param.is_auto_calculated = true;
1021 |     param.auto_formula_id = formula?.id || null;
1022 | 
1023 |     // recalc conventional if conversion_factor is available
1024 |     if (param.conversion_factor && !isNaN(parseFloat(param.conversion_factor))) {
1025 |       try {
1026 |         param.conventional = (parseFloat(param.resultValue) * parseFloat(param.conversion_factor)).toFixed(2);
1027 |       } catch (e) {
1028 |         param.conventional = '';
1029 |       }
1030 |     } else {
1031 |       param.conventional = '';
1032 |     }
1033 | 
1034 |     // Trigger flag calculation for the auto-filled value
1035 |     api.post('/results/calculate-derived', {
1036 |       result: Number(param.resultValue),
1037 |       unit: param.unit || '',
1038 |       test_parameter_id: param.id,
1039 |       patient_id: props.requestDetails?.patient?.id,
1040 |       equipment_id: param.instrument || null
1041 |     }).then(resp => {
1042 |       param.flag = resp.data.flag;
1043 |       testParameters.value[testId] = [...testParameters.value[testId]];
1044 |       forceButtonUpdate();
1045 |     }).catch(err => {
1046 |       console.error('Flag API error for computed param', err);
1047 |       param.flag = 'ERR';
1048 |       testParameters.value[testId] = [...testParameters.value[testId]];
1049 |       forceButtonUpdate();
1050 |     });
1051 | 
1052 |     // Ensure Vue reactivity
1053 |     testParameters.value[testId] = [...testParameters.value[testId]];
1054 |     return;
1055 |   }
1056 | };
1057 | 
1058 | const recalculateFormulasForSourceParameter = () => {
1059 |   try {
1060 |     const resultsMap = buildResultsMap();
1061 |     if (!formulas.value || formulas.value.length === 0) return;
1062 |     formulas.value.forEach(f => {
1063 |       // Ensure source variables exist and have numeric values
1064 |       const sourceIds = (f.source_variables || []).map(v => v.source_test_parameter_id || v.source_parameter_id || v.id).filter(Boolean);
1065 |       if (sourceIds.length === 0) return;
1066 |       const missing = sourceIds.some(id => resultsMap[id] === undefined);
1067 |       if (missing) return; // cannot compute if any source missing
1068 |       try {
1069 |         const computed = evaluateCompiledFormula(f.compiled_formula || f.compiledFormula || f.compiled || '', resultsMap);
1070 |         if (computed !== null && !isNaN(Number(computed))) {
1071 |           const targetId = f.target_test_parameter_id || f.target_parameter_id || (f.target_parameter && f.target_parameter.id);
1072 |           if (targetId) applyComputedValue(f, targetId, computed);
1073 |         }
1074 |       } catch (e) {
1075 |         // ignore evaluation errors for now
1076 |         // console.debug('Formula eval skipped:', e.message);
1077 |       }
1078 |     });
1079 |   } catch (e) {
1080 |     console.error('Error during formula recalculation:', e);
1081 |   }
1082 | };
1083 | 
1084 | // QC banner state and updater
1085 | const qcHoldBannerMessage = ref('');
1086 | const updateQCHoldBanner = () => {
1087 |   try {
1088 |     const involvedEquipmentIds = Array.from(new Set(
1089 |       Object.values(testParameters.value)
1090 |         .flat()
1091 |         .map(p => (p.equipment_id ?? p.instrument))
1092 |         .filter(id => id != null)
1093 |         .map(id => parseInt(id))
1094 |     ));
1095 |     if (involvedEquipmentIds.length > 0 && testCategoryId.value) {
1096 |       const categoryId = testCategoryId.value;
1097 |       const now = Date.now();
1098 |       
1099 |       if (staticCache.qcStatus[categoryId] && (now - staticCache.qcStatus[categoryId].timestamp < CACHE_TTL)) {
1100 |         console.log(`Using cached QC status for category: ${categoryId}`);
1101 |         const data = staticCache.qcStatus[categoryId].data;
1102 |         const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
1103 |         if (failing.length > 0) {
1104 |           const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
1105 |           qcHoldBannerMessage.value = lines.join(' | ');
1106 |         } else {
1107 |           qcHoldBannerMessage.value = '';
1108 |         }
1109 |         return;
1110 |       }
1111 | 
1112 |       api.get(`/qc-lockout/status-summary`, { params: { test_category_id: categoryId } })
1113 |         .then(({ data }) => {
1114 |           // Update cache
1115 |           staticCache.qcStatus[categoryId] = {
1116 |             data: data,
1117 |             timestamp: now
1118 |           };
1119 | 
1120 |           const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
1121 |           if (failing.length > 0) {
1122 |             const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
1123 |             qcHoldBannerMessage.value = lines.join(' | ');
1124 |           } else {
1125 |             qcHoldBannerMessage.value = '';
1126 |           }
1127 |         })
1128 |         .catch((error) => { 
1129 |           if (error.response?.status === 429) {
1130 |             console.warn(`Rate limited while fetching QC status for category: ${categoryId}`);
1131 |             if (staticCache.qcStatus[categoryId]) {
1132 |               const data = staticCache.qcStatus[categoryId].data;
1133 |               const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
1134 |               if (failing.length > 0) {
1135 |                 const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
1136 |                 qcHoldBannerMessage.value = lines.join(' | ');
1137 |               }
1138 |             }
1139 |           }
1140 |           /* keep previous banner state */ 
1141 |         });
1142 |     } else {
1143 |       qcHoldBannerMessage.value = '';
1144 |     }
1145 |   } catch (_) {
1146 |     // ignore
1147 |   }
1148 | };
1149 | 
1150 | // Active parameters in the current category view
1151 | const activeParameterIds = computed(() => {
1152 |   const ids = new Set();
1153 |   Object.values(testParameters.value).flat().forEach(p => {
1154 |     if (p) {
1155 |       if (p.id) ids.add(Number(p.id));
1156 |       if (p.parameterId) ids.add(Number(p.parameterId));
1157 |       if (p.test_parameter_id) ids.add(Number(p.test_parameter_id));
1158 |     }
1159 |   });
1160 |   return ids;
1161 | });
1162 | 
1163 | // CDS alerts state and updater
1164 | const cdsAlerts = ref([]);
1165 | const checkCdsRules = async (force = false) => {
1166 |   if (!props.requestDetails?.id) {
1167 |     return;
1168 |   }
1169 | 
1170 |   const requestId = props.requestDetails.id;
1171 |   const now = Date.now();
1172 |   if (!force && staticCache.cdsRules[requestId] && (now - staticCache.cdsRules[requestId].timestamp < CACHE_TTL)) {
1173 |     console.log(`Using cached CDS rules for request: ${requestId}`);
1174 |     const cachedData = staticCache.cdsRules[requestId].data;
1175 |     if (cachedData && cachedData.length > 0) {
1176 |       cdsAlerts.value = cachedData.filter(r => activeParameterIds.value.has(Number(r.trigger_test_id)));
1177 |     } else {
1178 |       cdsAlerts.value = [];
1179 |     }
1180 |     return;
1181 |   }
1182 | 
1183 |   try {
1184 |     const response = await api.get(`/results/check-cds/${props.requestDetails.id}`);
1185 |     const data = response.data;
1186 |     
1187 |     // Update cache
1188 |     staticCache.cdsRules[requestId] = {
1189 |       data: data.triggered_rules || [],
1190 |       timestamp: now
1191 |     };
1192 | 
1193 |     if (data.triggered_rules && data.triggered_rules.length > 0) {
1194 |       // Filter the triggered rules to only those relevant to the currently viewed parameters
1195 |       const filteredRules = data.triggered_rules.filter(r => activeParameterIds.value.has(Number(r.trigger_test_id)));
1196 |       cdsAlerts.value = filteredRules;
1197 |       
1198 |       // Show warning if there are blocking rules within this category
1199 |       const blockingRules = filteredRules.filter(r => r.is_blocking);
1200 |       if (blockingRules.length > 0) {
1201 |         ElMessage.warning({
1202 |           message: `${blockingRules.length} CDS rule(s) triggered for this category. Review required before validation.`,
1203 |           duration: 5000
1204 |         });
1205 |       }
1206 |     } else {
1207 |       cdsAlerts.value = [];
1208 |     }
1209 |   } catch (error) {
1210 |     if (error.response?.status === 429) {
1211 |       console.warn(`Rate limited while checking CDS rules for request: ${requestId}`);
1212 |       if (staticCache.cdsRules[requestId]) {
1213 |         const cachedData = staticCache.cdsRules[requestId].data;
1214 |         if (cachedData && cachedData.length > 0) {
1215 |           cdsAlerts.value = cachedData.filter(r => activeParameterIds.value.has(Number(r.trigger_test_id)));
1216 |         } else {
1217 |           cdsAlerts.value = [];
1218 |         }
1219 |         return;
1220 |       }
1221 |     }
1222 |     console.error('Error checking CDS rules:', error);
1223 |     // Don't show error to user, just log it
1224 |   }
1225 | };
1226 | 
1227 | const fetchAuditTrail = async (force = false) => {
1228 |   if (!props.requestDetails?.id) return;
1229 |   
1230 |   const requestId = props.requestDetails.id;
1231 |   const now = Date.now();
1232 |   if (!force && staticCache.auditTrail[requestId] && (now - staticCache.auditTrail[requestId].timestamp < CACHE_TTL)) {
1233 |     console.log(`Using cached audit trail for request: ${requestId}`);
1234 |     auditTrail.value = staticCache.auditTrail[requestId].data;
1235 |     return;
1236 |   }
1237 | 
1238 |   auditTrailLoading.value = true;
1239 |   console.log('Fetching audit trail for request:', requestId);
1240 |   try {
1241 |     const response = await api.get(`/laboratory-requests/${requestId}/audit-trail`);
1242 |     const data = response.data || [];
1243 |     
1244 |     // Update cache
1245 |     staticCache.auditTrail[requestId] = {
1246 |       data: data,
1247 |       timestamp: now
1248 |     };
1249 |     
1250 |     auditTrail.value = data;
1251 |   } catch (error) {
1252 |     if (error.response?.status === 429) {
1253 |       console.warn(`Rate limited while fetching audit trail for request: ${requestId}`);
1254 |       if (staticCache.auditTrail[requestId]) {
1255 |         auditTrail.value = staticCache.auditTrail[requestId].data;
1256 |         return;
1257 |       }
1258 |     }
1259 |     console.error('Error fetching audit trail:', error);
1260 |     ElMessage.error('Failed to load audit trail history');
1261 |   } finally {
1262 |     auditTrailLoading.value = false;
1263 |   }
1264 | };
1265 | 
1266 | // Delta Check Hold Status - Flagged results detection
1267 | const flaggedDeltaChecks = ref([]);
1268 | const allDeltaChecks = ref([]);
1269 | const hasHoldStatus = ref(false);
1270 | 
1271 | // Check for flagged delta checks when modal opens or request details change
1272 | const checkFlaggedDeltaChecks = async () => {
1273 |   if (!props.requestDetails?.id) {
1274 |     flaggedDeltaChecks.value = [];
1275 |     hasHoldStatus.value = false;
1276 |     return;
1277 |   }
1278 | 
1279 |   try {
1280 |     const requestId = props.requestDetails.id;
1281 |     const now = Date.now();
1282 |     let allFlaggedChecks = [];
1283 | 
1284 |     if (staticCache.allFlaggedDeltaChecks.data.length > 0 && (now - staticCache.allFlaggedDeltaChecks.timestamp < CACHE_TTL)) {
1285 |       console.log('Using cached flagged delta checks');
1286 |       allFlaggedChecks = staticCache.allFlaggedDeltaChecks.data;
1287 |     } else {
1288 |       console.log('Checking flagged delta checks for request ID:', requestId);
1289 |       
1290 |       // Fetch flagged delta checks for this laboratory request
1291 |       const response = await api.get('/management-report/delta-checks', {
1292 |         params: {
1293 |           status: 'flagged',
1294 |           per_page: 1000,
1295 |           page: 1
1296 |         }
1297 |       });
1298 | 
1299 |       allFlaggedChecks = response.data?.data || [];
1300 |       
1301 |       // Update cache
1302 |       staticCache.allFlaggedDeltaChecks.data = allFlaggedChecks;
1303 |       staticCache.allFlaggedDeltaChecks.timestamp = now;
1304 |     }
1305 | 
1306 |     console.log('Total flagged delta checks retrieved:', allFlaggedChecks.length);
1307 |     if (allFlaggedChecks.length > 0) {
1308 |       console.log('Sample delta check structure:', allFlaggedChecks[0]);
1309 |     }
1310 |     
1311 |     // Filter to only include checks for this laboratory request
1312 |     // Try multiple ways to access the laboratory_request_id
1313 |     flaggedDeltaChecks.value = allFlaggedChecks.filter(dc => {
1314 |       if (!dc || !dc.test_result) {
1315 |         return false;
1316 |       }
1317 |       
1318 |       // Try different paths to get laboratory_request_id
1319 |       const lrid = dc.test_result.laboratory_request_id || 
1320 |                    dc.test_result.laboratory_request?.id ||
1321 |                    (dc.test_result.laboratoryRequest && dc.test_result.laboratoryRequest.id) ||
1322 |                    null;
1323 |       
1324 |       // Convert both to numbers for comparison (in case of string vs number mismatch)
1325 |       const requestIdNum = Number(requestId);
1326 |       const lridNum = Number(lrid);
1327 |       
1328 |       const requestMatches = lridNum === requestIdNum || lrid === requestId;
1329 |       
1330 |       // Only include violations for parameters currently visible in this category's modal
1331 |       const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1332 |       const parameterMatches = testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1333 |       
1334 |       return requestMatches && parameterMatches;
1335 |     });
1336 | 
1337 |     // Check if any results have action_on_violation = 'flag_and_stop' which should set status to Hold
1338 |     const hasFlagAndStop = flaggedDeltaChecks.value.some(dc => 
1339 |       dc.action_on_violation === 'flag_and_stop' || 
1340 |       dc.action_on_violation === 'Flag and Stop'
1341 |     );
1342 |     
1343 |     // If we have flagged results with 'flag_and_stop', or if any flagged results exist (fallback)
1344 |     hasHoldStatus.value = hasFlagAndStop || flaggedDeltaChecks.value.length > 0;
1345 |     
1346 |     if (hasHoldStatus.value) {
1347 |       console.log('Hold status triggered. Flagged checks:', flaggedDeltaChecks.value.length, 
1348 |                   'Flag and Stop:', hasFlagAndStop);
1349 |     }
1350 |     
1351 |     console.log('Filtered flagged delta checks for request:', flaggedDeltaChecks.value.length);
1352 |     console.log('Has Hold status:', hasHoldStatus.value);
1353 |   } catch (error) {
1354 |     if (error.response?.status === 429) {
1355 |       console.warn('Rate limited while checking flagged delta checks');
1356 |       // If we have cached data, use it but don't re-filter yet as it's already done or we can't fetch new
1357 |       if (staticCache.allFlaggedDeltaChecks.data.length > 0) {
1358 |         // We still need to filter for the current request and correct parameters
1359 |         const requestId = props.requestDetails.id;
1360 |         flaggedDeltaChecks.value = staticCache.allFlaggedDeltaChecks.data.filter(dc => {
1361 |           if (!dc || !dc.test_result) return false;
1362 |           const lrid = dc.test_result.laboratory_request_id || dc.test_result.laboratory_request?.id;
1363 |           const requestMatches = Number(lrid) === Number(requestId);
1364 |           const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1365 |           const parameterMatches = testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1366 |           return requestMatches && parameterMatches;
1367 |         });
1368 |         hasHoldStatus.value = flaggedDeltaChecks.value.length > 0;
1369 |         return;
1370 |       }
1371 |     }
1372 |     console.error('Error checking flagged delta checks:', error);
1373 |     flaggedDeltaChecks.value = [];
1374 |     hasHoldStatus.value = false;
1375 |   }
1376 | };
1377 | 
1378 | const checkAllDeltaChecks = async () => {
1379 |   if (!props.requestDetails?.id) {
1380 |     allDeltaChecks.value = [];
1381 |     return;
1382 |   }
1383 | 
1384 |   try {
1385 |     const requestId = props.requestDetails.id;
1386 |     const now = Date.now();
1387 |     const cached = staticCache.deltaChecks[requestId];
1388 |     if (cached && (now - cached.timestamp < CACHE_TTL)) {
1389 |       const requestMatchesList = cached.data || [];
1390 |       allDeltaChecks.value = requestMatchesList.filter(dc => {
1391 |         const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1392 |         return testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1393 |       });
1394 |       return;
1395 |     }
1396 | 
1397 |     const params = {
1398 |       per_page: 1000,
1399 |       page: 1
1400 |     };
1401 |     if (props.requestDetails?.patient?.id) {
1402 |       params.patient_id = props.requestDetails.patient.id;
1403 |     }
1404 | 
1405 |     const response = await api.get('/management-report/delta-checks', { params });
1406 |     const allChecks = response.data?.data || [];
1407 |     
1408 |     // Filter to only this request
1409 |     const requestMatchesList = allChecks.filter(dc => {
1410 |       if (!dc || !dc.test_result) {
1411 |         return false;
1412 |       }
1413 |       const lrid = dc.test_result.laboratory_request_id || 
1414 |                    dc.test_result.laboratory_request?.id ||
1415 |                    dc.test_result.laboratoryRequest?.id ||
1416 |                    null;
1417 |       return Number(lrid) === Number(requestId);
1418 |     });
1419 | 
1420 |     // Cache the unfiltered (by parameter) request matches
1421 |     staticCache.deltaChecks[requestId] = {
1422 |       data: requestMatchesList,
1423 |       timestamp: now
1424 |     };
1425 | 
1426 |     // Filter by parameter for the current view
1427 |     allDeltaChecks.value = requestMatchesList.filter(dc => {
1428 |       const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1429 |       return testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1430 |     });
1431 |   } catch (error) {
1432 |     if (error.response?.status === 429) {
1433 |       const requestId = props.requestDetails.id;
1434 |       const cached = staticCache.deltaChecks[requestId];
1435 |       if (cached && cached.data) {
1436 |         allDeltaChecks.value = cached.data.filter(dc => {
1437 |           const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1438 |           return testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1439 |         });
1440 |         return;
1441 |       }
1442 |     }
1443 |     console.error('Error fetching delta checks:', error);
1444 |     allDeltaChecks.value = [];
1445 |   }
1446 | };
1447 | 
1448 | // Accept/Reject/Re-run modal states
1449 | const showRejectModal = ref(false);
1450 | // const showRerunModal = ref(false);
1451 | const showViolationsModal = ref(false);
1452 | const activeViolationTab = ref('summary');
1453 | const pendingPostAckAction = ref(null);
1454 | const hasUnsavedChanges = ref(false);
1455 | 
1456 | // Logged-in user (for reviewer auto-fill)
1457 | // const authStore = useAuthStore?.() || null;
1458 | // const currentUser = authStore?.user || null;
1459 | 
1460 | const nowTimestamp = () => {
1461 |   try { return format(new Date(), 'yyyy-MM-dd HH:mm:ss'); } catch { return new Date().toISOString(); }
1462 | };
1463 | 
1464 | // Rules moved to sub-modal components
1465 | const rootCauseOptions = [
1466 |   { label: 'Pre-Analytical', value: 'pre_analytical' },
1467 |   { label: 'Analytical', value: 'analytical' },
1468 |   { label: 'Post-Analytical', value: 'post_analytical' }
1469 | ];
1470 | const actionTakenOptions = [
1471 |   { label: 'Sample Cancelled', value: 'sample_cancelled' },
1472 |   { label: 'Re-run Initiated (same sample)', value: 'rerun_initiated' },
1473 |   { label: 'Re-draw Initiated (new order)', value: 'redraw_initiated' }
1474 | ];
1475 | const acceptingResult = ref(false);
1476 | const rejectingResult = ref(false);
1477 | // const rerunningResult = ref(false);
1478 | 
1479 | // Form validation rules
1480 | const acceptRules = {
1481 |   reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
1482 |   investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
1483 |   accept_reason: [{ required: true, message: 'Reason is required', trigger: 'change' }],
1484 |   rationale: [
1485 |     { required: true, message: 'Detailed rationale is required', trigger: 'blur' },
1486 |     { min: 50, message: 'Rationale must be at least 50 characters', trigger: 'blur' }
1487 |   ],
1488 |   preanalytical_status: [{ required: true, message: 'Status is required', trigger: 'change' }]
1489 | };
1490 | 
1491 | const rejectRules = {
1492 |   reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
1493 |   scope: [{ required: true, message: 'Scope is required', trigger: 'change' }],
1494 |   test_ids: [{
1495 |     validator: (rule, value, callback) => {
1496 |       if (rejectForm.value.scope === 'tests') {
1497 |         if (Array.isArray(value) && value.length > 0) return callback();
1498 |         return callback(new Error('Select at least one test'));
1499 |       }
1500 |       if (rejectForm.value.scope === 'results') {
1501 |         if (selectedTests.value.length > 0) return callback();
1502 |         return callback(new Error('Select at least one parameter in the table'));
1503 |       }
1504 |       return callback();
1505 |     },
1506 |     trigger: 'change'
1507 |   }],
1508 |   investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
1509 |   root_cause: [{ required: true, message: 'Root cause is required', trigger: 'change' }],
1510 |   action_taken: [{ required: true, message: 'Action taken is required', trigger: 'change' }],
1511 |   rejection_comment: [
1512 |     { required: true, message: 'Detailed comment is required', trigger: 'blur' },
1513 |     { min: 50, message: 'Comment must be at least 50 characters', trigger: 'blur' }
1514 |   ]
1515 | };
1516 | 
1517 | // Arrays to store role-specific users fetched from the API
1518 | const loadedPathologists = ref([]);
1519 | const loadedVerifiers = ref([]);
1520 | const loadedPerformers = ref([]);
1521 | const loadingRoles = ref(false);
1522 | 
1523 | // Remove hardcoded constant and replace with reactive variable
1524 | const testCategoryId = ref(null);
1525 | 
1526 | // Define loadEquipment (replaces loadAnalyzers) before it's used in any watcher
1527 | const loadEquipment = async (testCategoryId = null) => {
1528 |   const cacheKey = testCategoryId || 'all';
1529 |   const now = Date.now();
1530 |   
1531 |   if (staticCache.equipment[cacheKey] && (now - staticCache.equipment[cacheKey].timestamp < CACHE_TTL)) {
1532 |     console.log(`Using cached equipment for ${cacheKey}`);
1533 |     equipment.value = staticCache.equipment[cacheKey].data;
1534 |     return;
1535 |   }
1536 | 
1537 |   loadingEquipment.value = true;
1538 |   try {
1539 |     let endpoint = 'eim/equipment';
1540 |     const params = { per_page: 1000 };
1541 |     
1542 |     // If a test category ID is provided, filter by category
1543 |     if (testCategoryId) {
1544 |       params.test_category_id = testCategoryId;
1545 |       console.log(`Loading equipment for test category: ${testCategoryId}`);
1546 |     } else {
1547 |       console.log('Loading all equipment (no category filter)');
1548 |     }
1549 |     
1550 |     const response = await api.get(endpoint, { params });
1551 |     const data = response.data.data || response.data || [];
1552 |     equipment.value = data;
1553 |     
1554 |     // Update cache
1555 |     staticCache.equipment[cacheKey] = {
1556 |       data: data,
1557 |       timestamp: now
1558 |     };
1559 |     
1560 |     console.log('Loaded equipment:', equipment.value);
1561 |   } catch (error) {
1562 |     if (error.response?.status === 429) {
1563 |       console.warn(`Rate limited while fetching equipment for ${cacheKey}`);
1564 |       if (staticCache.equipment[cacheKey]) {
1565 |         equipment.value = staticCache.equipment[cacheKey].data;
1566 |         return;
1567 |       }
1568 |     }
1569 |     console.error('Error loading equipment:', error);
1570 |     ElMessage.error('Failed to load equipment');
1571 |   } finally {
1572 |     loadingEquipment.value = false;
1573 |   }
1574 | };
1575 | 
1576 | // Alias for loadEquipment - kept for existing code references
1577 | const loadAnalyzers = async (testCategoryId = null) => {
1578 |   await loadEquipment(testCategoryId);
1579 | };
1580 | 
1581 | // Define convertAllSIValues before it's used in any watcher
1582 | const convertAllSIValues = () => {
1583 |   console.log('Converting all SI values to conventional...');
1584 |   
1585 |   // Loop through all tests and their parameters
1586 |   Object.keys(testParameters.value).forEach(testId => {
1587 |     const params = testParameters.value[testId];
1588 |     if (Array.isArray(params)) {
1589 |       params.forEach(param => {
1590 |         if (param.resultValue && !isNaN(parseFloat(param.resultValue)) && param.conversion_factor) {
1591 |           // Calculate conventional value
1592 |           const numericValue = parseFloat(param.resultValue);
1593 |           const conversionFactor = parseFloat(param.conversion_factor);
1594 |           param.conventional = (numericValue * conversionFactor).toFixed(2);
1595 |           console.log(`Auto-converted ${param.name}: ${param.resultValue} ${param.unit} → ${param.conventional} ${param.conventional_unit}`);
1596 |         }
1597 |       });
1598 |     }
1599 |   });
1600 | };
1601 | 
1602 | // Simple function to force reactive updates of button states
1603 | const forceButtonUpdate = () => {
1604 |   console.log('Forcing button state update...');
1605 |   
1606 |   // Log current parameter state for debugging
1607 |   const allParams = Object.values(testParameters.value).flat();
1608 |   const paramsWithValues = allParams.filter(param => 
1609 |     param && param.resultValue && param.resultValue.trim() !== ''
1610 |   );
1611 |   const savedParams = paramsWithValues.filter(param => param.result_id !== null);
1612 |   const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
1613 |   
1614 |   console.log(`Button state calculation:
1615 |     - Total parameters: ${allParams.length}
1616 |     - Parameters with values: ${paramsWithValues.length}
1617 |     - Saved parameters: ${savedParams.length}
1618 |     - Unsaved parameters: ${unsavedParams.length}
1619 |   `);
1620 | 
1621 |   // Manually compute expected button states
1622 |   const shouldDisableSave = unsavedParams.length === 0;
1623 |   const shouldDisablePrint = unsavedParams.length > 0;
1624 |   const shouldDisableValidate = unsavedParams.length > 0 || 
1625 |     props.requestDetails.tests.every(test => test.pivot && test.pivot.item_status === 'Result Printed');
1626 | 
1627 |   console.log(`Expected button states:
1628 |     - Save button should be disabled: ${shouldDisableSave}
1629 |     - Print button should be disabled: ${shouldDisablePrint}
1630 |     - Validate button should be disabled: ${shouldDisableValidate}
1631 |   `);
1632 | 
1633 |   // Force Vue to re-evaluate the computed properties
1634 |   nextTick(() => {
1635 |     console.log('Actual button states after update:');
1636 |     console.log('- Save button disabled:', disableSaveButton.value);
1637 |     console.log('- Print button disabled:', disablePrintButton.value);
1638 |     console.log('- Validate button disabled:', disableValidateButton.value);
1639 |   });
1640 | };
1641 | 
1642 | // Fetch users by role type
1643 | const fetchUsersByRole = async (roleType) => {
1644 |   const now = Date.now();
1645 |   if (staticCache.roles[roleType] && (now - staticCache.roles[roleType].timestamp < CACHE_TTL)) {
1646 |     console.log(`Using cached users for role: ${roleType}`);
1647 |     return staticCache.roles[roleType].data;
1648 |   }
1649 | 
1650 |   try {
1651 |     const response = await api.get(`/users/role/${roleType}`);
1652 |     const data = response.data;
1653 |     
1654 |     // Update cache
1655 |     staticCache.roles[roleType] = {
1656 |       data: data,
1657 |       timestamp: now
1658 |     };
1659 |     
1660 |     return data;
1661 |   } catch (error) {
1662 |     if (error.response?.status === 429) {
1663 |       console.warn(`Rate limited while fetching users for role: ${roleType}`);
1664 |       if (staticCache.roles[roleType]) {
1665 |         return staticCache.roles[roleType].data;
1666 |       }
1667 |     }
1668 |     console.error(`Error fetching ${roleType}s:`, error);
1669 |     ElMessage.error(`Failed to load ${roleType}s`);
1670 |     return [];
1671 |   }
1672 | };
1673 | 
1674 | // Function to load all role-specific users
1675 | const loadRoleUsers = async () => {
1676 |   loadingRoles.value = true;
1677 |   try {
1678 |     // First load users by role type
1679 |     const [pathologists, verifiers, performers] = await Promise.all([
1680 |       fetchUsersByRole('pathologist'),
1681 |       fetchUsersByRole('verifier'),
1682 |       fetchUsersByRole('performer')
1683 |     ]);
1684 |     
1685 |     loadedPathologists.value = pathologists;
1686 |     loadedVerifiers.value = verifiers;
1687 |     loadedPerformers.value = performers;
1688 |     
1689 |     console.log('Loaded pathologists:', loadedPathologists.value);
1690 |     console.log('Loaded verifiers:', loadedVerifiers.value);
1691 |     console.log('Loaded performers:', loadedPerformers.value);
1692 |     
1693 |     // If we have specific user IDs from the test records, ensure they're loaded
1694 |     // This is for when users might not be in the role-specific lists
1695 |     const userIdsToLoad = [];
1696 |     
1697 |     // Skip IDs that are already in our loaded lists
1698 |     if (localSignatories.value.performedBy && 
1699 |         !loadedPerformers.value.some(user => user.id === localSignatories.value.performedBy)) {
1700 |       userIdsToLoad.push(localSignatories.value.performedBy);
1701 |     }
1702 |     
1703 |     if (localSignatories.value.verifiedBy && 
1704 |         !loadedVerifiers.value.some(user => user.id === localSignatories.value.verifiedBy)) {
1705 |       userIdsToLoad.push(localSignatories.value.verifiedBy);
1706 |     }
1707 |     
1708 |     if (localSignatories.value.pathologist && 
1709 |         !loadedPathologists.value.some(user => user.id === localSignatories.value.pathologist)) {
1710 |       userIdsToLoad.push(localSignatories.value.pathologist);
1711 |     }
1712 |     
1713 |     // If we have unique IDs to load
1714 |     if (userIdsToLoad.length > 0) {
1715 |       const uniqueIds = [...new Set(userIdsToLoad)];
1716 |       console.log('Loading additional users by ID:', uniqueIds);
1717 |       
1718 |       // Fetch additional users by ID
1719 |       try {
1720 |         const userPromises = uniqueIds.map(id => api.get(`/users/${id}`));
1721 |         const userResponses = await Promise.all(userPromises);
1722 |         
1723 |         // Add fetched users to appropriate lists
1724 |         userResponses.forEach(response => {
1725 |           const user = response.data;
1726 |           if (!user || !user.id) return;
1727 |           
1728 |           // Check if this user ID matches any of our signatories
1729 |           if (user.id === localSignatories.value.performedBy) {
1730 |             // Add to performers if not already there
1731 |             if (!loadedPerformers.value.some(p => p.id === user.id)) {
1732 |               loadedPerformers.value.push(user);
1733 |               console.log(`Added performer ${user.name} to performers list`);
1734 |             }
1735 |           }
1736 |           
1737 |           if (user.id === localSignatories.value.verifiedBy) {
1738 |             // Add to verifiers if not already there
1739 |             if (!loadedVerifiers.value.some(v => v.id === user.id)) {
1740 |               loadedVerifiers.value.push(user);
1741 |               console.log(`Added verifier ${user.name} to verifiers list`);
1742 |             }
1743 |           }
1744 |           
1745 |           if (user.id === localSignatories.value.pathologist) {
1746 |             // Add to pathologists if not already there
1747 |             if (!loadedPathologists.value.some(p => p.id === user.id)) {
1748 |               loadedPathologists.value.push(user);
1749 |               console.log(`Added pathologist ${user.name} to pathologists list`);
1750 |             }
1751 |           }
1752 |         });
1753 |       } catch (error) {
1754 |         console.error('Error loading users by ID:', error);
1755 |       }
1756 |     }
1757 |   } catch (error) {
1758 |     console.error('Error loading role users:', error);
1759 |     ElMessage.error('Failed to load signatories');
1760 |   } finally {
1761 |     loadingRoles.value = false;
1762 |   }
1763 | };
1764 | 
1765 | // Import the calculate age method from the parent component
1766 | const calculateAge = (dob) => {
1767 |   if (!dob) return 'N/A';
1768 |   
1769 |   try {
1770 |     const birthDate = parseISO(dob);
1771 |     const today = new Date();
1772 |     
1773 |     // Calculate differences
1774 |     const years = differenceInYears(today, birthDate);
1775 |     const months = differenceInMonths(today, birthDate);
1776 |     const days = differenceInDays(today, birthDate);
1777 |     
1778 |     // Return appropriate unit based on age
1779 |     if (years >= 1) {
1780 |       return `${years}`;
1781 |     } else if (months >= 1) {
1782 |       return `${months}`;
1783 |     } else {
1784 |       return `${days}`;
1785 |     }
1786 |   } catch (e) {
1787 |     console.error('Error calculating age:', e);
1788 |     return 'Invalid Date';
1789 |   }
1790 | };
1791 | 
1792 | // Compute the suffix unit based on the patient's age
1793 | const suffixUnit = computed(() => {
1794 |   if (!props.requestDetails?.patient?.date_of_birth) return 'years';
1795 |   
1796 |   const birthDate = parseISO(props.requestDetails.patient.date_of_birth);
1797 |   const today = new Date();
1798 |   
1799 |   const years = differenceInYears(today, birthDate);
1800 |   const months = differenceInMonths(today, birthDate);
1801 |   
1802 |   if (years >= 1) {
1803 |     return years === 1 ? 'year' : 'years';
1804 |   } else if (months >= 1) {
1805 |     return months === 1 ? 'month' : 'months';
1806 |   } else {
1807 |     return 'days';
1808 |   }
1809 | });
1810 | 
1811 | // Helper function to determine the correct parameter name
1812 | const getParameterName = (param, test) => {
1813 |   // Log the parameter object to debug
1814 |   console.log('Getting name for parameter:', param);
1815 |   
1816 |   // First try to get name directly from param
1817 |   if (param.name && typeof param.name === 'string' && param.name.trim() !== '') {
1818 |     return param.name.trim();
1819 |   }
1820 |   
1821 |   // Next try test_name
1822 |   if (param.test_name && typeof param.test_name === 'string' && param.test_name.trim() !== '') {
1823 |     return param.test_name.trim();
1824 |   }
1825 |   
1826 |   // Try the testParameter relationship if it exists
1827 |   if (param.testParameter && param.testParameter.name) {
1828 |     return param.testParameter.name.trim();
1829 |   }
1830 |   
1831 |   // If all else fails, use the parameter ID
1832 |   return `Parameter ${param.id}`;
1833 | };
1834 | 
1835 | // Fetch parameters for a single test
1836 | const fetchTestParameters = async (testId, resultsDataSource = null) => {
1837 |   if (!testId) return;
1838 |   
1839 |   console.log(`Log 4: fetchTestParameters called for testId: ${testId} with resultsDataSource:`, JSON.parse(JSON.stringify(resultsDataSource)));
1840 |   
1841 |   try {
1842 |     // Get the parameters for this test
1843 |     const response = await api.get(`/tests/${testId}/parameters`);
1844 |     
1845 |     if (!Array.isArray(response.data)) {
1846 |       console.error('Invalid response data format (not an array):', response.data);
1847 |       testParameters.value[testId] = [];
1848 |       return;
1849 |     }
1850 |     
1851 |     console.log(`Received ${response.data.length} parameters for test ID ${testId}:`, response.data);
1852 |     console.log('First parameter sample:', {
1853 |       id: response.data[0]?.id,
1854 |       name: response.data[0]?.name,
1855 |       conversion_factor: response.data[0]?.conversion_factor,
1856 |       unit: response.data[0]?.unit,
1857 |       conventional_unit: response.data[0]?.conventional_unit
1858 |     });
1859 | 
1860 |     let needsMoreDetails = false;
1861 |     for (const param of response.data) {
1862 |       if (param.test_name && (!param.name || param.name.trim() === '')) {
1863 |         param.name = param.test_name;
1864 |       }
1865 |       if ((!param.name || param.name.trim() === '') && param.id) {
1866 |         needsMoreDetails = true;
1867 |         break;
1868 |       }
1869 |     }
1870 | 
1871 |     if (needsMoreDetails) {
1872 |       console.log('Some parameters missing names, getting more details...');
1873 |       try {
1874 |         // Pass the resultsDataSource to fetchParametersWithDetails
1875 |         await fetchParametersWithDetails(testId, response.data, resultsDataSource);
1876 |         return; 
1877 |       } catch (detailsError) {
1878 |         console.error('Failed to get parameter details, falling back to basic parameters:', detailsError);
1879 |       }
1880 |     }
1881 | 
1882 |     // Determine the source for saved results
1883 |     // Build fallback mapping from requestDetails.results if testParameters is missing
1884 |     let fallbackResultsMapByTest = null;
1885 |     if (!resultsDataSource && (!props.requestDetails.testParameters || Object.keys(props.requestDetails.testParameters).length === 0)) {
1886 |       const resultsArr = props.requestDetails.results || [];
1887 |       if (Array.isArray(resultsArr) && resultsArr.length > 0) {
1888 |         fallbackResultsMapByTest = {};
1889 |         resultsArr.forEach(r => {
1890 |           const tId = r.test_id;
1891 |           const pId = r.test_parameter?.id ?? r.test_parameter_id;
1892 |           if (!tId || !pId) return;
1893 |           if (!fallbackResultsMapByTest[tId]) fallbackResultsMapByTest[tId] = [];
1894 |           fallbackResultsMapByTest[tId].push({
1895 |             id: pId,
1896 |             result_id: r.id,
1897 |             resultValue: r.result_value,
1898 |             unit: r.unit,
1899 |             instrument: r.equipment_id,
1900 |             resultComment: r.test_parameter?.result_comment ?? '',
1901 |             flag: r.flag ?? null,
1902 |             conventional: '',
1903 |             conventional_unit: '',
1904 |             conversion_factor: null,
1905 |             version: r.version || 'R0',
1906 |             status: r.status || 'pending',
1907 |             created_at: r.created_at,
1908 |             updated_at: r.updated_at
1909 |           });
1910 |         });
1911 |       }
1912 |     }
1913 |     const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters || fallbackResultsMapByTest || [];
1914 |     console.log(`Log 5: testId: ${testId} - sourceForSavedResults chosen:`, resultsDataSource ? "resultsDataSource (passed in)" : "props.requestDetails.testParameters (fallback)");
1915 |     console.log(`Log 5.1: testId: ${testId} - content of sourceForSavedResults:`, JSON.parse(JSON.stringify(sourceForSavedResults)));
1916 | 
1917 |     const savedResults = sourceForSavedResults?.[testId] || [];
1918 |     console.log(`Log 6: testId: ${testId} - savedResults extracted for this testId:`, JSON.parse(JSON.stringify(savedResults)));
1919 |     
1920 |     const savedResultsMap = {};
1921 |     if (Array.isArray(savedResults)) {
1922 |       savedResults.forEach(result => {
1923 |         const paramId = (result && (
1924 |           result.test_parameter_id ??
1925 |           result?.test_parameter?.id ??
1926 |           result.parameterId ??
1927 |           result.id
1928 |         ));
1929 |         if (paramId) {
1930 |           if (!savedResultsMap[paramId]) savedResultsMap[paramId] = [];
1931 |           savedResultsMap[paramId].push(result);
1932 |         }
1933 |       });
1934 |     }
1935 |     
1936 |     // Version selection helpers (basic mapping path)
1937 |     const rankVersionBasic = (v) => {
1938 |       if (!v) return 0;
1939 |       const m = String(v).match(/R(\d+)/i);
1940 |       return m ? parseInt(m[1], 10) : 0;
1941 |     };
1942 |     const pickLatestBasic = (list = []) => {
1943 |       if (!Array.isArray(list) || list.length === 0) return null;
1944 |       return list.slice().sort((a,b) => {
1945 |         const ra = rankVersionBasic(a.version);
1946 |         const rb = rankVersionBasic(b.version);
1947 |         if (rb !== ra) return rb - ra;
1948 |         const ca = new Date(a.created_at || 0).getTime();
1949 |         const cb = new Date(b.created_at || 0).getTime();
1950 |         return cb - ca;
1951 |       })[0];
1952 |     };
1953 |     const pickPreviousBasic = (list = [], current) => {
1954 |       if (!Array.isArray(list)) return null;
1955 |       const filtered = list.filter(r => !current || r !== current);
1956 |       if (filtered.length === 0) return null;
1957 |       return filtered.slice().sort((a,b) => {
1958 |         const ra = rankVersionBasic(a.version);
1959 |         const rb = rankVersionBasic(b.version);
1960 |         if (rb !== ra) return rb - ra;
1961 |         const ca = new Date(a.created_at || 0).getTime();
1962 |         const cb = new Date(b.created_at || 0).getTime();
1963 |         return cb - ca;
1964 |       })[0];
1965 |     };
1966 | 
1967 |     testParameters.value[testId] = response.data
1968 |       .filter(param => param && param.id)
1969 |       .map(param => {
1970 |         const savedList = savedResultsMap[param.id] || [];
1971 |         const current = pickLatestBasic(savedList);
1972 |         const previous = pickPreviousBasic(savedList, current);
1973 |         const paramObj = {
1974 |           id: param.id,
1975 |           name: param.name || param.test_name || '',
1976 |           test_name: param.test_name || param.name || `Parameter ${param.id}`,
1977 |           // When in re-run mode and only R0 exists, keep the input blank
1978 |           resultValue: current
1979 |             ? (isForRerun.value && String(current.version || 'R0').toUpperCase() === 'R0'
1980 |                 ? ''
1981 |                 : (current.resultValue ?? current.result_value ?? ''))
1982 |             : '',
1983 |           // Preserve the raw current saved value regardless of UI blanking
1984 |           currentResultRaw: current ? (current.resultValue ?? current.result_value ?? '') : '',
1985 |           unit: param.unit || '',
1986 |           instrument: current ? current.instrument || null : null,
1987 |           resultComment: current ? (current.resultComment || '') : '',
1988 |           flag: current ? current.flag || null : null,
1989 |           referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : [],
1990 |           conventional: current ? current.conventional || '' : '',
1991 |           conventional_unit: param.conventional_unit || '',
1992 |           conversion_factor: param.conversion_factor || null,
1993 |           result_id: current && current.result_id ? current.result_id : (current ? current.id : null),
1994 |           version: current ? (current.version || 'R0') : 'R0',
1995 |           status: current ? (current.status || 'pending') : 'pending',
1996 |           previousVersionStatus: previous ? `${previous.version || 'R0'} - ${previous.status || 'pending'}` : null,
1997 |           // For Original Result: if previous exists use it; otherwise when only R0 exists, use current R0
1998 |           previousValueRaw: previous
1999 |             ? (previous.resultValue ?? previous.result_value ?? '')
2000 |             : ((isForRerun.value && current && String(current.version || 'R0').toUpperCase() === 'R0')
[Tool]
File: frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue
IMPORTANT: File content truncated.
	Status: Showing lines 1-2000 of 6297 total lines.
	To read more: Use the read_file tool with offset=2001 and limit=2000.
	
	   1 | <template>
   2 |   <Teleport to="#app">
   3 | <el-dialog
   4 |   :model-value="props.visible"
   5 |   :fullscreen="true"
   6 |   class="hematology-modal"
   7 |   @close="handleClose"
   8 |   @opened="onDialogOpened"
   9 |   v-loading="props.loading || parametersLoading"
  10 | >
  11 |   <!-- Sticky Header / Toolbar -->
  12 |   <div class="result-header">
  13 |     <div class="header-left">
  14 |       <div class="brand">
  15 |         <div class="brand-logo-wrapper">
  16 |           <span class="brand-logo">🧪</span>
  17 |         </div>
  18 |         <div class="brand-text">
  19 |           <span class="brand-name">{{ modalTitle }}</span>
  20 |           <span class="brand-tagline">Laboratory Information System</span>
  21 |         </div>
  22 |       </div>
  23 |     </div>
  24 | 
  25 |     <div class="global-actions">
  26 |       <el-button-group>
  27 |         <el-button type="danger" plain @click="handleRejectAction" :disabled="disableRejectButton">
  28 |           <el-icon><Close /></el-icon>
  29 |           <span>{{ rejectButtonLabel }}</span>
  30 |         </el-button>
  31 |         <el-button type="warning" plain @click="handleRerunAction" :disabled="disableRerunButton">
  32 |           <el-icon><Refresh /></el-icon>
  33 |           <span>{{ rerunButtonLabel }}</span>
  34 |         </el-button>
  35 |         <el-button type="success" plain @click="handleAcceptResult" :disabled="disableAcceptButton">
  36 |           <el-icon><Check /></el-icon>
  37 |           <span>Accept & Acknowledge</span>
  38 |         </el-button>
  39 |       </el-button-group>
  40 |       <div class="header-divider"></div>
  41 |       <el-button-group>
  42 |         <el-button type="warning" plain @click="triggerSave" :disabled="disableSaveButton">
  43 |           <el-icon><Document /></el-icon>
  44 |           <span>Save Draft</span>
  45 |         </el-button>
  46 |         <el-button type="primary" @click="handlePrintRelease" :disabled="disablePrintButton">
  47 |           <el-icon><Printer /></el-icon>
  48 |           <span>Print & Export</span>
  49 |         </el-button>
  50 |         <el-button type="success" @click="handleValidateRelease" :disabled="disableValidateButton">
  51 |           <el-icon><Check /></el-icon>
  52 |           <span>Validate & Release</span>
  53 |         </el-button>
  54 |       </el-button-group>
  55 |       <div class="header-divider"></div>
  56 |       <el-button type="danger" circle @click="handleClose" class="close-btn">
  57 |         <el-icon><Close /></el-icon>
  58 |       </el-button>
  59 |     </div>
  60 |   </div>
  61 | 
  62 |   <!-- Rejected Banner (Read-only lock) -->
  63 |   <div v-if="isFullyRejected" class="status-banner banner-rejected">
  64 |     <div class="banner-content">
  65 |       <div class="banner-icon">
  66 |         <el-icon><Lock /></el-icon>
  67 |       </div>
  68 |       <div class="banner-text">
  69 |         <div class="banner-title">REQUEST REJECTED: READ-ONLY</div>
  70 |         <div class="banner-detail">
  71 |           No further actions allowed.
  72 |           <span v-if="latestRejectedAt"> Rejected on {{ formatDateTime(latestRejectedAt) }}</span>
  73 |           • <a href="#" @click.prevent="openAuditTrail" class="audit-link">View Audit Trail</a>
  74 |         </div>
  75 |       </div>
  76 |     </div>
  77 |   </div>
  78 | 
  79 |   <!-- Unified Alert Banner (Replaces Critical Action Banner & CDS Alert Banner) -->
  80 |   <div v-if="shouldShowUnifiedBanner" class="unified-banner-wrapper">
  81 |     <CdsAlertBanner
  82 |       :qc-hold-message="qcHoldBannerMessage"
  83 |       :has-hold-status="hasHoldStatus"
  84 |       :cds-alerts="cdsAlerts"
  85 |       :critical-values="criticalValues"
  86 |       :cds-acknowledge-data="cdsAcknowledgeData"
  87 |       :delta-acknowledge-data="deltaAcknowledgeData"
  88 |       :critical-acknowledge-data="criticalAcknowledgeData"
  89 |       :request-id="props.requestDetails.id"
  90 |       :on-acknowledge="handleAcknowledgeViolations"
  91 |       @approve="handleCdsAlertApproved"
  92 |       @deny="handleCdsAlertDenied"
  93 |       @acknowledge="handleAcknowledgeFromBanner"
  94 |       @refresh="refreshData"
  95 |     />
  96 |   </div>
  97 | 
  98 |   <div class="content-container" :class="{
  99 |     'with-banner': isFullyRejected || shouldShowUnifiedBanner
 100 |   }">
 101 |     <!-- Unified Patient & Request Information Banner (Professional Polish) -->
 102 |     <div class="patient-card-premium-unified">
 103 |       <!-- Left Side: Patient Information (Pill-based) -->
 104 |       <div class="patient-info-section">
 105 |         <div class="patient-main-info-wrapper">
 106 |           <div class="patient-avatar-wrapper">
 107 |             <div class="avatar-premium">
 108 |               {{ props.requestDetails.patient?.last_name?.[0] }}{{ props.requestDetails.patient?.first_name?.[0] }}
 109 |             </div>
 110 |             <div class="patient-gender-icon" :class="props.requestDetails.patient?.gender?.toLowerCase()">
 111 |               <el-icon v-if="props.requestDetails.patient?.gender?.toLowerCase() === 'male'"><User /></el-icon>
 112 |               <el-icon v-else><User /></el-icon>
 113 |             </div>
 114 |           </div>
 115 |           <div class="patient-info-content">
 116 |             <div class="patient-name-row">
 117 |               <h2 class="patient-name-premium">{{ props.requestDetails.patient?.last_name }}, {{ props.requestDetails.patient?.first_name }}</h2>
 118 |               <el-tag size="small" effect="dark" :type="props.requestDetails.priority_level?.toLowerCase() === 'stat' ? 'danger' : 'info'" class="priority-tag">
 119 |                 {{ props.requestDetails.priority_level || 'Routine' }}
 120 |               </el-tag>
 121 |             </div>
 122 |             <div class="patient-meta-container-premium">
 123 |               <div class="patient-meta-row-premium">
 124 |                 <span class="meta-pill"><el-icon><Memo /></el-icon> <b>ID:</b> {{ props.requestDetails.patient?.id || 'N/A' }}</span>
 125 |                 <span class="meta-pill"><el-icon><Calendar /></el-icon> <b>BIRTHDAY:</b> {{ formattedBirthday }}</span>
 126 |                 <span class="meta-pill"><el-icon><User /></el-icon> <b>AGE / SEX:</b> {{ formattedAgeGender }}</span>
 127 |               </div>
 128 |               <!-- Vertical Divider -->
 129 |               <div class="premium-horizontal-divider"></div>
 130 |               <div class="patient-meta-row-premium">
 131 |                 <span class="meta-pill"><el-icon><Phone /></el-icon> <b>CONTACT:</b> {{ formattedContact }}</span>
 132 |                 <span class="meta-pill"><el-icon><Location /></el-icon> <b>ADDRESS:</b> {{ formattedAddress }}</span>
 133 |                 <span class="meta-pill"><el-icon><Message /></el-icon> <b>EMAIL:</b> {{ formattedEmail }}</span>
 134 |               </div>
 135 |             </div>
 136 |           </div>
 137 |         </div>
 138 |       </div>
 139 | 
 140 |       <!-- Vertical Divider -->
 141 |       <div class="premium-vertical-divider"></div>
 142 | 
 143 |       <!-- Right Side: Request Details (Row-based, 2-column grid) -->
 144 |       <div class="request-details-section">
 145 |         <div class="clinical-context-v2-grid-unified">
 146 |           <div class="context-item-v2">
 147 |             <span class="c-label">ACCESSION NO.:</span>
 148 |             <span class="c-value">{{ props.requestDetails.accession_id || props.requestDetails.sample_id || 'N/A' }}</span>
 149 |           </div>
 150 |           <div class="context-item-v2">
 151 |             <span class="c-label">PRIORITY LEVEL:</span>
 152 |             <el-tag size="small" effect="dark" :type="props.requestDetails.priority_level?.toLowerCase() === 'stat' ? 'danger' : 'info'" class="priority-tag-inline">
 153 |               {{ props.requestDetails.priority_level || 'Routine' }}
 154 |             </el-tag>
 155 |           </div>
 156 |           <div class="context-item-v2">
 157 |             <span class="c-label">REQUESTING DOCTOR:</span>
 158 |             <span class="c-value">{{ requestingDoctor }}</span>
 159 |           </div>
 160 |           <div class="context-item-v2">
 161 |             <span class="c-label">REFERRED BY:</span>
 162 |             <span class="c-value">{{ referringDoctorName }}</span>
 163 |           </div>
 164 |           <div class="context-item-v2">
 165 |             <span class="c-label">PATIENT TYPE:</span>
 166 |             <span class="c-value">{{ patientTypeName }}</span>
 167 |           </div>
 168 |           <div class="context-item-v2">
 169 |             <span class="c-label">OR NO.:</span>
 170 |             <span class="c-value">{{ orNumber }}</span>
 171 |           </div>
 172 |           <div class="context-item-v2">
 173 |             <span class="c-label">LOCATION:</span>
 174 |             <span class="c-value">{{ props.requestDetails.ward?.name || '—' }} / {{ props.requestDetails.room?.name || '—' }}</span>
 175 |           </div>
 176 |           <div class="context-item-v2">
 177 |             <span class="c-label">RECEIVED:</span>
 178 |             <span class="c-value">{{ formatDateTime(props.requestDetails.request_date) }}</span>
 179 |           </div>
 180 |           <div class="context-item-v2">
 181 |             <span class="c-label">QC STATUS:</span>
 182 |             <div :class="['qc-badge-v2', qcHoldBannerMessage ? 'fail' : 'pass']">
 183 |               <el-icon><Check v-if="!qcHoldBannerMessage" /><Close v-else /></el-icon>
 184 |               {{ qcHoldBannerMessage ? 'FAIL' : 'PASS' }}
 185 |             </div>
 186 |           </div>
 187 |         </div>
 188 |       </div>
 189 |     </div>
 190 | 
 191 |     <!-- Results Table Toolbar (Professional Integrated Style) -->
 192 |     <div class="table-actions-toolbar">
 193 |       <div class="quick-links">
 194 |         <el-button-group>
 195 |           <el-button type="primary"  @click="showPatientHistory = true">
 196 |             <el-icon><Document /></el-icon> 
 197 |             <span>Delta Check (6m)</span>
 198 |           </el-button>
 199 |           <!-- <el-button type="info" plain size="small" @click="openAuditTrail">
 200 |             <el-icon><Search /></el-icon> Audit Trail
 201 |           </el-button> -->
 202 |           <el-button type="info" >
 203 |             <el-icon><Plus /></el-icon> 
 204 |             <span>Add Test</span>
 205 |           </el-button>
 206 |         </el-button-group>
 207 |       </div>
 208 | 
 209 |     </div>
 210 | 
 211 |     <!-- 2. Action Bar & Secondary Meta -->
 212 | 
 213 |     <!-- 3. Main Results Table -->
 214 |     <div class="results-table-card">
 215 |       <div class="table-header-premium">
 216 |         <div class="table-header-info">
 217 |           <el-icon class="table-icon"><DocumentCopy /></el-icon>
 218 |           <span class="table-title">TEST PARAMETERS & RESULTS</span>
 219 |         </div>
 220 |         <div class="table-header-legend">
 221 |           <div class="legend-pill normal"><span></span> Normal</div>
 222 |           <div class="legend-pill abnormal"><span></span> Abnormal</div>
 223 |           <div class="legend-pill critical"><span></span> Critical</div>
 224 |         </div>
 225 |       </div>
 226 |       <el-table
 227 |         v-if="modalReady"
 228 |         :data="formattedTestResults"
 229 |         style="width: 100%"
 230 |         :row-key="getRowKey"
 231 |         :row-class-name="getModalRowClassName"
 232 |         @selection-change="handleSelectionChange"
 233 |         size="small"
 234 |         class="premium-table"
 235 |       >
 236 |         <el-table-column type="selection" width="48" align="center" />
 237 |         
 238 |         <el-table-column label="VER" width="60" align="center">
 239 |           <template #default="{ row }">
 240 |             <span class="version-badge" :class="row.versionStatus?.toLowerCase()">{{ row.versionStatus }}</span>
 241 |           </template>
 242 |         </el-table-column>
 243 | 
 244 |         <el-table-column label="PARAMETER" min-width="220">
 245 |           <template #default="{ row }">
 246 |             <div class="parameter-cell-premium">
 247 |               <span class="p-name">{{ row.parameterName }}</span>
 248 |               <el-icon v-if="row.flag === 'L' || row.flag === 'H'" class="status-icon-critical"><WarningFilled /></el-icon>
 249 |               <el-icon v-else-if="row.flag === 'l' || row.flag === 'h'" class="status-icon-abnormal"><InfoFilled /></el-icon>
 250 |               <el-tooltip v-if="row.is_auto_calculated" content="Auto-calculated" placement="top">
 251 |                 <span class="calc-indicator">fx</span>
 252 |               </el-tooltip>
 253 |             </div>
 254 |           </template>
 255 |         </el-table-column>
 256 | 
 257 |         <el-table-column label="FLAG" width="80" align="center">
 258 |           <template #default="{ row }">
 259 |             <div v-if="row.flag" class="flag-indicator" :class="getFlagCharClass(row.flag)">
 260 |               {{ row.flag }}
 261 |             </div>
 262 |           </template>
 263 |         </el-table-column>
 264 | 
 265 |         <el-table-column label="S.I. UNITS" align="center">
 266 |           <el-table-column label="RESULT" width="130" align="center">
 267 |             <template #default="{ row }">
 268 |               <div class="result-input-container">
 269 |                 <el-input
 270 |                   v-if="row.isParameter"
 271 |                   v-model="row.resultValue"
 272 |                   @change="updateResultValue(row, $event)"
 273 |                   :disabled="row.is_auto_calculated || isFullyRejected"
 274 |                   size="small"
 275 |                   :class="['premium-input-center', getFlagClass(row.flag), { 'is-bold': isResultValueBold(row.flag) }]"
 276 |                   placeholder="—"
 277 |                 >
 278 |                   <template #suffix v-if="row.isParameter && row.is_auto_calculated">
 279 |                     <el-button link @click.stop="overrideAutoCalculated(row)" class="override-btn">
 280 |                       <el-icon><Edit /></el-icon>
 281 |                     </el-button>
 282 |                   </template>
 283 |                 </el-input>
 284 |               </div>
 285 |             </template>
 286 |           </el-table-column>
 287 | 
 288 |           <el-table-column v-if="isForRerun" label="ORIGINAL" width="100" align="center">
 289 |             <template #default="{ row }">
 290 |               <span class="original-value-text" v-if="row.isParameter">{{ row.originalValue ?? '—' }}</span>
 291 |             </template>
 292 |           </el-table-column>
 293 | 
 294 |           <el-table-column label="PREVIOUS" width="120" align="center">
 295 |             <template #default="{ row }">
 296 |               <div class="previous-value-group" v-if="row.previousValue !== undefined">
 297 |                 <span class="prev-val">{{ row.previousValue }}</span>
 298 |                 <span class="delta-badge" v-if="row.deltaPercent !== undefined" :class="{ 'delta-warning': Math.abs(row.deltaPercent) > 20, 'delta-normal': Math.abs(row.deltaPercent) <= 20 }">
 299 |                   {{ Number(row.deltaPercent) > 0 ? '+' : '' }}{{ Number(row.deltaPercent).toFixed(0) }}%
 300 |                 </span>
 301 |               </div>
 302 |               <span v-else class="text-placeholder">—</span>
 303 |             </template>
 304 |           </el-table-column>
 305 | 
 306 |           <el-table-column prop="unit" label="UNITS" width="90" align="center">
 307 |             <template #default="{ row }">
 308 |                <span class="unit-text">{{ row.unit }}</span>
 309 |             </template>
 310 |           </el-table-column>
 311 | 
 312 |           <el-table-column label="REF RANGE" width="140" align="center">
 313 |             <template #default="{ row }">
 314 |               <span class="ref-range-pill">{{ row.referenceRange }}</span>
 315 |             </template>
 316 |           </el-table-column>
 317 |         </el-table-column>
 318 | 
 319 |         <el-table-column label="CONVENTIONAL" align="center">
 320 |           <el-table-column label="RESULT" width="100" align="center">
 321 |             <template #default="{ row }">
 322 |               <span class="conventional-value">{{ row.conventional || '—' }}</span>
 323 |             </template>
 324 |           </el-table-column>
 325 |           <el-table-column prop="units" label="UNITS" width="90" align="center">
 326 |              <template #default="{ row }">
 327 |                <span class="unit-text-secondary">{{ row.units }}</span>
 328 |             </template>
 329 |           </el-table-column>
 330 |           <el-table-column prop="refRange" label="REF RANGE" width="90" align="center">
 331 |              <template #default="{ row }">
 332 |                <span class="unit-text-secondary">{{ row.refRange }}</span>
 333 |             </template>
 334 |           </el-table-column>
 335 |         </el-table-column>
 336 | 
 337 |         <el-table-column label="INSTRUMENT" width="180" align="center">
 338 |           <template #default="{ row }">
 339 |             <el-select 
 340 |               v-if="row.isParameter"
 341 |               v-model="row.instrument" 
 342 |               size="small"
 343 |               @change="(value) => handleInstrumentChange(row, value)"
 344 |               :disabled="isFullyRejected"
 345 |               class="premium-select"
 346 |             >
 347 |               <el-option v-for="eq in equipment" :key="eq.id" :label="eq.model" :value="eq.id" />
 348 |             </el-select>
 349 |           </template>
 350 |         </el-table-column>
 351 | 
 352 |         <el-table-column label="COMMENTS" min-width="180">
 353 |           <template #default="{ row }">
 354 |             <el-input
 355 |               v-if="row.isParameter"
 356 |               v-model="row.resultComment"
 357 |               @blur="updateParameterComment(row)"
 358 |               placeholder="Technical note..."
 359 |               size="small"
 360 |               class="comment-input-minimal"
 361 |             >
 362 |                <template #prefix><el-icon><EditPen /></el-icon></template>
 363 |             </el-input>
 364 |           </template>
 365 |         </el-table-column>
 366 |       </el-table>
 367 |     </div>
 368 | 
 369 |     <!-- 4. Footer: Notes & Signatories -->
 370 |     <div class="bottom-organization">
 371 |       <div class="note-box-premium">
 372 |         <div class="box-header-premium">
 373 |           <el-icon><EditPen /></el-icon>
 374 |           <span>Technical Remarks</span>
 375 |         </div>
 376 |         <el-input 
 377 |           v-model="localRemarks" 
 378 |           type="textarea" 
 379 |           :rows="3" 
 380 |           placeholder="Enter internal technical observations..." 
 381 |           class="premium-textarea"
 382 |         />
 383 |       </div>
 384 |       <div class="note-box-premium">
 385 |         <div class="box-header-premium">
 386 |           <el-icon><ChatDotRound /></el-icon>
 387 |           <span>Clinical Comments</span>
 388 |         </div>
 389 |         <el-input 
 390 |           v-model="localComments" 
 391 |           type="textarea" 
 392 |           :rows="3" 
 393 |           placeholder="Enter comments for the official report..." 
 394 |           class="premium-textarea"
 395 |         />
 396 |       </div>
 397 |     </div>
 398 | 
 399 |     <div class="bottom-organization">
 400 |       <div class="signatories-card-premium">
 401 |         <div class="box-header-premium">
 402 |           <el-icon><Avatar /></el-icon>
 403 |           <span>AUTHORIZED SIGNATORIES</span>
 404 |         </div>
 405 |         <div class="sig-grid-v2">
 406 |           <div class="sig-field-v2">
 407 |             <label>Performed By</label>
 408 |             <el-select v-model="localSignatories.performedBy" size="small" filterable class="premium-select">
 409 |               <el-option v-for="user in loadedPerformers" :key="user.id" :label="user.name" :value="user.id" />
 410 |             </el-select>
 411 |           </div>
 412 |           <div class="sig-field-v2">
 413 |             <label>Verified By</label>
 414 |             <el-select v-model="localSignatories.verifiedBy" size="small" filterable class="premium-select">
 415 |               <el-option v-for="user in loadedVerifiers" :key="user.id" :label="user.name" :value="user.id" />
 416 |             </el-select>
 417 |           </div>
 418 |           <div class="sig-field-v2">
 419 |             <label>Pathologist</label>
 420 |             <el-select v-model="localSignatories.pathologist" size="small" filterable class="premium-select">
 421 |               <el-option v-for="user in loadedPathologists" :key="user.id" :label="user.name" :value="user.id" />
 422 |             </el-select>
 423 |           </div>
 424 |         </div>
 425 |       </div>
 426 |     </div>
 427 |   </div>
 428 | 
 429 |     <!-- Sub-Modal Components -->
 430 | 
 431 |     <RejectResultModal
 432 |       v-model:visible="showRejectModal"
 433 |       :loading="loading"
 434 |       :current-user="currentUser"
 435 |       :request-details="requestDetails"
 436 |       :selected-tests="selectedTests"
 437 |       @submit="refreshData"
 438 |     />
 439 | 
 440 |     <RerunModal
 441 |       v-model:visible="showRerunModal"
 442 |       :loading="loading"
 443 |       :request-details="requestDetails"
 444 |       :current-user="currentUser"
 445 |       :selected-tests="selectedTests"
 446 |       @submit="refreshData"
 447 |     />
 448 | 
 449 |     <ViolationsSummaryModal
 450 |       v-if="showViolationsModal"
 451 |       v-model:visible="showViolationsModal"
 452 |       :request-details="requestDetails"
 453 |       :request-id="requestDetails.id"
 454 |       :cds-alerts="cdsAlerts"
 455 |       :flagged-delta-checks="flaggedDeltaChecks"
 456 |       :critical-values="criticalValues"
 457 |       :audit-trail="auditTrail"
 458 |       :active-tab="activeViolationTab"
 459 |       :is-acknowledged="hasAnyRuleAcknowledgment"
 460 |       @close="showViolationsModal = false"
 461 |       @acknowledge="handleAcknowledgeViolations"
 462 |       @validate="handleValidateRelease"
 463 |       @reflex-action-taken="handleReflexActionTaken"
 464 |     />
 465 | 
 466 |     <TemplatePrintDialog
 467 |       v-model:visible="showTemplatePrintDialog"
 468 |       :request-details="requestDetails"
 469 |     />
 470 | 
 471 |     <PatientHistoryModal
 472 |       v-model:visible="showPatientHistory"
 473 |       :patient-id="requestDetails?.patient?.id"
 474 |       :category-id="categoryId"
 475 |     />
 476 | 
 477 |     <!-- Reflex Approval Modal REMOVED - Using CdsAlertBanner instead -->
 478 |   </el-dialog>
 479 | </Teleport>
 480 | </template>
 481 | 
 482 | <script>
 483 | // Global cache for static/shared data to prevent 429 errors
 484 | const staticCache = {
 485 |   roles: {}, // { roleType: { data: [], timestamp: 0 } }
 486 |   equipment: {}, // { categoryId: { data: [], timestamp: 0 } }
 487 |   formulas: { data: [], timestamp: 0 },
 488 |   deltaChecks: {}, // { requestId: { data: [], timestamp: 0 } }
 489 |   cdsRules: {}, // { requestId: { data: [], timestamp: 0 } }
 490 |   allFlaggedDeltaChecks: { data: [], timestamp: 0 },
 491 |   qcStatus: {}, // { categoryId: { data: [], timestamp: 0 } }
 492 |   auditTrail: {} // { requestId: { data: [], timestamp: 0 } }
 493 | };
 494 | const CACHE_TTL = 30000; // 30 seconds cache
 495 | </script>
 496 | 
 497 | <script setup>
 498 | // Note: staticCache moved to parent script block for persistence across instances
 499 | 
 500 | /**
 501 |  * BUTTON STATE MANAGEMENT SYSTEM
 502 |  * 
 503 |  * This component uses computed properties to directly control button states:
 504 |  * - disableSaveButton → Save button
 505 |  * - disablePrintButton → Print & Release button
 506 |  * - disableValidateButton → Validate & Release button
 507 |  * 
 508 |  * These computed properties automatically react to changes in test parameters
 509 |  * and test statuses. No additional reactive variables are used for button states.
 510 |  */
 511 | import { ref, computed, reactive, watch, onMounted, onUnmounted, onBeforeUnmount, nextTick } from 'vue';
 512 | import {
 513 |   View,
 514 |   Document,
 515 |   Printer,
 516 |   Check,
 517 |   Close,
 518 |   User,
 519 |   Refresh,
 520 |   Plus,
 521 |   Loading,
 522 |   Lock,
 523 |   WarningFilled,
 524 |   SuccessFilled,
 525 |   InfoFilled,
 526 |   Calendar,
 527 |   Location,
 528 |   DocumentCopy,
 529 |   Memo,
 530 |   Avatar,
 531 |   FolderOpened,
 532 |   Filter,
 533 |   CircleClose,
 534 |   EditPen,
 535 |   ChatDotRound,
 536 |   Edit,
 537 |   Search,
 538 |   Warning,
 539 |   Operation
 540 | } from '@element-plus/icons-vue';
 541 | import { format, parseISO } from 'date-fns';
 542 | import { useAuthStore } from '@/stores/auth';
 543 | import api from '@/utils/axios';
 544 | import { ElMessage, ElMessageBox } from 'element-plus';
 545 | import { differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';
 546 | import TemplatePrintDialog from '../TemplatePrintDialog.vue';
 547 | import PatientHistoryModal from './PatientHistoryModal.vue';
 548 | import RejectResultModal from './RejectResultModal.vue';
 549 | import RerunModal from './RerunModal.vue';
 550 | import ViolationsSummaryModal from './ViolationsSummaryModal.vue';
 551 | import CdsAlertBanner from '@/components/CdsAlertBanner.vue';
 552 | 
 553 | // Lifecycle guards to prevent updates after unmount
 554 | const isMounted = ref(true)
 555 | const activeTimeouts = ref([])
 556 | 
 557 | const props = defineProps({
 558 |   visible: {
 559 |     type: Boolean,
 560 |     default: false,
 561 |   },
 562 |   requestDetails: {
 563 |     type: Object,
 564 |     default: () => ({ tests: [], patient: {}, performer_id: null, verifier_id: null, pathologist_id: null, remarks: '', comment: '' }),
 565 |   },
 566 |   loading: {
 567 |     type: Boolean,
 568 |     default: false,
 569 |   },
 570 |   verifiers: {
 571 |     type: Array,
 572 |     default: () => [],
 573 |   },
 574 |   performers: {
 575 |     type: Array,
 576 |     default: () => [],
 577 |   },
 578 |   pathologists: {
 579 |     type: Array,
 580 |     default: () => [],
 581 |   },
 582 |   categoryName: {
 583 |     type: String,
 584 |     default: '',
 585 |   },
 586 |   categoryId: {
 587 |     type: [String, Number],
 588 |     default: null,
 589 |   },
 590 | });
 591 | 
 592 | const emit = defineEmits(['update:visible', 'close', 'save', 'validate', 'print', 'refresh']);
 593 | 
 594 | const authStore = useAuthStore();
 595 | const currentUser = ref(null);
 596 | // Override data source for saved results to ensure version/status accuracy after re-run
 597 | const overrideResultsDataSource = ref(null);
 598 | // Show Original Result column only when request is tagged For Re-run
 599 | const isForRerun = ref(false);
 600 | 
 601 | onMounted(async () => {
 602 |   isMounted.value = true
 603 |   currentUser.value = authStore.user;
 604 | });
 605 | 
 606 | // Re-run state
 607 | const showRerunModal = ref(false);
 608 | const rerunningResult = ref(false);
 609 | const rerunFormRef = ref(null);
 610 | const rerunForm = reactive({
 611 |   reason: '',
 612 |   comments: ''
 613 | });
 614 | 
 615 | // Template print state
 616 | const showTemplatePrintDialog = ref(false);
 617 | const showPatientHistory = ref(false);
 618 | const rerunRules = {
 619 |   reason: [{ required: true, message: 'Please select a reason for the re-run.', trigger: 'change' }]
 620 | };
 621 | 
 622 | const selectedTests = ref([]);
 623 | 
 624 | // --- Dynamic Button Logic ---
 625 | const rejectButtonLabel = computed(() => {
 626 |   const count = selectedTests.value.length;
 627 |   return count > 0 ? `Reject Selected (${count})` : 'Reject Request';
 628 | });
 629 | 
 630 | const rerunButtonLabel = computed(() => {
 631 |   const count = selectedTests.value.length;
 632 |   return count > 0 ? `Rerun Selected (${count})` : 'Rerun Request';
 633 | });
 634 | 
 635 | const disableRejectButton = computed(() => {
 636 |   if (isFullyRejected.value || isFullyValidatedOrReleased.value) return true;
 637 |   return false;
 638 | });
 639 | 
 640 | const disableRerunButton = computed(() => {
 641 |   if (isFullyRejected.value || isFullyValidatedOrReleased.value) return true;
 642 |   return false;
 643 | });
 644 | 
 645 | const disableAcceptButton = computed(() => {
 646 |   if (isFullyRejected.value) return true;
 647 |   return !hasUnacknowledgedAlerts.value;
 648 | });
 649 | 
 650 | const handleRejectAction = async () => {
 651 |   if (isFullyRejected.value) {
 652 |     ElMessage.warning('This request is fully rejected and read-only.');
 653 |     return;
 654 |   }
 655 | 
 656 |   if (selectedTests.value.length === 0) {
 657 |     try {
 658 |       await ElMessageBox.confirm(
 659 |         `
 660 |         <div class="bulk-action-warning">
 661 |           <div style="color: #f56c6c; font-weight: bold; font-size: 16px; margin-bottom: 12px;">Warning: Bulk Action Detected</div>
 662 |           <p>You are about to reject the entire request (<b>#${props.requestDetails.accession_id || props.requestDetails.sample_id || 'N/A'}</b>). This cannot be undone.</p>
 663 |           <div style="margin-top: 16px; padding: 12px; background: #fef0f0; border-radius: 4px; border-left: 4px solid #f56c6c;">
 664 |             <p><b>To Reject Everything:</b> Click "Confirm Global Reject" and provide a reason in the next step.</p>
 665 |             <p style="margin-top: 8px;"><b>To Reject Specific Tests:</b> Close this window, select the individual parameters in the table, and click "Reject Selected."</p>
 666 |           </div>
 667 |         </div>
 668 |         `,
 669 |         'Bulk Action Detected',
 670 |         {
 671 |           confirmButtonText: 'Confirm Global Reject',
 672 |           cancelButtonText: 'Close',
 673 |           type: 'warning',
 674 |           dangerouslyUseHTMLString: true,
 675 |           confirmButtonClass: 'el-button--danger'
 676 |         }
 677 |       );
 678 |       
 679 |       showRejectModal.value = true;
 680 |     } catch (e) {
 681 |       // User cancelled
 682 |     }
 683 |   } else {
 684 |     showRejectModal.value = true;
 685 |   }
 686 | };
 687 | 
 688 | const handleRerunAction = async () => {
 689 |   if (isFullyRejected.value) {
 690 |     ElMessage.warning('This request is fully rejected and read-only.');
 691 |     return;
 692 |   }
 693 | 
 694 |   if (selectedTests.value.length === 0) {
 695 |     try {
 696 |       await ElMessageBox.confirm(
 697 |         `
 698 |         <div class="bulk-action-warning">
 699 |           <div style="color: #e6a23c; font-weight: bold; font-size: 16px; margin-bottom: 12px;">Warning: Bulk Action Detected</div>
 700 |           <p>You are about to re-run the entire request (<b>#${props.requestDetails.accession_id || props.requestDetails.sample_id || 'N/A'}</b>).</p>
 701 |           <div style="margin-top: 16px; padding: 12px; background: #fdf6ec; border-radius: 4px; border-left: 4px solid #e6a23c;">
 702 |             <p><b>To Re-run Everything:</b> Click "Confirm Global Re-run" and provide a reason in the next step.</p>
 703 |             <p style="margin-top: 8px;"><b>To Re-run Specific Tests:</b> Close this window, select the individual parameters in the table, and click "Rerun Selected."</p>
 704 |           </div>
 705 |         </div>
 706 |         `,
 707 |         'Bulk Action Detected',
 708 |         {
 709 |           confirmButtonText: 'Confirm Global Re-run',
 710 |           cancelButtonText: 'Close',
 711 |           type: 'warning',
 712 |           dangerouslyUseHTMLString: true,
 713 |           confirmButtonClass: 'el-button--warning'
 714 |         }
 715 |       );
 716 |       
 717 |       // For global rerun, we might need to select all results first or handle it in the modal
 718 |       // For now, just show the modal
 719 |       showRerunModal.value = true;
 720 |     } catch (e) {
 721 |       // User cancelled
 722 |     }
 723 |   } else {
 724 |     showRerunModal.value = true;
 725 |   }
 726 | };
 727 | const requestTests = computed(() => props.requestDetails?.tests || []);
 728 | 
 729 | // Read-only lock if all tests are Rejected
 730 | const isFullyRejected = computed(() => {
 731 |   const tests = requestTests.value || [];
 732 |   if (tests.length === 0) return false;
 733 |   return tests.every(t => t?.pivot?.item_status === 'Rejected');
 734 | });
 735 | 
 736 | const isFullyValidatedOrReleased = computed(() => {
 737 |   const tests = requestTests.value || [];
 738 |   if (tests.length === 0) return false;
 739 |   return tests.every(t => 
 740 |     t?.pivot?.item_status === 'Result Validated' || 
 741 |     t?.pivot?.item_status === 'Result Printed' || 
 742 |     t?.pivot?.item_status === 'Released'
 743 |   );
 744 | });
 745 | 
 746 | const latestRejectedAt = computed(() => {
 747 |   const tests = requestTests.value || [];
 748 |   const stamps = tests
 749 |     .filter(t => t?.pivot?.item_status === 'Rejected' && t?.pivot?.rejected_at)
 750 |     .map(t => t.pivot.rejected_at);
 751 |   if (stamps.length === 0) return null;
 752 |   return stamps.sort((a, b) => new Date(b) - new Date(a))[0];
 753 | });
 754 | 
 755 | const openAuditTrail = () => {
 756 |   showViolationsModal.value = true;
 757 |   activeViolationTab.value = 'audit';
 758 |   fetchAuditTrail();
 759 | };
 760 | 
 761 | const getSelectedTestIds = () => {
 762 |   const ids = selectedTests.value
 763 |     .map(item => item.testId || item.test_id || item.test?.id)
 764 |     .filter(id => !!id);
 765 |   return Array.from(new Set(ids));
 766 | };
 767 | 
 768 | const handleSelectionChange = (selection) => {
 769 |   selectedTests.value = selection;
 770 | };
 771 | 
 772 | const getRowKey = (row) => {
 773 |   // Use the unique ID generated in formattedTestResults (e.g., param-123 or test-456)
 774 |   return row.id;
 775 | };
 776 | 
 777 | const submitRerun = async () => {
 778 |   if (!rerunFormRef.value) return;
 779 |   await rerunFormRef.value.validate(async (valid) => {
 780 |     if (valid) {
 781 |       rerunningResult.value = true;
 782 |       try {
 783 |         const flagged_results = selectedTests.value
 784 |           .filter(item => item.result_id) // Only include items with a result_id
 785 |           .map(item => ({
 786 |             test_result_id: item.result_id,
 787 |             test_parameter_id: item.parameterId
 788 |           }));
 789 | 
 790 |         if (flagged_results.length === 0) {
 791 |           ElMessage.error('Please select at least one valid test result to re-run.');
 792 |           rerunningResult.value = false;
 793 |           return;
 794 |         }
 795 |         const payload = {
 796 |           flagged_results: flagged_results,
 797 |           reason: rerunForm.reason,
 798 |           comments: rerunForm.comments,
 799 |           requested_by_id: currentUser.value?.id
 800 |         };
 801 | 
 802 |         // Call the API to initiate re-run and capture new results
 803 |         const rerunResp = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/rerun`, payload);
 804 | 
 805 |         ElMessage.success('Re-run initiated successfully.');
 806 |         showRerunModal.value = false;
 807 | 
 808 |         try {
 809 |           // Build latestResultsDataSource from backend new_test_results
 810 |           const latestResultsDataSource = {};
 811 |           const newResults = rerunResp?.data?.new_test_results || [];
 812 |           newResults.forEach(item => {
 813 |             const testId = item.test_id;
 814 |             const paramId = item.test_parameter_id;
 815 |             if (!testId || !paramId) return;
 816 |             if (!latestResultsDataSource[testId]) latestResultsDataSource[testId] = [];
 817 |             latestResultsDataSource[testId].push({
 818 |               id: paramId,
 819 |               result_id: item.id,
 820 |               resultValue: item.result_value,
 821 |               unit: item.unit || '',
 822 |               instrument: item.equipment_id || null,
 823 |               resultComment: item.result_comment || '',
 824 |               flag: item.flag || null,
 825 |               conventional: '',
 826 |               conventional_unit: '',
 827 |               conversion_factor: null,
 828 |               version: item.version || 'R1',
 829 |               status: item.status || 'pending'
 830 |             });
 831 |             // Immediate in-place UI update for version/status
 832 |             const params = testParameters.value[testId] || [];
 833 |             const idx = params.findIndex(p => p.id === paramId || p.parameterId === paramId);
 834 |             if (idx !== -1) {
 835 |               params[idx].version = item.version || 'R1';
 836 |               params[idx].status = item.status || 'pending';
 837 |               params[idx].result_id = item.id;
 838 |               params[idx].resultValue = item.result_value || '';
 839 |             }
 840 |           });
 841 |           // Persist override so subsequent refreshes use the correct version/status
 842 |           overrideResultsDataSource.value = latestResultsDataSource;
 843 |           // Re-fetch parameters, merging with latestResultsDataSource to inject version/status
 844 |           await fetchAllTestParameters(props.requestDetails.tests, latestResultsDataSource);
 845 |         } catch (e) {
 846 |           console.warn('UI refresh after re-run failed:', e);
 847 |         }
 848 | 
 849 |         // You might want to refresh the data or update the UI accordingly
 850 |         emit('save', props.requestDetails.id, flagged_results);
 851 | 
 852 |       } catch (error) {
 853 |         console.error('Error initiating re-run:', error);
 854 |         ElMessage.error('Failed to initiate re-run.');
 855 |       } finally {
 856 |         rerunningResult.value = false;
 857 |       }
 858 |     }
 859 |   });
 860 | };
 861 | 
 862 | // Computed property for dynamic modal title
 863 | const modalTitle = computed(() => {
 864 |   const categoryName = props.categoryName.toLocaleUpperCase() || 'Result';
 865 |   return `${categoryName} Result Window`;
 866 | });
 867 | 
 868 | const localSignatories = ref({ performedBy: null, verifiedBy: null, pathologist: null });
 869 | const localRemarks = ref('');
 870 | const localComments = ref('');
 871 | const resultFields = ref([]);
 872 | const auditTrail = ref([]);
 873 | const auditTrailLoading = ref(false);
 874 | 
 875 | const cdsAcknowledgeData = computed(() => {
 876 |   if (!auditTrail.value || auditTrail.value.length === 0) return null;
 877 |   return auditTrail.value.find(a => a.rule_type === 'CDS' && a.decision === 'acknowledged');
 878 | });
 879 | 
 880 | const deltaAcknowledgeData = computed(() => {
 881 |   if (!auditTrail.value || auditTrail.value.length === 0) return null;
 882 |   return auditTrail.value.find(a => a.rule_type === 'DELTA_CHECK' && a.decision === 'acknowledged');
 883 | });
 884 | 
 885 | const criticalAcknowledgeData = computed(() => {
 886 |   if (!auditTrail.value || auditTrail.value.length === 0) return null;
 887 |   return auditTrail.value.find(a => a.rule_type === 'CRITICAL_VALUE' && a.decision === 'acknowledged');
 888 | });
 889 | 
 890 | const hasAnyRuleAcknowledgment = computed(() => {
 891 |   return !!(cdsAcknowledgeData.value || deltaAcknowledgeData.value || criticalAcknowledgeData.value);
 892 | });
 893 | 
 894 | // Computed property to determine if unified banner should show
 895 | const shouldShowUnifiedBanner = computed(() => {
 896 |   return hasHoldStatus.value ||
 897 |          qcHoldBannerMessage.value ||
 898 |          (cdsAlerts.value && cdsAlerts.value.length > 0) ||
 899 |          criticalValues.value.length > 0 ||
 900 |          hasAnyRuleAcknowledgment.value;
 901 | });
 902 | const hasUnacknowledgedAlerts = computed(() => {
 903 |   const hasCds = cdsAlerts.value.length > 0;
 904 |   const hasDelta = flaggedDeltaChecks.value.length > 0;
 905 |   const hasCritical = criticalValues.value.length > 0;
 906 |   return (hasCds && !cdsAcknowledgeData.value)
 907 |     || (hasDelta && !deltaAcknowledgeData.value)
 908 |     || (hasCritical && !criticalAcknowledgeData.value);
 909 | });
 910 | const testParameters = ref({});
 911 | const parametersLoading = ref(false);
 912 | const equipment = ref([]);
 913 | const loadingEquipment = ref(false);
 914 | // Loading state for initial checks when modal opens
 915 | const initialChecksLoading = ref(false);
 916 | // Flag to indicate modal is fully opened and stable
 917 | const modalReady = ref(false);
 918 | // Formula auto-computation state
 919 | const formulas = ref([]);
 920 | const formulasByTarget = ref({});
 921 | 
 922 | const buildFormulasIndex = () => {
 923 |   const idx = {};
 924 |   (formulas.value || []).forEach(f => {
 925 |     const targetId = f.target_test_parameter_id || f.target_parameter_id || (f.target_parameter && f.target_parameter.id);
 926 |     if (targetId) idx[targetId] = f;
 927 |   });
 928 |   formulasByTarget.value = idx;
 929 | };
 930 | 
 931 | const fetchFormulas = async () => {
 932 |   const now = Date.now();
 933 |   if (staticCache.formulas.data.length > 0 && (now - staticCache.formulas.timestamp < CACHE_TTL)) {
 934 |     console.log('Using cached formulas');
 935 |     formulas.value = staticCache.formulas.data;
 936 |     buildFormulasIndex();
 937 |     return;
 938 |   }
 939 | 
 940 |   try {
 941 |     const resp = await api.get('/formulas');
 942 |     // support either { data: [...] } or [...] response shapes
 943 |     const data = resp.data && Array.isArray(resp.data) ? resp.data : (resp.data?.data || resp.data || []);
 944 |     formulas.value = data;
 945 |     
 946 |     // Update cache
 947 |     staticCache.formulas.data = data;
 948 |     staticCache.formulas.timestamp = now;
 949 |     
 950 |     buildFormulasIndex();
 951 |   } catch (e) {
 952 |     if (e.response?.status === 429) {
 953 |       console.warn('Rate limited while fetching formulas, using stale cache if available');
 954 |       if (staticCache.formulas.data.length > 0) {
 955 |         formulas.value = staticCache.formulas.data;
 956 |         buildFormulasIndex();
 957 |         return;
 958 |       }
 959 |     }
 960 |     console.error('Failed to load formulas for auto-computation:', e);
 961 |     formulas.value = [];
 962 |     formulasByTarget.value = {};
 963 |   }
 964 | };
 965 | 
 966 | const buildResultsMap = () => {
 967 |   const map = {};
 968 |   Object.values(testParameters.value).flat().forEach(p => {
 969 |     if (!p) return;
 970 |     const raw = p.resultValue;
 971 |     if (raw === undefined || raw === null) return;
 972 |     const s = String(raw).trim();
 973 |     if (s === '' || isNaN(parseFloat(s))) return;
 974 |     map[p.id] = parseFloat(s);
 975 |   });
 976 |   return map;
 977 | };
 978 | 
 979 | const evaluateCompiledFormula = (compiled, resultsMap) => {
 980 |   if (!compiled) return null;
 981 |   // Replace tokens like [123] or {123} with numeric values from resultsMap
 982 |   let expr = String(compiled).replace(/[\[\{](\d+)[\]\}]/g, (m, id) => {
 983 |     const v = resultsMap[id];
 984 |     if (v === undefined) throw new Error('Missing value for parameter ' + id);
 985 |     return String(Number(v));
 986 |   });
 987 | 
 988 |   // Disallow letters (to prevent identifiers) and only allow safe math chars
 989 |   if (/[A-Za-z]/.test(expr)) throw new Error('Invalid characters in formula');
 990 |   if (/[^0-9+\-*/().%\s]/.test(expr)) throw new Error('Disallowed characters in formula');
 991 | 
 992 |   // Simple balanced parentheses check
 993 |   let depth = 0;
 994 |   for (let ch of expr) {
 995 |     if (ch === '(') depth++;
 996 |     if (ch === ')') {
 997 |       depth--;
 998 |       if (depth < 0) throw new Error('Unbalanced parentheses');
 999 |     }
1000 |   }
1001 |   if (depth !== 0) throw new Error('Unbalanced parentheses');
1002 | 
1003 |   // Evaluate in a strict function scope
1004 |   try {
1005 |     // eslint-disable-next-line no-new-func
1006 |     const val = Function('"use strict";return (' + expr + ')')();
1007 |     if (typeof val !== 'number' || !isFinite(val)) throw new Error('Non-numeric result');
1008 |     return val;
1009 |   } catch (e) {
1010 |     throw new Error('Evaluation error: ' + e.message);
1011 |   }
1012 | };
1013 | 
1014 | const applyComputedValue = (formula, targetParamId, numericValue) => {
1015 |   if (numericValue === null || numericValue === undefined || isNaN(Number(numericValue))) return;
1016 |   for (const testId of Object.keys(testParameters.value)) {
1017 |     const params = testParameters.value[testId] || [];
1018 |     const idx = params.findIndex(p => p.id === targetParamId);
1019 |     if (idx === -1) continue;
1020 |     const param = params[idx];
1021 |     const decimals = (formula && (formula.decimal_places || formula.decimal_places === 0)) ? Number(formula.decimal_places) : null;
1022 |     const rounded = decimals !== null ? Number(Number(numericValue).toFixed(decimals)) : numericValue;
1023 |     param.resultValue = String(rounded);
1024 |     param.result_id = null;
1025 |     param.is_auto_calculated = true;
1026 |     param.auto_formula_id = formula?.id || null;
1027 | 
1028 |     // recalc conventional if conversion_factor is available
1029 |     if (param.conversion_factor && !isNaN(parseFloat(param.conversion_factor))) {
1030 |       try {
1031 |         param.conventional = (parseFloat(param.resultValue) * parseFloat(param.conversion_factor)).toFixed(2);
1032 |       } catch (e) {
1033 |         param.conventional = '';
1034 |       }
1035 |     } else {
1036 |       param.conventional = '';
1037 |     }
1038 | 
1039 |     // Trigger flag calculation for the auto-filled value
1040 |     api.post('/results/calculate-derived', {
1041 |       result: Number(param.resultValue),
1042 |       unit: param.unit || '',
1043 |       test_parameter_id: param.id,
1044 |       patient_id: props.requestDetails?.patient?.id,
1045 |       equipment_id: param.instrument || null
1046 |     }).then(resp => {
1047 |       param.flag = resp.data.flag;
1048 |       testParameters.value[testId] = [...testParameters.value[testId]];
1049 |       forceButtonUpdate();
1050 |     }).catch(err => {
1051 |       console.error('Flag API error for computed param', err);
1052 |       param.flag = 'ERR';
1053 |       testParameters.value[testId] = [...testParameters.value[testId]];
1054 |       forceButtonUpdate();
1055 |     });
1056 | 
1057 |     // Ensure Vue reactivity
1058 |     testParameters.value[testId] = [...testParameters.value[testId]];
1059 |     return;
1060 |   }
1061 | };
1062 | 
1063 | const recalculateFormulasForSourceParameter = () => {
1064 |   try {
1065 |     const resultsMap = buildResultsMap();
1066 |     if (!formulas.value || formulas.value.length === 0) return;
1067 |     formulas.value.forEach(f => {
1068 |       // Ensure source variables exist and have numeric values
1069 |       const sourceIds = (f.source_variables || []).map(v => v.source_test_parameter_id || v.source_parameter_id || v.id).filter(Boolean);
1070 |       if (sourceIds.length === 0) return;
1071 |       const missing = sourceIds.some(id => resultsMap[id] === undefined);
1072 |       if (missing) return; // cannot compute if any source missing
1073 |       try {
1074 |         const computed = evaluateCompiledFormula(f.compiled_formula || f.compiledFormula || f.compiled || '', resultsMap);
1075 |         if (computed !== null && !isNaN(Number(computed))) {
1076 |           const targetId = f.target_test_parameter_id || f.target_parameter_id || (f.target_parameter && f.target_parameter.id);
1077 |           if (targetId) applyComputedValue(f, targetId, computed);
1078 |         }
1079 |       } catch (e) {
1080 |         // ignore evaluation errors for now
1081 |         // console.debug('Formula eval skipped:', e.message);
1082 |       }
1083 |     });
1084 |   } catch (e) {
1085 |     console.error('Error during formula recalculation:', e);
1086 |   }
1087 | };
1088 | 
1089 | // QC banner state and updater
1090 | const qcHoldBannerMessage = ref('');
1091 | const updateQCHoldBanner = () => {
1092 |   try {
1093 |     const involvedEquipmentIds = Array.from(new Set(
1094 |       Object.values(testParameters.value)
1095 |         .flat()
1096 |         .map(p => (p.equipment_id ?? p.instrument))
1097 |         .filter(id => id != null)
1098 |         .map(id => parseInt(id))
1099 |     ));
1100 |     if (involvedEquipmentIds.length > 0 && testCategoryId.value) {
1101 |       const categoryId = testCategoryId.value;
1102 |       const now = Date.now();
1103 |       
1104 |       if (staticCache.qcStatus[categoryId] && (now - staticCache.qcStatus[categoryId].timestamp < CACHE_TTL)) {
1105 |         console.log(`Using cached QC status for category: ${categoryId}`);
1106 |         const data = staticCache.qcStatus[categoryId].data;
1107 |         const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
1108 |         if (failing.length > 0) {
1109 |           const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
1110 |           qcHoldBannerMessage.value = lines.join(' | ');
1111 |         } else {
1112 |           qcHoldBannerMessage.value = '';
1113 |         }
1114 |         return;
1115 |       }
1116 | 
1117 |       api.get(`/qc-lockout/status-summary`, { params: { test_category_id: categoryId } })
1118 |         .then(({ data }) => {
1119 |           // Update cache
1120 |           staticCache.qcStatus[categoryId] = {
1121 |             data: data,
1122 |             timestamp: now
1123 |           };
1124 | 
1125 |           const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
1126 |           if (failing.length > 0) {
1127 |             const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
1128 |             qcHoldBannerMessage.value = lines.join(' | ');
1129 |           } else {
1130 |             qcHoldBannerMessage.value = '';
1131 |           }
1132 |         })
1133 |         .catch((error) => { 
1134 |           if (error.response?.status === 429) {
1135 |             console.warn(`Rate limited while fetching QC status for category: ${categoryId}`);
1136 |             if (staticCache.qcStatus[categoryId]) {
1137 |               const data = staticCache.qcStatus[categoryId].data;
1138 |               const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
1139 |               if (failing.length > 0) {
1140 |                 const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
1141 |                 qcHoldBannerMessage.value = lines.join(' | ');
1142 |               }
1143 |             }
1144 |           }
1145 |           /* keep previous banner state */ 
1146 |         });
1147 |     } else {
1148 |       qcHoldBannerMessage.value = '';
1149 |     }
1150 |   } catch (_) {
1151 |     // ignore
1152 |   }
1153 | };
1154 | 
1155 | // Active parameters in the current category view
1156 | const activeParameterIds = computed(() => {
1157 |   const ids = new Set();
1158 |   Object.values(testParameters.value).flat().forEach(p => {
1159 |     if (p) {
1160 |       if (p.id) ids.add(Number(p.id));
1161 |       if (p.parameterId) ids.add(Number(p.parameterId));
1162 |       if (p.test_parameter_id) ids.add(Number(p.test_parameter_id));
1163 |     }
1164 |   });
1165 |   return ids;
1166 | });
1167 | 
1168 | // CDS alerts state and updater
1169 | const cdsAlerts = ref([]);
1170 | 
1171 | // Filter CDS alerts to only reflex tests needing approval
1172 | const pendingReflexApprovals = computed(() => {
1173 |     return cdsAlerts.value.filter(alert =>
1174 |         alert.action_type === 'REFLEX_TEST' &&
1175 |         !alert.is_acknowledged &&
1176 |         !alert.reflex_created
1177 |     );
1178 | });
1179 | 
1180 | const hasPendingReflexApprovals = computed(() => {
1181 |     return pendingReflexApprovals.value.length > 0;
1182 | });
1183 | 
1184 | const checkCdsRules = async (force = false) => {
1185 |   // Comprehensive guard: only run when modal is fully ready
1186 |   if (!isMounted.value || !props.visible || !modalReady.value) return
1187 |   if (!props.requestDetails?.id) {
1188 |     return;
1189 |   }
1190 | 
1191 |   const requestId = props.requestDetails.id;
1192 |   const now = Date.now();
1193 |   if (!force && staticCache.cdsRules[requestId] && (now - staticCache.cdsRules[requestId].timestamp < CACHE_TTL)) {
1194 |     console.log(`Using cached CDS rules for request: ${requestId}`);
1195 |     const cachedData = staticCache.cdsRules[requestId].data;
1196 |     if (cachedData && cachedData.length > 0) {
1197 |       if (isMounted.value && props.visible && !showViolationsModal.value) {
1198 |         cdsAlerts.value = cachedData.filter(r => activeParameterIds.value.has(Number(r.trigger_test_id)));
1199 |       }
1200 |     } else {
1201 |       if (isMounted.value && props.visible && !showViolationsModal.value) {
1202 |         cdsAlerts.value = [];
1203 |       }
1204 |     }
1205 |     return;
1206 |   }
1207 | 
1208 |   try {
1209 |     const response = await api.get(`/results/check-cds/${props.requestDetails.id}`);
1210 |     const data = response.data;
1211 |     
1212 |     // Update cache
1213 |     staticCache.cdsRules[requestId] = {
1214 |       data: data.triggered_rules || [],
1215 |       timestamp: now
1216 |     };
1217 | 
1218 |     if (data.triggered_rules && data.triggered_rules.length > 0) {
1219 |       // Filter the triggered rules to only those relevant to the currently viewed parameters
1220 |       const filteredRules = data.triggered_rules.filter(r => activeParameterIds.value.has(Number(r.trigger_test_id)));
1221 |       if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1222 |         cdsAlerts.value = filteredRules;
1223 |       }
1224 |       
1225 |       // Reflex test alerts are now handled by CdsAlertBanner component
1226 |       // No need to automatically show approval modal
1227 |       
1228 |       // Show warning if there are blocking rules within this category
1229 |       const blockingRules = filteredRules.filter(r => r.is_blocking);
1230 |       if (blockingRules.length > 0 && isMounted.value && props.visible) {
1231 |         ElMessage.warning({
1232 |           message: `${blockingRules.length} CDS rule(s) triggered for this category. Review required before validation.`,
1233 |           duration: 5000
1234 |         });
1235 |       }
1236 |     } else {
1237 |       if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1238 |         cdsAlerts.value = [];
1239 |       }
1240 |     }
1241 |   } catch (error) {
1242 |     if (error.response?.status === 429) {
1243 |       console.warn(`Rate limited while checking CDS rules for request: ${requestId}`);
1244 |       if (staticCache.cdsRules[requestId]) {
1245 |         const cachedData = staticCache.cdsRules[requestId].data;
1246 |         if (cachedData && cachedData.length > 0) {
1247 |           if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1248 |             cdsAlerts.value = cachedData.filter(r => activeParameterIds.value.has(Number(r.trigger_test_id)));
1249 |           }
1250 |         } else {
1251 |           if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1252 |             cdsAlerts.value = [];
1253 |           }
1254 |         }
1255 |         return;
1256 |       }
1257 |     }
1258 |     console.error('Error checking CDS rules:', error);
1259 |     // Don't show error to user, just log it
1260 |   }
1261 | };
1262 | 
1263 | const fetchAuditTrail = async (force = false) => {
1264 |   if (!isMounted.value || !props.visible || !modalReady.value) return
1265 |   if (!props.requestDetails?.id) return;
1266 |   
1267 |   const requestId = props.requestDetails.id;
1268 |   const now = Date.now();
1269 |   if (!force && staticCache.auditTrail[requestId] && (now - staticCache.auditTrail[requestId].timestamp < CACHE_TTL)) {
1270 |     console.log(`Using cached audit trail for request: ${requestId}`);
1271 |     if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1272 |       auditTrail.value = staticCache.auditTrail[requestId].data;
1273 |     }
1274 |     return;
1275 |   }
1276 | 
1277 |   if (isMounted.value && props.visible && modalReady.value) {
1278 |     auditTrailLoading.value = true;
1279 |   }
1280 |   console.log('Fetching audit trail for request:', requestId);
1281 |   try {
1282 |     const response = await api.get(`/laboratory-requests/${requestId}/audit-trail`);
1283 |     const data = response.data || [];
1284 |     
1285 |     // Update cache
1286 |     staticCache.auditTrail[requestId] = {
1287 |       data: data,
1288 |       timestamp: now
1289 |     };
1290 |     
1291 |     if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1292 |       auditTrail.value = data;
1293 |     }
1294 |   } catch (error) {
1295 |     if (error.response?.status === 429) {
1296 |       console.warn(`Rate limited while fetching audit trail for request: ${requestId}`);
1297 |       if (staticCache.auditTrail[requestId]) {
1298 |         if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1299 |           auditTrail.value = staticCache.auditTrail[requestId].data;
1300 |         }
1301 |         return;
1302 |       }
1303 |     }
1304 |     console.error('Error fetching audit trail:', error);
1305 |     if (isMounted.value && props.visible && modalReady.value) {
1306 |       ElMessage.error('Failed to load audit trail history');
1307 |     }
1308 |   } finally {
1309 |     if (isMounted.value && props.visible && modalReady.value) {
1310 |       auditTrailLoading.value = false;
1311 |     }
1312 |   }
1313 | };
1314 | 
1315 | // Delta Check Hold Status - Flagged results detection
1316 | const flaggedDeltaChecks = ref([]);
1317 | const allDeltaChecks = ref([]);
1318 | const hasHoldStatus = ref(false);
1319 | 
1320 | // Check for flagged delta checks when modal opens or request details change
1321 | const checkFlaggedDeltaChecks = async () => {
1322 |   // Comprehensive guard: only run when modal is fully ready
1323 |   if (!isMounted.value || !props.visible || !modalReady.value || showViolationsModal.value) return
1324 |   if (!props.requestDetails?.id) {
1325 |     if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1326 |       flaggedDeltaChecks.value = [];
1327 |       hasHoldStatus.value = false;
1328 |     }
1329 |     return;
1330 |   }
1331 | 
1332 |   try {
1333 |     const requestId = props.requestDetails.id;
1334 |     const now = Date.now();
1335 |     let allFlaggedChecks = [];
1336 | 
1337 |     if (staticCache.allFlaggedDeltaChecks.data.length > 0 && (now - staticCache.allFlaggedDeltaChecks.timestamp < CACHE_TTL)) {
1338 |       console.log('Using cached flagged delta checks');
1339 |       allFlaggedChecks = staticCache.allFlaggedDeltaChecks.data;
1340 |     } else {
1341 |       console.log('Checking flagged delta checks for request ID:', requestId);
1342 |       
1343 |       // Fetch flagged delta checks for this laboratory request
1344 |       const response = await api.get('/management-report/delta-checks', {
1345 |         params: {
1346 |           status: 'flagged',
1347 |           per_page: 1000,
1348 |           page: 1
1349 |         }
1350 |       });
1351 | 
1352 |       allFlaggedChecks = response.data?.data || [];
1353 |       
1354 |       // Update cache
1355 |       staticCache.allFlaggedDeltaChecks.data = allFlaggedChecks;
1356 |       staticCache.allFlaggedDeltaChecks.timestamp = now;
1357 |     }
1358 | 
1359 |     // Check if still mounted, visible, and ready before updating state
1360 |     // Also check if violations modal is not being shown or is closing
1361 |     if (!isMounted.value || !props.visible || !modalReady.value || showViolationsModal.value) return;
1362 | 
1363 |     console.log('Total flagged delta checks retrieved:', allFlaggedChecks.length);
1364 |     if (allFlaggedChecks.length > 0) {
1365 |       console.log('Sample delta check structure:', allFlaggedChecks[0]);
1366 |     }
1367 |     
1368 |     // Filter to only include checks for this laboratory request
1369 |     // Try multiple ways to access the laboratory_request_id
1370 |     flaggedDeltaChecks.value = allFlaggedChecks.filter(dc => {
1371 |       if (!dc || !dc.test_result) {
1372 |         return false;
1373 |       }
1374 |       
1375 |       // Try different paths to get laboratory_request_id
1376 |       const lrid = dc.test_result.laboratory_request_id ||
1377 |                    dc.test_result.laboratory_request?.id ||
1378 |                    (dc.test_result.laboratoryRequest && dc.test_result.laboratoryRequest.id) ||
1379 |                    null;
1380 |       
1381 |       // Convert both to numbers for comparison (in case of string vs number mismatch)
1382 |       const requestIdNum = Number(requestId);
1383 |       const lridNum = Number(lrid);
1384 |       
1385 |       const requestMatches = lridNum === requestIdNum || lrid === requestId;
1386 |       
1387 |       // Only include violations for parameters currently visible in this category's modal
1388 |       const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1389 |       const parameterMatches = testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1390 |       
1391 |       return requestMatches && parameterMatches;
1392 |     });
1393 | 
1394 |     // Check if any results have action_on_violation = 'flag_and_stop' which should set status to Hold
1395 |     const hasFlagAndStop = flaggedDeltaChecks.value.some(dc =>
1396 |       dc.action_on_violation === 'flag_and_stop' ||
1397 |       dc.action_on_violation === 'Flag and Stop'
1398 |     );
1399 |     
1400 |     // If we have flagged results with 'flag_and_stop', or if any flagged results exist (fallback)
1401 |     hasHoldStatus.value = hasFlagAndStop || flaggedDeltaChecks.value.length > 0;
1402 |     
1403 |     if (hasHoldStatus.value) {
1404 |       console.log('Hold status triggered. Flagged checks:', flaggedDeltaChecks.value.length,
1405 |                   'Flag and Stop:', hasFlagAndStop);
1406 |     }
1407 |     
1408 |     console.log('Filtered flagged delta checks for request:', flaggedDeltaChecks.value.length);
1409 |     console.log('Has Hold status:', hasHoldStatus.value);
1410 |   } catch (error) {
1411 |     if (error.response?.status === 429) {
1412 |       console.warn('Rate limited while checking flagged delta checks');
1413 |       // If we have cached data, use it but don't re-filter yet as it's already done or we can't fetch new
1414 |       if (staticCache.allFlaggedDeltaChecks.data.length > 0) {
1415 |         // We still need to filter for the current request and correct parameters
1416 |         const requestId = props.requestDetails.id;
1417 |         const filtered = staticCache.allFlaggedDeltaChecks.data.filter(dc => {
1418 |           if (!dc || !dc.test_result) return false;
1419 |           const lrid = dc.test_result.laboratory_request_id || dc.test_result.laboratory_request?.id;
1420 |           const requestMatches = Number(lrid) === Number(requestId);
1421 |           const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1422 |           const parameterMatches = testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1423 |           return requestMatches && parameterMatches;
1424 |         });
1425 |         
1426 |         if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1427 |           flaggedDeltaChecks.value = filtered;
1428 |           hasHoldStatus.value = filtered.length > 0;
1429 |         }
1430 |         return;
1431 |       }
1432 |     }
1433 |     console.error('Error checking flagged delta checks:', error);
1434 |     if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1435 |       flaggedDeltaChecks.value = [];
1436 |       hasHoldStatus.value = false;
1437 |     }
1438 |   }
1439 | };
1440 | 
1441 | const checkAllDeltaChecks = async () => {
1442 |   // Comprehensive guard: only run when modal is fully ready and violations modal is not showing
1443 |   if (!isMounted.value || !props.visible || !modalReady.value || showViolationsModal.value) return
1444 |   if (!props.requestDetails?.id) {
1445 |     if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1446 |       allDeltaChecks.value = [];
1447 |     }
1448 |     return;
1449 |   }
1450 | 
1451 |   try {
1452 |     const requestId = props.requestDetails.id;
1453 |     const now = Date.now();
1454 |     const cached = staticCache.deltaChecks[requestId];
1455 |     if (cached && (now - cached.timestamp < CACHE_TTL)) {
1456 |       const requestMatchesList = cached.data || [];
1457 |       if (!isMounted.value || !props.visible || !modalReady.value) return;
1458 |       // Defer assignment to next macrotask to avoid race with modal closing
1459 |       const timeoutId = setTimeout(() => {
1460 |         if (!isMounted.value || !props.visible || !modalReady.value || showViolationsModal.value) return;
1461 |         allDeltaChecks.value = requestMatchesList.filter(dc => {
1462 |           if (!dc || !dc.test_result) {
1463 |             return false;
1464 |           }
1465 |           const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1466 |           return testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1467 |         });
1468 |       }, 0);
1469 |       // Track this timeout for cleanup on unmount
1470 |       if (isMounted.value && props.visible) {
1471 |         activeTimeouts.value.push(timeoutId);
1472 |       }
1473 |       return;
1474 |     }
1475 | 
1476 |     const params = {
1477 |       per_page: 1000,
1478 |       page: 1
1479 |     };
1480 |     if (props.requestDetails?.patient?.id) {
1481 |       params.patient_id = props.requestDetails.patient.id;
1482 |     }
1483 | 
1484 |     const response = await api.get('/management-report/delta-checks', { params });
1485 |     const allChecks = response.data?.data || [];
1486 |     
1487 |     // Filter to only this request
1488 |     const requestMatchesList = allChecks.filter(dc => {
1489 |       if (!dc || !dc.test_result) {
1490 |         return false;
1491 |       }
1492 |       const lrid = dc.test_result.laboratory_request_id ||
1493 |                    dc.test_result.laboratory_request?.id ||
1494 |                    dc.test_result.laboratoryRequest?.id ||
1495 |                    null;
1496 |       return Number(lrid) === Number(requestId);
1497 |     });
1498 | 
1499 |     // Cache the unfiltered (by parameter) request matches
1500 |     staticCache.deltaChecks[requestId] = {
1501 |       data: requestMatchesList,
1502 |       timestamp: now
1503 |     };
1504 | 
1505 |     // Check if still mounted, visible, and ready before waiting
1506 |     if (!isMounted.value || !props.visible || !modalReady.value) return;
1507 |     // Defer assignment to next macrotask to avoid race with modal closing
1508 |     const timeoutId = setTimeout(() => {
1509 |       try {
1510 |         if (!isMounted.value || !props.visible || !modalReady.value || showViolationsModal.value) return;
1511 |         allDeltaChecks.value = requestMatchesList.filter(dc => {
1512 |           if (!dc || !dc.test_result) {
1513 |             return false;
1514 |           }
1515 |           const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1516 |           return testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1517 |         });
1518 |       } catch (error) {
1519 |         console.error('Error updating allDeltaChecks:', error);
1520 |       }
1521 |     }, 0);
1522 |     // Track this timeout for cleanup on unmount
1523 |     if (isMounted.value && props.visible) {
1524 |       activeTimeouts.value.push(timeoutId);
1525 |     }
1526 |   } catch (error) {
1527 |     if (error.response?.status === 429) {
1528 |       const requestId = props.requestDetails.id;
1529 |       const cached = staticCache.deltaChecks[requestId];
1530 |       if (cached && cached.data) {
1531 |         if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1532 |           allDeltaChecks.value = cached.data.filter(dc => {
1533 |             if (!dc || !dc.test_result) {
1534 |               return false;
1535 |             }
1536 |             const testParamId = dc.test_parameter?.id || dc.test_result?.test_parameter_id || dc.test_parameter_id;
1537 |             return testParamId ? activeParameterIds.value.has(Number(testParamId)) : false;
1538 |           });
1539 |         }
1540 |         return;
1541 |       }
1542 |     }
1543 |     console.error('Error fetching delta checks:', error);
1544 |     if (isMounted.value && props.visible && modalReady.value && !showViolationsModal.value) {
1545 |       allDeltaChecks.value = [];
1546 |     }
1547 |   }
1548 | };
1549 | 
1550 | // Accept/Reject/Re-run modal states
1551 | const showRejectModal = ref(false);
1552 | // const showRerunModal = ref(false);
1553 | const showViolationsModal = ref(false);
1554 | const activeViolationTab = ref('summary');
1555 | const pendingPostAckAction = ref(null);
1556 | const hasUnsavedChanges = ref(false);
1557 | 
1558 | // Logged-in user (for reviewer auto-fill)
1559 | // const authStore = useAuthStore?.() || null;
1560 | // const currentUser = authStore?.user || null;
1561 | 
1562 | const nowTimestamp = () => {
1563 |   try { return format(new Date(), 'yyyy-MM-dd HH:mm:ss'); } catch { return new Date().toISOString(); }
1564 | };
1565 | 
1566 | // Rules moved to sub-modal components
1567 | const rootCauseOptions = [
1568 |   { label: 'Pre-Analytical', value: 'pre_analytical' },
1569 |   { label: 'Analytical', value: 'analytical' },
1570 |   { label: 'Post-Analytical', value: 'post_analytical' }
1571 | ];
1572 | const actionTakenOptions = [
1573 |   { label: 'Sample Cancelled', value: 'sample_cancelled' },
1574 |   { label: 'Re-run Initiated (same sample)', value: 'rerun_initiated' },
1575 |   { label: 'Re-draw Initiated (new order)', value: 'redraw_initiated' }
1576 | ];
1577 | const acceptingResult = ref(false);
1578 | const rejectingResult = ref(false);
1579 | // const rerunningResult = ref(false);
1580 | 
1581 | // Form validation rules
1582 | const acceptRules = {
1583 |   reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
1584 |   investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
1585 |   accept_reason: [{ required: true, message: 'Reason is required', trigger: 'change' }],
1586 |   rationale: [
1587 |     { required: true, message: 'Detailed rationale is required', trigger: 'blur' },
1588 |     { min: 50, message: 'Rationale must be at least 50 characters', trigger: 'blur' }
1589 |   ],
1590 |   preanalytical_status: [{ required: true, message: 'Status is required', trigger: 'change' }]
1591 | };
1592 | 
1593 | const rejectRules = {
1594 |   reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
1595 |   scope: [{ required: true, message: 'Scope is required', trigger: 'change' }],
1596 |   test_ids: [{
1597 |     validator: (rule, value, callback) => {
1598 |       if (rejectForm.value.scope === 'tests') {
1599 |         if (Array.isArray(value) && value.length > 0) return callback();
1600 |         return callback(new Error('Select at least one test'));
1601 |       }
1602 |       if (rejectForm.value.scope === 'results') {
1603 |         if (selectedTests.value.length > 0) return callback();
1604 |         return callback(new Error('Select at least one parameter in the table'));
1605 |       }
1606 |       return callback();
1607 |     },
1608 |     trigger: 'change'
1609 |   }],
1610 |   investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
1611 |   root_cause: [{ required: true, message: 'Root cause is required', trigger: 'change' }],
1612 |   action_taken: [{ required: true, message: 'Action taken is required', trigger: 'change' }],
1613 |   rejection_comment: [
1614 |     { required: true, message: 'Detailed comment is required', trigger: 'blur' },
1615 |     { min: 50, message: 'Comment must be at least 50 characters', trigger: 'blur' }
1616 |   ]
1617 | };
1618 | 
1619 | // Arrays to store role-specific users fetched from the API
1620 | const loadedPathologists = ref([]);
1621 | const loadedVerifiers = ref([]);
1622 | const loadedPerformers = ref([]);
1623 | const loadingRoles = ref(false);
1624 | 
1625 | // Remove hardcoded constant and replace with reactive variable
1626 | const testCategoryId = ref(null);
1627 | 
1628 | // Define loadEquipment (replaces loadAnalyzers) before it's used in any watcher
1629 | const loadEquipment = async (testCategoryId = null) => {
1630 |   const cacheKey = testCategoryId || 'all';
1631 |   const now = Date.now();
1632 |   
1633 |   if (staticCache.equipment[cacheKey] && (now - staticCache.equipment[cacheKey].timestamp < CACHE_TTL)) {
1634 |     console.log(`Using cached equipment for ${cacheKey}`);
1635 |     equipment.value = staticCache.equipment[cacheKey].data;
1636 |     return;
1637 |   }
1638 | 
1639 |   loadingEquipment.value = true;
1640 |   try {
1641 |     let endpoint = 'eim/equipment';
1642 |     const params = { per_page: 1000 };
1643 |     
1644 |     // If a test category ID is provided, filter by category
1645 |     if (testCategoryId) {
1646 |       params.test_category_id = testCategoryId;
1647 |       console.log(`Loading equipment for test category: ${testCategoryId}`);
1648 |     } else {
1649 |       console.log('Loading all equipment (no category filter)');
1650 |     }
1651 |     
1652 |     const response = await api.get(endpoint, { params });
1653 |     const data = response.data.data || response.data || [];
1654 |     equipment.value = data;
1655 |     
1656 |     // Update cache
1657 |     staticCache.equipment[cacheKey] = {
1658 |       data: data,
1659 |       timestamp: now
1660 |     };
1661 |     
1662 |     console.log('Loaded equipment:', equipment.value);
1663 |   } catch (error) {
1664 |     if (error.response?.status === 429) {
1665 |       console.warn(`Rate limited while fetching equipment for ${cacheKey}`);
1666 |       if (staticCache.equipment[cacheKey]) {
1667 |         equipment.value = staticCache.equipment[cacheKey].data;
1668 |         return;
1669 |       }
1670 |     }
1671 |     console.error('Error loading equipment:', error);
1672 |     ElMessage.error('Failed to load equipment');
1673 |   } finally {
1674 |     loadingEquipment.value = false;
1675 |   }
1676 | };
1677 | 
1678 | // Alias for loadEquipment - kept for existing code references
1679 | const loadAnalyzers = async (testCategoryId = null) => {
1680 |   await loadEquipment(testCategoryId);
1681 | };
1682 | 
1683 | // Define convertAllSIValues before it's used in any watcher
1684 | const convertAllSIValues = () => {
1685 |   console.log('Converting all SI values to conventional...');
1686 |   
1687 |   // Loop through all tests and their parameters
1688 |   Object.keys(testParameters.value).forEach(testId => {
1689 |     const params = testParameters.value[testId];
1690 |     if (Array.isArray(params)) {
1691 |       params.forEach(param => {
1692 |         if (param.resultValue && !isNaN(parseFloat(param.resultValue)) && param.conversion_factor) {
1693 |           // Calculate conventional value
1694 |           const numericValue = parseFloat(param.resultValue);
1695 |           const conversionFactor = parseFloat(param.conversion_factor);
1696 |           param.conventional = (numericValue * conversionFactor).toFixed(2);
1697 |           console.log(`Auto-converted ${param.name}: ${param.resultValue} ${param.unit} → ${param.conventional} ${param.conventional_unit}`);
1698 |         }
1699 |       });
1700 |     }
1701 |   });
1702 | };
1703 | 
1704 | // Simple function to force reactive updates of button states
1705 | const forceButtonUpdate = () => {
1706 |   console.log('Forcing button state update...');
1707 |   
1708 |   // Log current parameter state for debugging
1709 |   const allParams = Object.values(testParameters.value).flat();
1710 |   const paramsWithValues = allParams.filter(param => 
1711 |     param && param.resultValue && param.resultValue.trim() !== ''
1712 |   );
1713 |   const savedParams = paramsWithValues.filter(param => param.result_id !== null);
1714 |   const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
1715 |   
1716 |   console.log(`Button state calculation:
1717 |     - Total parameters: ${allParams.length}
1718 |     - Parameters with values: ${paramsWithValues.length}
1719 |     - Saved parameters: ${savedParams.length}
1720 |     - Unsaved parameters: ${unsavedParams.length}
1721 |   `);
1722 | 
1723 |   // Manually compute expected button states
1724 |   const shouldDisableSave = unsavedParams.length === 0;
1725 |   const shouldDisablePrint = unsavedParams.length > 0;
1726 |   const shouldDisableValidate = unsavedParams.length > 0 || 
1727 |     props.requestDetails.tests.every(test => test.pivot && test.pivot.item_status === 'Result Printed');
1728 | 
1729 |   console.log(`Expected button states:
1730 |     - Save button should be disabled: ${shouldDisableSave}
1731 |     - Print button should be disabled: ${shouldDisablePrint}
1732 |     - Validate button should be disabled: ${shouldDisableValidate}
1733 |   `);
1734 | 
1735 |   // Force Vue to re-evaluate the computed properties
1736 |   nextTick(() => {
1737 |     console.log('Actual button states after update:');
1738 |     console.log('- Save button disabled:', disableSaveButton.value);
1739 |     console.log('- Print button disabled:', disablePrintButton.value);
1740 |     console.log('- Validate button disabled:', disableValidateButton.value);
1741 |   });
1742 | };
1743 | 
1744 | // Fetch users by role type
1745 | const fetchUsersByRole = async (roleType) => {
1746 |   const now = Date.now();
1747 |   if (staticCache.roles[roleType] && (now - staticCache.roles[roleType].timestamp < CACHE_TTL)) {
1748 |     console.log(`Using cached users for role: ${roleType}`);
1749 |     return staticCache.roles[roleType].data;
1750 |   }
1751 | 
1752 |   try {
1753 |     const response = await api.get(`/users/role/${roleType}`);
1754 |     const data = response.data;
1755 |     
1756 |     // Update cache
1757 |     staticCache.roles[roleType] = {
1758 |       data: data,
1759 |       timestamp: now
1760 |     };
1761 |     
1762 |     return data;
1763 |   } catch (error) {
1764 |     if (error.response?.status === 429) {
1765 |       console.warn(`Rate limited while fetching users for role: ${roleType}`);
1766 |       if (staticCache.roles[roleType]) {
1767 |         return staticCache.roles[roleType].data;
1768 |       }
1769 |     }
1770 |     console.error(`Error fetching ${roleType}s:`, error);
1771 |     ElMessage.error(`Failed to load ${roleType}s`);
1772 |     return [];
1773 |   }
1774 | };
1775 | 
1776 | // Function to load all role-specific users
1777 | const loadRoleUsers = async () => {
1778 |   loadingRoles.value = true;
1779 |   try {
1780 |     // First load users by role type
1781 |     const [pathologists, verifiers, performers] = await Promise.all([
1782 |       fetchUsersByRole('pathologist'),
1783 |       fetchUsersByRole('verifier'),
1784 |       fetchUsersByRole('performer')
1785 |     ]);
1786 |     
1787 |     loadedPathologists.value = pathologists;
1788 |     loadedVerifiers.value = verifiers;
1789 |     loadedPerformers.value = performers;
1790 |     
1791 |     console.log('Loaded pathologists:', loadedPathologists.value);
1792 |     console.log('Loaded verifiers:', loadedVerifiers.value);
1793 |     console.log('Loaded performers:', loadedPerformers.value);
1794 |     
1795 |     // If we have specific user IDs from the test records, ensure they're loaded
1796 |     // This is for when users might not be in the role-specific lists
1797 |     const userIdsToLoad = [];
1798 |     
1799 |     // Skip IDs that are already in our loaded lists
1800 |     if (localSignatories.value.performedBy && 
1801 |         !loadedPerformers.value.some(user => user.id === localSignatories.value.performedBy)) {
1802 |       userIdsToLoad.push(localSignatories.value.performedBy);
1803 |     }
1804 |     
1805 |     if (localSignatories.value.verifiedBy && 
1806 |         !loadedVerifiers.value.some(user => user.id === localSignatories.value.verifiedBy)) {
1807 |       userIdsToLoad.push(localSignatories.value.verifiedBy);
1808 |     }
1809 |     
1810 |     if (localSignatories.value.pathologist && 
1811 |         !loadedPathologists.value.some(user => user.id === localSignatories.value.pathologist)) {
1812 |       userIdsToLoad.push(localSignatories.value.pathologist);
1813 |     }
1814 |     
1815 |     // If we have unique IDs to load
1816 |     if (userIdsToLoad.length > 0) {
1817 |       const uniqueIds = [...new Set(userIdsToLoad)];
1818 |       console.log('Loading additional users by ID:', uniqueIds);
1819 |       
1820 |       // Fetch additional users by ID
1821 |       try {
1822 |         const userPromises = uniqueIds.map(id => api.get(`/users/${id}`));
1823 |         const userResponses = await Promise.all(userPromises);
1824 |         
1825 |         // Add fetched users to appropriate lists
1826 |         userResponses.forEach(response => {
1827 |           const user = response.data;
1828 |           if (!user || !user.id) return;
1829 |           
1830 |           // Check if this user ID matches any of our signatories
1831 |           if (user.id === localSignatories.value.performedBy) {
1832 |             // Add to performers if not already there
1833 |             if (!loadedPerformers.value.some(p => p.id === user.id)) {
1834 |               loadedPerformers.value.push(user);
1835 |               console.log(`Added performer ${user.name} to performers list`);
1836 |             }
1837 |           }
1838 |           
1839 |           if (user.id === localSignatories.value.verifiedBy) {
1840 |             // Add to verifiers if not already there
1841 |             if (!loadedVerifiers.value.some(v => v.id === user.id)) {
1842 |               loadedVerifiers.value.push(user);
1843 |               console.log(`Added verifier ${user.name} to verifiers list`);
1844 |             }
1845 |           }
1846 |           
1847 |           if (user.id === localSignatories.value.pathologist) {
1848 |             // Add to pathologists if not already there
1849 |             if (!loadedPathologists.value.some(p => p.id === user.id)) {
1850 |               loadedPathologists.value.push(user);
1851 |               console.log(`Added pathologist ${user.name} to pathologists list`);
1852 |             }
1853 |           }
1854 |         });
1855 |       } catch (error) {
1856 |         console.error('Error loading users by ID:', error);
1857 |       }
1858 |     }
1859 |   } catch (error) {
1860 |     console.error('Error loading role users:', error);
1861 |     ElMessage.error('Failed to load signatories');
1862 |   } finally {
1863 |     loadingRoles.value = false;
1864 |   }
1865 | };
1866 | 
1867 | // Import the calculate age method from the parent component
1868 | const calculateAge = (dob) => {
1869 |   if (!dob) return 'N/A';
1870 |   
1871 |   try {
1872 |     const birthDate = parseISO(dob);
1873 |     const today = new Date();
1874 |     
1875 |     // Calculate differences
1876 |     const years = differenceInYears(today, birthDate);
1877 |     const months = differenceInMonths(today, birthDate);
1878 |     const days = differenceInDays(today, birthDate);
1879 |     
1880 |     // Return appropriate unit based on age
1881 |     if (years >= 1) {
1882 |       return `${years}`;
1883 |     } else if (months >= 1) {
1884 |       return `${months}`;
1885 |     } else {
1886 |       return `${days}`;
1887 |     }
1888 |   } catch (e) {
1889 |     console.error('Error calculating age:', e);
1890 |     return 'Invalid Date';
1891 |   }
1892 | };
1893 | 
1894 | // Compute the suffix unit based on the patient's age
1895 | const suffixUnit = computed(() => {
1896 |   if (!props.requestDetails?.patient?.date_of_birth) return 'years';
1897 |   
1898 |   const birthDate = parseISO(props.requestDetails.patient.date_of_birth);
1899 |   const today = new Date();
1900 |   
1901 |   const years = differenceInYears(today, birthDate);
1902 |   const months = differenceInMonths(today, birthDate);
1903 |   
1904 |   if (years >= 1) {
1905 |     return years === 1 ? 'year' : 'years';
1906 |   } else if (months >= 1) {
1907 |     return months === 1 ? 'month' : 'months';
1908 |   } else {
1909 |     return 'days';
1910 |   }
1911 | });
1912 | 
1913 | // Helper function to determine the correct parameter name
1914 | const getParameterName = (param, test) => {
1915 |   // Log the parameter object to debug
1916 |   console.log('Getting name for parameter:', param);
1917 |   
1918 |   // First try to get name directly from param
1919 |   if (param.name && typeof param.name === 'string' && param.name.trim() !== '') {
1920 |     return param.name.trim();
1921 |   }
1922 |   
1923 |   // Next try test_name
1924 |   if (param.test_name && typeof param.test_name === 'string' && param.test_name.trim() !== '') {
1925 |     return param.test_name.trim();
1926 |   }
1927 |   
1928 |   // Try the testParameter relationship if it exists
1929 |   if (param.testParameter && param.testParameter.name) {
1930 |     return param.testParameter.name.trim();
1931 |   }
1932 |   
1933 |   // If all else fails, use the parameter ID
1934 |   return `Parameter ${param.id}`;
1935 | };
1936 | 
1937 | // Fetch parameters for a single test
1938 | const fetchTestParameters = async (testId, resultsDataSource = null) => {
1939 |   if (!testId) return;
1940 |   
1941 |   console.log(`Log 4: fetchTestParameters called for testId: ${testId} with resultsDataSource:`, JSON.parse(JSON.stringify(resultsDataSource)));
1942 |   
1943 |   try {
1944 |     // Get the parameters for this test
1945 |     const response = await api.get(`/tests/${testId}/parameters`);
1946 |     
1947 |     if (!Array.isArray(response.data)) {
1948 |       console.error('Invalid response data format (not an array):', response.data);
1949 |       testParameters.value[testId] = [];
1950 |       return;
1951 |     }
1952 |     
1953 |     console.log(`Received ${response.data.length} parameters for test ID ${testId}:`, response.data);
1954 |     console.log('First parameter sample:', {
1955 |       id: response.data[0]?.id,
1956 |       name: response.data[0]?.name,
1957 |       conversion_factor: response.data[0]?.conversion_factor,
1958 |       unit: response.data[0]?.unit,
1959 |       conventional_unit: response.data[0]?.conventional_unit
1960 |     });
1961 | 
1962 |     let needsMoreDetails = false;
1963 |     for (const param of response.data) {
1964 |       if (param.test_name && (!param.name || param.name.trim() === '')) {
1965 |         param.name = param.test_name;
1966 |       }
1967 |       if ((!param.name || param.name.trim() === '') && param.id) {
1968 |         needsMoreDetails = true;
1969 |         break;
1970 |       }
1971 |     }
1972 | 
1973 |     if (needsMoreDetails) {
1974 |       console.log('Some parameters missing names, getting more details...');
1975 |       try {
1976 |         // Pass the resultsDataSource to fetchParametersWithDetails
1977 |         await fetchParametersWithDetails(testId, response.data, resultsDataSource);
1978 |         return; 
1979 |       } catch (detailsError) {
1980 |         console.error('Failed to get parameter details, falling back to basic parameters:', detailsError);
1981 |       }
1982 |     }
1983 | 
1984 |     // Determine the source for saved results
1985 |     // Build fallback mapping from requestDetails.results if testParameters is missing
1986 |     let fallbackResultsMapByTest = null;
1987 |     if (!resultsDataSource && (!props.requestDetails.testParameters || Object.keys(props.requestDetails.testParameters).length === 0)) {
1988 |       const resultsArr = props.requestDetails.results || [];
1989 |       if (Array.isArray(resultsArr) && resultsArr.length > 0) {
1990 |         fallbackResultsMapByTest = {};
1991 |         resultsArr.forEach(r => {
1992 |           const tId = r.test_id;
1993 |           const pId = r.test_parameter?.id ?? r.test_parameter_id;
1994 |           if (!tId || !pId) return;
1995 |           if (!fallbackResultsMapByTest[tId]) fallbackResultsMapByTest[tId] = [];
1996 |           fallbackResultsMapByTest[tId].push({
1997 |             id: pId,
1998 |             result_id: r.id,
1999 |             resultValue: r.result_value,
2000 |             unit: r.unit,
[Tool]
File: frontend/src/components/CdsAlertBanner.vue
  1 | <template>
  2 |   <div class="unified-alert-banner" :class="{
  3 |     'banner-success': transientState.value?.type === 'success',
  4 |     'banner-denied': transientState.value?.type === 'denied'
  5 |   }" v-if="shouldShowBanner">
  6 |     <!-- Banner Header -->
  7 |     <div class="banner-header">
  8 |       <div class="banner-icon">
  9 |         <el-icon v-if="transientState.value?.type === 'success'"><SuccessFilled /></el-icon>
 10 |         <el-icon v-else-if="transientState.value?.type === 'denied'"><InfoFilled /></el-icon>
 11 |         <el-icon v-else-if="bannerType === 'qc-hold'"><WarningFilled /></el-icon>
 12 |         <el-icon v-else-if="bannerType === 'critical-value'"><WarningFilled /></el-icon>
 13 |         <el-icon v-else-if="bannerType === 'cds-alert' && pendingReflexAlert"><InfoFilled /></el-icon>
 14 |         <el-icon v-else-if="bannerType === 'acknowledgment'"><SuccessFilled /></el-icon>
 15 |         <el-icon v-else><InfoFilled /></el-icon>
 16 |       </div>
 17 |       <div class="banner-title-section">
 18 |         <div class="banner-title">{{ bannerTitle }}</div>
 19 |         <div class="banner-subtitle">{{ bannerSubtitle }}</div>
 20 |         <div v-if="transientState.value" class="transient-countdown">
 21 |           <el-progress
 22 |             :percentage="transientProgress"
 23 |             :stroke-width="4"
 24 |             :show-text="false"
 25 |             :color="transientState.value.type === 'success' ? '#67c23a' : '#909399'"
 26 |           />
 27 |         </div>
 28 |       </div>
 29 |       <div class="banner-actions" v-if="hasActionButtons">
 30 |         <el-button 
 31 |           v-if="bannerType === 'cds-alert' && pendingReflexAlert"
 32 |           type="success" 
 33 |           size="large" 
 34 |           @click="handleApprove"
 35 |           :loading="isProcessing"
 36 |         >
 37 |           APPROVE {{ pendingReflexAlert?.reflex_test_name }}
 38 |         </el-button>
 39 |         <el-button 
 40 |           v-if="bannerType === 'cds-alert' && pendingReflexAlert"
 41 |           type="info" 
 42 |           size="large" 
 43 |           @click="showLogicModal = true"
 44 |         >
 45 |           VIEW CLINICAL LOGIC
 46 |         </el-button>
 47 |         <el-button 
 48 |           v-if="bannerType === 'cds-alert' && pendingReflexAlert"
 49 |           size="large" 
 50 |           @click="handleDeny"
 51 |           :disabled="isProcessing"
 52 |         >
 53 |           DENY/IGNORE
 54 |         </el-button>
 55 |         <el-button
 56 |           v-if="hasAcknowledgeButton"
 57 |           type="primary"
 58 |           size="large"
 59 |           @click="handleAcknowledge"
 60 |           :loading="acknowledging"
 61 |         >
 62 |           Acknowledge
 63 |         </el-button>
 64 |       </div>
 65 |       <el-button 
 66 |         class="expand-btn" 
 67 |         type="text" 
 68 |         @click="showDetails = !showDetails"
 69 |         :icon="showDetails ? 'ArrowUp' : 'ArrowDown'"
 70 |       />
 71 |     </div>
 72 | 
 73 |     <!-- Expandable Details Section -->
 74 |     <div class="banner-details" v-if="showDetails">
 75 |       <div class="details-content">
 76 |         <!-- QC Hold Details -->
 77 |         <div v-if="bannerType === 'qc-hold'">
 78 |           <h4>QC Lockout Active</h4>
 79 |           <p>{{ qcHoldMessage }}</p>
 80 |           <p class="details-note">Please contact your laboratory supervisor or quality control officer to resolve this issue before proceeding.</p>
 81 |         </div>
 82 | 
 83 |         <!-- CDS Alert Details (Reflex) -->
 84 |         <div v-else-if="bannerType === 'cds-alert' && pendingReflexAlert">
 85 |           <h4>Clinical Logic:</h4>
 86 |           <p>{{ pendingReflexAlert.description }}</p>
 87 |           <div v-if="pendingReflexAlert.conditions && pendingReflexAlert.conditions.length">
 88 |             <strong>Conditions:</strong>
 89 |             <ul>
 90 |               <li v-for="(cond, idx) in pendingReflexAlert.conditions" :key="idx">{{ cond }}</li>
 91 |             </ul>
 92 |           </div>
 93 |           <div v-if="pendingReflexAlert.reflex_test_name">
 94 |             <strong>Recommended Reflex Test:</strong> {{ pendingReflexAlert.reflex_test_name }}
 95 |           </div>
 96 |         </div>
 97 | 
 98 |         <!-- CDS Alert Details (Other) -->
 99 |         <div v-else-if="bannerType === 'cds-alert' && !pendingReflexAlert && unacknowledgedCdsAlert">
100 |           <h4>CDS Rule: {{ unacknowledgedCdsAlert.rule_name }}</h4>
101 |           <p>{{ unacknowledgedCdsAlert.description }}</p>
102 |           <div v-if="unacknowledgedCdsAlert.conditions && unacknowledgedCdsAlert.conditions.length">
103 |             <strong>Conditions:</strong>
104 |             <ul>
105 |               <li v-for="(cond, idx) in unacknowledgedCdsAlert.conditions" :key="idx">{{ cond }}</li>
106 |             </ul>
107 |           </div>
108 |         </div>
109 | 
110 |         <!-- Critical Values Details -->
111 |         <div v-else-if="bannerType === 'critical-value'">
112 |           <h4>Critical Results Requiring Action</h4>
113 |           <ul>
114 |             <li v-for="cv in criticalValues" :key="cv.parameter_id">
115 |               <strong>{{ cv.parameter }}</strong>: {{ cv.value }} {{ cv.unit }} - {{ cv.message }}
116 |             </li>
117 |           </ul>
118 |         </div>
119 | 
120 |         <!-- Acknowledgment Details -->
121 |         <div v-else-if="bannerType === 'acknowledgment'">
122 |           <h4>Acknowledged Items</h4>
123 |           <ul>
124 |             <li v-if="cdsAcknowledgeData">CDS Rule: {{ cdsAcknowledgeData.rule_name }}</li>
125 |             <li v-if="deltaAcknowledgeData">Delta Check: {{ deltaAcknowledgeData.rule_name }}</li>
126 |             <li v-if="criticalAcknowledgeData">Critical Value: {{ criticalAcknowledgeData.rule_name }}</li>
127 |           </ul>
128 |           <p class="details-note">All critical actions have been acknowledged. You may now proceed with validation.</p>
129 |         </div>
130 |       </div>
131 |     </div>
132 | 
133 |     <!-- Clinical Logic Modal (for reflex tests) -->
134 |     <el-dialog
135 |       v-model="showLogicModal"
136 |       title="Clinical Logic Details"
137 |       width="50%"
138 |       :close-on-click-modal="false"
139 |       v-if="pendingReflexAlert"
140 |     >
141 |       <div class="logic-modal-content">
142 |         <h4>Rule: {{ pendingReflexAlert.rule_name }}</h4>
143 |         <p><strong>Description:</strong> {{ pendingReflexAlert.description }}</p>
144 |         <div v-if="pendingReflexAlert.conditions && pendingReflexAlert.conditions.length">
145 |           <strong>Conditions:</strong>
146 |           <ul>
147 |             <li v-for="(cond, idx) in pendingReflexAlert.conditions" :key="idx">{{ cond }}</li>
148 |           </ul>
149 |         </div>
150 |         <div v-if="pendingReflexAlert.reflex_test_name">
151 |           <strong>Recommended Reflex Test:</strong> {{ pendingReflexAlert.reflex_test_name }}
152 |         </div>
153 |       </div>
154 |       <template #footer>
155 |         <el-button @click="showLogicModal = false">Close</el-button>
156 |       </template>
157 |     </el-dialog>
158 |   </div>
159 | </template>
160 | 
161 | <script setup>
162 | import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
163 | import { ElMessage } from 'element-plus'
164 | 
165 | // Transient state management for success/denied feedback
166 | const transientState = ref(null) // { type: 'success'|'denied', message: string, startTime: number }
167 | const isMounted = ref(true)
168 | const activeTimeouts = ref([])
169 | 
170 | onMounted(() => {
171 |   isMounted.value = true
172 | })
173 | 
174 | onBeforeUnmount(() => {
175 |   isMounted.value = false
176 |   // Clear all pending timeouts
177 |   activeTimeouts.value.forEach(timeoutId => {
178 |     clearTimeout(timeoutId)
179 |   })
180 |   activeTimeouts.value = []
181 | })
182 | 
183 | const clearTransientState = () => {
184 |   if (!isMounted.value) return
185 |   // Clear all pending timeouts
186 |   activeTimeouts.value.forEach(timeoutId => {
187 |     clearTimeout(timeoutId)
188 |   })
189 |   activeTimeouts.value = []
190 |   transientState.value = null
191 | }
192 | 
193 | const showTransientSuccess = async (message) => {
194 |   if (!isMounted.value) return
195 |   
196 |   await nextTick()
197 |   if (!isMounted.value) return
198 |   
199 |   clearTransientState()
200 |   if (!isMounted.value) return
201 |   
202 |   transientState.value = {
203 |     type: 'success',
204 |     message,
205 |     startTime: Date.now()
206 |   }
207 |   
208 |   const timeoutId = setTimeout(() => {
209 |     if (isMounted.value) {
210 |       clearTransientState()
211 |     }
212 |   }, 5000)
213 |   
214 |   activeTimeouts.value.push(timeoutId)
215 | }
216 | 
217 | const showTransientDenied = async (message) => {
218 |   if (!isMounted.value) return
219 |   
220 |   await nextTick()
221 |   if (!isMounted.value) return
222 |   
223 |   clearTransientState()
224 |   if (!isMounted.value) return
225 |   
226 |   transientState.value = {
227 |     type: 'denied',
228 |     message,
229 |     startTime: Date.now()
230 |   }
231 |   
232 |   const timeoutId = setTimeout(() => {
233 |     if (isMounted.value) {
234 |       clearTransientState()
235 |     }
236 |   }, 5000)
237 |   
238 |   activeTimeouts.value.push(timeoutId)
239 | }
240 | 
241 | const transientProgress = computed(() => {
242 |   if (!transientState.value?.startTime) return 0
243 |   const elapsed = Date.now() - transientState.value.startTime
244 |   const remaining = Math.max(0, 5000 - elapsed)
245 |   return Math.min(100, (elapsed / 5000) * 100)
246 | })
247 | 
248 | const props = defineProps({
249 |   // QC Hold data
250 |   qcHoldMessage: { 
251 |     type: String, 
252 |     default: '' 
253 |   },
254 |   hasHoldStatus: { 
255 |     type: Boolean, 
256 |     default: false 
257 |   },
258 |   
259 |   // CDS Alerts
260 |   cdsAlerts: { 
261 |     type: Array, 
262 |     default: () => [] 
263 |   },
264 |   
265 |   // Critical Values
266 |   criticalValues: { 
267 |     type: Array, 
268 |     default: () => [] 
269 |   },
270 |   
271 |   // Acknowledgment data
272 |   cdsAcknowledgeData: { 
273 |     type: Object, 
274 |     default: null 
275 |   },
276 |   deltaAcknowledgeData: { 
277 |     type: Object, 
278 |     default: null 
279 |   },
280 |   criticalAcknowledgeData: { 
281 |     type: Object, 
282 |     default: null 
283 |   },
284 |   
285 |   // Request data
286 |   requestId: { 
287 |     type: Number, 
288 |     required: true 
289 |   },
290 |   
291 |   // Callbacks
292 |   onApprove: { 
293 |     type: Function, 
294 |     default: null 
295 |   },
296 |   onDeny: { 
297 |     type: Function, 
298 |     default: null 
299 |   },
300 |   onAcknowledge: { 
301 |     type: Function, 
302 |     default: null 
303 |   },
304 |   onRefresh: { 
305 |     type: Function, 
306 |     default: null 
307 |   }
308 | })
309 | 
310 | const emit = defineEmits(['approve', 'deny', 'acknowledge', 'refresh'])
311 | 
312 | const isProcessing = ref(false)
313 | const acknowledging = ref(false)
314 | const showDetails = ref(false)
315 | const showLogicModal = ref(false)
316 | 
317 | // Computed properties for banner state
318 | const hasUnacknowledgedAlerts = computed(() => {
319 |   const hasCds = props.cdsAlerts.length > 0
320 |   const hasDelta = props.hasHoldStatus
321 |   const hasCritical = props.criticalValues.length > 0
322 |   return (hasCds && !props.cdsAcknowledgeData)
323 |     || (hasDelta && !props.deltaAcknowledgeData)
324 |     || (hasCritical && !props.criticalAcknowledgeData)
325 | })
326 | 
327 | const hasAnyAcknowledgment = computed(() => {
328 |   return !!(props.cdsAcknowledgeData || props.deltaAcknowledgeData || props.criticalAcknowledgeData)
329 | })
330 | 
331 | const pendingReflexAlert = computed(() => {
332 |   if (!props.cdsAlerts || props.cdsAlerts.length === 0) return null
333 |   return props.cdsAlerts.find(alert => 
334 |     alert.action_type === 'REFLEX_TEST' && 
335 |     !alert.is_acknowledged && 
336 |     !alert.reflex_created
337 |   )
338 | })
339 | 
340 | const unacknowledgedCdsAlert = computed(() => {
341 |   if (!props.cdsAlerts || props.cdsAlerts.length === 0) return null
342 |   return props.cdsAlerts.find(alert => 
343 |     !alert.is_acknowledged && 
344 |     alert.action_type !== 'REFLEX_TEST'
345 |   )
346 | })
347 | 
348 | const bannerType = computed(() => {
349 |   if (transientState.value) return transientState.value.type === 'success' ? 'success' : 'denied'
350 |   if (props.qcHoldMessage) return 'qc-hold'
351 |   if (hasUnacknowledgedAlerts.value) {
352 |     if (pendingReflexAlert.value) return 'cds-alert'
353 |     if (unacknowledgedCdsAlert.value) return 'cds-alert'
354 |     if (props.hasHoldStatus) return 'critical-hold'
355 |     if (props.criticalValues.length > 0) return 'critical-value'
356 |   }
357 |   if (hasAnyAcknowledgment.value) return 'acknowledgment'
358 |   return null
359 | })
360 | 
361 | const shouldShowBanner = computed(() => {
362 |   if (transientState.value) return true
363 |   if (pendingReflexAlert.value) return true
364 |   if (hasUnacknowledgedAlerts.value) return true
365 |   if (props.qcHoldMessage) return true
366 |   if (props.criticalValues.length > 0) return true
367 |   return false
368 | })
369 | 
370 | const bannerTitle = computed(() => {
371 |   switch (bannerType.value) {
372 |     case 'success':
373 |       return 'ACTION COMPLETED'
374 |     case 'denied':
375 |       return 'ACTION DECLINED'
376 |     case 'qc-hold':
377 |       return 'QC LOCKOUT ACTIVE'
378 |     case 'critical-hold':
379 |       return 'CRITICAL HOLD: ACTION REQUIRED'
380 |     case 'critical-value':
381 |       return 'CRITICAL RESULT: ACTION REQUIRED'
382 |     case 'cds-alert':
383 |       return pendingReflexAlert.value
384 |         ? 'REFLEX TEST REQUIRED'
385 |         : 'CDS RULE EXCEPTION: ACTION REQUIRED'
386 |     case 'acknowledgment':
387 |       return 'RULES ACKNOWLEDGED'
388 |     default:
389 |       return ''
390 |   }
391 | })
392 | 
393 | const bannerSubtitle = computed(() => {
394 |   switch (bannerType.value) {
395 |     case 'success':
396 |       return transientState.value?.message || 'Action completed successfully'
397 |     case 'denied':
398 |       return transientState.value?.message || 'Action has been declined'
399 |     case 'qc-hold':
400 |       return `Reason: ${props.qcHoldMessage}`
401 |     case 'critical-hold':
402 |       return 'Delta check violation detected. Action required before proceeding.'
403 |     case 'critical-value':
404 |       return 'Critical value detected. Acknowledgment required before validation.'
405 |     case 'cds-alert':
406 |       return pendingReflexAlert.value
407 |         ? `Rule: ${pendingReflexAlert.value.rule_name}`
408 |         : `Rule: ${unacknowledgedCdsAlert.value?.rule_name || 'CDS Alert'}`
409 |     case 'acknowledgment':
410 |       return 'All alerts and violations have been acknowledged.'
411 |     default:
412 |       return ''
413 |   }
414 | })
415 | 
416 | const hasActionButtons = computed(() => {
417 |   // No action buttons for transient states
418 |   if (transientState.value) return false
419 |   return bannerType.value === 'cds-alert' && pendingReflexAlert.value
420 | })
421 | 
422 | const hasAcknowledgeButton = computed(() => {
423 |   // No acknowledge button for transient states
424 |   if (transientState.value) return false
425 |   return hasUnacknowledgedAlerts.value && !pendingReflexAlert.value
426 | })
427 | 
428 | // Handlers
429 | const handleApprove = async () => {
430 |   if (!props.onApprove && !pendingReflexAlert.value) return
431 |   
432 |   if (!isMounted) return
433 |   isProcessing.value = true
434 |   try {
435 |     if (props.onApprove) {
436 |       await props.onApprove()
437 |     } else {
438 |       // Use the Laravel API endpoint directly
439 |       await window.axios.post(
440 |         `/laboratory-requests/${props.requestId}/approve-reflex-test`,
441 |         {
442 |           rule_id: pendingReflexAlert.value.id,
443 |           reflex_test_id: pendingReflexAlert.value.reflex_test_id,
444 |           auto_create: false
445 |         }
446 |       )
447 |       if (isMounted) {
448 |         ElMessage.success('Reflex test approved successfully')
449 |       }
450 |     }
451 |     if (isMounted) {
452 |       emit('approve')
453 |       emit('refresh')
454 |       // Show transient success state
455 |       showTransientSuccess('Reflex test approved successfully')
456 |     }
457 |   } catch (error) {
458 |     console.error('Error approving reflex test:', error)
459 |     if (isMounted) {
460 |       ElMessage.error('Failed to approve reflex test')
461 |     }
462 |   } finally {
463 |     if (isMounted) {
464 |       isProcessing.value = false
465 |     }
466 |   }
467 | }
468 | 
469 | const handleDeny = async () => {
470 |   if (!props.onDeny && !pendingReflexAlert.value) return
471 |   
472 |   if (!isMounted) return
473 |   try {
474 |     if (props.onDeny) {
475 |       await props.onDeny()
476 |     } else {
477 |       // Use the Laravel API endpoint directly
478 |       await window.axios.post(
479 |         `/laboratory-requests/${props.requestId}/deny-reflex-test`,
480 |         {
481 |           rule_id: pendingReflexAlert.value.id,
482 |           reason: ''
483 |         }
484 |       )
485 |       if (isMounted) {
486 |         ElMessage.info('Reflex test denied')
487 |       }
488 |     }
489 |     if (isMounted) {
490 |       emit('deny')
491 |       emit('refresh')
492 |       // Show transient denied state
493 |       showTransientDenied('Reflex test denied')
494 |     }
495 |   } catch (error) {
496 |     console.error('Error denying reflex test:', error)
497 |     if (isMounted) {
498 |       ElMessage.error('Failed to deny reflex test')
499 |     }
500 |   }
501 | }
502 | 
503 | const handleAcknowledge = async () => {
504 |   if (!props.onAcknowledge) return
505 |   
506 |   if (!isMounted) return
507 |   acknowledging.value = true
508 |   try {
509 |     await props.onAcknowledge()
510 |     if (isMounted) {
511 |       emit('acknowledge')
512 |       emit('refresh')
513 |     }
514 |   } catch (error) {
515 |     console.error('Error acknowledging:', error)
516 |     if (isMounted) {
517 |       ElMessage.error('Failed to acknowledge')
518 |     }
519 |   } finally {
520 |     if (isMounted) {
521 |       acknowledging.value = false
522 |     }
523 |   }
524 | }
525 | </script>
526 | 
527 | <style scoped>
528 | .unified-alert-banner {
529 |   position: relative;
530 |   border-radius: 8px;
531 |   box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
532 |   padding: 16px 24px;
533 |   margin-bottom: 16px;
534 |   width: 100%;
535 |   box-sizing: border-box;
536 | }
537 | 
538 | /* Banner Type Styling */
539 | .unified-alert-banner {
540 |   background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
541 |   border: 2px solid #f56c6c;
542 | }
543 | 
544 | .unified-alert-banner.banner-success {
545 |   background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
546 |   border: 2px solid #409eff;
547 | }
548 | 
549 | .unified-alert-banner.banner-denied {
550 |   background: linear-gradient(135deg, #f4f4f5 0%, #e9e9eb 100%);
551 |   border: 2px solid #909399;
552 | }
553 | 
554 | .unified-alert-banner.banner-type-acknowledgment {
555 |   background: linear-gradient(135deg, #f0f9ff 0%, #e1f5fe 100%);
556 |   border: 2px solid #409eff;
557 | }
558 | 
559 | .banner-header {
560 |   display: flex;
561 |   align-items: center;
562 |   gap: 16px;
563 |   flex-wrap: wrap;
564 | }
565 | 
566 | .banner-icon {
567 |   display: flex;
568 |   align-items: center;
569 |   justify-content: center;
570 |   width: 40px;
571 |   height: 40px;
572 |   border-radius: 50%;
573 |   background: rgba(245, 108, 108, 0.1);
574 |   color: #f56c6c;
575 | }
576 | 
577 | .banner-success .banner-icon {
578 |   background: rgba(64, 158, 255, 0.1);
579 |   color: #409eff;
580 | }
581 | 
582 | .banner-denied .banner-icon {
583 |   background: rgba(144, 147, 153, 0.1);
584 |   color: #909399;
585 | }
586 | 
587 | .banner-type-acknowledgment .banner-icon {
588 |   background: rgba(64, 158, 255, 0.1);
589 |   color: #409eff;
590 | }
591 | 
592 | .banner-title-section {
593 |   flex: 1;
594 |   min-width: 200px;
595 | }
596 | 
597 | .banner-title {
598 |   font-size: 18px;
599 |   font-weight: bold;
600 |   color: #303133;
601 |   margin-bottom: 4px;
602 | }
603 | 
604 | .banner-subtitle {
605 |   font-size: 14px;
606 |   color: #606266;
607 | }
608 | 
609 | .banner-actions {
610 |   display: flex;
611 |   gap: 12px;
612 |   align-items: center;
613 |   flex-wrap: wrap;
614 | }
615 | 
616 | .expand-btn {
617 |   margin-left: auto;
618 |   transform: rotate(0deg);
619 |   transition: transform 0.3s;
620 | }
621 | 
622 | .expand-btn.expanded {
623 |   transform: rotate(180deg);
624 | }
625 | 
626 | .banner-details {
627 |   background: rgba(255, 255, 255, 0.8);
628 |   border-top: 1px solid currentColor;
629 |   padding: 16px 24px;
630 |   margin-top: 12px;
631 |   border-radius: 0 0 8px 8px;
632 | }
633 | 
634 | .details-content h4 {
635 |   margin: 0 0 12px 0;
636 |   color: #f56c6c;
637 |   font-size: 16px;
638 | }
639 | 
640 | .banner-success .details-content h4 {
641 |   color: #409eff;
642 | }
643 | 
644 | .banner-denied .details-content h4 {
645 |   color: #909399;
646 | }
647 | 
648 | .banner-type-acknowledgment .details-content h4 {
649 |   color: #409eff;
650 | }
651 | 
652 | .details-content ul {
653 |   margin: 8px 0;
654 |   padding-left: 20px;
655 | }
656 | 
657 | .details-content li {
658 |   margin-bottom: 8px;
659 |   line-height: 1.6;
660 | }
661 | 
662 | .details-note {
663 |   margin-top: 12px;
664 |   font-style: italic;
665 |   color: #909399;
666 | }
667 | 
668 | .logic-modal-content h4 {
669 |   margin-top: 0;
670 |   color: #f56c6c;
671 | }
672 | 
673 | .transient-countdown {
674 |   margin-top: 8px;
675 |   width: 100%;
676 | }
677 | 
678 | .transient-countdown :deep(.el-progress-bar__outer) {
679 |   background-color: rgba(0, 0, 0, 0.1);
680 |   border-radius: 2px;
681 | }
682 | 
683 | .transient-countdown :deep(.el-progress-bar__inner) {
684 |   border-radius: 2px;
685 |   transition: all 0.3s ease;
686 | }
687 | 
688 | /* Responsive adjustments */
689 | @media (max-width: 768px) {
690 |   .banner-header {
691 |     flex-direction: column;
692 |     align-items: flex-start;
693 |   }
694 |   
695 |   .banner-actions {
696 |     width: 100%;
697 |     justify-content: flex-start;
698 |     margin-top: 12px;
699 |   }
700 |   
701 |   .expand-btn {
702 |     margin-left: 0;
703 |     align-self: flex-end;
704 |   }
705 | }
706 | </style>
707 | 
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:22:56.128Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Read and analyze UniversalResultModal.vue | Pending |
| 2 | Read UniversalResultModal-Reflex-broken.vue for comparison | Pending |
| 3 | Read CdsAlertBanner.vue to understand existing alert system | Pending |
| 4 | Read backend controllers and models for reflex tracking | Pending |
| 5 | Read API documentation and testing guide | Pending |
| 6 | Identify all issues in current implementation | Pending |
| 7 | Design comprehensive solution | Pending |
| 8 | Implement centralized alert banner component | Pending |
| 9 | Update UniversalResultModal with proper reflex handling | Pending |
| 10 | Add isMounted guards to all async operations | Pending |
| 11 | Implement visual state management (red→blue/green→gray) | Pending |
| 12 | Add auto-dismiss with countdown timer | Pending |
| 13 | Ensure audit trail logging | Pending |
| 14 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 15 | Verify all functions remain intact | Pending |
| 16 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Let me continue reading the remaining parts of these files and the backend files:
[Tool Use: read_file]
Path: frontend/src/components/modals/result/UniversalResultModal.vue
Mode: slice
Offset: 2001
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: backend/app/Http/Controllers/Api/ResultManagementController.php
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: backend/app/Models/LaboratoryRequest.php
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: backend/app/Models/LaboratoryRequestTest.php
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}

---

**User:**

[Tool]
File: frontend/src/components/modals/result/UniversalResultModal.vue
IMPORTANT: File content truncated.
	Status: Showing lines 2001-4000 of 6022 total lines.
	To read more: Use the read_file tool with offset=4001 and limit=2000.
	
	2001 |                 ? (current.resultValue ?? current.result_value ?? '')
2002 |                 : '')
2003 |         };
2004 |         return paramObj;
2005 |       });
2006 |       
2007 |     console.log(`Processed ${testParameters.value[testId].length} parameters for test ID ${testId}`, testParameters.value[testId]);
2008 |   } catch (error) {
2009 |     console.error(`Error fetching parameters for test ${testId}:`, error);
2010 |     testParameters.value[testId] = [];
2011 |   }
2012 | };
2013 | 
2014 | // Fetch parameters with more details - alternative API call
2015 | const fetchParametersWithDetails = async (testId, basicParameters = [], resultsDataSource = null) => {
2016 |   try {
2017 |     const response = await api.get(`/test-parameters?test_id=${testId}&include=details`);
2018 |     
2019 |     if (Array.isArray(response.data) && response.data.length > 0) {
2020 |       console.log('Got detailed parameters:', response.data);
2021 |       
2022 |       // Determine the source for saved results
2023 |       // Build fallback mapping from requestDetails.results if testParameters is missing
2024 |       let fallbackResultsMapByTest = null;
2025 |       if (!resultsDataSource && (!props.requestDetails.testParameters || Object.keys(props.requestDetails.testParameters).length === 0)) {
2026 |         const resultsArr = props.requestDetails.results || [];
2027 |         if (Array.isArray(resultsArr) && resultsArr.length > 0) {
2028 |           fallbackResultsMapByTest = {};
2029 |           resultsArr.forEach(r => {
2030 |             const tId = r.test_id;
2031 |             const pId = r.test_parameter?.id ?? r.test_parameter_id;
2032 |             if (!tId || !pId) return;
2033 |             if (!fallbackResultsMapByTest[tId]) fallbackResultsMapByTest[tId] = [];
2034 |             fallbackResultsMapByTest[tId].push({
2035 |               id: pId,
2036 |               result_id: r.id,
2037 |               resultValue: r.result_value,
2038 |               unit: r.unit,
2039 |               instrument: r.equipment_id,
2040 |               resultComment: r.test_parameter?.result_comment ?? '',
2041 |               flag: r.flag ?? null,
2042 |               conventional: '',
2043 |               conventional_unit: '',
2044 |               conversion_factor: null,
2045 |               version: r.version || 'R0',
2046 |               status: r.status || 'pending',
2047 |               created_at: r.created_at,
2048 |               updated_at: r.updated_at
2049 |             });
2050 |           });
2051 |         }
2052 |       }
2053 |       const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters || fallbackResultsMapByTest;
2054 |       const savedResults = sourceForSavedResults?.[testId] || [];
2055 |       
2056 |       const savedResultsMap = {};
2057 |       if (Array.isArray(savedResults)) {
2058 |         savedResults.forEach(result => {
2059 |           const paramId = (result && (
2060 |             result.test_parameter_id ??
2061 |             result?.test_parameter?.id ??
2062 |             result.parameterId ??
2063 |             result.id
2064 |           ));
2065 |           if (paramId) {
2066 |             if (!savedResultsMap[paramId]) savedResultsMap[paramId] = [];
2067 |             savedResultsMap[paramId].push(result);
2068 |           }
2069 |         });
2070 |       }
2071 |       
2072 |       const basicParamMap = {};
2073 |       basicParameters.forEach(param => {
2074 |         if (param && param.id) {
2075 |           basicParamMap[param.id] = param;
2076 |         }
2077 |       });
2078 |       
2079 |       // Utility to rank versions
2080 |       const rankVersion = (v) => {
2081 |         if (!v) return 0;
2082 |         const m = String(v).match(/R(\d+)/i);
2083 |         return m ? parseInt(m[1], 10) : 0;
2084 |       };
2085 |       const pickLatest = (list = []) => {
2086 |         if (!Array.isArray(list) || list.length === 0) return null;
2087 |         return list.slice().sort((a,b) => {
2088 |           const ra = rankVersion(a.version);
2089 |           const rb = rankVersion(b.version);
2090 |           if (rb !== ra) return rb - ra;
2091 |           const ca = new Date(a.created_at || 0).getTime();
2092 |           const cb = new Date(b.created_at || 0).getTime();
2093 |           return cb - ca;
2094 |         })[0];
2095 |       };
2096 |       const pickPrevious = (list = [], current) => {
2097 |         if (!Array.isArray(list)) return null;
2098 |         const filtered = list.filter(r => !current || r !== current);
2099 |         if (filtered.length === 0) return null;
2100 |         return filtered.slice().sort((a,b) => {
2101 |           const ra = rankVersion(a.version);
2102 |           const rb = rankVersion(b.version);
2103 |           if (rb !== ra) return rb - ra;
2104 |           const ca = new Date(a.created_at || 0).getTime();
2105 |           const cb = new Date(b.created_at || 0).getTime();
2106 |           return cb - ca;
2107 |         })[0];
2108 |       };
2109 | 
2110 |       testParameters.value[testId] = response.data
2111 |         .filter(param => param && param.id)
2112 |         .map(param => {
2113 |           const savedList = savedResultsMap[param.id] || [];
2114 |           const current = pickLatest(savedList);
2115 |           const previous = pickPrevious(savedList, current);
2116 |           const basicParam = basicParamMap[param.id];
2117 |           // ... (rest of the mapping logic, ensure it uses savedResult correctly for values)
2118 |           const paramObj = {
2119 |             id: param.id,
2120 |             name: param.name || (basicParam ? basicParam.name : '') || (basicParam ? basicParam.test_name : '') || '',
2121 |             test_name: param.test_name || param.name || (basicParam ? basicParam.test_name : '') || (basicParam ? basicParam.name : '') || `Parameter ${param.id}`,
2122 |             // When in re-run mode and only R0 exists, keep the input blank
2123 |             resultValue: current
2124 |               ? (isForRerun.value && String(current.version || 'R0').toUpperCase() === 'R0'
2125 |                   ? ''
2126 |                   : (current.resultValue ?? current.result_value ?? ''))
2127 |               : '',
2128 |             // Preserve the raw current saved value regardless of UI blanking
2129 |             currentResultRaw: current ? (current.resultValue ?? current.result_value ?? '') : '',
2130 |             unit: param.unit || (basicParam ? basicParam.unit : '') || '',
2131 |             instrument: current ? current.instrument || null : null,
2132 |             resultComment: current ? current.resultComment || '' : '',
2133 |             flag: current ? current.flag || null : null,
2134 |             result_id: current ? (current.result_id || current.id) : null,
2135 |             referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : 
2136 |                            (basicParam && Array.isArray(basicParam.reference_ranges) ? 
2137 |                             basicParam.reference_ranges : []),
2138 |             conventional: current ? current.conventional || '' : '',
2139 |             conventional_unit: param.conventional_unit || (basicParam ? basicParam.conventional_unit : '') || '',
2140 |             conversion_factor: param.conversion_factor || (basicParam ? basicParam.conversion_factor : null),
2141 |             result_id: current && current.result_id ? current.result_id : (current ? current.id : null),
2142 |             version: current ? (current.version || 'R0') : 'R0',
2143 |             status: current ? (current.status || 'pending') : 'pending',
2144 |             previousVersionStatus: previous ? `${previous.version || 'R0'} - ${previous.status || 'pending'}` : null,
2145 |             // For Original Result: if previous exists use it; otherwise when only R0 exists, use current R0
2146 |             previousValueRaw: previous
2147 |               ? (previous.resultValue ?? previous.result_value ?? '')
2148 |               : ((isForRerun.value && current && String(current.version || 'R0').toUpperCase() === 'R0')
2149 |                   ? (current.resultValue ?? current.result_value ?? '')
2150 |                   : '')
2151 |           };
2152 |         // ... (log for param state)
2153 |           return paramObj;
2154 |         });
2155 |         
2156 |       console.log(`Processed ${testParameters.value[testId].length} detailed parameters for test ID ${testId}`);
2157 |     } else {
2158 |       console.warn('No detailed parameters found, using basic parameters');
2159 |       throw new Error('No detailed parameters available');
2160 |     }
2161 |   } catch (error) {
2162 |     console.error(`Error fetching detailed parameters for test ${testId}:`, error);
2163 |     throw error; 
2164 |   }
2165 | };
2166 | 
2167 | // Fetch parameters for all tests in the current request
2168 | const fetchAllTestParameters = async (tests, resultsDataSource = null) => {
2169 |   let workingTests = tests;
2170 |   if (!workingTests || workingTests.length === 0) {
2171 |       const resultsArr = props.requestDetails.results || [];
2172 |       if (Array.isArray(resultsArr) && resultsArr.length > 0) {
2173 |         const uniqueTestIds = Array.from(new Set(resultsArr.map(r => r.test_id).filter(Boolean)));
2174 |         workingTests = uniqueTestIds.map(id => ({ id }));
2175 |         console.log("Derived tests from results for fetchAllTestParameters:", workingTests);
2176 |       } else {
2177 |         console.log("No tests provided to fetchAllTestParameters");
2178 |         return Promise.resolve();
2179 |       }
2180 |   }
2181 |   console.log("Starting fetchAllTestParameters for tests:", workingTests, "using resultsDataSource:", resultsDataSource);
2182 |   parametersLoading.value = true; // Corrected
2183 |   testParameters.value = {};
2184 |   
2185 |   try {
2186 |     const promises = workingTests.map(test => fetchTestParameters(test.id, resultsDataSource));
2187 |     await Promise.all(promises);
2188 |     console.log("All parameters fetched:", JSON.parse(JSON.stringify(testParameters.value)));
2189 |   } catch (error) {
2190 |      console.error("Error fetching one or more test parameters:", error);
2191 |      ElMessage.error('Failed to load parameters for some tests.');
2192 |   } finally {
2193 |     parametersLoading.value = false;
2194 |   }
2195 |   
2196 |   return Promise.resolve();
2197 | };
2198 | 
2199 | const formatDateTime = (data) => {
2200 |   if (!data) return 'N/A';
2201 |   try {
2202 |     return format(parseISO(data), 'yyyy-MM-dd p');
2203 |   } catch (e) {
2204 |     return 'Invalid Date';
2205 |   }
2206 | };
2207 | 
2208 | const formatDate = (data) => {
2209 |   if (!data) return 'N/A';
2210 |   try {
2211 |     return format(parseISO(data), 'yyyy-MM-dd');
2212 |   } catch (e) {
2213 |     return 'Invalid Date';
2214 |   }
2215 | };
2216 | 
2217 | // Add try/catch to the first watch function
2218 | watch(() => props.requestDetails, async (newDetails) => {
2219 |   try {
2220 |     if (newDetails && newDetails.tests && Array.isArray(newDetails.tests)) {
2221 |       console.log("Request Details updated:", newDetails);
2222 |       
2223 |       // Additional debug logging for testParameters
2224 |       if (newDetails.testParameters) {
2225 |         console.log("DEBUG - Examining testParameters structure:");
2226 |         Object.keys(newDetails.testParameters).forEach(testId => {
2227 |           const params = newDetails.testParameters[testId];
2228 |           console.log(`Test ${testId} has ${params.length} parameters:`);
2229 |           params.forEach(param => {
2230 |             console.log(`Parameter ${param.id}: resultValue = ${param.resultValue}, resultComment = ${param.resultComment}, result_id = ${param.result_id}`);
2231 |           });
2232 |         });
2233 |       }
2234 |       
2235 |       // Determine if any test is tagged For Re-run
2236 |       isForRerun.value = Array.isArray(newDetails.tests) && newDetails.tests.some(t => (t.pivot?.item_status === 'For Re-run'));
2237 |       // Initialize result fields from tests
2238 |       resultFields.value = newDetails.tests.map(test => ({
2239 |         id: test.id,
2240 |         pivot_id: test.pivot?.id,
2241 |         pivot_status: test.pivot?.item_status,
2242 |         testName: test.name,
2243 |         unit: test.unit || '',
2244 |         referenceRange: test.reference_range || '',
2245 |         conventional_unit: test.conventional_unit || '',
2246 |         conventional_reference_range: test.conventional_reference_range || '',
2247 |         initial_resultValue: test.initial_resultValue || '',
2248 |         initial_instrument: test.initial_instrument || null,
2249 |         initial_resultComment: test.initial_resultComment || '',
2250 |         version: test.version,
2251 |         status: test.status,
2252 |         // Track if the test already has a stored result
2253 |         result_id: test.result_id || null
2254 |       }));
2255 |       
2256 |       // Debug logging for non-parameterized tests
2257 |       console.log("DEBUG - Non-parameterized test initial values:");
2258 |       resultFields.value.forEach(test => {
2259 |         if (test.initial_resultValue || test.initial_resultComment) {
2260 |           console.log(`Test ${test.id}: result_value = ${test.initial_resultValue}, result_comment = ${test.initial_resultComment}, result_id = ${test.result_id}`);
2261 |         }
2262 |       });
2263 | 
2264 |       // Initialize remarks and comments from request details if available
2265 |       localRemarks.value = newDetails.remarks || 'RESULTS VERIFIED';
2266 |       localComments.value = newDetails.comment || '';
2267 |       
2268 |       // Initialize signatories from tests - we need to check any test for the values
2269 |       // since they're now stored at the laboratory_request_test level
2270 |       if (newDetails.tests.length > 0) {
2271 |         // Find first test with performer, verifier, or pathologist info
2272 |         const testWithTrackingInfo = newDetails.tests.find(test => 
2273 |           test.performer_id || test.verifier_id || test.pathologist_id
2274 |         );
2275 |         
2276 |         console.log("Test with tracking info:", testWithTrackingInfo);
2277 |         
2278 |         if (testWithTrackingInfo) {
2279 |           localSignatories.value = {
2280 |             performedBy: testWithTrackingInfo.performer_id || null,
2281 |             verifiedBy: testWithTrackingInfo.verifier_id || null,
2282 |             pathologist: testWithTrackingInfo.pathologist_id || null
2283 |           };
2284 |           console.log("Initialized signatories:", localSignatories.value);
2285 |         } else {
2286 |           // Fallback to old structure if pivot data not available
2287 |           localSignatories.value = {
2288 |             performedBy: newDetails.performer_id || null,
2289 |             verifiedBy: newDetails.verifier_id || null,
2290 |             pathologist: newDetails.pathologist_id || null
2291 |           };
2292 |         }
2293 |       }
2294 | 
2295 |       if (newDetails.tests.length > 0) {
2296 |         // Fetch audit trail
2297 |         fetchAuditTrail();
2298 |         // For initial load/prop update, pass newDetails.testParameters as the resultsDataSource
2299 |         await fetchAllTestParameters(newDetails.tests, overrideResultsDataSource.value || newDetails.testParameters);
2300 |         // Load active formulas (if any) and run an initial recalculation pass
2301 |         try {
2302 |           await fetchFormulas();
2303 |           recalculateFormulasForSourceParameter();
2304 |         } catch (e) {
2305 |           console.warn('Initial formula fetch/recalc failed:', e);
2306 |         }
2307 |         
2308 |         // IMPORTANT: Preserve result_ids for parameters that have values and are already saved
2309 |         console.log("Checking parameters with values to determine if they're already saved...");
2310 |         
2311 |         // Get all parameters with values
2312 |         let allParams = Object.values(testParameters.value).flat();
2313 |         let paramsWithValues = allParams.filter(param => 
2314 |           param && param.resultValue && param.resultValue.trim() !== ''
2315 |         );
2316 |         
2317 |         // Check if all parameters with values have result_ids (meaning they're already saved)
2318 |         let allSaved = paramsWithValues.every(param => param.result_id !== null);
2319 |         let anyUnsaved = paramsWithValues.some(param => param.result_id === null);
2320 |         
2321 |         console.log(`Parameter save status: 
2322 |           - Parameters with values: ${paramsWithValues.length}
2323 |           - All saved: ${allSaved}
2324 |           - Any unsaved: ${anyUnsaved}
2325 |         `);
2326 |         
2327 |         // Run detailed debug
2328 |         debugParameterStates();
2329 |         
2330 |         // After parameters are loaded, convert any existing values to conventional
2331 |         convertAllSIValues();
2332 |         
2333 |         // Force button state update
2334 |         forceButtonUpdate();
2335 |       } else {
2336 |         console.log("Watcher: Tests array is empty, skipping parameter fetch.");
2337 |         testParameters.value = {};
2338 |         parametersLoading.value = false;
2339 |       }
2340 |     } else {
2341 |       if (!newDetails) {
2342 |         console.log("Watcher: newDetails is null or undefined.");
2343 |       } else if (!newDetails.tests) {
2344 |         console.log("Watcher: newDetails.tests is null or undefined.");
2345 |       } else if (!Array.isArray(newDetails.tests)) {
2346 |         console.log("Watcher: newDetails.tests is not an array. Type:", typeof newDetails.tests, "Value:", newDetails.tests);
2347 |       }
2348 | 
2349 |       resultFields.value = [];
2350 |       localRemarks.value = 'RESULTS VERIFIED';
2351 |       localComments.value = '';
2352 |       localSignatories.value = { performedBy: null, verifiedBy: null, pathologist: null };
2353 |       testParameters.value = {};
2354 |       parametersLoading.value = false;
2355 |     }
2356 |   } catch (error) {
2357 |     console.error("Error in requestDetails watcher:", error);
2358 |     // Handle errors but don't crash
2359 |   }
2360 | }, { immediate: true, deep: true });
2361 | 
2362 | // Add try/catch to the second watch function
2363 | watch(() => props.requestDetails, (newDetails) => {
2364 |   try {
2365 |     if (newDetails && newDetails.tests && Array.isArray(newDetails.tests) && newDetails.tests.length > 0) {
2366 |       // Log the structure of the test data to help debug
2367 |       console.log('First test from request details:', newDetails.tests[0]);
2368 |       
2369 |       // Get the category ID from the first test
2370 |       const firstTest = newDetails.tests[0];
2371 |       
2372 |       // Try different possible paths to find the test_category_id
2373 |       let categoryId = null;
2374 |       
2375 |       if (firstTest.test_category_id) {
2376 |         categoryId = firstTest.test_category_id;
2377 |         testCategoryId.value = categoryId;
2378 |         console.log(`Found category ID directly on test: ${categoryId}`);
2379 |       } else if (firstTest.testCategory && firstTest.testCategory.id) {
2380 |         categoryId = firstTest.testCategory.id;
2381 |         testCategoryId.value = categoryId;
2382 |         console.log(`Found category ID via testCategory relation: ${categoryId}`);
2383 |       } else if (props.categoryId) {
2384 |         // Use categoryId prop as fallback
2385 |         categoryId = props.categoryId;
2386 |         testCategoryId.value = categoryId;
2387 |         console.log(`Using category ID from prop: ${categoryId}`);
2388 |       } else {
2389 |         // In production hematology service, we could hardcode a known ID from your database
2390 |         testCategoryId.value = null;
2391 |         console.log('Could not find test category ID, loading all equipment');
2392 |       }
2393 |       
2394 |       if (categoryId) {
2395 |         loadAnalyzers(categoryId);
2396 |       } else {
2397 |         // Fallback to loading all equipment
2398 |         loadAnalyzers();
2399 |       }
2400 |     } else {
2401 |       console.log('No tests found in request details, loading all equipment');
2402 |       loadAnalyzers();
2403 |     }
2404 |   } catch (error) {
2405 |     console.error("Error in equipment loading watcher:", error);
2406 |     // Try to load all equipment as a fallback
2407 |     loadAnalyzers();
2408 |   }
2409 | }, { immediate: true });
2410 | 
2411 | // --- Patient Age Calculation ---
2412 | // Keep QC banner in sync with data changes
2413 | watch(testParameters, () => updateQCHoldBanner(), { deep: true });
2414 | watch(testCategoryId, () => updateQCHoldBanner());
2415 | 
2416 | // --- Patient Age Calculation ---
2417 | const calculatePatientAge = (dob) => {
2418 |   if (!dob) return { years: null, months: null, days: null, monthsTotal: null, daysTotal: null };
2419 |   try {
2420 |     const birthDate = new Date(dob);
2421 |     const today = new Date();
2422 |     const years = differenceInYears(today, birthDate);
2423 |     const monthsTotal = differenceInMonths(today, birthDate);
2424 |     const daysTotal = differenceInDays(today, birthDate);
2425 |     let months = today.getMonth() - birthDate.getMonth();
2426 |     let days = today.getDate() - birthDate.getDate();
2427 |     if (months < 0 || (months === 0 && days < 0)) {
2428 |     }
2429 |     if (days < 0) {
2430 |       months--;
2431 |       const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
2432 |       days += lastMonth.getDate();
2433 |     }
2434 |      if (months < 0) {
2435 |        months += 12;
2436 |      }
2437 | 
2438 |     return { years, months, days, monthsTotal, daysTotal };
2439 |   } catch (e) {
2440 |     console.error("Error calculating age:", e);
2441 |     return { years: null, months: null, days: null, monthsTotal: null, daysTotal: null };
2442 |   }
2443 | };
2444 | 
2445 | const patientAge = computed(() => calculatePatientAge(props.requestDetails?.patient?.date_of_birth));
2446 | const patientGender = computed(() => props.requestDetails?.patient?.gender || 'Any');
2447 | 
2448 | // --- Helper to calculate Conventional Value ---
2449 | const calculateConventionalValue = (siValue, factor) => {
2450 |   if (siValue === null || siValue === '' || factor === null || factor === undefined) {
2451 |     return ''; // Cannot calculate if SI value or factor is missing
2452 |   }
2453 |   const si = parseFloat(siValue);
2454 |   const f = parseFloat(factor);
2455 |   if (isNaN(si) || isNaN(f)) {
2456 |     return 'ERR'; // Error if values are not numeric
2457 |   }
2458 |   // Perform calculation and round to a reasonable number of decimal places
2459 |   const conventional = si * f;
2460 |   // Basic rounding - adjust precision as needed
2461 |   return Number(conventional.toFixed(2)).toString();
2462 | };
2463 | 
2464 | // --- Helper function for flag CSS class (needed in template) ---
2465 | const getFlagClass = (flag) => {
2466 |     if (!flag) return '';
2467 |     const f = flag.toString();
2468 |     if (f === 'L' || f === 'H') return 'flag-critical';
2469 |     if (f === 'l' || f === 'h') return 'flag-abnormal';
2470 |     if (f === 'ERR') return 'flag-error';
2471 |     return '';
2472 | };
2473 | 
2474 | // --- Helper for individual flag character styling ---
2475 | const getFlagCharClass = (flag) => {
2476 |     if (!flag) return '';
2477 |     const f = flag.toString();
2478 |     if (f === 'L') return 'flag-low-critical';
2479 |     if (f === 'H') return 'flag-high-critical';
2480 |     if (f === 'l') return 'flag-low-abnormal';
2481 |     if (f === 'h') return 'flag-high-abnormal';
2482 |     return '';
2483 | };
2484 | 
2485 | // Computed properties for violations summary modal
2486 | const criticalValues = computed(() => {
2487 |   const criticals = [];
2488 |   const allParams = Object.values(testParameters.value).flat();
2489 |   const currentPatientAge = calculatePatientAge(props.requestDetails?.patient?.date_of_birth);
2490 |   const currentPatientGender = props.requestDetails?.patient?.gender || 'Any';
2491 |   
2492 |   allParams.forEach(param => {
2493 |     if (param.flag === 'L' || param.flag === 'H') {
2494 |       // Find applicable reference range using same logic as formattedTestResults
2495 |       let applicableRange = null;
2496 |       
2497 |       try {
2498 |         if (param.referenceRanges && param.referenceRanges.length > 0) {
2499 |           // First try to match by analyzer if available
2500 |           if (param.instrument) {
2501 |             const equipmentMatches = param.referenceRanges.filter(range => 
2502 |               range && ((range.equipment_id && range.equipment_id === param.instrument) || 
2503 |               (range.equipment && range.equipment === param.instrument))
2504 |             );
2505 |             
2506 |             if (equipmentMatches.length > 0) {
2507 |               applicableRange = equipmentMatches.find(range => {
2508 |                 if (!range || range.age_min === undefined || range.age_max === undefined) return false;
2509 |                 const ageMatch = currentPatientAge.years >= range.age_min && 
2510 |                                  currentPatientAge.years <= range.age_max;
2511 |                 const genderMatch = !range.gender || range.gender === 'Any' || 
2512 |                                    range.gender === currentPatientGender;
2513 |                 return ageMatch && genderMatch;
2514 |               });
2515 |               
2516 |               if (!applicableRange) {
2517 |                 applicableRange = equipmentMatches[0];
2518 |               }
2519 |             }
2520 |           }
2521 |           
2522 |           // If no equipment match, try just age/gender match
2523 |           if (!applicableRange) {
2524 |             applicableRange = param.referenceRanges.find(range => {
2525 |               if (!range || range.age_min === undefined || range.age_max === undefined) return false;
2526 |               const ageMatch = currentPatientAge.years >= range.age_min && 
2527 |                              currentPatientAge.years <= range.age_max;
2528 |               const genderMatch = !range.gender || range.gender === 'Any' || 
2529 |                                 range.gender === currentPatientGender;
2530 |               return ageMatch && genderMatch;
2531 |             });
2532 |           }
2533 |           
2534 |           // If still no match, use the first range
2535 |           if (!applicableRange && param.referenceRanges.length > 0) {
2536 |             applicableRange = param.referenceRanges[0];
2537 |           }
2538 |         }
2539 |       } catch (error) {
2540 |         console.error('Error finding applicable range for critical value:', error);
2541 |       }
2542 |       
2543 |       // Determine reference range string
2544 |       let refRangeStr = 'N/A';
2545 |       if (applicableRange) {
2546 |         refRangeStr = applicableRange.si_range || 
2547 |                      ((applicableRange.low_value !== null && applicableRange.high_value !== null) ?
2548 |                       `${applicableRange.low_value} - ${applicableRange.high_value}` : 'N/A');
2549 |       } else if (param.referenceRange && param.referenceRange !== 'N/A' && param.referenceRange !== 'Not defined') {
2550 |         refRangeStr = param.referenceRange;
2551 |       }
2552 |       
2553 |       const flagText = param.flag === 'L' ? 'Below critical limit' : 'Above critical limit';
2554 |       criticals.push({
2555 |         parameter: param.name || param.test_name || 'Parameter',
2556 |         value: param.resultValue || '',
2557 |         unit: param.unit || '',
2558 |         flag: param.flag,
2559 |         refRange: refRangeStr,
2560 |         referenceRange: refRangeStr,
2561 |         message: `${flagText} (Ref: ${refRangeStr})`,
2562 |         parameter_id: param.id || param.parameterId || param.test_parameter_id || null,
2563 |         test_result_id: param.result_id ?? null
2564 |       });
2565 |     }
2566 |   });
2567 |   
2568 |   return criticals;
2569 | });
2570 | 
2571 | // Helper function to get parameter name from delta check
2572 | const getDeltaCheckParameterName = (dc) => {
2573 |   // Try multiple paths to get the parameter name
2574 |   let paramName = dc.test_parameter?.name || 
2575 |                   dc.test_parameter?.test_name ||
2576 |                   dc.test?.name ||
2577 |                   (dc.test_result?.testParameter ? (dc.test_result.testParameter.name || dc.test_result.testParameter.test_name) : null);
2578 |   
2579 |   // If still not found, try to match from testParameters by test_parameter_id
2580 |   if (!paramName || paramName === 'Parameter') {
2581 |     const testParamId = dc.test_parameter?.id || 
2582 |                         dc.test_result?.test_parameter_id ||
2583 |                         dc.test_parameter_id;
2584 |     
2585 |     if (testParamId) {
2586 |       const allParams = Object.values(testParameters.value).flat();
2587 |       const matchedParam = allParams.find(p => p.id === testParamId || p.parameterId === testParamId);
2588 |       if (matchedParam) {
2589 |         paramName = matchedParam.name || matchedParam.test_name || 'Parameter';
2590 |       }
2591 |     }
2592 |   }
2593 |   
2594 |   return paramName || 'Parameter';
2595 | };
2596 | 
2597 | // Helper function to get unit from delta check
2598 | const getDeltaCheckUnit = (dc) => {
2599 |   let unit = dc.test_parameter?.unit || 
2600 |              (dc.test_result?.testParameter ? dc.test_result.testParameter.unit : null);
2601 |   
2602 |   // If still not found, try to match from testParameters
2603 |   if (!unit) {
2604 |     const testParamId = dc.test_parameter?.id || 
2605 |                         dc.test_result?.test_parameter_id ||
2606 |                         dc.test_parameter_id;
2607 |     
2608 |     if (testParamId) {
2609 |       const allParams = Object.values(testParameters.value).flat();
2610 |       const matchedParam = allParams.find(p => p.id === testParamId || p.parameterId === testParamId);
2611 |       if (matchedParam) {
2612 |         unit = matchedParam.unit || '';
2613 |       }
2614 |     }
2615 |   }
2616 |   
2617 |   return unit || '';
2618 | };
2619 | 
2620 | const totalViolations = computed(() => {
2621 |   return criticalValues.value.length + 
2622 |          flaggedDeltaChecks.value.length + 
2623 |          cdsAlerts.value.length + 
2624 |          (qcHoldBannerMessage.value ? 1 : 0);
2625 | });
2626 | 
2627 | const hasAnyViolation = computed(() => totalViolations.value > 0);
2628 | 
2629 | const instrumentFlags = computed(() => {
2630 |   const flags = [];
2631 |   // Check for HIL indices or other instrument flags
2632 |   // This is a placeholder - you may need to add actual instrument flag detection logic
2633 |   return flags;
2634 | });
2635 | 
2636 | const hasInstrumentFlags = computed(() => {
2637 |   return instrumentFlags.value.length > 0;
2638 | });
2639 | 
2640 | // Export violations to sheets
2641 | const exportViolationsToSheets = () => {
2642 |   const data = [];
2643 |   
2644 |   // QC & Instrument Status
2645 |   if (qcHoldBannerMessage.value || hasInstrumentFlags.value) {
2646 |     data.push(['QC & Instrument Status']);
2647 |     if (qcHoldBannerMessage.value) {
2648 |       data.push(['Failed QC Run', qcHoldBannerMessage.value]);
2649 |     }
2650 |     instrumentFlags.value.forEach(flag => {
2651 |       data.push(['Instrument Flag', flag]);
2652 |     });
2653 |     data.push([]);
2654 |   }
2655 |   
2656 |   // Critical Values
2657 |   if (criticalValues.value.length > 0) {
2658 |     data.push(['Critical Values']);
2659 |     criticalValues.value.forEach(cv => {
2660 |       data.push([cv.parameter, `${cv.value} ${cv.unit}`, cv.message]);
2661 |     });
2662 |     data.push([]);
2663 |   }
2664 |   
2665 |   // Delta Checks
2666 |   if (flaggedDeltaChecks.value.length > 0) {
2667 |     data.push(['Delta Checks']);
2668 |     flaggedDeltaChecks.value.forEach(dc => {
2669 |       const paramName = dc.test_parameter?.name || 'Parameter';
2670 |       const change = dc.percentage_difference ? Math.abs(dc.percentage_difference).toFixed(0) + '%' : 'N/A';
2671 |       data.push([paramName, `${dc.previous_value} → ${dc.current_value}`, `Change: ${change}`]);
2672 |     });
2673 |     data.push([]);
2674 |   }
2675 |   
2676 |   // CDS Rules
2677 |   if (cdsAlerts.value.length > 0) {
2678 |     data.push(['CDS Rules']);
2679 |     cdsAlerts.value.forEach(alert => {
2680 |       data.push([alert.rule_name, alert.alert_message]);
2681 |     });
2682 |   }
2683 |   
2684 |   // Convert to CSV
2685 |   const csv = data.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
2686 |   const blob = new Blob([csv], { type: 'text/csv' });
2687 |   const url = window.URL.createObjectURL(blob);
2688 |   const a = document.createElement('a');
2689 |   a.href = url;
2690 |   a.download = `violations_summary_${props.requestDetails?.id || 'unknown'}_${new Date().toISOString().split('T')[0]}.csv`;
2691 |   document.body.appendChild(a);
2692 |   a.click();
2693 |   document.body.removeChild(a);
2694 |   window.URL.revokeObjectURL(url);
2695 |   
2696 |   ElMessage.success('Violations summary exported successfully');
2697 | };
2698 | 
2699 | // Handle acknowledgment of violations
2700 | const handleAcknowledgeViolations = async (modalPayload) => {
2701 |   if (props.requestDetails?.id) {
2702 |     try {
2703 |       const payload = {
2704 |         ...modalPayload
2705 |       };
2706 |       
2707 |       if (cdsAlerts.value && cdsAlerts.value.length > 0) {
2708 |         payload.cds_alerts = cdsAlerts.value;
2709 |       }
2710 |       if (flaggedDeltaChecks.value && flaggedDeltaChecks.value.length > 0 && !deltaAcknowledgeData.value) {
2711 |         payload.delta_checks = flaggedDeltaChecks.value.map(dc => ({
2712 |           test_result_id: dc.test_result?.id,
2713 |           test_parameter_id: dc.test_parameter?.id,
2714 |           message: `Delta check violation for ${dc.test_parameter?.name || 'Parameter'}: ${dc.previous_value} -> ${dc.current_value}`
2715 |         }));
2716 |       }
2717 |       if (criticalValues.value && criticalValues.value.length > 0) {
2718 |         payload.critical_values = criticalValues.value.map(cv => ({
2719 |           parameter_id: cv.parameter_id,
2720 |           message: cv.message
2721 |         }));
2722 |       }
2723 | 
2724 |       await api.post(`/laboratory-requests/${props.requestDetails.id}/acknowledge-violations`, payload);
2725 |       // Refresh audit trail after acknowledgment (forcing refresh)
2726 |       await fetchAuditTrail(true);
2727 |     } catch (error) {
2728 |       ElMessage.error(error.response?.data?.message || 'Failed to record acknowledgment');
2729 |       return;
2730 |     }
2731 |   }
2732 | 
2733 |   showViolationsModal.value = false;
2734 |   ElMessage.success('Violations acknowledged. You may now proceed with review actions.');
2735 | 
2736 |   if (pendingPostAckAction.value === 'print') {
2737 |     pendingPostAckAction.value = null;
2738 |     handlePrintRelease();
2739 |   } else if (pendingPostAckAction.value === 'validate') {
2740 |     pendingPostAckAction.value = null;
2741 |     handleValidateRelease();
2742 |   } else if (pendingPostAckAction.value === 'reject') {
2743 |     pendingPostAckAction.value = null;
2744 |     showRejectModal.value = true;
2745 |     rejectForm.value.reviewer_id = currentUser.value?.id || rejectForm.value.reviewer_id;
2746 |     rejectForm.value.reviewed_at = nowTimestamp();
2747 |   }
2748 | };
2749 | 
2750 | // Helper function to generate table rows for printing
2751 | const generatePrintTableRows = () => {
2752 |     return formattedTestResults.value.map(result => {
2753 |         if (!result.isParameter) return '';
2754 |         return `
2755 |             <tr>
2756 |                 <td>${result.parameterName}</td>
2757 |                 <td>${result.resultValue}</td>
2758 |                 <td>${result.unit}</td>
2759 |                 <td>${result.referenceRange}</td>
2760 |                 <td class="${getFlagClass(result.flag)}">${result.flag || ''}</td>
2761 |             </tr>
2762 |         `;
2763 |     }).join('');
2764 | };
2765 | 
2766 | // Helper function to get performer name
2767 | const getPerformerName = () => {
2768 |     return loadedPerformers.value.find(p => p.id === localSignatories.value.performedBy)?.name || 'N/A';
2769 | };
2770 | 
2771 | // Helper function to get pathologist name
2772 | const getPathologistName = () => {
2773 |     return loadedPathologists.value.find(p => p.id === localSignatories.value.pathologist)?.name || 'N/A';
2774 | };
2775 | 
2776 | // Computed property to identify test IDs that triggered CDS rules
2777 | const triggeredCdsTestIds = computed(() => {
2778 |   return new Set(cdsAlerts.value.map(alert => alert.trigger_test_id).filter(id => !!id));
2779 | });
2780 | 
2781 | // Row class name for the result table
2782 | const getModalRowClassName = ({ row }) => {
2783 |   const classes = [];
2784 |   if (row.isParameter) {
2785 |     if (triggeredCdsTestIds.value.has(row.testId)) {
2786 |       classes.push('row-cds-triggered');
2787 |     }
2788 |     
2789 |     // Add status-based classes
2790 |     const flag = row.flag?.toString();
2791 |     if (flag === 'L' || flag === 'H') {
2792 |       classes.push('row-critical');
2793 |     } else if (flag === 'l' || flag === 'h') {
2794 |       classes.push('row-abnormal');
2795 |     }
2796 |   }
2797 |   return classes.join(' ');
2798 | };
2799 | 
2800 | // Logic to determine if a value should be bolded based on flag
2801 | const isResultValueBold = (flag) => {
2802 |   if (!flag) return false;
2803 |   return ['L', 'H'].includes(flag.toString());
2804 | };
2805 | 
2806 | // --- Computed property for table display with improved reference range handling ---
2807 | const formattedTestResults = computed(() => {
2808 |   if (!resultFields.value || resultFields.value.length === 0) return [];
2809 | 
2810 |   const currentPatientAge = calculatePatientAge(props.requestDetails?.patient?.date_of_birth);
2811 |   const currentPatientGender = props.requestDetails?.patient?.gender || 'Any';
2812 |   
2813 |   // Array to hold the formatted results for the table
2814 |   const results = [];
2815 |   const deltaChecksByResultId = new Map();
2816 |   allDeltaChecks.value.forEach(dc => {
2817 |     const resultId = dc?.test_result?.id || dc?.test_result_id;
2818 |     if (resultId !== undefined && resultId !== null) {
2819 |       deltaChecksByResultId.set(Number(resultId), dc);
2820 |     }
2821 |   });
2822 |   
2823 |   // Iterate through all tests
2824 |   resultFields.value.forEach(test => {
2825 |     if (!test || !test.id) return;
2826 |     
2827 |     // Get parameters for this test
2828 |     const params = testParameters.value[test.id] || [];
2829 |     
2830 |     console.log(`Processing parameters for test ${test.id} - ${test.testName}:`, params);
2831 |     
2832 |     if (params.length > 0) {
2833 |       // Process each parameter
2834 |       params.forEach(param => {
2835 |         if (!param || !param.id) return;
2836 |         
2837 |         // Debug log each parameter with result_comment value
2838 |         console.log(`Processing parameter ${param.id} for UI:`, {
2839 |           name: param.name,
2840 |           test_name: param.test_name,
2841 |           resultValue: param.resultValue,
2842 |           resultComment: param.resultComment // <-- Log the result_comment value
2843 |         });
2844 |         
2845 |         // Find applicable reference range
2846 |         let applicableRange = null;
2847 |         
2848 |         try {
2849 |           // Find an applicable reference range based on analyzer, age, gender
2850 |           if (param.referenceRanges && param.referenceRanges.length > 0) {
2851 |             // First try to match by analyzer if available
2852 |             if (param.instrument) {
2853 |               const equipmentMatches = param.referenceRanges.filter(range => 
2854 |                 range && ((range.equipment_id && range.equipment_id === param.instrument) || 
2855 |                 (range.equipment && range.equipment === param.instrument))
2856 |               );
2857 |               
2858 |               if (equipmentMatches.length > 0) {
2859 |                 // Try to find age/gender match within equipment matches
2860 |                 applicableRange = equipmentMatches.find(range => {
2861 |                   if (!range || range.age_min === undefined || range.age_max === undefined) return false;
2862 |                   
2863 |                   const ageMatch = currentPatientAge.years >= range.age_min && 
2864 |                                    currentPatientAge.years <= range.age_max;
2865 |                   const genderMatch = !range.gender || range.gender === 'Any' || 
2866 |                                      range.gender === currentPatientGender;
2867 |                                      
2868 |                   return ageMatch && genderMatch;
2869 |                 });
2870 |                 
2871 |                 // If no specific age/gender match, use the first equipment match
2872 |                 if (!applicableRange) {
2873 |                   applicableRange = equipmentMatches[0];
2874 |                 }
2875 |               }
2876 |             }
2877 |             
2878 |             // If no equipment match, try just age/gender match
2879 |             if (!applicableRange) {
2880 |               applicableRange = param.referenceRanges.find(range => {
2881 |                 if (!range || range.age_min === undefined || range.age_max === undefined) return false;
2882 |                 
2883 |                 const ageMatch = currentPatientAge.years >= range.age_min && 
2884 |                                currentPatientAge.years <= range.age_max;
2885 |                 const genderMatch = !range.gender || range.gender === 'Any' || 
2886 |                                   range.gender === currentPatientGender;
2887 |                                   
2888 |                 return ageMatch && genderMatch;
2889 |               });
2890 |             }
2891 |             
2892 |             // If still no match, use the first range
2893 |             if (!applicableRange && param.referenceRanges.length > 0) {
2894 |               applicableRange = param.referenceRanges[0];
2895 |             }
2896 |           }
2897 |         } catch (error) {
2898 |           console.error('Error finding applicable range:', error);
2899 |         }
2900 |         
2901 |         // Create fallback range object if none found
2902 |         if (!applicableRange) {
2903 |           applicableRange = {
2904 |             si_unit: param.unit || '',
2905 |             si_range: 'N/A',
2906 |             low_value: null,
2907 |             high_value: null,
2908 |             conv_unit: param.conventional_unit || '',
2909 |             conv_range: 'N/A',
2910 |             conv_low: null,
2911 |             conv_high: null
2912 |           };
2913 |         }
2914 |         
2915 |         // Find delta check data for this parameter
2916 |         let previousValue = undefined;
2917 |         let deltaPercent = undefined;
2918 |         const fallbackPrevious = param.previousValueRaw;
2919 |         if (fallbackPrevious !== undefined && fallbackPrevious !== null && String(fallbackPrevious).trim() !== '') {
2920 |           previousValue = fallbackPrevious;
2921 |         }
2922 |         const flaggedDeltaCheck = flaggedDeltaChecks.value.find(dc => {
2923 |           const dcParamId = dc.test_parameter?.id || 
2924 |                            dc.test_result?.test_parameter_id ||
2925 |                            dc.test_parameter_id;
2926 |           return dcParamId === param.id || dcParamId === param.parameterId;
2927 |         });
2928 |         const resultDeltaCheck = param.result_id ? deltaChecksByResultId.get(Number(param.result_id)) : null;
2929 |         const deltaCheck = flaggedDeltaCheck || resultDeltaCheck;
2930 |         
2931 |         if (deltaCheck) {
2932 |           if (deltaCheck.previous_value !== undefined && deltaCheck.previous_value !== null && String(deltaCheck.previous_value).trim() !== '') {
2933 |             previousValue = deltaCheck.previous_value;
2934 |           }
2935 |           deltaPercent = deltaCheck.percentage_difference;
2936 |         }
2937 |         // Do not alter delta check values; they remain as originally triggered
2938 |         
2939 |         // Add parameter to results array
2940 |         results.push({
2941 |           id: `test-${test.id}-param-${param.id}`,
2942 |           isParameter: true,
2943 |           isTestHeader: false,
2944 |           testId: test.id,
2945 |           parameterId: param.id,
2946 |           parameterName: getParameterName(param, test), // Use the helper function
2947 |           result_id: param.result_id || null,
2948 |           flag: param.flag || null,
2949 |           resultValue: param.resultValue || '',
2950 |           unit: (applicableRange.si_unit) || param.unit || '',
2951 |           referenceRange: applicableRange.si_range || 
2952 |                          ((applicableRange.low_value !== null && applicableRange.high_value !== null) ?
2953 |                           `${applicableRange.low_value} - ${applicableRange.high_value}` : 'N/A'),
2954 |           conventional: param.conventional || '',
2955 |           units: applicableRange.conv_unit || param.conventional_unit || '',
2956 |           refRange: applicableRange.conv_range || 
2957 |                    ((applicableRange.conv_low !== null && applicableRange.conv_high !== null) ?
2958 |                     `${applicableRange.conv_low} - ${applicableRange.conv_high}` : 'N/A'),
2959 |           instrument: param.instrument || null,
2960 |           resultComment: param.resultComment || '', // Ensure resultComment is never null or undefined
2961 |           analyzerName: param.analyzer_name || '',
2962 |           previousValue: previousValue,
2963 |           deltaPercent: deltaPercent,
2964 |           // Original Result must show the prior version within this request (R0).
2965 |           // Use the saved previous value from this request only.
2966 |           originalValue: isForRerun.value
2967 |             ? (
2968 |                 (param.previousValueRaw !== undefined && param.previousValueRaw !== null && String(param.previousValueRaw).trim() !== '')
2969 |                   ? param.previousValueRaw
2970 |                   : (
2971 |                       String(param.version || 'R0').toUpperCase() === 'R0'
2972 |                         ? (param.currentResultRaw ?? '')
2973 |                         : ''
2974 |                     )
2975 |               )
2976 |             : undefined,
2977 |           // In re-run context, surface the current version as R1 in the UI.
2978 |           versionStatus: isForRerun.value ? 'R1' : `${param.version || 'R0'}`,
2979 |           // expose auto-calculation flags for UI
2980 |           is_auto_calculated: !!param.is_auto_calculated,
2981 |           auto_formula_id: param.auto_formula_id || null
2982 |         });
2983 |       });
2984 |     } else {
2985 |       // Non-parameterized test - check if it has a result value
2986 |       if (test.initial_resultValue && test.initial_resultValue.trim() !== '') {
2987 |         // Log non-parameterized test data for debugging
2988 |         console.log(`Non-parameterized test data for ${test.id} for UI:`, {
2989 |           test_id: test.id,
2990 |           result_value: test.initial_resultValue,
2991 |           unit: test.unit,
2992 |           analyzer_id: test.initial_instrument,
2993 |           result_comment: test.initial_resultComment, // <-- Log the result_comment value
2994 |           has_result_id: !!test.result_id
2995 |         });
2996 |         
2997 |         const resultEntry = {
2998 |           id: `test-${test.id}`,
2999 |           isParameter: false,
3000 |           parameterName: test.testName || test.name || `Test ${test.id}`,
3001 |           test_id: test.id,
3002 |           remarks: localRemarks.value,  // Add remarks at the test level
3003 |           comment: localComments.value  // Add comment at the test level
3004 |         };
3005 |         
3006 |         resultEntry.result_value = test.initial_resultValue.trim();
3007 |         resultEntry.unit = test.unit ? test.unit.trim() : '';
3008 |         resultEntry.equipment_id = test.initial_instrument;
3009 |         resultEntry.result_comment = test.initial_resultComment || '';
3010 |         resultEntry.versionStatus = `${test.version || 'R0'}`;
3011 |         
3012 |         // Include the result_id if available to help backend identify existing records
3013 |         if (test.result_id) {
3014 |           resultEntry.result_id = test.result_id;
3015 |         }
3016 |         
3017 |         results.push(resultEntry);
3018 |       }
3019 |     }
3020 |   });
3021 |   
3022 |   console.log('Final formattedTestResults:', results);
3023 |   return results;
3024 | });
3025 | 
3026 | const handleClose = () => {
3027 |   emit('update:visible', false);
3028 |   emit('close');
3029 | };
3030 | 
3031 | const triggerSave = async () => {
3032 |     const payload = preparePayload();
3033 |     
3034 |     // If no payload (no new results to save), return early
3035 |     if (!payload) {
3036 |         console.log("No parameters to save, skipping save operation");
3037 |         ElMessage.info("No changes to save");
3038 |         return;
3039 |     }
3040 |     
3041 |     console.log("================================================================");
3042 |     console.log("FULL SAVE PAYLOAD:", JSON.stringify(payload, null, 2));
3043 |     console.log("================================================================");
3044 |     
3045 |     if (!props.requestDetails?.id) {
3046 |         ElMessage.error("Cannot save results: Request ID is missing.");
3047 |         return;
3048 |     }
3049 |     
3050 |     try {
3051 |         const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/results`, payload);
3052 |         console.log("Save POST response:", JSON.parse(JSON.stringify(response.data))); 
3053 |         ElMessage.success("Results saved successfully");
3054 | 
3055 |         hasUnsavedChanges.value = false;
3056 | 
3057 |         let latestResultsDataSource = null;
3058 | 
3059 |         if (response.data && Array.isArray(response.data.results) && response.data.results.length > 0) {
3060 |             console.log("Attempting to transform POST response results for immediate UI refresh. Full results array:", JSON.parse(JSON.stringify(response.data.results)));
3061 |             const transformedResults = {};
3062 |             response.data.results.forEach(item => {
3063 |                 // Access test_parameter_id from the nested test_parameter object
3064 |                 const testParameterId = item.test_parameter?.id;
3065 |                 console.log(`Processing item from POST response: test_id = ${item.test_id}, test_parameter.id = ${testParameterId}`, item);
3066 |                 
3067 |                 if (item.test_id == null || testParameterId == null) { 
3068 |                     console.warn("Skipping result item due to missing (null/undefined) test_id or test_parameter.id. Actual values:", { test_id: item.test_id, test_parameter_id_from_nested: testParameterId }, item);
3069 |                     return;
3070 |                 }
3071 | 
3072 |                 if (!transformedResults[item.test_id]) {
3073 |                     transformedResults[item.test_id] = [];
3074 |                 }
3075 | 
3076 |                 // Calculate conventional value for the transformed object
3077 |                 const siValue = item.result_value;
3078 |                 const conversionFactorValue = item.test_parameter?.conversion_factor;
3079 |                 let conventionalValue = ''; 
3080 | 
3081 |                 if (siValue && siValue.trim() !== '' && conversionFactorValue != null) {
3082 |                     const numericSI = parseFloat(siValue);
3083 |                     const numericFactor = parseFloat(conversionFactorValue);
3084 |                     if (!isNaN(numericSI) && !isNaN(numericFactor)) {
3085 |                         conventionalValue = (numericSI * numericFactor).toFixed(2);
3086 |                     }
3087 |                 }
3088 | 
3089 |                 const paramResultObj = {
3090 |                     id: testParameterId,             
3091 |                     result_id: item.id,             
3092 |                     resultValue: item.result_value,
3093 |                     unit: item.unit || item.test_parameter?.unit || '', 
3094 |                     instrument: item.equipment_id,
3095 |                     resultComment: item.result_comment || item.test_parameter?.result_comment || '',
3096 |                     flag: item.flag || null,
3097 |                     name: item.test_parameter?.name || item.test_parameter?.test_name || '',
3098 |                     test_name: item.test_parameter?.test_name || item.test_parameter?.name || '',
3099 |                     conventional_unit: item.test_parameter?.conventional_unit || '',
3100 |                     conversion_factor: conversionFactorValue, // Use the retrieved factor
3101 |                     referenceRanges: Array.isArray(item.test_parameter?.reference_ranges) ? item.test_parameter.reference_ranges : [],
3102 |                     conventional: conventionalValue, // Assign calculated conventional value
3103 |                     version: item.version || 'R0',
3104 |                     status: item.status || 'pending'
3105 |                 };
3106 |                 transformedResults[item.test_id].push(paramResultObj);
3107 |             });
3108 |             latestResultsDataSource = transformedResults;
3109 |             console.log("Transformed POST results into latestResultsDataSource:", JSON.parse(JSON.stringify(latestResultsDataSource)));
3110 |         } else {
3111 |             // Fallback to fetching from GET if POST response is not as expected, though this is our problematic path
3112 |             console.warn("POST response did not contain usable results. Falling back to GET for latest state (might be stale).");
3113 |             const latestRequest = await api.get(`/laboratory-requests/${props.requestDetails.id}`);
3114 |             console.log("Full latestRequest.data after save (fallback path):", JSON.parse(JSON.stringify(latestRequest.data)));
3115 |             if (latestRequest.data && latestRequest.data.testParameters) { // Still check for the ideal field from GET
3116 |                 latestResultsDataSource = latestRequest.data.testParameters;
3117 |                 console.log("Using latestResultsDataSource from GET (fallback path):", JSON.parse(JSON.stringify(latestResultsDataSource)));
3118 |             } else {
3119 |                 console.warn("No testParameters in GET response either (fallback path). latestResultsDataSource remains null.");
3120 |             }
3121 |         }
3122 | 
3123 |         // Always re-fetch all parameters, passing the LATEST results (ideally from POST transform) as the dataSource
3124 |         await fetchAllTestParameters(props.requestDetails.tests, latestResultsDataSource);
3125 |         convertAllSIValues(); // Explicitly call to recalculate all conventional values after data refresh
3126 |         forceButtonUpdate();
3127 | 
3128 |         // Optionally, verify button states after save
3129 |         nextTick(() => {
3130 |             // Count parameters with values
3131 |             const allParams = Object.values(testParameters.value).flat();
3132 |             const paramsWithValues = allParams.filter(param => 
3133 |                 param && param.resultValue && param.resultValue.trim() !== ''
3134 |             );
3135 |             const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
3136 |             
3137 |             console.log(`Button state verification after save:\n    - Parameters with values: ${paramsWithValues.length}\n    - Unsaved parameters: ${unsavedParams.length}\n    - Save button should be disabled: ${unsavedParams.length === 0}\n    - Print/Validate buttons should be enabled: ${unsavedParams.length === 0}`);
3138 |         });
3139 | 
3140 |         // Update the status in the laboratory request (this updates the list view)
3141 |         api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
3142 |             status: 'Processing Results',
3143 |             test_category_id: testCategoryId.value  // Add this parameter
3144 |         }).then(() => {
3145 |             console.log("Updated laboratory request status to Processing Results");
3146 |         }).catch(error => {
3147 |             console.error("Error updating laboratory request status:", error);
3148 |         });
3149 | 
3150 |         // Emit save event to parent
3151 |         emit('save', props.requestDetails.id, payload);
3152 |         
3153 |         // Check CDS rules after saving results
3154 |         await checkCdsRules();
3155 |     } catch (error) {
3156 |         console.error("Error saving results:", error);
3157 |         ElMessage.error(`Failed to save results: ${error.response?.data?.message || error.message}`);
3158 |     }
3159 | };
3160 | 
3161 | const handleValidateRelease = async () => {
3162 |   if (isFullyRejected.value) {
3163 |     ElMessage.warning('This request is fully rejected and read-only.');
3164 |     return;
3165 |   }
3166 |   if (hasUnacknowledgedAlerts.value) {
3167 |     pendingPostAckAction.value = 'validate';
3168 |     showViolationsModal.value = true;
3169 |     activeViolationTab.value = 'summary';
3170 |     return;
3171 |   }
3172 | 
3173 |   // Check button state first
3174 |   if (disableValidateButton.value) {
3175 |       // Get specific reason for disabling
3176 |       const allTestParams = Object.values(testParameters.value).flat();
3177 |       const allSaved = allTestParams.every(param => param.result_id !== null);
3178 |       const allReleased = props.requestDetails.tests.every(test => 
3179 |         test.pivot && test.pivot.item_status === 'Result Printed'
3180 |       );
3181 |       
3182 |       if (allReleased) {
3183 |           ElMessage.warning("Tests are already in Released status and cannot be validated again.");
3184 |       } else {
3185 |           ElMessage.warning("Please save all results before validating and releasing");
3186 |       }
3187 |       return;
3188 |   }
3189 | 
3190 |   // Check CDS rules before validation (forcing fresh rules)
3191 |   await checkCdsRules(true);
3192 |   
3193 |   // Refresh audit trail (forcing refresh to ensure we have the latest acknowledgment)
3194 |   await fetchAuditTrail(true);
3195 |   
3196 |   if (hasUnacknowledgedAlerts.value) {
3197 |     pendingPostAckAction.value = 'validate';
3198 |     showViolationsModal.value = true;
3199 |     activeViolationTab.value = 'summary';
3200 |     return;
3201 |   }
3202 | 
3203 |   // The backend will handle the blocking logic, so we proceed directly to the API call.
3204 |   // This avoids race conditions where the frontend state is updated before the backend transaction is complete.
3205 |   const proceedValidate = () => {
3206 |       const payload = {
3207 |           ...preparePayload(),
3208 |           status: 'validated'
3209 |       };
3210 | 
3211 |       console.log("Prepared Validate Payload:", payload);
3212 | 
3213 |       if (!props.requestDetails?.id) {
3214 |           ElMessage.error("Cannot validate results: Request ID is missing.");
3215 |           return;
3216 |       }
3217 | 
3218 |       // Call the API to validate and save the results
3219 |       api.put(`/laboratory-requests/${props.requestDetails.id}/results`, payload)
3220 |           .then(response => {
3221 |               console.log("Validate response:", response.data);
3222 |               
3223 |               // Update the laboratory_request_test status to 'Validated'
3224 |               return api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
3225 |                   status: 'Result Validated',
3226 |                   test_category_id: testCategoryId.value  // Add this parameter
3227 |               });
3228 |           })
3229 |           .then(() => {
3230 |               ElMessage.success("Results validated successfully");
3231 |               
3232 |               // Refresh test statuses to ensure UI is up-to-date
3233 |               refreshTestStatuses();
3234 |               
3235 |               // Check CDS rules after validation
3236 |               checkCdsRules();
3237 |               
3238 |               // Emit an event to notify the parent component
3239 |               emit('validate', props.requestDetails.id, payload);
3240 | 
3241 |               handleClose();
3242 |           })
3243 |           .catch(error => {
3244 |               console.error("Error validating results:", error);
3245 |               const status = error.response?.status;
3246 |               const data = error.response?.data || {};
3247 |               if (status === 409 && data.error_type === 'cds_block') {
3248 |                   ElMessageBox.alert(
3249 |                       data.message || 'CDS rule triggered. Review required.',
3250 |                       'CDS Validation Blocked',
3251 |                       { type: 'warning', showClose: false }
3252 |                   );
3253 |                   // Do not close the modal or clear data, just show the error
3254 |               } else if (status === 403 && data.error_type === 'qc_lockout') {
3255 |                   const issues = data.issues || [];
3256 |                   // Build equipment-specific message
3257 |                   const lines = issues.map(i => {
3258 |                       const equipment = i.equipment_name || `Equipment #${i.equipment_id}`;
3259 |                       const param = i.test_parameter_name || `Parameter #${i.test_parameter_id}`;
3260 |                       const vio = Array.isArray(i.violations) ? i.violations.join(', ') : i.violations;
3261 |                       return `${equipment} → ${param}: ${vio}`;
3262 |                     });
3263 |                   ElMessageBox.alert(
3264 |                       `QC HOLD: Some results run on a failing analyzer are unreliable.\n\n` +
3265 |                       lines.join('\n'),
3266 |                       'QC Lockout Active',
3267 |                       { type: 'error', showClose: false }
3268 |                   );
3269 |                   // Do not close the modal or clear data
3270 |               } else {
3271 |                   ElMessage.error(`Failed to validate results: ${data.message || error.message}`);
3272 |               }
3273 |           });
3274 |   };
3275 | 
3276 |   // Pre-check analyzer QC hold before hitting backend
3277 |   try {
3278 |       const involvedEquipmentIds = Array.from(new Set(
3279 |           Object.values(testParameters.value)
3280 |               .flat()
3281 |               .map(p => p.equipment_id)
3282 |               .filter(id => id != null)
3283 |       ));
3284 |       if (involvedEquipmentIds.length > 0 && testCategoryId.value) {
3285 |           api.get(`/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId.value } })
3286 |               .then(({ data }) => {
3287 |                   const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
3288 |                   if (failing.length > 0) {
3289 |                       const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
3290 |                       ElMessageBox.alert(
3291 |                           `QC HOLD: Equipment QC is out of control for this request.\n\n` +
3292 |                           lines.join('\n'),
3293 |                           'QC Lockout Active',
3294 |                           { type: 'error', showClose: false }
3295 |                       );
3296 |                       return; // Do not proceed
3297 |                   }
3298 |                   proceedValidate();
3299 |               })
3300 |               .catch(() => proceedValidate());
3301 |           return; // Defer
3302 |       }
3303 |   } catch (_) {}
3304 | 
3305 |   proceedValidate();
3306 |   // After action attempts, refresh banner in case backend changed state
3307 |   updateQCHoldBanner();
3308 | 
3309 | };
3310 | 
3311 | // Handle Accept Result (for flagged delta checks)
3312 | const handleAcceptResult = () => {
3313 |   if (isFullyRejected.value) {
3314 |     ElMessage.warning('This request is fully rejected and read-only.');
3315 |     return;
3316 |   }
3317 |   if (!hasUnacknowledgedAlerts.value) {
3318 |     ElMessage.warning('No unacknowledged violations or flagged results to review.');
3319 |     return;
3320 |   }
3321 |   activeViolationTab.value = 'investigation';
3322 |   showViolationsModal.value = true;
3323 | };
3324 | 
3325 | 
3326 | // Submission logic moved to sub-modal components
3327 | 
3328 | // Submit Re-run action
3329 | // Re-run modal handling is now encapsulated in RerunModal component
3330 | 
3331 | const handlePrintRelease = async () => {
3332 |   if (isFullyRejected.value) {
3333 |     ElMessage.warning('This request is fully rejected and read-only.');
3334 |     return;
3335 |   }
3336 |   if (hasUnacknowledgedAlerts.value) {
3337 |     pendingPostAckAction.value = 'print';
3338 |     showViolationsModal.value = true;
3339 |     activeViolationTab.value = 'summary';
3340 |     return;
3341 |   }
3342 | 
3343 |   // Check button state first
3344 |   if (disablePrintButton.value) {
3345 |       ElMessage.warning("Please save all results before printing and releasing");
3346 |       return;
3347 |   }
3348 | 
3349 |   if (!props.requestDetails?.id) {
3350 |       ElMessage.error("Cannot print results: Request ID is missing.");
3351 |       return;
3352 |   }
3353 | 
3354 |   // Open template print dialog with all necessary data
3355 |   showTemplatePrintDialog.value = true;
3356 | };
3357 | 
3358 | const handlePrintComplete = (data) => {
3359 |   // Update test statuses to "Result Printed" in real-time
3360 |   console.log('Print completed, updating test statuses:', data);
3361 |   
3362 |   if (props.requestDetails?.tests && Array.isArray(props.requestDetails.tests)) {
3363 |     props.requestDetails.tests.forEach(test => {
3364 |       if (test.pivot) {
3365 |         test.pivot.item_status = 'Result Printed';
3366 |       }
3367 |     });
3368 |     console.log('Test statuses updated to Result Released');
3369 |     
3370 |     // Force Vue reactivity
3371 |     props.requestDetails.tests = [...props.requestDetails.tests];
3372 |   }
3373 | 
3374 |   if (props.requestDetails?.id) {
3375 |     emit('print', props.requestDetails.id, data);
3376 |   }
3377 |   
3378 |   // Close the print dialog
3379 |   showTemplatePrintDialog.value = false;
3380 | };
3381 | 
3382 | const handlePreview = () => {
3383 |   console.log('Preview triggered');
3384 |   ElMessage.info('Preview function not yet implemented.');
3385 | };
3386 | 
3387 | const updateResultValue = async (row, value) => {
3388 |   if (!row.isParameter || !row.testId || !row.parameterId) return;
3389 |   
3390 |   // Find the parameter in testParameters and update it directly
3391 |   const testParams = testParameters.value[row.testId];
3392 |   if (!testParams) return;
3393 |   
3394 |   const paramToUpdate = testParams.find(p => p.id === row.parameterId);
3395 |   if (!paramToUpdate) return;
3396 |   
3397 |   hasUnsavedChanges.value = true;
3398 | 
3399 |   paramToUpdate.resultValue = row.resultValue;
3400 |   const hasNewValue = paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '';
3401 |   
3402 |   if (hasNewValue) {
3403 |     paramToUpdate.result_id = null; // Mark as needing to be saved
3404 |   }
3405 |   // If value is cleared, also mark as unsaved
3406 |   if (!hasNewValue) {
3407 |     paramToUpdate.result_id = null;
3408 |   }
3409 |   // Force Vue reactivity by replacing the array reference
3410 |   testParameters.value[row.testId] = [...testParameters.value[row.testId]];
3411 |   forceButtonUpdate();
3412 |   
3413 |   // Process only if we have a value
3414 |   if (paramToUpdate.resultValue && !isNaN(parseFloat(paramToUpdate.resultValue))) {
3415 |     const numericValue = parseFloat(paramToUpdate.resultValue);
3416 |     
3417 |     // Calculate conventional value locally using conversion_factor
3418 |     if (paramToUpdate.conversion_factor && !isNaN(parseFloat(paramToUpdate.conversion_factor))) {
3419 |       const conversionFactor = parseFloat(paramToUpdate.conversion_factor);
3420 |       paramToUpdate.conventional = (numericValue * conversionFactor).toFixed(2);
3421 |       console.log(`Calculated conventional value: ${paramToUpdate.conventional} using factor: ${conversionFactor}`);
3422 |     } else {
3423 |       console.log('No conversion factor available for parameter:', {
3424 |         id: paramToUpdate.id,
3425 |         name: paramToUpdate.name,
3426 |         conversion_factor: paramToUpdate.conversion_factor
3427 |       });
3428 |       paramToUpdate.conventional = '';
3429 |     }
3430 |     
3431 |     // Call API only for flag calculation
3432 |     api.post('/results/calculate-derived', {
3433 |       result: numericValue,
3434 |       unit: paramToUpdate.unit || row.unit,
3435 |       test_parameter_id: row.parameterId, 
3436 |       patient_id: props.requestDetails?.patient?.id,
3437 |       equipment_id: paramToUpdate.instrument || row.instrument || null
3438 |     }).then(response => {
3439 |       // Only update flag from API response
3440 |       paramToUpdate.flag = response.data.flag;
3441 |       console.log('Updated flag after API call:', paramToUpdate.flag);
3442 |       // Force Vue reactivity by replacing the array reference
3443 |       testParameters.value[row.testId] = [...testParameters.value[row.testId]];
3444 |       // Force button state update after flag calculation (in case of async changes)
3445 |       forceButtonUpdate();
3446 |     }).catch(error => {
3447 |       console.error('API Error:', error);
3448 |       paramToUpdate.flag = 'ERR';
3449 |       testParameters.value[row.testId] = [...testParameters.value[row.testId]];
3450 |       forceButtonUpdate();
3451 |     });
3452 |   } else {
3453 |     // Clear values if input is empty or invalid
3454 |     paramToUpdate.flag = null;
3455 |     paramToUpdate.conventional = '';
3456 |     testParameters.value[row.testId] = [...testParameters.value[row.testId]];
3457 |     forceButtonUpdate();
3458 |   }
3459 |   try {
3460 |     recalculateFormulasForSourceParameter();
3461 |   } catch (e) {
3462 |     console.error('Recalculation after value change failed:', e);
3463 |   }
3464 | };
3465 | 
3466 | // Helper function to filter out DELTA_CHECK_HISTORY from remarks
3467 | const filterDeltaCheckHistory = (comment) => {
3468 |   if (!comment || typeof comment !== 'string') return '';
3469 |   
3470 |   // Remove DELTA_CHECK_HISTORY patterns (case-insensitive)
3471 |   // This regex matches "DELTA_CHECK_HISTORY:" followed by any text until end of line or next DELTA_CHECK_HISTORY
3472 |   let filtered = comment.replace(/DELTA_CHECK_HISTORY:.*?(?=\n|DELTA_CHECK_HISTORY:|$)/gi, '');
3473 |   
3474 |   // Clean up multiple consecutive newlines
3475 |   filtered = filtered.replace(/\n\n+/g, '\n').trim();
3476 |   
3477 |   return filtered;
3478 | };
3479 | 
3480 | // Update remarks without DELTA_CHECK_HISTORY - preserve delta history in backend but don't show in UI
3481 | const updateRemarksWithoutDeltaHistory = (row, newValue) => {
3482 |   if (!row.isParameter || !row.testId || !row.parameterId) return;
3483 |   
3484 |   const testParams = testParameters.value[row.testId];
3485 |   if (!testParams) {
3486 |     console.error(`updateRemarksWithoutDeltaHistory: No test parameters found for test ID ${row.testId}`);
3487 |     return;
3488 |   }
3489 |   
3490 |   const paramIndex = testParams.findIndex(p => p.id === row.parameterId);
3491 |   if (paramIndex === -1) {
3492 |     console.error(`updateRemarksWithoutDeltaHistory: Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
3493 |     return;
3494 |   }
3495 | 
3496 |   const paramToUpdate = testParams[paramIndex];
3497 |   
3498 |   // Get the original comment to preserve DELTA_CHECK_HISTORY
3499 |   const originalComment = paramToUpdate.resultComment || '';
3500 |   
3501 |   // Extract DELTA_CHECK_HISTORY parts from original comment
3502 |   const deltaHistoryMatches = originalComment.match(/DELTA_CHECK_HISTORY:.*?(?=\n|DELTA_CHECK_HISTORY:|$)/gi);
3503 |   const deltaHistoryText = deltaHistoryMatches ? deltaHistoryMatches.join('\n') : '';
3504 |   
3505 |   // Combine new user input with preserved delta history
3506 |   const updatedComment = deltaHistoryText 
3507 |     ? (newValue.trim() ? `${deltaHistoryText}\n${newValue}` : deltaHistoryText)
3508 |     : newValue;
3509 |   
3510 |   paramToUpdate.resultComment = updatedComment;
3511 |   row.resultComment = updatedComment; // Also update row for consistency
3512 |   
3513 |   hasUnsavedChanges.value = true;
3514 | 
3515 |   if (paramToUpdate.result_id !== null) {
3516 |     paramToUpdate.result_id = null;
3517 |   }
3518 |   
3519 |   // Create a new array for reactivity
3520 |   const newTestParamsArray = [...testParams];
3521 |   testParameters.value[row.testId] = newTestParamsArray;
3522 |   
3523 |   forceButtonUpdate();
3524 | };
3525 | 
3526 | const updateParameterComment = (row) => {
3527 |   if (!row.isParameter || !row.testId || !row.parameterId) return;
3528 |   
3529 |   const testParams = testParameters.value[row.testId];
3530 |   if (!testParams) {
3531 |     console.error(`updateParameterComment: No test parameters found for test ID ${row.testId}`);
3532 |     return;
3533 |   }
3534 |   
3535 |   const paramIndex = testParams.findIndex(p => p.id === row.parameterId);
3536 |   if (paramIndex === -1) {
3537 |     console.error(`updateParameterComment: Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
3538 |     return;
3539 |   }
3540 | 
3541 |   const paramToUpdate = testParams[paramIndex];
3542 | 
3543 |   if (paramToUpdate.resultComment !== row.resultComment) {
3544 |     paramToUpdate.resultComment = row.resultComment;
3545 |     console.log(`Synced row.resultComment to paramToUpdate.resultComment for param ${paramToUpdate.id}`);
3546 |   }
3547 | 
3548 |   hasUnsavedChanges.value = true;
3549 | 
3550 |   let markedUnsaved = false;
3551 |   if (paramToUpdate.result_id !== null) { // If it was previously saved
3552 |     paramToUpdate.result_id = null;
3553 |     markedUnsaved = true;
3554 |     console.log(`Marking parameter ${paramToUpdate.id} (testId: ${row.testId}) as unsaved due to comment change on a previously saved item.`);
3555 |   } else if (paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '' && paramToUpdate.resultComment && paramToUpdate.resultComment.trim() !== '') {
3556 |     // If it has a value, and now a comment, and was already unsaved, it remains unsaved (result_id is already null)
3557 |     // No change to result_id needed, but Save button logic will see it as an unsaved item with content.
3558 |   } else if (paramToUpdate.resultComment && paramToUpdate.resultComment.trim() !== ''){
3559 |     // If it only has a comment (no resultValue yet) and was previously no-result-id (e.g. pristine)
3560 |     paramToUpdate.result_id = null; // Stays unsaved, but this confirms it for Save button logic
3561 |     markedUnsaved = true; // Consider it a change that needs saving
3562 |     console.log(`Marking parameter ${paramToUpdate.id} (testId: ${row.testId}) as unsaved due to new comment on an item without a value/prior save.`);
3563 |   }
3564 |   
3565 |   const newTestParamsArray = [...testParams];
3566 |   testParameters.value[row.testId] = newTestParamsArray;
3567 |   
3568 |   forceButtonUpdate();
3569 |   
3570 |   console.log(`updateParameterComment: Parameter ${row.parameterId} (testId: ${row.testId}) resultComment is now: "${paramToUpdate.resultComment}". Marked unsaved: ${markedUnsaved}. Current result_id: ${paramToUpdate.result_id}`);
3571 | };
3572 | 
3573 | const overrideAutoCalculated = (row) => {
3574 |   if (!row || !row.testId || !row.parameterId) return;
3575 |   const params = testParameters.value[row.testId] || [];
3576 |   const idx = params.findIndex(p => p.id === row.parameterId);
3577 |   if (idx === -1) return;
3578 |   const param = params[idx];
3579 |   param.is_auto_calculated = false;
3580 |   param.auto_formula_id = null;
3581 |   param.result_id = null;
3582 |   hasUnsavedChanges.value = true;
3583 |   testParameters.value[row.testId] = [...testParameters.value[row.testId]];
3584 |   // After unsetting, focus the input by emitting nextTick (template will render enabled input)
3585 |   nextTick(() => {
3586 |     try {
3587 |       const selector = `input[value=\"${row.resultValue}\"]`;
3588 |       const el = document.querySelector(selector);
3589 |       if (el) el.focus();
3590 |     } catch (e) {
3591 |       // ignore focus failures
3592 |     }
3593 |   });
3594 | };
3595 | 
3596 | // Define this HEMATOLOGY_CATEGORY_ID constant - replace 1 with your actual ID from the database
3597 | const HEMATOLOGY_CATEGORY_ID = 4; // !!! REPLACE 1 with your actual Hematology Category ID !!!
3598 | 
3599 | const preparePayload = () => {
3600 |     // Build the results array format needed by the API
3601 |     const results = [];
3602 | 
3603 |     resultFields.value.forEach(test => {
3604 |         const params = testParameters.value[test.id] || [];
3605 |         const resultEntry = {
3606 |             test_id: test.id,
3607 |             remarks: localRemarks.value,  // Add remarks at the test level
3608 |             comment: localComments.value  // Add comment at the test level
3609 |         };
3610 | 
3611 |         if (params.length > 0) {
3612 |             // If the test has parameters, add them to the payload
3613 |             // Filter out parameters with missing or empty result values
3614 |             const validParams = params.filter(param => {
3615 |                 // Check if the parameter has a result value
3616 |                 return param.resultValue && param.resultValue.trim() !== '';
3617 |             });
3618 |             
3619 |             if (validParams.length > 0) {
3620 |                 resultEntry.parameters = validParams.map(param => {
3621 |                     // Explicitly get the resultComment from the parameter, defaulting to empty string
3622 |                     // This ensures we're using the correct value from the UI
3623 |                     const resultComment = typeof param.resultComment === 'string' ? param.resultComment : '';
3624 |                     
3625 |                     // Log each parameter's data for debugging
3626 |                     console.log(`Parameter data for ${param.id}:`, {
3627 |                         test_parameter_id: param.id,
3628 |                         result_value: param.resultValue,
3629 |                         unit: param.unit,
3630 |                         equipment_id: param.instrument,
3631 |                         result_comment: resultComment,
3632 |                         has_result_id: !!param.result_id
3633 |                     });
3634 |                     
3635 |                     // Make sure result_comment is included in the parameters array only
3636 |                     const parameterData = {
3637 |                         test_parameter_id: param.id,
3638 |                         result_value: param.resultValue.trim(),
3639 |                         unit: param.unit ? param.unit.trim() : '',
3640 |                         equipment_id: param.instrument,
3641 |                         result_comment: resultComment, // Always use the string version, never undefined
3642 |                         // Include the result_id if available to help backend identify existing records
3643 |                         ...(param.result_id ? { result_id: param.result_id } : {})
3644 |                     };
3645 |                     
3646 |                     return parameterData;
3647 |                 });
3648 |                 
3649 |                 // Only add the test if it has valid parameters to save
3650 |                 results.push(resultEntry);
3651 |             }
3652 |         } else {
3653 |             // Non-parameterized test - check if it has a result value
3654 |             if (test.initial_resultValue && test.initial_resultValue.trim() !== '') {
3655 |                 // Ensure result_comment is properly defined for non-parameterized tests too
3656 |                 const resultComment = typeof test.initial_resultComment === 'undefined' ? '' : test.initial_resultComment;
3657 |                 
3658 |                 // Log non-parameterized test data for debugging
3659 |                 console.log(`Non-parameterized test data for ${test.id}:`, {
3660 |                     test_id: test.id,
3661 |                     result_value: test.initial_resultValue,
3662 |                     unit: test.unit,
3663 |                     analyzer_id: test.initial_instrument,
3664 |                     result_comment: resultComment,
3665 |                     has_result_id: !!test.result_id
3666 |                 });
3667 |                 
3668 |                 // Add the non-parameterized test result
3669 |                 resultEntry.result_value = test.initial_resultValue.trim();
3670 |                 resultEntry.unit = test.unit ? test.unit.trim() : '';
3671 |                 resultEntry.equipment_id = test.initial_instrument;
3672 |                 resultEntry.result_comment = resultComment; // Use our fixed variable
3673 |                 
3674 |                 // Include the result_id if available
3675 |                 if (test.result_id) {
3676 |                     resultEntry.result_id = test.result_id;
3677 |                 }
3678 |                 
3679 |                 results.push(resultEntry);
3680 |             }
3681 |         }
3682 |     });
3683 |     
3684 |     // If there are no results, return null to prevent an empty request
3685 |     if (results.length === 0) {
3686 |         return null;
3687 |     }
3688 |     
3689 |     return {
3690 |         laboratory_request_id: props.requestDetails.id,
3691 |         active_test_category_id: testCategoryId.value, // ADD THIS LINE - the key change
3692 |         results: results,
3693 |         performer_id: localSignatories.value.performedBy,
3694 |         verifier_id: localSignatories.value.verifiedBy,
3695 |         pathologist_id: localSignatories.value.pathologist,
3696 |         status: 'saved'
3697 |     };
3698 | };
3699 | 
3700 | const handleKeyDown = (event) => {
3701 |   if (props.visible) {
3702 |     switch (event.key) {
3703 |       case 'F1':
3704 |         event.preventDefault();
3705 |         handlePreview();
3706 |         break;
3707 |       case 'F2':
3708 |         event.preventDefault();
3709 |         // Only trigger save if the button is not disabled
3710 |         if (!disableSaveButton.value) {
3711 |           triggerSave();
3712 |         } else {
3713 |           ElMessage.info('All results are already saved');
3714 |         }
3715 |         break;
3716 |       case 'F4':
3717 |         event.preventDefault();
3718 |         // Only trigger print & release if the button is not disabled
3719 |         if (!disablePrintButton.value) {
3720 |           handlePrintRelease();
3721 |         } else {
3722 |           ElMessage.warning('Please save all results before printing and releasing');
3723 |         }
3724 |         break;
3725 |       case 'F5':
3726 |         event.preventDefault();
3727 |         // Only trigger validate & release if the button is not disabled
3728 |         if (!disableValidateButton.value) {
3729 |           handleValidateRelease();
3730 |         } else {
3731 |           // Determine the specific reason for disabling
3732 |           const allTestParams = Object.values(testParameters.value).flat();
3733 |           const allSaved = allTestParams.every(param => param.result_id !== null);
3734 |           const allReleased = props.requestDetails.tests.every(test => 
3735 |             test.pivot && test.pivot.item_status === 'Result Printed'
3736 |           );
3737 |           
3738 |           if (allReleased) {
3739 |             ElMessage.warning('Tests are already in Released status and cannot be validated again');
3740 |           } else if (!allSaved) {
3741 |             ElMessage.warning('Please save all results before validating and releasing');
3742 |           }
3743 |         }
3744 |         break;
3745 |     }
3746 |   }
3747 | };
3748 | 
3749 | // Function to load equipment based on the tests in the current request
3750 | const loadAnalyzersForTests = () => {
3751 |   // If we have test results
3752 |   if (resultFields.value && resultFields.value.length > 0) {
3753 |     console.log('First test in resultFields:', resultFields.value[0]);
3754 |     
3755 |     // Try to get the test category ID from the first test
3756 |     const firstTest = resultFields.value[0];
3757 |     
3758 |     // Try different paths to find the category ID
3759 |     let categoryId = null;
3760 |     
3761 |     if (firstTest.test_category_id) {
3762 |       categoryId = firstTest.test_category_id;
3763 |       console.log(`Found category ID in resultFields: ${categoryId}`);
3764 |     } else if (firstTest.testCategory && firstTest.testCategory.id) {
3765 |       categoryId = firstTest.testCategory.id;
3766 |       console.log(`Found category ID via testCategory relation: ${categoryId}`);
3767 |     } else {
3768 |       // For a Hematology modal, we could hard-code the Hematology category ID
3769 |       // This is just a fallback for when the data structure doesn't contain what we need
3770 |       console.log('Could not find category ID in resultFields');
3771 |     }
3772 |     
3773 |     if (categoryId) {
3774 |       loadAnalyzers(categoryId);
3775 |     } else {
3776 |       // If no category ID is found, try to get it from the request details
3777 |       if (props.requestDetails && props.requestDetails.tests && 
3778 |           props.requestDetails.tests.length > 0 && 
3779 |           props.requestDetails.tests[0].test_category_id) {
3780 |         loadAnalyzers(props.requestDetails.tests[0].test_category_id);
3781 |       } else {
3782 |         // If all else fails, load all equipment
3783 |         loadAnalyzers();
3784 |       }
3785 |     }
3786 |   } else {
3787 |     // If no tests are loaded yet, load all equipment
3788 |     console.log('No tests in resultFields, loading all equipment');
3789 |     loadAnalyzers();
3790 |   }
3791 | };
3792 | 
3793 | // Add a watch to log button state changes for debugging
3794 | // watch(allResultsSaved, (newValue, oldValue) => {
3795 | //   if (newValue !== oldValue) {
3796 | //     console.log(`Button states updated: Save button disabled: ${newValue}, Print/Validate buttons enabled: ${newValue}`);
3797 | //   }
3798 | //   
3799 | //   // Update our reactive variable for immediate UI updates
3800 | //   isResultsSaved.value = newValue;
3801 | // });
3802 | // 
3803 | // // Watch for testsArePrinted changes
3804 | // watch(testsArePrinted, (newValue, oldValue) => {
3805 | //   if (newValue !== oldValue) {
3806 | //     console.log(`Printed state updated: Tests are printed: ${newValue}, Validate is disabled: ${newValue}`);
3807 | //   }
3808 | //   
3809 | //   // Update our reactive variable for immediate UI updates
3810 | //   resultsArePrinted.value = newValue;
3811 | // });
3812 | 
3813 | // Watch for changes in the request details to load category-specific equipment
3814 | watch(() => props.requestDetails, (newDetails) => {
3815 |   if (newDetails && newDetails.tests && Array.isArray(newDetails.tests) && newDetails.tests.length > 0) {
3816 |     // Log the structure of the test data to help debug
3817 |     console.log('First test from request details:', newDetails.tests[0]);
3818 |     
3819 |     // Get the category ID from the first test
3820 |     const firstTest = newDetails.tests[0];
3821 |     
3822 |     // Try different possible paths to find the test_category_id
3823 |     let categoryId = null;
3824 |     
3825 |     if (firstTest.test_category_id) {
3826 |       categoryId = firstTest.test_category_id;
3827 |       console.log(`Found category ID directly on test: ${categoryId}`);
3828 |     } else if (firstTest.testCategory && firstTest.testCategory.id) {
3829 |       categoryId = firstTest.testCategory.id;
3830 |       console.log(`Found category ID via testCategory relation: ${categoryId}`);
3831 |     } else {
3832 |       // In production hematology service, we could hardcode a known ID from your database
3833 |       console.log('Could not find test category ID, loading all equipment');
3834 |     }
3835 |     
3836 |     if (categoryId) {
3837 |       loadAnalyzers(categoryId);
3838 |     } else {
3839 |       // Fallback to loading all equipment
3840 |       loadAnalyzers();
3841 |     }
3842 |   } else {
3843 |     console.log('No tests found in request details, loading all equipment');
3844 |     loadAnalyzers();
3845 |   }
3846 | }, { immediate: true });
3847 | 
3848 | // Watch for modal visibility to check CDS rules and flagged delta checks when opened
3849 | watch(() => props.visible, async (newVal) => {
3850 |   if (newVal) {
3851 |     // Check for flagged delta checks when modal opens
3852 |     await checkFlaggedDeltaChecks();
3853 |     await checkAllDeltaChecks();
3854 |     // Check CDS rules when modal opens
3855 |     if (props.requestDetails?.id) {
3856 |       await checkCdsRules();
3857 |       fetchAuditTrail();
3858 |     }
3859 |   } else {
3860 |     // Clear alerts when modal closes
3861 |     cdsAlerts.value = [];
3862 |     flaggedDeltaChecks.value = [];
3863 |     allDeltaChecks.value = [];
3864 |     hasHoldStatus.value = false;
3865 |   }
3866 | });
3867 | 
3868 | // Watch for violations modal to reset state when closed
3869 | watch(() => showViolationsModal.value, (isOpen) => {
3870 |   if (!isOpen) {
3871 |     activeViolationTab.value = 'summary';
3872 |   }
3873 | });
3874 | 
3875 | // Watch for testParameters to be loaded and check for flagged results
3876 | watch(() => testParameters.value, async () => {
3877 |   // After parameters are loaded, check again for flagged delta checks
3878 |   // This is useful because we now have result_ids available
3879 |   if (props.visible && props.requestDetails?.id && Object.keys(testParameters.value).length > 0) {
3880 |     // Wait a bit for the data to be fully processed
3881 |     await new Promise(resolve => setTimeout(resolve, 500));
3882 |     console.log('Test parameters loaded, re-checking for flagged delta checks...');
3883 |     await checkFlaggedDeltaChecks();
3884 |     await checkAllDeltaChecks();
3885 |   }
3886 | }, { deep: true });
3887 | 
3888 | // Remove the initialization of old reactive variables in onMounted
3889 | onMounted(() => {
3890 |   loadAnalyzersForTests();
3891 |   loadRoleUsers(); // Load role-specific users
3892 |   window.addEventListener('keydown', handleKeyDown);
3893 |   // Initialize QC banner
3894 |   updateQCHoldBanner();
3895 |   
3896 |   // Check CDS rules on mount if modal is visible
3897 |   if (props.visible && props.requestDetails?.id) {
3898 |     checkCdsRules();
3899 |   }
3900 |   
3901 |   // Initialize button states immediately
3902 |   nextTick(() => {
3903 |     console.log('Component mounted - initializing button states');
3904 |     
3905 |     // Check for parameters with values
3906 |     const allParams = Object.values(testParameters.value).flat();
3907 |     console.log(`Found ${allParams.length} total parameters on initial load`);
3908 |     
3909 |     // Run detailed debug
3910 |     debugParameterStates();
3911 |     
3912 |     // CRITICAL FIX: Check if any parameters have been saved previously
3913 |     // If all parameters with values already have result_ids, we should enable Print/Validate buttons
3914 |     const paramsWithValues = allParams.filter(param => 
3915 |       param && param.resultValue && param.resultValue.trim() !== ''
3916 |     );
3917 |     
3918 |     const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
3919 |     
3920 |     // If we have parameters with values but all are already saved (have result_ids),
3921 |     // we should NOT mark them as unsaved - this keeps Save button disabled and Print/Validate enabled
3922 |     if (paramsWithValues.length > 0 && unsavedParams.length === 0) {
3923 |       console.log('All parameters with values already have result_ids - keeping them marked as saved');
3924 |       // No need to change result_ids, they should remain as is
3925 |     }
3926 |     // If we have any unsaved parameters with values, we should proceed as before
3927 |     else if (paramsWithValues.length > 0) {
3928 |       console.log('Parameters with values found, marking as unsaved to enable Save button');
3929 |       paramsWithValues.forEach(param => {
3930 |         // Clear result_id to mark as unsaved
3931 |         param.result_id = null;
3932 |         console.log(`Marked parameter ${param.id} with value "${param.resultValue}" as unsaved`);
3933 |       });
3934 |     }
3935 |     
3936 |     // Force update after setting initial state
3937 |     forceButtonUpdate();
3938 |     
3939 |     // Run debug again after modifications
3940 |     console.log('After initial state setup:');
3941 |     debugParameterStates();
3942 |   });
3943 |   
3944 |   // Enhanced debugging for signatories
3945 |   console.group('HematologyModal Signatories Debug');
3946 |   console.log('Initial signatories from props:', {
3947 |     performer_id: props.requestDetails?.performer_id,
3948 |     verifier_id: props.requestDetails?.verifier_id,
3949 |     pathologist_id: props.requestDetails?.pathologist_id
3950 |   });
3951 |   
3952 |   if (props.requestDetails?.tests?.length > 0) {
3953 |     console.log('First test tracking data:', {
3954 |       id: props.requestDetails.tests[0]?.id,
3955 |       performer_id: props.requestDetails.tests[0]?.performer_id,
3956 |       verifier_id: props.requestDetails.tests[0]?.verifier_id,
3957 |       pathologist_id: props.requestDetails.tests[0]?.pathologist_id
3958 |     });
3959 |   } else {
3960 |     console.log('No tests available in requestDetails');
3961 |   }
3962 |   
3963 |   console.log('Current localSignatories state:', localSignatories.value);
3964 |   console.groupEnd();
3965 | });
3966 | 
3967 | onUnmounted(() => {
3968 |   window.removeEventListener('keydown', handleKeyDown);
3969 | });
3970 | 
3971 |   // Enhanced handleInstrumentChange function to work with equipment IDs
3972 | const handleInstrumentChange = async (row, equipmentId) => {
3973 |   if (!row.isParameter || !row.testId || !row.parameterId) {
3974 |     console.log('Missing required data for handleInstrumentChange:', { row });
3975 |     return;
3976 |   }
3977 |   
3978 |   console.log(`Changing equipment for parameter ${row.parameterId} to ${equipmentId}`);
3979 |   
3980 |   // Find the parameter in testParameters
3981 |   const testParams = testParameters.value[row.testId];
3982 |   if (!testParams) {
3983 |     console.error(`No test parameters found for test ID ${row.testId}`);
3984 |     return;
3985 |   }
3986 |   
3987 |   const paramToUpdate = testParams.find(p => p.id === row.parameterId);
3988 |   if (!paramToUpdate) {
3989 |     console.error(`Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
3990 |     return;
3991 |   }
3992 |   
3993 |   // Update the instrument in the source (using equipment_id)
3994 |   paramToUpdate.instrument = equipmentId;
3995 |   
3996 |   // Find the equipment name for display
3997 |   const selectedEquipment = equipment.value.find(eq => eq.id === parseInt(equipmentId));
3998 |   const equipmentName = selectedEquipment ? `${selectedEquipment.manufacturer} - ${selectedEquipment.model}` : 'Unknown';
3999 |   console.log(`Found equipment: ${equipmentName}`);
4000 |   
[Tool]
File: backend/app/Http/Controllers/Api/ResultManagementController.php
IMPORTANT: File content truncated.
	Status: Showing lines 1-2000 of 2230 total lines.
	To read more: Use the read_file tool with offset=2001 and limit=2000.
	
	   1 | <?php
   2 | 
   3 | namespace App\Http\Controllers\Api;
   4 | 
   5 | use App\Http\Controllers\Controller;
   6 | use App\Models\LaboratoryRequest;
   7 | use Illuminate\Http\Request;
   8 | use Illuminate\Support\Facades\DB;
   9 | use Illuminate\Support\Facades\Log;
  10 | use Illuminate\Support\Facades\Auth;
  11 | use Illuminate\Database\Eloquent\ModelNotFoundException;
  12 | use App\Models\TestCategory; // Assuming TestCategory model exists
  13 | use App\Models\TestParameter;
  14 | use App\Models\TestResult;
  15 | use App\Models\User;
  16 | use App\Models\RuleDecisionAudit;
  17 | use App\Services\QualityControlLockoutService;
  18 | use App\Models\CdsCorrelationRule;
  19 | use App\Services\CdsRuleEngine;
  20 | use App\Services\ReflexTestService;
  21 | 
  22 | 
  23 | class ResultManagementController extends Controller
  24 | {
  25 |     /**
  26 |      * Fetch requests relevant to Hematology Result Management.
  27 |      *
  28 |      * Fetches requests that contain tests belonging to the 'Hematology' category
  29 |      * and includes relevant patient and test details.
  30 |      *
  31 |      * @param  \Illuminate\Http\Request  $request
  32 |      * @return \Illuminate\Http\JsonResponse
  33 |      */
  34 |     // public function getHematologyRequests(Request $request)
  35 |     // {
  36 |     //     // --- Configuration ---
  37 |     //     $hematologyCategoryName = 'Hematology'; // Case-sensitive category name
  38 |     //     // ---------------------
  39 | 
  40 |     //     try {
  41 |     //         // Find the ID of the Hematology category
  42 |     //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();
  43 | 
  44 |     //         if (!$hematologyCategory) {
  45 |     //             Log::warning("Hematology category '{$hematologyCategoryName}' not found.");
  46 |     //             // Return empty paginated response if category doesn't exist
  47 |     //             return response()->json(LaboratoryRequest::paginate($request->input('per_page', 15)));
  48 |     //         }
  49 | 
  50 |     //         $hematologyCategoryId = $hematologyCategory->id;
  51 | 
  52 |     //         $query = LaboratoryRequest::query();
  53 | 
  54 |     //         // 1. Filter Requests: Only include requests with at least one test in the Hematology category
  55 |     //         //    and where that test status is relevant for results (e.g., 'Accepted')
  56 |     //         $query->whereHas('tests', function ($testQuery) use ($hematologyCategoryId) {
  57 |     //             $testQuery->where('test_category_id', $hematologyCategoryId)
  58 |     //                       ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run']); // Statuses indicating results can be viewed/entered
  59 |     //         });
  60 | 
  61 |     //         // 2. Eager Load Relations:
  62 |     //         $query->with([
  63 |     //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email', // Select necessary patient fields
  64 |     //             'patientType:id,name', // Load patient type directly from LaboratoryRequest
  65 |     //             'ward:id,name',        // Load ward directly from LaboratoryRequest
  66 |     //             'room:id,name',        // Load room directly from LaboratoryRequest
  67 |     //             // Load ONLY the tests relevant to Hematology for this view
  68 |     //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
  69 |     //                 $testQuery->where('test_category_id', $hematologyCategoryId)
  70 |     //                           ->select('tests.id', 'tests.name') // Select only needed test fields
  71 |     //                           ->withPivot('item_status'); // Load pivot status
  72 |     //             }
  73 |     //         ]);
  74 | 
  75 |     //         // 3. Search Functionality (Optional - Example)
  76 |     //         if ($request->filled('search')) {
  77 |     //             $searchTerm = $request->input('search');
  78 |     //             $query->where(function ($q) use ($searchTerm) {
  79 |     //                 $q->where('id', 'like', "%{$searchTerm}%")
  80 |     //                   ->orWhere('sample_id', 'like', "%{$searchTerm}%")
  81 |     //                   ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
  82 |     //                       $patientQuery->where(DB::raw("CONCAT(lname, ' ', fname)"), 'like', "%{$searchTerm}%")
  83 |     //                                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
  84 |     //                                    ->orWhere('first_name', 'like', "%{$searchTerm}%")
  85 |     //                                    ->orWhere('middle_name', 'like', "%{$searchTerm}%")
  86 |     //                                    ->orWhere('id', 'like', "%{$searchTerm}%");
  87 |     //                   });
  88 |     //             });
  89 |     //         }
  90 | 
  91 |     //         // 4. Ordering
  92 |     //         $query->orderBy('priority_level', 'desc')
  93 |     //               ->orderBy('request_date', 'asc');
  94 | 
  95 |     //         // 5. Paginate
  96 |     //         $perPage = $request->input('per_page', 15);
  97 |     //         \Log::debug('Executing getHematologyRequests query: ' . $query->toSql());
  98 |     //         $requests = $query->paginate($perPage);
  99 | 
 100 |     //         // 6. Transform Data (Add computed fields like age, test list string, status)
 101 |     //         $requests->getCollection()->transform(function ($request) use ($hematologyCategoryId) {
 102 |     //             // Calculate Age
 103 |     //             //$request->patient_age = $request->patient && $request->patient->date_of_birth
 104 |     //             //    ? now()->diffInYears($request->patient->date_of_birth)
 105 |     //             //    : 'N/A';
 106 | 
 107 |     //             // Create comma-separated list of Hematology test names
 108 |     //             $request->hematology_test_list = $request->tests->pluck('name')->implode(', ');
 109 | 
 110 |     //             // Determine overall Hematology status (Placeholder Logic)
 111 |     //             // This needs refinement based on actual result entry/validation models/flags
 112 |     //             $hasValidated = $request->tests->contains(function ($test) {
 113 |     //                 return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
 114 |     //             });
 115 |     //              $hasPrinted = $request->tests->contains(function ($test) {
 116 |     //                 return $test->pivot->item_status === 'Result Printed';
 117 |     //             });
 118 |     //             $hasAccepted = $request->tests->contains(function ($test) {
 119 |     //                 return $test->pivot->item_status === 'Accepted' || $test->pivot->item_status === 'Collected' || $test->pivot->item_status === 'Send-out' || $test->pivot->item_status === 'Parked';
 120 |     //             });
 121 |     //             $hasResults = $request->tests->contains(function ($test) {
 122 |     //                 return $test->pivot->item_status === 'Processing Results';
 123 |     //             });
 124 |     //             $hasCheckedIn = $request->tests->contains(function ($test) {
 125 |     //                 return $test->pivot->item_status === 'Checked-in';
 126 |     //             });
 127 |     //             $hasForRerun = $request->tests->contains(function ($test) {
 128 |     //                 return $test->pivot->item_status === 'For Re-run';
 129 |     //             });
 130 | 
 131 |     //             if($hasPrinted) {
 132 |     //                 $request->hematology_status = 'Printed';
 133 |     //             } elseif ($hasForRerun) {
 134 |     //                 $request->hematology_status = 'For Re-run';
 135 |     //             } elseif ($hasValidated) {
 136 |     //                  $request->hematology_status = 'Validated';
 137 |     //             } elseif ($hasAccepted) {
 138 |     //                 $request->hematology_status = 'Pending Sample';
 139 |     //             } elseif ($hasCheckedIn) {
 140 |     //                  $request->hematology_status = 'Checked-in';
 141 |     //             } elseif ($hasResults) {
 142 |     //                     $request->hematology_status = 'Processing Results';
 143 |     //             } else {
 144 |     //                  $request->hematology_status = 'Unknown'; // Or Unknown?
 145 |     //             }
 146 | 
 147 |     //             // Add test parameter counts for progress tracking
 148 |     //             foreach ($request->tests as $test) {
 149 |     //                 // Get the total parameter count for this test
 150 |     //                 $test->parameter_count = TestParameter::where('test_id', $test->id)->count();
 151 |                     
 152 |     //                 // Get the completed parameter count (parameters with results)
 153 |     //                 $test->completed_parameter_count = TestResult::where([
 154 |     //                     'laboratory_request_id' => $request->id,
 155 |     //                     'test_id' => $test->id,
 156 |     //                 ])->whereNotNull('test_parameter_id')->count();
 157 |                     
 158 |     //                 // Special case: if the test has no parameters, count a single non-parameterized result
 159 |     //                 if ($test->parameter_count === 0) {
 160 |     //                     $test->parameter_count = 1;
 161 |     //                     $test->completed_parameter_count = TestResult::where([
 162 |     //                         'laboratory_request_id' => $request->id,
 163 |     //                         'test_id' => $test->id,
 164 |     //                         'test_parameter_id' => null,
 165 |     //                     ])->count();
 166 |     //                 }
 167 |     //             }
 168 | 
 169 |     //             // Optionally unset the loaded tests relation if frontend doesn't need the full structure
 170 |     //             // unset($request->tests);
 171 | 
 172 |     //             return $request;
 173 |     //         });
 174 | 
 175 |     //         return response()->json($requests);
 176 | 
 177 |     //     } catch (\Exception $e) {
 178 |     //         Log::error("Error fetching Hematology requests: " . $e->getMessage());
 179 |     //         // Return a generic error response
 180 |     //         return response()->json(['message' => 'An error occurred while fetching Hematology requests.'], 500);
 181 |     //     }
 182 |     // }
 183 | 
 184 |     /**
 185 |      * Fetch detailed information for a single Hematology request, including tests and results.
 186 |      *
 187 |      * @param  \App\Models\LaboratoryRequest  $laboratoryRequest
 188 |      * @return \Illuminate\Http\JsonResponse
 189 |      */
 190 |     // public function getHematologyRequestDetails(LaboratoryRequest $laboratoryRequest)
 191 |     // {
 192 |     //     // --- Configuration ---
 193 |     //     $hematologyCategoryName = 'Hematology';
 194 |     //     // ---------------------
 195 | 
 196 |     //     try {
 197 |     //         // Find the ID of the Hematology category
 198 |     //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();
 199 |     //         if (!$hematologyCategory) {
 200 |     //              return response()->json(['message' => "Hematology category '{$hematologyCategoryName}' not found."], 404);
 201 |     //         }
 202 |     //         $hematologyCategoryId = $hematologyCategory->id;
 203 |             
 204 |     //         // Add debugging logs
 205 |     //         \Log::info('⚡️ Starting getHematologyRequestDetails for laboratory request ID: ' . $laboratoryRequest->id);
 206 | 
 207 |     //         // 1. Load the core request data and RELATIONS, including filtered tests
 208 |     //         $laboratoryRequest->load([
 209 |     //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email',
 210 |     //             'patientType:id,name',
 211 |     //             'ward:id,name',
 212 |     //             'room:id,name',
 213 |     //             'doctor:id,name', // Assuming Doctor model has 'name' attribute
 214 |     //             'creator:id,name',  // Assuming User model has 'name' attribute
 215 |     //             // Load ONLY Hematology tests with basic pivot data (like item_status, remarks, comment)
 216 |     //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
 217 |     //                 $testQuery->where('test_category_id', $hematologyCategoryId)
 218 |     //                           ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run'])
 219 |     //                           ->withPivot('id', 'remarks', 'comment', 'perform_by', 'perform_at', 'verify_by', 'verify_at', 'pathologist_id'); // Include tracking fields
 220 |     //             },
 221 |     //             // Load test results for this laboratory request
 222 |     //             'results' => function($query) use ($hematologyCategoryId) {
 223 |     //                 $query->whereHas('test', function($q) use ($hematologyCategoryId) {
 224 |     //                     $q->where('test_category_id', $hematologyCategoryId);
 225 |     //                 })
 226 |     //                 ->with(['test', 'testParameter', 'equipment']);
 227 |     //             }
 228 |     //         ]);
 229 | 
 230 |     //         // Log the loaded test results for debugging
 231 |     //         \Log::debug('⚡️ Loaded test results:', [
 232 |     //             'count' => $laboratoryRequest->results->count(),
 233 |     //             'sample' => $laboratoryRequest->results->take(3)->map(function($result) {
 234 |     //                 return [
 235 |     //                     'id' => $result->id,
 236 |     //                     'test_id' => $result->test_id,
 237 |     //                     'test_parameter_id' => $result->test_parameter_id,
 238 |     //                     'result_value' => $result->result_value,
 239 |     //                     'result_comment' => $result->result_comment
 240 |     //                 ];
 241 |     //             })
 242 |     //         ]);
 243 | 
 244 |     //         // Process the loaded test results and associate them with their respective tests
 245 |     //         $tests = $laboratoryRequest->tests;
 246 |             
 247 |     //         // Create a map to store test parameters and their results by test_id
 248 |     //         $testResultsMap = [];
 249 |             
 250 |     //         // Add user information for tracking fields
 251 |     //         $laboratoryRequest->tests->each(function($test) {
 252 |     //             // Load performer user
 253 |     //             if ($test->pivot->perform_by) {
 254 |     //                 $test->performer_id = $test->pivot->perform_by;
 255 |     //                 $test->perform_at = $test->pivot->perform_at;
 256 |                     
 257 |     //                 // Load the actual user data if needed
 258 |     //                 try {
 259 |     //                     $performer = \App\Models\User::find($test->pivot->perform_by, ['id', 'name', 'email']);
 260 |     //                     if ($performer) {
 261 |     //                         $test->performer = $performer;
 262 |     //                     }
 263 |     //                 } catch (\Exception $e) {
 264 |     //                     \Log::error("Error loading performer user: " . $e->getMessage());
 265 |     //                 }
 266 |     //             }
 267 |                 
 268 |     //             // Load verifier user
 269 |     //             if ($test->pivot->verify_by) {
 270 |     //                 $test->verifier_id = $test->pivot->verify_by;
 271 |     //                 $test->verify_at = $test->pivot->verify_at;
 272 |                     
 273 |     //                 // Load the actual user data if needed
 274 |     //                 try {
 275 |     //                     $verifier = User::find($test->pivot->verify_by, ['id', 'name', 'email']);
 276 |     //                     if ($verifier) {
 277 |     //                         $test->verifier = $verifier;
 278 |     //                     }
 279 |     //                 } catch (\Exception $e) {
 280 |     //                     \Log::error("Error loading verifier user: " . $e->getMessage());
 281 |     //                 }
 282 |     //             }
 283 |                 
 284 |     //             // Load pathologist user
 285 |     //             if ($test->pivot->pathologist_id) {
 286 |     //                 $test->pathologist_id = $test->pivot->pathologist_id;
 287 |                     
 288 |     //                 // Load the actual user data if needed
 289 |     //                 try {
 290 |     //                     $pathologist = User::find($test->pivot->pathologist_id, ['id', 'name', 'email']);
 291 |     //                     if ($pathologist) {
 292 |     //                         $test->pathologist = $pathologist;
 293 |     //                     }
 294 |     //                 } catch (\Exception $e) {
 295 |     //                     \Log::error("Error loading pathologist user: " . $e->getMessage());
 296 |     //                 }
 297 |     //             }
 298 |     //         });
 299 |             
 300 |     //         // Process each test result and group by test_id
 301 |     //         foreach ($laboratoryRequest->results as $result) {
 302 |     //             $testId = $result->test_id;
 303 |                 
 304 |     //             if (!isset($testResultsMap[$testId])) {
 305 |     //                 $testResultsMap[$testId] = [
 306 |     //                     'parameters' => [],
 307 |     //                     'performer_id' => $result->performer_id,
 308 |     //                     'verifier_id' => $result->verifier_id,
 309 |     //                     'pathologist_id' => $result->pathologist_id,
 310 |     //                     'status' => $result->status
 311 |     //                 ];
 312 |     //             }
 313 |                 
 314 |     //             // Add this result to the parameters array if it has a parameter
 315 |     //             if ($result->testParameter) {
 316 |     //                 // Log the result_comment for debugging
 317 |     //                 \Log::debug('⚡️ Processing test result with parameter:', [
 318 |     //                     'result_id' => $result->id,
 319 |     //                     'test_id' => $result->test_id,
 320 |     //                     'parameter_id' => $result->test_parameter_id,
 321 |     //                     'result_value' => $result->result_value,
 322 |     //                     'result_comment' => $result->result_comment,
 323 |     //                     'result_comment_type' => gettype($result->result_comment)
 324 |     //                 ]);
 325 |                 
 326 |     //                 $testResultsMap[$testId]['parameters'][] = [
 327 |     //                     'id' => $result->testParameter->id,
 328 |     //                     'name' => $result->testParameter->name,
 329 |     //                     'test_name' => $result->testParameter->name,
 330 |     //                     'resultValue' => $result->result_value,
 331 |     //                     'unit' => $result->unit,
 332 |     //                     'instrument' => $result->equipment_id,
 333 |     //                     'equipment_name' => $result->equipment ? ($result->equipment->manufacturer . ' - ' . $result->equipment->model) : null,
 334 |     //                     'resultComment' => $result->result_comment,
 335 |     //                     'flag' => $result->flag,
 336 |     //                     'result_id' => $result->id, // Include the actual result ID for proper tracking
 337 |     //                     // Don't include reference ranges from saved results
 338 |     //                     // Let the frontend fetch these from test parameters directly
 339 |     //                 ];
 340 |     //             } else {
 341 |     //                 // Log non-parameterized test result
 342 |     //                 \Log::debug('⚡️ Processing non-parameterized test result:', [
 343 |     //                     'result_id' => $result->id,
 344 |     //                     'test_id' => $result->test_id,
 345 |     //                     'result_value' => $result->result_value,
 346 |     //                     'result_comment' => $result->result_comment,
 347 |     //                     'result_comment_type' => gettype($result->result_comment)
 348 |     //                 ]);
 349 |                     
 350 |     //                 // If it's a non-parameterized test, set the result directly on the test
 351 |     //                 $test = $tests->firstWhere('id', $testId);
 352 |     //                 if ($test) {
 353 |     //                     $test->initial_resultValue = $result->result_value;
 354 |     //                     $test->initial_instrument = $result->equipment_id;
 355 |     //                     $test->initial_resultComment = $result->result_comment;
 356 |     //                     $test->result_id = $result->id; // Include the actual result ID for proper tracking
 357 |     //                 }
 358 |     //             }
 359 |     //         }
 360 |             
 361 |     //         // Make sure result_comment is ONLY present in the parameters and NOT at the laboratory_request level
 362 |     //         // If a result_comment property accidentally exists at the laboratory_request level, remove it 
 363 |     //         if (property_exists($laboratoryRequest, 'result_comment')) {
 364 |     //             \Log::warning('⚡️ Found result_comment at laboratory_request level - removing it');
 365 |     //             unset($laboratoryRequest->result_comment);
 366 |     //         }
 367 |             
 368 |     //         // Debug log the final testParameters structure
 369 |     //         \Log::debug('⚡️ Final testParameters structure:', [
 370 |     //             'count' => count($testResultsMap),
 371 |     //             'sample' => array_slice($testResultsMap, 0, 1, true)
 372 |     //         ]);
 373 | 
 374 |     //         // Now associate the parameters with each test
 375 |     //         foreach ($tests as $test) {
 376 |     //             if (isset($testResultsMap[$test->id])) {
 377 |     //                 // Set the test parameters directly in the testParameters structure that the frontend expects
 378 |     //                 $testParameters = [];
 379 |     //                 $testParameters[$test->id] = $testResultsMap[$test->id]['parameters'];
 380 |     //                 $laboratoryRequest->testParameters = $testParameters;
 381 |                     
 382 |     //                 // Set the signatories
 383 |     //                 $laboratoryRequest->performer_id = $testResultsMap[$test->id]['performer_id'];
 384 |     //                 $laboratoryRequest->verifier_id = $testResultsMap[$test->id]['verifier_id'];
 385 |     //                 $laboratoryRequest->pathologist_id = $testResultsMap[$test->id]['pathologist_id'];
 386 |                     
 387 |     //                 // Add test result status - will be the same for all test parameters
 388 |     //                 $test->result_status = $testResultsMap[$test->id]['status'];
 389 |                     
 390 |     //                 // Also add the remarks and comment from the pivot
 391 |     //                 if ($test->pivot && $test->pivot->remarks) {
 392 |     //                     $laboratoryRequest->remarks = $test->pivot->remarks;
 393 |     //                 }
 394 |                     
 395 |     //                 if ($test->pivot && $test->pivot->comment) {
 396 |     //                     $laboratoryRequest->comment = $test->pivot->comment;
 397 |     //                 }
 398 |     //             }
 399 |     //         }
 400 | 
 401 |     //         // 2. Fetch User Lists for Dropdowns (if needed, can be separate API call)
 402 |     //         // Consider if these are truly needed here or could be fetched once by the frontend.
 403 |     //         $users = User::select('id', 'name')->orderBy('name')->get();
 404 |     //         $verifierList = $users;
 405 |     //         $performerList = $users;
 406 |     //         $pathologistList = $users;
 407 | 
 408 |     //          // 3. Calculate Age (can also be done on frontend)
 409 |     //         if (!$laboratoryRequest->patient_age && $laboratoryRequest->patient) {
 410 |     //             $laboratoryRequest->patient_age = $laboratoryRequest->patient && $laboratoryRequest->patient->date_of_birth
 411 |     //                 ? now()->diffInYears($laboratoryRequest->patient->date_of_birth)
 412 |     //                 : 'N/A';
 413 |     //         }
 414 | 
 415 |     //         // 4. Structure the response
 416 |     //         // Pass the main model and the user lists separately
 417 |     //         return response()->json([
 418 |     //             'request_details' => $laboratoryRequest,
 419 |     //             'user_lists' => [
 420 |     //                 'verifiers' => $verifierList,
 421 |     //                 'performers' => $performerList,
 422 |     //                 'pathologists' => $pathologistList,
 423 |     //             ]
 424 |     //         ]);
 425 | 
 426 |     //     } catch (ModelNotFoundException $e) {
 427 |     //         return response()->json(['message' => 'Laboratory request not found.'], 404);
 428 |     //     } catch (\Exception $e) {
 429 |     //         \Log::error("⚡️ Error in getHematologyRequestDetails: " . $e->getMessage() . "\n" . $e->getTraceAsString());
 430 |     //         return response()->json(['message' => 'An error occurred while fetching request details.'], 500);
 431 |     //     }
 432 |     // }
 433 | 
 434 |     // ----------------------------------------------------------------------------------
 435 |     // CHEMISTRY RESULT MANAGEMENT METHODS
 436 |     // ----------------------------------------------------------------------------------
 437 | 
 438 | /**
 439 |      * Fetch requests relevant to Hematology Result Management.
 440 |      *
 441 |      * Fetches requests that contain tests belonging to the 'Hematology' category
 442 |      * and includes relevant patient and test details.
 443 |      *
 444 |      * @param  \Illuminate\Http\Request  $request
 445 |      * @return \Illuminate\Http\JsonResponse
 446 |      */
 447 |     // public function getChemistryRequests(Request $request)
 448 |     // {
 449 |     //     // --- Configuration ---
 450 |     //     $hematologyCategoryName = 'Clinical Chemistry'; // Case-sensitive category name
 451 |     //     // ---------------------
 452 | 
 453 |     //     try {
 454 |     //         // Find the ID of the Hematology category
 455 |     //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();
 456 | 
 457 |     //         if (!$hematologyCategory) {
 458 |     //             Log::warning("Chemistry category '{$hematologyCategoryName}' not found.");
 459 |     //             // Return empty paginated response if category doesn't exist
 460 |     //             return response()->json(LaboratoryRequest::paginate($request->input('per_page', 15)));
 461 |     //         }
 462 | 
 463 |     //         $hematologyCategoryId = $hematologyCategory->id;
 464 | 
 465 |     //         $query = LaboratoryRequest::query();
 466 | 
 467 |     //         // 1. Filter Requests: Only include requests with at least one test in the Hematology category
 468 |     //         //    and where that test status is relevant for results (e.g., 'Accepted')
 469 |     //         $query->whereHas('tests', function ($testQuery) use ($hematologyCategoryId) {
 470 |     //             $testQuery->where('test_category_id', $hematologyCategoryId)
 471 |     //                       ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run']); // Statuses indicating results can be viewed/entered
 472 |     //         });
 473 | 
 474 |     //         // 2. Eager Load Relations:
 475 |     //         $query->with([
 476 |     //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email', // Select necessary patient fields
 477 |     //             'patientType:id,name', // Load patient type directly from LaboratoryRequest
 478 |     //             'ward:id,name',        // Load ward directly from LaboratoryRequest
 479 |     //             'room:id,name',        // Load room directly from LaboratoryRequest
 480 |     //             // Load ONLY the tests relevant to Hematology for this view
 481 |     //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
 482 |     //                 $testQuery->where('test_category_id', $hematologyCategoryId)
 483 |     //                           ->select('tests.id', 'tests.name') // Select only needed test fields
 484 |     //                           ->withPivot('item_status'); // Load pivot status
 485 |     //             }
 486 |     //         ]);
 487 | 
 488 |     //         // 3. Search Functionality (Optional - Example)
 489 |     //         if ($request->filled('search')) {
 490 |     //             $searchTerm = $request->input('search');
 491 |     //             $query->where(function ($q) use ($searchTerm) {
 492 |     //                 $q->where('id', 'like', "%{$searchTerm}%")
 493 |     //                   ->orWhere('sample_id', 'like', "%{$searchTerm}%")
 494 |     //                   ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
 495 |     //                       $patientQuery->where(DB::raw("CONCAT(lname, ' ', fname)"), 'like', "%{$searchTerm}%")
 496 |     //                                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
 497 |     //                                    ->orWhere('first_name', 'like', "%{$searchTerm}%")
 498 |     //                                    ->orWhere('middle_name', 'like', "%{$searchTerm}%")
 499 |     //                                    ->orWhere('id', 'like', "%{$searchTerm}%");
 500 |     //                   });
 501 |     //             });
 502 |     //         }
 503 | 
 504 |     //         // 4. Ordering
 505 |     //         $query->orderBy('priority_level', 'desc')
 506 |     //               ->orderBy('request_date', 'asc');
 507 | 
 508 |     //         // 5. Paginate
 509 |     //         $perPage = $request->input('per_page', 15);
 510 |     //         $requests = $query->paginate($perPage);
 511 | 
 512 |     //         // 6. Transform Data (Add computed fields like age, test list string, status)
 513 |     //         $requests->getCollection()->transform(function ($request) use ($hematologyCategoryId) {
 514 |     //             // Calculate Age
 515 |     //             //$request->patient_age = $request->patient && $request->patient->date_of_birth
 516 |     //             //    ? now()->diffInYears($request->patient->date_of_birth)
 517 |     //             //    : 'N/A';
 518 | 
 519 |     //             // Create comma-separated list of Hematology test names
 520 |     //             $request->hematology_test_list = $request->tests->pluck('name')->implode(', ');
 521 | 
 522 |     //             // Determine overall Hematology status (Placeholder Logic)
 523 |     //             // This needs refinement based on actual result entry/validation models/flags
 524 |     //             $hasValidated = $request->tests->contains(function ($test) {
 525 |     //                 return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
 526 |     //             });
 527 |     //              $hasPrinted = $request->tests->contains(function ($test) {
 528 |     //                 return $test->pivot->item_status === 'Result Printed';
 529 |     //             });
 530 |     //             $hasAccepted = $request->tests->contains(function ($test) {
 531 |     //                 return $test->pivot->item_status === 'Accepted' || $test->pivot->item_status === 'Collected' || $test->pivot->item_status === 'Send-out' || $test->pivot->item_status === 'Parked';
 532 |     //             });
 533 |     //             $hasResults = $request->tests->contains(function ($test) {
 534 |     //                 return $test->pivot->item_status === 'Processing Results';
 535 |     //             });
 536 |     //             $hasCheckedIn = $request->tests->contains(function ($test) {
 537 |     //                 return $test->pivot->item_status === 'Checked-in';
 538 |     //             });
 539 | 
 540 |     //             if($hasPrinted) {
 541 |     //                 $request->hematology_status = 'Printed';
 542 |     //             } elseif ($hasValidated) {
 543 |     //                  $request->hematology_status = 'Validated';
 544 |     //             } elseif ($hasAccepted) {
 545 |     //                 $request->hematology_status = 'Pending Sample';
 546 |     //             } elseif ($hasCheckedIn) {
 547 |     //                  $request->hematology_status = 'Checked-in';
 548 |     //             } elseif ($hasResults) {
 549 |     //                     $request->hematology_status = 'Processing Results';
 550 |     //             } else {
 551 |     //                  $request->hematology_status = 'Unknown'; // Or Unknown?
 552 |     //             }
 553 | 
 554 |     //             // Add test parameter counts for progress tracking
 555 |     //             foreach ($request->tests as $test) {
 556 |     //                 // Get the total parameter count for this test
 557 |     //                 $test->parameter_count = TestParameter::where('test_id', $test->id)->count();
 558 |                     
 559 |     //                 // Get the completed parameter count (parameters with results)
 560 |     //                 $test->completed_parameter_count = TestResult::where([
 561 |     //                     'laboratory_request_id' => $request->id,
 562 |     //                     'test_id' => $test->id,
 563 |     //                 ])->whereNotNull('test_parameter_id')->count();
 564 |                     
 565 |     //                 // Special case: if the test has no parameters, count a single non-parameterized result
 566 |     //                 if ($test->parameter_count === 0) {
 567 |     //                     $test->parameter_count = 1;
 568 |     //                     $test->completed_parameter_count = TestResult::where([
 569 |     //                         'laboratory_request_id' => $request->id,
 570 |     //                         'test_id' => $test->id,
 571 |     //                         'test_parameter_id' => null,
 572 |     //                     ])->count();
 573 |     //                 }
 574 |     //             }
 575 | 
 576 |     //             // Optionally unset the loaded tests relation if frontend doesn't need the full structure
 577 |     //             // unset($request->tests);
 578 | 
 579 |     //             return $request;
 580 |     //         });
 581 | 
 582 |     //         return response()->json($requests);
 583 | 
 584 |     //     } catch (\Exception $e) {
 585 |     //         Log::error("Error fetching Hematology requests: " . $e->getMessage());
 586 |     //         // Return a generic error response
 587 |     //         return response()->json(['message' => 'An error occurred while fetching Hematology requests.'], 500);
 588 |     //     }
 589 |     // }
 590 | 
 591 |     // // Add other result management methods here (e.g., getChemistryRequests, getMicrobiologyRequests)
 592 | 
 593 |     // /**
 594 |     //  * Fetch detailed information for a single Hematology request, including tests and results.
 595 |     //  *
 596 |     //  * @param  \App\Models\LaboratoryRequest  $laboratoryRequest
 597 |     //  * @return \Illuminate\Http\JsonResponse
 598 |     //  */
 599 |     // public function getChemistryRequestDetails(LaboratoryRequest $laboratoryRequest)
 600 |     // {
 601 |     //     // --- Configuration ---
 602 |     //     $hematologyCategoryName = 'Clinical Chemistry';
 603 |     //     // ---------------------
 604 | 
 605 |     //     try {
 606 |     //         // Find the ID of the Hematology category
 607 |     //         $hematologyCategory = TestCategory::where('name', $hematologyCategoryName)->first();
 608 |     //         if (!$hematologyCategory) {
 609 |     //              return response()->json(['message' => "Hematology category '{$hematologyCategoryName}' not found."], 404);
 610 |     //         }
 611 |     //         $hematologyCategoryId = $hematologyCategory->id;
 612 |             
 613 |     //         // Add debugging logs
 614 |     //         \Log::info('⚡️ Starting getHematologyRequestDetails for laboratory request ID: ' . $laboratoryRequest->id);
 615 | 
 616 |     //         // 1. Load the core request data and RELATIONS, including filtered tests
 617 |     //         $laboratoryRequest->load([
 618 |     //             'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email',
 619 |     //             'patientType:id,name',
 620 |     //             'ward:id,name',
 621 |     //             'room:id,name',
 622 |     //             'doctor:id,name', // Assuming Doctor model has 'name' attribute
 623 |     //             'creator:id,name',  // Assuming User model has 'name' attribute
 624 |     //             // Load ONLY Hematology tests with basic pivot data (like item_status, remarks, comment)
 625 |     //             'tests' => function ($testQuery) use ($hematologyCategoryId) {
 626 |     //                 $testQuery->where('test_category_id', $hematologyCategoryId)
 627 |     //                           ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed'])
 628 |     //                           ->withPivot('id', 'remarks', 'comment', 'perform_by', 'perform_at', 'verify_by', 'verify_at', 'pathologist_id'); // Include tracking fields
 629 |     //             },
 630 |     //             // Load test results for this laboratory request
 631 |     //             'results' => function($query) use ($hematologyCategoryId) {
 632 |     //                 $query->whereHas('test', function($q) use ($hematologyCategoryId) {
 633 |     //                     $q->where('test_category_id', $hematologyCategoryId);
 634 |     //                 })
 635 |     //                 ->with(['test', 'testParameter', 'equipment']);
 636 |     //             }
 637 |     //         ]);
 638 | 
 639 |     //         // Log the loaded test results for debugging
 640 |     //         \Log::debug('⚡️ Loaded test results:', [
 641 |     //             'count' => $laboratoryRequest->results->count(),
 642 |     //             'sample' => $laboratoryRequest->results->take(3)->map(function($result) {
 643 |     //                 return [
 644 |     //                     'id' => $result->id,
 645 |     //                     'test_id' => $result->test_id,
 646 |     //                     'test_parameter_id' => $result->test_parameter_id,
 647 |     //                     'result_value' => $result->result_value,
 648 |     //                     'result_comment' => $result->result_comment
 649 |     //                 ];
 650 |     //             })
 651 |     //         ]);
 652 | 
 653 |     //         // Process the loaded test results and associate them with their respective tests
 654 |     //         $tests = $laboratoryRequest->tests;
 655 |             
 656 |     //         // Create a map to store test parameters and their results by test_id
 657 |     //         $testResultsMap = [];
 658 |             
 659 |     //         // Add user information for tracking fields
 660 |     //         $laboratoryRequest->tests->each(function($test) {
 661 |     //             // Load performer user
 662 |     //             if ($test->pivot->perform_by) {
 663 |     //                 $test->performer_id = $test->pivot->perform_by;
 664 |     //                 $test->perform_at = $test->pivot->perform_at;
 665 |                     
 666 |     //                 // Load the actual user data if needed
 667 |     //                 try {
 668 |     //                     $performer = \App\Models\User::find($test->pivot->perform_by, ['id', 'name', 'email']);
 669 |     //                     if ($performer) {
 670 |     //                         $test->performer = $performer;
 671 |     //                     }
 672 |     //                 } catch (\Exception $e) {
 673 |     //                     \Log::error("Error loading performer user: " . $e->getMessage());
 674 |     //                 }
 675 |     //             }
 676 |                 
 677 |     //             // Load verifier user
 678 |     //             if ($test->pivot->verify_by) {
 679 |     //                 $test->verifier_id = $test->pivot->verify_by;
 680 |     //                 $test->verify_at = $test->pivot->verify_at;
 681 |                     
 682 |     //                 // Load the actual user data if needed
 683 |     //                 try {
 684 |     //                     $verifier = User::find($test->pivot->verify_by, ['id', 'name', 'email']);
 685 |     //                     if ($verifier) {
 686 |     //                         $test->verifier = $verifier;
 687 |     //                     }
 688 |     //                 } catch (\Exception $e) {
 689 |     //                     \Log::error("Error loading verifier user: " . $e->getMessage());
 690 |     //                 }
 691 |     //             }
 692 |                 
 693 |     //             // Load pathologist user
 694 |     //             if ($test->pivot->pathologist_id) {
 695 |     //                 $test->pathologist_id = $test->pivot->pathologist_id;
 696 |                     
 697 |     //                 // Load the actual user data if needed
 698 |     //                 try {
 699 |     //                     $pathologist = User::find($test->pivot->pathologist_id, ['id', 'name', 'email']);
 700 |     //                     if ($pathologist) {
 701 |     //                         $test->pathologist = $pathologist;
 702 |     //                     }
 703 |     //                 } catch (\Exception $e) {
 704 |     //                     \Log::error("Error loading pathologist user: " . $e->getMessage());
 705 |     //                 }
 706 |     //             }
 707 |     //         });
 708 |             
 709 |     //         // Process each test result and group by test_id
 710 |     //         foreach ($laboratoryRequest->results as $result) {
 711 |     //             $testId = $result->test_id;
 712 |                 
 713 |     //             if (!isset($testResultsMap[$testId])) {
 714 |     //                 $testResultsMap[$testId] = [
 715 |     //                     'parameters' => [],
 716 |     //                     'performer_id' => $result->performer_id,
 717 |     //                     'verifier_id' => $result->verifier_id,
 718 |     //                     'pathologist_id' => $result->pathologist_id,
 719 |     //                     'status' => $result->status
 720 |     //                 ];
 721 |     //             }
 722 |                 
 723 |     //             // Add this result to the parameters array if it has a parameter
 724 |     //             if ($result->testParameter) {
 725 |     //                 // Log the result_comment for debugging
 726 |     //                 \Log::debug('⚡️ Processing test result with parameter:', [
 727 |     //                     'result_id' => $result->id,
 728 |     //                     'test_id' => $result->test_id,
 729 |     //                     'parameter_id' => $result->test_parameter_id,
 730 |     //                     'result_value' => $result->result_value,
 731 |     //                     'result_comment' => $result->result_comment,
 732 |     //                     'result_comment_type' => gettype($result->result_comment)
 733 |     //                 ]);
 734 |                 
 735 |     //                 $testResultsMap[$testId]['parameters'][] = [
 736 |     //                     'id' => $result->testParameter->id,
 737 |     //                     'name' => $result->testParameter->name,
 738 |     //                     'test_name' => $result->testParameter->name,
 739 |     //                     'resultValue' => $result->result_value,
 740 |     //                     'unit' => $result->unit,
 741 |     //                     'instrument' => $result->equipment_id,
 742 |     //                     'equipment_name' => $result->equipment ? ($result->equipment->manufacturer . ' - ' . $result->equipment->model) : null,
 743 |     //                     'resultComment' => $result->result_comment,
 744 |     //                     'flag' => $result->flag,
 745 |     //                     'result_id' => $result->id, // Include the actual result ID for proper tracking
 746 |     //                     // Don't include reference ranges from saved results
 747 |     //                     // Let the frontend fetch these from test parameters directly
 748 |     //                 ];
 749 |     //             } else {
 750 |     //                 // Log non-parameterized test result
 751 |     //                 \Log::debug('⚡️ Processing non-parameterized test result:', [
 752 |     //                     'result_id' => $result->id,
 753 |     //                     'test_id' => $result->test_id,
 754 |     //                     'result_value' => $result->result_value,
 755 |     //                     'result_comment' => $result->result_comment,
 756 |     //                     'result_comment_type' => gettype($result->result_comment)
 757 |     //                 ]);
 758 |                     
 759 |     //                 // If it's a non-parameterized test, set the result directly on the test
 760 |     //                 $test = $tests->firstWhere('id', $testId);
 761 |     //                 if ($test) {
 762 |     //                     $test->initial_resultValue = $result->result_value;
 763 |     //                     $test->initial_instrument = $result->equipment_id;
 764 |     //                     $test->initial_resultComment = $result->result_comment;
 765 |     //                     $test->result_id = $result->id; // Include the actual result ID for proper tracking
 766 |     //                 }
 767 |     //             }
 768 |     //         }
 769 |             
 770 |     //         // Make sure result_comment is ONLY present in the parameters and NOT at the laboratory_request level
 771 |     //         // If a result_comment property accidentally exists at the laboratory_request level, remove it 
 772 |     //         if (property_exists($laboratoryRequest, 'result_comment')) {
 773 |     //             \Log::warning('⚡️ Found result_comment at laboratory_request level - removing it');
 774 |     //             unset($laboratoryRequest->result_comment);
 775 |     //         }
 776 |             
 777 |     //         // Debug log the final testParameters structure
 778 |     //         \Log::debug('⚡️ Final testParameters structure:', [
 779 |     //             'count' => count($testResultsMap),
 780 |     //             'sample' => array_slice($testResultsMap, 0, 1, true)
 781 |     //         ]);
 782 | 
 783 |     //         // Now associate the parameters with each test
 784 |     //         foreach ($tests as $test) {
 785 |     //             if (isset($testResultsMap[$test->id])) {
 786 |     //                 // Set the test parameters directly in the testParameters structure that the frontend expects
 787 |     //                 $testParameters = [];
 788 |     //                 $testParameters[$test->id] = $testResultsMap[$test->id]['parameters'];
 789 |     //                 $laboratoryRequest->testParameters = $testParameters;
 790 |                     
 791 |     //                 // Set the signatories
 792 |     //                 $laboratoryRequest->performer_id = $testResultsMap[$test->id]['performer_id'];
 793 |     //                 $laboratoryRequest->verifier_id = $testResultsMap[$test->id]['verifier_id'];
 794 |     //                 $laboratoryRequest->pathologist_id = $testResultsMap[$test->id]['pathologist_id'];
 795 |                     
 796 |     //                 // Add test result status - will be the same for all test parameters
 797 |     //                 $test->result_status = $testResultsMap[$test->id]['status'];
 798 |                     
 799 |     //                 // Also add the remarks and comment from the pivot
 800 |     //                 if ($test->pivot && $test->pivot->remarks) {
 801 |     //                     $laboratoryRequest->remarks = $test->pivot->remarks;
 802 |     //                 }
 803 |                     
 804 |     //                 if ($test->pivot && $test->pivot->comment) {
 805 |     //                     $laboratoryRequest->comment = $test->pivot->comment;
 806 |     //                 }
 807 |     //             }
 808 |     //         }
 809 | 
 810 |     //         // 2. Fetch User Lists for Dropdowns (if needed, can be separate API call)
 811 |     //         // Consider if these are truly needed here or could be fetched once by the frontend.
 812 |     //         $users = User::select('id', 'name')->orderBy('name')->get();
 813 |     //         $verifierList = $users;
 814 |     //         $performerList = $users;
 815 |     //         $pathologistList = $users;
 816 | 
 817 |     //          // 3. Calculate Age (can also be done on frontend)
 818 |     //         if (!$laboratoryRequest->patient_age && $laboratoryRequest->patient) {
 819 |     //             $laboratoryRequest->patient_age = $laboratoryRequest->patient && $laboratoryRequest->patient->date_of_birth
 820 |     //                 ? now()->diffInYears($laboratoryRequest->patient->date_of_birth)
 821 |     //                 : 'N/A';
 822 |     //         }
 823 | 
 824 |     //         // 4. Structure the response
 825 |     //         // Pass the main model and the user lists separately
 826 |     //         return response()->json([
 827 |     //             'request_details' => $laboratoryRequest,
 828 |     //             'user_lists' => [
 829 |     //                 'verifiers' => $verifierList,
 830 |     //                 'performers' => $performerList,
 831 |     //                 'pathologists' => $pathologistList,
 832 |     //             ]
 833 |     //         ]);
 834 | 
 835 |     //     } catch (ModelNotFoundException $e) {
 836 |     //         return response()->json(['message' => 'Laboratory request not found.'], 404);
 837 |     //     } catch (\Exception $e) {
 838 |     //         \Log::error("⚡️ Error in getHematologyRequestDetails: " . $e->getMessage() . "\n" . $e->getTraceAsString());
 839 |     //         return response()->json(['message' => 'An error occurred while fetching request details.'], 500);
 840 |     //     }
 841 |     // }
 842 | 
 843 |     // /**
 844 |     //  * Fetch parameters for a specific test within a laboratory request, 
 845 |     //  * including any existing results.
 846 |     //  *
 847 |     //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
 848 |     //  * @param  \\App\\Models\\Test  $test
 849 |     //  * @param  \\Illuminate\\Http\\Request  $request (for is_rerun, though not used yet)
 850 |     //  * @return \\Illuminate\\Http\\JsonResponse
 851 |     //  */
 852 |     // public function getChemistryTestParameters(LaboratoryRequest $laboratoryRequest, Test $test, Request $request)
 853 |     // {
 854 |     //     // is_rerun logic is not implemented yet as per user's information.
 855 |     //     // For now, this will fetch current parameters and their saved results.
 856 | 
 857 |     //     try {
 858 |     //         $testTypeParameters = TestParameter::where('test_id', $test->id)
 859 |     //                                 ->orderBy('order', 'asc')
 860 |     //                                 ->get([
 861 |     //                                     'id as parameter_id', 
 862 |     //                                     'parameter_name',
 863 |     //                                     'test_id',
 864 |     //                                     'si_unit', 
 865 |     //                                     'si_default_value',
 866 |     //                                     'conventional_unit', 
 867 |     //                                     'conventional_default_value',
 868 |     //                                     'conversion_factor',
 869 |     //                                     'decimal_places',
 870 |     //                                     'reference_note'
 871 |     //                                 ]);
 872 | 
 873 |     //         if ($testTypeParameters->isEmpty()) {
 874 |     //             return response()->json([]); // Return empty if no parameters defined for the test
 875 |     //         }
 876 | 
 877 |     //         $patientAgeYears = $laboratoryRequest->patient ? Carbon::parse($laboratoryRequest->patient->date_of_birth)->age : null;
 878 |     //         $patientGender = $laboratoryRequest->patient ? $laboratoryRequest->patient->gender : null;
 879 | 
 880 |     //         $parametersWithResults = $testTypeParameters->map(function ($paramDefinition) use ($laboratoryRequest, $patientAgeYears, $patientGender) {
 881 |     //             $currentResult = TestResult::where('laboratory_request_id', $laboratoryRequest->id)
 882 |     //                                        ->where('test_id', $paramDefinition->test_id) // Ensure test_id context
 883 |     //                                        ->where('test_parameter_id', $paramDefinition->parameter_id)
 884 |     //                                        ->first();
 885 |                
 886 |     //             $referenceRangeData = ReferenceRange::getEffectiveReferenceRange(
 887 |     //                     $paramDefinition->parameter_id, 
 888 |     //                     $patientAgeYears,
 889 |     //                     $patientGender,
 890 |     //                     $currentResult->equipment_id ?? null
 891 |     //                 );
 892 | 
 893 |     //             $paramData = [
 894 |     //                 'test_result_id' => $currentResult->id ?? null,
 895 |     //                 'parameter_id' => $paramDefinition->parameter_id,
 896 |     //                 'parameter_name' => $paramDefinition->parameter_name,
 897 |     //                 'result_value' => $currentResult->result_value ?? $paramDefinition->si_default_value ?? null,
 898 |     //                 'si_unit' => $paramDefinition->si_unit,
 899 |     //                 'si_ref_range' => $referenceRangeData['si_range'] ?? 'N/A',
 900 |     //                 'conventional_value' => $currentResult->conventional_value ?? null,
 901 |     //                 'conventional_unit' => $paramDefinition->conventional_unit,
 902 |     //                 'conventional_ref_range' => $referenceRangeData['conventional_range'] ?? 'N/A',
 903 |     //                 'instrument_id' => $currentResult->equipment_id ?? null,
 904 |     //                 'result_remarks' => $currentResult->result_comment ?? '',
 905 |     //                 'flag' => $currentResult->flag ?? null,
 906 |     //                 'status' => $currentResult->status ?? 'Draft',
 907 |     //                 'decimal_places' => $paramDefinition->decimal_places,
 908 |     //                 'reference_note' => $paramDefinition->reference_note,
 909 |     //             ];
 910 |                
 911 |     //             // Calculate conventional value from SI if no current result and SI default exists
 912 |     //             if (!$currentResult && $paramData['result_value'] !== null && $paramDefinition->conversion_factor) {
 913 |     //                 $paramData['conventional_value'] = (float)$paramData['result_value'] * (float)$paramDefinition->conversion_factor;
 914 |     //                 if ($paramDefinition->decimal_places !== null) {
 915 |     //                     $paramData['conventional_value'] = round($paramData['conventional_value'], $paramDefinition->decimal_places);
 916 |     //                 }
 917 |     //             }
 918 |     //             return $paramData;
 919 |     //         });
 920 | 
 921 |     //         return response()->json($parametersWithResults);
 922 | 
 923 |     //     } catch (\Exception $e) {
 924 |     //         Log::error("Error fetching Chemistry test parameters for lab request ID {$laboratoryRequest->id}, test ID {$test->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
 925 |     //         return response()->json(['message' => 'An error occurred while fetching test parameters.'], 500);
 926 |     //     }
 927 |     // }
 928 | 
 929 |     // /**
 930 |     //  * Save Chemistry results for a laboratory request.
 931 |     //  *
 932 |     //  * @param  \\Illuminate\\Http\\Request  $request
 933 |     //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
 934 |     //  * @return \\Illuminate\\Http\\JsonResponse
 935 |     //  */
 936 |     // public function saveChemistryResults(Request $request, LaboratoryRequest $laboratoryRequest)
 937 |     // {
 938 |     //     $validator = Validator::make($request->all(), [
 939 |     //         'test_results' => 'present|array', // Array of parameters to save
 940 |     //         'test_results.*.test_result_id' => 'nullable|integer|exists:test_results,id',
 941 |     //         'test_results.*.parameter_id' => 'required_without:test_results.*.test_result_id|integer|exists:test_parameters,id',
 942 |     //         'test_results.*.result_value' => 'nullable', // Allow null to clear result
 943 |     //         'test_results.*.instrument_id' => 'nullable|integer|exists:analyzers,id',
 944 |     //         'test_results.*.result_remarks' => 'nullable|string',
 945 |     //         'performed_by_id' => 'nullable|integer|exists:users,id',
 946 |     //         'verified_by_id' => 'nullable|integer|exists:users,id', // For potential direct save with verifier
 947 |     //         'pathologist_id' => 'nullable|integer|exists:users,id', // For potential direct save with pathologist
 948 |     //     ]);
 949 | 
 950 |     //     if ($validator->fails()) {
 951 |     //         return response()->json(['errors' => $validator->errors()], 422);
 952 |     //     }
 953 | 
 954 |     //     DB::beginTransaction();
 955 |     //     try {
 956 |     //         $chemistryCategoryName = 'Chemistry';
 957 |     //         $chemistryCategory = TestCategory::where('name', $chemistryCategoryName)->firstOrFail();
 958 | 
 959 |     //         $savedTestResultIds = [];
 960 |     //         $affectedRequestTestPivots = []; // To store [test_id => pivot_data_updates]
 961 | 
 962 |     //         foreach ($request->input('test_results', []) as $paramResultData) {
 963 |     //             $parameter = TestParameter::with('test')->find($paramResultData['parameter_id']);
 964 |     //             if (!$parameter || $parameter->test->test_category_id !== $chemistryCategory->id) {
 965 |     //                 Log::warning('Attempted to save result for non-chemistry parameter or invalid parameter.', $paramResultData);
 966 |     //                 continue; // Skip this parameter
 967 |     //             }
 968 | 
 969 |     //             $testId = $parameter->test_id;
 970 | 
 971 |     //             $resultAttributes = [
 972 |     //                 'laboratory_request_id' => $laboratoryRequest->id,
 973 |     //                 'test_id' => $testId,
 974 |     //                 'test_parameter_id' => $paramResultData['parameter_id'],
 975 |     //             ];
 976 |                 
 977 |     //             $resultValues = [
 978 |     //                 'result_value' => $paramResultData['result_value'],
 979 |     //                 'equipment_id' => $paramResultData['instrument_id'] ?? null, // Frontend sends instrument_id
 980 |     //                 'result_comment' => $paramResultData['result_remarks'] ?? '',
 981 |     //                 'status' => 'Saved',
 982 |     //             ];
 983 | 
 984 |     //             $currentVersion = 0;
 985 |     //             if (isset($paramResultData['test_result_id']) && $paramResultData['test_result_id']) {
 986 |     //                 // This is a re-run or update of an existing result
 987 |     //                 $existingResult = TestResult::find($paramResultData['test_result_id']);
 988 |     //                 if ($existingResult) {
 989 |     //                     $currentVersion = $existingResult->version + 1;
 990 |     //                     // Optionally, mark the old result as superseded or inactive if needed
 991 |     //                     // For now, we just create a new one with an incremented version
 992 |     //                 }
 993 |     //             }
 994 | 
 995 |     //             $testResult = new TestResult();
 996 |     //             $testResult->fill($resultAttributes);
 997 |     //             $testResult->fill($resultValues);
 998 |     //             $testResult->version = $currentVersion;
 999 |     //             $testResult->save();
1000 |     //             $testResult->calculateFlag(); // Assumes TestResult model has this method
1001 |     //             $savedTestResultIds[] = $testResult->id;
1002 | 
1003 |     //             // Mark this test in the pivot as having results being processed
1004 |     //             if (!isset($affectedRequestTestPivots[$testId])) {
1005 |     //                 $affectedRequestTestPivots[$testId] = ['item_status' => 'Processing Results'];
1006 |     //             }
1007 |     //         }
1008 | 
1009 |     //         // Update personnel on the pivot table (laboratory_request_test)
1010 |     //         // This applies to all tests within the chemistry category for this request
1011 |     //         // if personnel are provided at the top level of the save request.
1012 |     //         $mainPersonnelUpdates = [];
1013 |     //         if ($request->filled('performed_by_id')) {
1014 |     //             $mainPersonnelUpdates['perform_by'] = $request->input('performed_by_id');
1015 |     //             $mainPersonnelUpdates['perform_at'] = now();
1016 |     //         }
1017 |     //         // If saving also implies verification (less common for just 'Save' button)
1018 |     //         if ($request->filled('verified_by_id')) {
1019 |     //              $mainPersonnelUpdates['verify_by'] = $request->input('verified_by_id');
1020 |     //              $mainPersonnelUpdates['verify_at'] = now();
1021 |     //              // If verified, typically all items within should also be marked as at least 'Saved' or 'Validated'
1022 |     //              // For simplicity, this save action primarily focuses on 'Saved' status for parameters.
1023 |     //         }
1024 |     //          if ($request->filled('pathologist_id')) {
1025 |     //              $mainPersonnelUpdates['pathologist_id'] = $request->input('pathologist_id');
1026 |     //         }
1027 | 
1028 |     //         if (!empty($mainPersonnelUpdates)) {
1029 |     //             foreach ($laboratoryRequest->tests()->where('test_category_id', $chemistryCategory->id)->get() as $requestTest) {
1030 |     //                 if (isset($affectedRequestTestPivots[$requestTest->id])) {
1031 |     //                     $pivotUpdates = array_merge($affectedRequestTestPivots[$requestTest->id], $mainPersonnelUpdates);
1032 |     //                 } else {
1033 |     //                     $pivotUpdates = $mainPersonnelUpdates;
1034 |     //                 }
1035 |     //                 $laboratoryRequest->tests()->updateExistingPivot($requestTest->id, $pivotUpdates);
1036 |     //             }
1037 |     //         } elseif (!empty($affectedRequestTestPivots)) {
1038 |     //              foreach ($affectedRequestTestPivots as $testIdToUpdate => $pivotDataOnlyStatus) {
1039 |     //                 $laboratoryRequest->tests()->updateExistingPivot($testIdToUpdate, $pivotDataOnlyStatus);
1040 |     //              }
1041 |     //         }
1042 | 
1043 |     //         // Update overall request status if all chemistry tests are now 'Saved' or further
1044 |     //         $this->updateOverallRequestStatus($laboratoryRequest, $chemistryCategory->id);
1045 | 
1046 |     //         DB::commit();
1047 |     //         return response()->json(['message' => 'Chemistry results saved successfully.', 'saved_ids' => $savedTestResultIds], 200);
1048 | 
1049 |     //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
1050 |     //         DB::rollBack();
1051 |     //         Log::error("Error saving Chemistry results: Category not found. " . $e->getMessage());
1052 |     //         return response()->json(['message' => 'Configuration error: Chemistry category not found.'], 500);
1053 |     //     } catch (\Exception $e) {
1054 |     //         DB::rollBack();
1055 |     //         Log::error("Error saving Chemistry results for lab request ID {$laboratoryRequest->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
1056 |     //         return response()->json(['message' => 'An error occurred while saving Chemistry results.'], 500);
1057 |     //     }
1058 |     // }
1059 | 
1060 |     // /**
1061 |     //  * Handle Print & Release for Chemistry results.
1062 |     //  *
1063 |     //  * @param  \\Illuminate\\Http\\Request  $request
1064 |     //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
1065 |     //  * @return \\Illuminate\\Http\\JsonResponse
1066 |     //  */
1067 |     // public function printReleaseChemistryResults(Request $request, LaboratoryRequest $laboratoryRequest)
1068 |     // {
1069 |     //     $validator = Validator::make($request->all(), [
1070 |     //         'test_result_ids' => 'required|array',
1071 |     //         'test_result_ids.*' => 'integer|exists:test_results,id',
1072 |     //         'performed_by_id' => 'required|integer|exists:users,id',
1073 |     //         'verified_by_id' => 'required|integer|exists:users,id',
1074 |     //         'pathologist_id' => 'nullable|integer|exists:users,id',
1075 |     //     ]);
1076 | 
1077 |     //     if ($validator->fails()) {
1078 |     //         return response()->json(['errors' => $validator->errors()], 422);
1079 |     //     }
1080 | 
1081 |     //     DB::beginTransaction();
1082 |     //     try {
1083 |     //         $chemistryCategoryName = 'Chemistry';
1084 |     //         $chemistryCategory = TestCategory::where('name', $chemistryCategoryName)->firstOrFail();
1085 |             
1086 |     //         $testResultIds = $request->input('test_result_ids');
1087 |     //         TestResult::whereIn('id', $testResultIds)
1088 |     //                     ->where('laboratory_request_id', $laboratoryRequest->id)
1089 |     //                     ->update(['status' => 'Printed']); 
1090 |             
1091 |     //         // Update pivot table for associated tests
1092 |     //         $affectedTestIds = TestResult::whereIn('id', $testResultIds)->pluck('test_id')->unique();
1093 |     //         $pivotUpdates = [
1094 |     //             'item_status' => 'Result Printed',
1095 |     //             'perform_by' => $request->input('performed_by_id'),
1096 |     //             'perform_at' => now(), // Or use existing perform_at if already set and not re-assigning performer
1097 |     //             'verify_by' => $request->input('verified_by_id'),
1098 |     //             'verify_at' => now(),
1099 |     //         ];
1100 |     //         if ($request->filled('pathologist_id')) {
1101 |     //             $pivotUpdates['pathologist_id'] = $request->input('pathologist_id');
1102 |     //         }
1103 |     //         $laboratoryRequest->tests()->whereIn('tests.id', $affectedTestIds)->where('test_category_id', $chemistryCategory->id)->updateExistingPivot($affectedTestIds, $pivotUpdates);
1104 | 
1105 |     //         $this->updateOverallRequestStatus($laboratoryRequest, $chemistryCategory->id);
1106 |             
1107 |     //         // TODO: PDF Generation Logic here
1108 |     //         // $pdf = generateChemistryReport($laboratoryRequest, $testResultIds);
1109 |     //         // return $pdf->download('chemistry-report-'.$laboratoryRequest->id.'.pdf');
1110 | 
1111 |     //         DB::commit();
1112 |     //         // For now, returning a success message. Frontend expects blob for auto-print.
1113 |     //         // This needs to be changed to return a PDF stream once PDF generation is implemented.
1114 |     //         // Create a dummy PDF blob for now to match frontend expectation
1115 |     //         $dummyPdfContent = "Dummy PDF content for request " . $laboratoryRequest->id;
1116 |     //         return response($dummyPdfContent, 200)
1117 |     //             ->header('Content-Type', 'application/pdf')
1118 |     //             ->header('Content-Disposition', 'inline; filename="chemistry-report-'.$laboratoryRequest->id.'.pdf"');
1119 | 
1120 |     //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
1121 |     //         DB::rollBack();
1122 |     //         Log::error("Error printing Chemistry results: Category not found. " . $e->getMessage());
1123 |     //         return response()->json(['message' => 'Configuration error: Chemistry category not found.'], 500);
1124 |     //     } catch (\Exception $e) {
1125 |     //         DB::rollBack();
1126 |     //         Log::error("Error printing Chemistry results for lab request ID {$laboratoryRequest->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
1127 |     //         return response()->json(['message' => 'An error occurred while printing Chemistry results.'], 500);
1128 |     //     }
1129 |     // }
1130 | 
1131 |     // /**
1132 |     //  * Handle Validate & Release for Chemistry results.
1133 |     //  *
1134 |     //  * @param  \\Illuminate\\Http\\Request  $request
1135 |     //  * @param  \\App\\Models\\LaboratoryRequest  $laboratoryRequest
1136 |     //  * @return \\Illuminate\\Http\\JsonResponse
1137 |     //  */
1138 |     // public function validateReleaseChemistryResults(Request $request, LaboratoryRequest $laboratoryRequest)
1139 |     // {
1140 |     //     $validator = Validator::make($request->all(), [
1141 |     //         'test_result_ids' => 'required|array',
1142 |     //         'test_result_ids.*' => 'integer|exists:test_results,id',
1143 |     //         'performed_by_id' => 'required|integer|exists:users,id',
1144 |     //         'verified_by_id' => 'required|integer|exists:users,id',
1145 |     //         'pathologist_id' => 'nullable|integer|exists:users,id',
1146 |     //     ]);
1147 | 
1148 |     //     if ($validator->fails()) {
1149 |     //         return response()->json(['errors' => $validator->errors()], 422);
1150 |     //     }
1151 | 
1152 |     //     DB::beginTransaction();
1153 |     //     try {
1154 |     //         $chemistryCategoryName = 'Chemistry';
1155 |     //         $chemistryCategory = TestCategory::where('name', $chemistryCategoryName)->firstOrFail();
1156 | 
1157 |     //         $testResultIds = $request->input('test_result_ids');
1158 |             
1159 |     //         // Check for QC lockout before releasing results
1160 |     //         $testResults = TestResult::whereIn('id', $testResultIds)
1161 |     //                     ->where('laboratory_request_id', $laboratoryRequest->id)
1162 |     //                     ->with(['testParameter', 'equipment'])
1163 |     //                     ->get();
1164 | 
1165 |     //         Log::info('QC Lockout Check - Test Result IDs:', $testResultIds);
1166 |     //         Log::info('QC Lockout Check - Found results count: ' . $testResults->count());
1167 | 
1168 |     //         $qcLockoutService = new QualityControlLockoutService();
1169 |     //         $lockedParameters = [];
1170 |             
1171 |     //         foreach ($testResults as $result) {
1172 |     //             Log::info("QC Lockout Check - Result ID: {$result->id}, Equipment: {$result->equipment_id}, Parameter: {$result->test_parameter_id}");
1173 |                 
1174 |     //             if ($result->equipment_id && $result->test_parameter_id) {
1175 |     //                 $qcStatus = $qcLockoutService->checkQCStatus($result->equipment_id, $result->test_parameter_id);
1176 |     //                 Log::info("QC Lockout Check - QC Status:", ['is_in_control' => $qcStatus['is_in_control'], 'violations' => $qcStatus['violations']]);
1177 |                     
1178 |     //                 if (!$qcStatus['is_in_control']) {
1179 |     //                     $lockedParameters[] = [
1180 |     //                         'parameter' => $result->testParameter->test_name ?? 'Unknown',
1181 |     //                         'violations' => implode(', ', $qcStatus['violations'])
1182 |     //                     ];
1183 |     //                 }
1184 |     //             }
1185 |     //         }
1186 |             
1187 |     //         Log::info('QC Lockout Check - Locked Parameters Count: ' . count($lockedParameters));
1188 | 
1189 |     //         // If QC is out of control, block the release
1190 |     //         if (!empty($lockedParameters)) {
1191 |     //             // Mark affected test results as locked
1192 |     //             foreach ($testResults as $result) {
1193 |     //                 if ($result->equipment_id && $result->test_parameter_id) {
1194 |     //                     $qcStatus = $qcLockoutService->checkQCStatus($result->equipment_id, $result->test_parameter_id);
1195 |     //                     if (!$qcStatus['is_in_control']) {
1196 |     //                         $result->qc_lockout_active = true;
1197 |     //                         $result->qc_lockout_date = now();
1198 |     //                         $result->requires_review = true;
1199 |     //                         $result->save();
1200 |     //                     }
1201 |     //                 }
1202 |     //             }
1203 |                 
1204 |     //             DB::rollBack();
1205 |     //             return response()->json([
1206 |     //                 'message' => 'Quality Control lockout active. Results cannot be released.',
1207 |     //                 'locked_parameters' => $lockedParameters,
1208 |     //                 'error_type' => 'qc_lockout'
1209 |     //             ], 403);
1210 |     //         }
1211 | 
1212 |     //         // Run CDS correlation rules for ALL categories (generic engine)
1213 |     //         $cdsViolation = $this->runCdsChecks($laboratoryRequest, $testResults);
1214 |     //         if ($cdsViolation !== null) {
1215 |     //             DB::rollBack();
1216 |     //             return response()->json([
1217 |     //                 'message' => $cdsViolation['message'] ?? 'CDS rule triggered. Auto-verification stopped.',
1218 |     //                 'rule' => $cdsViolation['rule'] ?? null,
1219 |     //                 'action' => $cdsViolation['action'] ?? 'STOP_AUTO_VERIFY',
1220 |     //                 'error_type' => 'cds_block'
1221 |     //             ], 409);
1222 |     //         }
1223 | 
1224 |     //         // QC is in control and CDS passed, proceed with validation
1225 |     //         TestResult::whereIn('id', $testResultIds)
1226 |     //                     ->where('laboratory_request_id', $laboratoryRequest->id)
1227 |     //                     ->update(['status' => 'Validated']);
1228 |             
1229 |     //         $affectedTestIds = TestResult::whereIn('id', $testResultIds)->pluck('test_id')->unique();
1230 |     //         $pivotUpdates = [
1231 |     //             'item_status' => 'Result Validated',
1232 |     //             'perform_by' => $request->input('performed_by_id'),
1233 |     //             'perform_at' => now(), // Or use existing perform_at
1234 |     //             'verify_by' => $request->input('verified_by_id'),
1235 |     //             'verify_at' => now(),
1236 |     //         ];
1237 |     //         if ($request->filled('pathologist_id')) {
1238 |     //             $pivotUpdates['pathologist_id'] = $request->input('pathologist_id');
1239 |     //         }
1240 |     //         $laboratoryRequest->tests()->whereIn('tests.id', $affectedTestIds)->where('test_category_id', $chemistryCategory->id)->updateExistingPivot($affectedTestIds, $pivotUpdates);
1241 | 
1242 |     //         $this->updateOverallRequestStatus($laboratoryRequest, $chemistryCategory->id);
1243 | 
1244 |     //         DB::commit();
1245 |     //         return response()->json(['message' => 'Chemistry results validated successfully.'], 200);
1246 | 
1247 |     //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
1248 |     //         DB::rollBack();
1249 |     //         Log::error("Error validating Chemistry results: Category not found. " . $e->getMessage());
1250 |     //         return response()->json(['message' => 'Configuration error: Chemistry category not found.'], 500);
1251 |     //     } catch (\Exception $e) {
1252 |     //         DB::rollBack();
1253 |     //         Log::error("Error validating Chemistry results for lab request ID {$laboratoryRequest->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
1254 |     //         return response()->json(['message' => 'An error occurred while validating Chemistry results.'], 500);
1255 |     //     }
1256 |     // }
1257 | 
1258 |     /**
1259 |      * Helper function to update the overall status of a LaboratoryRequest 
1260 |      * based on the status of its tests within a specific category.
1261 |      */
1262 |     private function updateOverallRequestStatus(LaboratoryRequest $laboratoryRequest, $categoryId)
1263 |     {
1264 |         $allCategoryTests = $laboratoryRequest->tests()->where('test_category_id', $categoryId)->get();
1265 |         if ($allCategoryTests->isEmpty()) {
1266 |             return; // No tests in this category for this request
1267 |         }
1268 | 
1269 |         $allValidated = $allCategoryTests->every(function ($test) {
1270 |             return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
1271 |         });
1272 | 
1273 |         $allPrinted = $allCategoryTests->every(function ($test) {
1274 |             return $test->pivot->item_status === 'Result Printed';
1275 |         });
1276 | 
1277 |         $newOverallStatus = null; // Overall status is now derived from pivot item_status only
1278 | 
1279 |         if ($allPrinted) {
1280 |             $newOverallStatus = 'Printed';
1281 |         } elseif ($allValidated) {
1282 |             $newOverallStatus = 'Validated';
1283 |         } else {
1284 |             // Check if any are still pending or processing to determine intermediate status
1285 |             $hasProcessing = $allCategoryTests->some(fn($test) => in_array($test->pivot->item_status, ['Processing Results', 'Checked-in', 'Accepted', 'Pending Sample']));
1286 |             if ($hasProcessing) {
1287 |                  // This part might need more granular logic based on your exact overall status flow
1288 |                  // For now, if not all validated/printed but some are processing, it might remain 'Processing' or similar
1289 |                  // Or, if the frontend status for Chemistry is derived (like in getChemistryRequests), this overall LaboratoryRequest.status might be more global.
1290 |                  // Let's assume for now that if any Chemistry test is being processed, the overall status is at least 'Processing' (if not already 'Validated' or 'Printed').
1291 |                  if(!in_array($laboratoryRequest->status, ['Validated', 'Printed'])){
1292 |                     // $newOverallStatus = 'Processing'; // Example status
1293 |                  }
1294 |             }
1295 |         }
1296 |         
1297 |         // Do not persist overall status to laboratory_requests table; frontend derives per-category status from pivot
1298 |     }
1299 | 
1300 |     /**
1301 |      * Execute CDS engine for a set of results within a request.
1302 |      * Returns an array with info if a blocking action is required, otherwise null.
1303 |      */
1304 |     private function runCdsChecks(LaboratoryRequest $laboratoryRequest, $testResults): ?array
1305 |     {
1306 |         try {
1307 |             $parameterIds = $testResults->pluck('test_parameter_id')->filter()->unique()->values()->toArray();
1308 |             if (empty($parameterIds)) {
1309 |                 return null;
1310 |             }
1311 | 
1312 |             $rules = CdsCorrelationRule::active()
1313 |                 ->whereIn('trigger_test_id', $parameterIds)
1314 |                 ->get();
1315 | 
1316 |             if ($rules->isEmpty()) {
1317 |                 return null;
1318 |             }
1319 | 
1320 |             $engine = new CdsRuleEngine();
1321 |             foreach ($rules as $rule) {
1322 |                 $correlated = collect(explode(',', (string)$rule->correlated_tests))
1323 |                     ->filter()
1324 |                     ->map(fn($v) => (int)trim($v))
1325 |                     ->values()
1326 |                     ->toArray();
1327 | 
1328 |                 $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $parameterIds)));
1329 |                 $fires = $engine->evaluate((array)$rule->rule_logic_json, $laboratoryRequest, $needed);
1330 |                 if ($fires) {
1331 |                     if ($rule->action_type === 'STOP_AUTO_VERIFY') {
1332 |                         return [
1333 |                             'action' => 'STOP_AUTO_VERIFY',
1334 |                             'message' => $rule->alert_message ?? 'CDS rule triggered. Review required.',
1335 |                             'rule' => $rule,
1336 |                         ];
1337 |                     }
1338 |                     if ($rule->action_type === 'REFLEX_TEST') {
1339 |                         // Check if already acknowledged/created
1340 |                         $existingReflex = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
1341 |                             ->where('rule_type', 'CDS')
1342 |                             ->where('rule_id', $rule->id)
1343 |                             ->where('decision', 'reflex_created')
1344 |                             ->exists();
1345 |                         
1346 |                         if (!$existingReflex) {
1347 |                             // Return special response indicating reflex test is needed
1348 |                             return [
1349 |                                 'action' => 'REFLEX_TEST',
1350 |                                 'message' => $rule->alert_message ?? 'CDS suggests a reflex test.',
1351 |                                 'rule' => $rule,
1352 |                                 'requires_approval' => true, // Frontend should show approval dialog
1353 |                                 'reflex_test_id' => $rule->reflex_test_id,
1354 |                             ];
1355 |                         }
1356 |                         // Already created, don't block
1357 |                         return null;
1358 |                     }
1359 |                     if ($rule->action_type === 'ADD_COMMENT') {
1360 |                         // Not blocking; could append comments to results in future.
1361 |                         // Continue without blocking.
1362 |                         continue;
1363 |                     }
1364 |                 }
1365 |             }
1366 |         } catch (\Throwable $e) {
1367 |             Log::error('CDS Engine error: '.$e->getMessage());
1368 |             // Fail-open: do not block release if engine fails
1369 |             return null;
1370 |         }
1371 | 
1372 |         return null;
1373 |     }
1374 | 
1375 |     /**
1376 |      * Record a rule decision audit entry.
1377 |      *
1378 |      * @param array $data
1379 |      * @return void
1380 |      */
1381 |     private function recordRuleDecision(array $data): void
1382 |     {
1383 |         RuleDecisionAudit::create([
1384 |             'rule_type' => $data['rule_type'] ?? null,
1385 |             'decision' => $data['decision'] ?? null,
1386 |             'message' => $data['message'] ?? null,
1387 |             'rule_id' => $data['rule_id'] ?? null,
1388 |             'policy' => $data['policy'] ?? null,
1389 |             'laboratory_request_id' => $data['laboratory_request_id'] ?? null,
1390 |             'test_result_id' => $data['test_result_id'] ?? null,
1391 |             'test_parameter_id' => $data['test_parameter_id'] ?? null,
1392 |             'test_id' => $data['test_id'] ?? null,
1393 |             'user_id' => $data['user_id'] ?? null,
1394 |             'supervisor_id' => $data['supervisor_id'] ?? null,
1395 |             'context' => $data['context'] ?? null,
1396 |         ]);
1397 |     }
1398 | 
1399 |     /**
1400 |      * Approve and create reflex test(s)
1401 |      * POST /api/laboratory-requests/{id}/approve-reflex-test
1402 |      *
1403 |      * @param Request $request
1404 |      * @param LaboratoryRequest $laboratoryRequest
1405 |      * @return \Illuminate\Http\JsonResponse
1406 |      */
1407 |     public function approveReflexTest(Request $request, LaboratoryRequest $laboratoryRequest)
1408 |     {
1409 |         $validated = $request->validate([
1410 |             'rule_id' => 'required|integer|exists:cds_correlation_rules,id',
1411 |             'reflex_test_id' => 'required|integer|exists:test_parameters,id',
1412 |             'auto_create' => 'boolean',
1413 |         ]);
1414 | 
1415 |         $rule = CdsCorrelationRule::findOrFail($validated['rule_id']);
1416 |         $user = Auth::user();
1417 |         $service = new ReflexTestService();
1418 | 
1419 |         try {
1420 |             DB::beginTransaction();
1421 | 
1422 |             // Create the reflex test(s)
1423 |             $createdRequests = $service->createReflexTests(
1424 |                 $laboratoryRequest,
1425 |                 $rule,
1426 |                 $user,
1427 |                 $validated['auto_create'] ?? false
1428 |             );
1429 | 
1430 |             // Record the approval decision
1431 |             $this->recordRuleDecision([
1432 |                 'rule_type' => 'CDS',
1433 |                 'decision' => 'reflex_created',
1434 |                 'rule_id' => $rule->id,
1435 |                 'message' => "Reflex test approved. Created " . count($createdRequests) . " request(s).",
1436 |                 'laboratory_request_id' => $laboratoryRequest->id,
1437 |                 'user_id' => $user->id,
1438 |                 'context' => [
1439 |                     'reflex_requests' => collect($createdRequests)->pluck('id')->toArray(),
1440 |                     'reflex_test_id' => $validated['reflex_test_id'],
1441 |                 ]
1442 |             ]);
1443 | 
1444 |             DB::commit();
1445 | 
1446 |             return response()->json([
1447 |                 'message' => 'Reflex test(s) created successfully',
1448 |                 'reflex_requests' => $createdRequests,
1449 |             ]);
1450 | 
1451 |         } catch (\Exception $e) {
1452 |             DB::rollBack();
1453 |             Log::error('Reflex test creation failed: ' . $e->getMessage());
1454 |             return response()->json([
1455 |                 'message' => 'Failed to create reflex test: ' . $e->getMessage()
1456 |             ], 500);
1457 |         }
1458 |     }
1459 | 
1460 |     /**
1461 |      * Deny reflex test suggestion
1462 |      * POST /api/laboratory-requests/{id}/deny-reflex-test
1463 |      *
1464 |      * @param Request $request
1465 |      * @param LaboratoryRequest $laboratoryRequest
1466 |      * @return \Illuminate\Http\JsonResponse
1467 |      */
1468 |     public function denyReflexTest(Request $request, LaboratoryRequest $laboratoryRequest)
1469 |     {
1470 |         $validated = $request->validate([
1471 |             'rule_id' => 'required|integer|exists:cds_correlation_rules,id',
1472 |             'reason' => 'nullable|string|max:500',
1473 |         ]);
1474 | 
1475 |         $user = Auth::user();
1476 | 
1477 |         $this->recordRuleDecision([
1478 |             'rule_type' => 'CDS',
1479 |             'decision' => 'reflex_denied',
1480 |             'rule_id' => $validated['rule_id'],
1481 |             'message' => $validated['reason'] ?? 'Reflex test denied by user',
1482 |             'laboratory_request_id' => $laboratoryRequest->id,
1483 |             'user_id' => $user->id,
1484 |             'context' => ['denied_at' => now()->toDateTimeString()]
1485 |         ]);
1486 | 
1487 |         return response()->json(['message' => 'Reflex test denied']);
1488 |     }
1489 | 
1490 |     /**
1491 |      * Check CDS rules for a laboratory request and return all triggered rules.
1492 |      * This endpoint is used by the frontend to display CDS alerts.
1493 |      *
1494 |      * @param LaboratoryRequest $laboratoryRequest
1495 |      * @return \Illuminate\Http\JsonResponse
1496 |      */
1497 |     public function checkCdsRules(LaboratoryRequest $laboratoryRequest)
1498 |     {
1499 |         try {
1500 |             // Get all test results for this request
1501 |             $testResults = TestResult::where('laboratory_request_id', $laboratoryRequest->id)
1502 |                 ->whereNotNull('test_parameter_id')
1503 |                 ->whereNotNull('result_value')
1504 |                 ->get();
1505 | 
1506 |             if ($testResults->isEmpty()) {
1507 |                 return response()->json([
1508 |                     'triggered_rules' => [],
1509 |                     'has_blocking_rules' => false
1510 |                 ]);
1511 |             }
1512 | 
1513 |             $parameterIds = $testResults->pluck('test_parameter_id')->filter()->unique()->values()->toArray();
1514 | 
1515 |             // Get all active CDS rules that might be triggered by these parameters
1516 |             $rules = CdsCorrelationRule::active()
1517 |                 ->whereIn('trigger_test_id', $parameterIds)
1518 |                 ->get();
1519 | 
1520 |             if ($rules->isEmpty()) {
1521 |                 return response()->json([
1522 |                     'triggered_rules' => [],
1523 |                     'has_blocking_rules' => false
1524 |                 ]);
1525 |             }
1526 | 
1527 |             $engine = new CdsRuleEngine();
1528 |             $triggeredRules = [];
1529 |             $hasBlockingRules = false;
1530 | 
1531 |             // Check if this request has already been acknowledged for CDS rules
1532 |             $isAcknowledged = RuleDecisionAudit::where('laboratory_request_id', $laboratoryRequest->id)
1533 |                 ->where('rule_type', 'CDS')
1534 |                 ->where('decision', 'acknowledged')
1535 |                 ->exists();
1536 | 
1537 |             foreach ($rules as $rule) {
1538 |                 $correlated = collect(explode(',', (string)$rule->correlated_tests))
1539 |                     ->filter()
1540 |                     ->map(fn($v) => (int)trim($v))
1541 |                     ->values()
1542 |                     ->toArray();
1543 | 
1544 |                 $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $parameterIds)));
1545 |                 $fires = $engine->evaluate((array)$rule->rule_logic_json, $laboratoryRequest, $needed);
1546 | 
1547 |                 if ($fires) {
1548 |                     $isBlocking = in_array($rule->action_type, ['STOP_AUTO_VERIFY', 'REFLEX_TEST']);
1549 |                     
1550 |                     // If it's blocking but acknowledged, we still include it in triggered_rules
1551 |                     // but we don't mark the whole request as blocked.
1552 |                     if ($isBlocking && !$isAcknowledged) {
1553 |                         $hasBlockingRules = true;
1554 |                     }
1555 | 
1556 |                     $triggeredRules[] = [
1557 |                         'id' => $rule->id,
1558 |                         'rule_name' => $rule->rule_name,
1559 |                         'alert_message' => $rule->alert_message ?? 'CDS rule triggered.',
1560 |                         'action_type' => $rule->action_type,
1561 |                         'is_blocking' => $isBlocking,
1562 |                         'is_acknowledged' => $isAcknowledged, // Pass this info to frontend
1563 |                         'trigger_test_id' => $rule->trigger_test_id,
1564 |                         'reflex_test_id' => $rule->reflex_test_id,
1565 |                     ];
1566 |                 }
1567 |             }
1568 | 
1569 |             return response()->json([
1570 |                 'triggered_rules' => $triggeredRules,
1571 |                 'has_blocking_rules' => $hasBlockingRules,
1572 |                 'is_acknowledged' => $isAcknowledged
1573 |             ]);
1574 | 
1575 |         } catch (\Throwable $e) {
1576 |             Log::error('CDS Check endpoint error: '.$e->getMessage());
1577 |             return response()->json([
1578 |                 'triggered_rules' => [],
1579 |                 'has_blocking_rules' => false,
1580 |                 'error' => 'Error checking CDS rules'
1581 |             ], 500);
1582 |         }
1583 |     }
1584 | 
1585 |     /**
1586 |      * Bulk check rules (CDS, Delta, Critical) for multiple laboratory requests
1587 |      *
1588 |      * @param  \Illuminate\Http\Request  $request
1589 |      * @return \Illuminate\Http\JsonResponse
1590 |      */
1591 |     public function bulkCheckRules(Request $request)
1592 |     {
1593 |         try {
1594 |             $requestIds = $request->input('request_ids', []);
1595 |             if (empty($requestIds)) {
1596 |                 return response()->json(['results' => []]);
1597 |             }
1598 | 
1599 |             $laboratoryRequests = LaboratoryRequest::whereIn('id', $requestIds)->get();
1600 |             $results = [];
1601 |             $engine = new CdsRuleEngine();
1602 | 
1603 |             // Pre-fetch all active CDS rules to avoid redundant queries
1604 |             $allActiveRules = CdsCorrelationRule::active()->get();
1605 | 
1606 |             // Pre-fetch all acknowledgments for these request IDs (CDS, Delta, Critical)
1607 |             $allAcks = RuleDecisionAudit::whereIn('laboratory_request_id', $requestIds)
1608 |                 ->where('decision', 'acknowledged')
1609 |                 ->get()
1610 |                 ->groupBy('laboratory_request_id');
1611 | 
1612 |             foreach ($laboratoryRequests as $laboratoryRequest) {
1613 |                 $acks = $allAcks->get($laboratoryRequest->id, collect());
1614 |                 $isCdsAcknowledged = $acks->where('rule_type', 'CDS')->isNotEmpty();
1615 |                 $isDeltaAcknowledged = $acks->where('rule_type', 'DELTA_CHECK')->isNotEmpty();
1616 |                 $isCriticalAcknowledged = $acks->where('rule_type', 'CRITICAL_VALUE')->isNotEmpty();
1617 | 
1618 |                 $testResults = TestResult::where('laboratory_request_id', $laboratoryRequest->id)
1619 |                     ->whereNotNull('test_parameter_id')
1620 |                     ->whereNotNull('result_value')
1621 |                     ->get();
1622 | 
1623 |                 // 1. CDS Rule Checks
1624 |                 $cdsResults = [
1625 |                     'triggered_rules' => [],
1626 |                     'has_blocking_rules' => false,
1627 |                     'is_acknowledged' => $isCdsAcknowledged
1628 |                 ];
1629 | 
1630 |                 if (!$testResults->isEmpty()) {
1631 |                     $parameterIds = $testResults->pluck('test_parameter_id')->filter()->unique()->values()->toArray();
1632 |                     $relevantRules = $allActiveRules->filter(fn($rule) => in_array($rule->trigger_test_id, $parameterIds));
1633 | 
1634 |                     foreach ($relevantRules as $rule) {
1635 |                         $correlated = collect(explode(',', (string)$rule->correlated_tests))
1636 |                             ->filter()
1637 |                             ->map(fn($v) => (int)trim($v))
1638 |                             ->values()
1639 |                             ->toArray();
1640 | 
1641 |                         $needed = array_values(array_unique(array_merge([$rule->trigger_test_id], $correlated, $parameterIds)));
1642 |                         if ($engine->evaluate((array)$rule->rule_logic_json, $laboratoryRequest, $needed)) {
1643 |                             $isBlocking = in_array($rule->action_type, ['STOP_AUTO_VERIFY', 'REFLEX_TEST']);
1644 |                             if ($isBlocking && !$isCdsAcknowledged) {
1645 |                                 $cdsResults['has_blocking_rules'] = true;
1646 |                             }
1647 | 
1648 |                             $cdsResults['triggered_rules'][] = [
1649 |                                 'id' => $rule->id,
1650 |                                 'rule_name' => $rule->rule_name,
1651 |                                 'alert_message' => $rule->alert_message ?? 'CDS rule triggered.',
1652 |                                 'action_type' => $rule->action_type,
1653 |                                 'is_blocking' => $isBlocking,
1654 |                                 'is_acknowledged' => $isCdsAcknowledged,
1655 |                                 'trigger_test_id' => $rule->trigger_test_id,
1656 |                                 'reflex_test_id' => $rule->reflex_test_id,
1657 |                             ];
1658 |                         }
1659 |                     }
1660 |                 }
1661 | 
1662 |                 // 2. Delta Check & Critical Value info (basic presence check)
1663 |                 $results[$laboratoryRequest->id] = [
1664 |                     'cds' => $cdsResults,
1665 |                     'delta' => [
1666 |                         'is_acknowledged' => $isDeltaAcknowledged,
1667 |                         // Delta flags are usually fetched separately by CategoryResults.vue via loadDeltaFlags, 
1668 |                         // but we provide acknowledgment status here
1669 |                     ],
1670 |                     'critical' => [
1671 |                         'is_acknowledged' => $isCriticalAcknowledged
1672 |                     ]
1673 |                 ];
1674 |             }
1675 | 
1676 |             return response()->json([
1677 |                 'results' => $results
1678 |             ]);
1679 | 
1680 |         } catch (\Throwable $e) {
1681 |             Log::error('Bulk Rules Check error: '.$e->getMessage());
1682 |             return response()->json([
1683 |                 'error' => 'Error performing bulk rules check',
1684 |                 'message' => $e->getMessage()
1685 |             ], 500);
1686 |         }
1687 |     }
1688 | 
1689 |     /**
1690 |      * Bulk check CDS rules for multiple laboratory requests
1691 |      * (Deprecated: Use bulkCheckRules instead)
1692 |      *
1693 |      * @param  \Illuminate\Http\Request  $request
1694 |      * @return \Illuminate\Http\JsonResponse
1695 |      */
1696 |     public function bulkCheckCdsRules(Request $request)
1697 |     {
1698 |         return $this->bulkCheckRules($request);
1699 |     }
1700 | 
1701 |     /**
1702 |      * Get dashboard statistics for result management
1703 |      * Returns count per status per test category
1704 |      */
1705 |     public function getDashboardStats(Request $request)
1706 |     {
1707 |         try {
1708 |             $categories = TestCategory::withCount('tests')->get(['id', 'name']);
1709 |             $stats = [];
1710 |             
1711 |             Log::info('Dashboard stats - Found categories: ' . $categories->count());
1712 |             
1713 |             foreach ($categories as $category) {
1714 |                 // Get all requests with tests from this category, including the tests relationship
1715 |                 $requests = LaboratoryRequest::whereHas('tests', function ($q) use ($category) {
1716 |                     $q->where('test_category_id', $category->id);
1717 |                 })->with(['tests' => function ($testQuery) use ($category) {
1718 |                     $testQuery->where('test_category_id', $category->id)
1719 |                               ->withPivot('item_status');
1720 |                 }])->get();
1721 |                 
1722 |                 // Count by status
1723 |                 $stats[$category->id] = [
1724 |                     'total' => 0,
1725 |                     'pending' => 0,
1726 |                     'processing' => 0,
1727 |                     'validated' => 0,
1728 |                     'released' => 0
1729 |                 ];
1730 |                 
1731 |                 Log::info("Category {$category->name} (ID: {$category->id}) - Found {$requests->count()} requests");
1732 |                 
1733 |                 foreach ($requests as $request) {
1734 |                     $categoryTests = $request->tests->where('test_category_id', $category->id);
1735 |                     
1736 |                     if ($categoryTests->count() > 0) {
1737 |                         $stats[$category->id]['total']++;
1738 |                         
1739 |                         // Determine the overall status for this request in this category
1740 |                         $hasValidated = $categoryTests->contains(function ($test) {
1741 |                             return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
1742 |                         });
1743 |                         $hasPrinted = $categoryTests->contains(function ($test) {
1744 |                             return $test->pivot->item_status === 'Result Printed';
1745 |                         });
1746 |                         $hasAccepted = $categoryTests->contains(function ($test) {
1747 |                             return in_array($test->pivot->item_status, ['Accepted', 'Collected', 'Send-out', 'Parked', 'Pending']);
1748 |                         });
1749 |                         $hasResults = $categoryTests->contains(function ($test) {
1750 |                             return $test->pivot->item_status === 'Processing Results';
1751 |                         });
1752 |                         $hasCheckedIn = $categoryTests->contains(function ($test) {
1753 |                             return $test->pivot->item_status === 'Checked-in';
1754 |                         });
1755 |                         
1756 |                         // Count based on the overall request status for this category
1757 |                         if($hasPrinted) {
1758 |                             $stats[$category->id]['released']++;
1759 |                         } elseif ($hasValidated) {
1760 |                             $stats[$category->id]['validated']++;
1761 |                         } elseif ($hasAccepted) {
1762 |                             $stats[$category->id]['pending']++;
1763 |                         } elseif ($hasCheckedIn) {
1764 |                             $stats[$category->id]['processing']++;
1765 |                         } elseif ($hasResults) {
1766 |                             $stats[$category->id]['processing']++;
1767 |                         }
1768 |                     }
1769 |                 }
1770 |             }
1771 |             
1772 |             Log::info('Dashboard stats result: ' . json_encode($stats));
1773 |             return response()->json($stats);
1774 |         } catch (\Exception $e) {
1775 |             Log::error("Error fetching dashboard stats: " . $e->getMessage());
1776 |             return response()->json(['message' => 'Error fetching dashboard stats'], 500);
1777 |         }
1778 |     }
1779 |     
1780 |     /**
1781 |      * Get turnaround time data per test category
1782 |      */
1783 |     public function getTatData(Request $request)
1784 |     {
1785 |         try {
1786 |             $categories = TestCategory::all(['id', 'name']);
1787 |             $tatData = [];
1788 |             
1789 |             foreach ($categories as $category) {
1790 |                 $requests = LaboratoryRequest::whereHas('tests', function ($q) use ($category) {
1791 |                     $q->where('test_category_id', $category->id);
1792 |                 })
1793 |                 ->whereNotNull('updated_at') // Use updated_at instead of completed_at
1794 |                 ->get();
1795 |                 
1796 |                 if ($requests->count() === 0) {
1797 |                     $tatData[] = [
1798 |                         'category_id' => $category->id,
1799 |                         'category_name' => $category->name,
1800 |                         'avg_tat' => 0
1801 |                     ];
1802 |                     continue;
1803 |                 }
1804 |                 
1805 |                 $totalMinutes = 0;
1806 |                 foreach ($requests as $request) {
1807 |                     if ($request->request_date && $request->updated_at) {
1808 |                         $minutes = \Carbon\Carbon::parse($request->updated_at)
1809 |                             ->diffInMinutes(\Carbon\Carbon::parse($request->request_date));
1810 |                         $totalMinutes += $minutes;
1811 |                     }
1812 |                 }
1813 |                 
1814 |                 $avgTat = $totalMinutes > 0 ? ($totalMinutes / $requests->count()) / 60 : 0; // Convert to hours
1815 |                 
1816 |                 $tatData[] = [
1817 |                     'category_id' => $category->id,
1818 |                     'category_name' => $category->name,
1819 |                     'avg_tat' => round($avgTat, 2)
1820 |                 ];
1821 |             }
1822 |             
1823 |             return response()->json($tatData);
1824 |         } catch (\Exception $e) {
1825 |             Log::error("Error fetching TAT data: " . $e->getMessage());
1826 |             return response()->json(['message' => 'Error fetching TAT data'], 500);
1827 |         }
1828 |     }
1829 |     
1830 |     /**
1831 |      * Get requests by category (generic endpoint for any category)
1832 |      */
1833 |     public function getCategoryRequests(Request $request, $categoryId)
1834 |     {
1835 |         try {
1836 |             $category = TestCategory::findOrFail($categoryId);
1837 |             $categoryName = $category->name;
1838 |             
1839 |             $query = LaboratoryRequest::query();
1840 |             
1841 |             $query->whereHas('tests', function ($testQuery) use ($categoryId) {
1842 |                 $testQuery->where('test_category_id', $categoryId)
1843 |                           ->whereIn('laboratory_request_test.item_status', ['Accepted', 'Checked-in', 'Processing Results', 'Result Validated', 'Result Printed', 'For Re-run', 'Rejected']);
1844 |             });
1845 |             
1846 |             $query->with([
1847 |                 'patient:id,last_name,first_name,middle_name,date_of_birth,gender,civil_status,address,phone,email',
1848 |                 'patientType:id,name',
1849 |                 'ward:id,name',
1850 |                 'room:id,name',
1851 |                 'doctor:id,name',
1852 |                 'tests' => function ($testQuery) use ($categoryId) {
1853 |                     $testQuery->where('test_category_id', $categoryId)
1854 |                               ->select('tests.id', 'tests.name')
1855 |                               ->withPivot('item_status')
1856 |                               ->with('testParameters:id,test_id');
1857 |                 }
1858 |             ]);
1859 |             
1860 |             if ($request->filled('search')) {
1861 |                 $searchTerm = $request->input('search');
1862 |                 $query->where(function ($q) use ($searchTerm) {
1863 |                     $q->where('id', 'like', "%{$searchTerm}%")
1864 |                       ->orWhere('sample_id', 'like', "%{$searchTerm}%")
1865 |                       ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
1866 |                           $patientQuery->where('last_name', 'like', "%{$searchTerm}%")
1867 |                                        ->orWhere('first_name', 'like', "%{$searchTerm}%");
1868 |                       });
1869 |                 });
1870 |             }
1871 |             
1872 |             $query->orderBy('priority_level', 'desc')
1873 |                   ->orderBy('request_date', 'asc');
1874 |             
1875 |             $perPage = $request->input('per_page', 15);
1876 |             $requests = $query->paginate($perPage);
1877 |             
1878 |             $requests->getCollection()->transform(function ($request) use ($categoryId, $categoryName) {
1879 |                 // Create normalized field names (lowercase with underscores)
1880 |                 $normalizedCategoryName = strtolower(str_replace(' ', '_', $categoryName));
1881 |                 $statusFieldName = $normalizedCategoryName . '_status';
1882 |                 $testListFieldName = $normalizedCategoryName . '_test_list';
1883 |                 
1884 |                 $request->{$testListFieldName} = $request->tests->pluck('name')->implode(', ');
1885 |                 
1886 |                 $hasValidated = $request->tests->contains(function ($test) {
1887 |                     return $test->pivot->item_status === 'Result Validated' || $test->pivot->item_status === 'Result Printed';
1888 |                 });
1889 |                 $hasPrinted = $request->tests->contains(function ($test) {
1890 |                     return $test->pivot->item_status === 'Result Printed';
1891 |                 });
1892 |                 $hasAccepted = $request->tests->contains(function ($test) {
1893 |                     return in_array($test->pivot->item_status, ['Accepted', 'Collected', 'Send-out', 'Parked']);
1894 |                 });
1895 |                 $hasResults = $request->tests->contains(function ($test) {
1896 |                     return $test->pivot->item_status === 'Processing Results';
1897 |                 });
1898 |                 $hasCheckedIn = $request->tests->contains(function ($test) {
1899 |                     return $test->pivot->item_status === 'Checked-in';
1900 |                 });
1901 |                 $hasForRerun = $request->tests->contains(function ($test) {
1902 |                     return $test->pivot->item_status === 'For Re-run';
1903 |                 });
1904 |                 $hasRejected = $request->tests->contains(function ($test) {
1905 |                     return $test->pivot->item_status === 'Rejected';
1906 |                 });
1907 |                 $allRejected = $request->tests->every(function ($test) {
1908 |                     return $test->pivot->item_status === 'Rejected';
1909 |                 });
1910 | 
1911 |                 if ($allRejected) {
1912 |                     $request->{$statusFieldName} = 'Rejected';
1913 |                 } elseif ($hasRejected) {
1914 |                     $request->{$statusFieldName} = 'Partially Rejected';
1915 |                 } elseif ($hasPrinted) {
1916 |                     $request->{$statusFieldName} = 'Released';
1917 |                 } elseif ($hasValidated) {
1918 |                     $request->{$statusFieldName} = 'Validated';
1919 |                 } elseif ($hasAccepted) {
1920 |                     $request->{$statusFieldName} = 'Pending Sample';
1921 |                 } elseif ($hasCheckedIn) {
1922 |                     $request->{$statusFieldName} = 'Checked-in';
1923 |                 } elseif ($hasResults) {
1924 |                     $request->{$statusFieldName} = 'Processing Results';
1925 |                 } elseif ($hasForRerun) {
1926 |                     $request->hematology_status = 'For Re-run';
1927 |                 } else {
1928 |                     $request->{$statusFieldName} = 'Unknown';
1929 |                 }
1930 |                 
1931 |                 foreach ($request->tests as $test) {
1932 |                     $test->parameter_count = TestParameter::where('test_id', $test->id)->count();
1933 |                     $test->completed_parameter_count = TestResult::where([
1934 |                         'laboratory_request_id' => $request->id,
1935 |                         'test_id' => $test->id,
1936 |                     ])->whereNotNull('test_parameter_id')->count();
1937 |                     
1938 |                     if ($test->parameter_count === 0) {
1939 |                         $test->parameter_count = 1;
1940 |                         $test->completed_parameter_count = TestResult::where([
1941 |                             'laboratory_request_id' => $request->id,
1942 |                             'test_id' => $test->id,
1943 |                             'test_parameter_id' => null,
1944 |                         ])->count();
1945 |                     }
1946 |                 }
1947 | 
1948 |                 // Attach distinct analyzer ids and names used in this request (if any)
1949 |                 try {
1950 |                     $equipmentIds = TestResult::where('laboratory_request_id', $request->id)
1951 |                         ->whereNotNull('equipment_id')
1952 |                         ->distinct()
1953 |                         ->pluck('equipment_id')
1954 |                         ->toArray();
1955 |                     $request->equipment_ids = $equipmentIds;
1956 |                     if (!empty($equipmentIds)) {
1957 |                         $names = DB::table('equipment')->whereIn('id', $equipmentIds)
1958 |                             ->get()
1959 |                             ->mapWithKeys(function($eq) {
1960 |                                 return [$eq->id => $eq->manufacturer . ' - ' . $eq->model];
1961 |                             });
1962 |                         $request->equipment_names = $names;
1963 |                     } else {
1964 |                         $request->equipment_names = [];
1965 |                     }
1966 |                 } catch (\Throwable $e) {
1967 |                     $request->equipment_ids = [];
1968 |                     $request->equipment_names = [];
1969 |                 }
1970 |                 
1971 |                 return $request;
1972 |             });
1973 |             
1974 |             return response()->json($requests);
1975 |             
1976 |         } catch (\Exception $e) {
1977 |             Log::error("Error fetching category requests: " . $e->getMessage());
1978 |             return response()->json(['message' => 'An error occurred while fetching category requests.'], 500);
1979 |         }
1980 |     }
1981 | 
1982 |     /**
1983 |      * Generic method to fetch detailed information for a single request by category ID.
1984 |      * This works for any category, not just hematology or chemistry.
1985 |      *
1986 |      * @param  \Illuminate\Http\Request  $request
1987 |      * @param  int  $categoryId
1988 |      * @param  \App\Models\LaboratoryRequest  $laboratoryRequest
1989 |      * @return \Illuminate\Http\JsonResponse
1990 |      */
1991 |     public function getCategoryRequestDetails(Request $request, $categoryId, LaboratoryRequest $laboratoryRequest)
1992 |     {
1993 |         try {
1994 |             // Find the category by ID
1995 |             $category = TestCategory::findOrFail($categoryId);
1996 |             $categoryId = $category->id; // Use the category ID from the found category
1997 |             
1998 |             \Log::info('⚡️ Starting getCategoryRequestDetails for laboratory request ID: ' . $laboratoryRequest->id . ' and category ID: ' . $categoryId);
1999 | 
2000 |             // 1. Load the core request data and RELATIONS, including filtered tests
[Tool]
File: backend/app/Models/LaboratoryRequest.php
  1 | <?php
  2 | 
  3 | namespace App\Models;
  4 | 
  5 | use Illuminate\Database\Eloquent\Factories\HasFactory;
  6 | use Illuminate\Database\Eloquent\Model;
  7 | use Illuminate\Database\Eloquent\Relations\BelongsTo;
  8 | use Illuminate\Database\Eloquent\Relations\BelongsToMany;
  9 | use Illuminate\Database\Eloquent\Relations\HasMany;
 10 | 
 11 | class LaboratoryRequest extends Model
 12 | {
 13 |     use HasFactory;
 14 | 
 15 |     protected $fillable = [
 16 |         'patient_id',
 17 |         'doctor_id',
 18 |         'sample_id',
 19 |         'ward_id',
 20 |         'room_id',
 21 |         'referring_doctor',
 22 |         'priority_level',
 23 |         'patient_type_id',
 24 |         'created_by',
 25 |         'request_date',
 26 |         'is_reflex',
 27 |         'reflex_parent_request_id',
 28 |         'reflex_triggered_by_rule_id',
 29 |     ];
 30 | 
 31 |     /**
 32 |      * The attributes that should be cast.
 33 |      *
 34 |      * @var array<string, string>
 35 |      */
 36 |     protected $casts = [
 37 |         'request_date' => 'datetime',
 38 |         'is_reflex' => 'boolean',
 39 |     ];
 40 | 
 41 |     /**
 42 |      * Get the patient that owns the laboratory request.
 43 |      */
 44 |     public function patient(): BelongsTo
 45 |     {
 46 |         return $this->belongsTo(Patient::class);
 47 |     }
 48 | 
 49 |     /**
 50 |      * Get the doctor associated with the laboratory request.
 51 |      */
 52 |     public function doctor(): BelongsTo
 53 |     {
 54 |         return $this->belongsTo(Doctor::class);
 55 |     }
 56 | 
 57 |     /**
 58 |      * The tests that belong to the laboratory request.
 59 |      */
 60 |     public function tests(): BelongsToMany
 61 |     {
 62 |         // Ensure the pivot table name matches your migration ('laboratory_request_test')
 63 |         return $this->belongsToMany(Test::class, 'laboratory_request_test')
 64 |                     ->using(LaboratoryRequestTest::class) // Use the custom pivot model
 65 |                     ->withPivot(
 66 |                         'id',
 67 |                         'item_status',
 68 |                         'price_at_request',
 69 |                         'rejection_reason',
 70 |                         'collected_at',
 71 |                         'accepted_at',
 72 |                         'rejected_at',
 73 |                         'remarks',
 74 |                         'comment',
 75 |                         'sendout_at',
 76 |                         'verify_at'
 77 |                     ) // Include tracking timestamps needed by reports
 78 |                     ->withTimestamps(); // If using timestamps on pivot
 79 |     }
 80 | 
 81 |     public function results()
 82 |     {
 83 |         return $this->hasMany(TestResult::class);
 84 |     }
 85 | 
 86 |     public function payment()
 87 |     {
 88 |         return $this->hasOne(Payment::class);
 89 |     }
 90 | 
 91 |     public function ward(): BelongsTo
 92 |     {
 93 |         return $this->belongsTo(Ward::class);
 94 |     }
 95 | 
 96 |     public function room(): BelongsTo
 97 |     {
 98 |         return $this->belongsTo(Room::class);
 99 |     }
100 | 
101 |     public function patientType(): BelongsTo
102 |     {
103 |         return $this->belongsTo(PatientType::class);
104 |     }
105 | 
106 |     public function creator(): BelongsTo
107 |     {
108 |         return $this->belongsTo(User::class, 'created_by');
109 |     }
110 | 
111 |     /**
112 |      * Get the parent request if this is a reflex test.
113 |      */
114 |     public function parentRequest(): BelongsTo
115 |     {
116 |         return $this->belongsTo(LaboratoryRequest::class, 'reflex_parent_request_id');
117 |     }
118 | 
119 |     /**
120 |      * Get the CDS rule that triggered this reflex request.
121 |      */
122 |     public function reflexTriggerRule(): BelongsTo
123 |     {
124 |         return $this->belongsTo(CdsCorrelationRule::class, 'reflex_triggered_by_rule_id');
125 |     }
126 | 
127 |     /**
128 |      * Get all reflex children of this request.
129 |      */
130 |     public function reflexRequests(): HasMany
131 |     {
132 |         return $this->hasMany(LaboratoryRequest::class, 'reflex_parent_request_id');
133 |     }
134 | }
135 | 
[Tool]
File: backend/app/Models/LaboratoryRequestTest.php
  1 | <?php
  2 | 
  3 | namespace App\Models;
  4 | 
  5 | use Illuminate\Database\Eloquent\Relations\Pivot;
  6 | 
  7 | class LaboratoryRequestTest extends Pivot
  8 | {
  9 |     /**
 10 |      * The table associated with the model.
 11 |      *
 12 |      * @var string
 13 |      */
 14 |     protected $table = 'laboratory_request_test';
 15 | 
 16 |     /**
 17 |      * Indicates if the IDs are auto-incrementing.
 18 |      *
 19 |      * @var bool
 20 |      */
 21 |     public $incrementing = true;
 22 | 
 23 |     /**
 24 |      * The attributes that are mass assignable.
 25 |      *
 26 |      * @var array<int, string>
 27 |      */
 28 |     protected $fillable = [
 29 |         'laboratory_request_id',
 30 |         'test_id',
 31 |         'item_status',
 32 |         'price_at_request',
 33 |         'rejection_reason',
 34 |         'remarks',
 35 |         'comment',
 36 |         // New tracking fields
 37 |         'checkin_by',
 38 |         'checkin_at',
 39 |         'sendout_by',
 40 |         'sendout_at',
 41 |         'park_by',
 42 |         'park_at',
 43 |         'perform_by',
 44 |         'perform_at',
 45 |         'verify_by',
 46 |         'verify_at',
 47 |         'pathologist_id',
 48 |         // Reflex tracking fields
 49 |         'is_reflex_test',
 50 |         'reflex_triggered_at',
 51 |         'reflex_triggered_by_rule_id',
 52 |     ];
 53 | 
 54 |     /**
 55 |      * The attributes that should be cast.
 56 |      *
 57 |      * @var array<string, string>
 58 |      */
 59 |     protected $casts = [
 60 |         'checkin_at' => 'datetime',
 61 |         'sendout_at' => 'datetime',
 62 |         'park_at' => 'datetime',
 63 |         'perform_at' => 'datetime',
 64 |         'verify_at' => 'datetime',
 65 |         'reflex_triggered_at' => 'datetime',
 66 |     ];
 67 | 
 68 |     public function laboratoryRequest()
 69 |     {
 70 |         return $this->belongsTo(LaboratoryRequest::class);
 71 |     }
 72 | 
 73 |     public function test()
 74 |     {
 75 |         return $this->belongsTo(Test::class);
 76 |     }
 77 | 
 78 |     // Relationships for user tracking
 79 |     public function checkinUser()
 80 |     {
 81 |         return $this->belongsTo(User::class, 'checkin_by');
 82 |     }
 83 | 
 84 |     public function sendoutUser()
 85 |     {
 86 |         return $this->belongsTo(User::class, 'sendout_by');
 87 |     }
 88 | 
 89 |     public function parkUser()
 90 |     {
 91 |         return $this->belongsTo(User::class, 'park_by');
 92 |     }
 93 | 
 94 |     public function performer()
 95 |     {
 96 |         return $this->belongsTo(User::class, 'perform_by');
 97 |     }
 98 | 
 99 |     public function verifier()
100 |     {
101 |         return $this->belongsTo(User::class, 'verify_by');
102 |     }
103 | 
104 |     public function pathologist()
105 |     {
106 |         return $this->belongsTo(User::class, 'pathologist_id');
107 |     }
108 | 
109 |     /**
110 |      * Get the CDS rule that triggered this reflex test.
111 |      */
112 |     public function reflexTriggerRule()
113 |     {
114 |         return $this->belongsTo(CdsCorrelationRule::class, 'reflex_triggered_by_rule_id');
115 |     }
116 | }
117 | 
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:26:48.974Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Read and analyze UniversalResultModal.vue | Pending |
| 2 | Read UniversalResultModal-Reflex-broken.vue for comparison | Pending |
| 3 | Read CdsAlertBanner.vue to understand existing alert system | Pending |
| 4 | Read backend controllers and models for reflex tracking | Pending |
| 5 | Read API documentation and testing guide | Pending |
| 6 | Identify all issues in current implementation | Pending |
| 7 | Design comprehensive solution | Pending |
| 8 | Implement centralized alert banner component | Pending |
| 9 | Update UniversalResultModal with proper reflex handling | Pending |
| 10 | Add isMounted guards to all async operations | Pending |
| 11 | Implement visual state management (red→blue/green→gray) | Pending |
| 12 | Add auto-dismiss with countdown timer | Pending |
| 13 | Ensure audit trail logging | Pending |
| 14 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 15 | Verify all functions remain intact | Pending |
| 16 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Let me read the API documentation and testing guide to understand the full requirements:
[Tool Use: read_file]
Path: REFLEX_TEST_API_DOCUMENTATION.md
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}
[Tool Use: read_file]
Path: REFLEX_TEST_TESTING_GUIDE.md
Mode: slice
Offset: 1
Limit: 2000
Indentation: {
  "anchor_line": 1,
  "max_levels": 0,
  "max_lines": 0,
  "include_siblings": false,
  "include_header": true
}

---

**User:**

[Tool]
File: REFLEX_TEST_API_DOCUMENTATION.md
  1 | # Reflex Test Workflow - API Documentation
  2 | 
  3 | ## Table of Contents
  4 | 1. [Introduction](#1-introduction)
  5 | 2. [Endpoints Reference](#2-endpoints-reference)
  6 | 3. [Data Models](#3-data-models)
  7 | 4. [Error Handling](#4-error-handling)
  8 | 5. [Rate Limiting](#5-rate-limiting)
  9 | 6. [Examples](#6-examples)
 10 | 7. [Frontend Integration Notes](#7-frontend-integration-notes)
 11 | 8. [Testing](#8-testing)
 12 | 
 13 | ---
 14 | 
 15 | ## 1. Introduction
 16 | 
 17 | ### Overview
 18 | The Reflex Test Workflow is an automated laboratory testing system that triggers additional tests based on Clinical Decision Support (CDS) rules. When initial test results meet specific criteria, the system suggests reflex tests for laboratory staff approval or denial.
 19 | 
 20 | ### Authentication
 21 | All endpoints require authentication via Laravel Sanctum. Include the Bearer token in the Authorization header:
 22 | 
 23 | ```
 24 | Authorization: Bearer {your_token}
 25 | ```
 26 | 
 27 | ### Base URL Format
 28 | ```
 29 | https://your-domain.com/api
 30 | ```
 31 | 
 32 | All reflex test endpoints are prefixed with `/api/results`.
 33 | 
 34 | ---
 35 | 
 36 | ## 2. Endpoints Reference
 37 | 
 38 | ### 2.1 Check CDS Rules (Modified)
 39 | 
 40 | **Endpoint**
 41 | ```
 42 | GET /api/results/check-cds/{laboratoryRequest}
 43 | ```
 44 | 
 45 | **Description**
 46 | Checks all CDS correlation rules for a laboratory request and returns any triggered rules, including reflex test suggestions. This endpoint is used by the frontend to display CDS alerts to users.
 47 | 
 48 | **Authentication**
 49 | Required
 50 | 
 51 | **Headers**
 52 | ```
 53 | Accept: application/json
 54 | Authorization: Bearer {token}
 55 | ```
 56 | 
 57 | **Parameters**
 58 | - `laboratoryRequest` (path parameter, required) - ID of the laboratory request to check
 59 | 
 60 | **Response**
 61 | 
 62 | Success Response (200 OK):
 63 | ```json
 64 | {
 65 |     "triggered_rules": [
 66 |         {
 67 |             "id": 456,
 68 |             "rule_name": "High Glucose Reflex",
 69 |             "alert_message": "Glucose level exceeds 200 mg/dL. Consider HbA1c test.",
 70 |             "action_type": "REFLEX_TEST",
 71 |             "is_blocking": true,
 72 |             "is_acknowledged": false,
 73 |             "trigger_test_id": 123,
 74 |             "reflex_test_id": 789
 75 |         }
 76 |     ],
 77 |     "has_blocking_rules": true,
 78 |     "is_acknowledged": false
 79 | }
 80 | ```
 81 | 
 82 | **Response Fields**
 83 | - `triggered_rules[]` - Array of triggered CDS rules
 84 |   - `id` - Rule ID
 85 |   - `rule_name` - Name of the rule
 86 |   - `alert_message` - Message to display to user
 87 |   - `action_type` - Type of action (`REFLEX_TEST`, `STOP_AUTO_VERIFY`, etc.)
 88 |   - `is_blocking` - Whether the rule blocks further processing
 89 |   - `is_acknowledged` - Whether the rule has been previously acknowledged
 90 |   - `trigger_test_id` - ID of the test parameter that triggered the rule
 91 |   - `reflex_test_id` - ID of the test parameter to be used for reflex testing
 92 | - `has_blocking_rules` - Overall flag indicating if any blocking rules exist
 93 | - `is_acknowledged` - Whether all rules for this request have been acknowledged
 94 | 
 95 | **Notes**
 96 | - The endpoint evaluates all active CDS rules that match the test parameters in the request
 97 | - Rules with `action_type` of `REFLEX_TEST` will include `reflex_test_id`
 98 | - The `is_acknowledged` flag helps determine if alerts should be shown again
 99 | 
100 | ---
101 | 
102 | ### 2.2 Approve Reflex Test
103 | 
104 | **Endpoint**
105 | ```
106 | POST /api/results/laboratory-requests/{laboratoryRequest}/approve-reflex-test
107 | ```
108 | 
109 | **Description**
110 | Approves a reflex test suggestion and creates one or more reflex laboratory requests based on the CDS rule configuration. This is the main endpoint for initiating reflex testing.
111 | 
112 | **Authentication**
113 | Required
114 | 
115 | **Headers**
116 | ```
117 | Accept: application/json
118 | Content-Type: application/json
119 | Authorization: Bearer {token}
120 | ```
121 | 
122 | **Parameters**
123 | - `laboratoryRequest` (path parameter, required) - ID of the parent laboratory request
124 | 
125 | **Request Body (JSON)**
126 | ```json
127 | {
128 |     "rule_id": 456,
129 |     "reflex_test_id": 789,
130 |     "auto_create": false
131 | }
132 | ```
133 | 
134 | **Required Fields**
135 | - `rule_id` (integer) - ID of the CDS correlation rule that triggered the reflex
136 | - `reflex_test_id` (integer) - ID of the test_parameter that triggered the reflex test
137 | 
138 | **Optional Fields**
139 | - `auto_create` (boolean) - If `true`, skips approval check and creates reflex automatically (for automated workflows). Default: `false`
140 | 
141 | **Responses**
142 | 
143 | *Success Response (200 OK)*:
144 | ```json
145 | {
146 |     "message": "Reflex test(s) created successfully",
147 |     "reflex_requests": [
148 |         {
149 |             "id": 101,
150 |             "sample_id": "SAMPLE-2025-001",
151 |             "patient_id": 1,
152 |             "doctor_id": 2,
153 |             "is_reflex": true,
154 |             "reflex_parent_request_id": 100,
155 |             "reflex_triggered_by_rule_id": 456,
156 |             "priority_level": "STAT",
157 |             "request_date": "2025-06-18T10:30:00.000000Z",
158 |             "created_at": "2025-06-18T10:30:00.000000Z",
159 |             "updated_at": "2025-06-18T10:30:00.000000Z"
160 |         }
161 |     ]
162 | }
163 | ```
164 | 
165 | *Validation Error (400 Bad Request)*:
166 | ```json
167 | {
168 |     "message": "The given data was invalid.",
169 |     "errors": {
170 |         "rule_id": [
171 |             "The selected rule id is invalid."
172 |         ],
173 |         "reflex_test_id": [
174 |             "The selected reflex test id is invalid."
175 |         ]
176 |     }
177 | }
178 | ```
179 | 
180 | *Not Found (404 Not Found)*:
181 | ```json
182 | {
183 |     "message": "Rule not found"
184 | }
185 | ```
186 | 
187 | *Server Error (500 Internal Server Error)*:
188 | ```json
189 | {
190 |     "message": "Failed to create reflex test: Error details here"
191 | }
192 | ```
193 | 
194 | **Notes**
195 | - The endpoint creates reflex requests grouped by test category
196 | - Each reflex request includes all tests in the same category as the reflex parameter
197 | - A `RuleDecisionAudit` record is created with decision `reflex_created`
198 | - The operation is wrapped in a database transaction for data integrity
199 | - If a reflex already exists for this rule/parent request combination, no new requests are created (returns empty array)
200 | 
201 | ---
202 | 
203 | ### 2.3 Deny Reflex Test
204 | 
205 | **Endpoint**
206 | ```
207 | POST /api/results/laboratory-requests/{laboratoryRequest}/deny-reflex-test
208 | ```
209 | 
210 | **Description**
211 | Denies a reflex test suggestion and logs the decision with an optional reason. This prevents the reflex test from being automatically created.
212 | 
213 | **Authentication**
214 | Required
215 | 
216 | **Headers**
217 | ```
218 | Accept: application/json
219 | Content-Type: application/json
220 | Authorization: Bearer {token}
221 | ```
222 | 
223 | **Parameters**
224 | - `laboratoryRequest` (path parameter, required) - ID of the parent laboratory request
225 | 
226 | **Request Body (JSON)**
227 | ```json
228 | {
229 |     "rule_id": 456,
230 |     "reason": "Clinically not indicated based on patient history"
231 | }
232 | ```
233 | 
234 | **Required Fields**
235 | - `rule_id` (integer) - ID of the CDS correlation rule being denied
236 | 
237 | **Optional Fields**
238 | - `reason` (string, max 500 characters) - Reason for denying the reflex test
239 | 
240 | **Responses**
241 | 
242 | *Success Response (200 OK)*:
243 | ```json
244 | {
245 |     "message": "Reflex test denied"
246 | }
247 | ```
248 | 
249 | *Validation Error (400 Bad Request)*:
250 | ```json
251 | {
252 |     "message": "The given data was invalid.",
253 |     "errors": {
254 |         "rule_id": [
255 |             "The selected rule id is invalid."
256 |         ],
257 |         "reason": [
258 |             "The reason may not be greater than 500 characters."
259 |         ]
260 |     }
261 | }
262 | ```
263 | 
264 | *Server Error (500 Internal Server Error)*:
265 | ```json
266 | {
267 |     "message": "Failed to deny reflex test: Error details here"
268 | }
269 | ```
270 | 
271 | **Notes**
272 | - A `RuleDecisionAudit` record is created with decision `reflex_denied`
273 | - The denial is logged for audit trail and reporting purposes
274 | - Denial does not prevent future reflex suggestions if conditions are met again
275 | 
276 | ---
277 | 
278 | ### 2.4 Get Reflex Requests (Optional)
279 | 
280 | **Endpoint**
281 | ```
282 | GET /api/laboratory-requests?reflex_parent_id={id}
283 | ```
284 | 
285 | **Description**
286 | Filters laboratory requests to find all reflex requests for a specific parent request. This is a standard list endpoint with optional filtering.
287 | 
288 | **Authentication**
289 | Required
290 | 
291 | **Headers**
292 | ```
293 | Accept: application/json
294 | Authorization: Bearer {token}
295 | ```
296 | 
297 | **Parameters**
298 | - `reflex_parent_id` (query parameter, optional) - Parent laboratory request ID to filter by
299 | - Standard pagination parameters: `page`, `per_page`
300 | 
301 | **Response**
302 | Paginated list of laboratory requests with `is_reflex=true` matching the parent ID.
303 | 
304 | **Example Response**:
305 | ```json
306 | {
307 |     "current_page": 1,
308 |     "data": [
309 |         {
310 |             "id": 101,
311 |             "sample_id": "SAMPLE-2025-001",
312 |             "is_reflex": true,
313 |             "reflex_parent_request_id": 100,
314 |             "reflex_triggered_by_rule_id": 456,
315 |             "patient_id": 1,
316 |             "priority_level": "STAT",
317 |             "request_date": "2025-06-18T10:30:00.000000Z"
318 |         }
319 |     ],
320 |     "total": 1,
321 |     "per_page": 15,
322 |     "last_page": 1
323 | }
324 | ```
325 | 
326 | **Notes**
327 | - This endpoint uses the standard laboratory request list with filtering
328 | - Only returns requests where `is_reflex = true` when `reflex_parent_id` is provided
329 | - Can be combined with other filters (date range, status, etc.)
330 | 
331 | ---
332 | 
333 | ## 3. Data Models
334 | 
335 | ### 3.1 LaboratoryRequest (Reflex Fields)
336 | 
337 | The `LaboratoryRequest` model includes the following reflex-specific fields:
338 | 
339 | ```json
340 | {
341 |     "id": 101,
342 |     "sample_id": "SAMPLE-2025-001",
343 |     "patient_id": 1,
344 |     "doctor_id": 2,
345 |     "ward_id": null,
346 |     "room_id": null,
347 |     "priority_level": "STAT",
348 |     "patient_type_id": 1,
349 |     "request_date": "2025-06-18T10:30:00.000000Z",
350 |     "is_reflex": true,
351 |     "reflex_parent_request_id": 100,
352 |     "reflex_triggered_by_rule_id": 456,
353 |     "created_at": "2025-06-18T10:30:00.000000Z",
354 |     "updated_at": "2025-06-18T10:30:00.000000Z"
355 | }
356 | ```
357 | 
358 | **Reflex-Specific Fields**:
359 | - `is_reflex` (boolean) - Indicates this is a reflex test request
360 | - `reflex_parent_request_id` (integer, nullable) - ID of the parent laboratory request that triggered this reflex
361 | - `reflex_triggered_by_rule_id` (integer, nullable) - ID of the CDS rule that caused this reflex to be created
362 | 
363 | **Relationships**:
364 | - `belongsTo(Patient::class)`
365 | - `belongsTo(Doctor::class)`
366 | - `belongsToMany(Test::class, 'laboratory_request_test')` - with pivot `LaboratoryRequestTest`
367 | - `hasMany(TestResult::class)`
368 | - `hasOne(Payment::class)`
369 | 
370 | ---
371 | 
372 | ### 3.2 LaboratoryRequestTest (Reflex Fields)
373 | 
374 | The pivot model `LaboratoryRequestTest` includes:
375 | 
376 | ```json
377 | {
378 |     "id": 201,
379 |     "laboratory_request_id": 101,
380 |     "test_id": 50,
381 |     "item_status": "pending",
382 |     "price_at_request": 150.00,
383 |     "rejection_reason": null,
384 |     "collected_at": null,
385 |     "accepted_at": null,
386 |     "rejected_at": null,
387 |     "remarks": null,
388 |     "comment": null,
389 |     "checkin_by": null,
390 |     "checkin_at": null,
391 |     "sendout_by": null,
392 |     "sendout_at": null,
393 |     "park_by": null,
394 |     "park_at": null,
395 |     "perform_by": null,
396 |     "perform_at": null,
397 |     "verify_by": null,
398 |     "verify_at": null,
399 |     "pathologist_id": null,
400 |     "is_reflex_test": true,
401 |     "reflex_triggered_at": "2025-06-18T10:30:00.000000Z",
402 |     "reflex_triggered_by_rule_id": 456,
403 |     "created_at": "2025-06-18T10:30:00.000000Z",
404 |     "updated_at": "2025-06-18T10:30:00.000000Z"
405 | }
406 | ```
407 | 
408 | **Reflex-Specific Fields**:
409 | - `is_reflex_test` (boolean) - Indicates this test entry is part of a reflex workflow
410 | - `reflex_triggered_at` (datetime) - Timestamp when the reflex was triggered
411 | - `reflex_triggered_by_rule_id` (integer, nullable) - ID of the CDS rule that triggered this test
412 | 
413 | ---
414 | 
415 | ### 3.3 RuleDecisionAudit (Reflex Decisions)
416 | 
417 | The `RuleDecisionAudit` model tracks all rule decisions including reflex approvals/denials:
418 | 
419 | ```json
420 | {
421 |     "id": 301,
422 |     "rule_type": "CDS",
423 |     "decision": "reflex_created",
424 |     "message": "Reflex test approved. Created 1 request(s).",
425 |     "rule_id": 456,
426 |     "policy": null,
427 |     "laboratory_request_id": 100,
428 |     "test_result_id": null,
429 |     "test_parameter_id": null,
430 |     "test_id": null,
431 |     "user_id": 5,
432 |     "supervisor_id": null,
433 |     "context": {
434 |         "reflex_requests": [101],
435 |         "reflex_test_id": 789,
436 |         "auto_created": false
437 |     },
438 |     "created_at": "2025-06-18T10:30:00.000000Z",
439 |     "updated_at": "2025-06-18T10:30:00.000000Z"
440 | }
441 | ```
442 | 
443 | **Key Fields**:
444 | - `rule_type` - Always `"CDS"` for reflex workflows
445 | - `decision` - Either `"reflex_created"` or `"reflex_denied"`
446 | - `rule_id` - ID of the CDS correlation rule
447 | - `laboratory_request_id` - Parent request ID
448 | - `user_id` - User who made the decision
449 | - `context` (JSON) - Additional context:
450 |   - For `reflex_created`: `reflex_requests[]` (array of created request IDs), `reflex_test_id`, `auto_created`
451 |   - For `reflex_denied`: `denied_at`
452 | 
453 | ---
454 | 
455 | ### 3.4 CdsCorrelationRule (Relevant Fields)
456 | 
457 | ```json
458 | {
459 |     "id": 456,
460 |     "rule_name": "High Glucose Reflex",
461 |     "description": "Triggers HbA1c when glucose > 200",
462 |     "rule_logic_json": {
463 |         "operator": "AND",
464 |         "conditions": [
465 |             {
466 |                 "test_parameter_id": 123,
467 |                 "operator": ">",
468 |                 "value": 200
469 |             }
470 |         ]
471 |     },
472 |     "trigger_test_id": 123,
473 |     "correlated_tests": "789,790",
474 |     "reflex_test_id": 789,
475 |     "action_type": "REFLEX_TEST",
476 |     "alert_message": "Glucose level exceeds 200 mg/dL. Consider HbA1c test.",
477 |     "is_active": true,
478 |     "created_at": "2025-06-01T00:00:00.000000Z"
479 | }
480 | ```
481 | 
482 | **Key Reflex Fields**:
483 | - `trigger_test_id` - Test parameter that triggers the rule
484 | - `reflex_test_id` - Test parameter to be used for reflex testing
485 | - `correlated_tests` - Comma-separated list of additional test parameter IDs
486 | - `action_type` - Must be `"REFLEX_TEST"` for reflex workflows
487 | - `alert_message` - Message shown to users
488 | 
489 | ---
490 | 
491 | ## 4. Error Handling
492 | 
493 | ### HTTP Status Codes
494 | 
495 | | Code | Description | When Used |
496 | |------|-------------|-----------|
497 | | 200 | Success | Reflex approved/denied successfully |
498 | | 400 | Bad Request | Validation errors, missing required fields |
499 | | 404 | Not Found | Rule ID doesn't exist, laboratory request not found |
500 | | 500 | Internal Server Error | Database errors, service exceptions |
501 | 
502 | ### Validation Error Format
503 | 
504 | All validation errors return a consistent format:
505 | 
506 | ```json
507 | {
508 |     "message": "The given data was invalid.",
509 |     "errors": {
510 |         "field_name": [
511 |             "Error message 1",
512 |             "Error message 2"
513 |         ]
514 |     }
515 | }
516 | ```
517 | 
518 | ### Common Validation Rules
519 | 
520 | **Approve Reflex Test**:
521 | - `rule_id`: required, integer, must exist in `cds_correlation_rules` table
522 | - `reflex_test_id`: required, integer, must exist in `test_parameters` table
523 | - `auto_create`: optional, boolean
524 | 
525 | **Deny Reflex Test**:
526 | - `rule_id`: required, integer, must exist in `cds_correlation_rules` table
527 | - `reason`: optional, string, max 500 characters
528 | 
529 | ### Server Error Logging
530 | 
531 | All server errors (500) are logged with context:
532 | - Parent request ID
533 | - Rule ID
534 | - User ID
535 | - Full exception message and stack trace
536 | 
537 | Log entries include the `ReflexTestService` namespace for easy filtering.
538 | 
539 | ---
540 | 
541 | ## 5. Rate Limiting
542 | 
543 | Currently, no specific rate limits are applied to reflex test endpoints. The endpoints inherit the default Laravel Sanctum authentication middleware configuration.
544 | 
545 | **Recommendation**: For production deployments, consider implementing rate limiting:
546 | - Approve/Deny endpoints: 60 requests per minute per user
547 | - Check CDS endpoint: 120 requests per minute per user
548 | 
549 | Configuration can be added in `routes/api.php` using Laravel's `throttle` middleware.
550 | 
551 | ---
552 | 
553 | ## 6. Examples
554 | 
555 | ### 6.1 Complete Workflow Example
556 | 
557 | #### Step 1: Check CDS Rules
558 | 
559 | When a user enters results for a test, the frontend calls the check endpoint:
560 | 
561 | ```bash
562 | curl -X GET "https://your-domain.com/api/results/check-cds/123" \
563 |   -H "Accept: application/json" \
564 |   -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci..."
565 | ```
566 | 
567 | Response:
568 | ```json
569 | {
570 |     "triggered_rules": [
571 |         {
572 |             "id": 456,
573 |             "rule_name": "High Glucose Reflex",
574 |             "alert_message": "Glucose level exceeds 200 mg/dL. Consider HbA1c test.",
575 |             "action_type": "REFLEX_TEST",
576 |             "is_blocking": true,
577 |             "is_acknowledged": false,
578 |             "trigger_test_id": 123,
579 |             "reflex_test_id": 789
580 |         }
581 |     ],
582 |     "has_blocking_rules": true,
583 |     "is_acknowledged": false
584 | }
585 | ```
586 | 
587 | #### Step 2: Approve Reflex Test
588 | 
589 | User clicks "Approve" in the UI:
590 | 
591 | ```bash
592 | curl -X POST "https://your-domain.com/api/results/laboratory-requests/123/approve-reflex-test" \
593 |   -H "Accept: application/json" \
594 |   -H "Content-Type: application/json" \
595 |   -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci..." \
596 |   -d '{
597 |     "rule_id": 456,
598 |     "reflex_test_id": 789,
599 |     "auto_create": false
600 |   }'
601 | ```
602 | 
603 | Response:
604 | ```json
605 | {
606 |     "message": "Reflex test(s) created successfully",
607 |     "reflex_requests": [
608 |         {
609 |             "id": 101,
610 |             "sample_id": "SAMPLE-2025-001",
611 |             "is_reflex": true,
612 |             "reflex_parent_request_id": 123,
613 |             "reflex_triggered_by_rule_id": 456,
614 |             "priority_level": "STAT",
615 |             "request_date": "2025-06-18T10:30:00.000000Z"
616 |         }
617 |     ]
618 | }
619 | ```
620 | 
621 | #### Step 3: Verify Reflex Request in Worklist
622 | 
623 | The newly created reflex request appears in the laboratory worklist with `is_reflex=true` and can be processed normally.
624 | 
625 | ### 6.2 cURL Examples
626 | 
627 | #### Check CDS Rules
628 | ```bash
629 | curl -X GET "https://your-domain.com/api/results/check-cds/{laboratoryRequestId}" \
630 |   -H "Accept: application/json" \
631 |   -H "Authorization: Bearer {token}"
632 | ```
633 | 
634 | #### Approve Reflex Test
635 | ```bash
636 | curl -X POST "https://your-domain.com/api/results/laboratory-requests/{laboratoryRequestId}/approve-reflex-test" \
637 |   -H "Accept: application/json" \
638 |   -H "Content-Type: application/json" \
639 |   -H "Authorization: Bearer {token}" \
640 |   -d '{
641 |     "rule_id": {ruleId},
642 |     "reflex_test_id": {reflexTestParameterId},
643 |     "auto_create": false
644 |   }'
645 | ```
646 | 
647 | #### Deny Reflex Test
648 | ```bash
649 | curl -X POST "https://your-domain.com/api/results/laboratory-requests/{laboratoryRequestId}/deny-reflex-test" \
650 |   -H "Accept: application/json" \
651 |   -H "Content-Type: application/json" \
652 |   -H "Authorization: Bearer {token}" \
653 |   -d '{
654 |     "rule_id": {ruleId},
655 |     "reason": "Not clinically indicated based on patient history"
656 |   }'
657 | ```
658 | 
659 | #### Get Reflex Requests for Parent
660 | ```bash
661 | curl -X GET "https://your-domain.com/api/laboratory-requests?reflex_parent_id={parentRequestId}&per_page=20" \
662 |   -H "Accept: application/json" \
663 |   -H "Authorization: Bearer {token}"
664 | ```
665 | 
666 | ---
667 | 
668 | ## 7. Frontend Integration Notes
669 | 
670 | ### UniversalResultModal.vue Integration
671 | 
672 | The `UniversalResultModal.vue` component should:
673 | 
674 | 1. **On result entry/save**: Automatically call `GET /api/results/check-cds/{id}` after results are saved
675 | 2. **Display alerts**: Show a modal or notification when `triggered_rules` is non-empty
676 | 3. **Handle REFLEX_TEST action**:
677 |    - Display alert message with "Approve" and "Deny" buttons
678 |    - On "Approve": Call `POST /approve-reflex-test` with `rule_id` and `reflex_test_id`
679 |    - On "Deny": Call `POST /deny-reflex-test` with `rule_id` and optional reason
680 | 4. **Track acknowledged state**: Store `is_acknowledged` to avoid showing duplicate alerts
681 | 5. **Handle blocking rules**: Prevent auto-verify/print if `has_blocking_rules=true` and not acknowledged
682 | 
683 | **Example Integration Code**:
684 | ```javascript
685 | // After saving results
686 | const checkCdsRules = async (requestId) => {
687 |   const response = await axios.get(`/api/results/check-cds/${requestId}`);
688 |   if (response.data.triggered_rules.length > 0) {
689 |     showCdsAlerts(response.data.triggered_rules);
690 |   }
691 | };
692 | 
693 | // Approve reflex
694 | const approveReflex = async (requestId, ruleId, reflexTestId) => {
695 |   await axios.post(
696 |     `/api/results/laboratory-requests/${requestId}/approve-reflex-test`,
697 |     { rule_id: ruleId, reflex_test_id: reflexTestId, auto_create: false }
698 |   );
699 |   refreshWorklist();
700 | };
701 | ```
702 | 
703 | ### CategoryResults.vue Integration
704 | 
705 | The `CategoryResults.vue` component should:
706 | 
707 | 1. **Display reflex status**: Add a visual indicator (badge/icon) for requests where `is_reflex=true`
708 | 2. **Show parent link**: Display parent request ID/sample ID for reflex requests
709 | 3. **Filter support**: Add filter option to show only reflex requests or only parent requests
710 | 4. **Worklist columns**: Include `reflex_triggered_by_rule_id` and `reflex_parent_request_id` in table if needed
711 | 
712 | **Example Display**:
713 | ```vue
714 | <template>
715 |   <tr v-for="request in requests" :key="request.id">
716 |     <td>
717 |       <span v-if="request.is_reflex" class="badge badge-reflex">
718 |         Reflex
719 |       </span>
720 |       <span v-else>Parent</span>
721 |     </td>
722 |     <td>{{ request.sample_id }}</td>
723 |     <td v-if="request.is_reflex">
724 |       Parent: {{ request.reflex_parent_request_id }}
725 |     </td>
726 |   </tr>
727 | </template>
728 | ```
729 | 
730 | ### Event Handling
731 | 
732 | **Events to Emit**:
733 | - `reflex-approved` - When user approves a reflex test
734 | - `reflex-denied` - When user denies a reflex test
735 | - `cds-alert-triggered` - When CDS rules are triggered
736 | 
737 | **State Management**:
738 | - Store `is_acknowledged` flag per request to prevent repeated alerts
739 | - Refresh worklist after reflex approval/denial to show new requests
740 | - Update local cache/state with newly created reflex request IDs
741 | 
742 | ---
743 | 
744 | ## 8. Testing
745 | 
746 | For comprehensive testing procedures, refer to:
747 | 
748 | **[REFLEX_TEST_TESTING_GUIDE.md](./REFLEX_TEST_TESTING_GUIDE.md)**
749 | 
750 | ### Quick Test Scenarios
751 | 
752 | 1. **Happy Path**: Enter result that triggers CDS rule → Check endpoint returns rule → Approve → Verify reflex request created
753 | 2. **Denial Path**: Trigger rule → Deny with reason → Verify audit log entry created
754 | 3. **Duplicate Prevention**: Approve same rule twice → Second approval returns empty array (no duplicate)
755 | 4. **Validation**: Send invalid `rule_id` → Receive 400 error
756 | 5. **Auto-Create**: Set `auto_create=true` → Reflex created without prior approval check
757 | 
758 | ---
759 | 
760 | ## Appendix
761 | 
762 | ### Migration Files
763 | - `backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php`
764 | - `backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php`
765 | 
766 | ### Key Service Class
767 | - `backend/app/Services/ReflexTestService.php` - Core business logic for reflex test creation
768 | 
769 | ### Controller
770 | - `backend/app/Http/Controllers/Api/ResultManagementController.php` - API endpoint implementations
771 | 
772 | ### Routes
773 | - Defined in `backend/routes/api.php` under the `results` prefix group
774 | 
775 | ---
776 | 
777 | **Document Version**: 1.0
778 | **Last Updated**: 2025-06-18
779 | **Applicable System**: LIS (Laboratory Information System)
[Tool]
File: REFLEX_TEST_TESTING_GUIDE.md
IMPORTANT: File content truncated.
	Status: Showing lines 1-2000 of 2024 total lines.
	To read more: Use the read_file tool with offset=2001 and limit=2000.
	
	   1 | # Reflex Test Workflow - Comprehensive Testing & Integration Guide
   2 | 
   3 | ## Table of Contents
   4 | 1. [Pre-Testing Setup](#1-pre-testing-setup)
   5 | 2. [Database Migration Testing](#2-database-migration-testing)
   6 | 3. [Backend Service Testing (ReflexTestService)](#3-backend-service-testing-reflextestservice)
   7 | 4. [API Endpoint Testing](#4-api-endpoint-testing)
   8 | 5. [Frontend Testing](#5-frontend-testing)
   9 | 6. [Integration Testing](#6-integration-testing)
  10 | 7. [Data Integrity Checks](#7-data-integrity-checks)
  11 | 8. [Performance Testing](#8-performance-testing)
  12 | 9. [User Acceptance Testing (UAT)](#9-user-acceptance-testing-uat)
  13 | 10. [Rollback Testing](#10-rollback-testing)
  14 | 11. [Troubleshooting](#troubleshooting)
  15 | 
  16 | ---
  17 | 
  18 | ## 1. Pre-Testing Setup
  19 | 
  20 | ### 1.1 Environment Preparation
  21 | 
  22 | ```bash
  23 | # 1. Navigate to backend directory
  24 | cd backend
  25 | 
  26 | # 2. Install dependencies (if not already installed)
  27 | composer install
  28 | npm install
  29 | 
  30 | # 3. Configure environment
  31 | cp .env.example .env
  32 | # Edit .env with database credentials and app settings
  33 | 
  34 | # 4. Generate application key
  35 | php artisan key:generate
  36 | ```
  37 | 
  38 | ### 1.2 Database Setup
  39 | 
  40 | ```bash
  41 | # 1. Run all migrations
  42 | php artisan migrate
  43 | 
  44 | # 2. Seed essential data (if needed)
  45 | php artisan db:seed --class=TestCategorySeeder
  46 | php artisan db:seed --class=TestParameterSeeder
  47 | php artisan db:seed --class=PatientTypeSeeder
  48 | php artisan db:seed --class=WardSeeder
  49 | php artisan db:seed --class=RoomSeeder
  50 | php artisan db:seed --class=DoctorSeeder
  51 | ```
  52 | 
  53 | ### 1.3 Verify Reflex Columns Exist
  54 | 
  55 | ```sql
  56 | -- Check laboratory_requests table
  57 | DESCRIBE laboratory_requests;
  58 | 
  59 | -- Expected new columns:
  60 | -- - is_reflex (TINYINT(1), default: 0)
  61 | -- - reflex_parent_request_id (BIGINT, unsigned, nullable)
  62 | -- - reflex_triggered_by_rule_id (BIGINT, unsigned, nullable)
  63 | 
  64 | -- Check laboratory_request_test table
  65 | DESCRIBE laboratory_request_test;
  66 | 
  67 | -- Expected new columns:
  68 | -- - is_reflex_test (TINYINT(1), default: 0)
  69 | -- - reflex_triggered_at (TIMESTAMP, nullable)
  70 | -- - reflex_triggered_by_rule_id (BIGINT, unsigned, nullable)
  71 | ```
  72 | 
  73 | ### 1.4 Verify Foreign Key Constraints
  74 | 
  75 | ```sql
  76 | -- Check foreign keys in laboratory_requests
  77 | SELECT 
  78 |     TABLE_NAME,
  79 |     COLUMN_NAME,
  80 |     CONSTRAINT_NAME,
  81 |     REFERENCED_TABLE_NAME,
  82 |     REFERENCED_COLUMN_NAME
  83 | FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  84 | WHERE TABLE_SCHEMA = DATABASE()
  85 |     AND TABLE_NAME = 'laboratory_requests'
  86 |     AND COLUMN_NAME IN ('reflex_parent_request_id', 'reflex_triggered_by_rule_id');
  87 | 
  88 | -- Check foreign keys in laboratory_request_test
  89 | SELECT 
  90 |     TABLE_NAME,
  91 |     COLUMN_NAME,
  92 |     CONSTRAINT_NAME,
  93 |     REFERENCED_TABLE_NAME,
  94 |     REFERENCED_COLUMN_NAME
  95 | FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  96 | WHERE TABLE_SCHEMA = DATABASE()
  97 |     AND TABLE_NAME = 'laboratory_request_test'
  98 |     AND COLUMN_NAME = 'reflex_triggered_by_rule_id';
  99 | ```
 100 | 
 101 | ### 1.5 Create Test Data
 102 | 
 103 | #### Step 1: Create Required Reference Data
 104 | 
 105 | ```sql
 106 | -- Ensure these records exist:
 107 | 
 108 | -- 1. Patient Type
 109 | INSERT INTO patient_types (name, description, created_at, updated_at)
 110 | VALUES ('Inpatient', 'Hospital admitted patient', NOW(), NOW())
 111 | ON DUPLICATE KEY UPDATE description=description;
 112 | 
 113 | -- 2. Ward (if using)
 114 | INSERT INTO wards (name, description, created_at, updated_at)
 115 | VALUES ('General Ward', 'General medical ward', NOW(), NOW())
 116 | ON DUPLICATE KEY UPDATE description=description;
 117 | 
 118 | -- 3. Room (if using)
 119 | INSERT INTO rooms (name, ward_id, capacity, created_at, updated_at)
 120 | SELECT 'Room 101', id, 4 FROM wards WHERE name = 'General Ward'
 121 | ON DUPLICATE KEY UPDATE capacity=capacity;
 122 | 
 123 | -- 4. Doctor
 124 | INSERT INTO doctors (name, specialization, email, phone, created_at, updated_at)
 125 | VALUES ('Dr. Test Physician', 'Internal Medicine', 'test@doctor.com', '1234567890', NOW(), NOW())
 126 | ON DUPLICATE KEY UPDATE specialization=specialization;
 127 | ```
 128 | 
 129 | #### Step 2: Create Test Patient
 130 | 
 131 | ```sql
 132 | INSERT INTO patients (
 133 |     id, 
 134 |     last_name, 
 135 |     first_name, 
 136 |     middle_name, 
 137 |     date_of_birth, 
 138 |     gender, 
 139 |     civil_status, 
 140 |     address, 
 141 |     phone, 
 142 |     email, 
 143 |     created_at, 
 144 |     updated_at
 145 | ) VALUES (
 146 |     'TEST001',
 147 |     'TestPatient',
 148 |     'John',
 149 |     'Doe',
 150 |     '1990-05-15',
 151 |     'Male',
 152 |     'Single',
 153 |     '123 Test Street',
 154 |     '9876543210',
 155 |     'test.patient@email.com',
 156 |     NOW(),
 157 |     NOW()
 158 | ) ON DUPLICATE KEY UPDATE 
 159 |     first_name=VALUES(first_name),
 160 |     last_name=VALUES(last_name);
 161 | ```
 162 | 
 163 | #### Step 3: Create Test Category and Tests
 164 | 
 165 | ```sql
 166 | -- Create a test category (e.g., Chemistry)
 167 | INSERT INTO test_categories (name, description, display_order, created_at, updated_at)
 168 | VALUES ('Chemistry', 'Chemistry Tests', 1, NOW(), NOW())
 169 | ON DUPLICATE KEY UPDATE description=description;
 170 | 
 171 | -- Get the category ID
 172 | SET @category_id = (SELECT id FROM test_categories WHERE name = 'Chemistry');
 173 | 
 174 | -- Create a test
 175 | INSERT INTO tests (
 176 |     name, 
 177 |     test_category_id, 
 178 |     description, 
 179 |     normal_range, 
 180 |     unit, 
 181 |     created_at, 
 182 |     updated_at
 183 | ) VALUES (
 184 |     'Glucose',
 185 |     @category_id,
 186 |     'Blood glucose measurement',
 187 |     '70-100',
 188 |     'mg/dL',
 189 |     NOW(),
 190 |     NOW()
 191 | ) ON DUPLICATE KEY UPDATE 
 192 |     description=VALUES(description);
 193 | 
 194 | -- Get the test ID
 195 | SET @test_id = (SELECT id FROM tests WHERE name = 'Glucose' AND test_category_id = @category_id);
 196 | 
 197 | -- Create test parameters for the test
 198 | INSERT INTO test_parameters (
 199 |     test_id,
 200 |     name,
 201 |     normal_range,
 202 |     unit,
 203 |     display_order,
 204 |     created_at,
 205 |     updated_at
 206 | ) VALUES
 207 | (@test_id, 'Glucose', '70-100', 'mg/dL', 1, NOW(), NOW()),
 208 | (@test_id, 'HbA1c', '4.0-5.6', '%', 2, NOW(), NOW());
 209 | ```
 210 | 
 211 | #### Step 4: Create CDS Correlation Rule for Reflex Testing
 212 | 
 213 | ```sql
 214 | -- Ensure CdsCorrelationRule records have reflex_test_id populated
 215 | INSERT INTO cds_correlation_rules (
 216 |     name,
 217 |     description,
 218 |     trigger_condition,
 219 |     alert_message,
 220 |     action_type,
 221 |     reflex_test_id,
 222 |     requires_approval,
 223 |     is_active,
 224 |     created_at,
 225 |     updated_at
 226 | ) VALUES (
 227 |     'High Glucose Reflex',
 228 |     'When glucose is very high, reflex to HbA1c test',
 229 |     '{"parameter_name": "Glucose", "operator": ">", "value": 200}',
 230 |     'Glucose level is critically high. Recommend HbA1c test to assess long-term control.',
 231 |     'REFLEX_TEST',
 232 |     (SELECT id FROM test_parameters WHERE name = 'HbA1c' LIMIT 1),
 233 |     1, -- requires_approval
 234 |     1, -- is_active
 235 |     NOW(),
 236 |     NOW()
 237 | ) ON DUPLICATE KEY UPDATE 
 238 |     alert_message=VALUES(alert_message),
 239 |     reflex_test_id=VALUES(reflex_test_id);
 240 | ```
 241 | 
 242 | #### Step 5: Create Laboratory Request with Test
 243 | 
 244 | ```sql
 245 | -- Get references
 246 | SET @patient_id = (SELECT id FROM patients WHERE last_name = 'TestPatient' LIMIT 1);
 247 | SET @doctor_id = (SELECT id FROM doctors WHERE name = 'Dr. Test Physician' LIMIT 1);
 248 | SET @patient_type_id = (SELECT id FROM patient_types WHERE name = 'Inpatient' LIMIT 1);
 249 | SET @ward_id = (SELECT id FROM wards WHERE name = 'General Ward' LIMIT 1);
 250 | SET @room_id = (SELECT id FROM rooms WHERE name = 'Room 101' LIMIT 1);
 251 | SET @test_id = (SELECT id FROM tests WHERE name = 'Glucose' LIMIT 1);
 252 | 
 253 | -- Create laboratory request
 254 | INSERT INTO laboratory_requests (
 255 |     patient_id,
 256 |     doctor_id,
 257 |     ward_id,
 258 |     room_id,
 259 |     patient_type_id,
 260 |     sample_id,
 261 |     priority_level,
 262 |     request_date,
 263 |     created_by,
 264 |     created_at,
 265 |     updated_at
 266 | ) VALUES (
 267 |     @patient_id,
 268 |     @doctor_id,
 269 |     @ward_id,
 270 |     @room_id,
 271 |     @patient_type_id,
 272 |     CONCAT('SAMPLE-', DATE_FORMAT(NOW(), '%Y%m%d-%H%i%s')),
 273 |     'ROUTINE',
 274 |     NOW(),
 275 |     1, -- Assuming user ID 1 exists
 276 |     NOW(),
 277 |     NOW()
 278 | );
 279 | 
 280 | -- Get the request ID
 281 | SET @request_id = LAST_INSERT_ID();
 282 | 
 283 | -- Create laboratory request test entry
 284 | INSERT INTO laboratory_request_test (
 285 |     laboratory_request_id,
 286 |     test_id,
 287 |     item_status,
 288 |     created_at,
 289 |     updated_at
 290 | ) VALUES (
 291 |     @request_id,
 292 |     @test_id,
 293 |     'pending',
 294 |     NOW(),
 295 |     NOW()
 296 | );
 297 | ```
 298 | 
 299 | ---
 300 | 
 301 | ## 2. Database Migration Testing
 302 | 
 303 | ### 2.1 Migration Execution Tests
 304 | 
 305 | #### Test Case 1: Fresh Migration
 306 | ```bash
 307 | # Test command
 308 | php artisan migrate
 309 | ```
 310 | 
 311 | **Expected Result:**
 312 | - Migrations run without errors
 313 | - Output shows: "Migrated: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table"
 314 | - Output shows: "Migrated: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table"
 315 | 
 316 | **Verification:**
 317 | ```sql
 318 | -- Check columns exist
 319 | SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
 320 | SHOW COLUMNS FROM laboratory_requests LIKE 'reflex_parent_request_id';
 321 | SHOW COLUMNS FROM laboratory_requests LIKE 'reflex_triggered_by_rule_id';
 322 | 
 323 | SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
 324 | SHOW COLUMNS FROM laboratory_request_test LIKE 'reflex_triggered_at';
 325 | SHOW COLUMNS FROM laboratory_request_test LIKE 'reflex_triggered_by_rule_id';
 326 | ```
 327 | 
 328 | #### Test Case 2: Column Data Types and Positions
 329 | 
 330 | ```sql
 331 | -- Verify laboratory_requests columns
 332 | SELECT 
 333 |     COLUMN_NAME,
 334 |     DATA_TYPE,
 335 |     IS_NULLABLE,
 336 |     COLUMN_DEFAULT,
 337 |     EXTRA
 338 | FROM INFORMATION_SCHEMA.COLUMNS
 339 | WHERE TABLE_SCHEMA = DATABASE()
 340 |     AND TABLE_NAME = 'laboratory_requests'
 341 |     AND COLUMN_NAME IN ('is_reflex', 'reflex_parent_request_id', 'reflex_triggered_by_rule_id')
 342 | ORDER BY ORDINAL_POSITION;
 343 | 
 344 | -- Expected:
 345 | -- is_reflex: tinyint(1), NO, 0
 346 | -- reflex_parent_request_id: bigint, YES, NULL
 347 | -- reflex_triggered_by_rule_id: bigint, YES, NULL
 348 | 
 349 | -- Verify laboratory_request_test columns
 350 | SELECT 
 351 |     COLUMN_NAME,
 352 |     DATA_TYPE,
 353 |     IS_NULLABLE,
 354 |     COLUMN_DEFAULT,
 355 |     EXTRA
 356 | FROM INFORMATION_SCHEMA.COLUMNS
 357 | WHERE TABLE_SCHEMA = DATABASE()
 358 |     AND TABLE_NAME = 'laboratory_request_test'
 359 |     AND COLUMN_NAME IN ('is_reflex_test', 'reflex_triggered_at', 'reflex_triggered_by_rule_id');
 360 | ```
 361 | 
 362 | #### Test Case 3: Foreign Key Constraints
 363 | 
 364 | ```sql
 365 | -- Check laboratory_requests foreign keys
 366 | SELECT 
 367 |     CONSTRAINT_NAME,
 368 |     COLUMN_NAME,
 369 |     REFERENCED_TABLE_NAME,
 370 |     REFERENCED_COLUMN_NAME,
 371 |     DELETE_RULE
 372 | FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
 373 | JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
 374 |     ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
 375 | WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
 376 |     AND rc.TABLE_NAME = 'laboratory_requests'
 377 |     AND kcu.COLUMN_NAME IN ('reflex_parent_request_id', 'reflex_triggered_by_rule_id');
 378 | 
 379 | -- Expected:
 380 | -- reflex_parent_request_id -> laboratory_requests.id (SET NULL)
 381 | -- reflex_triggered_by_rule_id -> cds_correlation_rules.id (SET NULL)
 382 | 
 383 | -- Check laboratory_request_test foreign key
 384 | SELECT 
 385 |     CONSTRAINT_NAME,
 386 |     COLUMN_NAME,
 387 |     REFERENCED_TABLE_NAME,
 388 |     REFERENCED_COLUMN_NAME,
 389 |     DELETE_RULE
 390 | FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
 391 | JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
 392 |     ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
 393 | WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
 394 |     AND rc.TABLE_NAME = 'laboratory_request_test'
 395 |     AND kcu.COLUMN_NAME = 'reflex_triggered_by_rule_id';
 396 | 
 397 | -- Expected:
 398 | -- reflex_triggered_by_rule_id -> cds_correlation_rules.id (SET NULL)
 399 | ```
 400 | 
 401 | #### Test Case 4: Rollback Testing
 402 | 
 403 | ```bash
 404 | # Test rollback
 405 | php artisan migrate:rollback
 406 | ```
 407 | 
 408 | **Expected Result:**
 409 | - Output shows: "Rolled back: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table"
 410 | - Output shows: "Rolled back: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table"
 411 | 
 412 | **Verification:**
 413 | ```sql
 414 | -- Columns should no longer exist
 415 | SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
 416 | -- Should return empty set
 417 | 
 418 | SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
 419 | -- Should return empty set
 420 | ```
 421 | 
 422 | ```bash
 423 | # Re-apply migrations
 424 | php artisan migrate
 425 | ```
 426 | 
 427 | ---
 428 | 
 429 | ## 3. Backend Service Testing (ReflexTestService)
 430 | 
 431 | ### 3.1 Testing getTestsForReflexParameter()
 432 | 
 433 | #### Test Case 1.1: Parameter exists and test is standalone
 434 | ```php
 435 | // Test setup
 436 | $parameterId = 1; // ID of a test parameter that belongs to a standalone test
 437 | $service = new ReflexTestService();
 438 | $result = $service->getTestsForReflexParameter($parameterId);
 439 | 
 440 | // Expected result:
 441 | // - Collection with 1 Test model
 442 | // - Test contains the parameter
 443 | // - Test does not include package tests
 444 | ```
 445 | 
 446 | #### Test Case 1.2: Parameter belongs to a test that is part of a package
 447 | ```php
 448 | // Test setup
 449 | $parameterId = 2; // ID of a parameter that belongs to a test in a package
 450 | $result = $service->getTestsForReflexParameter($parameterId);
 451 | 
 452 | // Expected result:
 453 | // - Collection with multiple Test models (all tests in the package)
 454 | // - Original test + all other tests in the same package
 455 | // - No duplicate tests (unique by ID)
 456 | ```
 457 | 
 458 | #### Test Case 1.3: Parameter does not exist
 459 | ```php
 460 | $parameterId = 999999; // Non-existent ID
 461 | $result = $service->getTestsForReflexParameter($parameterId);
 462 | 
 463 | // Expected result:
 464 | // - Empty collection
 465 | // - Warning logged: "Reflex parameter not found"
 466 | ```
 467 | 
 468 | #### Test Case 1.4: Parameter exists but test not found
 469 | ```php
 470 | // Edge case: parameter record exists but test relationship is broken
 471 | $parameterId = 123; // Parameter with test_id pointing to non-existent test
 472 | $result = $service->getTestsForReflexParameter($parameterId);
 473 | 
 474 | // Expected result:
 475 | // - Empty collection
 476 | // - Warning logged: "Test not found for parameter"
 477 | ```
 478 | 
 479 | ### 3.2 Testing groupTestsByCategory()
 480 | 
 481 | #### Test Case 2.1: Single test
 482 | ```php
 483 | $tests = collect([$test1]); // Single test with test_category_id = 1
 484 | $result = $service->groupTestsByCategory($tests);
 485 | 
 486 | // Expected result:
 487 | // array(1) {
 488 | //   [1] => Collection([$test1])
 489 | // }
 490 | ```
 491 | 
 492 | #### Test Case 2.2: Multiple tests in same category
 493 | ```php
 494 | $tests = collect([$test1, $test2]); // Both with test_category_id = 1
 495 | $result = $service->groupTestsByCategory($tests);
 496 | 
 497 | // Expected result:
 498 | // array(1) {
 499 | //   [1] => Collection([$test1, $test2])
 500 | // }
 501 | ```
 502 | 
 503 | #### Test Case 2.3: Multiple tests in different categories
 504 | ```php
 505 | $tests = collect([$test1, $test2, $test3]); // test1, test2 in cat 1; test3 in cat 2
 506 | $result = $service->groupTestsByCategory($tests);
 507 | 
 508 | // Expected result:
 509 | // array(2) {
 510 | //   [1] => Collection([$test1, $test2])
 511 | //   [2] => Collection([$test3])
 512 | // }
 513 | ```
 514 | 
 515 | #### Test Case 2.4: Test with null category
 516 | ```php
 517 | $test = new Test(['test_category_id' => null]);
 518 | $tests = collect([$test]);
 519 | $result = $service->groupTestsByCategory($tests);
 520 | 
 521 | // Expected result:
 522 | // array(1) {
 523 | //   [null] => Collection([$test])
 524 | // }
 525 | ```
 526 | 
 527 | ### 3.3 Testing hasExistingReflex()
 528 | 
 529 | #### Test Case 3.1: No existing reflex
 530 | ```php
 531 | $parentRequest = LaboratoryRequest::find(1);
 532 | $rule = CdsCorrelationRule::find(1);
 533 | $result = $service->hasExistingReflex($parentRequest, $rule);
 534 | 
 535 | // Expected result: false
 536 | ```
 537 | 
 538 | #### Test Case 3.2: Reflex request exists
 539 | ```php
 540 | // Setup: Create a reflex request
 541 | LaboratoryRequest::create([
 542 |     'patient_id' => $parentRequest->patient_id,
 543 |     'is_reflex' => true,
 544 |     'reflex_parent_request_id' => $parentRequest->id,
 545 |     'reflex_triggered_by_rule_id' => $rule->id,
 546 |     // ... other required fields
 547 | ]);
 548 | 
 549 | $result = $service->hasExistingReflex($parentRequest, $rule);
 550 | 
 551 | // Expected result: true
 552 | ```
 553 | 
 554 | #### Test Case 3.3: RuleDecisionAudit entry exists with reflex_created
 555 | ```php
 556 | // Setup: No reflex request, but audit entry exists
 557 | RuleDecisionAudit::create([
 558 |     'rule_type' => 'cds_correlation',
 559 |     'decision' => 'reflex_created',
 560 |     'rule_id' => $rule->id,
 561 |     'laboratory_request_id' => $parentRequest->id,
 562 |     'user_id' => 1,
 563 |     'context' => []
 564 | ]);
 565 | 
 566 | $result = $service->hasExistingReflex($parentRequest, $rule);
 567 | 
 568 | // Expected result: true
 569 | ```
 570 | 
 571 | #### Test Case 3.4: RuleDecisionAudit entry exists with different decision
 572 | ```php
 573 | // Setup: Audit entry with 'reflex_denied' decision
 574 | RuleDecisionAudit::create([
 575 |     'rule_type' => 'cds_correlation',
 576 |     'decision' => 'reflex_denied',
 577 |     'rule_id' => $rule->id,
 578 |     'laboratory_request_id' => $parentRequest->id,
 579 |     'user_id' => 1,
 580 |     'context' => []
 581 | ]);
 582 | 
 583 | $result = $service->hasExistingReflex($parentRequest, $rule);
 584 | 
 585 | // Expected result: false (denied is not created)
 586 | ```
 587 | 
 588 | ### 3.4 Testing createSingleReflexRequest()
 589 | 
 590 | #### Test Case 4.1: Successful creation
 591 | ```php
 592 | $parentRequest = LaboratoryRequest::find(1);
 593 | $test = Test::find(1);
 594 | $reflexParameter = TestParameter::find(1);
 595 | $rule = CdsCorrelationRule::find(1);
 596 | $user = User::find(1);
 597 | 
 598 | $result = $service->createSingleReflexRequest(
 599 |     $parentRequest, $test, $reflexParameter, $rule, $user
 600 | );
 601 | 
 602 | // Expected result:
 603 | // - LaboratoryRequest model returned
 604 | // - $result->is_reflex = true
 605 | // - $result->reflex_parent_request_id = $parentRequest->id
 606 | // - $result->reflex_triggered_by_rule_id = $rule->id
 607 | // - $result->patient_id = $parentRequest->patient_id
 608 | // - $result->doctor_id = $parentRequest->doctor_id
 609 | // - $result->ward_id = $parentRequest->ward_id
 610 | // - $result->room_id = $parentRequest->room_id
 611 | // - $result->patient_type_id = $parentRequest->patient_type_id
 612 | // - $result->priority_level = $parentRequest->priority_level
 613 | // - $result->sample_id is unique (not same as parent)
 614 | // - $result->created_by = $user->id
 615 | ```
 616 | 
 617 | #### Test Case 4.2: Transaction rollback on failure
 618 | ```php
 619 | // Simulate failure (e.g., database constraint violation)
 620 | // Mock or create scenario where LaboratoryRequest::create() throws exception
 621 | 
 622 | try {
 623 |     $result = $service->createSingleReflexRequest(...);
 624 | } catch (\Exception $e) {
 625 |     // Expected: Exception thrown
 626 | }
 627 | 
 628 | // Verify:
 629 | // - No LaboratoryRequest record created
 630 | // - Database transaction rolled back
 631 | // - Error logged: "Failed to create single reflex request"
 632 | ```
 633 | 
 634 | ### 3.5 Testing createReflexTests()
 635 | 
 636 | #### Test Case 5.1: Single category, auto_create = false
 637 | ```php
 638 | $parentRequest = LaboratoryRequest::find(1);
 639 | $rule = CdsCorrelationRule::find(1); // reflex_test_id points to parameter in single category
 640 | $user = User::find(1);
 641 | 
 642 | $result = $service->createReflexTests($parentRequest, $rule, $user, false);
 643 | 
 644 | // Expected result:
 645 | // - Array of 1 LaboratoryRequest
 646 | // - Request has is_reflex = true
 647 | // - Request has correct reflex_parent_request_id and reflex_triggered_by_rule_id
 648 | // - LaboratoryRequestTest entries created for all tests in category
 649 | // - Each LaboratoryRequestTest has:
 650 | //   * is_reflex_test = true
 651 | //   * reflex_triggered_at = current timestamp
 652 | //   * reflex_triggered_by_rule_id = $rule->id
 653 | // - RuleDecisionAudit entry created with decision = 'reflex_created'
 654 | ```
 655 | 
 656 | #### Test Case 5.2: Multiple categories (package test)
 657 | ```php
 658 | // Setup: reflex_test_id points to parameter in a test that belongs to a package
 659 | // Package contains tests from Chemistry and Hematology categories
 660 | $parentRequest = LaboratoryRequest::find(1);
 661 | $rule = CdsCorrelationRule::find(2); // Points to package test parameter
 662 | $user = User::find(1);
 663 | 
 664 | $result = $service->createReflexTests($parentRequest, $rule, $user, false);
 665 | 
 666 | // Expected result:
 667 | // - Array of 2 LaboratoryRequest models (one per category)
 668 | // - First request: category = Chemistry, contains Chemistry tests from package
 669 | // - Second request: category = Hematology, contains Hematology tests from package
 670 | // - All requests have is_reflex = true
 671 | // - All requests link to same parent and rule
 672 | // - Each request has separate unique sample_id
 673 | // - RuleDecisionAudit entry created with context containing all request IDs
 674 | ```
 675 | 
 676 | #### Test Case 5.3: Duplicate prevention
 677 | ```php
 678 | // First call
 679 | $result1 = $service->createReflexTests($parentRequest, $rule, $user, false);
 680 | // Expected: Creates requests
 681 | 
 682 | // Second call (same parent + rule)
 683 | $result2 = $service->createReflexTests($parentRequest, $rule, $user, false);
 684 | // Expected: Returns empty array []
 685 | // Log: "Reflex test already exists"
 686 | ```
 687 | 
 688 | #### Test Case 5.4: No tests found for parameter
 689 | ```php
 690 | // Setup: rule.reflex_test_id points to non-existent parameter
 691 | $rule->reflex_test_id = 999999;
 692 | $result = $service->createReflexTests($parentRequest, $rule, $user, false);
 693 | 
 694 | // Expected result: empty array []
 695 | // Warning logged: "No tests found for reflex parameter"
 696 | ```
 697 | 
 698 | #### Test Case 5.5: Auto-create flag
 699 | ```php
 700 | $result = $service->createReflexTests($parentRequest, $rule, $user, true);
 701 | 
 702 | // Expected:
 703 | // - Same as normal creation
 704 | // - RuleDecisionAudit context includes 'auto_created' => true
 705 | ```
 706 | 
 707 | ---
 708 | 
 709 | ## 4. API Endpoint Testing
 710 | 
 711 | ### 4.1 CDS Check Endpoint
 712 | 
 713 | #### Endpoint: GET /api/laboratory-requests/{id}/check-cds-rules
 714 | 
 715 | **Test Case 4.1.1: Request with no CDS alerts**
 716 | ```bash
 717 | curl -X GET "http://lis.test/api/results/check-cds/1" \
 718 |   -H "Accept: application/json" \
 719 |   -H "Authorization: Bearer {token}"
 720 | ```
 721 | 
 722 | **Expected Response (200 OK):**
 723 | ```json
 724 | {
 725 |   "alerts": [],
 726 |   "has_blocking_alerts": false
 727 | }
 728 | ```
 729 | 
 730 | **Test Case 4.1.2: Request with CDS alert (non-reflex)**
 731 | ```bash
 732 | curl -X GET "http://lis.test/api/results/check-cds/2" \
 733 |   -H "Accept: application/json" \
 734 |   -H "Authorization: Bearer {token}"
 735 | ```
 736 | 
 737 | **Expected Response (200 OK):**
 738 | ```json
 739 | {
 740 |   "alerts": [
 741 |     {
 742 |       "id": 1,
 743 |       "rule_id": 1,
 744 |       "rule_name": "High Glucose Warning",
 745 |       "alert_message": "Glucose level is elevated",
 746 |       "is_blocking": false,
 747 |       "action_type": "WARNING",
 748 |       "is_acknowledged": false
 749 |     }
 750 |   ],
 751 |   "has_blocking_alerts": false
 752 | }
 753 | ```
 754 | 
 755 | **Test Case 4.1.3: Request with reflex-triggering CDS alert**
 756 | ```bash
 757 | curl -X GET "http://lis.test/api/results/check-cds/3" \
 758 |   -H "Accept: application/json" \
 759 |   -H "Authorization: Bearer {token}"
 760 | ```
 761 | 
 762 | **Expected Response (200 OK):**
 763 | ```json
 764 | {
 765 |   "alerts": [
 766 |     {
 767 |       "id": 2,
 768 |       "rule_id": 1,
 769 |       "rule_name": "High Glucose Reflex",
 770 |       "alert_message": "Glucose level is critically high. Recommend HbA1c test.",
 771 |       "is_blocking": true,
 772 |       "action_type": "REFLEX_TEST",
 773 |       "reflex_test_id": 5,
 774 |       "requires_approval": true,
 775 |       "is_acknowledged": false,
 776 |       "reflex_created": false
 777 |     }
 778 |   ],
 779 |   "has_blocking_alerts": true
 780 | }
 781 | ```
 782 | 
 783 | ### 4.2 Approve Reflex Test Endpoint
 784 | 
 785 | #### Endpoint: POST /api/laboratory-requests/{id}/approve-reflex-test
 786 | 
 787 | **Test Case 4.2.1: Successful approval**
 788 | ```bash
 789 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
 790 |   -H "Accept: application/json" \
 791 |   -H "Content-Type: application/json" \
 792 |   -H "Authorization: Bearer {token}" \
 793 |   -d '{
 794 |     "rule_id": 1,
 795 |     "reflex_test_id": 5,
 796 |     "auto_create": false
 797 |   }'
 798 | ```
 799 | 
 800 | **Expected Response (200 OK):**
 801 | ```json
 802 | {
 803 |   "message": "Reflex test(s) created successfully",
 804 |   "reflex_requests": [
 805 |     {
 806 |       "id": 2,
 807 |       "sample_id": "uuid-generated-id",
 808 |       "patient_id": 1,
 809 |       "doctor_id": 1,
 810 |       "is_reflex": true,
 811 |       "reflex_parent_request_id": 1,
 812 |       "reflex_triggered_by_rule_id": 1,
 813 |       "priority_level": "ROUTINE",
 814 |       "created_at": "2025-06-18T10:30:00.000000Z"
 815 |     }
 816 |   ]
 817 | }
 818 | ```
 819 | 
 820 | **Database Verification:**
 821 | ```sql
 822 | -- Check reflex request created
 823 | SELECT * FROM laboratory_requests 
 824 | WHERE reflex_parent_request_id = 1 
 825 |   AND reflex_triggered_by_rule_id = 1;
 826 | 
 827 | -- Should return 1 row with is_reflex = 1
 828 | 
 829 | -- Check reflex test items
 830 | SELECT * FROM laboratory_request_test 
 831 | WHERE laboratory_request_id = 2 
 832 |   AND is_reflex_test = 1 
 833 |   AND reflex_triggered_by_rule_id = 1;
 834 | 
 835 | -- Should return all tests for the reflex parameter
 836 | 
 837 | -- Check audit entry
 838 | SELECT * FROM rule_decision_audit 
 839 | WHERE laboratory_request_id = 1 
 840 |   AND rule_id = 1 
 841 |   AND decision = 'reflex_created';
 842 | 
 843 | -- Should return 1 row
 844 | ```
 845 | 
 846 | **Test Case 4.2.2: Invalid rule_id**
 847 | ```bash
 848 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
 849 |   -H "Accept: application/json" \
 850 |   -H "Content-Type: application/json" \
 851 |   -H "Authorization: Bearer {token}" \
 852 |   -d '{
 853 |     "rule_id": 999999,
 854 |     "reflex_test_id": 5
 855 |   }'
 856 | ```
 857 | 
 858 | **Expected Response (404 Not Found):**
 859 | ```json
 860 | {
 861 |   "message": "No query results for model [CdsCorrelationRule] 999999"
 862 | }
 863 | ```
 864 | 
 865 | **Test Case 4.2.3: Missing required parameters**
 866 | ```bash
 867 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
 868 |   -H "Accept: application/json" \
 869 |   -H "Content-Type: application/json" \
 870 |   -H "Authorization: Bearer {token}" \
 871 |   -d '{
 872 |     "rule_id": 1
 873 |     // missing reflex_test_id
 874 |   }'
 875 | ```
 876 | 
 877 | **Expected Response (422 Unprocessable Entity):**
 878 | ```json
 879 | {
 880 |   "message": "The given data was invalid.",
 881 |   "errors": {
 882 |     "reflex_test_id": [
 883 |       "The reflex test id field is required."
 884 |     ]
 885 |   }
 886 | }
 887 | ```
 888 | 
 889 | **Test Case 4.2.4: Approve same reflex twice**
 890 | ```bash
 891 | # First approval
 892 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
 893 |   -H "Authorization: Bearer {token}" \
 894 |   -d '{"rule_id": 1, "reflex_test_id": 5}'
 895 | 
 896 | # Second approval (same parent + rule)
 897 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
 898 |   -H "Authorization: Bearer {token}" \
 899 |   -d '{"rule_id": 1, "reflex_test_id": 5}'
 900 | ```
 901 | 
 902 | **Expected Second Response (200 OK but no creation):**
 903 | ```json
 904 | {
 905 |   "message": "Reflex test(s) created successfully",
 906 |   "reflex_requests": []
 907 | }
 908 | ```
 909 | 
 910 | **Log Entry:**
 911 | ```
 912 | Reflex test already exists, parent_request_id: 1, rule_id: 1
 913 | ```
 914 | 
 915 | ### 4.3 Deny Reflex Test Endpoint
 916 | 
 917 | #### Endpoint: POST /api/laboratory-requests/{id}/deny-reflex-test
 918 | 
 919 | **Test Case 4.3.1: Successful denial**
 920 | ```bash
 921 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
 922 |   -H "Accept: application/json" \
 923 |   -H "Content-Type: application/json" \
 924 |   -H "Authorization: Bearer {token}" \
 925 |   -d '{
 926 |     "rule_id": 1,
 927 |     "reason": "Clinically not indicated"
 928 |   }'
 929 | ```
 930 | 
 931 | **Expected Response (200 OK):**
 932 | ```json
 933 | {
 934 |   "message": "Reflex test denied"
 935 | }
 936 | ```
 937 | 
 938 | **Database Verification:**
 939 | ```sql
 940 | -- Check audit entry
 941 | SELECT * FROM rule_decision_audit 
 942 | WHERE laboratory_request_id = 1 
 943 |   AND rule_id = 1 
 944 |   AND decision = 'reflex_denied';
 945 | 
 946 | -- Should return 1 row with reason in message or context
 947 | ```
 948 | 
 949 | **Test Case 4.3.2: Denial without reason**
 950 | ```bash
 951 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
 952 |   -H "Accept: application/json" \
 953 |   -H "Content-Type: application/json" \
 954 |   -H "Authorization: Bearer {token}" \
 955 |   -d '{
 956 |     "rule_id": 1
 957 |   }'
 958 | ```
 959 | 
 960 | **Expected Response (200 OK):**
 961 | ```json
 962 | {
 963 |   "message": "Reflex test denied"
 964 | }
 965 | ```
 966 | 
 967 | **Audit Entry:**
 968 | ```sql
 969 | -- message should be "Reflex test denied by user"
 970 | ```
 971 | 
 972 | **Test Case 4.3.3: Invalid rule_id**
 973 | ```bash
 974 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
 975 |   -H "Accept: application/json" \
 976 |   -H "Content-Type: application/json" \
 977 |   -H "Authorization: Bearer {token}" \
 978 |   -d '{
 979 |     "rule_id": 999999
 980 |   }'
 981 | ```
 982 | 
 983 | **Expected Response (404 Not Found):**
 984 | 
 985 | ### 4.4 Edge Cases and Error Handling
 986 | 
 987 | **Test Case 4.4.1: Deny then approve**
 988 | ```bash
 989 | # Step 1: Deny
 990 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
 991 |   -H "Authorization: Bearer {token}" \
 992 |   -d '{"rule_id": 1}'
 993 | 
 994 | # Step 2: Try to approve
 995 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
 996 |   -H "Authorization: Bearer {token}" \
 997 |   -d '{"rule_id": 1, "reflex_test_id": 5}'
 998 | ```
 999 | 
1000 | **Expected:**
1001 | - Approval succeeds but returns empty array (hasExistingReflex returns true from audit)
1002 | - No new reflex requests created
1003 | 
1004 | **Test Case 4.4.2: Approve then deny**
1005 | ```bash
1006 | # Step 1: Approve
1007 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
1008 |   -H "Authorization: Bearer {token}" \
1009 |   -d '{"rule_id": 1, "reflex_test_id": 5}'
1010 | 
1011 | # Step 2: Try to deny
1012 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/deny-reflex-test" \
1013 |   -H "Authorization: Bearer {token}" \
1014 |   -d '{"rule_id": 1, "reason": "Second denial attempt"}'
1015 | ```
1016 | 
1017 | **Expected:**
1018 | - Denial succeeds (audit entry created)
1019 | - But hasExistingReflex still returns true (reflex was created)
1020 | - Both audit entries exist: 'reflex_created' and 'reflex_denied'
1021 | 
1022 | **Test Case 4.4.3: Unauthorized access**
1023 | ```bash
1024 | curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
1025 |   -H "Content-Type: application/json" \
1026 |   -d '{"rule_id": 1, "reflex_test_id": 5}'
1027 |   # No Authorization header
1028 | ```
1029 | 
1030 | **Expected Response (401 Unauthorized)**
1031 | 
1032 | ---
1033 | 
1034 | ## 5. Frontend Testing
1035 | 
1036 | ### 5.1 UniversalResultModal.vue
1037 | 
1038 | #### Test Case 5.1.1: Modal opens with reflex-triggering results
1039 | 1. Open result modal for a request that has results triggering a CDS rule with REFLEX_TEST action
1040 | 2. CDS check should automatically run
1041 | 3. Reflex approval modal should appear automatically
1042 | 
1043 | **Expected Behavior:**
1044 | - Modal displays patient information correctly
1045 | - CDS banner shows with reflex alert
1046 | - Alert shows: rule name, reflex test parameter name, requires approval
1047 | - "Approve & Order" and "Deny" buttons appear
1048 | 
1049 | #### Test Case 5.1.2: Click "Approve & Order"
1050 | 1. With reflex alert showing, click "Approve & Order" button
1051 | 2. API call should be made to `/api/results/laboratory-requests/{id}/approve-reflex-test`
1052 | 
1053 | **Expected Behavior:**
1054 | - Success message appears: "Reflex test(s) created successfully"
1055 | - Modal closes
1056 | - Request details refresh (if viewing parent request, new reflex requests may appear in worklist)
1057 | - CDS alerts are cleared
1058 | 
1059 | **Network Request Verification:**
1060 | ```javascript
1061 | // Request payload
1062 | {
1063 |   "rule_id": 1,
1064 |   "reflex_test_id": 5,
1065 |   "auto_create": false
1066 | }
1067 | 
1068 | // Response handling
1069 | {
1070 |   "message": "Reflex test(s) created successfully",
1071 |   "reflex_requests": [{ "id": 2, "sample_id": "..." }]
1072 | }
1073 | ```
1074 | 
1075 | #### Test Case 5.1.3: Click "Deny"
1076 | 1. With reflex alert showing, click "Deny" button
1077 | 2. Confirmation dialog should appear
1078 | 3. Confirm denial
1079 | 4. API call to `/api/results/laboratory-requests/{id}/deny-reflex-test`
1080 | 
1081 | **Expected Behavior:**
1082 | - Info message appears: "Reflex test denied"
1083 | - Modal closes
1084 | - CDS alerts are re-checked
1085 | - Alert should no longer appear (or show as denied)
1086 | 
1087 | **Network Request Verification:**
1088 | ```javascript
1089 | // Request payload
1090 | {
1091 |   "rule_id": 1,
1092 |   "reason": "" // optional, user may not provide
1093 | }
1094 | 
1095 | // Response
1096 | {
1097 |   "message": "Reflex test denied"
1098 | }
1099 | ```
1100 | 
1101 | #### Test Case 5.1.4: Error handling
1102 | 1. Simulate API error (500 or network failure) on approval
1103 | 2. Click "Approve & Order"
1104 | 
1105 | **Expected Behavior:**
1106 | - Error message displayed: "Failed to create reflex test: {error}"
1107 | - Modal remains open
1108 | - User can retry
1109 | 
1110 | ### 5.2 ViolationsSummaryModal.vue
1111 | 
1112 | #### Test Case 5.2.1: Reflex alert styling
1113 | 1. Open Violations Summary modal for a request with reflex alerts
1114 | 2. Navigate to "CDS Rules" section
1115 | 
1116 | **Expected Behavior:**
1117 | - Reflex alerts have yellow/warning background
1118 | - Badge shows "REFLEX TEST" (not just "WARNING" or "INFO")
1119 | - "Approve" and "Deny" buttons visible (not "Acknowledge")
1120 | 
1121 | #### Test Case 5.2.2: Approve from violations modal
1122 | 1. In Violations Summary, find reflex alert
1123 | 2. Click "Approve" button
1124 | 
1125 | **Expected Behavior:**
1126 | - API call to approve-reflex-test endpoint
1127 | - Success message
1128 | - Alert marked as processed (removed from list or marked acknowledged)
1129 | - Modal remains open for other alerts
1130 | 
1131 | #### Test Case 5.2.3: Deny from violations modal
1132 | 1. In Violations Summary, find reflex alert
1133 | 2. Click "Deny" button
1134 | 3. Confirmation dialog appears
1135 | 4. Confirm denial
1136 | 
1137 | **Expected Behavior:**
1138 | - API call to deny-reflex-test endpoint
1139 | - Alert marked as denied
1140 | - Success message
1141 | - Alert removed or marked denied in UI
1142 | 
1143 | ### 5.3 CategoryResults.vue (Worklist)
1144 | 
1145 | #### Test Case 5.3.1: Reflex request display
1146 | 1. Navigate to worklist (e.g., Chemistry results)
1147 | 2. Reflex requests should appear in the list
1148 | 
1149 | **Expected Behavior:**
1150 | - "Type" column shows red "REFLEX" badge
1151 | - Sample ID displayed normally
1152 | - Patient information displayed normally
1153 | - Status pill shows appropriate status (not blocked by parent)
1154 | 
1155 | #### Test Case 5.3.2: "Show Only Reflex Tests" filter
1156 | 1. Check "Show Only Reflex Tests" checkbox
1157 | 2. Worklist should filter to show only reflex requests
1158 | 
1159 | **Expected Behavior:**
1160 | - Only requests with `is_reflex = true` are shown
1161 | - Count in triage tabs updates accordingly
1162 | - "Reflex Origin" column appears showing parent request ID
1163 | - Parent request ID is clickable
1164 | 
1165 | #### Test Case 5.3.3: Navigate to parent request
1166 | 1. With reflex filter active, click parent request ID in "Reflex Origin" column
1167 | 2. Should navigate to parent request details
1168 | 
1169 | **Expected Behavior:**
1170 | - Router navigates to parent request view
1171 | - Parent request modal/opens correctly
1172 | - Can view original tests and results
1173 | 
1174 | #### Test Case 5.3.4: Reflex in correct category
1175 | 1. Create reflex tests that span multiple categories
1176 | 2. Navigate to Chemistry worklist
1177 | 3. Navigate to Hematology worklist
1178 | 
1179 | **Expected Behavior:**
1180 | - Chemistry reflex requests appear in Chemistry worklist
1181 | - Hematology reflex requests appear in Hematology worklist
1182 | - Each in correct category tab
1183 | 
1184 | #### Test Case 5.3.5: Reflex request status flow
1185 | 1. Create reflex request via approval
1186 | 2. Check worklist
1187 | 3. Process reflex request through normal workflow (collect, accept, enter results, etc.)
1188 | 
1189 | **Expected Behavior:**
1190 | - Reflex request follows same status flow as normal requests
1191 | - Can enter results for reflex tests
1192 | - Can validate and release reflex results
1193 | - No special restrictions beyond normal workflow
1194 | 
1195 | ---
1196 | 
1197 | ## 6. Integration Testing
1198 | 
1199 | ### 6.1 Full End-to-End Workflow
1200 | 
1201 | **Scenario: Complete Reflex Test Lifecycle**
1202 | 
1203 | 1. **Setup**: Create test patient, doctor, laboratory request with Glucose test
1204 | 2. **Enter Result**: Clinician enters Glucose result = 250 mg/dL (above 200 threshold)
1205 | 3. **Save Result**: System saves result and triggers CDS check
1206 | 4. **CDS Alert**: CDS rule "High Glucose Reflex" triggers with REFLEX_TEST action
1207 | 5. **Frontend Display**: UniversalResultModal shows reflex approval prompt
1208 | 6. **User Action**: Clinician clicks "Approve & Order"
1209 | 7. **Backend Processing**:
1210 |    - `approveReflexTest()` validates request
1211 |    - `ReflexTestService::createReflexTests()` creates reflex request(s)
1212 |    - Audit entry recorded
1213 | 8. **Worklist Update**: New reflex request appears in appropriate category worklist
1214 | 9. **Reflex Processing**: Technologist processes reflex HbA1c test
1215 | 10. **Result Entry**: HbA1c result entered and validated
1216 | 11. **Report Generation**: Final report includes both parent and reflex results
1217 | 
1218 | **Verification Points:**
1219 | - Database: `laboratory_requests.is_reflex = 1` for reflex request
1220 | - Database: `laboratory_request_test.is_reflex_test = 1` for reflex test items
1221 | - Database: `rule_decision_audit` has 'reflex_created' entry
1222 | - Frontend: Reflex badge visible in worklist
1223 | - Frontend: Parent request ID link works
1224 | - Audit trail complete with timestamps and user IDs
1225 | 
1226 | ### 6.2 Package/Group Test Handling
1227 | 
1228 | **Scenario: Reflex to Package Test**
1229 | 
1230 | 1. **Setup**: Create a test package "Diabetes Panel" containing:
1231 |    - Glucose test (Chemistry category)
1232 |    - HbA1c test (Chemistry category)
1233 |    - Fructosamine test (Chemistry category)
1234 | 2. **CDS Rule**: Trigger reflex to a parameter in Glucose test
1235 | 3. **Approval**: Clinician approves reflex
1236 | 4. **Verification**:
1237 |    - All tests in package are included
1238 |    - All tests belong to same category (Chemistry)
1239 |    - Single reflex request created with all 3 tests
1240 |    - If package spanned categories, multiple requests created
1241 | 
1242 | **Test SQL:**
1243 | ```sql
1244 | -- Check all tests created
1245 | SELECT lrt.test_id, t.name, tc.name as category
1246 | FROM laboratory_request_test lrt
1247 | JOIN tests t ON lrt.test_id = t.id
1248 | JOIN test_categories tc ON t.test_category_id = tc.id
1249 | WHERE lrt.laboratory_request_id = {reflex_request_id};
1250 | ```
1251 | 
1252 | ### 6.3 Priority Inheritance
1253 | 
1254 | **Scenario: Priority Propagation**
1255 | 
1256 | 1. **Setup**: Create parent request with priority = "STAT"
1257 | 2. **Trigger**: Result triggers reflex
1258 | 3. **Approve**: Create reflex test
1259 | 4. **Verify**:
1260 |    ```sql
1261 |    SELECT priority_level FROM laboratory_requests 
1262 |    WHERE id = {reflex_request_id};
1263 |    -- Should return "STAT"
1264 |    ```
1265 | 
1266 | **Test with different priorities:**
1267 | - STAT → STAT
1268 | - ROUTINE → ROUTINE
1269 | - URGENT → URGENT
1270 | 
1271 | **Also verify inherited fields:**
1272 | - patient_id (same as parent)
1273 | - doctor_id (same as parent)
1274 | - ward_id (same as parent)
1275 | - room_id (same as parent)
1276 | - patient_type_id (same as parent)
1277 | 
1278 | **Verify NOT inherited:**
1279 | - sample_id (different, unique)
1280 | - request_date (current timestamp)
1281 | - created_by (current user)
1282 | 
1283 | ### 6.4 Duplicate Prevention
1284 | 
1285 | **Scenario: Prevent Duplicate Reflex Creation**
1286 | 
1287 | 1. **First Approval**:
1288 |    ```bash
1289 |    curl -X POST ".../approve-reflex-test" \
1290 |      -d '{"rule_id": 1, "reflex_test_id": 5}'
1291 |    ```
1292 |    - Creates reflex request(s)
1293 |    - Audit entry: 'reflex_created'
1294 | 
1295 | 2. **Second Approval Attempt**:
1296 |    ```bash
1297 |    curl -X POST ".../approve-reflex-test" \
1298 |      -d '{"rule_id": 1, "reflex_test_id": 5}'
1299 |    ```
1300 |    - Returns empty array
1301 |    - No new requests created
1302 |    - Log: "Reflex test already exists"
1303 | 
1304 | 3. **Verification**:
1305 |    ```sql
1306 |    -- Count reflex requests for this parent+rule
1307 |    SELECT COUNT(*) FROM laboratory_requests 
1308 |    WHERE reflex_parent_request_id = 1 
1309 |      AND reflex_triggered_by_rule_id = 1;
1310 |    -- Should be 1
1311 |    ```
1312 | 
1313 | ### 6.5 Denial Workflow
1314 | 
1315 | **Scenario: Denied Reflex Cannot Be Re-Approved**
1316 | 
1317 | 1. **Denial**:
1318 |    ```bash
1319 |    curl -X POST ".../deny-reflex-test" \
1320 |      -d '{"rule_id": 1, "reason": "Not clinically needed"}'
1321 |    ```
1322 |    - Audit entry: 'reflex_denied'
1323 | 
1324 | 2. **Attempt Approval**:
1325 |    ```bash
1326 |    curl -X POST ".../approve-reflex-test" \
1327 |      -d '{"rule_id": 1, "reflex_test_id": 5}'
1328 |    ```
1329 |    - Returns empty array (hasExistingReflex returns true from audit)
1330 |    - No requests created
1331 | 
1332 | 3. **Verification**:
1333 |    ```sql
1334 |    -- Should have only 'reflex_denied' entry, no 'reflex_created'
1335 |    SELECT decision FROM rule_decision_audit 
1336 |    WHERE laboratory_request_id = 1 AND rule_id = 1;
1337 |    ```
1338 | 
1339 | ---
1340 | 
1341 | ## 7. Data Integrity Checks
1342 | 
1343 | ### 7.1 Reflex Request Validation
1344 | 
1345 | ```sql
1346 | -- 1. All reflex requests have is_reflex = true
1347 | SELECT COUNT(*) as non_reflex_orphans
1348 | FROM laboratory_requests 
1349 | WHERE is_reflex = 0 
1350 |   AND (reflex_parent_request_id IS NOT NULL OR reflex_triggered_by_rule_id IS NOT NULL);
1351 | 
1352 | -- Expected: 0
1353 | 
1354 | -- 2. Reflex parent references are valid
1355 | SELECT COUNT(*) as invalid_parent_refs
1356 | FROM laboratory_requests 
1357 | WHERE reflex_parent_request_id IS NOT NULL 
1358 |   AND reflex_parent_request_id NOT IN (SELECT id FROM laboratory_requests);
1359 | 
1360 | -- Expected: 0
1361 | 
1362 | -- 3. Reflex rule references are valid
1363 | SELECT COUNT(*) as invalid_rule_refs
1364 | FROM laboratory_requests 
1365 | WHERE reflex_triggered_by_rule_id IS NOT NULL 
1366 |   AND reflex_triggered_by_rule_id NOT IN (SELECT id FROM cds_correlation_rules);
1367 | 
1368 | -- Expected: 0
1369 | 
1370 | -- 4. Reflex parent is not itself (circular reference prevention)
1371 | SELECT COUNT(*) as circular_refs
1372 | FROM laboratory_requests 
1373 | WHERE id = reflex_parent_request_id;
1374 | 
1375 | -- Expected: 0
1376 | ```
1377 | 
1378 | ### 7.2 Reflex Test Items Validation
1379 | 
1380 | ```sql
1381 | -- 1. All reflex test items have is_reflex_test = true
1382 | SELECT COUNT(*) as non_reflex_test_items
1383 | FROM laboratory_request_test 
1384 | WHERE is_reflex_test = 1 
1385 |   AND (reflex_triggered_at IS NULL OR reflex_triggered_by_rule_id IS NULL);
1386 | 
1387 | -- Expected: 0
1388 | 
1389 | -- 2. Reflex test items reference valid requests
1390 | SELECT COUNT(*) as invalid_request_refs
1391 | FROM laboratory_request_test 
1392 | WHERE is_reflex_test = 1 
1393 |   AND laboratory_request_id NOT IN (SELECT id FROM laboratory_requests);
1394 | 
1395 | -- Expected: 0
1396 | 
1397 | -- 3. Reflex test items reference valid rules
1398 | SELECT COUNT(*) as invalid_rule_refs
1399 | FROM laboratory_request_test 
1400 | WHERE is_reflex_test = 1 
1401 |   AND reflex_triggered_by_rule_id NOT IN (SELECT id FROM cds_correlation_rules);
1402 | 
1403 | -- Expected: 0
1404 | 
1405 | -- 4. Reflex test items have timestamps
1406 | SELECT COUNT(*) as missing_timestamps
1407 | FROM laboratory_request_test 
1408 | WHERE is_reflex_test = 1 
1409 |   AND reflex_triggered_at IS NULL;
1410 | 
1411 | -- Expected: 0
1412 | ```
1413 | 
1414 | ### 7.3 Audit Trail Completeness
1415 | 
1416 | ```sql
1417 | -- 1. All reflex creations have audit entries
1418 | SELECT COUNT(*) as reflex_requests_without_audit
1419 | FROM laboratory_requests lr
1420 | WHERE lr.is_reflex = 1
1421 |   AND NOT EXISTS (
1422 |     SELECT 1 FROM rule_decision_audit rda
1423 |     WHERE rda.laboratory_request_id = lr.reflex_parent_request_id
1424 |       AND rda.rule_id = lr.reflex_triggered_by_rule_id
1425 |       AND rda.decision = 'reflex_created'
1426 |   );
1427 | 
1428 | -- Expected: 0
1429 | 
1430 | -- 2. All reflex denials have audit entries
1431 | SELECT COUNT(*) as denied_reflexes_without_audit
1432 | FROM laboratory_requests lr
1433 | WHERE lr.is_reflex = 1
1434 |   AND NOT EXISTS (
1435 |     SELECT 1 FROM rule_decision_audit rda
1436 |     WHERE rda.laboratory_request_id = lr.reflex_parent_request_id
1437 |       AND rda.rule_id = lr.reflex_triggered_by_rule_id
1438 |       AND rda.decision = 'reflex_denied'
1439 |   );
1440 | 
1441 | -- Expected: 0 (or count of denials you expect)
1442 | 
1443 | -- 3. Audit entries have user_id
1444 | SELECT COUNT(*) as orphaned_audit_entries
1445 | FROM rule_decision_audit 
1446 | WHERE user_id IS NULL 
1447 |   OR user_id NOT IN (SELECT id FROM users);
1448 | 
1449 | -- Expected: 0
1450 | ```
1451 | 
1452 | ### 7.4 Sample ID Uniqueness
1453 | 
1454 | ```sql
1455 | -- Verify all sample IDs are unique
1456 | SELECT sample_id, COUNT(*) as count
1457 | FROM laboratory_requests
1458 | GROUP BY sample_id
1459 | HAVING count > 1;
1460 | 
1461 | -- Expected: empty result set
1462 | 
1463 | -- Verify reflex sample IDs differ from parent
1464 | SELECT COUNT(*) as matching_sample_ids
1465 | FROM laboratory_requests lr
1466 | JOIN laboratory_requests parent ON lr.reflex_parent_request_id = parent.id
1467 | WHERE lr.sample_id = parent.sample_id;
1468 | 
1469 | -- Expected: 0
1470 | ```
1471 | 
1472 | ---
1473 | 
1474 | ## 8. Performance Testing
1475 | 
1476 | ### 8.1 Bulk Reflex Creation
1477 | 
1478 | **Test Scenario:** Create multiple reflex requests simultaneously
1479 | 
1480 | ```bash
1481 | # Use a script to trigger multiple reflex approvals
1482 | for i in {1..10}; do
1483 |   curl -X POST "http://lis.test/api/results/laboratory-requests/1/approve-reflex-test" \
1484 |     -H "Authorization: Bearer {token}" \
1485 |     -d "{\"rule_id\": 1, \"reflex_test_id\": 5}" &
1486 | done
1487 | wait
1488 | ```
1489 | 
1490 | **Expected Behavior:**
1491 | - No race conditions in `hasExistingReflex()` check
1492 | - Only first request creates reflex, others return empty array
1493 | - Database locks handled properly
1494 | - No duplicate entries
1495 | 
1496 | **Monitoring:**
1497 | ```sql
1498 | -- Check count after concurrent requests
1499 | SELECT COUNT(*) FROM laboratory_requests 
1500 | WHERE reflex_parent_request_id = 1 AND reflex_triggered_by_rule_id = 1;
1501 | -- Should be 1
1502 | ```
1503 | 
1504 | ### 8.2 Worklist Loading with Many Reflex Requests
1505 | 
1506 | **Test Scenario:** Load worklist with 100+ reflex requests
1507 | 
1508 | ```sql
1509 | -- Generate test data
1510 | INSERT INTO laboratory_requests (
1511 |     patient_id, doctor_id, patient_type_id, sample_id, 
1512 |     priority_level, is_reflex, reflex_parent_request_id, 
1513 |     reflex_triggered_by_rule_id, created_at
1514 | )
1515 | SELECT 
1516 |     patient_id, doctor_id, patient_type_id,
1517 |     CONCAT('BULK-', UUID()),
1518 |     'ROUTINE',
1519 |     1, -- is_reflex
1520 |     FLOOR(RAND() * 10) + 1, -- random parent
1521 |     1, -- rule_id
1522 |     NOW()
1523 | FROM laboratory_requests
1524 | WHERE is_reflex = 0
1525 | LIMIT 100;
1526 | ```
1527 | 
1528 | **Expected Behavior:**
1529 | - Worklist loads in < 2 seconds
1530 | - Pagination works correctly
1531 | - Filter "Show Only Reflex Tests" works efficiently
1532 | - No timeouts or memory issues
1533 | 
1534 | ### 8.3 Database Indexing
1535 | 
1536 | **Recommended Indexes:**
1537 | ```sql
1538 | -- Add these indexes if not present for performance
1539 | 
1540 | -- laboratory_requests
1541 | CREATE INDEX idx_laboratory_requests_reflex_parent ON laboratory_requests(reflex_parent_request_id);
1542 | CREATE INDEX idx_laboratory_requests_reflex_rule ON laboratory_requests(reflex_triggered_by_rule_id);
1543 | CREATE INDEX idx_laboratory_requests_is_reflex ON laboratory_requests(is_reflex);
1544 | 
1545 | -- laboratory_request_test
1546 | CREATE INDEX idx_laboratory_request_test_reflex_rule ON laboratory_request_test(reflex_triggered_by_rule_id);
1547 | CREATE INDEX idx_laboratory_request_test_is_reflex ON laboratory_request_test(is_reflex_test);
1548 | 
1549 | -- rule_decision_audit
1550 | CREATE INDEX idx_rule_decision_audit_parent_rule ON rule_decision_audit(laboratory_request_id, rule_id, decision);
1551 | ```
1552 | 
1553 | **Verification:**
1554 | ```sql
1555 | -- Check if indexes exist
1556 | SHOW INDEX FROM laboratory_requests;
1557 | SHOW INDEX FROM laboratory_request_test;
1558 | ```
1559 | 
1560 | ---
1561 | 
1562 | ## 9. User Acceptance Testing (UAT)
1563 | 
1564 | ### 9.1 Clinical Staff Workflow
1565 | 
1566 | **Test Participants:**
1567 | - Clinicians (doctors, residents)
1568 | - Laboratory technologists
1569 | - Pathologists
1570 | 
1571 | **Scenario 1: Clinician Approves Reflex**
1572 | 1. Clinician enters patient result (e.g., high glucose)
1573 | 2. System shows reflex suggestion modal automatically
1574 | 3. Clinician reviews suggested reflex test (HbA1c)
1575 | 4. Clinician clicks "Approve & Order"
1576 | 5. System confirms success
1577 | 6. Clinician sees new test order in worklist
1578 | 
1579 | **Acceptance Criteria:**
1580 | - Modal appears automatically without manual refresh
1581 | - Information is clear: what test, why it's recommended
1582 | - Approval is one-click
1583 | - Confirmation is immediate and clear
1584 | - New order appears in correct worklist
1585 | 
1586 | **Scenario 2: Clinician Denies Reflex**
1587 | 1. Clinician sees reflex suggestion
1588 | 2. Clinician clicks "Deny"
1589 | 3. Optional: Enter reason (e.g., "Patient already has recent HbA1c")
1590 | 4. System confirms denial
1591 | 5. No new order created
1592 | 
1593 | **Acceptance Criteria:**
1594 | - Denial is simple
1595 | - Reason field is optional but available
1596 | - No new order cluttered the worklist
1597 | - Denial is logged for audit
1598 | 
1599 | **Scenario 3: Technologist Processes Reflex**
1600 | 1. Reflex order appears in worklist with "REFLEX" badge
1601 | 2. Technologist sees it's a reflex (not original order)
1602 | 3. Can view parent request by clicking "Reflex Origin" link
1603 | 4. Processes reflex test normally
1604 | 5. Results appear in final report
1605 | 
1606 | **Acceptance Criteria:**
1607 | - Reflex orders are clearly marked
1608 | - Easy to distinguish from regular orders
1609 | - Can trace back to parent request
1610 | - Normal workflow applies
1611 | 
1612 | ### 9.2 Usability Checklist
1613 | 
1614 | - [ ] Reflex alerts are noticeable but not obtrusive
1615 | - [ ] "Approve" and "Deny" buttons are clearly labeled
1616 | - [ ] Reflex orders in worklist have distinct visual styling
1617 | - [ ] "REFLEX" badge is prominently displayed
1618 | - [ ] Parent request ID link is clickable and intuitive
1619 | - [ ] Filter "Show Only Reflex Tests" works as expected
1620 | - [ ] No confusion between reflex and non-reflex tests
1621 | - [ ] Audit trail is accessible for compliance
1622 | 
1623 | ### 9.3 Compliance and Audit
1624 | 
1625 | **Test: Complete Audit Trail**
1626 | 1. Perform reflex approval
1627 | 2. Perform reflex denial
1628 | 3. Query database for audit entries
1629 | 
1630 | **Expected:**
1631 | ```sql
1632 | SELECT * FROM rule_decision_audit 
1633 | WHERE laboratory_request_id = {parent_id}
1634 | ORDER BY created_at;
1635 | ```
1636 | 
1637 | Should show:
1638 | - Entry 1: decision = 'reflex_created', user_id = approver, timestamp
1639 | - Entry 2: decision = 'reflex_denied', user_id = denier, timestamp (if both actions performed)
1640 | 
1641 | Each entry should have:
1642 | - rule_id
1643 | - rule_type = 'CDS' or 'cds_correlation'
1644 | - message describing action
1645 | - context with relevant IDs
1646 | - user_id (who performed action)
1647 | 
1648 | ---
1649 | 
1650 | ## 10. Rollback Testing
1651 | 
1652 | ### 10.1 Migration Rollback
1653 | 
1654 | ```bash
1655 | # 1. Backup database (important!)
1656 | mysqldump -u username -p database_name > backup_before_rollback.sql
1657 | 
1658 | # 2. Rollback reflex migrations
1659 | cd backend
1660 | php artisan migrate:rollback --step=2
1661 | 
1662 | # Expected output:
1663 | # Rolled back: 2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table
1664 | # Rolled back: 2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table
1665 | ```
1666 | 
1667 | **Verification:**
1668 | ```sql
1669 | -- Columns should be removed
1670 | SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
1671 | -- Should return empty set
1672 | 
1673 | SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
1674 | -- Should return empty set
1675 | ```
1676 | 
1677 | ### 10.2 Application Behavior After Rollback
1678 | 
1679 | **Test: Application Still Functions**
1680 | 
1681 | 1. **Backend API:**
1682 |    - Existing endpoints should still work
1683 |    - Reflex-specific endpoints (approve-reflex-test, deny-reflex-test) should return 404 or graceful error
1684 |    - No crashes or exceptions
1685 | 
1686 | 2. **Frontend:**
1687 |    - Pages load without JavaScript errors
1688 |    - Reflex-related UI elements should be hidden or disabled
1689 |    - No broken features
1690 | 
1691 | **Expected Graceful Degradation:**
1692 | - Reflex features silently disabled
1693 | - No impact on core LIS functionality
1694 | - Users can still use all non-reflex features
1695 | 
1696 | ### 10.3 Re-apply Migrations
1697 | 
1698 | ```bash
1699 | # Re-apply migrations
1700 | php artisan migrate
1701 | 
1702 | # Verify columns restored
1703 | SHOW COLUMNS FROM laboratory_requests LIKE 'is_reflex';
1704 | SHOW COLUMNS FROM laboratory_request_test LIKE 'is_reflex_test';
1705 | 
1706 | # Expected: columns exist with correct definitions
1707 | ```
1708 | 
1709 | ### 10.4 Data Preservation
1710 | 
1711 | **Check: No Data Loss**
1712 | 
1713 | ```sql
1714 | -- After rollback and re-migrate, existing non-reflex data should be intact
1715 | SELECT COUNT(*) FROM patients;
1716 | SELECT COUNT(*) FROM laboratory_requests WHERE is_reflex = 0;
1717 | -- Should match pre-rollback counts
1718 | 
1719 | -- Reflex-specific data will be lost (columns dropped)
1720 | -- This is expected - ensure this is acceptable or backup first
1721 | ```
1722 | 
1723 | ---
1724 | 
1725 | ## Troubleshooting
1726 | 
1727 | ### Issue: "Column not found: 1054 Unknown column 'is_reflex' in 'field list'"
1728 | 
1729 | **Solution:** Migrations not run. Execute:
1730 | ```bash
1731 | php artisan migrate
1732 | ```
1733 | 
1734 | ### Issue: "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row"
1735 | 
1736 | **Solution:** Foreign key constraint fails. Ensure referenced data exists:
1737 | ```sql
1738 | SELECT id FROM cds_correlation_rules WHERE id = {rule_id};
1739 | SELECT id FROM laboratory_requests WHERE id = {parent_id};
1740 | ```
1741 | 
1742 | ### Issue: Reflex request not created, but no error
1743 | 
1744 | **Check:**
1745 | 1. Verify `hasExistingReflex()` returns false
1746 | 2. Check logs: `storage/logs/laravel.log`
1747 | 3. Verify rule has `reflex_test_id` populated
1748 | 4. Check test parameter exists and belongs to a test
1749 | 
1750 | ### Issue: Reflex request created but no tests attached
1751 | 
1752 | **Check:**
1753 | 1. Verify `getTestsForReflexParameter()` returns tests
1754 | 2. Check if test belongs to package and package has tests
1755 | 3. Verify test_category_id is set on tests
1756 | 
1757 | ### Issue: Frontend modal doesn't show reflex approval
1758 | 
1759 | **Check:**
1760 | 1. CDS check endpoint returns reflex alerts with `action_type: 'REFLEX_TEST'`
1761 | 2. Frontend receives and processes alerts correctly
1762 | 3. Check browser console for JavaScript errors
1763 | 4. Verify `showViolationsModal` is triggered on modal open
1764 | 
1765 | ### Issue: "Reflex Origin" column not showing
1766 | 
1767 | **Check:**
1768 | 1. `showOnlyReflex` checkbox is checked
1769 | 2. Worklist requests have `is_reflex = true`
1770 | 3. `reflex_parent_request_id` is set on reflex requests
1771 | 4. Component logic for column visibility
1772 | 
1773 | ---
1774 | 
1775 | ## Appendix
1776 | 
1777 | ### A. Test Data Seeder
1778 | 
1779 | Create `database/seeders/ReflexTestSeeder.php` for consistent test data:
1780 | 
1781 | ```php
1782 | <?php
1783 | 
1784 | namespace Database\Seeders;
1785 | 
1786 | use Illuminate\Database\Seeder;
1787 | use App\Models\Patient;
1788 | use App\Models\Doctor;
1789 | use App\Models\TestCategory;
1790 | use App\Models\Test;
1791 | use App\Models\TestParameter;
1792 | use App\Models\CdsCorrelationRule;
1793 | use App\Models\LaboratoryRequest;
1794 | use App\Models\LaboratoryRequestTest;
1795 | use App\Models\User;
1796 | 
1797 | class ReflexTestSeeder extends Seeder
1798 | {
1799 |     public function run()
1800 |     {
1801 |         // Create user for testing
1802 |         $user = User::firstOrCreate(
1803 |             ['email' => 'test@lab.com'],
1804 |             ['name' => 'Test User', 'password' => bcrypt('password')]
1805 |         );
1806 | 
1807 |         // Create patient
1808 |         $patient = Patient::firstOrCreate(
1809 |             ['id' => 'TEST001'],
1810 |             [
1811 |                 'last_name' => 'TestPatient',
1812 |                 'first_name' => 'John',
1813 |                 'date_of_birth' => '1990-05-15',
1814 |                 'gender' => 'Male',
1815 |             ]
1816 |         );
1817 | 
1818 |         // Create doctor
1819 |         $doctor = Doctor::firstOrCreate(
1820 |             ['email' => 'test.doctor@hospital.com'],
1821 |             ['name' => 'Dr. Test Physician']
1822 |         );
1823 | 
1824 |         // Create category and test
1825 |         $category = TestCategory::firstOrCreate(
1826 |             ['name' => 'Chemistry'],
1827 |             ['description' => 'Chemistry Tests']
1828 |         );
1829 | 
1830 |         $test = Test::firstOrCreate(
1831 |             ['name' => 'Glucose', 'test_category_id' => $category->id],
1832 |             ['description' => 'Blood glucose', 'normal_range' => '70-100', 'unit' => 'mg/dL']
1833 |         );
1834 | 
1835 |         // Create parameters
1836 |         $glucoseParam = TestParameter::firstOrCreate(
1837 |             ['test_id' => $test->id, 'name' => 'Glucose'],
1838 |             ['normal_range' => '70-100', 'unit' => 'mg/dL', 'display_order' => 1]
1839 |         );
1840 | 
1841 |         $hbA1cParam = TestParameter::firstOrCreate(
1842 |             ['test_id' => $test->id, 'name' => 'HbA1c'],
1843 |             ['normal_range' => '4.0-5.6', 'unit' => '%', 'display_order' => 2]
1844 |         );
1845 | 
1846 |         // Create CDS rule
1847 |         $rule = CdsCorrelationRule::firstOrCreate(
1848 |             ['name' => 'High Glucose Reflex'],
1849 |             [
1850 |                 'description' => 'Triggers when glucose > 200',
1851 |                 'trigger_condition' => json_encode(['parameter_name' => 'Glucose', 'operator' => '>', 'value' => 200]),
1852 |                 'alert_message' => 'High glucose detected. Consider HbA1c test.',
1853 |                 'action_type' => 'REFLEX_TEST',
1854 |                 'reflex_test_id' => $hbA1cParam->id,
1855 |                 'requires_approval' => 1,
1856 |                 'is_active' => 1,
1857 |             ]
1858 |         );
1859 | 
1860 |         // Create laboratory request
1861 |         $request = LaboratoryRequest::create([
1862 |             'patient_id' => $patient->id,
1863 |             'doctor_id' => $doctor->id,
1864 |             'patient_type_id' => 1,
1865 |             'sample_id' => 'TEST-SAMPLE-' . time(),
1866 |             'priority_level' => 'ROUTINE',
1867 |             'created_by' => $user->id,
1868 |         ]);
1869 | 
1870 |         // Create test item
1871 |         LaboratoryRequestTest::create([
1872 |             'laboratory_request_id' => $request->id,
1873 |             'test_id' => $test->id,
1874 |             'item_status' => 'pending',
1875 |         ]);
1876 | 
1877 |         echo "Test data created:\n";
1878 |         echo "- Patient: {$patient->id} - {$patient->last_name}, {$patient->first_name}\n";
1879 |         echo "- Request: {$request->id} - Sample: {$request->sample_id}\n";
1880 |         echo "- CDS Rule: {$rule->id} - {$rule->name}\n";
1881 |         echo "- Reflex Parameter: {$hbA1cParam->id} - {$hbA1cParam->name}\n";
1882 |     }
1883 | }
1884 | ```
1885 | 
1886 | Run seeder:
1887 | ```bash
1888 | php artisan db:seed --class=ReflexTestSeeder
1889 | ```
1890 | 
1891 | ### B. API Testing with Postman Collection
1892 | 
1893 | Import this collection into Postman:
1894 | 
1895 | ```json
1896 | {
1897 |   "info": {
1898 |     "name": "Reflex Test API",
1899 |     "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
1900 |   },
1901 |   "item": [
1902 |     {
1903 |       "name": "CDS Check",
1904 |       "request": {
1905 |         "method": "GET",
1906 |         "header": [
1907 |           { "key": "Authorization", "value": "Bearer {{token}}" }
1908 |         ],
1909 |         "url": { "raw": "{{base_url}}/api/results/check-cds/{{request_id}}" }
1910 |       }
1911 |     },
1912 |     {
1913 |       "name": "Approve Reflex",
1914 |       "request": {
1915 |         "method": "POST",
1916 |         "header": [
1917 |           { "key": "Content-Type", "value": "application/json" },
1918 |           { "key": "Authorization", "value": "Bearer {{token}}" }
1919 |         ],
1920 |         "body": {
1921 |           "mode": "raw",
1922 |           "raw": "{\n  \"rule_id\": {{rule_id}},\n  \"reflex_test_id\": {{reflex_test_id}},\n  \"auto_create\": false\n}"
1923 |         },
1924 |         "url": { "raw": "{{base_url}}/api/results/laboratory-requests/{{request_id}}/approve-reflex-test" }
1925 |       }
1926 |     },
1927 |     {
1928 |       "name": "Deny Reflex",
1929 |       "request": {
1930 |         "method": "POST",
1931 |         "header": [
1932 |           { "key": "Content-Type", "value": "application/json" },
1933 |           { "key": "Authorization", "value": "Bearer {{token}}" }
1934 |         ],
1935 |         "body": {
1936 |           "mode": "raw",
1937 |           "raw": "{\n  \"rule_id\": {{rule_id}},\n  \"reason\": \"Not clinically indicated\"\n}"
1938 |         },
1939 |         "url": { "raw": "{{base_url}}/api/results/laboratory-requests/{{request_id}}/deny-reflex-test" }
1940 |       }
1941 |     }
1942 |   ]
1943 | }
1944 | ```
1945 | 
1946 | ### C. Useful Queries for Monitoring
1947 | 
1948 | ```sql
1949 | -- Daily reflex creation count
1950 | SELECT 
1951 |     DATE(created_at) as date,
1952 |     COUNT(*) as reflex_count
1953 | FROM laboratory_requests
1954 | WHERE is_reflex = 1
1955 |   AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
1956 | GROUP BY DATE(created_at)
1957 | ORDER BY date DESC;
1958 | 
1959 | -- Most common reflex rules
1960 | SELECT 
1961 |     r.name as rule_name,
1962 |     COUNT(lr.id) as reflex_count
1963 | FROM laboratory_requests lr
1964 | JOIN cds_correlation_rules r ON lr.reflex_triggered_by_rule_id = r.id
1965 | WHERE lr.is_reflex = 1
1966 | GROUP BY r.id, r.name
1967 | ORDER BY reflex_count DESC
1968 | LIMIT 10;
1969 | 
1970 | -- Reflex approval/denial ratio
1971 | SELECT 
1972 |     decision,
1973 |     COUNT(*) as count,
1974 |     ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
1975 | FROM rule_decision_audit
1976 | WHERE rule_type = 'CDS'
1977 |   AND decision IN ('reflex_created', 'reflex_denied')
1978 |   AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
1979 | GROUP BY decision;
1980 | 
1981 | -- Reflex requests by priority
1982 | SELECT 
1983 |     priority_level,
1984 |     COUNT(*) as count
1985 | FROM laboratory_requests
1986 | WHERE is_reflex = 1
1987 | GROUP BY priority_level
1988 | ORDER BY FIELD(priority_level, 'STAT', 'URGENT', 'ROUTINE');
1989 | ```
1990 | 
1991 | ---
1992 | 
1993 | ## Document Summary
1994 | 
1995 | This comprehensive testing guide covers:
1996 | 
1997 | ✅ **Pre-Testing Setup** - Database preparation, test data creation
1998 | ✅ **Database Migration Testing** - Migration execution, rollback, constraints
1999 | ✅ **Backend Service Testing** - All ReflexTestService methods with edge cases
2000 | ✅ **API Endpoint Testing** - CDS check, approve, deny with curl examples
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:28:06.512Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Read and analyze UniversalResultModal.vue | Pending |
| 2 | Read UniversalResultModal-Reflex-broken.vue for comparison | Pending |
| 3 | Read CdsAlertBanner.vue to understand existing alert system | Pending |
| 4 | Read backend controllers and models for reflex tracking | Pending |
| 5 | Read API documentation and testing guide | Pending |
| 6 | Identify all issues in current implementation | Pending |
| 7 | Design comprehensive solution | Pending |
| 8 | Implement centralized alert banner component | Pending |
| 9 | Update UniversalResultModal with proper reflex handling | Pending |
| 10 | Add isMounted guards to all async operations | Pending |
| 11 | Implement visual state management (red→blue/green→gray) | Pending |
| 12 | Add auto-dismiss with countdown timer | Pending |
| 13 | Ensure audit trail logging | Pending |
| 14 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 15 | Verify all functions remain intact | Pending |
| 16 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Now I have a complete understanding of the system. Let me update my todo list and create a comprehensive implementation plan:
[Tool Use: update_todo_list]
Todos: [x] Read and analyze UniversalResultModal.vue
[x] Read UniversalResultModal-Reflex-broken.vue for comparison
[x] Read CdsAlertBanner.vue to understand existing alert system
[x] Read backend controllers and models for reflex tracking
[x] Read API documentation and testing guide
[-] Identify all issues in current implementation
[ ] Design comprehensive solution
[ ] Implement centralized alert banner component
[ ] Update UniversalResultModal with proper reflex handling
[ ] Add isMounted guards to all async operations
[ ] Implement visual state management (red→blue/green→gray)
[ ] Add auto-dismiss with countdown timer
[ ] Ensure audit trail logging
[ ] Integrate with worklist (badges, filtering, navigation)
[ ] Verify all functions remain intact
[ ] Test for edge cases and memory leaks

---

**User:**

[Tool]
Todo list updated successfully.
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:28:28.317Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Read and analyze UniversalResultModal.vue | Completed |
| 2 | Read UniversalResultModal-Reflex-broken.vue for comparison | Completed |
| 3 | Read CdsAlertBanner.vue to understand existing alert system | Completed |
| 4 | Read backend controllers and models for reflex tracking | Completed |
| 5 | Read API documentation and testing guide | Completed |
| 6 | Identify all issues in current implementation | In Progress |
| 7 | Design comprehensive solution | Pending |
| 8 | Implement centralized alert banner component | Pending |
| 9 | Update UniversalResultModal with proper reflex handling | Pending |
| 10 | Add isMounted guards to all async operations | Pending |
| 11 | Implement visual state management (red→blue/green→gray) | Pending |
| 12 | Add auto-dismiss with countdown timer | Pending |
| 13 | Ensure audit trail logging | Pending |
| 14 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 15 | Verify all functions remain intact | Pending |
| 16 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Now I'll analyze the issues and design the comprehensive solution. Let me create the implementation plan:

## Issues Identified:

1. **UniversalResultModal.vue** (current version):
   - ❌ Missing `isMounted` guards on async operations
   - ❌ No `onBeforeUnmount` lifecycle hook
   - ❌ No active timeout tracking/cleanup
   - ❌ Uses inline banner instead of centralized `CdsAlertBanner`
   - ❌ No auto-dismiss with countdown for success/denied states
   - ❌ Potential memory leaks from unhandled async callbacks

2. **CdsAlertBanner.vue**:
   - ✅ Has good structure but needs styling updates
   - ✅ Has transient state management
   - ✅ Has isMounted guards (but missing onBeforeUnmount)
   - ⚠️ Needs visual state improvements (red→blue/green→gray)

3. **Backend**:
   - ✅ API endpoints exist and work
   - ✅ Audit trail implemented
   - ✅ Reflex tracking in models

4. **Worklist Integration**:
   - ⚠️ Need to verify CategoryResults.vue has reflex badges and filtering

Let me start implementing the fixes:
[Tool Use: update_todo_list]
Todos: [x] Identify all issues in current implementation
[x] Design comprehensive solution
[-] Implement centralized alert banner component (CdsAlertBanner.vue)
[ ] Update UniversalResultModal with proper reflex handling
[ ] Add isMounted guards to all async operations
[ ] Implement visual state management (red→blue/green→gray)
[ ] Add auto-dismiss with countdown timer
[ ] Ensure audit trail logging (backend already has it)
[ ] Integrate with worklist (badges, filtering, navigation)
[ ] Verify all functions remain intact
[ ] Test for edge cases and memory leaks

---

**User:**

[Tool]
Todo list updated successfully.
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:30:19.929Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>orchestrator</slug>
<name>🪃 Orchestrator</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Identify all issues in current implementation | Completed |
| 2 | Design comprehensive solution | Completed |
| 3 | Implement centralized alert banner component (CdsAlertBanner.vue) | In Progress |
| 4 | Update UniversalResultModal with proper reflex handling | Pending |
| 5 | Add isMounted guards to all async operations | Pending |
| 6 | Implement visual state management (red→blue/green→gray) | Pending |
| 7 | Add auto-dismiss with countdown timer | Pending |
| 8 | Ensure audit trail logging (backend already has it) | Pending |
| 9 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 10 | Verify all functions remain intact | Pending |
| 11 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

I'll now implement the complete solution. Let me start by enhancing the `CdsAlertBanner.vue` component with proper lifecycle management and visual state improvements:
[Tool Use: read_file]
Path: frontend/src/components/CdsAlertBanner.vue
Mode: slice
Offset: 1
Limit: 1000

---

**User:**

[Tool (Error)]
{"status":"error","message":"The tool execution failed","error":"Tool \"read_file\" is not allowed in orchestrator mode."}
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:30:34.189Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>orchestrator</slug>
<name>🪃 Orchestrator</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Identify all issues in current implementation | Completed |
| 2 | Design comprehensive solution | Completed |
| 3 | Implement centralized alert banner component (CdsAlertBanner.vue) | In Progress |
| 4 | Update UniversalResultModal with proper reflex handling | Pending |
| 5 | Add isMounted guards to all async operations | Pending |
| 6 | Implement visual state management (red→blue/green→gray) | Pending |
| 7 | Add auto-dismiss with countdown timer | Pending |
| 8 | Ensure audit trail logging (backend already has it) | Pending |
| 9 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 10 | Verify all functions remain intact | Pending |
| 11 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

I cannot read files in orchestrator mode. Let me switch to code mode to implement the solution:
[Tool Use: switch_mode]
Mode_slug: code
Reason: Need to implement file changes for reflex alert system improvements

---

**User:**

[Tool]
{"status":"denied","feedback":"When you implement do it per subtask."}
<environment_details>
# VSCode Visible Files
frontend/src/components/modals/result/UniversalResultModal.vue

# VSCode Open Tabs
frontend/src/components/ReportDesigner.vue,frontend/src/views/Phlebotomy.vue,frontend/src/views/EquipmentManagement/ConsumablesManagement.vue,frontend/src/global-design-system.css,frontend/src/style.css,LIS_SYSTEM_SPECIFICATION.md,README.md,backend/app/Http/Controllers/Api/ManagementReportController.php,frontend/src/views/ManagementReport/TurnAroundTime.vue,backend/database/migrations/2025_06_18_000000_add_reflex_tracking_to_laboratory_requests_table.php,backend/database/migrations/2025_06_18_000001_add_reflex_tracking_to_laboratory_request_test_table.php,backend/app/Http/Controllers/Api/ResultManagementController.php,frontend/src/components/modals/result/ViolationsSummaryModal.vue,frontend/src/views/ResultManagement/CategoryResults.vue,frontend/src/components/modals/result/UniversalResultModal-Reflex-broken.vue,frontend/src/components/modals/result/UniversalResultModal.vue,backend/app/Models/LaboratoryRequest.php,backend/app/Models/LaboratoryRequestTest.php,REFLEX_TEST_TESTING_GUIDE.md,REFLEX_TEST_API_DOCUMENTATION.md

# Current Time
Current time in ISO 8601 UTC format: 2026-03-27T08:31:14.677Z
User time zone: Asia/Manila, UTC+8:00

# Current Cost
$0.00

# Current Mode
<slug>orchestrator</slug>
<name>🪃 Orchestrator</name>
<model>stepfun/step-3.5-flash:free</model>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Identify all issues in current implementation | Completed |
| 2 | Design comprehensive solution | Completed |
| 3 | Implement centralized alert banner component (CdsAlertBanner.vue) | In Progress |
| 4 | Update UniversalResultModal with proper reflex handling | Pending |
| 5 | Add isMounted guards to all async operations | Pending |
| 6 | Implement visual state management (red→blue/green→gray) | Pending |
| 7 | Add auto-dismiss with countdown timer | Pending |
| 8 | Ensure audit trail logging (backend already has it) | Pending |
| 9 | Integrate with worklist (badges, filtering, navigation) | Pending |
| 10 | Verify all functions remain intact | Pending |
| 11 | Test for edge cases and memory leaks | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

