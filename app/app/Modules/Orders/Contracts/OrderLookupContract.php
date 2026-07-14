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

    /**
     * Đơn THỦ CÔNG (source='manual') đã tạo bằng SĐT này (mới nhất trước) — dùng để cảnh báo
     * SĐT trùng ở form tạo đơn thủ công (SPEC 2026-07-13 v2). Khớp theo SĐT chuẩn hoá (buyer_phone
     * hoặc shipping_address.phone), KHÔNG qua customer_id — vì đơn thủ công chỉ tạo/gắn Customer khi
     * user điền đủ "Khách hàng" (SPEC 0002 §4.2); nhiều đơn chỉ điền "Nhận hàng" nên buyer_phone rỗng.
     * Không liên quan đơn sàn (TikTok/Shopee/Lazada) — sàn không lộ SĐT thật hoặc đã có luồng riêng.
     *
     * @return list<OrderSummary>
     */
    public function recentManualByPhone(int $tenantId, string $rawPhone, int $limit = 20): array;
}
