<?php

use CMBcoreSeller\Modules\Billing\Http\Controllers\PaymentWebhookController;
use CMBcoreSeller\Modules\Channels\Http\Controllers\WebhookController;
use CMBcoreSeller\Modules\Fulfillment\Http\Controllers\CarrierWebhookController;
use CMBcoreSeller\Modules\Messaging\Http\Controllers\MessagingWebhookController;
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

/*
| Carrier webhooks (SPEC 0021): /webhook/carriers/{carrier}
|   POST /webhook/carriers/ghn   — GHN push trạng thái (Token header verify)
| Mở rộng: thêm GHTK/J&T chỉ cần connector khai báo cap `webhook` + parseWebhook;
| controller chung tự xử lý.
*/
Route::post('carriers/{carrier}', [CarrierWebhookController::class, 'handle'])
    ->whereIn('carrier', ['ghn', 'ghtk', 'jt', 'viettelpost'])
    ->name('carriers');

/*
| Messaging webhooks (SPEC-0024 — Phase 7.x đề xuất):
|   POST /webhook/messaging/{provider}  — sàn push message events (verify chữ ký ở connector)
|   GET  /webhook/messaging/facebook    — Meta hub.challenge verify (setup)
|
| Mở rộng provider: cùng controller chung, khác `MessagingConnector::verifyWebhookSignature`
| + `parseWebhook`. KHÔNG sửa controller khi thêm sàn — đúng ADR-0017.
|
| `manual` provider có ở registry để test pipeline (verify trả true trong non-prod).
*/
Route::post('messaging/{provider}', [MessagingWebhookController::class, 'handle'])
    ->whereIn('provider', ['manual', 'facebook_page', 'facebook', 'tiktok_chat', 'shopee_chat', 'lazada_chat'])
    ->name('messaging');
Route::get('messaging/facebook', [MessagingWebhookController::class, 'verify'])
    ->defaults('provider', 'facebook')
    ->name('messaging.verify');
