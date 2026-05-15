<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Hoá đơn nhà cung cấp (AP). Phase 7.3 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int|null $supplier_id
 * @property int|null $purchase_order_id
 * @property int|null $goods_receipt_id
 * @property string|null $bill_no
 * @property Carbon $bill_date
 * @property Carbon|null $due_date
 * @property int $subtotal
 * @property int $tax
 * @property int $total
 * @property string $status
 * @property string|null $memo
 * @property int|null $journal_entry_id
 * @property int|null $created_by
 * @property Carbon|null $recorded_at
 * @property int|null $recorded_by
 */
class VendorBill extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_RECORDED = 'recorded';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'tenant_id', 'code', 'supplier_id', 'purchase_order_id', 'goods_receipt_id',
        'bill_no', 'bill_date', 'due_date', 'subtotal', 'tax', 'total',
        'status', 'memo', 'journal_entry_id', 'created_by', 'recorded_at', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'datetime',
            'due_date' => 'datetime',
            'recorded_at' => 'datetime',
            'subtotal' => 'integer', 'tax' => 'integer', 'total' => 'integer',
            'supplier_id' => 'integer', 'purchase_order_id' => 'integer', 'goods_receipt_id' => 'integer',
            'journal_entry_id' => 'integer', 'created_by' => 'integer', 'recorded_by' => 'integer',
        ];
    }
}
