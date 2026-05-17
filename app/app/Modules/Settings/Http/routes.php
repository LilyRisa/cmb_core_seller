<?php

use CMBcoreSeller\Modules\Settings\Http\Controllers\AdminSystemSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings routes — `/api/v1/admin/system-settings/*` (Spec 2026-05-17).
|--------------------------------------------------------------------------
| Admin-only; cùng middleware stack với module Admin (web + auth:admin_web).
| Wildcard `{key}` cho phép key dạng `marketplace.tiktok.app_key`.
*/

Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/system-settings')->group(function () {

        Route::get('/', [AdminSystemSettingController::class, 'index'])
            ->name('admin.system-settings.index');

        Route::post('sync-from-env', [AdminSystemSettingController::class, 'syncFromEnv'])
            ->name('admin.system-settings.sync-from-env');

        Route::get('{key}/reveal', [AdminSystemSettingController::class, 'reveal'])
            ->where('key', '[a-zA-Z0-9._\-]+')
            ->name('admin.system-settings.reveal');

        Route::patch('{key}', [AdminSystemSettingController::class, 'update'])
            ->where('key', '[a-zA-Z0-9._\-]+')
            ->name('admin.system-settings.update');

        Route::delete('{key}', [AdminSystemSettingController::class, 'destroy'])
            ->where('key', '[a-zA-Z0-9._\-]+')
            ->name('admin.system-settings.destroy');
    });
