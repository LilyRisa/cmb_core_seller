<?php

namespace CMBcoreSeller\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate-limit AI-suggestion theo tenant (chống abuse / hammer LLM provider).
        RateLimiter::for('ai-suggestion', function (Request $request) {
            $tenantId = $request->user()?->tenant_id ?? $request->ip();

            return Limit::perMinute(20)->by('ai-suggestion:'.$tenantId);
        });
    }
}
