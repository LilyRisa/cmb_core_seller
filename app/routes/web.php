<?php

use CMBcoreSeller\Modules\Channels\Http\Controllers\OAuthCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Two things live here: OAuth callbacks, and the SPA catch-all that serves
| the React app for every other path. /api/* and /webhook/* are handled by
| their own route groups (see bootstrap/app.php). See docs/06-frontend/overview.md.
*/

// --- OAuth callback for marketplace connections (Channels module) ---
// Verify state -> exchange code -> create/update channel_account -> register
// webhooks -> kick off a 90-day backfill -> redirect into the SPA.
Route::get('oauth/{provider}/callback', OAuthCallbackController::class)
    ->whereIn('provider', ['tiktok', 'shopee', 'lazada'])->name('oauth.callback');

// --- SPA catch-all: serve the React app for everything that isn't an API,
//     webhook, OAuth, health, or build/storage asset. React Router handles
//     client-side routing from there.
Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api(/|$)|webhook(/|$)|oauth(/|$)|build(/|$)|storage(/|$)|sanctum(/|$)|up$).*$')
    ->name('spa');
