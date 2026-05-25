<?php

use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminAdminUserController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminAuditLogController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminAuthController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminBroadcastController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminPlanController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminTenantController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminUserController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminVoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes — /api/v1/admin/* (Spec 2026-05-17).
|--------------------------------------------------------------------------
| Auth tách lập:
|   - /api/v1/admin/auth/login           (web + throttle, NO auth) — set session admin
|   - /api/v1/admin/auth/{logout,me,...} (web + auth:admin)
|   - /api/v1/admin/*                    (web + auth:admin, throttle 60/min)
|
| Drop super_admin middleware cũ + Gate::before super-admin. Admin không truy
| cập route /api/v1/* của user (guard khác).
*/

// --- Public auth (login) ----------------------------------------------------
Route::middleware(['web', 'throttle:10,1'])
    ->prefix('api/v1/admin/auth')->group(function () {
        Route::post('login', [AdminAuthController::class, 'login'])->name('admin.auth.login');
    });

// --- Authenticated auth (logout/me/change-password) -------------------------
Route::middleware(['web', 'auth:admin_web'])
    ->prefix('api/v1/admin/auth')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.auth.logout');
        Route::get('me', [AdminAuthController::class, 'me'])->name('admin.auth.me');
        Route::post('change-password', [AdminAuthController::class, 'changePassword'])->name('admin.auth.change-password');
    });

// --- Admin business routes --------------------------------------------------
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin')->group(function () {

        // --- Tenants (SPEC 0020) ---
        Route::get('tenants', [AdminTenantController::class, 'index'])->name('admin.tenants.index');
        Route::get('tenants/{id}', [AdminTenantController::class, 'show'])->whereNumber('id')->name('admin.tenants.show');

        Route::delete('tenants/{tid}/channel-accounts/{caid}', [AdminTenantController::class, 'deleteChannelAccount'])
            ->whereNumber('tid')->whereNumber('caid')->name('admin.tenants.channel-accounts.destroy');

        Route::post('tenants/{tid}/subscription', [AdminTenantController::class, 'changePlan'])
            ->whereNumber('tid')->name('admin.tenants.subscription.change');

        Route::post('tenants/{tid}/suspend', [AdminTenantController::class, 'suspend'])
            ->whereNumber('tid')->name('admin.tenants.suspend');
        Route::post('tenants/{tid}/reactivate', [AdminTenantController::class, 'reactivate'])
            ->whereNumber('tid')->name('admin.tenants.reactivate');

        // --- Tenant operations (SPEC 0023) ---
        Route::post('tenants/{tid}/extend-trial', [AdminTenantController::class, 'extendTrial'])
            ->whereNumber('tid')->name('admin.tenants.extend-trial');
        Route::post('tenants/{tid}/feature-overrides', [AdminTenantController::class, 'featureOverrides'])
            ->whereNumber('tid')->name('admin.tenants.feature-overrides');
        Route::post('tenants/{tid}/invoices', [AdminTenantController::class, 'createInvoice'])
            ->whereNumber('tid')->name('admin.tenants.invoices.create');

        // --- Invoice & payment global (SPEC 0023) ---
        Route::post('invoices/{id}/mark-paid', [AdminTenantController::class, 'markInvoicePaid'])
            ->whereNumber('id')->name('admin.invoices.mark-paid');
        Route::post('payments/{id}/refund', [AdminTenantController::class, 'refundPayment'])
            ->whereNumber('id')->name('admin.payments.refund');

        // --- Vouchers (SPEC 0023) ---
        Route::get('vouchers', [AdminVoucherController::class, 'index'])->name('admin.vouchers.index');
        Route::post('vouchers', [AdminVoucherController::class, 'store'])->name('admin.vouchers.store');
        Route::get('vouchers/{id}', [AdminVoucherController::class, 'show'])->whereNumber('id')->name('admin.vouchers.show');
        Route::patch('vouchers/{id}', [AdminVoucherController::class, 'update'])->whereNumber('id')->name('admin.vouchers.update');
        Route::delete('vouchers/{id}', [AdminVoucherController::class, 'destroy'])->whereNumber('id')->name('admin.vouchers.destroy');
        Route::post('vouchers/{id}/grant', [AdminVoucherController::class, 'grant'])->whereNumber('id')->name('admin.vouchers.grant');

        // --- Plan editor (SPEC 0023) ---
        Route::get('plans', [AdminPlanController::class, 'index'])->name('admin.plans.index');
        Route::post('plans', [AdminPlanController::class, 'store'])->name('admin.plans.store');
        Route::get('plans/{id}', [AdminPlanController::class, 'show'])->whereNumber('id')->name('admin.plans.show');
        Route::patch('plans/{id}', [AdminPlanController::class, 'update'])->whereNumber('id')->name('admin.plans.update');

        // --- Audit log search (SPEC 0023) ---
        Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index');

        // --- Broadcasts (SPEC 0023) ---
        Route::get('broadcasts', [AdminBroadcastController::class, 'index'])->name('admin.broadcasts.index');
        Route::post('broadcasts', [AdminBroadcastController::class, 'store'])->name('admin.broadcasts.store');
        Route::get('broadcasts/{id}', [AdminBroadcastController::class, 'show'])->whereNumber('id')->name('admin.broadcasts.show');

        // --- Tenant Users (SPEC 0020 + Spec 2026-05-17 mở rộng) ---
        Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('users/{id}', [AdminUserController::class, 'show'])
            ->whereNumber('id')->name('admin.users.show');
        Route::patch('users/{id}', [AdminUserController::class, 'update'])
            ->whereNumber('id')->name('admin.users.update');
        Route::post('users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])
            ->whereNumber('id')->name('admin.users.reset-password');
        Route::post('users/{id}/suspend', [AdminUserController::class, 'suspend'])
            ->whereNumber('id')->name('admin.users.suspend');
        Route::post('users/{id}/reactivate', [AdminUserController::class, 'reactivate'])
            ->whereNumber('id')->name('admin.users.reactivate');

        // --- Admin Users — quản lý chính các super-admin (Spec 2026-05-17) ---
        Route::get('admin-users', [AdminAdminUserController::class, 'index'])->name('admin.admin-users.index');
        Route::post('admin-users', [AdminAdminUserController::class, 'store'])->name('admin.admin-users.store');
        Route::get('admin-users/{id}', [AdminAdminUserController::class, 'show'])
            ->whereNumber('id')->name('admin.admin-users.show');
        Route::patch('admin-users/{id}', [AdminAdminUserController::class, 'update'])
            ->whereNumber('id')->name('admin.admin-users.update');
        Route::post('admin-users/{id}/reset-password', [AdminAdminUserController::class, 'resetPassword'])
            ->whereNumber('id')->name('admin.admin-users.reset-password');
        Route::post('admin-users/{id}/suspend', [AdminAdminUserController::class, 'suspend'])
            ->whereNumber('id')->name('admin.admin-users.suspend');
        Route::post('admin-users/{id}/reactivate', [AdminAdminUserController::class, 'reactivate'])
            ->whereNumber('id')->name('admin.admin-users.reactivate');
    });
