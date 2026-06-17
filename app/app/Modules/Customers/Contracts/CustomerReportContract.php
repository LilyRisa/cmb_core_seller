<?php

namespace CMBcoreSeller\Modules\Customers\Contracts;

/**
 * Cách DUY NHẤT module khác (Orders) hỏi trạng thái report "bom hàng" của một
 * đơn — qua interface, không chạm Services nội bộ của Customers (SPEC 0038 v2).
 */
interface CustomerReportContract
{
    /** Đơn này đã được tạo report "bom hàng" chưa? */
    public function isOrderReported(int $tenantId, int $orderId): bool;
}
