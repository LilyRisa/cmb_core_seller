<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Khi 1 order đổi canonical status → fire `order_status` rule trên các
 * conversation đã link order đó (vd `delivered` → "Cảm ơn anh/chị đã nhận hàng").
 *
 * `delay_minutes` trong trigger_config: S5 fire NGAY (đơn giản, test được). Honor
 * delay = dispatch qua delayed job — follow-up 1 dòng (TODO), không đổi engine.
 *
 * Conversation↔order link do `LinkConversationToOrder` (best-guess, S5/sau) set;
 * nếu chưa có conversation nào gắn order ⇒ no-op.
 */
class RunAutoReplyOnOrderStatus
{
    public function __construct(private AutoReplyEngine $engine) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $status = $event->to->value;

        $conversations = Conversation::withoutGlobalScope(TenantScope::class)
            ->where('order_id', $order->getKey())
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->get();

        foreach ($conversations as $conv) {
            $this->engine->fire($conv, AutoReplyRule::TRIGGER_ORDER_STATUS, [
                'order_id' => $order->getKey(),
                'order_status' => $status,
            ]);
        }
    }
}
