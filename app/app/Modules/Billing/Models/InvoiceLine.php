<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dòng hoá đơn. Không có tenant_id (tra qua invoice).
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $kind
 * @property string $description
 * @property int $quantity
 * @property int $unit_price
 * @property int $amount
 */
class InvoiceLine extends Model
{
    public const KIND_PLAN = 'plan';

    public const KIND_ADDON = 'addon';

    public const KIND_DISCOUNT = 'discount';

    protected $fillable = ['invoice_id', 'kind', 'description', 'quantity', 'unit_price', 'amount'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
