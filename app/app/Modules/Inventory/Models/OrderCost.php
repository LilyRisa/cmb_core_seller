<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Sổ COGS của đơn — bất biến. 1 row / `order_item`. SPEC 0014.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property int $order_item_id
 * @property int $sku_id
 * @property int $qty
 * @property int $cogs_unit_avg
 * @property int $cogs_total
 * @property string $cost_method
 * @property array|null $layers_used
 * @property Carbon $shipped_at
 * @property Carbon $created_at
 */
class OrderCost extends Model
{
    use BelongsToTenant;

    public $timestamps = false;   // chỉ giữ created_at (bất biến)

    protected $fillable = ['tenant_id', 'order_id', 'order_item_id', 'sku_id', 'qty', 'cogs_unit_avg', 'cogs_total', 'cost_method', 'layers_used', 'shipped_at', 'created_at'];

    protected function casts(): array
    {
        return [
            'qty' => 'integer', 'cogs_unit_avg' => 'integer', 'cogs_total' => 'integer',
            'layers_used' => 'array',
            'shipped_at' => 'datetime', 'created_at' => 'datetime',
        ];
    }
}
