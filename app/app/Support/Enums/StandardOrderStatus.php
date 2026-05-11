<?php

namespace CMBcoreSeller\Support\Enums;

/**
 * Canonical (standard) order status used across the whole system.
 *
 * Every channel maps its own raw statuses onto these via its connector's
 * status map. See docs/03-domain/order-status-state-machine.md.
 */
enum StandardOrderStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Processing = 'processing';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case DeliveryFailed = 'delivery_failed';
    case Returning = 'returning';
    case ReturnedRefunded = 'returned_refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Chờ thanh toán',
            self::Pending => 'Chờ xử lý',
            self::Processing => 'Đang xử lý',
            self::ReadyToShip => 'Chờ bàn giao',
            self::Shipped => 'Đang vận chuyển',
            self::Delivered => 'Đã giao',
            self::Completed => 'Hoàn tất',
            self::DeliveryFailed => 'Giao thất bại',
            self::Returning => 'Đang trả/hoàn',
            self::ReturnedRefunded => 'Đã trả/hoàn',
            self::Cancelled => 'Đã huỷ',
        };
    }

    /** True if no goods have left the warehouse yet for this status. */
    public function isPreShipment(): bool
    {
        return in_array($this, [self::Unpaid, self::Pending, self::Processing, self::ReadyToShip], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::ReturnedRefunded], true);
    }
}
