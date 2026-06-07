<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\UtilityTemplate;
use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SPEC 0031 (+ 0032) — gửi 1 tin xác nhận đơn (kèm link tra cứu SPEC 0030) cho
 * khách trong hội thoại đơn vừa được tạo.
 *
 * Best-effort: mọi lỗi log, không ném — không được làm hỏng link-order. Capability-
 * gated, KHÔNG branch theo tên sàn (extensibility-rules.md).
 *
 * SPEC 0032 — Meta đã KHAI TỬ message tag (POST_PURCHASE_UPDATE → error 1893061).
 * Tin tự động ngoài cửa sổ 24h phải đi qua **utility template đã duyệt**:
 *   1. Có template `order_confirmation` APPROVED cho Page ⇒ gửi qua template (mọi lúc).
 *   2. Không có ⇒ fallback: còn trong 24h gửi text thường (RESPONSE, KHÔNG tag);
 *      ngoài 24h ⇒ bỏ qua êm (không bao giờ gắn tag chết).
 */
class OrderConfirmationNotifier
{
    private const TEMPLATE_CODE = 'order_confirmation';

    private const FREE_WINDOW_HOURS = 24;

    private const SYSTEM_KIND = 'order_confirmation';

    private const META_SENT_KEY = 'order_confirmation_order_ids';

    public function __construct(
        private readonly MessagingRegistry $registry,
        private readonly OutboundMessageService $outbound,
        private readonly UtilityTemplateService $utilityTemplates,
    ) {}

    public function notify(Conversation $conv, Order $order): void
    {
        try {
            if (! $this->eligible($conv, $order) || $this->alreadySent($conv, $order)) {
                return;
            }

            $url = $this->trackingUrl($order);

            $message = $this->send($conv, $order, $url);
            if ($message === null) {
                return; // không gửi được (ngoài cửa sổ + chưa có template) — best-effort
            }

            // queue* chỉ lưu meta whitelist cố định — đóng dấu audit marker sau.
            $message->update([
                'meta' => array_merge((array) $message->meta, [
                    'system_kind' => self::SYSTEM_KIND,
                    'order_id' => $order->getKey(),
                ]),
            ]);

            $this->markSent($conv, $order);
        } catch (Throwable $e) {
            Log::warning('SPEC0031 order-confirmation message skipped', [
                'conversation_id' => $conv->getKey(),
                'order_id' => $order->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Chọn cách gửi: ưu tiên utility template APPROVED; nếu chưa có thì fallback
     * text trong cửa sổ 24h. Trả null khi không thể gửi (ngoài cửa sổ + chưa template).
     */
    private function send(Conversation $conv, Order $order, string $url): ?Message
    {
        $body = "Xác nhận đơn đặt hàng\nBạn có thể xem trực tiếp tại ".$url;

        $template = $this->utilityTemplates->resolveApproved(
            (int) $conv->channel_account_id,
            self::TEMPLATE_CODE,
        );

        if ($template !== null) {
            [$vars, $preview] = $this->resolveTemplate($template, $order, $url, $body);

            return $this->outbound->queueUtilityTemplate($conv, (int) $template->getKey(), $vars, $preview);
        }

        // Fallback: chỉ gửi khi còn trong cửa sổ 24h (text tự do `RESPONSE`, không tag).
        if (! $this->withinFreeWindow($conv)) {
            return null;
        }

        return $this->supportsButtons($conv->provider)
            ? $this->outbound->queueInteractive($conv, [
                'text' => $body,
                'buttons' => [['type' => 'url', 'title' => 'Xem đơn hàng', 'url' => $url]],
            ])
            : $this->outbound->queueText($conv, ['body' => $body]);
    }

    /**
     * Điền biến template theo `variables` (thứ tự {{1}},{{2}}…) từ dữ liệu đơn, và
     * dựng preview (đã thay placeholder) để hiển thị trong inbox.
     *
     * @return array{0: list<string>, 1: string}
     */
    private function resolveTemplate(UtilityTemplate $template, Order $order, string $url, string $fallbackPreview): array
    {
        $data = [
            'order_number' => (string) $order->order_number,
            'tracking_url' => $url,
        ];

        $names = array_values((array) ($template->variables ?? []));
        $vars = array_map(fn ($name): string => (string) ($data[$name] ?? ''), $names);

        $preview = (string) $template->body;
        if ($preview === '') {
            $preview = $fallbackPreview;
        }
        foreach ($vars as $i => $value) {
            $preview = str_replace('{{'.($i + 1).'}}', $value, $preview);
        }

        return [$vars, $preview];
    }

    private function withinFreeWindow(Conversation $conv): bool
    {
        $lastInbound = $conv->last_inbound_at;
        if (! $lastInbound) {
            return false; // chưa có inbound ⇒ coi như ngoài cửa sổ (an toàn)
        }

        return abs(CarbonImmutable::now()->diffInHours($lastInbound)) < self::FREE_WINDOW_HOURS;
    }

    private function eligible(Conversation $conv, Order $order): bool
    {
        // DM threads only (a comment thread's id is not a PSID); trackable manual
        // orders only; and the channel must actually be able to send text.
        if ($conv->thread_type !== Conversation::THREAD_MESSAGE) {
            return false;
        }
        if ($order->source !== 'manual' || (string) $order->order_number === '') {
            return false;
        }
        if (! $this->registry->has($conv->provider)) {
            return false;
        }

        return $this->registry->for($conv->provider)->supports('outbound.text');
    }

    private function supportsButtons(string $provider): bool
    {
        if (! $this->registry->has($provider)) {
            return false;
        }
        $connector = $this->registry->for($provider);

        return $connector instanceof InteractiveMessagingConnector
            && $connector->supports('outbound.interactive');
    }

    /** @return list<int> */
    private function sentOrderIds(Conversation $conv): array
    {
        return array_map('intval', (array) data_get($conv->meta, self::META_SENT_KEY, []));
    }

    private function alreadySent(Conversation $conv, Order $order): bool
    {
        return in_array((int) $order->getKey(), $this->sentOrderIds($conv), true);
    }

    private function markSent(Conversation $conv, Order $order): void
    {
        $ids = $this->sentOrderIds($conv);
        $ids[] = (int) $order->getKey();
        $conv->update([
            'meta' => array_merge((array) $conv->meta, [
                self::META_SENT_KEY => array_values(array_unique($ids)),
            ]),
        ]);
    }

    private function trackingUrl(Order $order): string
    {
        return rtrim((string) config('app.url'), '/').'/tracking?code='.rawurlencode((string) $order->order_number);
    }
}
