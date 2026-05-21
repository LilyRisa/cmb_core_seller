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
        // Key theo X-Tenant-Id — chính nguồn EnsureTenant dùng (header/query) — vì middleware
        // throttle chạy TRƯỚC khi tenant được resolve (CurrentTenant chưa set lúc này).
        // Fallback: user id (đã auth) rồi IP. User là belongsToMany(Tenant) nên không có cột tenant_id.
        RateLimiter::for('ai-suggestion', function (Request $request) {
            $tenantId = $request->header('X-Tenant-Id')
                ?: $request->query('X-Tenant-Id')
                ?: ($request->user()?->getAuthIdentifier() ?? $request->ip());

            return Limit::perMinute(20)->by('ai-suggestion:'.$tenantId);
        });
    }
}
