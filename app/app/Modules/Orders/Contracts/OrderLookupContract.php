<?php

namespace CMBcoreSeller\Modules\Orders\Contracts;

use CMBcoreSeller\Modules\Orders\DTO\OrderSummary;

/**
 * Read-only seam để module khác (Messaging/AI) đọc đơn của 1 khách / đơn đã gắn hội thoại
 * — KHÔNG chạm Order model/Service. Bound to OrderLookupService.
 *
 * Dùng cho AI trả lời câu hỏi về đơn DỰA TRÊN liên kết hội thoại (conversation.order_id /
 * customer_id), KHÔNG tra cứu số điện thoại.
 */
interface OrderLookupContract
{
    /**
     * Đơn gần đây của 1 khách (mới nhất trước), tối đa $limit.
     *
     * @return list<OrderSummary>
     */
    public function recentByCustomer(int $tenantId, int $customerId, int $limit = 5): array;

    /** 1 đơn theo id — để resolve customer_id từ đơn đã gắn hội thoại. */
    public function find(int $tenantId, int $orderId): ?OrderSummary;
}
