<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\TestCategoryController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\LaboratoryRequestController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\Api\PaymentController;

use App\Http\Controllers\AuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // --- Add your other protected LIS API routes here ---
    // Example:
    // Route::get('/patients', [PatientController::class, 'index']);
    // Route::post('/patients', [PatientController::class, 'store']);
    // ... etc.
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Dashboard routes
Route::get('/dashboard', [DashboardController::class, 'index']);

// Patient routes
Route::apiResource('patients', PatientController::class);

// Doctor routes
Route::apiResource('doctors', DoctorController::class);

// Test Category routes
Route::apiResource('test-categories', TestCategoryController::class);

// Test routes
Route::apiResource('tests', TestController::class);
Route::get('/test-categories-list', [TestController::class, 'getCategories']);

// Laboratory Request routes
Route::apiResource('laboratory-requests', LaboratoryRequestController::class);
Route::get('/patients-list', [LaboratoryRequestController::class, 'getPatients']);
Route::get('/doctors-list', [LaboratoryRequestController::class, 'getDoctors']);
Route::get('/tests-list', [LaboratoryRequestController::class, 'getTests']);

// Test Result routes
Route::apiResource('test-results', TestResultController::class);
Route::get('/pending-laboratory-requests', [TestResultController::class, 'getLaboratoryRequests']);
Route::get('/laboratory-requests/{laboratoryRequest}/pending-tests', [TestResultController::class, 'getTestsByRequest']);

// Payment routes
Route::apiResource('payments', PaymentController::class);
Route::get('/unpaid-requests', [PaymentController::class, 'getUnpaidRequests']);
Route::get('/payment-summary', [PaymentController::class, 'getPaymentSummary']);
