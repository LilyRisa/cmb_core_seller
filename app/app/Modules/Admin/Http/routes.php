<?php

use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminAuditLogController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminBroadcastController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminPlanController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminTenantController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminUserController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminVoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes — /api/v1/admin/* (SPEC 0020 + 0023). Super-admin only.
|--------------------------------------------------------------------------
| KHÔNG có middleware `tenant` — admin global xuyên mọi tenant. Mọi
| controller bỏ TenantScope tường minh.
|
| Rate limit 60/phút/user (chống misuse). Audit log mỗi action ghi.
*/

Route::middleware(['api', 'auth:sanctum', 'super_admin', 'throttle:60,1'])
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
        Route::get('plans/{id}', [AdminPlanController::class, 'show'])->whereNumber('id')->name('admin.plans.show');
        Route::patch('plans/{id}', [AdminPlanController::class, 'update'])->whereNumber('id')->name('admin.plans.update');

        // --- Audit log search (SPEC 0023) ---
        Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs.index');

        // --- Broadcasts (SPEC 0023) ---
        Route::get('broadcasts', [AdminBroadcastController::class, 'index'])->name('admin.broadcasts.index');
        Route::post('broadcasts', [AdminBroadcastController::class, 'store'])->name('admin.broadcasts.store');
        Route::get('broadcasts/{id}', [AdminBroadcastController::class, 'show'])->whereNumber('id')->name('admin.broadcasts.show');

        // --- Users (SPEC 0020) ---
        Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');
    });
