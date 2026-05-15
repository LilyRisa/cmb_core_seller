<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Phiếu thu (AR — SPEC 0019 Phase 7.2).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int|null $customer_id
 * @property Carbon $received_at
 * @property int $amount
 * @property int|null $cash_account_id
 * @property string $payment_method
 * @property array|null $applied_orders
 * @property string|null $memo
 * @property int|null $journal_entry_id
 * @property string $status
 * @property int|null $created_by
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 */
class CustomerReceipt extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'code', 'customer_id', 'received_at', 'amount',
        'cash_account_id', 'payment_method', 'applied_orders', 'memo',
        'journal_entry_id', 'status', 'created_by', 'confirmed_at', 'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'amount' => 'integer',
            'cash_account_id' => 'integer',
            'customer_id' => 'integer',
            'journal_entry_id' => 'integer',
            'applied_orders' => 'array',
            'created_by' => 'integer',
            'confirmed_by' => 'integer',
        ];
    }
}
