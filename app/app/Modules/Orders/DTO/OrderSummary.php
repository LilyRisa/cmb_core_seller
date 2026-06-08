<?php

namespace CMBcoreSeller\Modules\Orders\DTO;

use CMBcoreSeller\Modules\Orders\Models\Order;

/**
 * Lát cắt MỎNG của 1 đơn ở biên module — đủ để Messaging/AI tóm tắt đơn cho khách
 * mà KHÔNG chạm Order model/Service. Trả về bởi OrderLookupContract.
 *
 * Tiền = VND nguyên (đồng). `statusCode` là mã chuẩn, `statusLabel` là nhãn tiếng Việt.
 */
final class OrderSummary
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $customerId,
        public readonly string $orderNumber,
        public readonly string $statusCode,
        public readonly string $statusLabel,
        public readonly int $grandTotal,
        public readonly ?string $placedAt,
        public readonly ?string $itemSummary,
    ) {}

    public static function fromModel(Order $o): self
    {
        $items = $o->relationLoaded('items') ? $o->items : collect();
        $summary = $items->take(4)
            ->map(fn ($it) => trim(((string) ($it->name ?? 'Sản phẩm')).' x'.(int) ($it->quantity ?? 1)))
            ->implode(', ');
        if ($items->count() > 4) {
            $summary .= ', …';
        }

        $status = $o->status; // Order cast → StandardOrderStatus (luôn là enum)

        return new self(
            id: (int) $o->getKey(),
            customerId: $o->customer_id ? (int) $o->customer_id : null,
            orderNumber: $o->order_number ?: ('#'.$o->getKey()),
            statusCode: $status->value,
            statusLabel: $status->label(),
            grandTotal: (int) $o->grand_total,
            placedAt: ($o->placed_at ?? $o->created_at)?->toIso8601String(),
            itemSummary: $summary !== '' ? $summary : null,
        );
    }

    /** @return array<string,mixed> Mảng phẳng để nhét vào ConversationSnapshot.orderContext (lớp AI không import module). */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->orderNumber,
            'status_code' => $this->statusCode,
            'status' => $this->statusLabel,
            'total' => $this->grandTotal,
            'date' => $this->placedAt,
            'items' => $this->itemSummary,
        ];
    }
}
