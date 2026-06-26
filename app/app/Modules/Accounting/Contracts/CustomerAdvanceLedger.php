<?php

namespace CMBcoreSeller\Modules\Accounting\Contracts;

/**
 * GL cho ví trả trước của khách (advance). Module khác (Customers) post GL nạp ví QUA contract này
 * — KHÔNG gọi service nội bộ Accounting trực tiếp (module rule). SPEC 2026-06-26.
 */
interface CustomerAdvanceLedger
{
    /** Nạp ví: Dr 1111|1121 / Cr 131 (party=customer). Trả journal_entry_id. */
    public function recordTopup(int $tenantId, int $customerId, int $amount, string $paymentMethod, string $memo, ?int $userId): int;
}
