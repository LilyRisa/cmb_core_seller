<?php

use CMBcoreSeller\Http\Controllers\HealthController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\AuthController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes — prefix /api, versioned under /v1.
|--------------------------------------------------------------------------
| Conventions: docs/05-api/conventions.md. SPA auth: Sanctum cookie
| (call GET /sanctum/csrf-cookie first). Webhooks live in routes/webhook.php;
| OAuth callbacks in routes/web.php.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // --- Health (DB / cache / Redis / queue worker probe; see docs/07-infra/observability-and-backup.md §4) ---
    Route::get('health', HealthController::class)->name('health');

    // --- Auth (public) ---
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // --- Authenticated, tenant-agnostic ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');

        // --- Authenticated + a chosen tenant (header X-Tenant-Id) ---
        Route::middleware('tenant')->group(function () {
            Route::get('tenant', [TenantController::class, 'show'])->name('tenant.show');
            Route::get('tenant/members', [TenantController::class, 'members'])->name('tenant.members');
            Route::post('tenant/members', [TenantController::class, 'addMember'])->name('tenant.members.add');

            // Future module routes (orders, inventory, products, fulfillment, ...) mount here,
            // ideally registered from each module's service provider.
        });
    });
});
