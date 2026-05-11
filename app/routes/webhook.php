<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook routes — prefix /webhook, mounted from bootstrap/app.php.
|--------------------------------------------------------------------------
| No CSRF, no auth. Each marketplace/carrier endpoint must verify its
| signature first (XWebhookVerifier in the connector), record the event in
| `webhook_events`, return 200 fast, and process asynchronously.
| See docs/05-api/webhooks-and-oauth.md.
|
| Real handlers land with the Channels module / TikTok connector (Phase 1).
| For now these stubs acknowledge receipt so endpoints exist for app review.
*/

foreach (['tiktok', 'shopee', 'lazada'] as $provider) {
    Route::post($provider, function (Request $request) use ($provider) {
        // TODO(Phase 1): verify signature -> store webhook_events -> dispatch ProcessWebhookEvent.
        logger()->info('webhook.received.unimplemented', ['provider' => $provider]);

        return response()->json(['ok' => true]);
    })->name($provider);
}
