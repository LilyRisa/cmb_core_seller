<?php

namespace CMBcoreSeller\Integrations\Payments\DTO;

/**
 * Yêu cầu tạo phiên thanh toán cho 1 invoice. SPEC 0018 §3.2.
 *
 * `reference` BẮT BUỘC = invoice.code (vd `INV-202605-0001`). Webhook về sẽ map
 * ngược lại invoice qua reference này.
 */
final class CheckoutRequest
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $invoiceId,
        public readonly string $reference,
        public readonly int $amount,                  // VND đồng (bigint)
        public readonly string $description,
        public readonly ?string $returnUrl = null,    // VNPay-style — SPA URL
        public readonly ?string $cancelUrl = null,
    ) {}
}
