<?php

use CMBcoreSeller\Http\Controllers\HealthController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\ChannelAccountController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\SyncLogController;
use CMBcoreSeller\Modules\Customers\Http\Controllers\CustomerController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\InventoryController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\SkuController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\SkuMappingController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\WarehouseController;
use CMBcoreSeller\Modules\Orders\Http\Controllers\DashboardController;
use CMBcoreSeller\Modules\Orders\Http\Controllers\OrderController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ChannelListingController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ProductController;
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
            Route::patch('channel-accounts/{id}', [ChannelAccountController::class, 'update'])->whereNumber('id')->name('channel-accounts.update');   // set display alias
            Route::delete('channel-accounts/{id}', [ChannelAccountController::class, 'destroy'])->whereNumber('id')->name('channel-accounts.destroy');
            Route::post('channel-accounts/{id}/resync', [ChannelAccountController::class, 'resync'])->whereNumber('id')->name('channel-accounts.resync');
            Route::post('channel-accounts/{id}/resync-listings', [ChannelAccountController::class, 'resyncListings'])->whereNumber('id')->name('channel-accounts.resync-listings');

            // --- Sync log (Phase 1) — webhook_events / sync_runs + re-drive ---
            Route::get('sync-runs', [SyncLogController::class, 'runs'])->name('sync-runs.index');
            Route::post('sync-runs/{id}/redrive', [SyncLogController::class, 'redriveRun'])->whereNumber('id')->name('sync-runs.redrive');
            Route::get('webhook-events', [SyncLogController::class, 'webhookEvents'])->name('webhook-events.index');
            Route::post('webhook-events/{id}/redrive', [SyncLogController::class, 'redriveWebhook'])->whereNumber('id')->name('webhook-events.redrive');

            // --- Orders (Phase 1 + manual orders Phase 2) ---
            Route::get('orders/stats', [OrderController::class, 'stats'])->name('orders.stats');
            Route::post('orders/sync', [OrderController::class, 'sync'])->name('orders.sync');             // resync all active shops
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::post('orders', [OrderController::class, 'store'])->name('orders.store');               // manual order
            Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id')->name('orders.show');
            Route::patch('orders/{id}', [OrderController::class, 'update'])->whereNumber('id')->name('orders.update');   // manual order edit
            Route::post('orders/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id')->name('orders.cancel');
            Route::post('orders/{id}/tags', [OrderController::class, 'updateTags'])->whereNumber('id')->name('orders.tags');
            Route::patch('orders/{id}/note', [OrderController::class, 'updateNote'])->whereNumber('id')->name('orders.note');
            Route::get('orders/unmapped-skus', [SkuMappingController::class, 'unmappedFromOrders'])->name('orders.unmapped-skus');   // SPEC 0004
            Route::post('orders/link-skus', [SkuMappingController::class, 'linkFromOrders'])->name('orders.link-skus');

            // --- Products / SKUs / Inventory / Listings & SKU mapping (Phase 2 / SPEC 0003 + 0004) ---
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::get('products/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('products.show');
            Route::patch('products/{id}', [ProductController::class, 'update'])->whereNumber('id')->name('products.update');
            Route::delete('products/{id}', [ProductController::class, 'destroy'])->whereNumber('id')->name('products.destroy');

            Route::get('skus', [SkuController::class, 'index'])->name('skus.index');
            Route::post('skus', [SkuController::class, 'store'])->name('skus.store');
            Route::get('skus/{id}', [SkuController::class, 'show'])->whereNumber('id')->name('skus.show');
            Route::patch('skus/{id}', [SkuController::class, 'update'])->whereNumber('id')->name('skus.update');
            Route::delete('skus/{id}', [SkuController::class, 'destroy'])->whereNumber('id')->name('skus.destroy');
            Route::post('skus/{id}/image', [SkuController::class, 'uploadImage'])->whereNumber('id')->name('skus.image.upload');
            Route::delete('skus/{id}/image', [SkuController::class, 'deleteImage'])->whereNumber('id')->name('skus.image.delete');

            Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
            Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
            Route::patch('warehouses/{id}', [WarehouseController::class, 'update'])->whereNumber('id')->name('warehouses.update');

            Route::get('inventory/levels', [InventoryController::class, 'levels'])->name('inventory.levels');
            Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
            Route::post('inventory/bulk-adjust', [InventoryController::class, 'bulkAdjust'])->name('inventory.bulk-adjust');     // SPEC 0004
            Route::post('inventory/push-stock', [InventoryController::class, 'pushStock'])->name('inventory.push-stock');       // SPEC 0004
            Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

            Route::get('channel-listings', [ChannelListingController::class, 'index'])->name('channel-listings.index');
            Route::post('channel-listings/sync', [ChannelListingController::class, 'sync'])->name('channel-listings.sync');
            Route::patch('channel-listings/{id}', [ChannelListingController::class, 'update'])->whereNumber('id')->name('channel-listings.update');
            Route::post('sku-mappings', [SkuMappingController::class, 'store'])->name('sku-mappings.store');
            Route::post('sku-mappings/auto-match', [SkuMappingController::class, 'autoMatch'])->name('sku-mappings.auto-match');
            Route::delete('sku-mappings/{id}', [SkuMappingController::class, 'destroy'])->whereNumber('id')->name('sku-mappings.destroy');

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
