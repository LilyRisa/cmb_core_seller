<?php

use CMBcoreSeller\Http\Controllers\HealthController;
use CMBcoreSeller\Http\Controllers\MediaController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\ChannelAccountController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\ShopReportController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\SyncLogController;
use CMBcoreSeller\Modules\Customers\Http\Controllers\CustomerController;
use CMBcoreSeller\Modules\Fulfillment\Http\Controllers\CarrierAccountController;
use CMBcoreSeller\Modules\Fulfillment\Http\Controllers\MasterDataController;
use CMBcoreSeller\Modules\Fulfillment\Http\Controllers\PrintJobController;
use CMBcoreSeller\Modules\Fulfillment\Http\Controllers\ShipmentController;
use CMBcoreSeller\Modules\Fulfillment\Http\Controllers\ShippingLabelTemplateController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\InventoryController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\SkuController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\SkuMappingController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\WarehouseController;
use CMBcoreSeller\Modules\Inventory\Http\Controllers\WarehouseDocumentController;
use CMBcoreSeller\Modules\Notifications\Http\Controllers\EmailVerificationController;
use CMBcoreSeller\Modules\Notifications\Http\Controllers\PasswordResetController;
use CMBcoreSeller\Modules\Orders\Http\Controllers\DashboardController;
use CMBcoreSeller\Modules\Orders\Http\Controllers\OrderController;
use CMBcoreSeller\Modules\Orders\Http\Controllers\ReturnController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ChannelListingController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ExtensionTokenController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ListingDraftController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ListingPushController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ListingTaxonomyController;
use CMBcoreSeller\Modules\Products\Http\Controllers\PromotionController;
use CMBcoreSeller\Modules\Products\Http\Controllers\ProductController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\AuthController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\MemberController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\MobileDeviceController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\RoleController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\TenantController;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\TokenAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes — prefix /api, versioned under /v1.
|--------------------------------------------------------------------------
| Conventions: docs/05-api/conventions.md, docs/05-api/endpoints.md. SPA auth:
| Sanctum cookie (call GET /sanctum/csrf-cookie first). Webhooks live in
| routes/webhook.php; OAuth callbacks in routes/web.php.
*/

Route::prefix('v1')->name('api.v1.')->middleware('throttle:120,1')->group(function () {

    // --- Health (DB / cache / Redis / queue worker probe) ---
    Route::get('health', HealthController::class)->name('health');

    // Site key CAPTCHA cho FE render widget (public, không nhạy cảm). SPEC 2026-06-10.
    Route::get('auth/captcha-config', [AuthController::class, 'captchaConfig'])->name('auth.captcha-config');

    // --- Auth (public) — rate limit strictest to prevent brute force ---
    Route::middleware('throttle:15,1')->group(function () {
        // `captcha` (SPEC 2026-06-10) chống bot/brute-force; pass-through khi CAPTCHA_ENABLED=false.
        Route::post('auth/register', [AuthController::class, 'register'])->middleware('captcha')->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->middleware('captcha')->name('auth.login');
        // Mobile / 3rd-party client — cấp bearer token (SPEC 2026-06-01). Cùng throttle với login.
        Route::post('auth/token', [TokenAuthController::class, 'token'])->name('auth.token');
    });

    // --- Email verification (SPEC 0022) — signed URL public; throttle chống brute hash ---
    Route::get('auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->where(['id' => '[0-9]+'])
        ->name('auth.email.verify');

    // --- Password reset (SPEC 0022) — public, throttle anti-enumerate + anti-brute ---
    Route::post('auth/password/forgot', [PasswordResetController::class, 'forgot'])
        ->middleware(['throttle:5,15', 'captcha'])->name('auth.password.forgot');
    Route::post('auth/password/reset', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:30,60')->name('auth.password.reset');

    // --- Authenticated, tenant-agnostic ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::patch('auth/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');   // SPEC 0011

        // Mobile token auth (SPEC 2026-06-01) — logout = thu hồi token hiện tại; quản lý thiết bị.
        Route::delete('auth/token', [TokenAuthController::class, 'revoke'])->name('auth.token.revoke');
        Route::get('auth/devices', [TokenAuthController::class, 'devices'])->name('auth.devices.index');
        Route::delete('auth/devices', [TokenAuthController::class, 'revokeOthers'])->name('auth.devices.revoke-others');
        Route::delete('auth/devices/{id}', [TokenAuthController::class, 'revokeDevice'])->whereNumber('id')->name('auth.devices.revoke');

        // SPEC 0022 — resend verification email cho user đã login nhưng chưa verify.
        Route::post('auth/email/verify/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,60')->name('auth.email.verify.resend');

        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');

        // --- Authenticated + a chosen tenant (header X-Tenant-Id) ---
        // `verified` (SPEC 0022) chặn API tới khi user verify email — JSON envelope
        // `403 EMAIL_NOT_VERIFIED`. `plan.over_quota_lock` (SPEC 0020) chặn write sau
        // 2 ngày vượt hạn mức.
        Route::middleware(['verified', 'tenant', 'plan.over_quota_lock'])->group(function () {
            Route::post('media/image', [MediaController::class, 'upload'])->name('media.image.upload');   // generic image upload (e.g. quick-add order item)
            Route::post('media/video', [MediaController::class, 'uploadVideo'])->name('media.video.upload');   // listing draft video

            // --- Mobile push device registry (SPEC 0029) — Expo push token ---
            Route::post('me/devices', [MobileDeviceController::class, 'store'])->name('me.devices.store');
            Route::delete('me/devices/{id}', [MobileDeviceController::class, 'destroy'])->whereNumber('id')->name('me.devices.destroy');

            Route::get('tenant', [TenantController::class, 'show'])->name('tenant.show');
            Route::patch('tenant', [TenantController::class, 'update'])->name('tenant.update');   // SPEC 0011 — workspace info

            // SPEC 0031 — sub-accounts & granular custom roles. Mutations gated by `team.manage`
            // inside the controllers; the permission catalog is readable by any member.
            Route::get('tenant/permissions', [RoleController::class, 'permissions'])->name('tenant.permissions');
            Route::get('tenant/roles', [RoleController::class, 'index'])->name('tenant.roles.index');
            Route::post('tenant/roles', [RoleController::class, 'store'])->name('tenant.roles.store');
            Route::put('tenant/roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('tenant.roles.update');
            Route::delete('tenant/roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('tenant.roles.destroy');

            Route::get('tenant/members', [MemberController::class, 'index'])->name('tenant.members');
            Route::post('tenant/members', [MemberController::class, 'store'])->name('tenant.members.add');
            Route::put('tenant/members/{user}', [MemberController::class, 'update'])->whereNumber('user')->name('tenant.members.update');
            Route::delete('tenant/members/{user}', [MemberController::class, 'destroy'])->whereNumber('user')->name('tenant.members.destroy');
            Route::post('tenant/members/{user}/reset-password', [MemberController::class, 'resetPassword'])->whereNumber('user')->name('tenant.members.reset-password');

            // --- Channels (Phase 1) — connected shops & OAuth connect ---
            Route::get('channel-accounts', [ChannelAccountController::class, 'index'])->name('channel-accounts.index');
            Route::get('channel-accounts/outbound-ip', [ChannelAccountController::class, 'outboundIp'])->name('channel-accounts.outbound-ip');   // IP để copy vào Lazada IP Whitelist
            // SPEC 0018 — gating hạn mức gian hàng theo gói. `402 PLAN_LIMIT_REACHED` khi
            // vượt `plan.limits.max_channel_accounts`.
            Route::post('channel-accounts/{provider}/connect', [ChannelAccountController::class, 'connect'])
                ->middleware('plan.limit:channel_accounts')
                ->whereIn('provider', ['tiktok', 'shopee', 'lazada'])->name('channel-accounts.connect');
            Route::patch('channel-accounts/{id}', [ChannelAccountController::class, 'update'])->whereNumber('id')->name('channel-accounts.update');   // set display alias
            Route::patch('channel-accounts/{id}/messaging', [ChannelAccountController::class, 'setMessaging'])->whereNumber('id')->name('channel-accounts.messaging');
            Route::patch('channel-accounts/{id}/auto-rts', [ChannelAccountController::class, 'toggleAutoRts'])->whereNumber('id')->name('channel-accounts.auto-rts');
            Route::delete('channel-accounts/{id}', [ChannelAccountController::class, 'destroy'])->whereNumber('id')->name('channel-accounts.destroy');
            Route::post('channel-accounts/{id}/resync', [ChannelAccountController::class, 'resync'])->whereNumber('id')->name('channel-accounts.resync');
            Route::post('channel-accounts/{id}/resync-unprocessed', [ChannelAccountController::class, 'resyncUnprocessed'])->whereNumber('id')->name('channel-accounts.resync-unprocessed');
            Route::post('channel-accounts/{id}/resync-listings', [ChannelAccountController::class, 'resyncListings'])->whereNumber('id')->name('channel-accounts.resync-listings');
            Route::post('channel-accounts/{id}/resync-chat', [ChannelAccountController::class, 'resyncChat'])->whereNumber('id')->name('channel-accounts.resync-chat');

            // --- Báo cáo sàn (read-only) — sức khỏe/hiệu suất/điểm phạt theo gian hàng. SPEC 2026-06-06.
            // Gated `shop_health_reports` (Pro). Mỗi sàn lộ dữ liệu khác nhau (capability per-sàn).
            Route::get('channel-shop-report', [ShopReportController::class, 'index'])
                ->middleware('plan.feature:shop_health_reports')->name('channel-shop-report.index');
            Route::post('channel-shop-report/{id}/ai-insight', [ShopReportController::class, 'aiInsight'])
                ->whereNumber('id')->middleware('plan.feature:shop_health_reports')->name('channel-shop-report.ai-insight');

            // --- Sync log (Phase 1) — webhook_events / sync_runs + re-drive ---
            Route::get('sync-runs', [SyncLogController::class, 'runs'])->name('sync-runs.index');
            Route::post('sync-runs/{id}/redrive', [SyncLogController::class, 'redriveRun'])->whereNumber('id')->name('sync-runs.redrive');
            Route::get('webhook-events', [SyncLogController::class, 'webhookEvents'])->name('webhook-events.index');
            Route::post('webhook-events/{id}/redrive', [SyncLogController::class, 'redriveWebhook'])->whereNumber('id')->name('webhook-events.redrive');

            // --- Orders (Phase 1 + manual orders Phase 2) ---
            Route::get('orders/stats', [OrderController::class, 'stats'])->name('orders.stats');
            Route::post('orders/sync', [OrderController::class, 'sync'])->name('orders.sync');             // resync all active shops
            // `abilities:orders:read` — extension token (chỉ `copy-product:push`) bị chặn 403 khỏi
            // dữ liệu đơn. SPA cookie & token `*` thoả mãn mọi ability nên không ảnh hưởng (A5).
            Route::get('orders', [OrderController::class, 'index'])->middleware('abilities:orders:read')->name('orders.index');
            // S4 (Sprint 3) — throttle riêng cho POST /orders chống spam tạo đơn manual (reserve stock).
            Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:30,1')->name('orders.store');
            Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id')->name('orders.show');
            Route::patch('orders/{id}', [OrderController::class, 'update'])->whereNumber('id')->name('orders.update');   // manual order edit
            Route::post('orders/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id')->name('orders.cancel');
            // Thao tác hàng loạt: huỷ (local, ngừng theo dõi) + xoá mềm đơn đã huỷ.
            Route::post('orders/bulk-cancel', [OrderController::class, 'bulkCancel'])->name('orders.bulk-cancel');
            Route::post('orders/bulk-delete', [OrderController::class, 'bulkDelete'])->name('orders.bulk-delete');
            Route::post('orders/{id}/tags', [OrderController::class, 'updateTags'])->whereNumber('id')->name('orders.tags');
            Route::patch('orders/{id}/note', [OrderController::class, 'updateNote'])->whereNumber('id')->name('orders.note');

            // --- Đơn Hoàn & Hủy (after-sales) — SPEC 0025 ---
            Route::get('returns/stats', [ReturnController::class, 'stats'])->name('returns.stats');
            Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
            Route::get('returns/{id}', [ReturnController::class, 'show'])->whereNumber('id')->name('returns.show');
            Route::post('returns/{id}/approve', [ReturnController::class, 'approve'])->whereNumber('id')->name('returns.approve');
            Route::post('returns/{id}/reject', [ReturnController::class, 'reject'])->whereNumber('id')->name('returns.reject');
            Route::get('orders/unmapped-skus', [SkuMappingController::class, 'unmappedFromOrders'])->name('orders.unmapped-skus');   // SPEC 0004
            Route::post('orders/link-skus', [SkuMappingController::class, 'linkFromOrders'])->name('orders.link-skus');

            // --- Products / SKUs / Inventory / Listings & SKU mapping (Phase 2 / SPEC 0003 + 0004) ---
            // Token cho Chrome Extension "copy sản phẩm" (A5) — chỉ ability `copy-product:push`,
            // không hết hạn (config('sanctum.expiration') = null). Cấp/thu hồi bằng SPA session.
            Route::post('extension-tokens', [ExtensionTokenController::class, 'store'])->name('extension-tokens.store');
            Route::delete('extension-tokens/{id}', [ExtensionTokenController::class, 'destroy'])->whereNumber('id')->name('extension-tokens.destroy');

            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            // Route extension dùng để đẩy sản phẩm — gate `copy-product:push` để extension token
            // (ability hẹp) chỉ gọi được đúng route này; throttle chống spam tạo SP. SPA `*` vẫn pass.
            Route::post('products', [ProductController::class, 'store'])
                ->middleware(['abilities:copy-product:push', 'throttle:60,1'])
                ->name('products.store');
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

            // --- WMS phiếu kho (Phase 5 / SPEC 0010): nhập kho / chuyển kho / kiểm kê ---
            Route::prefix('warehouse-docs/{type}')->whereIn('type', ['goods-receipts', 'stock-transfers', 'stocktakes'])->group(function () {
                Route::get('/', [WarehouseDocumentController::class, 'index'])->name('warehouse-docs.index');
                Route::post('/', [WarehouseDocumentController::class, 'store'])->name('warehouse-docs.store');
                Route::get('{id}', [WarehouseDocumentController::class, 'show'])->whereNumber('id')->name('warehouse-docs.show');
                Route::post('{id}/confirm', [WarehouseDocumentController::class, 'confirm'])->whereNumber('id')->name('warehouse-docs.confirm');
                Route::post('{id}/cancel', [WarehouseDocumentController::class, 'cancel'])->whereNumber('id')->name('warehouse-docs.cancel');
            });

            Route::get('inventory/levels', [InventoryController::class, 'levels'])->name('inventory.levels');
            Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
            Route::post('inventory/bulk-adjust', [InventoryController::class, 'bulkAdjust'])->name('inventory.bulk-adjust');     // SPEC 0004
            Route::post('inventory/push-stock', [InventoryController::class, 'pushStock'])->name('inventory.push-stock');       // SPEC 0004
            Route::get('inventory/stock-push-logs', [InventoryController::class, 'stockPushLogs'])->name('inventory.stock-push-logs');
            Route::post('inventory/stock-push-logs/{id}/retry', [InventoryController::class, 'retryStockPush'])->whereNumber('id')->name('inventory.stock-push-logs.retry');
            Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

            Route::get('channel-listings', [ChannelListingController::class, 'index'])->name('channel-listings.index');
            Route::post('channel-listings/sync', [ChannelListingController::class, 'sync'])->name('channel-listings.sync');
            // Sửa sản phẩm đã có trên sàn (tiêu đề/mô tả/ảnh/giá đẩy lên sàn). Tồn KHÔNG sửa ở đây.
            Route::get('channel-listings/{id}/marketplace-detail', [ChannelListingController::class, 'marketplaceDetail'])->whereNumber('id')->name('channel-listings.marketplace-detail');
            Route::put('channel-listings/{id}/marketplace', [ChannelListingController::class, 'marketplaceUpdate'])->whereNumber('id')->name('channel-listings.marketplace-update');
            Route::post('channel-listings/{id}/clone-to-shops', [ChannelListingController::class, 'cloneToShops'])->whereNumber('id')->name('channel-listings.clone-to-shops');
            Route::post('channel-listings/{id}/ai-description', [ChannelListingController::class, 'aiDescription'])->whereNumber('id')->name('channel-listings.ai-description');

            // Chiến dịch giảm giá nhiều SKU (Shopee/Lazada/TikTok). Route tĩnh trước {id}.
            Route::get('channel-promotions', [PromotionController::class, 'index'])->name('channel-promotions.index');
            Route::get('channel-promotions/busy-skus', [PromotionController::class, 'busySkus'])->name('channel-promotions.busy-skus');
            Route::get('channel-promotions/capabilities', [PromotionController::class, 'capabilities'])->name('channel-promotions.capabilities');
            Route::post('channel-promotions/sync', [PromotionController::class, 'sync'])->name('channel-promotions.sync');
            Route::post('channel-promotions', [PromotionController::class, 'store'])->name('channel-promotions.store');
            Route::get('channel-promotions/{id}', [PromotionController::class, 'show'])->whereNumber('id')->name('channel-promotions.show');
            Route::patch('channel-promotions/{id}', [PromotionController::class, 'update'])->whereNumber('id')->name('channel-promotions.update');
            Route::post('channel-promotions/{id}/skus', [PromotionController::class, 'setSkus'])->whereNumber('id')->name('channel-promotions.skus');
            Route::post('channel-promotions/{id}/push', [PromotionController::class, 'push'])->whereNumber('id')->name('channel-promotions.push');
            Route::post('channel-promotions/{id}/end', [PromotionController::class, 'end'])->whereNumber('id')->name('channel-promotions.end');
            Route::delete('channel-promotions/{id}', [PromotionController::class, 'destroy'])->whereNumber('id')->name('channel-promotions.destroy');
            Route::patch('channel-listings/{id}', [ChannelListingController::class, 'update'])->whereNumber('id')->name('channel-listings.update');
            Route::post('sku-mappings', [SkuMappingController::class, 'store'])->name('sku-mappings.store');
            Route::post('sku-mappings/auto-match', [SkuMappingController::class, 'autoMatch'])->name('sku-mappings.auto-match');
            Route::delete('sku-mappings/{id}', [SkuMappingController::class, 'destroy'])->whereNumber('id')->name('sku-mappings.destroy');

            // --- Listing taxonomy proxy (SPEC marketplace product publishing) — cached read-through
            // over a provider's category tree / attributes / brands for a connected shop.
            Route::get('channels/{provider}/categories', [ListingTaxonomyController::class, 'categories'])->name('channels.categories');
            Route::get('channels/{provider}/categories/search', [ListingTaxonomyController::class, 'searchCategories'])->name('channels.categories.search');
            Route::get('channels/{provider}/category-path', [ListingTaxonomyController::class, 'categoryPath'])->name('channels.category-path');
            Route::get('channels/{provider}/listing-limits', [ListingTaxonomyController::class, 'listingLimits'])->name('channels.listing-limits');
            Route::get('channels/{provider}/attributes', [ListingTaxonomyController::class, 'attributes'])->name('channels.attributes');
            Route::get('channels/{provider}/brands', [ListingTaxonomyController::class, 'brands'])->name('channels.brands');
            Route::get('channels/{provider}/shipping-options', [ListingTaxonomyController::class, 'shippingOptions'])->name('channels.shipping-options');

            // --- Listing drafts (SPEC marketplace product publishing) — seed a publishing
            // draft from a master product, edit per-provider fields, revalidate to READY.
            Route::post('products/{productId}/listings', [ListingDraftController::class, 'store'])->whereNumber('productId')->name('listing-drafts.store');
            Route::get('listings/{id}', [ListingDraftController::class, 'show'])->whereNumber('id')->name('listing-drafts.show');
            Route::put('listings/{id}', [ListingDraftController::class, 'update'])->whereNumber('id')->name('listing-drafts.update');
            Route::delete('listings/{id}', [ListingDraftController::class, 'destroy'])->whereNumber('id')->name('listing-drafts.destroy');
            Route::post('listings/{id}/clone', [ListingDraftController::class, 'cloneTo'])->whereNumber('id')->name('listing-drafts.clone');
            Route::post('listings/{id}/ai-description', [ListingDraftController::class, 'aiDescription'])->whereNumber('id')->name('listing-drafts.ai-description');

            // --- Listing publish (SPEC marketplace product publishing — Task E4) — push a
            // READY draft to its marketplace via a tracked ProductPushBatch on the `listings` queue.
            Route::post('listings/bulk-push', [ListingPushController::class, 'bulkPush'])->name('listings.bulk-push');
            Route::post('listings/{id}/push', [ListingPushController::class, 'push'])->whereNumber('id')->name('listings.push');
            Route::get('push-batches/{id}', [ListingPushController::class, 'batch'])->whereNumber('id')->name('push-batches.show');

            // --- Customers (Phase 2 / SPEC 0002) — internal buyer registry & reputation ---
            Route::post('customers/merge', [CustomerController::class, 'merge'])->name('customers.merge');
            // SPEC 0038 v2 — báo cáo "bom hàng" cho 1 đơn thủ công đã hoàn (idempotent/đơn).
            Route::post('customers/reports', [CustomerController::class, 'storeReport'])->name('customers.reports.store');
            // SPEC 0021 — tra cứu nhanh theo SĐT (UI tạo đơn). Phải đặt TRƯỚC route `{id}` để khớp đường.
            // Không throttle riêng (theo yêu cầu — bỏ chống dò phone); chỉ chịu giới hạn chung của group (120/phút).
            Route::get('customers/lookup', [CustomerController::class, 'lookup'])->name('customers.lookup');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{id}', [CustomerController::class, 'show'])->whereNumber('id')->name('customers.show');
            Route::get('customers/{id}/orders', [CustomerController::class, 'orders'])->whereNumber('id')->name('customers.orders');
            Route::post('customers/{id}/notes', [CustomerController::class, 'storeNote'])->whereNumber('id')->name('customers.notes.store');
            Route::delete('customers/{id}/notes/{noteId}', [CustomerController::class, 'destroyNote'])->whereNumber('id')->whereNumber('noteId')->name('customers.notes.destroy');
            Route::post('customers/{id}/block', [CustomerController::class, 'block'])->whereNumber('id')->name('customers.block');
            Route::post('customers/{id}/unblock', [CustomerController::class, 'unblock'])->whereNumber('id')->name('customers.unblock');
            Route::post('customers/{id}/tags', [CustomerController::class, 'tags'])->whereNumber('id')->name('customers.tags');

            // --- Fulfillment (Phase 3 / SPEC 0006) — vận đơn, ĐVVC, in tem, picking/packing, scan-to-pack ---
            Route::get('carriers', [CarrierAccountController::class, 'carriers'])->name('carriers.index');
            // SPEC 0021 — master-data VN cho AddressPicker khi tạo đơn manual. Cache 24h, không tenant-scoped.
            Route::get('master-data/provinces', [MasterDataController::class, 'provinces'])->name('master-data.provinces');
            Route::get('master-data/districts', [MasterDataController::class, 'districts'])->name('master-data.districts');
            Route::get('master-data/wards', [MasterDataController::class, 'wards'])->name('master-data.wards');
            Route::get('carrier-accounts', [CarrierAccountController::class, 'index'])->name('carrier-accounts.index');
            Route::post('carrier-accounts', [CarrierAccountController::class, 'store'])->name('carrier-accounts.store');
            Route::patch('carrier-accounts/{id}', [CarrierAccountController::class, 'update'])->whereNumber('id')->name('carrier-accounts.update');
            Route::delete('carrier-accounts/{id}', [CarrierAccountController::class, 'destroy'])->whereNumber('id')->name('carrier-accounts.destroy');
            // A2 (SPEC 0021) — kiểm tra credentials còn hợp lệ. Auto-verify lúc store; user retry qua nút "Kiểm tra".
            Route::post('carrier-accounts/{id}/verify', [CarrierAccountController::class, 'verify'])->whereNumber('id')->name('carrier-accounts.verify');
            // Proxy GHN master-data (province/district/ward) bằng token user đang nhập — dùng trong form
            // "Thêm tài khoản GHN" để cascade dropdown thay vì gõ tay mã quận. Cache theo hash token.
            Route::post('carrier-accounts/ghn/master-data', [CarrierAccountController::class, 'ghnMasterData'])->name('carrier-accounts.ghn.master-data');
            // Proxy GHN shop list — 1 token có thể có nhiều shop. Form pick 1 shop thay vì gõ ShopId.
            Route::post('carrier-accounts/ghn/shops', [CarrierAccountController::class, 'ghnShops'])->name('carrier-accounts.ghn.shops');
            // Proxy Viettel Post master-data (Tỉnh/Phường đơn vị HC mới v3) cho form chọn địa chỉ kho. SPEC 0034.
            Route::post('carrier-accounts/viettelpost/master-data', [CarrierAccountController::class, 'viettelpostMasterData'])->name('carrier-accounts.viettelpost.master-data');

            Route::get('fulfillment/ready', [ShipmentController::class, 'ready'])->name('fulfillment.ready');
            Route::get('fulfillment/processing', [ShipmentController::class, 'processing'])->name('fulfillment.processing');           // SPEC 0009 — màn xử lý đơn
            Route::get('fulfillment/processing/counts', [ShipmentController::class, 'processingCounts'])->name('fulfillment.processing.counts');
            Route::post('fulfillment/quote', [ShipmentController::class, 'quote'])->name('fulfillment.quote');                          // gợi ý phí ship (carrier-agnostic)
            Route::post('orders/{id}/ship', [ShipmentController::class, 'createForOrder'])->whereNumber('id')->name('orders.ship');
            Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
            Route::post('shipments/bulk-create', [ShipmentController::class, 'bulkCreate'])->name('shipments.bulk-create');
            Route::post('shipments/pack', [ShipmentController::class, 'pack'])->name('shipments.pack');                                  // bulk đóng gói
            Route::post('shipments/handover', [ShipmentController::class, 'handover'])->name('shipments.handover');                      // bulk bàn giao
            Route::post('shipments/bulk-refetch-slip', [ShipmentController::class, 'bulkRefetchSlip'])->name('shipments.bulk-refetch-slip'); // "Nhận phiếu giao hàng lại" — SPEC 0013
            Route::get('shipments/{id}', [ShipmentController::class, 'show'])->whereNumber('id')->name('shipments.show');
            Route::post('shipments/{id}/track', [ShipmentController::class, 'track'])->whereNumber('id')->name('shipments.track');
            Route::post('shipments/{id}/cancel', [ShipmentController::class, 'cancel'])->whereNumber('id')->name('shipments.cancel');
            Route::get('shipments/{id}/label', [ShipmentController::class, 'label'])->whereNumber('id')->name('shipments.label');
            Route::post('scan-pack', [ShipmentController::class, 'scanPack'])->name('scan-pack');                                        // quét → đóng gói
            Route::post('scan-handover', [ShipmentController::class, 'scanHandover'])->name('scan-handover');                            // (app) quét → bàn giao ĐVVC

            Route::get('print-jobs', [PrintJobController::class, 'index'])->name('print-jobs.index');
            Route::post('print-jobs', [PrintJobController::class, 'store'])->name('print-jobs.store');
            Route::get('print-jobs/{id}', [PrintJobController::class, 'show'])->whereNumber('id')->name('print-jobs.show');
            Route::post('print-jobs/{id}/mark-printed', [PrintJobController::class, 'markPrinted'])->whereNumber('id')->name('print-jobs.mark-printed'); // "Đánh dấu đã in" — SPEC 0013
            Route::get('print-jobs/{id}/download', [PrintJobController::class, 'download'])->whereNumber('id')->name('print-jobs.download');

            // --- Shipping label templates (drag/drop editor cho phiếu giao hàng đơn manual) ---
            Route::get('shipping-label-templates', [ShippingLabelTemplateController::class, 'index'])->name('shipping-label-templates.index');
            Route::post('shipping-label-templates', [ShippingLabelTemplateController::class, 'store'])->name('shipping-label-templates.store');
            Route::post('shipping-label-templates/preview', [ShippingLabelTemplateController::class, 'previewInline'])->name('shipping-label-templates.preview-inline');
            Route::get('shipping-label-templates/{id}', [ShippingLabelTemplateController::class, 'show'])->whereNumber('id')->name('shipping-label-templates.show');
            Route::put('shipping-label-templates/{id}', [ShippingLabelTemplateController::class, 'update'])->whereNumber('id')->name('shipping-label-templates.update');
            Route::delete('shipping-label-templates/{id}', [ShippingLabelTemplateController::class, 'destroy'])->whereNumber('id')->name('shipping-label-templates.destroy');
            Route::post('shipping-label-templates/{id}/set-default', [ShippingLabelTemplateController::class, 'setDefault'])->whereNumber('id')->name('shipping-label-templates.set-default');
            Route::post('shipping-label-templates/{id}/duplicate', [ShippingLabelTemplateController::class, 'duplicate'])->whereNumber('id')->name('shipping-label-templates.duplicate');
            Route::post('shipping-label-templates/{id}/preview', [ShippingLabelTemplateController::class, 'preview'])->whereNumber('id')->name('shipping-label-templates.preview');

            // --- Dashboard ---
            Route::get('dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
        });
    });
});
