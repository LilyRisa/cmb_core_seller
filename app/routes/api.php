<?php

use CMBcoreSeller\Http\Controllers\HealthController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\ChannelAccountController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\SyncLogController;
use CMBcoreSeller\Modules\Customers\Http\Controllers\CustomerController;
use CMBcoreSeller\Modules\Orders\Http\Controllers\DashboardController;
use CMBcoreSeller\Modules\Orders\Http\Controllers\OrderController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\AuthController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes — prefix /api, versioned under /v1.
|--------------------------------------------------------------------------
| Conventions: docs/05-api/conventions.md, docs/05-api/endpoints.md. SPA auth:
| Sanctum cookie (call GET /sanctum/csrf-cookie first). Webhooks live in
| routes/webhook.php; OAuth callbacks in routes/web.php.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // --- Health (DB / cache / Redis / queue worker probe) ---
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

            // --- Channels (Phase 1) — connected shops & OAuth connect ---
            Route::get('channel-accounts', [ChannelAccountController::class, 'index'])->name('channel-accounts.index');
            Route::post('channel-accounts/{provider}/connect', [ChannelAccountController::class, 'connect'])
                ->whereIn('provider', ['tiktok', 'shopee', 'lazada'])->name('channel-accounts.connect');
            Route::delete('channel-accounts/{id}', [ChannelAccountController::class, 'destroy'])->whereNumber('id')->name('channel-accounts.destroy');
            Route::post('channel-accounts/{id}/resync', [ChannelAccountController::class, 'resync'])->whereNumber('id')->name('channel-accounts.resync');

            // --- Sync log (Phase 1) — webhook_events / sync_runs + re-drive ---
            Route::get('sync-runs', [SyncLogController::class, 'runs'])->name('sync-runs.index');
            Route::post('sync-runs/{id}/redrive', [SyncLogController::class, 'redriveRun'])->whereNumber('id')->name('sync-runs.redrive');
            Route::get('webhook-events', [SyncLogController::class, 'webhookEvents'])->name('webhook-events.index');
            Route::post('webhook-events/{id}/redrive', [SyncLogController::class, 'redriveWebhook'])->whereNumber('id')->name('webhook-events.redrive');

            // --- Orders (Phase 1) ---
            Route::get('orders/stats', [OrderController::class, 'stats'])->name('orders.stats');
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id')->name('orders.show');
            Route::post('orders/{id}/tags', [OrderController::class, 'updateTags'])->whereNumber('id')->name('orders.tags');
            Route::patch('orders/{id}/note', [OrderController::class, 'updateNote'])->whereNumber('id')->name('orders.note');

            // --- Customers (Phase 2 / SPEC 0002) — internal buyer registry & reputation ---
            Route::post('customers/merge', [CustomerController::class, 'merge'])->name('customers.merge');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{id}', [CustomerController::class, 'show'])->whereNumber('id')->name('customers.show');
            Route::get('customers/{id}/orders', [CustomerController::class, 'orders'])->whereNumber('id')->name('customers.orders');
            Route::post('customers/{id}/notes', [CustomerController::class, 'storeNote'])->whereNumber('id')->name('customers.notes.store');
            Route::delete('customers/{id}/notes/{noteId}', [CustomerController::class, 'destroyNote'])->whereNumber('id')->whereNumber('noteId')->name('customers.notes.destroy');
            Route::post('customers/{id}/block', [CustomerController::class, 'block'])->whereNumber('id')->name('customers.block');
            Route::post('customers/{id}/unblock', [CustomerController::class, 'unblock'])->whereNumber('id')->name('customers.unblock');
            Route::post('customers/{id}/tags', [CustomerController::class, 'tags'])->whereNumber('id')->name('customers.tags');

            // --- Dashboard ---
            Route::get('dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
        });
    });
});
