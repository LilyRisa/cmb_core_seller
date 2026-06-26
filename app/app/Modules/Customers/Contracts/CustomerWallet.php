<?php

namespace CMBcoreSeller\Modules\Customers\Contracts;

use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;

/** Ví trả trước của khách. Module khác (Orders) thao tác ví QUA contract này. SPEC 2026-06-26. */
interface CustomerWallet
{
    /** Nạp ví: yêu cầu số tiền + số/mã hóa đơn ($invoiceRef). Post GL Dr tiền/Cr 131. */
    public function topup(int $tenantId, int $customerId, int $amount, string $paymentMethod, string $invoiceRef, ?string $note, ?int $userId): CustomerWalletTransaction;

    /** Trừ ví cho đơn (idempotent theo order_id). Throw RuntimeException nếu số dư không đủ. */
    public function deductForOrder(int $tenantId, int $customerId, int $orderId, int $amount, ?int $userId): CustomerWalletTransaction;

    /** Hoàn ví khi huỷ/hoàn đơn (idempotent; no-op nếu không có order_payment hoặc đã refund). */
    public function refundForOrder(int $tenantId, int $customerId, int $orderId, ?int $userId): ?CustomerWalletTransaction;
}
