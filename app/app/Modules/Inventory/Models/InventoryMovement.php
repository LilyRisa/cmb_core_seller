<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Immutable stock ledger row. No soft delete. `balance_after` = on_hand of the
 * warehouse after this change. See SPEC 0003 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $sku_id
 * @property int $warehouse_id
 * @property int $qty_change
 * @property string $type
 * @property string|null $ref_type
 * @property int|null $ref_id
 * @property int $balance_after
 * @property string|null $note
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read Sku|null $sku
 */
class InventoryMovement extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    public const MANUAL_ADJUST = 'manual_adjust';

    public const GOODS_RECEIPT = 'goods_receipt';

    public const ORDER_RESERVE = 'order_reserve';

    public const ORDER_RELEASE = 'order_release';

    public const ORDER_SHIP = 'order_ship';

    public const RETURN_IN = 'return_in';

    public const TRANSFER_OUT = 'transfer_out';

    public const TRANSFER_IN = 'transfer_in';

    public const STOCKTAKE_ADJUST = 'stocktake_adjust';

    protected $fillable = [
        'tenant_id', 'sku_id', 'warehouse_id', 'qty_change', 'type',
        'ref_type', 'ref_id', 'balance_after', 'note', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return ['qty_change' => 'integer', 'ref_id' => 'integer', 'balance_after' => 'integer', 'created_at' => 'datetime'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
