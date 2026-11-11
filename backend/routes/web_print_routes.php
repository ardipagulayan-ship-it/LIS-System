<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportPdfController;

// Signed preview route used by Browsershot (no auth but signed)
Route::get('/internal/print/preview/{templateId}/{sampleId}', [ReportPdfController::class, 'preview'])
    ->name('reports.preview');
