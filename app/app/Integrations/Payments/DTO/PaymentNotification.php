<?php

namespace CMBcoreSeller\Integrations\Payments\DTO;

use Carbon\CarbonImmutable;

/**
 * Thông báo thanh toán đã chuẩn hoá từ webhook hoặc query. SPEC 0018 §3.3.
 *
 * `reference` = `invoice.code` (mã hoá đơn của ta gắn vào memo / metadata khi tạo phiên).
 *
 * `status`:
 *   - `succeeded` (đủ tiền + đúng reference)
 *   - `pending`   (đang chờ confirm — vd VNPay redirect chưa có IPN)
 *   - `failed`    (cổng báo fail)
 *
 * `rawPayload`: chỉ giữ metadata KHÔNG nhạy cảm (transaction_id, bank_code, amount, ...).
 * KHÔNG lưu PAN/CVV (PCI scope minimization — SPEC 0018 §8).
 */
final class PaymentNotification
{
    public function __construct(
        public readonly string $gateway,           // 'sepay' | 'vnpay' | 'momo'
        public readonly string $externalRef,       // mã giao dịch của cổng (unique theo gateway)
        public readonly string $reference,         // = invoice.code
        public readonly int $amount,
        public readonly string $status,            // 'succeeded' | 'pending' | 'failed'
        public readonly CarbonImmutable $occurredAt,
        public readonly array $rawPayload = [],
    ) {}

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
