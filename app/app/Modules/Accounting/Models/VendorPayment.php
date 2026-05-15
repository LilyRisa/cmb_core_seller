<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Phiếu chi NCC. Phase 7.3 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int|null $supplier_id
 * @property Carbon $paid_at
 * @property int $amount
 * @property string $payment_method
 * @property int|null $cash_account_id
 * @property array|null $applied_bills
 * @property string|null $memo
 * @property int|null $journal_entry_id
 * @property string $status
 * @property int|null $created_by
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 */
class VendorPayment extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'code', 'supplier_id', 'paid_at', 'amount',
        'payment_method', 'cash_account_id', 'applied_bills', 'memo',
        'journal_entry_id', 'status', 'created_by', 'confirmed_at', 'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'amount' => 'integer',
            'supplier_id' => 'integer', 'cash_account_id' => 'integer',
            'applied_bills' => 'array',
            'journal_entry_id' => 'integer', 'created_by' => 'integer', 'confirmed_by' => 'integer',
        ];
    }
}
