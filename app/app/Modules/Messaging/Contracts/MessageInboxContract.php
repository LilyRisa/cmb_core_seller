<?php

namespace CMBcoreSeller\Modules\Messaging\Contracts;

/**
 * Read-only contract cho module khác (Orders, Customers) đọc số tin chưa đọc
 * trên 1 order / customer — KHÔNG cho phép import ruột Messaging.
 *
 * Implementation: `CMBcoreSeller\Modules\Messaging\Services\MessageInboxReader`.
 *
 * Bind ở `MessagingServiceProvider::register()`.
 */
interface MessageInboxContract
{
    /**
     * Trả số tin chưa đọc trên conversation của 1 order (best-guess link).
     * 0 nếu không có conversation gắn.
     */
    public function unreadCountForOrder(int $orderId): int;

    /**
     * Trả số tin chưa đọc trên mọi conversation của 1 customer.
     */
    public function unreadCountForCustomer(int $customerId): int;

    /**
     * @return array{conversation_id:int, unread:int, last_message_at:?string}|null
     */
    public function inboxBadgeForOrder(int $orderId): ?array;
}
