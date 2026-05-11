<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Two things live here: OAuth callbacks, and the SPA catch-all that serves
| the React app for every other path. /api/* and /webhook/* are handled by
| their own route groups (see bootstrap/app.php). See docs/06-frontend/overview.md.
*/

// --- OAuth callback for marketplace connections ---
// Real exchange (verify state -> exchange code -> create channel_account ->
// register webhooks -> kick off backfill -> redirect to SPA) lands with the
// Channels module / TikTok connector (Phase 1).
Route::get('oauth/{provider}/callback', function (Request $request, string $provider) {
    logger()->info('oauth.callback.unimplemented', ['provider' => $provider, 'has_code' => $request->filled('code')]);

    return response('OAuth callback for [' . e($provider) . '] is not implemented yet (Phase 1).', 501);
})->whereIn('provider', ['tiktok', 'shopee', 'lazada'])->name('oauth.callback');

// --- SPA catch-all: serve the React app for everything that isn't an API,
//     webhook, OAuth, health, or build/storage asset. React Router handles
//     client-side routing from there.
Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api(/|$)|webhook(/|$)|oauth(/|$)|build(/|$)|storage(/|$)|sanctum(/|$)|up$).*$')
    ->name('spa');
