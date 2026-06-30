<?php

use Illuminate\Support\Facades\Route;

// Routes HĐĐT — group + controller được thêm ở Task 11.
Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.feature:einvoice'])
    ->prefix('api/v1/einvoice')->group(function () {
        // (Task 11) account routes
    });
