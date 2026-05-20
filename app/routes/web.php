<?php

use CMBcoreSeller\Http\Controllers\AdminSpaController;
use CMBcoreSeller\Http\Controllers\SpaController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\OAuthCallbackController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\FacebookOAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Three SPA / web endpoints live here:
|   - OAuth callbacks for marketplace connections.
|   - Admin SPA at `/admin/{any?}` (Spec 2026-05-17 — separate Vite bundle).
|   - User SPA catch-all for everything else.
|
| /api/* and /webhook/* are handled by their own route groups
| (see bootstrap/app.php). See docs/06-frontend/overview.md. No Closure
| routes here — keeps `php artisan route:cache` working in prod.
*/

// --- OAuth callback for marketplace connections (Channels module) ---
// The marketplace redirects here after the seller authorizes: verify state ->
// exchange code -> create/update channel_account -> register webhooks -> kick off
// a 90-day backfill -> redirect into the SPA. The callback URL registered in the
// marketplace partner console MUST be exactly  https://<APP_URL host>/oauth/<provider>/callback
// (e.g. https://app.cmbcore.com/oauth/tiktok/callback). GET only — auth callbacks are redirects.
Route::get('oauth/{provider}/callback', OAuthCallbackController::class)
    ->whereIn('provider', ['tiktok', 'shopee', 'lazada'])
    ->name('oauth.callback');

// Facebook Page messaging OAuth (SPEC-0024) — MessagingConnector, flow riêng.
Route::get('oauth/facebook_page/callback', [FacebookOAuthController::class, 'callback'])
    ->name('messaging.facebook.callback');

// --- Admin SPA shell (Spec 2026-05-17) ---
// `/admin` và mọi sub-path serve Blade `admin.blade.php` nạp bundle `admin.tsx`.
// React Router xử lý routing client-side trong admin app.
Route::get('admin/{any?}', AdminSpaController::class)
    ->where('any', '.*')
    ->name('admin.spa');

// --- SPA catch-all: serve the React user app for everything that isn't an API,
//     webhook, OAuth callback, admin SPA, health, or build/storage asset. React
//     Router handles client-side routing from there.
Route::get('/{any?}', SpaController::class)
    ->where('any', '^(?!api(/|$)|webhook(/|$)|oauth(/|$)|admin(/|$)|build(/|$)|storage(/|$)|sanctum(/|$)|up$).*$')
    ->name('spa');
