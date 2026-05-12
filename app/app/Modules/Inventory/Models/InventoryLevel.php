<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Stock of one SKU in one warehouse. `available` = max(0, on_hand - reserved -
 * safety_stock); cached in `available_cached`. See docs/03-domain/inventory-and-sku-mapping.md §1.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $sku_id
 * @property int $warehouse_id
 * @property int $on_hand
 * @property int $reserved
 * @property int $safety_stock
 * @property int $available_cached
 * @property bool $is_negative
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Sku|null $sku
 * @property-read Warehouse|null $warehouse
 */
class InventoryLevel extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'sku_id', 'warehouse_id', 'on_hand', 'reserved', 'safety_stock', 'available_cached', 'is_negative'];

    protected function casts(): array
    {
        return [
            'on_hand' => 'integer', 'reserved' => 'integer', 'safety_stock' => 'integer',
            'available_cached' => 'integer', 'is_negative' => 'boolean',
        ];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function available(): int
    {
        return max(0, $this->on_hand - $this->reserved - $this->safety_stock);
    }
}
