<?php

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
    });
