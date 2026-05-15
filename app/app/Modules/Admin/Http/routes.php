<?php

use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminTenantController;
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes — /api/v1/admin/* (SPEC 0020). Super-admin only.
|--------------------------------------------------------------------------
| KHÔNG có middleware `tenant` — admin global xuyên mọi tenant. Mọi
| controller bỏ TenantScope tường minh.
|
| Rate limit 60/phút/user (chống misuse). Audit log mỗi action ghi.
*/

Route::middleware(['api', 'auth:sanctum', 'super_admin', 'throttle:60,1'])
    ->prefix('api/v1/admin')->group(function () {

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

        Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');
    });
