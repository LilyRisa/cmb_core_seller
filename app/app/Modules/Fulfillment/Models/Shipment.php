<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One parcel for an order via a carrier. v1: 1 order = 1 active shipment. See SPEC 0006 §5,
 * docs/03-domain/fulfillment-and-printing.md §1.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property string $carrier
 * @property int|null $carrier_account_id
 * @property string|null $package_no
 * @property string|null $tracking_no
 * @property string $status
 * @property string|null $service
 * @property int|null $weight_grams
 * @property array|null $dims
 * @property int $cod_amount
 * @property int $fee
 * @property string|null $label_url
 * @property string|null $label_path
 * @property Carbon|null $picked_up_at
 * @property Carbon|null $delivered_at
 * @property array|null $raw
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order|null $order
 * @property-read CarrierAccount|null $carrierAccount
 * @property-read Collection<int, ShipmentEvent> $events
 */
class Shipment extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CREATED = 'created';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses meaning "still actionable" — used to enforce 1 active shipment per order. */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_CREATED, self::STATUS_PICKED_UP, self::STATUS_IN_TRANSIT, self::STATUS_DELIVERED, self::STATUS_FAILED, self::STATUS_RETURNED];

    protected $fillable = [
        'tenant_id', 'order_id', 'carrier', 'carrier_account_id', 'package_no', 'tracking_no', 'status', 'service',
        'weight_grams', 'dims', 'cod_amount', 'fee', 'label_url', 'label_path', 'picked_up_at', 'delivered_at', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'dims' => 'array', 'raw' => 'array', 'cod_amount' => 'integer', 'fee' => 'integer', 'weight_grams' => 'integer',
            'picked_up_at' => 'datetime', 'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function carrierAccount(): BelongsTo
    {
        return $this->belongsTo(CarrierAccount::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('occurred_at');
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', self::OPEN_STATUSES);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
