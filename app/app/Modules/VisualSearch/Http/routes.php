<?php

use CMBcoreSeller\Modules\VisualSearch\Http\Controllers\TrainingImageController;
use CMBcoreSeller\Modules\VisualSearch\Http\Controllers\TrainingItemController;
use CMBcoreSeller\Modules\VisualSearch\Http\Controllers\VisualLookupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| VisualSearch REST API (/api/v1/visual-search/*) — SPEC 2026-06-16
|--------------------------------------------------------------------------
|
| Middleware: Sanctum + verified + tenant + plan.over_quota_lock +
| plan.feature:messaging_visual_search. Quyền: đọc `messaging.view`,
| mutate `messaging.ai.train` (đây là AI training).
|
*/

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.over_quota_lock', 'plan.feature:messaging_visual_search'])
    ->prefix('api/v1/visual-search')->group(function () {
        // CRUD item AI training
        Route::get('items', [TrainingItemController::class, 'index'])->name('visual-search.items.index');
        Route::post('items', [TrainingItemController::class, 'store'])->name('visual-search.items.store');
        Route::get('items/{id}', [TrainingItemController::class, 'show'])->whereNumber('id')->name('visual-search.items.show');
        Route::patch('items/{id}', [TrainingItemController::class, 'update'])->whereNumber('id')->name('visual-search.items.update');
        Route::delete('items/{id}', [TrainingItemController::class, 'destroy'])->whereNumber('id')->name('visual-search.items.destroy');

        // Ảnh của item
        Route::post('items/{itemId}/images', [TrainingImageController::class, 'store'])
            ->whereNumber('itemId')->name('visual-search.images.store');
        Route::delete('items/{itemId}/images/{imageId}', [TrainingImageController::class, 'destroy'])
            ->whereNumber('itemId')->whereNumber('imageId')->name('visual-search.images.destroy');
        Route::post('items/{itemId}/images/{imageId}/primary', [TrainingImageController::class, 'setPrimary'])
            ->whereNumber('itemId')->whereNumber('imageId')->name('visual-search.images.primary');

        // Tìm bằng ảnh (seller) — rate-limit chống lạm dụng.
        Route::post('lookup', [VisualLookupController::class, 'lookup'])
            ->middleware('throttle:30,1')->name('visual-search.lookup');
    });
