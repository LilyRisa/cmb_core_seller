<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $stock_transfer_id
 * @property int $sku_id
 * @property int $qty
 * @property-read Sku|null $sku
 * @property-read StockTransfer|null $stockTransfer
 */
class StockTransferItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'stock_transfer_id', 'sku_id', 'qty'];

    protected function casts(): array
    {
        return ['qty' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }
}
