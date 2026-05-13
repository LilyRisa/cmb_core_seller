<?php

namespace CMBcoreSeller\Modules\Procurement\Models;

use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Đơn mua (Purchase Order). draft → confirmed → partially_received → received | cancelled. SPEC 0014.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string $status
 * @property string|null $expected_at
 * @property string|null $note
 * @property int $total_qty
 * @property int $total_cost
 * @property int|null $created_by
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier|null $supplier
 * @property-read Warehouse|null $warehouse
 * @property-read Collection<int, PurchaseOrderItem> $items
 * @property-read Collection<int, GoodsReceipt> $goodsReceipts
 */
class PurchaseOrder extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_RECEIVED, self::STATUS_RECEIVED, self::STATUS_CANCELLED];

    protected $fillable = [
        'tenant_id', 'code', 'supplier_id', 'warehouse_id', 'status', 'expected_at', 'note',
        'total_qty', 'total_cost', 'created_by', 'confirmed_at', 'confirmed_by', 'cancelled_at', 'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'total_qty' => 'integer', 'total_cost' => 'integer',
            'expected_at' => 'date', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'purchase_order_id');
    }

    /** Sinh mã PO kế tiếp: PO-YYYYMM-NNNN trong phạm vi tenant. */
    public static function nextCode(int $tenantId): string
    {
        $prefix = 'PO-'.now()->format('Ym').'-';
        $n = self::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('code', 'like', $prefix.'%')->count() + 1;

        return sprintf('%s%04d', $prefix, $n);
    }
}
