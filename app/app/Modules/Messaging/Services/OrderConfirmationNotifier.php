<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SPEC 0031 — sends a one-time order-confirmation message (with the public
 * tracking link from SPEC 0030) to the buyer in the conversation an order was
 * just created from.
 *
 * Best-effort: every failure is logged, never thrown — it must not break the
 * link-order action. Capability-gated, never branched on provider name
 * (extensibility-rules.md): a button is sent only when the connector implements
 * {@see InteractiveMessagingConnector}; otherwise plain text with the link.
 */
class OrderConfirmationNotifier
{
    /** Facebook post-purchase tag — lets the message through outside the 24h window. */
    private const TAG = 'POST_PURCHASE_UPDATE';

    private const SYSTEM_KIND = 'order_confirmation';

    private const META_SENT_KEY = 'order_confirmation_order_ids';

    public function __construct(
        private readonly MessagingRegistry $registry,
        private readonly OutboundMessageService $outbound,
    ) {}

    public function notify(Conversation $conv, Order $order): void
    {
        try {
            if (! $this->eligible($conv, $order) || $this->alreadySent($conv, $order)) {
                return;
            }

            $url = $this->trackingUrl($order);
            $body = "Xác nhận đơn đặt hàng\nBạn có thể xem trực tiếp tại ".$url;

            $message = $this->supportsButtons($conv->provider)
                ? $this->outbound->queueInteractive($conv, [
                    'text' => $body,
                    'buttons' => [['type' => 'url', 'title' => 'Xem đơn hàng', 'url' => $url]],
                ], ['message_tag' => self::TAG])
                : $this->outbound->queueText($conv, [
                    'body' => $body,
                    'message_tag' => self::TAG,
                ]);

            // queue* only persists a fixed meta whitelist — stamp the audit marker after.
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
