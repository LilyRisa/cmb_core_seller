<?php

use CMBcoreSeller\Modules\Notifications\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | In-app Notifications — /api/v1/notifications/* (SPEC 0036)
 |--------------------------------------------------------------------------
 |
 | Chuông thông báo của user trong tenant hiện tại. KHÔNG gate plan.feature (UX lõi).
 | Cần đăng nhập + email verified + tenant. Throttle nhẹ (chuông poll khi Reverb tắt).
 */

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant'])
    ->prefix('api/v1/notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])
            ->middleware('throttle:120,1')->name('notifications.index');
        Route::post('read-all', [NotificationController::class, 'readAll'])
            ->middleware('throttle:60,1')->name('notifications.read-all');
        Route::post('{id}/read', [NotificationController::class, 'read'])
            ->whereNumber('id')->middleware('throttle:120,1')->name('notifications.read');
    });
