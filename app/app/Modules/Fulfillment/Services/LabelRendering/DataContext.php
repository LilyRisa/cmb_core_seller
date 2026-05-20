<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

/**
 * Snapshot dữ liệu cần render cho 1 đơn manual. Resolver build 1 lần / order;
 * field type chỉ đọc, không query DB. Tất cả key đã format sẵn để hiển thị.
 */
final class DataContext
{
    /**
     * @param  list<array{name: string, sku: ?string, qty: int}>  $items
     */
    public function __construct(
        public readonly string $order_number,
        public readonly ?string $tracking_no,
        public readonly ?string $carrier,                     // 'ghn'|'ghtk'|... raw key
        public readonly string $sender_name,
        public readonly string $sender_phone,
        public readonly string $sender_address,
        public readonly string $recipient_name,
        public readonly string $recipient_phone,
        public readonly string $recipient_address,            // detail + admin joined
        public readonly string $recipient_address_detail,
        public readonly string $recipient_address_admin,
        public readonly int $cod,
        public readonly ?int $weight_g,
        public readonly int $total_qty,
        public readonly string $print_note,
        public readonly string $created_at_fmt,
        public readonly array $items,
    ) {}
}
