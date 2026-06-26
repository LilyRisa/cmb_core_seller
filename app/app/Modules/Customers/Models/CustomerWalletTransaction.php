<?php

namespace CMBcoreSeller\Modules\Customers\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Sổ giao dịch ví trả trước của khách (append-only). Số dư denormalized ở customers.prepaid_balance.
 * SPEC 2026-06-26. amount: + nạp/hoàn, − trừ đơn. Idempotency unique (order_id,type).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property int|null $order_id
 * @property string $type
 * @property int $amount
 * @property int $balance_after
 * @property string|null $payment_method
 * @property string|null $invoice_ref
 * @property int|null $journal_entry_id
 * @property string|null $note
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
class CustomerWalletTransaction extends Model
{
    use BelongsToTenant;

    public const TYPE_TOPUP = 'topup';

    public const TYPE_ORDER_PAYMENT = 'order_payment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const UPDATED_AT = null; // append-only

    protected $fillable = [
        'tenant_id', 'customer_id', 'order_id', 'type', 'amount', 'balance_after',
        'payment_method', 'invoice_ref', 'journal_entry_id', 'note', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime'];
    }
}
