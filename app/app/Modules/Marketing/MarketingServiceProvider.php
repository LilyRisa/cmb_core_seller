<?php

namespace CMBcoreSeller\Modules\Marketing;

use Illuminate\Support\ServiceProvider;

/**
 * Marketing module (SPEC 2026-06-04 — Facebook Ads near-real-time + AI). Owns
 * ad_accounts / ad_entities / ad_insight_snapshots, sync jobs, and HTTP. Consumes
 * the `Ads` integration axis (AdsRegistry) + the `Ai` axis (later phases).
 */
class MarketingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Marketing-owned AI client (isolated from Integrations/Ai messaging flow).
        $this->app->bind(
            \CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient::class,
            \CMBcoreSeller\Modules\Marketing\Services\LlmMarketingAnalysisClient::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
