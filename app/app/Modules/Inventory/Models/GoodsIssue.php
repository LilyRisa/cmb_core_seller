<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Phiếu xuất kho (Phase 5 WMS). draft → confirmed (áp sổ cái: on_hand -= qty) → cancelled.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int $warehouse_id
 * @property string|null $reason
 * @property string|null $note
 * @property string $status
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 * @property int|null $created_by
 * @property-read Warehouse|null $warehouse
 * @property-read Collection<int, GoodsIssueItem> $items
 */
class GoodsIssue extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['tenant_id', 'code', 'warehouse_id', 'reason', 'note', 'status', 'confirmed_at', 'confirmed_by', 'created_by'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
    }
}
