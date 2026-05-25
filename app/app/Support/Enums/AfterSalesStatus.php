<?php

namespace CMBcoreSeller\Support\Enums;

/**
 * Canonical status for an after-sales record (cancel / return / refund) — separate
 * from the order's StandardOrderStatus. Every channel maps its own raw return/cancel
 * statuses onto these via its connector's return-status map. See SPEC 0025.
 */
enum AfterSalesStatus: string
{
    /** Buyer requested; waiting for seller (or platform) to approve/reject. */
    case Requested = 'requested';
    /** Seller/platform approved the request (refund/return/cancel in progress). */
    case Approved = 'approved';
    /** Seller/platform rejected the request. */
    case Rejected = 'rejected';
    /** Goods are being returned / refund processing. */
    case Processing = 'processing';
    /** Done — fully refunded / order cancelled. Terminal. */
    case Completed = 'completed';
    /** The request itself was withdrawn/cancelled (by buyer/system). Terminal. */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Chờ xử lý',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Đã từ chối',
            self::Processing => 'Đang xử lý',
            self::Completed => 'Hoàn tất',
            self::Cancelled => 'Đã huỷ yêu cầu',
        };
    }

    /** Still needs attention / not finished. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Requested, self::Approved, self::Processing], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Completed, self::Cancelled], true);
    }
}
