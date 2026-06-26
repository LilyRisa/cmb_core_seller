<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Contracts\CustomerAdvanceLedger;

/**
 * Tái dùng phiếu thu (advance — không applied_orders) ⇒ confirm post Dr 1111|1121 / Cr 131 (party=customer).
 * SPEC 2026-06-26.
 */
class CustomerAdvanceLedgerService implements CustomerAdvanceLedger
{
    public function __construct(private readonly CustomerReceiptService $receipts) {}

    public function recordTopup(int $tenantId, int $customerId, int $amount, string $paymentMethod, string $memo, ?int $userId): int
    {
        $method = in_array($paymentMethod, ['cash', 'bank', 'ewallet'], true) ? $paymentMethod : 'cash';
        $receipt = $this->receipts->create($tenantId, [
            'customer_id' => $customerId,
            'received_at' => now()->toIso8601String(),
            'amount' => $amount,
            'payment_method' => $method,
            'memo' => $memo,
        ], (int) ($userId ?? 0));
        $confirmed = $this->receipts->confirm($receipt, (int) ($userId ?? 0));

        return (int) $confirmed->journal_entry_id;
    }
}
