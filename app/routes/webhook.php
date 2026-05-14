<?php

use CMBcoreSeller\Modules\Billing\Http\Controllers\PaymentWebhookController;
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

/*
| Payment gateway webhooks (Phase 6.4 / SPEC 0018):
|   POST /webhook/payments/sepay   — chuyển khoản về (SePay đẩy) — PR2
|   POST /webhook/payments/vnpay   — IPN VNPay — PR3
|   POST /webhook/payments/momo    — placeholder skeleton — PR3
*/
Route::post('payments/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->whereIn('gateway', ['sepay', 'vnpay', 'momo'])
    ->name('payments');
