<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import API controllers
use App\Http\Controllers\Api\DashboardController; // Assuming you have this
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\TestCategoryController; // Assuming you have this
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\LaboratoryRequestController;
use App\Http\Controllers\Api\TestResultController; // Assuming you have this
use App\Http\Controllers\Api\PaymentController;    // Assuming you have this
use App\Http\Controllers\Api\PhlebotomyController;
use App\Http\Controllers\Api\OrderManagementController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\WardController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\PatientTypeController;
use App\Http\Controllers\Api\TestParameterController;
use App\Http\Controllers\Api\AnalyzerController;
use App\Http\Controllers\Api\ResultManagementController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReferenceRangeController;

// Import Auth controller
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\LisBridgeConfigController;
use App\Http\Controllers\Api\LisBridgeReceiverController;

// Import Quality Control controllers
use App\Http\Controllers\Api\QualityControlController;
use App\Http\Controllers\Api\QualityControlResultController;
use App\Http\Controllers\Api\QualityControlTestParameterController;
use App\Http\Controllers\Api\QualityControlLockoutController;
use App\Http\Controllers\Api\CorrectiveActionController;
use App\Http\Controllers\Api\SigmaMetricController;
use App\Http\Controllers\Api\ManagementReportController;
use App\Http\Controllers\Api\DeltaCheckReportController;
use App\Http\Controllers\Api\CdsRuleController;
use App\Http\Controllers\Api\DeltaCheckRuleController;
use App\Http\Controllers\Api\QcRuleController;
use App\Http\Controllers\Api\DeltaCheckSeverityLevelController;
use App\Http\Controllers\Api\CensusReportController;
use App\Http\Controllers\Api\SystemSettingController;

// Import EIM controllers
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\ServiceLogController;
use App\Http\Controllers\Api\EIMDashboardController;
use App\Http\Controllers\Api\ConsumableController;
use App\Http\Controllers\Api\InventoryManagementController;
use App\Http\Controllers\Api\EquipmentUsageController;

// Import Formula controller
use App\Http\Controllers\Api\FormulaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (Login)
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Report Layouts & Groups (Result Form Builder)
    Route::prefix('report-layouts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ReportLayoutController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\ReportLayoutController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ReportLayoutController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\ReportLayoutController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\ReportLayoutController::class, 'destroy']);
    });

    Route::prefix('report-groups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ReportGroupController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\ReportGroupController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ReportGroupController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\ReportGroupController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\ReportGroupController::class, 'destroy']);
        Route::put('/assign-parameter/{parameterId}', [\App\Http\Controllers\Api\ReportGroupController::class, 'assignParameter']);
    });

    // Print / PDF generation with grouping
    Route::prefix('print')->group(function () {
        Route::get('/grouped-pdf/{laboratoryRequestId}', [\App\Http\Controllers\Api\PrintController::class, 'generateGroupedPdf']);
        Route::get('/grouped-results/{laboratoryRequestId}', [\App\Http\Controllers\Api\PrintController::class, 'getGroupedResults']);
        // Browsershot-based template PDF generation
        Route::post('/template/{templateId}/sample/{sampleId}', [\App\Http\Controllers\ReportPdfController::class, 'generatePdf']);
    });

    Route::get('/system-settings', [SystemSettingController::class, 'index']);
    Route::get('/system-settings/{key}', [SystemSettingController::class, 'show']);
    Route::put('/system-settings/{key}', [SystemSettingController::class, 'update']);

    // --- Dashboard / Stats Routes ---
    // Existing general dashboard route (if used)
    // Route::get('/dashboard', [DashboardController::class, 'index']);
    // New route for Front Desk specific stats
    Route::get('/dashboard/frontdesk-stats', [DashboardController::class, 'getFrontDeskStats']);
    Route::get('/dashboard/phlebotomy-stats', [DashboardController::class, 'getPhlebotomyStats']);

    // --- Front Desk Module API Routes ---

    // Patient Management (Full CRUD)
    Route::apiResource('patients', PatientController::class);

    // Doctor Listing (Only index for now, for selection)
    Route::get('doctors', [DoctorController::class, 'index']);
    // If you need full Doctor CRUD later, use: Route::apiResource('doctors', DoctorController::class);

    // Test Listing (Only index for now, for selection)
    Route::get('tests', [TestController::class, 'index']);
    // If you need full Test CRUD later, use: Route::apiResource('tests', TestController::class);

    // Sample ID Generation
    Route::get('/laboratory-requests/generate-sample-id', [LaboratoryRequestController::class, 'generateSampleId']);

    // Laboratory Requests (Full CRUD)
    Route::apiResource('laboratory-requests', LaboratoryRequestController::class);
    Route::post('/laboratory-requests/{laboratoryRequest}/acknowledge-violations', [LaboratoryRequestController::class, 'acknowledgeViolations']);
    Route::get('/laboratory-requests/{laboratoryRequest}/audit-trail', [LaboratoryRequestController::class, 'getAuditTrail']);
    
    // Reflex test endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/laboratory-requests/{laboratoryRequest}/approve-reflex-test', [ResultManagementController::class, 'approveReflexTest']);
        Route::post('/laboratory-requests/{laboratoryRequest}/deny-reflex-test', [ResultManagementController::class, 'denyReflexTest']);
    });

    // Helper routes for Select Dropdowns in Frontend Forms
    Route::get('/patients-list', [LaboratoryRequestController::class, 'getPatients']);
    Route::get('/doctors-list', [LaboratoryRequestController::class, 'getDoctors']);
    Route::get('/tests-list/{category?}', [LaboratoryRequestController::class, 'getTests']);

    // Fetch tests list - Modified to accept optional category
    Route::get('/tests-list/{category?}', [LaboratoryRequestController::class, 'getTests']);

    // Existing doctors list route
    //Route::get('/doctors-list', [DoctorController::class, 'list']);

    // Add routes for Order Management
    // Phlebotomy routes
    Route::get('/order/requests', [OrderManagementController::class, 'getRequests'])->name('order.requests');
    Route::get('/order/stats', [OrderManagementController::class, 'getPhlebotomyStats'])->name('order.stats'); // Corrected stat route if needed
    Route::post('/order/requests/collect/{item_id}', [OrderManagementController::class, 'markAsCollected']);
    Route::post('/order/requests/accept/{item_id}', [OrderManagementController::class, 'markAsAccepted']);
    Route::post('/order/requests/reject/{item_id}', [OrderManagementController::class, 'markAsRejected']);
    Route::post('/order/requests/collect-batch', [OrderManagementController::class, 'markBatchAsCollected']);
    Route::post('/order/requests/accept-batch', [OrderManagementController::class, 'markBatchAsAccepted']);
    Route::post('/order/requests/reject-batch', [OrderManagementController::class, 'markBatchAsRejected']); // Added batch reject route
    // Phlebotomy routes
    Route::get('/phlebotomy/requests', [PhlebotomyController::class, 'getRequests'])->name('phlebotomy.requests');
    Route::get('/phlebotomy/stats', [PhlebotomyController::class, 'getPhlebotomyStats'])->name('phlebotomy.stats'); // Corrected stat route if needed
    Route::post('/phlebotomy/requests/collect/{item_id}', [PhlebotomyController::class, 'markAsCollected']);
    Route::post('/phlebotomy/requests/accept/{item_id}', [PhlebotomyController::class, 'markAsAccepted']);
    Route::post('/phlebotomy/requests/reject/{item_id}', [PhlebotomyController::class, 'markAsRejected']);
    Route::post('/phlebotomy/requests/collect-batch', [PhlebotomyController::class, 'markBatchAsCollected']);
    Route::post('/phlebotomy/requests/accept-batch', [PhlebotomyController::class, 'markBatchAsAccepted']);
    Route::post('/phlebotomy/requests/reject-batch', [PhlebotomyController::class, 'markBatchAsRejected']); // Added batch reject route

    // --- Other Existing Protected Routes ---
    // (Keep your other existing routes like Dashboard, Test Categories, Results, Payments etc. here)

    // Example:
    // Route::get('/dashboard', [DashboardController::class, 'index']);
    // Route::apiResource('test-categories', TestCategoryController::class);
    // Route::apiResource('test-results', TestResultController::class);
    // Route::apiResource('payments', PaymentController::class);
    // Route::get('/unpaid-requests', [PaymentController::class, 'getUnpaidRequests']);
    // Route::get('/payment-summary', [PaymentController::class, 'getPaymentSummary']);
    // Route::get('/pending-laboratory-requests', [TestResultController::class, 'getLaboratoryRequests']);
    // Route::get('/laboratory-requests/{laboratoryRequest}/pending-tests', [TestResultController::class, 'getTestsByRequest']);

    // Note: The second Route::get('/user', ...) definition you had previously is redundant and can be removed.

    // Dropdown/Form Data Helpers
    Route::get('/wards', [WardController::class, 'index']);
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/get-rooms', [RoomController::class, 'show']);
    Route::get('/patient-types', [PatientTypeController::class, 'index']);

    // --- Result Management Routes ---
    Route::prefix('results')->middleware('auth:sanctum')->group(function () {
        Route::get('dashboard/stats', [ResultManagementController::class, 'getDashboardStats'])->name('results.dashboard.stats');
        Route::get('dashboard/tat', [ResultManagementController::class, 'getTatData'])->name('results.dashboard.tat');
        Route::get('category/{categoryId}', [ResultManagementController::class, 'getCategoryRequests'])->name('results.category');
        Route::get('category/{categoryId}/request/{laboratoryRequest}', [ResultManagementController::class, 'getCategoryRequestDetails'])->name('results.category.details');
        Route::get('hematology', [ResultManagementController::class, 'getHematologyRequests']);
        Route::get('hematology/{laboratoryRequest}', [ResultManagementController::class, 'getHematologyRequestDetails'])->name('results.hematology.details');
        Route::get('chemistry', [ResultManagementController::class, 'getChemistryRequests']);
        Route::get('chemistry/{laboratoryRequest}', [ResultManagementController::class, 'getChemistryRequestDetails'])->name('results.chemistry.details');
        Route::get('chemistry/parameters/{laboratoryRequest}/{test}', [ResultManagementController::class, 'getChemistryTestParameters'])->name('results.chemistry.parameters');
        Route::post('chemistry/save/{laboratoryRequest}', [ResultManagementController::class, 'saveChemistryResults'])->name('results.chemistry.save');
        Route::post('chemistry/print-release/{laboratoryRequest}', [ResultManagementController::class, 'printReleaseChemistryResults'])->name('results.chemistry.print');
        Route::post('chemistry/validate-release/{laboratoryRequest}', [ResultManagementController::class, 'validateReleaseChemistryResults'])->name('results.chemistry.validate');
        Route::post('check-cds-bulk', [ResultManagementController::class, 'bulkCheckCdsRules'])->name('results.check-cds.bulk');
        Route::get('check-cds/{laboratoryRequest}', [ResultManagementController::class, 'checkCdsRules'])->name('results.check-cds');
        Route::get('patient-history/{patientId}', [ResultManagementController::class, 'getPatientHistory'])->name('results.patient-history');
    });

    // --- CDS Correlation Rules Management ---
    Route::get('/cds-rules', [CdsRuleController::class, 'index']);
    Route::get('/cds-rules/{cdsRule}', [CdsRuleController::class, 'show']);
    Route::post('/cds-rules', [CdsRuleController::class, 'store']);
    Route::put('/cds-rules/{cdsRule}', [CdsRuleController::class, 'update']);
    Route::delete('/cds-rules/{cdsRule}', [CdsRuleController::class, 'destroy']);

    // --- Delta Check Rules Management ---
    Route::get('/delta-check-rules', [DeltaCheckRuleController::class, 'index']);
    Route::get('/delta-check-rules/{deltaCheckRule}', [DeltaCheckRuleController::class, 'show']);
    Route::post('/delta-check-rules', [DeltaCheckRuleController::class, 'store']);
    Route::put('/delta-check-rules/{deltaCheckRule}', [DeltaCheckRuleController::class, 'update']);
    Route::delete('/delta-check-rules/{deltaCheckRule}', [DeltaCheckRuleController::class, 'destroy']);
    Route::put('/delta-check-rules/{deltaCheckRule}/toggle-active', [DeltaCheckRuleController::class, 'toggleActive']);

    // --- Delta Check Severity Levels ---
    Route::get('/delta-check-severity-levels', [DeltaCheckSeverityLevelController::class, 'index']);
    Route::get('/delta-check-severity-levels/{deltaCheckSeverityLevel}', [DeltaCheckSeverityLevelController::class, 'show']);

    // --- QC Rules Management ---
    Route::get('/qc-rules', [QcRuleController::class, 'index']);
    Route::get('/qc-rules/{qcRule}', [QcRuleController::class, 'show']);
    Route::post('/qc-rules', [QcRuleController::class, 'store']);
    Route::put('/qc-rules/{qcRule}', [QcRuleController::class, 'update']);
    Route::delete('/qc-rules/{qcRule}', [QcRuleController::class, 'destroy']);
    Route::put('/qc-rules/{qcRule}/toggle-active', [QcRuleController::class, 'toggleActive']);

    // CRUD Resources (using apiResource for standard API routes)
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('wards', WardController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('patient-types', PatientTypeController::class);
    Route::apiResource('test-parameters', TestParameterController::class);
    Route::apiResource('test-categories', TestCategoryController::class);
    Route::apiResource('reference-ranges', ReferenceRangeController::class);
    Route::apiResource('tests', TestController::class);

    // Custom route to get parameters for a specific test
    Route::get('/tests/{test}/parameters', [TestParameterController::class, 'getParametersByTest'])->name('tests.parameters.index');
    Route::get('/tests/{test}/test-parameters', [TestParameterController::class, 'getParametersByTests'])->name('tests.test-parameters.index');

    // Custom route for updating test item status
    Route::patch('/laboratory-requests/{labRequest}/tests/{test}/status', [LaboratoryRequestController::class, 'updateTestItemStatus'])->name('labRequests.tests.updateStatus');

    // Custom route for updating phlebotomy tracking
    Route::patch('/laboratory-requests/{labRequest}/tests/{test}/phlebotomy', [LaboratoryRequestController::class, 'updatePhlebotomyTracking'])->name('labRequests.tests.updatePhlebotomy');

    // Analyzer routes
    Route::apiResource('analyzers', AnalyzerController::class);
    Route::get('/analyzers/by-test-category/{testCategoryId}', [AnalyzerController::class, 'getByTestCategory']);
    Route::post('/analyzers/migrate', [AnalyzerController::class, 'migrateExistingAnalyzers']);
    
    // Route to get reference ranges for a specific parameter and equipment
    Route::get('/tests/{test}/parameters/{parameter}/reference-ranges', [TestParameterController::class, 'getReferenceRangesByEquipment']);
    
    // User role routes
    Route::get('/users/role/{roleType}', [UserRoleController::class, 'getUsersByRoleType']);
    Route::apiResource('user-roles', UserRoleController::class);
    
    // User routes
    Route::apiResource('users', UserController::class);

    // Quality Control Routes
    Route::prefix('quality-controls')->group(function () {
        // Global QC recs (prioritize above wildcards)
        Route::get('/qc-recommendations', [QualityControlController::class, 'globalQCRecommendations']);
        Route::get('/recommendations', [QualityControlController::class, 'globalRecommendations']);
        Route::get('/', [QualityControlController::class, 'index']);
        Route::post('/', [QualityControlController::class, 'store']);
        Route::get('/{qualityControl}', [QualityControlController::class, 'show']);
        Route::put('/{qualityControl}', [QualityControlController::class, 'update']);
        Route::delete('/{qualityControl}', [QualityControlController::class, 'destroy']);
        Route::get('/test-parameter/{testParameter}', [QualityControlController::class, 'getByTestParameter']);
        Route::get('/equipment/{equipment}', [QualityControlController::class, 'getByEquipmentWithParameters']);
        Route::get('/{qualityControl}/levey-jennings', [QualityControlController::class, 'getLeveyJenningsData']);
        Route::get('/{qualityControl}/results-by-parameter', [QualityControlController::class, 'getResultsByParameter']);
        Route::get('/{qualityControl}/parameters', [QualityControlController::class, 'getParameters']);
    });

    // New API Resource for QualityControlTestParameter
    Route::apiResource('quality-control-test-parameters', QualityControlTestParameterController::class);

    // Quality Control Results
    Route::prefix('quality-control-test-parameters/{qualityControlTestParameter}')->group(function () {
        Route::get('/results', [QualityControlResultController::class, 'getResultsByControlAndParameter']);
        Route::post('/results', [QualityControlResultController::class, 'store']);
        Route::get('/lj-data', [QualityControlResultController::class, 'getLeveyJenningsDataByControlAndParameter']);
        Route::post('/recalculate-stats', [QualityControlResultController::class, 'recalculateStatistics']);
    });

    // Quality Control Result Actions
    Route::prefix('quality-control-results')->group(function () {
        Route::put('/{qualityControlResult}', [QualityControlResultController::class, 'update']);
        Route::delete('/{qualityControlResult}', [QualityControlResultController::class, 'destroy']);
        Route::put('/{qualityControlResult}/accept', [QualityControlResultController::class, 'accept']);
        Route::put('/{qualityControlResult}/reject', [QualityControlResultController::class, 'reject']);
    });

    // QC Lockout analyzer-specific endpoints
    Route::prefix('qc-lockout')->group(function () {
        Route::get('/status-summary', [QualityControlLockoutController::class, 'statusSummary']);
        Route::get('/locked-results/{equipmentId}', [QualityControlLockoutController::class, 'lockedResultsByEquipment']);
        Route::post('/release', [QualityControlLockoutController::class, 'release']);
    });

    // Corrective Actions
    Route::apiResource('corrective-actions', CorrectiveActionController::class);
    Route::get('/quality-control-test-parameters/{qualityControlTestParameter}/corrective-actions', 
        [CorrectiveActionController::class, 'getByQCTestParameter']);

    // Sigma Metric QC Monitor Routes
    Route::prefix('sigma-metrics')->group(function () {
        // Dashboard - get all analytes with Sigma metrics
        Route::get('/', [SigmaMetricController::class, 'index']);
        // Calculate for all analytes
        Route::post('/calculate-all', [SigmaMetricController::class, 'calculateAll']);
        // Calculate for specific test parameter
        Route::post('/test-parameters/{testParameter}/calculate', [SigmaMetricController::class, 'calculate']);
        // Get trend data for a test parameter
        Route::get('/test-parameters/{testParameter}/trend', [SigmaMetricController::class, 'getTrend']);
        // Diagnostic endpoint
        Route::get('/diagnostics', [SigmaMetricController::class, 'diagnostics']);
        // TE_a Configuration management
        Route::get('/configs', [SigmaMetricController::class, 'getConfigs']);
        Route::get('/configs/test-parameters/{testParameter}', [SigmaMetricController::class, 'getConfig']);
        Route::post('/configs', [SigmaMetricController::class, 'storeConfig']);
        Route::put('/configs/{config}', [SigmaMetricController::class, 'updateConfig']);
        Route::delete('/configs/{config}', [SigmaMetricController::class, 'deleteConfig']);
    });

    // EIM (Equipment & Instrument Management) Routes
    Route::prefix('eim')->group(function () {
        // Dashboard routes
        Route::get('/dashboard', [EIMDashboardController::class, 'index']);
        Route::get('/dashboard/compliance', [EIMDashboardController::class, 'compliance']);
        Route::get('/dashboard/cost-analysis', [EIMDashboardController::class, 'costAnalysis']);
        
        // Equipment routes
        Route::apiResource('equipment', EquipmentController::class);
        Route::post('/equipment/bulk-import', [EquipmentController::class, 'bulkImport']);
        Route::get('/equipment/{id}/barcode', [EquipmentController::class, 'generateBarcode']);
        Route::get('/equipment/{id}/statistics', [EquipmentController::class, 'statistics']);
        Route::put('/equipment/{id}/status', [EquipmentController::class, 'updateStatus']);
        
        // Maintenance Schedule routes
        Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class);
        Route::get('/maintenance-schedules/overdue', [MaintenanceScheduleController::class, 'overdue']);
        Route::get('/maintenance-schedules/due-soon', [MaintenanceScheduleController::class, 'dueSoon']);
        Route::get('/maintenance-schedules/statistics', [MaintenanceScheduleController::class, 'statistics']);
        Route::get('/maintenance-schedules/work-orders', [MaintenanceScheduleController::class, 'generateWorkOrders']);
        Route::post('/maintenance-schedules/{id}/mark-completed', [MaintenanceScheduleController::class, 'markCompleted']);
        
        // Service Log routes
        Route::apiResource('service-logs', ServiceLogController::class);
        Route::get('/service-logs/statistics', [ServiceLogController::class, 'statistics']);
        Route::get('/service-logs/equipment/{equipmentId}/history', [ServiceLogController::class, 'equipmentHistory']);
        Route::get('/service-logs/equipment/{equipmentId}/metrics', [ServiceLogController::class, 'equipmentMetrics']);
        Route::post('/service-logs/{id}/signature', [ServiceLogController::class, 'addSignature']);
        
        // Consumables routes
        Route::apiResource('consumables', ConsumableController::class);
        Route::post('/consumables/{id}/deduct-stock', [ConsumableController::class, 'deductStock']);
        Route::post('/consumables/{id}/add-stock', [ConsumableController::class, 'addStock']);
        Route::get('/consumables/alerts/expiration', [ConsumableController::class, 'getExpirationAlerts']);
        Route::get('/consumables/alerts/low-stock', [ConsumableController::class, 'getLowStockAlerts']);
        Route::get('/consumables/stats/dashboard', [ConsumableController::class, 'getDashboardStats']);
        
        // Inventory Management Module (IMM) routes
        Route::prefix('imm')->group(function () {
            // Dashboard and Statistics
            Route::get('/dashboard', [InventoryManagementController::class, 'getDashboardStats']);
            
            // Material Master (Catalog) routes
            Route::get('/materials', [InventoryManagementController::class, 'getMaterials']);
            Route::post('/materials', [InventoryManagementController::class, 'createMaterial']);
            Route::put('/materials/{id}', [InventoryManagementController::class, 'updateMaterial']);
            Route::delete('/materials/{id}', [InventoryManagementController::class, 'deleteMaterial']);
            
            // Lot and Stock Record routes
            Route::get('/lots', [InventoryManagementController::class, 'getLots']);
            Route::post('/lots', [InventoryManagementController::class, 'createLot']);
            Route::put('/lots/{id}', [InventoryManagementController::class, 'updateLot']);
            Route::delete('/lots/{id}', [InventoryManagementController::class, 'deleteLot']);
            Route::post('/lots/{lotId}/release-quarantine', [InventoryManagementController::class, 'releaseFromQuarantine']);
            Route::post('/lots/{lotId}/deduct-stock', [InventoryManagementController::class, 'deductStock']);
            Route::get('/lots/{lotId}/performance', [InventoryManagementController::class, 'getLotPerformance']);
            
            // Vendor/Supplier routes
            Route::get('/vendors', [InventoryManagementController::class, 'getVendors']);
            Route::post('/vendors', [InventoryManagementController::class, 'createVendor']);
            
            // Alerts and Reports
            Route::get('/alerts/expiration', [InventoryManagementController::class, 'getExpirationAlerts']);
            Route::get('/alerts/low-stock', [InventoryManagementController::class, 'getLowStockAlerts']);
            Route::get('/reports/reorder', [InventoryManagementController::class, 'getReorderReport']);
        });

        // Equipment Usage routes
        Route::prefix('equipment-usage')->group(function () {
            Route::get('/all-equipment', [EquipmentUsageController::class, 'getAllEquipment']);
            Route::get('/statistics', [EquipmentUsageController::class, 'getStatistics']);
            Route::get('/assignments', [EquipmentUsageController::class, 'getAssignments']);
            Route::post('/assignments', [EquipmentUsageController::class, 'createAssignment']);
            Route::delete('/assignments/{id}', [EquipmentUsageController::class, 'deleteAssignment']);
            Route::post('/assignments/{id}/deactivate', [EquipmentUsageController::class, 'deactivateAssignment']);
            Route::get('/usage', [EquipmentUsageController::class, 'getUsage']);
            Route::get('/usage-history', [EquipmentUsageController::class, 'getUsageHistory']);
            Route::post('/usage', [EquipmentUsageController::class, 'recordUsage']);
        });

    // Equipment routes - add endpoint for getting equipment by test category
    Route::get('/equipment/by-test-category/{testCategoryId}', [EquipmentController::class, 'getByTestCategory']);
    });

    // Formula Auto-Computation Module
    Route::prefix('formulas')->group(function () {
        Route::get('/categories', [FormulaController::class, 'getTestCategories']);
        Route::get('/parameters', [FormulaController::class, 'getTestParameters']);
        Route::get('/parameter/{parameterId}/dependents', [FormulaController::class, 'getDependentFormulas']);
        Route::post('', [FormulaController::class, 'store']);
        Route::get('', [FormulaController::class, 'index']);
        Route::get('{id}', [FormulaController::class, 'show']);
        Route::put('{id}', [FormulaController::class, 'update']);
        Route::delete('{id}', [FormulaController::class, 'destroy']);
    });
});

// Management Report Routes (temporarily outside auth middleware)
Route::prefix('management-report')->group(function () {
    Route::get('/worksheet', [ManagementReportController::class, 'getWorksheetData']);
    Route::get('/census', [CensusReportController::class, 'getCensusData']);
    Route::get('/column-metadata', [ManagementReportController::class, 'getColumnMetadata']);
    Route::get('/test-categories', [ManagementReportController::class, 'getTestCategories']);
    Route::get('/tat', [ManagementReportController::class, 'getTATData']);
    Route::get('/tat/detailed', [ManagementReportController::class, 'getDetailedTATData']);

    // Delta Check Report Route
    Route::get('/delta-checks', [DeltaCheckReportController::class, 'index']);
    Route::get('/delta-checks/statistics', [DeltaCheckReportController::class, 'statistics']);
    Route::get('/delta-checks/recommendations/{testParameterId}', [DeltaCheckReportController::class, 'getRecommendations']);
    Route::post('/delta-checks/update-limits/{testParameterId}', [DeltaCheckReportController::class, 'updateLimits']);
});

// Route for calculating flag and conventional result on-the-fly
Route::post('/results/calculate-derived', [TestResultController::class, 'calculateDerivedData']);

// Route for saving test results for a laboratory request
Route::post('/laboratory-requests/{laboratoryRequest}/results', [TestResultController::class, 'store']);
Route::put('/laboratory-requests/{laboratoryRequest}/results', [TestResultController::class, 'update']);
// Route for updating the status of all tests in a laboratory request
Route::put('/laboratory-requests/{laboratoryRequest}/status', [LaboratoryRequestController::class, 'updateRequestStatus']);
Route::post('/laboratory-requests/{laboratoryRequest}/update-remarks', [TestResultController::class, 'updateRemarks']);
// Routes for delta check actions (Accept/Reject/Re-run)
Route::post('/laboratory-requests/{laboratoryRequest}/delta-check/accept', [LaboratoryRequestController::class, 'acceptDeltaCheckResult']);
Route::post('/laboratory-requests/{laboratoryRequest}/delta-check/reject', [LaboratoryRequestController::class, 'rejectDeltaCheckResult']);
Route::post('/laboratory-requests/{laboratoryRequest}/delta-check/rerun', [LaboratoryRequestController::class, 'rerunDeltaCheckResult']);
Route::post('/laboratory-requests/{laboratoryRequest}/reject', [LaboratoryRequestController::class, 'rejectRequest']);
// Equipment ids for a laboratory request
Route::get('/laboratory-requests/{laboratoryRequest}/equipment', [TestResultController::class, 'getEquipmentForRequest']);
// Results by equipment for a specific request (fallback for QC log)
Route::get('/laboratory-requests/{laboratoryRequest}/results-by-equipment/{equipmentId}', [TestResultController::class, 'getResultsByRequestAndEquipment']);

// Test Categories
Route::get('/test-categories', [TestCategoryController::class, 'index']);
Route::get('/test-categories/{categoryId}/tests', [TestCategoryController::class, 'getTests']);
Route::get('/get-test-categories', [TestCategoryController::class, 'show']);
// Route::apiResource('reference-ranges', [ReferenceRangeController::class]);

// Doctors Corner module
// Route::apiResource('consultations', \App\Http\Controllers\API\ConsultationController::class);
// Route::post('consultations/{id}/medical-history', [\App\Http\Controllers\API\ConsultationController::class, 'addMedicalHistory']);
// Route::post('consultations/{id}/physical-exam', [\App\Http\Controllers\API\ConsultationController::class, 'addPhysicalExam']);
// Route::post('consultations/{id}/diagnosis', [\App\Http\Controllers\API\ConsultationController::class, 'addDiagnosis']);
// Route::post('consultations/{id}/prescription', [\App\Http\Controllers\API\ConsultationController::class, 'addPrescription']);
// Route::post('consultations/{id}/lab-request', [\App\Http\Controllers\API\ConsultationController::class, 'createLabRequest']);

// Report Templates API
Route::prefix('report-templates')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\ReportTemplateController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\ReportTemplateController::class, 'store']);
    Route::post('/validate-schema', [\App\Http\Controllers\ReportTemplateController::class, 'validateSchema']);
    Route::get('/search', [\App\Http\Controllers\ReportTemplateController::class, 'search']);
    Route::get('/{id}', [\App\Http\Controllers\ReportTemplateController::class, 'show']);
    Route::get('/{id}/schema', [\App\Http\Controllers\ReportTemplateController::class, 'getSchema']);
    Route::get('/{id}/preview-data', [\App\Http\Controllers\ReportTemplateController::class, 'previewData']);
    Route::put('/{id}', [\App\Http\Controllers\ReportTemplateController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\ReportTemplateController::class, 'destroy']);
    Route::post('/{id}/clone', [\App\Http\Controllers\ReportTemplateController::class, 'clone']);
});

// ─────────────────────────────────────────────────────────────────────────────
// LIS Bridge Receiver Endpoints
// These routes are OUTSIDE the auth:sanctum group — they use their own
// VerifyLisBridgeApiKey middleware which validates Bearer API tokens.
//
// All three routes accept Content-Type: application/json and return HTTP 204
// on success. The Python forwarder expects 2xx for SYNCED status.
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['lis.bridge', 'throttle:lis.bridge'])->group(function () {
    // POST /api/v1/patient-results
    // Receives patient (non-QC) result payloads from the Python forwarder.
    Route::post('/patient-results', [LisBridgeReceiverController::class, 'ingestPatient']);

    // POST /api/v1/qc-results
    // Receives QC result payloads from the Python forwarder.
    Route::post('/qc-results', [LisBridgeReceiverController::class, 'ingestQc']);
});

// POST /api/lis-bridge/test-connection
// Reachability probe used by the forwarder's admin UI.
// Returns 204 regardless of body — its sole purpose is to prove the endpoint exists.
// ─────────────────────────────────────────────────────────────────────────────
// LIS Bridge Admin API
// These routes are inside auth:sanctum — only authenticated lab staff may
// manage bridge configs and API keys. The Python forwarder does NOT use these.
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Bridge configs
    Route::get('/lis-bridge/configs', [LisBridgeConfigController::class, 'index']);
    Route::post('/lis-bridge/configs', [LisBridgeConfigController::class, 'store']);
    Route::get('/lis-bridge/configs/{id}', [LisBridgeConfigController::class, 'show']);
    Route::put('/lis-bridge/configs/{id}', [LisBridgeConfigController::class, 'update']);
    Route::delete('/lis-bridge/configs/{id}', [LisBridgeConfigController::class, 'destroy']);

    // API keys for a bridge config
    Route::get('/lis-bridge/configs/{configId}/keys', [LisBridgeConfigController::class, 'listKeys']);
    Route::post('/lis-bridge/configs/{configId}/keys', [LisBridgeConfigController::class, 'createKey']);
    Route::post('/lis-bridge/configs/{configId}/keys/{keyId}/revoke', [LisBridgeConfigController::class, 'revokeKey']);
    Route::delete('/lis-bridge/configs/{configId}/keys/{keyId}', [LisBridgeConfigController::class, 'deleteKey']);

    // Test connectivity
    Route::post('/lis-bridge/configs/{configId}/test-connection', [LisBridgeConfigController::class, 'testConnection']);

    // Ingest logs
    Route::get('/lis-bridge/ingest-logs', [LisBridgeConfigController::class, 'ingestLog']);
});

// POST /api/lis-bridge/test-connection
// Public reachability probe — no auth required. Returns 200 with bridge info
// if the middleware is installed, or 404 if the endpoint doesn't exist.
Route::post('/lis-bridge/test-connection', [LisBridgeReceiverController::class, 'testConnection']);

// INTERNAL ONLY: POST /api/lis-bridge/internal-test
// Used by the Admin UI's "Test Connection" button. Bypasses the Bearer-token
// middleware (it uses auth:sanctum instead) so we can call the receiver
// endpoint without needing the raw API token.
Route::middleware('auth:sanctum')->post('/lis-bridge/internal-test', function (\Illuminate\Http\Request $request) {
    $configId = $request->validate(['config_id' => 'required|integer']);

    $config = \App\Models\LisBridgeConfig::findOrFail($configId['config_id']);
    $activeKey = $config->activeApiKey();

    if (!$activeKey) {
        return response()->json([
            'ok' => false,
            'message' => 'No active API key found for this bridge config.',
        ], 422);
    }

    $baseUrl = rtrim($config->lis_url ?: config('app.url'), '/');
    $url = $baseUrl . '/api/lis-bridge/test-connection';

    try {
        // Pass the raw token — the receiver middleware will hash and verify it.
        // We retrieve it via a direct DB query since the model hides it.
        $rawToken = \Illuminate\Support\Facades\DB::table('lis_bridge_api_keys')
            ->where('id', $activeKey->id)
            ->value('key_hash'); // <-- This IS the hash, not the raw token.

        // We cannot call the receiver with hash authentication.
        // Instead, call the internal test which only checks HTTP reachability.
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['_internal' => true]),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return response()->json([
                'ok' => false,
                'message' => 'cURL error: ' . $curlError,
            ], 503);
        }

        return response()->json([
            'ok' => $httpCode >= 200 && $httpCode < 300,
            'http_status' => $httpCode,
            'response_body' => $response,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'message' => 'Connection failed: ' . $e->getMessage(),
        ], 503);
    }
});
