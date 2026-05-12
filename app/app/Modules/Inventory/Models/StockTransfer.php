<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Phiếu chuyển kho (Phase 5 WMS). confirm ⇒ transfer_out ở kho nguồn + transfer_in ở kho đích.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property string|null $note
 * @property string $status
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Warehouse|null $fromWarehouse
 * @property-read Warehouse|null $toWarehouse
 * @property-read Collection<int, StockTransferItem> $items
 */
class StockTransfer extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['tenant_id', 'code', 'from_warehouse_id', 'to_warehouse_id', 'note', 'status', 'confirmed_at', 'confirmed_by', 'created_by'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
