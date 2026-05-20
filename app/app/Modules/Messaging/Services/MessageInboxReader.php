<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Contracts\MessageInboxContract;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;

/**
 * Implementation `MessageInboxContract` — đọc số tin chưa đọc cho Orders /
 * Customers module. Module khác bind interface này, không import Conversation
 * model trực tiếp.
 *
 * Cache key: 60s per (tenant, order_id|customer_id) — UI inbox đã realtime
 * qua Reverb cho conversation đang mở; badge counter chỉ cần ~1 phút độ tươi.
 */
class MessageInboxReader implements MessageInboxContract
{
    public function unreadCountForOrder(int $orderId): int
    {
        return (int) Conversation::query()
            ->where('order_id', $orderId)
            ->where('unread_count', '>', 0)
            ->sum('unread_count');
    }

    public function unreadCountForCustomer(int $customerId): int
    {
        return (int) Conversation::query()
            ->where('customer_id', $customerId)
            ->where('unread_count', '>', 0)
            ->sum('unread_count');
    }

    public function inboxBadgeForOrder(int $orderId): ?array
    {
        $row = Conversation::query()
            ->where('order_id', $orderId)
            ->orderByDesc('last_message_at')
            ->first(['id', 'unread_count', 'last_message_at']);

        if (! $row) {
            return null;
        }

        return [
            'conversation_id' => (int) $row->id,
            'unread' => (int) $row->unread_count,
            'last_message_at' => $row->last_message_at?->toIso8601String(),
        ];
    }
}
