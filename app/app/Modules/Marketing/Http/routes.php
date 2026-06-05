<?php

use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdAccountController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdAuthoringController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdDraftController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdEntityController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdForecastController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdInsightController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdminMarketingAiProviderController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdMonitorController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdsOAuthController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AudienceTemplateController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\CampaignAiInsightController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\GeoExclusionTemplateController;
use CMBcoreSeller\Modules\Marketing\Http\Controllers\SavedReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing module REST routes (/api/v1/marketing/*) — SPEC 2026-06-04.
|--------------------------------------------------------------------------
| Facebook Ads near-real-time insights + (later) AI optimization. OAuth
| callback lives in routes/web.php (GET, redirect). Permissions: marketing.*
| (Owner/Admin via wildcard). AdAccountController/AdInsightController added next.
*/

// SPEC 0032 — toàn bộ module Quảng cáo Facebook chỉ mở ở gói Pro (`marketing_facebook`).
Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant', 'plan.feature:marketing_facebook'])
    ->prefix('api/v1/marketing')->group(function () {
        // Facebook Ads OAuth connect — returns authorize URL for FE redirect.
        Route::post('ads/connect', [AdsOAuthController::class, 'start'])
            ->name('marketing.ads.connect');

        // Ad accounts management.
        Route::get('ad-accounts', [AdAccountController::class, 'index'])
            ->name('marketing.ad-accounts.index');
        Route::delete('ad-accounts/{id}', [AdAccountController::class, 'destroy'])
            ->whereNumber('id')->name('marketing.ad-accounts.destroy');
        Route::post('ad-accounts/disconnect-bulk', [AdAccountController::class, 'disconnectBulk'])
            ->name('marketing.ad-accounts.disconnect-bulk');
        Route::post('ad-accounts/refresh-accounts', [AdAccountController::class, 'refreshAccounts'])
            ->name('marketing.ad-accounts.refresh-accounts');
        Route::post('ad-accounts/{id}/refresh', [AdAccountController::class, 'refresh'])
            ->whereNumber('id')->name('marketing.ad-accounts.refresh');
        Route::post('ad-accounts/{id}/claim-automation', [AdAccountController::class, 'claimAutomation'])
            ->whereNumber('id')->name('marketing.ad-accounts.claim-automation');

        // Dashboard insights (account summary + entity tree with latest metrics).
        Route::get('ad-accounts/{id}/insights', [AdInsightController::class, 'index'])
            ->whereNumber('id')->name('marketing.ad-accounts.insights');
        // Ads-Manager-style report (campaign/adset/ad × date range, drill-down, filters).
        Route::get('ad-accounts/{id}/report', [AdInsightController::class, 'report'])
            ->whereNumber('id')->name('marketing.ad-accounts.report');
        // Daily reconciliation: ad metrics vs manual orders.
        Route::get('ad-accounts/{id}/reconciliation', [AdInsightController::class, 'reconciliation'])
            ->whereNumber('id')->name('marketing.ad-accounts.reconciliation');
        // Live edit one entity (rename / daily budget / pause-resume).
        Route::patch('ad-accounts/{id}/entities/{externalId}', [AdEntityController::class, 'update'])
            ->whereNumber('id')->name('marketing.ad-accounts.entities.update');
        // Auto-monitor rules (raise budget / pause by cost-per-result).
        Route::get('ad-accounts/{id}/monitors', [AdMonitorController::class, 'index'])
            ->whereNumber('id')->name('marketing.monitors.index');
        Route::put('ad-accounts/{id}/monitors', [AdMonitorController::class, 'upsert'])
            ->whereNumber('id')->name('marketing.monitors.upsert');
        Route::delete('monitors/{monitor}', [AdMonitorController::class, 'destroy'])
            ->whereNumber('monitor')->name('marketing.monitors.destroy');
        Route::get('ad-accounts/{id}/monitor-actions', [AdMonitorController::class, 'actions'])
            ->whereNumber('id')->name('marketing.monitor-actions.index');
        Route::delete('monitor-actions/{action}', [AdMonitorController::class, 'destroyAction'])
            ->whereNumber('action')->name('marketing.monitor-actions.destroy');
        // Saved report snapshots (per filter run, reviewable over time).
        Route::get('ad-accounts/{id}/saved-reports', [SavedReportController::class, 'index'])
            ->whereNumber('id')->name('marketing.saved-reports.index');
        Route::post('ad-accounts/{id}/saved-reports', [SavedReportController::class, 'store'])
            ->whereNumber('id')->name('marketing.saved-reports.store');
        Route::get('saved-reports/{report}', [SavedReportController::class, 'show'])
            ->whereNumber('report')->name('marketing.saved-reports.show');
        Route::delete('saved-reports/{report}', [SavedReportController::class, 'destroy'])
            ->whereNumber('report')->name('marketing.saved-reports.destroy');
        // AI strategic forecast (on-demand, cooldown-cached).
        Route::get('ad-accounts/{id}/forecast', [AdForecastController::class, 'show'])
            ->whereNumber('id')->name('marketing.ad-accounts.forecast.show');
        Route::post('ad-accounts/{id}/forecast', [AdForecastController::class, 'generate'])
            ->whereNumber('id')->name('marketing.ad-accounts.forecast.generate');
        // Per-campaign AI analysis (on-demand, cooldown-cached, params-aware).
        Route::get('ad-accounts/{id}/campaigns/{campaignId}/ai-insight', [CampaignAiInsightController::class, 'show'])
            ->whereNumber('id')->name('marketing.ad-accounts.campaign-insight.show');
        Route::post('ad-accounts/{id}/campaigns/{campaignId}/ai-insight', [CampaignAiInsightController::class, 'generate'])
            ->whereNumber('id')->name('marketing.ad-accounts.campaign-insight.generate');
        Route::get('ad-accounts/{id}/campaigns/{campaignId}/ai-insight/history', [CampaignAiInsightController::class, 'history'])
            ->whereNumber('id')->name('marketing.ad-accounts.campaign-insight.history');
        Route::delete('campaign-insights/{insight}', [CampaignAiInsightController::class, 'destroy'])
            ->whereNumber('insight')->name('marketing.campaign-insights.destroy');

        // Wizard authoring reads (pages/posts/targeting/preview).
        Route::get('ad-accounts/{id}/pages', [AdAuthoringController::class, 'pages'])->whereNumber('id')->name('marketing.authoring.pages');
        Route::get('ad-accounts/{id}/pixels', [AdAuthoringController::class, 'pixels'])->whereNumber('id')->name('marketing.authoring.pixels');
        Route::post('ad-accounts/{id}/pixels/{pixelId}/share', [AdAuthoringController::class, 'sharePixel'])->whereNumber('id')->name('marketing.authoring.pixels.share');
        Route::get('ad-accounts/{id}/pages/{pageId}/posts', [AdAuthoringController::class, 'pagePosts'])->whereNumber('id')->name('marketing.authoring.page-posts');
        Route::get('ad-accounts/{id}/targeting-search', [AdAuthoringController::class, 'targetingSearch'])->whereNumber('id')->name('marketing.authoring.targeting-search');
        Route::post('ad-accounts/{id}/audience-estimate', [AdAuthoringController::class, 'audienceEstimate'])->whereNumber('id')->name('marketing.authoring.audience-estimate');
        Route::post('ad-accounts/{id}/ad-previews', [AdAuthoringController::class, 'previews'])->whereNumber('id')->name('marketing.authoring.previews');

        // Wizard drafts (CRUD + autosave). Write gated by marketing.ads.create.
        Route::get('ad-drafts', [AdDraftController::class, 'index'])->name('marketing.ad-drafts.index');
        Route::post('ad-drafts', [AdDraftController::class, 'store'])->name('marketing.ad-drafts.store');
        Route::get('ad-drafts/{id}', [AdDraftController::class, 'show'])->whereNumber('id')->name('marketing.ad-drafts.show');
        Route::patch('ad-drafts/{id}', [AdDraftController::class, 'update'])->whereNumber('id')->name('marketing.ad-drafts.update');
        Route::delete('ad-drafts/{id}', [AdDraftController::class, 'destroy'])->whereNumber('id')->name('marketing.ad-drafts.destroy');
        Route::post('ad-drafts/{id}/publish', [AdDraftController::class, 'publish'])->whereNumber('id')->name('marketing.ad-drafts.publish');
        Route::post('ad-drafts/{id}/duplicate', [AdDraftController::class, 'duplicate'])->whereNumber('id')->name('marketing.ad-drafts.duplicate');

        // Geo exclusion templates (tenant-scoped saved sets of excluded locations).
        Route::get('exclusion-templates', [GeoExclusionTemplateController::class, 'index'])->name('marketing.exclusion-templates.index');
        Route::post('exclusion-templates', [GeoExclusionTemplateController::class, 'store'])->name('marketing.exclusion-templates.store');
        Route::delete('exclusion-templates/{template}', [GeoExclusionTemplateController::class, 'destroy'])->name('marketing.exclusion-templates.destroy');

        // Detailed-targeting (audience) templates: saved interests/behaviours/demographics sets.
        Route::get('audience-templates', [AudienceTemplateController::class, 'index'])->name('marketing.audience-templates.index');
        Route::post('audience-templates', [AudienceTemplateController::class, 'store'])->name('marketing.audience-templates.store');
        Route::delete('audience-templates/{template}', [AudienceTemplateController::class, 'destroy'])->name('marketing.audience-templates.destroy');
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
