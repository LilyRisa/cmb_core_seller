<?php

namespace CMBcoreSeller\Modules\Billing\Events;

use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Phát ra khi 1 payment thành công + tổng tiền đã ≥ invoice.total.
 * Listener `ActivateSubscription` (queue `billing`) sẽ swap subscription / extend kỳ.
 *
 * SPEC 0018 §3.3.
 */
class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Payment $payment,
    ) {}
}
