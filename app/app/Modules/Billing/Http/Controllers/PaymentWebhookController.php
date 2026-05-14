<?php

namespace CMBcoreSeller\Modules\Billing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Payments\PaymentRegistry;
use CMBcoreSeller\Modules\Billing\Jobs\ProcessPaymentWebhook;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook nhận thông báo thanh toán từ gateway. SPEC 0018 §3.3.
 *
 *  POST /webhook/payments/sepay   (PR2)
 *  POST /webhook/payments/vnpay   (PR3)
 *
 * Tuân thủ `docs/05-api/webhooks-and-oauth.md`:
 *   1. Verify chữ ký ⇒ sai trả 401, KHÔNG ghi gì.
 *   2. Đúng ⇒ ghi `webhook_events` (provider=`payments.<gateway>`, status=`pending`) + dispatch
 *      job ProcessPaymentWebhook → trả 200 ngay.
 *
 * Dedupe ở job (unique `(gateway, external_ref)` trên `payments`). webhook_events partition
 * theo tháng — KHÔNG cần dedupe ở đây.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentRegistry $registry) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        if (! $this->registry->has($gateway)) {
            return response()->json(['error' => ['code' => 'UNKNOWN_GATEWAY', 'message' => 'Gateway không hỗ trợ.']], 404);
        }

        $connector = $this->registry->for($gateway);

        if (! $connector->verifyWebhookSignature($request)) {
            return response()->json(['error' => ['code' => 'WEBHOOK_SIGNATURE_INVALID', 'message' => 'Chữ ký không hợp lệ.']], 401);
        }

        // Ghi vào webhook_events (BảnG này đã có sẵn — Phase 1). Không tenant scope: payments
        // webhook không có "current tenant" — resolve qua invoice.tenant_id ở job.
        $event = WebhookEvent::query()->forceCreate([
            'tenant_id' => null,                       // resolve sau ở job
            'channel_account_id' => null,
            'provider' => 'payments.'.$gateway,
            'event_type' => 'payment.notification',
            'external_id' => (string) ($request->input('id') ?? uniqid('pay_', true)),
            'signature_ok' => true,
            'headers' => ['authorization_prefix' => substr((string) $request->header('Authorization', ''), 0, 16).'…'],
            'payload' => $request->all(),
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        ProcessPaymentWebhook::dispatch((int) $event->getKey());

        return response()->json(['data' => ['ok' => true]]);
    }
}
