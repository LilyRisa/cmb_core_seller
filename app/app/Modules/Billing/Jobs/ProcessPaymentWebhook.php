<?php

namespace CMBcoreSeller\Modules\Billing\Jobs;

use CMBcoreSeller\Integrations\Payments\PaymentRegistry;
use CMBcoreSeller\Modules\Billing\Services\PaymentService;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Xử lý webhook thanh toán đã được verify + ghi vào `webhook_events`.
 *
 * Bước:
 *   1. Load `webhook_events` theo id.
 *   2. Build `Request` giả lập từ payload để gọi `connector.parseWebhook`.
 *   3. Apply notification qua `PaymentService`.
 *   4. Cập nhật `webhook_events.status` = `processed|ignored|failed`.
 *
 * Idempotent qua unique `(gateway, external_ref)` ở DB.
 *
 * Queue `webhooks` (priority cao — webhook về cần xử lý nhanh).
 */
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $webhookEventId)
    {
        $this->onQueue('webhooks');
    }

    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    public function handle(PaymentRegistry $registry, PaymentService $service): void
    {
        /** @var WebhookEvent|null $event */
        $event = WebhookEvent::query()->find($this->webhookEventId);
        if ($event === null) {
            Log::warning('payments.webhook.event_missing', ['id' => $this->webhookEventId]);
            return;
        }

        $gateway = str_replace('payments.', '', (string) $event->provider);
        if (! $registry->has($gateway)) {
            $event->forceFill(['status' => 'ignored', 'error' => "no connector for {$gateway}"])->save();
            return;
        }

        // Reconstruct Request từ raw payload đã lưu (verify đã ở controller).
        $payload = (array) ($event->payload ?? []);
        $request = Request::create('/webhook/payments/'.$gateway, 'POST', $payload);

        $connector = $registry->for($gateway);
        $notification = $connector->parseWebhook($request);
        $result = $service->applyNotification($notification);

        $event->forceFill([
            'status' => match ($result['outcome']) {
                'created', 'duplicate' => 'processed',
                'orphan' => 'ignored',
                default => 'failed',
            },
            'processed_at' => now(),
            'error' => $result['reason'] ?? null,
            'attempts' => ($event->attempts ?? 0) + 1,
        ])->save();
    }
}
