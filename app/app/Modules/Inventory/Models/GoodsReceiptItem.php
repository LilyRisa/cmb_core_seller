<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $goods_receipt_id
 * @property int $sku_id
 * @property int $qty
 * @property int $unit_cost
 * @property-read Sku|null $sku
 * @property-read GoodsReceipt|null $goodsReceipt
 */
class GoodsReceiptItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'goods_receipt_id', 'sku_id', 'qty', 'unit_cost'];

    protected function casts(): array
    {
        return ['qty' => 'integer', 'unit_cost' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }
}
