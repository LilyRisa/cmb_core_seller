<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * 1 lô nhập = 1 dòng `cost_layers`. FIFO consume rút từ layer cũ nhất theo `received_at`. SPEC 0014.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $sku_id
 * @property int|null $warehouse_id
 * @property string $source_type
 * @property int|null $source_id
 * @property Carbon $received_at
 * @property int $unit_cost
 * @property int $qty_received
 * @property int $qty_remaining
 * @property Carbon|null $exhausted_at
 */
class CostLayer extends Model
{
    use BelongsToTenant;

    public const SOURCE_GOODS_RECEIPT = 'goods_receipt';

    public const SOURCE_STOCKTAKE_IN = 'stocktake_in';

    public const SOURCE_OPENING = 'opening';

    public const SOURCE_ADJUST_IN = 'adjust_in';

    protected $fillable = ['tenant_id', 'sku_id', 'warehouse_id', 'source_type', 'source_id', 'received_at', 'unit_cost', 'qty_received', 'qty_remaining', 'exhausted_at'];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'integer', 'qty_received' => 'integer', 'qty_remaining' => 'integer',
            'received_at' => 'datetime', 'exhausted_at' => 'datetime',
        ];
    }
}
