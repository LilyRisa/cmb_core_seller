<?php

use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdAccountController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdInsightController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdsOAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing module REST routes (/api/v1/marketing/*) — SPEC 2026-06-04.
|--------------------------------------------------------------------------
| Facebook Ads near-real-time insights + (later) AI optimization. OAuth
| callback lives in routes/web.php (GET, redirect). Permissions: marketing.*
| (Owner/Admin via wildcard). AdAccountController/AdInsightController added next.
*/

Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant'])
    ->prefix('api/v1/marketing')->group(function () {
        // Facebook Ads OAuth connect — returns authorize URL for FE redirect.
        Route::post('ads/connect', [AdsOAuthController::class, 'start'])
            ->name('marketing.ads.connect');

        // Ad accounts management.
        Route::get('ad-accounts', [AdAccountController::class, 'index'])
            ->name('marketing.ad-accounts.index');
        Route::delete('ad-accounts/{id}', [AdAccountController::class, 'destroy'])
            ->whereNumber('id')->name('marketing.ad-accounts.destroy');
        Route::post('ad-accounts/{id}/refresh', [AdAccountController::class, 'refresh'])
            ->whereNumber('id')->name('marketing.ad-accounts.refresh');

        // Dashboard insights (account summary + entity tree with latest metrics).
        Route::get('ad-accounts/{id}/insights', [AdInsightController::class, 'index'])
            ->whereNumber('id')->name('marketing.ad-accounts.insights');
        // Daily reconciliation: ad metrics vs manual orders.
        Route::get('ad-accounts/{id}/reconciliation', [AdInsightController::class, 'reconciliation'])
            ->whereNumber('id')->name('marketing.ad-accounts.reconciliation');
    });
