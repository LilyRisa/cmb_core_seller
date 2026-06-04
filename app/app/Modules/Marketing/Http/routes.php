<?php

use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdAccountController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdAuthoringController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdDraftController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdForecastController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdInsightController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdminMarketingAiProviderController;
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
        // Ads-Manager-style report (campaign/adset/ad × date range, drill-down, filters).
        Route::get('ad-accounts/{id}/report', [AdInsightController::class, 'report'])
            ->whereNumber('id')->name('marketing.ad-accounts.report');
        // Daily reconciliation: ad metrics vs manual orders.
        Route::get('ad-accounts/{id}/reconciliation', [AdInsightController::class, 'reconciliation'])
            ->whereNumber('id')->name('marketing.ad-accounts.reconciliation');
        // AI strategic forecast (on-demand, cooldown-cached).
        Route::get('ad-accounts/{id}/forecast', [AdForecastController::class, 'show'])
            ->whereNumber('id')->name('marketing.ad-accounts.forecast.show');
        Route::post('ad-accounts/{id}/forecast', [AdForecastController::class, 'generate'])
            ->whereNumber('id')->name('marketing.ad-accounts.forecast.generate');

        // Wizard authoring reads (pages/posts/targeting/preview).
        Route::get('ad-accounts/{id}/pages', [AdAuthoringController::class, 'pages'])->whereNumber('id')->name('marketing.authoring.pages');
        Route::get('ad-accounts/{id}/pages/{pageId}/posts', [AdAuthoringController::class, 'pagePosts'])->whereNumber('id')->name('marketing.authoring.page-posts');

        // Wizard drafts (CRUD + autosave). Write gated by marketing.ads.create.
        Route::get('ad-drafts', [AdDraftController::class, 'index'])->name('marketing.ad-drafts.index');
        Route::post('ad-drafts', [AdDraftController::class, 'store'])->name('marketing.ad-drafts.store');
        Route::get('ad-drafts/{id}', [AdDraftController::class, 'show'])->whereNumber('id')->name('marketing.ad-drafts.show');
        Route::patch('ad-drafts/{id}', [AdDraftController::class, 'update'])->whereNumber('id')->name('marketing.ad-drafts.update');
        Route::delete('ad-drafts/{id}', [AdDraftController::class, 'destroy'])->whereNumber('id')->name('marketing.ad-drafts.destroy');
        Route::post('ad-drafts/{id}/publish', [AdDraftController::class, 'publish'])->whereNumber('id')->name('marketing.ad-drafts.publish');
    });

// Super-admin: dedicated marketing AI provider (separate from messaging ai-providers).
// Guard admin_web, no tenant — same stack as /admin/ai-providers.
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/marketing-ai-providers')->group(function () {
        Route::get('/', [AdminMarketingAiProviderController::class, 'index'])->name('admin.marketing-ai-providers.index');
        Route::post('/', [AdminMarketingAiProviderController::class, 'store'])->name('admin.marketing-ai-providers.store');
        Route::patch('{code}', [AdminMarketingAiProviderController::class, 'update'])
            ->where('code', '[a-z0-9][a-z0-9_-]*')->name('admin.marketing-ai-providers.update');
        Route::delete('{code}', [AdminMarketingAiProviderController::class, 'destroy'])
            ->where('code', '[a-z0-9][a-z0-9_-]*')->name('admin.marketing-ai-providers.destroy');
    });
