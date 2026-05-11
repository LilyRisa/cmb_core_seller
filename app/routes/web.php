<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Only OAuth callbacks live here (plus, later, the SPA catch-all that serves
| the React app for every non-/api, non-/webhook, non-/oauth path).
| See docs/05-api/webhooks-and-oauth.md and docs/06-frontend/overview.md.
*/

// --- OAuth callback for marketplace connections ---
// Real exchange (verify state -> exchange code -> create channel_account ->
// register webhooks -> kick off backfill -> redirect to SPA) lands with the
// Channels module / TikTok connector (Phase 1).
Route::get('oauth/{provider}/callback', function (Request $request, string $provider) {
    logger()->info('oauth.callback.unimplemented', ['provider' => $provider, 'has_code' => $request->filled('code')]);

    return response('OAuth callback for [' . e($provider) . '] is not implemented yet (Phase 1).', 501);
})->whereIn('provider', ['tiktok', 'shopee', 'lazada'])->name('oauth.callback');

// --- Placeholder home (replaced by the React SPA catch-all in the frontend slice) ---
Route::get('/', fn () => view('welcome'));
