<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\Contracts\ConversionReportingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Báo cáo sự kiện Purchase (Conversions API for Business Messaging) về Meta Ads khi 1 đơn
 * vừa được tạo TRONG khung chat Facebook Messenger với khách đến từ quảng cáo
 * Click-to-Messenger (design 2026-07-14-fb-messenger-conversion-reporting).
 *
 * Best-effort: dispatch cạnh `OrderConfirmationNotifier` trong `linkOrder()`, KHÔNG bao
 * giờ được làm hỏng luồng tạo/link đơn. Idempotent theo `order.meta['fb_conversion_reported_at']`.
 */
class ReportOrderConversionToMeta implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $conversationId, public int $orderId)
    {
        $this->onQueue('messaging-outbound');
    }

    public function uniqueId(): string
    {
        return "fb-conversion-report:{$this->orderId}";
    }

    public function uniqueFor(): int
    {
        return 600;
    }

    public function backoff(): array
    {
        return [30, 300, 900];
    }

    public function handle(MessagingRegistry $registry): void
    {
        $conversation = Conversation::withoutGlobalScope(TenantScope::class)->find($this->conversationId);
        if (! $conversation || $conversation->provider !== 'facebook_page') {
            return;
        }

        // Chỉ hội thoại đến từ quảng cáo CTM (first-touch stampAdReferral) — tránh trộn
        // với tin nhắn tự nhiên (yêu cầu gốc của tính năng).
        $adReferral = (array) ($conversation->meta['ad_referral'] ?? []);
        if ($adReferral === []) {
            return;
        }

        $order = Order::withoutGlobalScope(TenantScope::class)->find($this->orderId);
        if (! $order || ! empty($order->meta['fb_conversion_reported_at'] ?? null)) {
            return; // đơn không tồn tại hoặc đã báo cáo rồi (idempotent)
        }

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($conversation->channel_account_id);
        $fb = (array) ($meta?->settings['fb_conversions'] ?? []);
        if (! $meta || ! ($fb['enabled'] ?? false)) {
            return; // kênh chưa bật báo cáo chuyển đổi
        }

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($conversation->channel_account_id);
        if (! $account) {
            return;
        }

        $connector = $registry->has($conversation->provider) ? $registry->for($conversation->provider) : null;
        if (! $connector instanceof ConversionReportingConnector || ! $connector->supports('conversion.report')) {
            return; // phòng hờ — không nên xảy ra khi provider=facebook_page
        }

        $auth = new MessagingAuthContext(
            channelAccountId: $account->id,
            provider: $account->provider,
            externalShopId: $account->external_shop_id,
            accessToken: (string) ($account->access_token ?? ''),
        );

        try {
            $datasetId = $fb['dataset_id'] ?? null;
            if (! $datasetId) {
                $datasetId = $connector->ensureDataset($auth);
                $fb['dataset_id'] = $datasetId;
                $settings = (array) ($meta->settings ?? []);
                $settings['fb_conversions'] = $fb;
                $meta->forceFill(['settings' => $settings])->save();
            }

            $connector->reportPurchase(
                $auth,
                $datasetId,
                (string) $conversation->buyer_external_id,
                (int) $order->grand_total,
                $order->created_at,
                "order-{$order->id}",
            );

            $order->forceFill([
                'meta' => [...((array) ($order->meta ?? [])), 'fb_conversion_reported_at' => now()->toIso8601String()],
            ])->save();
        } catch (MissingScopeException) {
            $fb['last_error'] = 'missing_scope';
            $fb['last_error_at'] = now()->toIso8601String();
            $settings = (array) ($meta->settings ?? []);
            $settings['fb_conversions'] = $fb;
            $meta->forceFill(['settings' => $settings])->save();
            // Không retry — thiếu quyền không tự khỏi.
        } catch (Throwable $e) {
            Log::warning('messaging.fb_conversion_report.failed', [
                'order_id' => $order->id, 'attempt' => $this->attempts(), 'error' => $e->getMessage(),
            ]);
            if ($this->attempts() < $this->tries) {
                throw $e; // để queue retry theo backoff
            }
        }
    }
}
