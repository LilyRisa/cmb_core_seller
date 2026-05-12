<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $stocktake_id
 * @property int $sku_id
 * @property int $system_qty
 * @property int $counted_qty
 * @property int $diff
 * @property-read Sku|null $sku
 * @property-read Stocktake|null $stocktake
 */
class StocktakeItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'stocktake_id', 'sku_id', 'system_qty', 'counted_qty', 'diff'];

    protected function casts(): array
    {
        return ['system_qty' => 'integer', 'counted_qty' => 'integer', 'diff' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
    }
}
