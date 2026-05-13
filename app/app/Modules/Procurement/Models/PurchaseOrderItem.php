<?php

namespace CMBcoreSeller\Modules\Procurement\Models;

use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dòng PO. `qty_received` cộng dồn theo các GoodsReceipt liên kết về PO. SPEC 0014.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $purchase_order_id
 * @property int $sku_id
 * @property int $qty_ordered
 * @property int $qty_received
 * @property int $unit_cost
 * @property string|null $note
 * @property-read Sku|null $sku
 * @property-read PurchaseOrder|null $purchaseOrder
 */
class PurchaseOrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'purchase_order_id', 'sku_id', 'qty_ordered', 'qty_received', 'unit_cost', 'note'];

    protected function casts(): array
    {
        return ['qty_ordered' => 'integer', 'qty_received' => 'integer', 'unit_cost' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function isReceived(): bool
    {
        return $this->qty_received >= $this->qty_ordered;
    }
}
