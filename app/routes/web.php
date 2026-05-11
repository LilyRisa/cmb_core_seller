<?php

use CMBcoreSeller\Http\Controllers\SpaController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\OAuthCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Two things live here: OAuth callbacks, and the SPA catch-all that serves
| the React app for every other path. /api/* and /webhook/* are handled by
| their own route groups (see bootstrap/app.php). See docs/06-frontend/overview.md.
| (No Closure routes here — keep `php artisan route:cache` working in prod.)
*/

// --- OAuth callback for marketplace connections (Channels module) ---
// The marketplace redirects here after the seller authorizes: verify state ->
// exchange code -> create/update channel_account -> register webhooks -> kick off
// a 90-day backfill -> redirect into the SPA. The callback URL registered in the
// marketplace partner console MUST be exactly  https://<APP_URL host>/oauth/<provider>/callback
// (e.g. https://app.cmbcore.com/oauth/tiktok/callback). GET only — auth callbacks are redirects.
Route::get('oauth/{provider}/callback', OAuthCallbackController::class)
    ->whereIn('provider', ['tiktok', 'shopee', 'lazada'])->name('oauth.callback');

// --- SPA catch-all: serve the React app for everything that isn't an API,
//     webhook, OAuth, health, or build/storage asset. React Router handles
//     client-side routing from there.
Route::get('/{any?}', SpaController::class)
    ->where('any', '^(?!api(/|$)|webhook(/|$)|oauth(/|$)|build(/|$)|storage(/|$)|sanctum(/|$)|up$).*$')
    ->name('spa');
