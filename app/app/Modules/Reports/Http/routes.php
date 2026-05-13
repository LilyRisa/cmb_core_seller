<?php

use CMBcoreSeller\Modules\Reports\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Reports routes — /api/v1/reports/* (Phase 6.1 / SPEC 0015).
 |--------------------------------------------------------------------------
 | `reports.view`: index endpoints; `reports.export`: CSV stream (UTF-8 BOM cho Excel).
 */

Route::middleware(['api', 'auth:sanctum', 'tenant'])->prefix('api/v1/reports')->group(function () {
    Route::get('revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('profit', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('top-products', [ReportController::class, 'topProducts'])->name('reports.top-products');
    Route::get('export', [ReportController::class, 'export'])->name('reports.export');
});
