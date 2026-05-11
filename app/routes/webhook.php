<?php

use CMBcoreSeller\Modules\Channels\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook routes — prefix /webhook, mounted from bootstrap/app.php.
|--------------------------------------------------------------------------
| No CSRF, no auth. The connector's verifier checks the signature first
| (sai chữ ký ⇒ 401, không ghi gì), then the event is stored verbatim and
| processed asynchronously. See docs/05-api/webhooks-and-oauth.md.
|
| Providers without a connector yet (shopee/lazada) get a 404 from
| WebhookIngestService — the route + handler exist; the connector is pending.
*/

foreach (['tiktok', 'shopee', 'lazada'] as $provider) {
    Route::post($provider, [WebhookController::class, 'handle'])
        ->defaults('provider', $provider)
        ->name($provider);
}
